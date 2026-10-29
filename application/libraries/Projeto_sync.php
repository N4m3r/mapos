<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Sincroniza o projeto (obras/etapas) com as mudancas de status das OS (ADR-015).
 *
 * A decisao e pura (decidir) para ser testada sem banco; aposMudarStatusOs aplica.
 */
class Projeto_sync
{
    public const ETAPA_PENDENTE = 'pendente';
    public const ETAPA_ANDAMENTO = 'em_andamento';
    public const ETAPA_CONCLUIDA = 'concluida';
    public const OBRA_PLANEJAMENTO = 'planejamento';
    public const OBRA_EXECUCAO = 'em_execucao';
    public const PONTO_FINAL = 'testado';

    private const OS_FINAIS = ['Finalizado', 'Faturado'];

    /** @var CI_Controller */
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * Decide o que fazer, sem tocar no banco.
     *
     * @param string[] $statusOsDaEtapa status de todas as OS da etapa (inclui a atual)
     */
    public static function decidir(string $novo, array $statusOsDaEtapa, int $pontosPendentes): array
    {
        $r = ['iniciar' => false, 'concluir_etapa' => false, 'pendencia_pontos' => false];

        if ($novo === 'Em Andamento') {
            $r['iniciar'] = true;
            return $r;
        }

        if (in_array($novo, self::OS_FINAIS, true)) {
            $ativas = array_values(array_filter($statusOsDaEtapa, function ($s) {
                return $s !== 'Cancelado';
            }));
            if ($ativas === []) {
                return $r;
            }
            foreach ($ativas as $s) {
                if (! in_array($s, self::OS_FINAIS, true)) {
                    return $r;
                }
            }
            if ($pontosPendentes > 0) {
                $r['pendencia_pontos'] = true;
            } else {
                $r['concluir_etapa'] = true;
            }
        }

        return $r;
    }

    /** Reage a mudanca de status da OS; devolve o que foi feito. */
    public function aposMudarStatusOs($idOs, $anterior, $novo): array
    {
        $acoes = [];
        $db = $this->ci->db;

        if (! $db->table_exists('obra_os')) {
            return $acoes;
        }
        $vinculo = $db->where('os_id', (int) $idOs)->get('obra_os')->row();
        if (! $vinculo) {
            return $acoes;
        }

        $obraId = (int) $vinculo->obra_id;
        $etapaId = $vinculo->etapa_id ? (int) $vinculo->etapa_id : 0;
        $hoje = date('Y-m-d');

        $statusEtapa = [];
        $pendentes = 0;
        if (in_array($novo, self::OS_FINAIS, true) && $etapaId) {
            $rows = $db->select('os.status')->from('obra_os')
                ->join('os', 'os.idOs = obra_os.os_id')
                ->where('obra_os.etapa_id', $etapaId)->get()->result();
            foreach ($rows as $row) {
                $statusEtapa[] = $row->status;
            }
            if ($db->table_exists('obra_pontos')) {
                $pendentes = (int) $db->where('obra_id', $obraId)
                    ->where('status <>', self::PONTO_FINAL)->count_all_results('obra_pontos');
            }
        }

        $d = self::decidir((string) $novo, $statusEtapa, $pendentes);

        if ($d['iniciar']) {
            if ($etapaId) {
                $etapa = $db->where('idEtapa', $etapaId)->get('obra_etapas')->row();
                if ($etapa && $etapa->status === self::ETAPA_PENDENTE) {
                    $upd = ['status' => self::ETAPA_ANDAMENTO];
                    if (empty($etapa->data_inicio_real)) {
                        $upd['data_inicio_real'] = $hoje;
                    }
                    $db->where('idEtapa', $etapaId)->update('obra_etapas', $upd);
                    $acoes[] = 'etapa_iniciada';
                }
            }
            $obra = $db->where('idObra', $obraId)->get('obras')->row();
            if ($obra && $obra->status === self::OBRA_PLANEJAMENTO) {
                $db->where('idObra', $obraId)->update('obras', ['status' => self::OBRA_EXECUCAO]);
                $acoes[] = 'projeto_iniciado';
            }
        }

        if ($d['concluir_etapa']) {
            $db->where('idEtapa', $etapaId)->update('obra_etapas', [
                'status' => self::ETAPA_CONCLUIDA,
                'percentual_concluido' => 100,
                'data_fim_real' => $hoje,
            ]);
            $this->ci->load->model('obras_model');
            $this->ci->obras_model->recalcularProgresso($obraId);
            $acoes[] = 'etapa_concluida';
        }

        if ($d['pendencia_pontos']) {
            log_message('info', 'Projeto ' . $obraId . ': OS ' . $idOs . ' fechou a etapa ' . $etapaId . ' mas ha ' . $pendentes . ' ponto(s) pendente(s); etapa nao concluida');
            $acoes[] = 'pendencia_pontos';
        }

        return $acoes;
    }
}
