<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Catalogo unico da navegacao do layout administrativo.
 *
 * Antes existiam duas superficies: a barra lateral (15 itens) e os dropdowns do
 * topo (Atalhos, Perfis, Relatorios, Configuracoes). O usuario precisava saber
 * em qual das duas estava cada tela, e 7 rotas apareciam nas duas. Agora este
 * catalogo e a unica fonte: a barra lateral renderiza tudo, agrupado, e o topo
 * fica so com perfil e busca.
 *
 * Funcoes puras, testadas em tests/Unit/MenuNavegacaoTest.php.
 */
if (! function_exists('menu_navegacao_catalogo')) {
    /**
     * Grupos na ordem em que aparecem na barra lateral.
     *
     * Item: id, rotulo, dica (tooltip), icone, rota, permissoes (qualquer uma
     * libera; vazio = sempre visivel) e aliases (outras rotas que marcam o item
     * como ativo).
     *
     * @return array<string, array{titulo: string, itens: array<int, array>}>
     */
    function menu_navegacao_catalogo()
    {
        return [
            'operacao' => [
                'titulo' => 'Operação',
                'itens' => [
                    ['id' => 'home', 'rotulo' => 'Início', 'dica' => 'Início', 'icone' => 'bx-home-alt', 'rota' => '', 'permissoes' => []],
                    ['id' => 'os', 'rotulo' => 'Ordens de Serviço', 'dica' => 'Ordens', 'icone' => 'bx-file', 'rota' => 'os', 'permissoes' => ['vOs'], 'aliases' => ['os/adicionar']],
                    ['id' => 'atendimento', 'rotulo' => 'Central de Atendimento', 'dica' => 'Atendimento', 'icone' => 'bx-headphone', 'rota' => 'os/atribuir', 'permissoes' => ['eOs']],
                    ['id' => 'agenda', 'rotulo' => 'Agenda', 'dica' => 'Agenda', 'icone' => 'bx-calendar', 'rota' => 'agenda', 'permissoes' => ['vOs']],
                    ['id' => 'osrealizadas', 'rotulo' => 'OS realizadas', 'dica' => 'OS realizadas', 'icone' => 'bx-check-square', 'rota' => 'relatorioos', 'permissoes' => ['rOs']],
                    ['id' => 'sla', 'rotulo' => 'Prazos (SLA)', 'dica' => 'SLA', 'icone' => 'bx-time-five', 'rota' => 'sla', 'permissoes' => ['vOs']],
                    ['id' => 'tecnico', 'rotulo' => 'Área do Técnico', 'dica' => 'Técnico', 'icone' => 'bx-wrench', 'rota' => 'tecnico', 'permissoes' => ['vTecnicoDashboard']],
                    ['id' => 'mapa', 'rotulo' => 'Gestão no Mapa', 'dica' => 'Mapa', 'icone' => 'bx-map-alt', 'rota' => 'localizacao/mapa', 'permissoes' => ['vTecnicoMapa']],
                ],
            ],
            'cadastros' => [
                'titulo' => 'Cadastros',
                'itens' => [
                    ['id' => 'clientes', 'rotulo' => 'Clientes e Fornecedores', 'dica' => 'Clientes', 'icone' => 'bx-user', 'rota' => 'clientes', 'permissoes' => ['vCliente']],
                    ['id' => 'produtos', 'rotulo' => 'Produtos', 'dica' => 'Produtos', 'icone' => 'bx-basket', 'rota' => 'produtos', 'permissoes' => ['vProduto']],
                    ['id' => 'servicos', 'rotulo' => 'Serviços', 'dica' => 'Serviços', 'icone' => 'bx-wrench', 'rota' => 'servicos', 'permissoes' => ['vServico']],
                    ['id' => 'garantias', 'rotulo' => 'Termos de Garantia', 'dica' => 'Garantias', 'icone' => 'bx-receipt', 'rota' => 'garantias', 'permissoes' => ['vGarantia']],
                    ['id' => 'arquivos', 'rotulo' => 'Arquivos', 'dica' => 'Arquivos', 'icone' => 'bx-box', 'rota' => 'arquivos', 'permissoes' => ['vArquivo']],
                    ['id' => 'rh', 'rotulo' => 'Recursos Humanos', 'dica' => 'RH', 'icone' => 'bx-id-card', 'rota' => 'rh', 'permissoes' => ['vRh']],
                ],
            ],
            'comercial' => [
                'titulo' => 'Comercial e Projetos',
                'itens' => [
                    ['id' => 'vendas', 'rotulo' => 'Vendas', 'dica' => 'Vendas', 'icone' => 'bx-cart-alt', 'rota' => 'vendas', 'permissoes' => ['vVenda']],
                    ['id' => 'pdv', 'rotulo' => 'PDV', 'dica' => 'PDV', 'icone' => 'bx-store-alt', 'rota' => 'pdv', 'permissoes' => ['aVenda']],
                    ['id' => 'caixa', 'rotulo' => 'Caixa', 'dica' => 'Caixa', 'icone' => 'bx-money', 'rota' => 'caixa', 'permissoes' => ['vVenda']],
                    ['id' => 'obras', 'rotulo' => 'Projetos', 'dica' => 'Projetos', 'icone' => 'bx-network-chart', 'rota' => 'obras', 'permissoes' => ['vObras']],
                    ['id' => 'contratos', 'rotulo' => 'Contratos', 'dica' => 'Contratos', 'icone' => 'bx-file-blank', 'rota' => 'contratos', 'permissoes' => ['vContrato']],
                ],
            ],
            'financeiro' => [
                'titulo' => 'Financeiro',
                'itens' => [
                    ['id' => 'lancamentos', 'rotulo' => 'Lançamentos', 'dica' => 'Lançamentos', 'icone' => 'bx-bar-chart-alt-2', 'rota' => 'financeiro/lancamentos', 'permissoes' => ['vLancamento']],
                    ['id' => 'cobrancas', 'rotulo' => 'Cobranças', 'dica' => 'Cobranças', 'icone' => 'bx-dollar-circle', 'rota' => 'cobrancas/cobrancas', 'permissoes' => ['vCobranca']],
                    ['id' => 'entradas', 'rotulo' => 'Entradas', 'dica' => 'Entradas', 'icone' => 'bx-import', 'rota' => 'entradas', 'permissoes' => ['vEntrada']],
                    ['id' => 'nfe', 'rotulo' => 'Notas Fiscais', 'dica' => 'NFS-e', 'icone' => 'bx-receipt', 'rota' => 'nfe', 'permissoes' => ['vNfe']],
                    ['id' => 'conciliacao', 'rotulo' => 'Conciliação bancária', 'dica' => 'Conciliação', 'icone' => 'bx-link-external', 'rota' => 'conciliacao', 'permissoes' => ['vLancamento']],
                    ['id' => 'margem', 'rotulo' => 'Custo e margem por OS', 'dica' => 'Margem', 'icone' => 'bx-tachometer', 'rota' => 'margem', 'permissoes' => ['rFinanceiro']],
                ],
            ],
            'relatorios' => [
                'titulo' => 'Relatórios e análise',
                'itens' => [
                    ['id' => 'painelgerencial', 'rotulo' => 'Painel gerencial', 'dica' => 'Painel', 'icone' => 'bx-line-chart', 'rota' => 'painelgerencial', 'permissoes' => ['vDashboard', 'rOs']],
                    ['id' => 'rel_clientes', 'rotulo' => 'Clientes', 'dica' => 'Clientes', 'icone' => 'bx-group', 'rota' => 'relatorios/clientes', 'permissoes' => ['rCliente']],
                    ['id' => 'rel_produtos', 'rotulo' => 'Produtos', 'dica' => 'Produtos', 'icone' => 'bx-package', 'rota' => 'relatorios/produtos', 'permissoes' => ['rProduto']],
                    ['id' => 'rel_servicos', 'rotulo' => 'Serviços', 'dica' => 'Serviços', 'icone' => 'bx-wrench', 'rota' => 'relatorios/servicos', 'permissoes' => ['rServico']],
                    ['id' => 'rel_os', 'rotulo' => 'Ordens de Serviço', 'dica' => 'OS', 'icone' => 'bx-file', 'rota' => 'relatorios/os', 'permissoes' => ['rOs']],
                    ['id' => 'rel_ciclo', 'rotulo' => 'Ciclo e faturamento das OS', 'dica' => 'Ciclo', 'icone' => 'bx-refresh', 'rota' => 'relatorios/cicloFaturamento', 'permissoes' => ['rOs']],
                    ['id' => 'rel_nps', 'rotulo' => 'Satisfação (NPS)', 'dica' => 'NPS', 'icone' => 'bx-medal', 'rota' => 'relatorios/nps', 'permissoes' => ['rOs']],
                    ['id' => 'rel_vendas', 'rotulo' => 'Vendas', 'dica' => 'Vendas', 'icone' => 'bx-cart-alt', 'rota' => 'relatorios/vendas', 'permissoes' => ['rVenda']],
                    ['id' => 'rel_financeiro', 'rotulo' => 'Financeiro', 'dica' => 'Financeiro', 'icone' => 'bx-dollar-circle', 'rota' => 'relatorios/financeiro', 'permissoes' => ['rFinanceiro']],
                    ['id' => 'rel_sku', 'rotulo' => 'SKU', 'dica' => 'SKU', 'icone' => 'bx-data', 'rota' => 'relatorios/sku', 'permissoes' => ['rVenda', 'rProduto']],
                    ['id' => 'rel_mei', 'rotulo' => 'Receitas brutas (MEI)', 'dica' => 'MEI', 'icone' => 'bx-spreadsheet', 'rota' => 'relatorios/receitasBrutasMei', 'permissoes' => ['rFinanceiro']],
                    ['id' => 'rel_cobranca', 'rotulo' => 'Cobrança do mês', 'dica' => 'Cobrança', 'icone' => 'bx-receipt', 'rota' => 'relatoriocobranca', 'permissoes' => ['rFinanceiro']],
                    ['id' => 'comprasugerida', 'rotulo' => 'Compra sugerida', 'dica' => 'Compras', 'icone' => 'bx-cart', 'rota' => 'comprasugerida', 'permissoes' => ['rProduto']],
                    ['id' => 'atendimentos', 'rotulo' => 'Atendimentos', 'dica' => 'Atendimentos', 'icone' => 'bx-time', 'rota' => 'relatorioatendimentos', 'permissoes' => ['vRelatorioAtendimentos']],
                ],
            ],
            'sistema' => [
                'titulo' => 'Sistema',
                'itens' => [
                    ['id' => 'configuracoes', 'rotulo' => 'Configurações', 'dica' => 'Configurações', 'icone' => 'bx-cog', 'rota' => 'mapos/configurar', 'permissoes' => ['cSistema']],
                    ['id' => 'usuarios', 'rotulo' => 'Usuários', 'dica' => 'Usuários', 'icone' => 'bx-user-circle', 'rota' => 'usuarios', 'permissoes' => ['cUsuario']],
                    ['id' => 'permissoes', 'rotulo' => 'Permissões', 'dica' => 'Permissões', 'icone' => 'bx-lock-alt', 'rota' => 'permissoes', 'permissoes' => ['cPermissao']],
                    ['id' => 'emitente', 'rotulo' => 'Emitente', 'dica' => 'Emitente', 'icone' => 'bx-buildings', 'rota' => 'mapos/emitente', 'permissoes' => ['cEmitente']],
                    ['id' => 'notificacoes', 'rotulo' => 'Notificações', 'dica' => 'Notificações', 'icone' => 'bx-bell', 'rota' => 'notificacoes', 'permissoes' => ['cSistema', 'cEmail']],
                    ['id' => 'automacao', 'rotulo' => 'Automação (aprovação)', 'dica' => 'Automação', 'icone' => 'bx-broadcast', 'rota' => 'automacao', 'permissoes' => ['cAutomacao', 'cSistema']],
                    ['id' => 'formularios', 'rotulo' => 'Formulários de atendimento', 'dica' => 'Formulários', 'icone' => 'bx-list-check', 'rota' => 'formularios', 'permissoes' => ['cFormularioAtendimento']],
                    ['id' => 'auditoria', 'rotulo' => 'Auditoria', 'dica' => 'Auditoria', 'icone' => 'bx-shield-quarter', 'rota' => 'auditoria', 'permissoes' => ['cAuditoria']],
                    ['id' => 'backups', 'rotulo' => 'Backup', 'dica' => 'Backup', 'icone' => 'bx-cloud-upload', 'rota' => 'mapos/backups', 'permissoes' => ['cBackup']],
                ],
            ],
        ];
    }
}

