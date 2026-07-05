<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_permissao_btn_atendimento extends CI_Migration {

    public function up()
    {
        // Esta migration documenta a adição da permissão vBtnAtendimento
        // A permissão é armazenada como string no array serializado do campo 'permissoes' na tabela 'permissoes'
        //
        // Nova permissão: vBtnAtendimento
        // Descrição: Permite visualizar os botões de Iniciar/Finalizar Atendimento na visualização da OS
        //
        // Como usar:
        // - A permissão já está disponível automaticamente no sistema de permissões
        // - Ao editar ou criar um grupo de permissões, marque a opção "Visualizar Botões Iniciar/Finalizar Atendimento"
        //   na seção "Ordens de Serviço"
        //
        // Comportamento:
        // - Usuários com permissão 'vBtnAtendimento' OU 'eOs' podem ver os botões
        // - Técnicos com apenas 'vTecnicoOS' (sem 'eOs') só veem OS atribuídas a eles
        //   e só podem ver os botões se tiverem 'vBtnAtendimento'
        //
        // Arquivos modificados:
        // - application/controllers/Permissoes.php (adicionada no array de permissões)
        // - application/views/permissoes/adicionarPermissao.php (adicionado checkbox)
        // - application/views/permissoes/editarPermissao.php (adicionado checkbox)
        // - application/views/os/visualizarOs.php (verificação da permissão)

        log_message('info', 'Migration vBtnAtendimento executada - permissão documentada');
    }

    public function down()
    {
        // Não é necessário remover nada pois a permissão é apenas uma string no array serializado
        // Para "remover", basta desmarcar a opção no grupo de permissões
        log_message('info', 'Rollback vBtnAtendimento - nenhuma ação necessária');
    }
}
