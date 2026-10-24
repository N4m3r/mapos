<?php

/**
 * Inventario (contagem fisica) de estoque (ADR-005, F1).
 *
 * Abrir copia o estoque de todos os produtos; a contagem pode ser salva aos
 * poucos; finalizar ajusta o estoque dos itens contados para o valor contado
 * (registrando cada diferenca em estoque_movimentacoes, tipo "inventario").
 */
class Inventarios_model extends CI_Model
{
    public function listar()
    {
        return $this->db->select('i.*, u.nome AS usuario,
                (SELECT COUNT(*) FROM inventario_itens x WHERE x.inventarios_id = i.id) AS itens,
                (SELECT COUNT(*) FROM inventario_itens x WHERE x.inventarios_id = i.id AND x.contado IS NOT NULL) AS contados', false)
            ->join('usuarios u', 'u.idUsuarios = i.usuarios_id', 'left')
            ->order_by('i.id', 'DESC')
            ->get('inventarios i')->result();
    }

    public function getById($id)
    {
        return $this->db->where('id', (int) $id)->get('inventarios')->row();
    }

    public function itens($id)
    {
        return $this->db->select('it.*, p.descricao, p.codDeBarra, p.unidade, p.estoque AS estoque_atual')
            ->join('produtos p', 'p.idProdutos = it.produtos_id')
            ->where('it.inventarios_id', (int) $id)
            ->order_by('p.descricao')
            ->get('inventario_itens it')->result();
    }

    public function abrir($descricao, $observacao, $usuario)
    {
        $this->db->trans_start();
        $this->db->insert('inventarios', [
            'descricao' => $descricao,
            'observacao' => $observacao,
            'status' => 'aberto',
            'usuarios_id' => $usuario,
            'criado_em' => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $this->db->insert_id();
        $this->db->query(
            'INSERT INTO inventario_itens (inventarios_id, produtos_id, estoque_sistema)
             SELECT ?, idProdutos, estoque FROM produtos',
            [$id]
        );
        $this->db->trans_complete();

        return $this->db->trans_status() ? $id : null;
    }

    /**
     * Grava as quantidades contadas. Campo vazio = nao contado (o produto nao sera ajustado).
     *
     * @param array $contagens [produtos_id => quantidade ou '']
     */
    public function salvarContagem($id, array $contagens)
    {
        $this->load->model('produtos_model');
        $inteiro = $this->produtos_model->estoqueInteiro();
        foreach ($contagens as $produto => $valor) {
            $valor = str_replace(',', '.', trim((string) $valor));
            $contado = null;
            if ($valor !== '' && is_numeric($valor)) {
                $contado = $inteiro ? round((float) $valor) : (float) $valor;
            }
            $this->db->where('inventarios_id', (int) $id)->where('produtos_id', (int) $produto)
                ->update('inventario_itens', ['contado' => $contado]);
        }
    }

    /**
     * Ajusta o estoque dos itens contados e fecha o inventario.
     *
     * @return int quantidade de produtos com estoque alterado
     */
    public function finalizar($id)
    {
        $this->load->model('produtos_model');
        $alterados = 0;

        $this->db->trans_start();
        foreach ($this->itens($id) as $item) {
            if ($item->contado === null) {
                continue;
            }
            $ajuste = (float) $item->contado - (float) $item->estoque_atual;
            $this->db->where('id', $item->id)->update('inventario_itens', ['ajuste' => $ajuste]);
            if ($ajuste != 0) {
                $this->produtos_model->ajustarEstoque($item->produtos_id, (float) $item->contado, [
                    'tipo' => 'inventario',
                    'referencia' => $id,
                    'observacao' => 'Contagem do inventário',
                ]);
                $alterados++;
            }
        }
        $this->db->where('id', (int) $id)->update('inventarios', ['status' => 'finalizado', 'finalizado_em' => date('Y-m-d H:i:s')]);
        $this->db->trans_complete();

        return $this->db->trans_status() ? $alterados : null;
    }

    public function cancelar($id)
    {
        $this->db->where('id', (int) $id)->where('status', 'aberto')
            ->update('inventarios', ['status' => 'cancelado', 'finalizado_em' => date('Y-m-d H:i:s')]);
    }
}
