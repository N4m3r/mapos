<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Entradas / Créditos de IBS/CBS (Reforma Tributária - Fase 3).
 *
 * Registra as notas de compra (fornecedores) e calcula a apuração do período:
 * débito (IBS/CBS das saídas) - crédito (IBS/CBS das entradas) = saldo a recolher.
 */
class Entradas_model extends CI_Model
{
    /* -------------------------------------------------- CRUD */

    public function add(array $data)
    {
        $itens = $data['itens'] ?? [];
        unset($data['itens']);
        $this->db->insert('notas_entrada', $this->filtrarColunas($data));
        $id = $this->db->insert_id();

        if ($id && $itens && $this->db->table_exists('notas_entrada_itens')) {
            $cols = array_flip($this->db->list_fields('notas_entrada_itens'));
            foreach ($itens as $it) {
                $this->db->insert('notas_entrada_itens', array_intersect_key(['nota_entrada_id' => $id] + $it, $cols));
            }
        }

        return $id;
    }

    public function update($id, array $data)
    {
        $this->db->where('idEntrada', $id);

        return $this->db->update('notas_entrada', $this->filtrarColunas($data));
    }

    public function delete($id)
    {
        if ($this->db->table_exists('notas_entrada_itens')) {
            $this->db->delete('notas_entrada_itens', ['nota_entrada_id' => $id]);
        }
        $this->db->where('idEntrada', $id);

        return $this->db->delete('notas_entrada');
    }

    public function getById($id)
    {
        return $this->db->get_where('notas_entrada', ['idEntrada' => $id])->row();
    }

    public function existeChave($chave)
    {
        if (empty($chave)) {
            return false;
        }

        return $this->db->where('chave', $chave)->count_all_results('notas_entrada') > 0;
    }

    /** Lista as entradas, opcionalmente filtrando por competência (AAAA-MM). */
    public function getEntradas($competencia = null, $limit = null, $offset = 0)
    {
        if (!empty($competencia)) {
            $this->db->where('competencia', $competencia);
        }
        $this->db->order_by('data_emissao', 'DESC');
        $this->db->order_by('idEntrada', 'DESC');
        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }
        // Não traz o XML (LONGTEXT) na listagem.
        $this->db->select('idEntrada, chave, modelo, numero, serie, emitente_cnpj, emitente_nome, emitente_crt, data_pagamento, data_emissao, competencia, valor_total, v_bc_ibscbs, v_ibs, v_cbs, v_cred_pres_zfm, credita, observacao');

