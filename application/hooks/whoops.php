<?php

class WhoopsHook
{
    public function bootWhoops()
    {
        // A pagina do Whoops mostra codigo, caminhos e variaveis de ambiente
        // (senha do banco, chaves): so em desenvolvimento.
        if (! defined('ENVIRONMENT') || ENVIRONMENT !== 'development' || ! class_exists(\Whoops\Run::class)) {
            return;
        }

        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new Whoops\Handler\PrettyPageHandler());
        $whoops->register();
    }
}
