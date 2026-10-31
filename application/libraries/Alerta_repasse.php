<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Alerta de ponto parado (ADR-014), enviado por "php index.php lembretes/pontos_parados".
 *
 * Ponto com responsavel e "aguardando" ha mais de N horas => WhatsApp ao tecnico
 * (resumo por projeto). Passando de 2N horas, resumo ao responsavel do projeto.
 * Nao repete o alerta do mesmo ponto antes de N horas (obra_pontos.alertado_em /
 * alertado_gestor_em). Config no .env: ALERTA_REPASSE_ATIVO e ALERTA_REPASSE_HORAS.
 *
 * A logica pura e estatica (testada em tests/Unit/AlertaRepasseTest.php); os
 * pontos entram como arrays com: idPonto, obra_id, obra_nome, obra_status,
 * obra_responsavel_id, identificacao, responsavel_id, aguardando, status,
 * repassado_em, atualizado_em, alertado_em, alertado_gestor_em.
 */
class Alerta_repasse
{
    private const PROJETO_ENCERRADO = ['concluida', 'entregue', 'cancelada'];

    // ---------- logica pura ----------

    public static function horasConfig($valor, $padrao = 24)
    {
        $h = (int) $valor;

        return $h > 0 ? $h : $padrao;
    }

    /** Timestamp desde quando o ponto espera acao (repassado_em, senao atualizado_em). */
    public static function desde(array $p)
    {
        foreach (['repassado_em', 'atualizado_em'] as $campo) {
            if (! empty($p[$campo]) && ($t = strtotime($p[$campo])) !== false) {
                return $t;
            }
        }

        return null;
    }

    private static function aberto(array $p)
    {
        return ! empty($p['aguardando'])
            && ($p['status'] ?? '') !== 'testado'
            && ! in_array($p['obra_status'] ?? '', self::PROJETO_ENCERRADO, true);
    }

    private static function foraDaJanela($ultimo, $limite)
    {
        return empty($ultimo) || (($t = strtotime($ultimo)) !== false && $t <= $limite);
    }

    /** Pontos com tecnico parados ha >= N horas e sem alerta ao tecnico dentro da janela. */
    public static function paraTecnico(array $pontos, $agora, $horas)
    {
        $limite = $agora - $horas * 3600;

        return array_values(array_filter($pontos, static function ($p) use ($limite) {
            $d = self::desde($p);

            return self::aberto($p) && ! empty($p['responsavel_id']) && $d !== null && $d <= $limite
                && self::foraDaJanela($p['alertado_em'] ?? null, $limite);
        }));
    }

    /** Pontos parados ha >= 2N horas, com projeto que tem responsavel, sem alerta ao gestor na janela. */
    public static function paraGestor(array $pontos, $agora, $horas)
    {
        $limite = $agora - 2 * $horas * 3600;
        $janela = $agora - $horas * 3600;

        return array_values(array_filter($pontos, static function ($p) use ($limite, $janela) {
            $d = self::desde($p);

            return self::aberto($p) && ! empty($p['obra_responsavel_id']) && $d !== null && $d <= $limite
                && self::foraDaJanela($p['alertado_gestor_em'] ?? null, $janela);
        }));
    }

    /** @return array<int, array<int, array>> [destinatario_id => [obra_id => pontos[]]] */
    public static function agrupar(array $pontos, $campoDestinatario)
    {
        $grupos = [];
        foreach ($pontos as $p) {
            $grupos[(int) $p[$campoDestinatario]][(int) $p['obra_id']][] = $p;
        }

        return $grupos;
    }

    private static function lista(array $pontos, $max = 8)
    {
        $ids = array_map(static fn ($p) => $p['identificacao'], $pontos);
        $txt = implode(', ', array_slice($ids, 0, $max));

        return count($ids) > $max ? $txt . ' e mais ' . (count($ids) - $max) : $txt;
    }

    private static function plural($n)
    {
        return $n === 1 ? '1 ponto parado' : "{$n} pontos parados";
    }

    /** @param array<int, array> $porObra [obra_id => pontos[]] */
    public static function mensagemTecnico($nome, array $porObra, $link, $horas)
    {
        $nome = trim(explode(' ', trim((string) $nome))[0]) ?: 'tecnico';
        $linhas = [];
        foreach ($porObra as $pontos) {
            $linhas[] = 'Você tem ' . self::plural(count($pontos)) . " no projeto {$pontos[0]['obra_nome']}: " . self::lista($pontos) . '.';
        }

        return "Olá, {$nome}! Pontos aguardando você há mais de {$horas}h.\n" . implode("\n", $linhas) . "\nAbra a sua fila: {$link}";
    }

