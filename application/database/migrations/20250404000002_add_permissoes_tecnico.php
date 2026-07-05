<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_permissoes_tecnico extends CI_Migration {

    public function up()
    {
        // Verificar se ja existe um grupo de permissao chamado "Tecnico"
        $this->db->where('nome', 'Tecnico');
        $exists = $this->db->get('permissoes');

        if ($exists->num_rows() == 0) {
            // Criar grupo de permissao para Tecnicos
            // Permissoes: Visualizar OS, Visualizar Produtos, Visualizar Servicos, Visualizar Clientes
            // Permissoes especificas: vTecnicoDashboard, vTecnicoOS, eTecnicoCheckin, eTecnicoCheckout, eTecnicoFotos
            $permissoes_array = [
                'aCliente' => 0,
                'eCliente' => 0,
                'dCliente' => 0,
                'vCliente' => 1,  // Visualizar clientes

                'aProduto' => 0,
                'eProduto' => 0,
                'dProduto' => 0,
                'vProduto' => 1,  // Visualizar produtos

                'aServico' => 0,
                'eServico' => 0,
                'dServico' => 0,
                'vServico' => 1,  // Visualizar servicos

                'aOs' => 0,
                'eOs' => 0,
                'dOs' => 0,
                'vOs' => 0,  // Nao acessa o padrao de OS
                'vBtnAtendimento' => 0,
                'vTecnicoOS' => 1,       // Visualizar OS na area do tecnico
                'eTecnicoCheckin' => 1,  // Fazer checkin
                'eTecnicoCheckout' => 1, // Fazer checkout
                'eTecnicoFotos' => 1,    // Adicionar fotos

                'aVenda' => 0,
                'eVenda' => 0,
                'dVenda' => 0,
                'vVenda' => 0,

                'aGarantia' => 0,
                'eGarantia' => 0,
                'dGarantia' => 0,
                'vGarantia' => 0,

                'aArquivo' => 0,
                'eArquivo' => 0,
                'dArquivo' => 0,
                'vArquivo' => 0,

                'aPagamento' => 0,
                'ePagamento' => 0,
                'dPagamento' => 0,
                'vPagamento' => 0,

                'aLancamento' => 0,
                'eLancamento' => 0,
                'dLancamento' => 0,
                'vLancamento' => 0,

                'cUsuario' => 0,
                'cEmitente' => 0,
                'cPermissao' => 0,
                'cBackup' => 0,
                'cAuditoria' => 0,
                'cEmail' => 0,
                'cSistema' => 0,

                'rCliente' => 0,
                'rProduto' => 0,
                'rServico' => 0,
                'rOs' => 0,
                'rVenda' => 0,
                'rFinanceiro' => 0,

                'aCobranca' => 0,
                'eCobranca' => 0,
                'dCobranca' => 0,
                'vCobranca' => 0,

                // Permissoes para acesso ao dashboard do tecnico
                'vTecnicoDashboard' => 1,
            ];

            $data = [
                'nome' => 'Tecnico',
                'data' => date('Y-m-d'),
                'permissoes' => serialize($permissoes_array),
                'situacao' => 1,
            ];

            $this->db->insert('permissoes', $data);
            log_message('info', 'Grupo de permissao Tecnico criado com sucesso');
        }
    }

    public function down()
    {
        // Remover grupo de permissao Tecnico
        $this->db->where('nome', 'Tecnico');
        $this->db->delete('permissoes');
    }
}
