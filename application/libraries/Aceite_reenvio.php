<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Loop de reenvio do link de aceite até o cliente assinar.
 *
 * Ao finalizar, Aviso_aceite agenda a OS aqui (tabela aceite_reenvios) com o
 * intervalo e o limite do gatilho. O cron "php index.php notificacoes/reenviar_aceite"
 * (a cada 15 min) chama executar(): reenvia o que venceu e encerra o loop quando
 * o cliente aceita ou recusa, quando a OS é cancelada ou o link revogado, ou
 * quando acabam as tentativas (aí dispara "aceite_esgotado").
 */
class Aceite_reenvio
{
    /** Eventos cujo gatilho pode configurar o reenvio. */
    public const EVENTOS = ['os_aceite', 'atividade_np_finalizada'];

    public const INTERVALO_MAX_HORAS = 720;

    public const TENTATIVAS_MAX = 30;

    protected $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * Normaliza o que vem do formulário: [intervalo_horas, max_tentativas],
     * ambos null quando o reenvio fica desligado (qualquer um vazio ou <= 0).
     */
    public static function normalizarConfig($intervalo, $max)
    {
        $intervalo = (int) $intervalo;
        $max = (int) $max;
        if ($intervalo <= 0 || $max <= 0) {
            return [null, null];
        }

        return [min($intervalo, self::INTERVALO_MAX_HORAS), min($max, self::TENTATIVAS_MAX)];
    }

    /** Configuração de reenvio do gatilho, ou null se ele não reenvia. */
    public static function configDoGatilho($gatilho)
    {
        if (! $gatilho) {
            return null;
        }
        [$intervalo, $max] = self::normalizarConfig($gatilho->reenvio_intervalo_horas ?? 0, $gatilho->reenvio_max_tentativas ?? 0);

        return $intervalo ? ['intervalo_horas' => $intervalo, 'max_tentativas' => $max] : null;
    }

    public static function proximoEnvio($agora, $intervaloHoras)
    {
        return date('Y-m-d H:i:s', $agora + ((int) $intervaloHoras * 3600));
    }

    /**
     * O que fazer com um item ativo da fila.
     *
     * @param object      $item      linha de aceite_reenvios
     * @param object|null $os        OS atual (null = apagada)
     * @param string      $situacao  Aceite_model::situacao(): pendente|expirado|aceito|recusado|invalido
     * @param int         $agora     timestamp
     * @return string reenviar|aguardar|aceito|recusado|cancelado|esgotado
     */
    public static function decidir($item, $os, $situacao, $agora)
    {
        if (! $os || ($os->status ?? '') === 'Cancelado' || $situacao === 'invalido') {
            return 'cancelado';
        }
        if (in_array($situacao, ['aceito', 'recusado'], true)) {
            return $situacao;
        }
        if (! empty($item->proximo_envio) && strtotime($item->proximo_envio) > $agora) {
            return 'aguardar';
        }
        if ((int) $item->tentativas >= (int) $item->max_tentativas) {
            return 'esgotado';
        }

        return 'reenviar';
    }

    /** Põe (ou recoloca) a OS no loop; o envio inicial já foi feito. */
    public function agendar($osId, $evento, array $config, $agora = null)
    {
        $agora = $agora ?? time();
        $dados = [
            'evento' => (string) $evento,
            'intervalo_horas' => (int) $config['intervalo_horas'],
            'max_tentativas' => (int) $config['max_tentativas'],
            'tentativas' => 0,
            'situacao' => 'ativo',
            'ultimo_envio' => date('Y-m-d H:i:s', $agora),
            'proximo_envio' => self::proximoEnvio($agora, $config['intervalo_horas']),
            'encerrado_em' => null,
        ];

        return $this->gravar((int) $osId, $dados, $agora);
    }

    /** Encerra o loop da OS (aceito, recusado, cancelado ou esgotado). */
    public function encerrar($osId, $situacao, $agora = null)
    {
        $agora = $agora ?? time();
        $item = $this->itemDaOs((int) $osId);
        if (! $item || $item->situacao !== 'ativo') {
            return false;
        }
        $this->atualizar($item->id, ['situacao' => $situacao, 'encerrado_em' => date('Y-m-d H:i:s', $agora), 'proximo_envio' => null]);

        return true;
    }

