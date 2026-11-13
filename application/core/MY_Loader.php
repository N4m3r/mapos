<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Loader do sistema: carrega o banco com o cache de colunas (Cache_colunas_db).
 *
 * O DB() do CodeIgniter só declara a classe CI_DB quando ela ainda não existe.
 * Declarando-a aqui antes, com o trait do cache, todos os drivers (mysqli,
 * pdo) passam a herdar o cache sem alterar o framework em application/vendor.
 */
class MY_Loader extends CI_Loader
{
    public function database($params = '', $return = false, $query_builder = null)
    {
        // Com query builder desligado de propósito, segue o caminho padrão do CI.
        if ($query_builder !== false && ! class_exists('CI_DB', false)) {
            require_once BASEPATH . 'database/DB_driver.php';
            require_once BASEPATH . 'database/DB_query_builder.php';
            require_once APPPATH . 'core/CI_DB_com_cache.php';
        }

        return parent::database($params, $return, $query_builder);
    }
}
