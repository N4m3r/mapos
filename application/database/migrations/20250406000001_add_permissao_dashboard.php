<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_permissao_dashboard extends CI_Migration {

    public function up()
    {
        // Verificar se ja existe uma permissao chamada "Dashboard"
        $this->db->where('nome', 'Dashboard');
        $exists = $this->db->get('permissoes');

        if ($exists->num_rows() == 0) {
            // Criar grupo de permissao para Dashboard
            $permissoes_array = [
                'aCliente' => 0, 'eCliente' => 0, 'dCliente' => 0, 'vCliente' => 0,
                'aProduto' => 0, 'eProduto' => 0, 'dProduto' => 0, 'vProduto' => 0,
                'aServico' => 0, 'eServico' => 0, 'dServico' => 0, 'vServico' => 0,
                'aOs' => 0, 'eOs' => 0, 'dOs' => 0, 'vOs' => 0,
                'vBtnAtendimento' => 0, 'vTecnicoOS' => 0, 'eTecnicoCheckin' => 0,
                'eTecnicoCheckout' => 0, 'eTecnicoFotos' => 0,
                'aVenda' => 0, 'eVenda' => 0, 'dVenda' => 0, 'vVenda' => 0,
                'aGarantia' => 0, 'eGarantia' => 0, 'dGarantia' => 0, 'vGarantia' => 0,
                'aArquivo' => 0, 'eArquivo' => 0, 'dArquivo' => 0, 'vArquivo' => 0,
                'aPagamento' => 0, 'ePagamento' => 0, 'dPagamento' => 0, 'vPagamento' => 0,
                'aLancamento' => 0, 'eLancamento' => 0, 'dLancamento' => 0, 'vLancamento' => 0,
                'cUsuario' => 0, 'cEmitente' => 0, 'cPermissao' => 0, 'cBackup' => 0,
                'cAuditoria' => 0, 'cEmail' => 0, 'cSistema' => 0,
                'rCliente' => 0, 'rProduto' => 0, 'rServico' => 0, 'rOs' => 0,
                'rVenda' => 0, 'rFinanceiro' => 0,
                'aCobranca' => 0, 'eCobranca' => 0, 'dCobranca' => 0, 'vCobranca' => 0,
                'vDashboard' => 1, // Permissao do dashboard
                'vRelatorioCompleto' => 1,
                'vExportarDados' => 1,
            ];

            $data = [
                'nome' => 'Dashboard',
                'data' => date('Y-m-d'),
                'permissoes' => serialize($permissoes_array),
                'situacao' => 1,
            ];

            $this->db->insert('permissoes', $data);
            log_message('info', 'Grupo de permissao Dashboard criado com sucesso');
        }
    }

    public function down()
    {
        // Remover grupo de permissao Dashboard
        $this->db->where('nome', 'Dashboard');
        $this->db->delete('permissoes');
    }
}
