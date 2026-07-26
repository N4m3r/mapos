<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Conecte_model extends CI_Model
{
    public function add($table, $data, $returnId = false)
    {
        $this->db->insert($table, $data);
        if ($this->db->affected_rows() == '1') {
            if ($returnId == true) {
                return $this->db->insert_id($table);
            }

            return true;
        }

        return false;
    }

    public function getLastOs($cliente)
    {
        $this->db->from('os');
        $this->db->join('usuarios', 'os.usuarios_id = usuarios.idUsuarios', 'left');
        $this->db->where_in('clientes_id', (array) $cliente);
        $this->db->limit(10);
        $this->db->order_by('idOs', 'desc');

        return $this->db->get()->result();
    }

    public function getLastCompras($cliente)
    {
        $this->db->select('vendas.*,usuarios.nome');
        $this->db->from('vendas');
        $this->db->join('usuarios', 'usuarios.idUsuarios = vendas.usuarios_id');
        $this->db->order_by('idVendas', 'desc');
        $this->db->where_in('clientes_id', (array) $cliente);
        $this->db->limit(10);
        $this->db->order_by('idVendas', 'desc');

        return $this->db->get()->result();
    }

    public function getCompras($table, $fields, $where, $perpage, $start, $one, $array, $cliente, $filtros = [])
    {
        $this->db->select($fields);
        $this->db->from($table);
        $this->db->join('usuarios', 'vendas.usuarios_id = usuarios.idUsuarios', 'left');
        $this->db->order_by('idVendas', 'desc');
        $this->db->where_in('vendas.clientes_id', (array) $cliente);
        $this->aplicarFiltros($filtros, 'vendas.clientes_id', 'vendas.dataVenda');
        $this->db->limit($perpage, $start);
        $this->db->order_by('idVendas', 'desc');
        if ($where) {
            $this->db->where($where);
        }

        $query = $this->db->get();

        $result = ! $one ? $query->result() : $query->row();

        return $result;
    }

    public function getCobrancas($table, $fields, $where, $perpage, $start, $one, $array, $cliente, $filtros = [])
    {
        $this->db->select($fields);
        $this->db->from($table);
        $this->db->join('clientes', 'cobrancas.clientes_id = clientes.idClientes', 'left');
        $this->db->where_in('cobrancas.clientes_id', (array) $cliente);
        $this->aplicarFiltros($filtros, 'cobrancas.clientes_id', 'cobrancas.expire_at');
        $this->db->order_by('expire_at', 'desc');
        $this->db->limit($perpage, $start);
        $this->db->order_by('idCobranca', 'desc');
        if ($where) {
            $this->db->where($where);
        }

        $query = $this->db->get();

        $result = ! $one ? $query->result() : $query->row();

        return $result;
    }

    public function getOs($table, $fields, $where, $perpage, $start, $one, $array, $cliente, $filtros = [])
    {
        $this->db->select($fields);
        $this->db->from($table);
        $this->db->join('usuarios', 'os.usuarios_id = usuarios.idUsuarios', 'left');
        $this->db->where_in('os.clientes_id', (array) $cliente);
        $this->aplicarFiltros($filtros, 'os.clientes_id', 'os.dataInicial');
        $this->db->limit($perpage, $start);
        $this->db->order_by('idOs', 'desc');
        if ($where) {
            $this->db->where($where);
        }

        $query = $this->db->get();

        $result = ! $one ? $query->result() : $query->row();

        return $result;
    }

    /**
     * Aplica os filtros do portal (CNPJ/cliente e período) ao query builder
     * atual. Usado pelas listagens e por count() para manter lista e contagem
     * da paginação coerentes. As colunas variam por módulo, por isso são
     * recebidas como parâmetro.
     *
     * @param array       $filtros    ['cliente_id', 'data_inicio', 'data_fim']
     * @param string      $clienteCol coluna do cliente (ex.: 'os.clientes_id')
     * @param string|null $dataCol    coluna de data p/ o período (null = sem)
     */
    private function aplicarFiltros($filtros, $clienteCol, $dataCol)
    {
        if (! empty($filtros['cliente_id'])) {
            $this->db->where($clienteCol, (int) $filtros['cliente_id']);
        }
        if ($dataCol !== null && ! empty($filtros['data_inicio'])) {
            $this->db->where($dataCol . ' >=', $filtros['data_inicio']);
        }
        if ($dataCol !== null && ! empty($filtros['data_fim'])) {
            $this->db->where($dataCol . ' <=', $filtros['data_fim']);
        }
    }

    /**
     * Clientes/CNPJs acessíveis (para popular o filtro do portal).
     *
     * @param int[] $ids
     */
    public function getClientesByIds($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $this->db->select('idClientes, nomeCliente, documento');
        $this->db->from('clientes');
        $this->db->where_in('idClientes', (array) $ids);
        $this->db->order_by('nomeCliente', 'asc');

        return $this->db->get()->result();
    }

    /**
     * Ids acessíveis por um login principal: ele próprio + os clientes/CNPJs
     * vinculados (tabela clientes_vinculos). Sempre inclui o master.
     *
     * @return int[]
     */
    public function getVinculados($masterId)
    {
        $ids = [(int) $masterId];
        if ($this->db->table_exists('clientes_vinculos') && ! empty($masterId)) {
            $this->db->select('cliente_id');
            $this->db->where('cliente_master_id', $masterId);
            foreach ($this->db->get('clientes_vinculos')->result() as $r) {
                $ids[] = (int) $r->cliente_id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * OS pendentes de aprovação (aprovacao_status = 'pendente') de um conjunto
     * de clientes. Retorna [] se o módulo de aprovação ainda não foi migrado.
     */
    public function getOsPendentesAprovacao($clientes)
    {
        if (! $this->db->field_exists('aprovacao_status', 'os')) {
            return [];
        }

        $this->db->select('os.*, clientes.nomeCliente, clientes.documento');
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id', 'left');
        $this->db->where_in('os.clientes_id', (array) $clientes);
        $this->db->where('os.aprovacao_status', 'pendente');
        $this->db->order_by('os.idOs', 'desc');

        return $this->db->get()->result();
    }

    public function getById($id)
    {
        $this->db->select('os.*, clientes.*, clientes.celular as celular_cliente, garantias.refGarantia, garantias.textoGarantia, usuarios.telefone as telefone_usuario, usuarios.email as email_usuario, usuarios.nome');
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os.usuarios_id');
        $this->db->join('garantias', 'garantias.idGarantias = os.garantias_id', 'left');
        $this->db->where('os.idOs', $id);
        $this->db->limit(1);

        return $this->db->get()->row();
    }

    public function count($table, $cliente, $filtros = [])
    {
        $this->db->where_in('clientes_id', (array) $cliente);
        if (! empty($filtros)) {
            // count_all_results() usa uma única tabela (sem join), então as
            // colunas não precisam de prefixo. A coluna de data varia por módulo.
            $dataCols = ['os' => 'dataInicial', 'vendas' => 'dataVenda', 'cobrancas' => 'expire_at'];
            $dataCol = isset($dataCols[$table]) ? $dataCols[$table] : null;
            $this->aplicarFiltros($filtros, 'clientes_id', $dataCol);
        }

        return $this->db->count_all_results($table);
    }

    public function getDados()
    {
        $this->db->where('idclientes', $this->session->userdata('cliente_id'));
        $this->db->limit(1);

        return $this->db->get('clientes')->row();
    }

    public function edit($table, $data, $fieldID, $ID)
    {
        $this->db->where($fieldID, $ID);
        $this->db->update($table, $data);

        if ($this->db->affected_rows() >= 0) {
            return true;
        }

        return false;
    }

    public function getQrCode($id, $pixKey, $emitente)
    {
        if (empty($id) || empty($pixKey) || empty($emitente)) {
            return;
        }

        $result = $this->valorTotalOS($id);
        $amount = $result['valor_desconto'] != 0 ? round(floatval($result['valor_desconto']), 2) : round(floatval($result['totalServico'] + $result['totalProdutos']), 2);

        if ($amount <= 0) {
            return;
        }

        $pix = (new StaticPayload())
            ->setAmount($amount)
            ->setTid($id)
            ->setDescription(sprintf('%s OS %s', substr($emitente->nome, 0, 18), $id), true)
            ->setPixKey(getPixKeyType($pixKey), $pixKey)
            ->setMerchantName($emitente->nome)
            ->setMerchantCity($emitente->cidade);

        return $pix->getQRCode();
    }
}

/* End of file conecte_model.php */
/* Location: ./application/models/conecte_model.php */
