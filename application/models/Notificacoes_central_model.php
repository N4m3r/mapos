<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Leituras da Central de Notificacoes (tela unica).
 *
 * Reune num so lugar o que a tela precisa: gatilhos por evento, modelos de
 * e-mail e de WhatsApp, historico unificado (e-mail + WhatsApp) e os totais
 * dos cartoes. Nao decide nada de envio: quem dispara continua sendo
 * Notificador / Os_email / Evolution_api.
 */
class Notificacoes_central_model extends CI_Model
{
    /** Quantidade de envios exibida por pagina no historico. */
    public const HISTORICO_POR_PAGINA = 40;

    /* ------------------------------------------------------------------ */
    /* Gatilhos                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Gatilhos cadastrados, agrupados por evento e ordenados pelo catalogo.
     *
     * @return array<string, array{evento: array, gatilhos: object[]}>
     */
    public function eventosComGatilhos()
    {
        $this->load->model('notification_triggers_model');
        $triggers = $this->notification_triggers_model->getAll();

        require_once APPPATH . 'helpers/notificacoes_helper.php';

        return notif_agrupar_por_evento($triggers);
    }

    /**
     * Eventos do catalogo que ainda nao tem nenhum gatilho cadastrado.
     *
     * @return array<string, array>
     */
    public function eventosSemGatilho()
    {
        $this->load->model('notification_triggers_model');
        $catalogo = Notification_triggers_model::eventosCatalogo();
        $cadastrados = $this->eventosComGatilhos();

        $faltando = [];
        foreach ($catalogo as $slug => $evento) {
            if (! isset($cadastrados[$slug])) {
                $faltando[$slug] = $evento;
            }
        }

        return $faltando;
    }

    /* ------------------------------------------------------------------ */
    /* Modelos                                                             */
    /* ------------------------------------------------------------------ */

    /** @return object[] */
    public function modelosEmail()
    {
        $this->load->model('email_templates_model');

        return $this->email_templates_model->getAll();
    }

    /** @return object[] */
    public function modelosWhatsapp()
    {
        $this->load->model('whatsapp_templates_model');

        return $this->whatsapp_templates_model->getAll();
    }

    /* ------------------------------------------------------------------ */
    /* Historico unificado                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * Une os logs de e-mail e de WhatsApp em uma lista unica, mais recente
     * primeiro, ja paginada.
     *
     * @param string|null $canal  'email' | 'whatsapp' | null (todos)
     * @param string|null $status 'enviado' | 'falha' | null (todos)
     * @param int         $pagina 1-based
     *
     * @return array{itens: array, total: int, pagina: int, paginas: int}
     */
    public function historico($canal = null, $status = null, $pagina = 1)
    {
        $porPagina = self::HISTORICO_POR_PAGINA;
        $pagina = max(1, (int) $pagina);
        $offset = ($pagina - 1) * $porPagina;

        $itens = [];

        // Com os dois canais juntos, cada tabela e lida do inicio ate o fim da
        // pagina atual; a lista unificada e ordenada e cortada depois. Assim a
        // pagina 2 enxerga os itens que estao antes dela nas duas tabelas.
        $limite = $canal === null ? $offset + $porPagina : $porPagina;
        $inicio = $canal === null ? 0 : $offset;

        if ($canal === null || $canal === 'email') {
            if ($this->db->table_exists('email_envios')) {
                $this->aplicarFiltroStatus('email_envios', $status);
                $rows = $this->db->select($this->colunasEmail())
                    ->order_by('data_envio', 'DESC')->order_by('id', 'DESC')
                    ->limit($limite, $inicio)
                    ->get('email_envios')->result();
                foreach ($rows as $r) {
                    $itens[] = $this->normalizarItem('email', $r);
                }
            }
        }

        if ($canal === null || $canal === 'whatsapp') {
            if ($this->db->table_exists('whatsapp_envios')) {
                $this->aplicarFiltroStatus('whatsapp_envios', $status);
                $rows = $this->db->select($this->colunasWhatsapp())
                    ->order_by('data_envio', 'DESC')->order_by('id', 'DESC')
                    ->limit($limite, $inicio)
                    ->get('whatsapp_envios')->result();
                foreach ($rows as $r) {
                    $itens[] = $this->normalizarItem('whatsapp', $r);
                }
            }
        }

        usort($itens, function ($a, $b) {
            return strcmp((string) $b['data_envio'], (string) $a['data_envio'])
                ?: ($b['id'] <=> $a['id']);
        });
        if ($canal === null) {
            $itens = array_slice($itens, $offset, $porPagina);
        }

        $total = $this->contarHistorico($canal, $status);

        return [
            'itens' => $itens,
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => max(1, (int) ceil($total / $porPagina)),
        ];
    }

