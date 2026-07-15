<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Nfe_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ---------------- Configurações ---------------- */

    public function getConfig()
    {
        return $this->db->get_where('configuracoes_nfe', ['id' => 1])->row();
    }

    public function saveConfig(array $data)
    {
        $this->db->where('id', 1);

        return $this->db->update('configuracoes_nfe', $data);
    }

    /**
     * Reserva o próximo número de forma atômica (evita numeração duplicada
     * quando dois usuários emitem ao mesmo tempo).
     * $campo: 'proximo_numero_nfe' ou 'proximo_numero_dps'
     */
    public function reservarNumero($campo)
    {
        if (!in_array($campo, ['proximo_numero_nfe', 'proximo_numero_dps'])) {
            return false;
        }
        $this->db->query("UPDATE configuracoes_nfe SET {$campo} = LAST_INSERT_ID({$campo}) + 1 WHERE id = 1");

        return (int) $this->db->query('SELECT LAST_INSERT_ID() as numero')->row()->numero;
    }

    /* ---------------- Notas fiscais ---------------- */

    public function addNota(array $data)
    {
        $this->db->insert('notas_fiscais', $this->filtrarColunas($data));

        return $this->db->insert_id();
    }

    public function updateNota($idNota, array $data)
    {
        $this->db->where('idNota', $idNota);

        return $this->db->update('notas_fiscais', $this->filtrarColunas($data));
    }

    /**
     * Mantém no array apenas as chaves que existem como coluna em notas_fiscais.
     * Assim campos novos (ex.: descricao_servico, xml) não quebram a gravação em
     * instalações onde o update de schema ainda não foi aplicado.
     */
    private function filtrarColunas(array $data)
    {
        $cols = $this->db->list_fields('notas_fiscais');

        return array_intersect_key($data, array_flip($cols));
    }

    /**
     * SELECT de notas sem a coluna xml (LONGTEXT) — evita carregar XMLs
     * inteiros em listagens.
     */
    private function selectNotasSemXml()
    {
        if (! $this->db->field_exists('xml', 'notas_fiscais')) {
            return 'notas_fiscais.*';
        }

        $cols = array_diff($this->db->list_fields('notas_fiscais'), ['xml']);

        return implode(', ', array_map(function ($c) {
            return 'notas_fiscais.' . $c;
        }, $cols));
    }

    /**
     * Conteúdo do XML autorizado: preferência pelo banco; fallback no arquivo.
     *
     * @param object|null $nota
     * @return string|null
     */
    public function obterXmlConteudo($nota)
    {
        if (! $nota) {
            return null;
        }
        if (isset($nota->xml) && $nota->xml !== null && $nota->xml !== '') {
            return $nota->xml;
        }
        if (! empty($nota->xml_path) && is_file($nota->xml_path)) {
            $conteudo = @file_get_contents($nota->xml_path);

            return ($conteudo !== false && $conteudo !== '') ? $conteudo : null;
        }

        return null;
    }

    /**
     * Nome sugerido para download do XML.
     */
    public function nomeArquivoXml($nota)
    {
        if (! empty($nota->xml_path)) {
            return basename($nota->xml_path);
        }
        $tipo = (! empty($nota->tipo) && $nota->tipo === 'nfse') ? 'nfse' : 'nfe';
        $ref = ! empty($nota->chave) ? $nota->chave : (string) ($nota->numero ?? $nota->idNota);

        return $tipo . '_' . $ref . '.xml';
    }

    public function getNotaById($idNota)
    {
        return $this->db->get_where('notas_fiscais', ['idNota' => $idNota])->row();
    }

    /**
     * Última nota não-cancelada de uma venda ou OS (para bloquear emissão duplicada).
     */
    public function getNotaAtiva($tipo, $campo, $id)
    {
        return $this->db
            ->where('tipo', $tipo)
            ->where($campo, $id)
            ->where_not_in('status', ['cancelada', 'rejeitada', 'erro', 'substituida'])
            ->order_by('idNota', 'DESC')
            ->get('notas_fiscais')
            ->row();
    }

    /**
     * Última nota rejeitada/erro de uma origem (OS/Venda), para reaproveitar o
     * número ao retransmitir após corrigir os dados — evita queimar numeração.
     */
    public function getNotaReaproveitavel($tipo, $campo, $id)
    {
        return $this->db
            ->where('tipo', $tipo)
            ->where($campo, $id)
            ->where_in('status', ['rejeitada', 'erro'])
            ->order_by('idNota', 'DESC')
            ->get('notas_fiscais')
            ->row();
    }

    /**
     * Todas as notas fiscais (qualquer status) de uma OS ou Venda,
     * para a aba de consulta dentro do documento de origem.
     */
    public function getNotasByOrigem($campo, $id)
    {
        if (!in_array($campo, ['os_id', 'vendas_id'])) {
            return [];
        }

        return $this->db
            ->where($campo, $id)
            ->order_by('idNota', 'DESC')
            ->get('notas_fiscais')
            ->result();
    }

    public function getNotas($porPagina = 0, $inicio = 0, $status = null)
    {
        $this->db->select($this->selectNotasSemXml() . ', clientes.nomeCliente', false);
        $this->db->from('notas_fiscais');
        $this->db->join('vendas', 'vendas.idVendas = notas_fiscais.vendas_id', 'left');
        $this->db->join('os', 'os.idOs = notas_fiscais.os_id', 'left');
        $this->db->join('clientes', 'clientes.idClientes = COALESCE(vendas.clientes_id, os.clientes_id)', 'left', false);
        if ($status) {
            $this->db->where('notas_fiscais.status', $status);
        }
        $this->db->order_by('notas_fiscais.idNota', 'DESC');
        if ($porPagina > 0) {
            $this->db->limit($porPagina, $inicio);
        }

        return $this->db->get()->result();
    }

    public function countNotas($status = null)
    {
        if ($status) {
            $this->db->where('status', $status);
        }

        return $this->db->count_all_results('notas_fiscais');
    }

    /**
     * Notas fiscais de um conjunto de clientes (portal multi-CNPJ). A nota liga
     * ao cliente via OS ou Venda (COALESCE), como em getNotas().
     *
     * @param int[]       $clienteIds
     * @param string|null $status     ex.: 'autorizada' (null = todas)
     */
    public function getNotasByClientes(array $clienteIds, $status = 'autorizada')
    {
        if (empty($clienteIds)) {
            return [];
        }

        $this->db->select(
            $this->selectNotasSemXml() . ', clientes.nomeCliente, clientes.documento, COALESCE(vendas.clientes_id, os.clientes_id) AS clientes_id',
            false
        );
        $this->db->from('notas_fiscais');
        $this->db->join('vendas', 'vendas.idVendas = notas_fiscais.vendas_id', 'left');
        $this->db->join('os', 'os.idOs = notas_fiscais.os_id', 'left');
        $this->db->join('clientes', 'clientes.idClientes = COALESCE(vendas.clientes_id, os.clientes_id)', 'left', false);
        $this->db->where_in('COALESCE(vendas.clientes_id, os.clientes_id)', $clienteIds, false);
        if ($status) {
            $this->db->where('notas_fiscais.status', $status);
        }
        $this->db->order_by('notas_fiscais.idNota', 'DESC');

        return $this->db->get()->result();
    }

    /**
     * Uma nota com o clientes_id resolvido (via OS/Venda), para checagem de
     * posse (ownership) no portal do cliente.
     */
    public function getNotaComCliente($idNota)
    {
        $this->db->select('notas_fiscais.*, COALESCE(vendas.clientes_id, os.clientes_id) AS clientes_id', false);
        $this->db->from('notas_fiscais');
        $this->db->join('vendas', 'vendas.idVendas = notas_fiscais.vendas_id', 'left');
        $this->db->join('os', 'os.idOs = notas_fiscais.os_id', 'left');
        $this->db->where('notas_fiscais.idNota', $idNota);
        $this->db->limit(1);

        return $this->db->get()->row();
    }
}
