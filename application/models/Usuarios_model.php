<?php

class Usuarios_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Lista de usuários com o nome do perfil e, quando pedido, filtros.
     *
     * O quarto parâmetro é opcional para não quebrar a API, que chama
     * get($perPage, $start). Filtros: pesquisa (nome, e-mail, CPF, telefone),
     * perfil (idPermissao) e situacao (0/1).
     *
     * @param array<string, mixed> $filtros
     */
    public function get($perpage = 0, $start = 0, $one = false, array $filtros = [])
    {
        $this->db->from('usuarios');
        $this->db->select('usuarios.*, permissoes.nome as permissao, permissoes.situacao as permissao_situacao');
        $this->db->join('permissoes', 'usuarios.permissoes_id = permissoes.idPermissao', 'left');
        $this->aplicarFiltros($filtros);
        $this->db->order_by('usuarios.nome', 'ASC');
        $this->db->limit($perpage, $start);

        $query = $this->db->get();

        return ! $one ? $query->result() : $query->row();
    }

    /**
     * Total de usuários com os mesmos filtros de get(), para a paginação
     * bater com o que a lista mostra (antes o total era sempre o da tabela
     * inteira e a segunda página vinha vazia quando havia busca).
     *
     * @param array<string, mixed> $filtros
     */
    public function countComFiltros(array $filtros = [])
    {
        $this->db->from('usuarios');
        $this->db->join('permissoes', 'usuarios.permissoes_id = permissoes.idPermissao', 'left');
        $this->aplicarFiltros($filtros);

        return (int) $this->db->count_all_results();
    }

    /** @param array<string, mixed> $filtros */
    private function aplicarFiltros(array $filtros)
    {
        $pesquisa = trim((string) ($filtros['pesquisa'] ?? ''));
        if ($pesquisa !== '') {
            $this->db->group_start();
            $this->db->like('usuarios.nome', $pesquisa);
            $this->db->or_like('usuarios.email', $pesquisa);
            $this->db->or_like('usuarios.cpf', $pesquisa);
            $this->db->or_like('usuarios.telefone', $pesquisa);
            $this->db->or_like('usuarios.celular', $pesquisa);
            $this->db->group_end();
        }

        $perfil = (int) ($filtros['perfil'] ?? 0);
        if ($perfil > 0) {
            $this->db->where('usuarios.permissoes_id', $perfil);
        }

        if (isset($filtros['situacao']) && $filtros['situacao'] !== '') {
            $this->db->where('usuarios.situacao', (int) $filtros['situacao']);
        }
    }

    /**
     * Marca o instante do acesso. Silencioso quando a coluna ainda não existe
     * (migration não rodada): o login não pode falhar por causa disso.
     */
    public function marcarAcesso($id)
    {
        if (! $this->db->field_exists('ultimo_acesso', 'usuarios')) {
            return false;
        }

        $this->db->where('idUsuarios', (int) $id)->update('usuarios', ['ultimo_acesso' => date('Y-m-d H:i:s')]);

        return true;
    }

    /** E-mail já usado por outra conta (o próprio cadastro não conta). */
    public function emailEmUso($email, $ignorarId = 0)
    {
        $this->db->from('usuarios');
        $this->db->where('email', trim((string) $email));
        if ($ignorarId > 0) {
            $this->db->where('idUsuarios !=', (int) $ignorarId);
        }

        return $this->db->count_all_results() > 0;
    }

    /**
     * CPF já usado por outra conta. Compara só os dígitos, porque o cadastro
     * varia entre "000.000.000-00" e "00000000000".
     */
    public function cpfEmUso($cpf, $ignorarId = 0)
    {
        $digitos = preg_replace('/\D/', '', (string) $cpf);
        if ($digitos === '') {
            return false;
        }

        $this->db->from('usuarios');
        $this->db->where("REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ", $digitos, false);
        if ($ignorarId > 0) {
            $this->db->where('idUsuarios !=', (int) $ignorarId);
        }

        return $this->db->count_all_results() > 0;
    }

    /**
     * Quantos registros de histórico apontam para o usuário. As FKs de OS,
     * vendas, lançamentos e garantias são ON DELETE NO ACTION: apagar a conta
     * falharia no banco, então a tela de usuário consulta antes e orienta a
     * desativar.
     */
    public function contarVinculos($id)
    {
        $tabelas = [
            'os' => 'usuarios_id',
            'vendas' => 'usuarios_id',
            'lancamentos' => 'usuarios_id',
            'garantias' => 'usuarios_id',
        ];

        $total = 0;
        foreach ($tabelas as $tabela => $coluna) {
            if (! $this->db->table_exists($tabela)) {
                continue;
            }
            $total += (int) $this->db->where($coluna, (int) $id)->count_all_results($tabela);
        }

        return $total;
    }

    public function getAllTipos()
    {
        $this->db->where('situacao', 1);

        return $this->db->get('tiposUsuario')->result();
    }

    public function getById($id)
    {
        $this->db->where('idUsuarios', $id);
        $this->db->limit(1);

        return $this->db->get('usuarios')->row();
    }

    public function getAll()
    {
        return $this->db->get('usuarios')->result();
    }

    public function add($table, $data)
    {
        $this->db->insert($table, $data);
        if ($this->db->affected_rows() == '1') {
            return true;
        }

        return false;
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

    public function delete($table, $fieldID, $ID)
    {
        $this->db->where($fieldID, $ID);
        $this->db->delete($table);
        if ($this->db->affected_rows() == '1') {
            return true;
        }

        return false;
    }

    public function count($table)
    {
        return $this->db->count_all($table);
    }
}