        return $this->db->get('notas_entrada')->result();
    }

    public function countEntradas($competencia = null)
    {
        if (!empty($competencia)) {
            $this->db->where('competencia', $competencia);
        }

        return $this->db->count_all_results('notas_entrada');
    }

    public function getItens($idEntrada)
    {
        if (!$this->db->table_exists('notas_entrada_itens')) {
            return [];
        }

        return $this->db->order_by('n_item')->get_where('notas_entrada_itens', ['nota_entrada_id' => $idEntrada])->result();
    }

    /** Mantém só as chaves que existem como coluna (tolerante a schema antigo). */
    private function filtrarColunas(array $data)
    {
        $cols = $this->db->list_fields('notas_entrada');

        return array_intersect_key($data, array_flip($cols));
    }

    /* -------------------------------------------------- Apuração */

    /** Define (ou limpa, com null) a data de pagamento da nota. */
    public function setDataPagamento($id, $data)
    {
        return $this->update($id, ['data_pagamento' => $data ?: null]);
    }

    /** Último dia da competência AAAA-MM. */
    private static function fimCompetencia($competencia)
    {
        return date('Y-m-t', strtotime($competencia . '-01'));
    }

    private function somaCredito($competencia, $pago)
    {
        $this->db->select('COALESCE(SUM(v_ibs),0) AS v_ibs, COALESCE(SUM(v_cbs),0) AS v_cbs, COALESCE(SUM(v_cred_pres_zfm),0) AS v_zfm, COUNT(*) AS qtd', false);
        $this->db->where('competencia', $competencia);
        $this->db->where('credita', 1);
        if ($pago) {
            // Crédito financeiro (art. 47 LC 214): só após o pagamento, até o fim da competência.
            $this->db->where('data_pagamento IS NOT NULL', null, false);
            $this->db->where('data_pagamento <=', self::fimCompetencia($competencia));
        } else {
            $this->db->group_start()->where('data_pagamento IS NULL', null, false)
                ->or_where('data_pagamento >', self::fimCompetencia($competencia))->group_end();
        }
        $row = $this->db->get('notas_entrada')->row();

        $vIbs = (float) ($row->v_ibs ?? 0);
        $vCbs = (float) ($row->v_cbs ?? 0);
        $vZfm = (float) ($row->v_zfm ?? 0);

        return [
            'v_ibs' => $vIbs,
            'v_cbs' => $vCbs,
            'v_cred_pres_zfm' => $vZfm,
            'total' => $vIbs + $vCbs + $vZfm,
            'qtd' => (int) ($row->qtd ?? 0),
        ];
    }

    /**
     * Crédito aproveitável da competência: notas com credita=1 e pagamento
     * informado até o fim do período. Retorna ['v_ibs','v_cbs','v_cred_pres_zfm','total','qtd'].
     */
    public function creditoDoPeriodo($competencia)
    {
        return $this->somaCredito($competencia, true);
    }

    /** Crédito marcado, mas pendente de pagamento (sem data ou paga depois do período). */
    public function creditoPendente($competencia)
    {
        return $this->somaCredito($competencia, false);
    }

    /**
     * Débito das saídas autorizadas (NF-e, NFC-e e NFS-e) da competência, lido do XML.
     * Retorna ['v_ibs','v_cbs','total','qtd','linhas'] (linhas = detalhe por documento).
     */
    public function debitoDoPeriodo($competencia)
    {
        $vazio = ['v_ibs' => 0, 'v_cbs' => 0, 'total' => 0, 'qtd' => 0, 'linhas' => []];
        [$ano, $mes] = array_pad(explode('-', (string) $competencia), 2, '');
        if (!ctype_digit((string) $ano) || !ctype_digit((string) $mes)) {
            return $vazio;
        }
        foreach (['apuracao_totais_xml_nfe' => 'apuracao_helper', 'danfse_dados_ibscbs' => 'danfe_helper'] as $fn => $h) {
            if (!function_exists($fn)) {
                require_once APPPATH . 'helpers/' . $h . '.php';
            }
        }

        $this->db->select('tipo, numero, data_emissao, xml');
        $this->db->where_in('tipo', ['nfe', 'nfce', 'nfse']);
        $this->db->where('status', 'autorizada'); // canceladas ficam de fora
        // Intervalo do mês (o query builder não monta bem DATE_FORMAT(...) = ?).
        $inicio = sprintf('%04d-%02d-01', $ano, $mes);
        $this->db->where('data_emissao >=', $inicio);
        $this->db->where('data_emissao <', date('Y-m-d', strtotime($inicio . ' +1 month')));
        $this->db->where('xml IS NOT NULL', null, false);
        $this->db->order_by('data_emissao');
        $notas = $this->db->get('notas_fiscais')->result();

        $nomes = ['nfe' => 'NF-e', 'nfce' => 'NFC-e', 'nfse' => 'NFS-e'];
        $linhas = [];
        foreach ($notas as $n) {
            if ($n->tipo === 'nfse') {
                $d = danfse_dados_ibscbs((string) $n->xml);
                $t = ['v_ibs_uf' => (float) ($d['vIBSUF'] ?? 0), 'v_ibs_mun' => (float) ($d['vIBSMun'] ?? 0), 'v_cbs' => (float) ($d['vCBS'] ?? 0)];
                if ($t['v_ibs_uf'] == 0.0 && $t['v_ibs_mun'] == 0.0) {
                    $t['v_ibs_uf'] = (float) ($d['vIBS'] ?? 0);
                }
            } else {
                $t = apuracao_totais_xml_nfe((string) $n->xml);
            }
            $linhas[] = ['tipo' => 'Débito', 'documento' => $nomes[$n->tipo], 'numero' => $n->numero,
                'data' => $n->data_emissao ? date('d/m/Y', strtotime($n->data_emissao)) : ''] + $t;
        }

        $vIbs = array_sum(array_map(fn ($l) => $l['v_ibs_uf'] + $l['v_ibs_mun'], $linhas));
        $vCbs = array_sum(array_column($linhas, 'v_cbs'));

        return ['v_ibs' => $vIbs, 'v_cbs' => $vCbs, 'total' => $vIbs + $vCbs, 'qtd' => count($linhas), 'linhas' => $linhas];
    }

    /** Notas de entrada com crédito da competência (pagas ou pendentes), para detalhe. UF/Município vêm do XML; ZFM em linha própria. */
    public function linhasCredito($competencia, $pago)
    {
        if (!function_exists('apuracao_credito_nota')) {
            require_once APPPATH . 'helpers/apuracao_helper.php';
        }
        $this->db->select('numero, emitente_nome, data_emissao, v_ibs, v_cbs, v_cred_pres_zfm, xml');
        $this->db->where('competencia', $competencia)->where('credita', 1);
        $fim = self::fimCompetencia($competencia);
        if ($pago) {
            $this->db->where('data_pagamento IS NOT NULL', null, false)->where('data_pagamento <=', $fim);
        } else {
            $this->db->group_start()->where('data_pagamento IS NULL', null, false)->or_where('data_pagamento >', $fim)->group_end();
        }
        $linhas = [];
        foreach ($this->db->get('notas_entrada')->result() as $r) {
            $c = apuracao_credito_nota((array) $r);
            $base = ['tipo' => $pago ? 'Crédito' : 'Crédito pendente', 'numero' => $r->numero ?? '',
                'data' => !empty($r->data_emissao) ? date('d/m/Y', strtotime($r->data_emissao)) : ''];
            $nome = (string) ($r->emitente_nome ?? 'Compra');
            $linhas[] = $base + ['documento' => $nome . ($c['origem'] === 'manual' ? ' (manual)' : ''),
                'v_ibs_uf' => $c['v_ibs_uf'], 'v_ibs_mun' => $c['v_ibs_mun'], 'v_cbs' => $c['v_cbs']];
            if ($c['v_zfm'] > 0) {
                $linhas[] = $base + ['documento' => 'Crédito presumido ZFM — ' . $nome, 'v_zfm' => $c['v_zfm']];
            }
        }

        return $linhas;
    }

    /**
     * Lista as competências que possuem entradas (para o seletor da apuração).
     */
    public function competenciasComEntrada()
    {
        $this->db->distinct();
        $this->db->select('competencia');
        $this->db->where('competencia IS NOT NULL', null, false);
        $this->db->order_by('competencia', 'DESC');

        return array_map(fn ($r) => $r->competencia, $this->db->get('notas_entrada')->result());
    }

    /* -------------------------------------------------- Parser de XML */

    /** Extrai os campos de uma NF-e (e itens, em 'itens') via entradas_xml_helper. Null se inválido. */
    public static function parseNfe($xmlString)
    {
        if (!function_exists('entradas_parse_nfe')) {
            require_once APPPATH . 'helpers/entradas_xml_helper.php';
        }
        $dados = entradas_parse_nfe((string) $xmlString);

        return $dados === [] ? null : $dados;
    }

    /** Soma vIBS + vCBS do totalizador IBSCBSTot de um XML de NF-e (ou zero). */
    public static function extrairTotalIbsCbs($xmlString)
    {
        $dados = self::parseNfe($xmlString);
        if ($dados === null) {
            return ['v_ibs' => 0.0, 'v_cbs' => 0.0];
        }

        return ['v_ibs' => (float) $dados['v_ibs'], 'v_cbs' => (float) $dados['v_cbs']];
    }
}
