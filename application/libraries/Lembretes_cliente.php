<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Lembretes automaticos ao cliente (ADR-005, F6), enviados por
 * "php index.php lembretes/executar" (agendar uma vez por dia).
 *
 * - visita: vespera da data agendada da OS (reenvia se a OS for reagendada)
 * - orcamento: OS em "Orçamento" sem resposta ha LEMBRETE_ORCAMENTO_DIAS dias (uma vez)
 * - retirada: OS "Finalizado" (nao faturada) ha LEMBRETE_RETIRADA_DIAS dias (uma vez)
 *
 * Canais: e-mail (fila do sistema) e WhatsApp (se a Evolution API estiver ativa).
 * Desligado por padrao: LEMBRETES_ATIVO=true no .env liga. Dias = 0 desliga o tipo.
 */
class Lembretes_cliente
{
    private const FECHADOS = ['Finalizado', 'Faturado', 'Cancelado'];

    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function ativo()
    {
        return filter_var($_ENV['LEMBRETES_ATIVO'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public function disponivel()
    {
        return $this->ci->db->table_exists('lembretes_enviados');
    }

    /**
     * @param bool $simular so lista o que seria enviado, sem enviar nem registrar
     * @return array<int, array{tipo: string, os: int, cliente: string, canais: string[], erro: ?string}>
     */
    public function executar($simular = false, $agora = null)
    {
        $agora = $agora ?? time();
        $emitente = $this->ci->db->get('emitente')->row();
        $resultado = [];

        foreach ($this->pendentes($agora) as [$tipo, $chave, $os]) {
            $mensagem = $this->mensagem($tipo, $os, $emitente);
            $item = ['tipo' => $tipo, 'os' => (int) $os->idOs, 'cliente' => (string) $os->nomeCliente, 'canais' => [], 'erro' => null, 'mensagem' => $mensagem];
            if ($simular) {
                $item['canais'] = $this->canaisPossiveis($os);
                $resultado[] = $item;
                continue;
            }

            [$item['canais'], $item['erro']] = $this->enviar($tipo, $os, $mensagem, $emitente);
            if ($item['canais']) {
                $this->ci->db->insert('lembretes_enviados', [
                    'os_id' => (int) $os->idOs,
                    'tipo' => $tipo,
                    'chave' => $chave,
                    'canais' => implode(',', $item['canais']),
                    'enviado_em' => date('Y-m-d H:i:s', $agora),
                ]);
            }
            $resultado[] = $item;
        }

        return $resultado;
    }

    /**
     * Lembretes devidos e ainda nao enviados: lista de [tipo, chave, os].
     */
    public function pendentes($agora)
    {
        $lista = [];

        if ($this->ci->db->field_exists('data_agendamento', 'os')) {
            $amanha = date('Y-m-d', strtotime('+1 day', $agora));
            foreach ($this->consultaOs()->where('DATE(o.data_agendamento)', $amanha)->where_not_in('o.status', self::FECHADOS)->get()->result() as $os) {
                $lista[] = ['visita', date('Y-m-d H:i', strtotime($os->data_agendamento)), $os];
            }
        }

        if (($dias = $this->dias('LEMBRETE_ORCAMENTO_DIAS', 3)) > 0) {
            $q = $this->consultaOs()->where('o.status', 'Orçamento')->where('o.dataInicial <=', date('Y-m-d', strtotime("-{$dias} days", $agora)));
            if ($this->ci->db->field_exists('aprovacao_status', 'os')) {
                $q->group_start()->where('o.aprovacao_status IS NULL', null, false)->or_where('o.aprovacao_status', 'pendente')->group_end();
            }
            foreach ($q->get()->result() as $os) {
                $lista[] = ['orcamento', '1', $os];
            }
        }

        if (($dias = $this->dias('LEMBRETE_RETIRADA_DIAS', 5)) > 0) {
            $q = $this->consultaOs()->where('o.status', 'Finalizado')->where('o.dataFinal <=', date('Y-m-d', strtotime("-{$dias} days", $agora)));
            foreach ($q->get()->result() as $os) {
                $lista[] = ['retirada', '1', $os];
            }
        }

        return array_values(array_filter($lista, function ($item) {
            return ! $this->jaEnviado($item[2]->idOs, $item[0], $item[1]);
        }));
    }

    public function mensagem($tipo, $os, $emitente)
    {
        $nome = trim(explode(' ', trim((string) $os->nomeCliente))[0]) ?: 'cliente';
        $empresa = $emitente->nome ?? '';
        $contato = trim((string) ($emitente->telefone ?? ''));
        $rodape = ($contato !== '' ? "\nDúvidas ou imprevistos: {$contato}." : '') . ($empresa !== '' ? "\n\n{$empresa}" : '');

        switch ($tipo) {
            case 'visita':
                $quando = strtotime($os->data_agendamento);
                $endereco = trim(implode(', ', array_filter([$os->rua, $os->numero, $os->bairro, $os->cidade])));

                return "Olá, {$nome}! Lembrete: o atendimento da OS #{$os->idOs} está agendado para amanhã, "
                    . date('d/m/Y', $quando) . ' às ' . date('H:i', $quando) . '.'
                    . ($endereco !== '' ? "\nEndereço: {$endereco}." : '') . $rodape;

            case 'orcamento':
                $link = $this->linkAprovacao($os);

                return "Olá, {$nome}! O orçamento da OS #{$os->idOs} está aguardando a sua resposta."
                    . ($link ? "\nPara aprovar ou recusar: {$link}" : "\nResponda esta mensagem para aprovar ou tirar dúvidas.") . $rodape;

            case 'retirada':
                return "Olá, {$nome}! O serviço da OS #{$os->idOs} foi concluído e o equipamento está pronto para retirada." . $rodape;
        }

        return '';
    }

    /** @return array{0: string[], 1: ?string} canais enviados e erro (se algum canal falhou) */
    private function enviar($tipo, $os, $mensagem, $emitente)
    {
        $canais = [];
        $erros = [];

        if ($this->emailValido($os->email) && $emitente && ! empty($emitente->email)) {
            $assuntos = ['visita' => 'Lembrete do seu atendimento', 'orcamento' => 'Seu orçamento aguarda resposta', 'retirada' => 'Seu equipamento está pronto'];
            $this->ci->db->insert('email_queue', [
                'to' => $os->email,
                'message' => nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8')),
                'status' => 'pending',
                'date' => date('Y-m-d H:i:s'),
                'headers' => serialize(['From' => $emitente->email, 'Subject' => $assuntos[$tipo] . ' - OS #' . $os->idOs, 'Return-Path' => '']),
            ]);
            $canais[] = 'email';
        }

        $celular = $os->celular ?: $os->telefone;
        if (! empty($celular)) {
            try {
                $this->ci->load->library('evolution_api');
                if ($this->ci->evolution_api->estaAtivo()) {
                    $this->ci->evolution_api->enviarTexto($celular, $mensagem, ['tipo' => 'lembrete_' . $tipo, 'os_id' => $os->idOs]);
                    $canais[] = 'whatsapp';
                }
            } catch (Throwable $e) {
                $erros[] = 'WhatsApp: ' . $e->getMessage();
            }
        }

        if (! $canais && ! $erros) {
            $erros[] = 'cliente sem e-mail válido nem WhatsApp disponível';
        }

        return [$canais, $erros ? implode('; ', $erros) : null];
    }

    private function canaisPossiveis($os)
    {
        $canais = [];
        if ($this->emailValido($os->email)) {
            $canais[] = 'email';
        }
        if (! empty($os->celular ?: $os->telefone)) {
            $canais[] = 'whatsapp?';
        }

        return $canais;
    }

    private function consultaOs()
    {
        return $this->ci->db->select('o.*, c.nomeCliente, c.email, c.celular, c.telefone, c.rua, c.numero, c.bairro, c.cidade')
            ->from('os o')
            ->join('clientes c', 'c.idClientes = o.clientes_id');
    }

    private function jaEnviado($osId, $tipo, $chave)
    {
        return $this->ci->db->where(['os_id' => (int) $osId, 'tipo' => $tipo, 'chave' => $chave])->count_all_results('lembretes_enviados') > 0;
    }

    private function linkAprovacao($os)
    {
        if (empty($os->aprovacao_token) || ($os->aprovacao_status ?? '') !== 'pendente') {
            return null;
        }
        if (! empty($os->aprovacao_expira) && strtotime($os->aprovacao_expira) < time()) {
            return null;
        }

        return site_url('aprovacao/' . $os->aprovacao_token);
    }

    private function dias($chave, $padrao)
    {
        $valor = $_ENV[$chave] ?? $padrao;

        return max(0, (int) $valor);
    }

    private function emailValido($email)
    {
        return filter_var(trim((string) $email), FILTER_VALIDATE_EMAIL) !== false;
    }
}