    public static function mensagemGestor($nome, array $porObra, $horas)
    {
        $nome = trim(explode(' ', trim((string) $nome))[0]) ?: 'gestor';
        $linhas = [];
        foreach ($porObra as $pontos) {
            $linhas[] = '- ' . $pontos[0]['obra_nome'] . ': ' . self::plural(count($pontos)) . ' (' . self::lista($pontos, 5) . ')';
        }

        return "Olá, {$nome}! Pontos parados há mais de " . (2 * $horas) . "h:\n" . implode("\n", $linhas);
    }

    // ---------- orquestracao (banco + WhatsApp) ----------

    public function ativo()
    {
        return filter_var($_ENV['ALERTA_REPASSE_ATIVO'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public function horas()
    {
        return self::horasConfig($_ENV['ALERTA_REPASSE_HORAS'] ?? 24);
    }

    public function disponivel()
    {
        $ci = &get_instance();

        return $ci->db->table_exists('obra_pontos') && $ci->db->field_exists('alertado_em', 'obra_pontos');
    }

    /**
     * @return array<int, array{tipo: string, usuario: int, nome: string, pontos: int, enviado: bool, erro: ?string, mensagem: string}>
     */
    public function executar($simular = false, $agora = null)
    {
        $ci = &get_instance();
        $agora = $agora ?? time();
        $horas = $this->horas();

        $pontos = $ci->db->select('p.idPonto, p.obra_id, p.identificacao, p.status, p.aguardando, p.responsavel_id, p.repassado_em, p.atualizado_em, p.alertado_em, p.alertado_gestor_em, o.nome as obra_nome, o.status as obra_status, o.responsavel_id as obra_responsavel_id')
            ->from('obra_pontos p')
            ->join('obras o', 'o.idObra = p.obra_id')
            ->where('p.aguardando IS NOT NULL', null, false)
            ->where('p.aguardando !=', '')
            ->where('p.status !=', 'testado')
            ->where_not_in('o.status', self::PROJETO_ENCERRADO)
            ->get()->result_array();

        $ci->load->library('evolution_api');
        $ativo = $ci->evolution_api->estaAtivo();
        $link = site_url('tecnico/fila');
        $resultado = [];

        $envios = [];
        foreach (self::agrupar(self::paraTecnico($pontos, $agora, $horas), 'responsavel_id') as $uid => $porObra) {
            $envios[] = ['tecnico', $uid, $porObra, 'alertado_em'];
        }
        foreach (self::agrupar(self::paraGestor($pontos, $agora, $horas), 'obra_responsavel_id') as $uid => $porObra) {
            $envios[] = ['gestor', $uid, $porObra, 'alertado_gestor_em'];
        }

        foreach ($envios as [$tipo, $uid, $porObra, $coluna]) {
            $u = $ci->db->select('nome, telefone')->where('idUsuarios', $uid)->get('usuarios')->row();
            $msg = $tipo === 'tecnico'
                ? self::mensagemTecnico($u->nome ?? '', $porObra, $link, $horas)
                : self::mensagemGestor($u->nome ?? '', $porObra, $horas);
            $ids = [];
            foreach ($porObra as $lista) {
                $ids = array_merge($ids, array_column($lista, 'idPonto'));
            }
            $item = ['tipo' => $tipo, 'usuario' => $uid, 'nome' => (string) ($u->nome ?? ''), 'pontos' => count($ids), 'enviado' => false, 'erro' => null, 'mensagem' => $msg];

            if (! $simular) {
                if (! $ativo) {
                    $item['erro'] = 'WhatsApp (Evolution API) não está ativo';
                } elseif (empty($u->telefone)) {
                    $item['erro'] = 'usuário sem telefone';
                } else {
                    try {
                        $ci->evolution_api->enviarTexto($u->telefone, $msg, ['tipo' => 'alerta_repasse_' . $tipo]);
                        $item['enviado'] = true;
                        $ci->db->where_in('idPonto', $ids)->update('obra_pontos', [$coluna => date('Y-m-d H:i:s', $agora)]);
                    } catch (Throwable $e) {
                        $item['erro'] = $e->getMessage();
                    }
                }
                if ($item['erro']) {
                    log_message('error', "Alerta de ponto parado ({$tipo} #{$uid}): " . $item['erro']);
                }
            }
            $resultado[] = $item;
        }

        return $resultado;
    }
}
