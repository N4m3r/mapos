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
        $this->db->insert('notas_entrada', $this->filtrarColunas($data));

        return $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        $this->db->where('idEntrada', $id);

        return $this->db->update('notas_entrada', $this->filtrarColunas($data));
    }

    public function delete($id)
    {
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
        $this->db->select('idEntrada, chave, modelo, numero, serie, emitente_cnpj, emitente_nome, data_emissao, competencia, valor_total, v_bc_ibscbs, v_ibs, v_cbs, v_cred_pres_zfm, credita, observacao');

        return $this->db->get('notas_entrada')->result();
    }

    public function countEntradas($competencia = null)
    {
        if (!empty($competencia)) {
            $this->db->where('competencia', $competencia);
        }

        return $this->db->count_all_results('notas_entrada');
    }

    /** Mantém só as chaves que existem como coluna (tolerante a schema antigo). */
    private function filtrarColunas(array $data)
    {
        $cols = $this->db->list_fields('notas_entrada');

        return array_intersect_key($data, array_flip($cols));
    }

    /* -------------------------------------------------- Apuração */

    /**
     * Total de crédito das entradas de uma competência (só o que credita).
     * Retorna ['v_ibs','v_cbs','v_cred_pres_zfm','total'].
     */
    public function creditoDoPeriodo($competencia)
    {
        $this->db->select('COALESCE(SUM(v_ibs),0) AS v_ibs, COALESCE(SUM(v_cbs),0) AS v_cbs, COALESCE(SUM(v_cred_pres_zfm),0) AS v_zfm', false);
        $this->db->where('competencia', $competencia);
        $this->db->where('credita', 1);
        $row = $this->db->get('notas_entrada')->row();

        $vIbs = (float) ($row->v_ibs ?? 0);
        $vCbs = (float) ($row->v_cbs ?? 0);
        $vZfm = (float) ($row->v_zfm ?? 0);

        return [
            'v_ibs' => $vIbs,
            'v_cbs' => $vCbs,
            'v_cred_pres_zfm' => $vZfm,
            'total' => $vIbs + $vCbs + $vZfm,
        ];
    }

    /**
     * Total de débito das saídas (NF-e autorizadas) de uma competência, obtido
     * lendo o grupo IBSCBSTot do XML de cada nota. Notas anteriores à reforma
     * (sem o grupo) contribuem com zero. Retorna ['v_ibs','v_cbs','total','qtd'].
     */
    public function debitoDoPeriodo($competencia)
    {
        // competencia = 'AAAA-MM'
        [$ano, $mes] = array_pad(explode('-', (string) $competencia), 2, '');
        if (!ctype_digit((string) $ano) || !ctype_digit((string) $mes)) {
            return ['v_ibs' => 0, 'v_cbs' => 0, 'total' => 0, 'qtd' => 0];
        }

        $this->db->select('xml');
        $this->db->where('tipo', 'nfe');
        $this->db->where('status', 'autorizada');
        $this->db->where("DATE_FORMAT(data_emissao, '%Y-%m')", "{$ano}-{$mes}");
        $this->db->where('xml IS NOT NULL', null, false);
        $notas = $this->db->get('notas_fiscais')->result();

        $vIbs = 0.0;
        $vCbs = 0.0;
        foreach ($notas as $n) {
            $tot = self::extrairTotalIbsCbs($n->xml);
            $vIbs += $tot['v_ibs'];
            $vCbs += $tot['v_cbs'];
        }

        return [
            'v_ibs' => $vIbs,
            'v_cbs' => $vCbs,
            'total' => $vIbs + $vCbs,
            'qtd' => count($notas),
        ];
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

    /**
     * Extrai os campos de uma NF-e (modelo 55/65) a partir do XML de compra.
     * Namespace é removido para permitir XPath simples. Retorna null se inválido.
     */
    public static function parseNfe($xmlString)
    {
        $xmlString = trim((string) $xmlString);
        if ($xmlString === '') {
            return null;
        }
        // Remove os namespaces (default e prefixados) para XPath sem prefixo.
        $limpo = preg_replace('#\sxmlns(:\w+)?="[^"]*"#', '', $xmlString);

        libxml_use_internal_errors(true);
        $sx = simplexml_load_string($limpo);
        libxml_clear_errors();
        if ($sx === false) {
            return null;
        }

        $inf = $sx->xpath('//infNFe');
        if (empty($inf)) {
            return null; // não é uma NF-e
        }

        $get = function ($xpath) use ($sx) {
            $r = $sx->xpath($xpath);

            return !empty($r) ? trim((string) $r[0]) : null;
        };

        $chaveRaw = (string) $inf[0]['Id'];
        $chave = preg_replace('/\D/', '', $chaveRaw); // 44 dígitos

        $dh = $get('//ide/dhEmi') ?: $get('//ide/dEmi');
        $dataEmissao = null;
        $competencia = null;
        if ($dh) {
            $ts = strtotime($dh);
            if ($ts) {
                $dataEmissao = date('Y-m-d', $ts);
                $competencia = date('Y-m', $ts);
            }
        }

        return [
            'chave' => strlen($chave) === 44 ? $chave : null,
            'modelo' => $get('//ide/mod') ?: '55',
            'numero' => $get('//ide/nNF'),
            'serie' => $get('//ide/serie'),
            'emitente_cnpj' => $get('//emit/CNPJ') ?: $get('//emit/CPF'),
            'emitente_nome' => $get('//emit/xNome'),
            'data_emissao' => $dataEmissao,
            'competencia' => $competencia,
            'valor_total' => (float) ($get('//total/ICMSTot/vNF') ?: 0),
            // No totalizador IBSCBSTot os valores ficam aninhados:
            //   IBSCBSTot/vBCIBSCBS, IBSCBSTot/gIBS/vIBS, IBSCBSTot/gCBS/vCBS
            'v_bc_ibscbs' => (float) ($get('//total/IBSCBSTot/vBCIBSCBS') ?: 0),
            'v_ibs' => (float) ($get('//total/IBSCBSTot/gIBS/vIBS') ?: $get('//IBSCBSTot/gIBS/vIBS') ?: 0),
            'v_cbs' => (float) ($get('//total/IBSCBSTot/gCBS/vCBS') ?: $get('//IBSCBSTot/gCBS/vCBS') ?: 0),
            'v_cred_pres_zfm' => (float) ($get('//IBSCBSTot//vCredPresIBSZFM') ?: 0),
        ];
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