    /**
     * Processa a fila. Retorna o que aconteceu com cada OS:
     * [['os' => int, 'acao' => string, 'tentativa' => int, 'enviado' => bool]].
     */
    public function executar($agora = null)
    {
        $agora = $agora ?? time();
        $resultado = [];

        foreach ($this->ativos($agora) as $item) {
            $os = $this->carregarOs((int) $item->os_id);
            $acao = self::decidir($item, $os, $os ? $this->situacaoAceite($os) : 'invalido', $agora);
            $linha = ['os' => (int) $item->os_id, 'acao' => $acao, 'tentativa' => (int) $item->tentativas, 'enviado' => false];

            if ($acao === 'aguardar') {
                continue;
            }

            if ($acao === 'reenviar') {
                $tentativa = (int) $item->tentativas + 1;
                $linha['tentativa'] = $tentativa;
                $linha['enviado'] = (bool) $this->reenviarLink($os, $tentativa);
                // Conta a tentativa mesmo sem envio (WhatsApp fora): o loop sempre termina.
                $this->atualizar($item->id, [
                    'tentativas' => $tentativa,
                    'ultimo_envio' => date('Y-m-d H:i:s', $agora),
                    'proximo_envio' => self::proximoEnvio($agora, $item->intervalo_horas),
                ]);
            } else {
                $this->atualizar($item->id, ['situacao' => $acao, 'encerrado_em' => date('Y-m-d H:i:s', $agora), 'proximo_envio' => null]);
                if ($acao === 'esgotado') {
                    $this->avisarEsgotado($os, (int) $item->tentativas);
                }
            }

            $resultado[] = $linha;
        }

        return $resultado;
    }

    /* ------------------------------------------------------------------ */
    /* Banco e envio (sobrescritos nos testes)                             */
    /* ------------------------------------------------------------------ */

    public function disponivel()
    {
        return $this->ci->db->table_exists('aceite_reenvios');
    }

    /** Itens ativos com envio vencido. */
    protected function ativos($agora)
    {
        if (! $this->disponivel()) {
            return [];
        }

        return $this->ci->db->where('situacao', 'ativo')
            ->where('proximo_envio <=', date('Y-m-d H:i:s', $agora))
            ->order_by('proximo_envio', 'ASC')
            ->get('aceite_reenvios')->result();
    }

    protected function itemDaOs($osId)
    {
        if (! $this->disponivel()) {
            return null;
        }

        return $this->ci->db->where('os_id', $osId)->limit(1)->get('aceite_reenvios')->row();
    }

    protected function gravar($osId, array $dados, $agora)
    {
        if (! $this->disponivel()) {
            return false;
        }
        $item = $this->itemDaOs($osId);
        if ($item) {
            return $this->atualizar($item->id, $dados);
        }

        return $this->ci->db->insert('aceite_reenvios', $dados + ['os_id' => $osId, 'criado_em' => date('Y-m-d H:i:s', $agora)]);
    }

    protected function atualizar($id, array $dados)
    {
        return $this->ci->db->where('id', (int) $id)->update('aceite_reenvios', $dados);
    }

    protected function carregarOs($osId)
    {
        $this->ci->load->model('os_model');

        return $this->ci->os_model->getById($osId) ?: null;
    }

    protected function situacaoAceite($os)
    {
        $this->ci->load->model('aceite_model');

        return $this->ci->aceite_model->situacao($os);
    }

    protected function reenviarLink($os, $tentativa)
    {
        $this->ci->load->library('aviso_aceite');

        return $this->ci->aviso_aceite->reenviar($os, $tentativa);
    }

    protected function avisarEsgotado($os, $tentativas)
    {
        $this->ci->load->library('aviso_aceite');
        $this->ci->aviso_aceite->avisarEsgotado($os, $tentativas);
    }
}
