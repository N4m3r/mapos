<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu superior do layout administrativo (tema/topo) para as telas de uso
 * menos frequente. Ficam aqui, e nao no menu lateral, para o dia a dia
 * continuar curto: cliente, produto, servico, venda, OS e o operacional.
 *
 * Funcoes puras, testadas em tests/Unit/MenuSuperiorTest.php.
 */
if (! function_exists('menu_superior_atalhos')) {
    /**
     * Atalhos liberados para o usuario, na ordem em que aparecem no menu.
     *
     * @param callable $pode Recebe a atividade (ex.: 'vRh') e devolve bool.
     *
     * @return array<int, array{id: string, rotulo: string, icone: string, rota: string}>
     */
    function menu_superior_atalhos(callable $pode)
    {
        $catalogo = [
            ['id' => 'mapa', 'rotulo' => 'Gestão no Mapa', 'icone' => 'bx-map-alt', 'rota' => 'localizacao/mapa', 'permissao' => 'vTecnicoMapa'],
            ['id' => 'rh', 'rotulo' => 'Recursos Humanos', 'icone' => 'bx-id-card', 'rota' => 'rh', 'permissao' => 'vRh'],
            ['id' => 'nfe', 'rotulo' => 'Notas Fiscais', 'icone' => 'bx-receipt', 'rota' => 'nfe', 'permissao' => 'vNfe'],
            ['id' => 'entradas', 'rotulo' => 'Entradas', 'icone' => 'bx-import', 'rota' => 'entradas', 'permissao' => 'vEntrada'],
            ['id' => 'contratos', 'rotulo' => 'Contratos', 'icone' => 'bx-file-blank', 'rota' => 'contratos', 'permissao' => 'vContrato'],
            ['id' => 'cobrancas', 'rotulo' => 'Cobranças', 'icone' => 'bx-dollar-circle', 'rota' => 'cobrancas/cobrancas', 'permissao' => 'vCobranca'],
            ['id' => 'osrealizadas', 'rotulo' => 'OS realizadas', 'icone' => 'bx-check-square', 'rota' => 'relatorioos', 'permissao' => 'rOs'],
        ];

        $atalhos = [];
        foreach ($catalogo as $atalho) {
            if (! $pode($atalho['permissao'])) {
                continue;
            }
            unset($atalho['permissao']);
            $atalhos[] = $atalho;
        }

        return $atalhos;
    }
}

if (! function_exists('menu_superior_ativo')) {
    /**
     * Verdadeiro quando a rota atual pertence ao modulo do atalho. Compara o
     * primeiro segmento da URI, para marcar "Cobranças" tambem na configuracao
     * do gateway (cobrancas/configCora) e nao so na lista.
     */
    function menu_superior_ativo($atual, $rota)
    {
        $primeiro = function ($caminho) {
            $caminho = preg_replace('#^index\.php/?#i', '', trim((string) $caminho, '/'));
            $partes = explode('/', $caminho);

            return strtolower($partes[0]);
        };

        $modulo = $primeiro($rota);

        return $modulo !== '' && $primeiro($atual) === $modulo;
    }
}
