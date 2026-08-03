<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Copiar OS para outro cliente/CNPJ — MAP-OS.
 *
 * A funcionalidade de "Copiar OS" (botão de cópia na listagem de OS) reaproveita
 * o cabeçalho, produtos e serviços de uma OS de origem para uma nova OS,
 * trocando apenas o cliente/CNPJ. A numeração da nova OS é a próxima da
 * sequência (auto_increment de `os.idOs`).
 *
 * A cópia NÃO cria colunas novas: usa apenas campos já existentes em
 * `os`, `produtos_os` e `servicos_os`. A única dependência de DADOS é que o
 * status inicial da OS copiada — "Orçamento" — exista na lista de status
 * configurável (configuracoes.idConfig = 12, chave os_status_list). Em bases
 * onde essa lista foi editada e o "Orçamento" removido, a nova OS ficaria com
 * um status fora da lista. Esta migration garante, de forma idempotente, que o
 * status esteja presente.
 *
 * Idempotente: só insere "Orçamento" se ele ainda não estiver na lista.
 */
class Migration_add_os_duplicar extends CI_Migration
{
    public function up()
    {
        $row = $this->db
            ->select('valor')
            ->where('idConfig', 12)
            ->get('configuracoes')
            ->row();

        // Sem a chave os_status_list ainda: os seeds anteriores cuidam disso.
        if (! $row) {
            log_message('info', 'Migration add_os_duplicar: os_status_list ausente, nada a fazer.');

            return;
        }

        $osStatus = json_decode($row->valor, true);
        if (! is_array($osStatus) || empty($osStatus)) {
            $osStatus = ['Aberto', 'Faturado', 'Negociação', 'Em Andamento', 'Orçamento', 'Finalizado', 'Cancelado', 'Aguardando Peças', 'Aprovado'];
        }

        if (! in_array('Orçamento', $osStatus, true)) {
            $osStatus[] = 'Orçamento';
            $this->db->query('UPDATE `configuracoes` SET valor = ? WHERE idConfig = 12', [json_encode($osStatus)]);
        }

        log_message('info', 'Migration add_os_duplicar executada com sucesso');
    }

    public function down()
    {
        // Não removemos "Orçamento" da lista de status: é um status base do
        // sistema, usado muito além da cópia de OS.
    }
}