if (! function_exists('menu_navegacao_para')) {
    /**
     * Grupos e itens que a permissao libera, na ordem do catalogo. Grupo sem
     * nenhum item liberado nao aparece.
     *
     * @param callable $pode Recebe a atividade (ex.: 'vOs') e devolve bool.
     *
     * @return array<string, array{titulo: string, itens: array<int, array>}>
     */
    function menu_navegacao_para(callable $pode)
    {
        $grupos = [];

        foreach (menu_navegacao_catalogo() as $chave => $grupo) {
            $itens = [];
            foreach ($grupo['itens'] as $item) {
                $permissoes = $item['permissoes'] ?? [];
                $liberado = empty($permissoes);
                foreach ($permissoes as $atividade) {
                    if ($pode($atividade)) {
                        $liberado = true;
                        break;
                    }
                }
                if (! $liberado) {
                    continue;
                }

                unset($item['permissoes']);
                $itens[] = $item;
            }

            if (! empty($itens)) {
                $grupos[$chave] = ['titulo' => $grupo['titulo'], 'itens' => $itens];
            }
        }

        return $grupos;
    }
}

if (! function_exists('menu_navegacao_ativo')) {
    /**
     * Verdadeiro quando a rota atual e a do item (ou um apelido).
     *
     * A raiz so casa com a URI vazia, para o item Inicio nao ficar ativo em
     * todas as telas.
     */
    function menu_navegacao_ativo($atual, $rota, array $aliases = [])
    {
        $norm = function ($caminho) {
            $caminho = preg_replace('#^index\.php/?#i', '', trim((string) $caminho, '/'));

            return strtolower($caminho);
        };

        $atual = $norm($atual);
        foreach (array_merge([$rota], $aliases) as $r) {
            $r = $norm($r);
            if ($r === '') {
                if ($atual === '') {
                    return true;
                }
                continue;
            }
            if ($atual === $r || strpos($atual, $r . '/') === 0) {
                return true;
            }
        }

        return false;
    }
}
