<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/Cache_colunas_db.php';

/**
 * CI_DB do sistema: o mesmo query builder do CodeIgniter, com o cache de
 * colunas por requisição. Carregado pelo MY_Loader antes do DB() do framework.
 */
class CI_DB extends CI_DB_query_builder
{
    use Cache_colunas_db;
}
