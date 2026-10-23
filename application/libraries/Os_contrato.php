<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Campos de contrato, prioridade/SLA e equipe da OS montados a partir do POST.
 *
 * Extraido do controller Os (ADR-003) sem mudanca de comportamento.
 */
class Os_contrato
{
    /** @var CI_Controller */
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * Monta os campos de Contrato/SLA e Equipe para gravar na OS, com base no
     * POST do formulário e no estado atual da OS ($osAtual).
     *
     * - Recalcula os prazos de SLA (resposta/solução) quando o contrato ou a
     *   prioridade mudam, ou quando ainda não havia prazo definido.
     * - Cronometra o cumprimento: marca "respondido" ao entrar em atendimento
     *   e "solucionado" ao finalizar/faturar.
     * - Só devolve colunas que existem no banco (resiliente a migration não
     *   aplicada), para não quebrar o UPDATE.
     */
    public function camposContratoEquipe($osAtual)
    {
        $extra = [];

        // --- Contrato + prioridade + SLA -----------------------------------
        if ($this->ci->db->field_exists('contrato_id', 'os')) {
            $contrato_id = (int) $this->ci->input->post('contrato_id') ?: null;
            $prioridade = trim((string) $this->ci->input->post('prioridade')) ?: null;
            $extra['contrato_id'] = $contrato_id;
            $extra['prioridade'] = $prioridade;

            if ($this->ci->db->field_exists('sla_solucao_prazo', 'os')) {
                $mudou = ((int) ($osAtual->contrato_id ?? 0) !== (int) $contrato_id)
                    || (($osAtual->prioridade ?? null) !== $prioridade);

                if ($contrato_id && $prioridade && $this->ci->db->table_exists('contrato_sla')) {
                    if ($mudou || empty($osAtual->sla_solucao_prazo)) {
                        $this->ci->load->model('contratos_model');
                        $sla = $this->ci->contratos_model->slaPara($contrato_id, $prioridade);
                        $base = time();
                        $extra['sla_resposta_prazo'] = ($sla && $sla->resposta_horas > 0)
                            ? date('Y-m-d H:i:s', $base + ((int) $sla->resposta_horas * 3600)) : null;
                        $extra['sla_solucao_prazo'] = ($sla && $sla->solucao_horas > 0)
                            ? date('Y-m-d H:i:s', $base + ((int) $sla->solucao_horas * 3600)) : null;
                    }
                } else {
                    // Sem contrato/prioridade → zera os prazos.
                    $extra['sla_resposta_prazo'] = null;
                    $extra['sla_solucao_prazo'] = null;
                }
            }

            // Cronômetro de cumprimento pelo status.
            $extra = array_merge($extra, $this->camposCronometroSla($osAtual, $this->ci->input->post('status')));
        }

        // --- Equipe (externa/terceira) + custo -----------------------------
        if ($this->ci->db->field_exists('equipe_id', 'os')) {
            $extra['equipe_id'] = (int) $this->ci->input->post('equipe_id') ?: null;
        }
        // Só toca no custo se o campo veio no POST (o input é oculto para quem
        // não tem permissão financeira — não podemos zerar o valor existente).
        if ($this->ci->db->field_exists('custo_equipe', 'os') && $this->ci->input->post('custo_equipe') !== null) {
            $extra['custo_equipe'] = $this->parseMoeda($this->ci->input->post('custo_equipe'));
        }

        return $extra;
    }

    /**
     * Cronômetro de cumprimento do SLA pelo novo status: marca "respondido" ao
     * entrar em atendimento e "solucionado" ao finalizar/faturar (só na primeira vez).
     * Usado pela edição da OS e pela troca rápida de status.
     */
    public function camposCronometroSla($osAtual, $novoStatus)
    {
        $extra = [];
        $novoStatus = trim((string) $novoStatus);
        if ($this->ci->db->field_exists('sla_resposta_em', 'os')
            && empty($osAtual->sla_resposta_em)
            && in_array($novoStatus, ['Em Andamento', 'Finalizado', 'Faturado'], true)) {
            $extra['sla_resposta_em'] = date('Y-m-d H:i:s');
        }
        if ($this->ci->db->field_exists('sla_solucao_em', 'os')
            && empty($osAtual->sla_solucao_em)
            && in_array($novoStatus, ['Finalizado', 'Faturado'], true)) {
            $extra['sla_solucao_em'] = date('Y-m-d H:i:s');
        }

        return $extra;
    }

    /** Converte um valor monetário digitado (pt-BR ou simples) em float. */
    public function parseMoeda($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return 0.0;
        }
        // "1.500,00" (pt-BR): ponto é milhar, vírgula é decimal.
        if (strpos($raw, ',') !== false) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }
        return (float) $raw;
    }
}
