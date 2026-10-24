<?php

/*
 * Bootstrap dos testes de unidade.
 *
 * Nao sobe o CodeIgniter: carrega o autoload do Composer e define o minimo que
 * as classes testadas esperam (BASEPATH, get_instance, log_info...). Cada teste
 * monta seu proprio CI falso em $GLOBALS['CI'].
 */

define('BASEPATH', __DIR__ . '/../application/vendor/codeigniter/framework/system/');
define('APPPATH', __DIR__ . '/../application/');
define('FCPATH', __DIR__ . '/../');

require __DIR__ . '/../application/vendor/autoload.php';

if (! function_exists('get_instance')) {
    function &get_instance()
    {
        return $GLOBALS['CI'];
    }
}

if (! function_exists('log_info')) {
    function log_info($mensagem)
    {
    }
}

require_once APPPATH . 'helpers/general_helper.php';