    /**
     * Colunas lidas do log de e-mail. A tabela de instalacoes antigas pode nao
     * ter o vinculo com a fila (queue_id), entao ele entra so quando existe.
     */
    private function colunasEmail()
    {
        $colunas = 'id, data_envio, destino, assunto, tipo, status, erro';
        if ($this->db->field_exists('queue_id', 'email_envios')) {
            $colunas .= ', queue_id';
        }

        return $colunas;
    }

    /**
     * Colunas lidas do log de WhatsApp (evento/retorno/mensagem so quando a
     * instalacao ja tem a tabela completa).
     */
    private function colunasWhatsapp()
    {
        $colunas = 'id, data_envio, destino, tipo, status, erro';
        foreach (['os_id', 'evento', 'retorno', 'mensagem'] as $campo) {
            if ($this->db->field_exists($campo, 'whatsapp_envios')) {
                $colunas .= ', ' . $campo;
            }
        }

        return $colunas;
    }

    /**
     * @return array{enviados: int, falhas: int, pendentes: int, total: int}
     */
    public function totaisHistorico()
    {
        $totais = ['enviados' => 0, 'falhas' => 0, 'pendentes' => 0, 'total' => 0];

        foreach (['email_envios', 'whatsapp_envios'] as $tabela) {
            if (! $this->db->table_exists($tabela)) {
                continue;
            }
            $linhas = $this->db->select('status, COUNT(*) AS total')->group_by('status')->get($tabela)->result();
            foreach ($linhas as $l) {
                $qtd = (int) $l->total;
                $totais['total'] += $qtd;
                if ($l->status === 'enviado') {
                    $totais['enviados'] += $qtd;
                } elseif ($l->status === 'falha') {
                    $totais['falhas'] += $qtd;
                } else {
                    $totais['pendentes'] += $qtd;
                }
            }
        }

        return $totais;
    }

    /** Filas pendentes (e-mail aguardando disparo) para o cartao de resumo. */
    public function filaEmailPendente()
    {
        if (! $this->db->table_exists('email_queue')) {
            return 0;
        }

        return (int) $this->db->where('status', 'pending')->count_all_results('email_queue');
    }

    private function aplicarFiltroStatus($tabela, $status)
    {
        if ($status === 'enviado' || $status === 'falha') {
            $this->db->where('status', $status);
        }
    }

    private function contarHistorico($canal, $status)
    {
        $total = 0;

        if ($canal === null || $canal === 'email') {
            if ($this->db->table_exists('email_envios')) {
                $this->aplicarFiltroStatus('email_envios', $status);
                $total += (int) $this->db->count_all_results('email_envios');
            }
        }

        if ($canal === null || $canal === 'whatsapp') {
            if ($this->db->table_exists('whatsapp_envios')) {
                $this->aplicarFiltroStatus('whatsapp_envios', $status);
                $total += (int) $this->db->count_all_results('whatsapp_envios');
            }
        }

        return $total;
    }

    /**
     * Uniformiza a linha de cada tabela de log no formato usado pela view.
     */
    private function normalizarItem($canal, $r)
    {
        $whats = ($canal === 'whatsapp');

        // Nem toda instalacao tem todas as colunas (queue_id, evento, os_id...);
        // so le as que vieram na consulta.
        $campo = function ($nome, $padrao = null) use ($r) {
            return property_exists($r, $nome) ? $r->$nome : $padrao;
        };

        return [
            'canal' => $canal,
            'id' => (int) $r->id,
            'data_envio' => $r->data_envio,
            'destino' => (string) $r->destino,
            'assunto' => $whats ? null : (string) $campo('assunto', ''),
            'tipo' => (string) $campo('tipo', ''),
            'evento' => $whats ? (string) $campo('evento', '') : '',
            'os_id' => $whats ? (int) $campo('os_id', 0) : 0,
            'status' => (string) $r->status,
            'erro' => (string) $campo('erro', ''),
            'fila_id' => $whats ? 0 : (int) $campo('queue_id', 0),
        ];
    }
}
