<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Catalogo unico das permissoes do sistema.
 *
 * Antes, a lista de chaves existia em tres lugares que divergiam entre si: as
 * views de perfil (duas copias de ~800 linhas, uma para cadastrar e outra para
 * editar), o array de cada controller que salvava o POST, e o seed do banco.
 * O resultado era conhecido:
 *
 *  - `editar()` do controller salvava 103 chaves e `adicionar()` salvava 109:
 *    editar um perfil apagava as cinco permissoes de Contrato;
 *  - `aPagamento`/`ePagamento`/`dPagamento`/`vPagamento` nunca chegavam a ser
 *    usadas pelo codigo e continuavam no formulario confundindo quem configurava;
 *  - duas views de ~70 KB, distintas so no estado inicial das caixas.
 *
 * Aqui a lista vive uma vez: chave, rotulo, descricao e secao. As views so
 * desenham; o controller so grava as chaves que estao aqui. Chave fora do
 * catalogo e ignorada, entao uma permissao nova exige uma entrada aqui — e a
 * suite quebra se alguem usar uma chave no codigo sem cadastra-la.
 *
 * Funcoes puras, testadas em tests/Unit/PermissoesCatalogoTest.php.
 */
if (! function_exists('permissoes_catalogo')) {
    /**
     * Secoes na ordem em que aparecem na tela, cada uma com as chaves que agrupa.
     *
     * `padrao` diz se a caixa nasce marcada ao criar um perfil novo: so as
     * permissões de leitura essenciais, para um perfil novo não nascer sem
     * acesso a nada nem com tudo liberado.
     *
     * @return array<string, array{titulo: string, icone: string, descricao: string, chaves: array<int, array{chave: string, rotulo: string, descricao: string, padrao?: bool, perfis?: array<int, string>}>}>
     */
    function permissoes_catalogo()
    {
        return [
            'clientes' => [
                'titulo' => 'Clientes',
                'icone' => 'bx-group',
                'descricao' => 'Cadastro de clientes e fornecedores.',
                'chaves' => [
                    permissoes_chave('vCliente', 'Visualizar clientes', 'Abre a lista e a ficha. Sem ela, o item não aparece no menu.', true),
                    permissoes_chave('aCliente', 'Cadastrar cliente', 'Cria um cliente ou fornecedor novo.'),
                    permissoes_chave('eCliente', 'Editar cliente', 'Altera dados, endereço e perfil fiscal.'),
                    permissoes_chave('dCliente', 'Excluir cliente', 'Remove o cadastro. Histórico de OS e vendas fica órfão, por isso é separada de editar.'),
                ],
            ],
            'produtos' => [
                'titulo' => 'Produtos',
                'icone' => 'bx-package',
                'descricao' => 'Estoque, preços e movimentações.',
                'chaves' => [
                    permissoes_chave('vProduto', 'Visualizar produtos', 'Lista, ficha e consulta de estoque.', true),
                    permissoes_chave('aProduto', 'Cadastrar produto'),
                    permissoes_chave('eProduto', 'Editar produto', 'Inclui preço de compra e de venda.'),
                    permissoes_chave('dProduto', 'Excluir produto'),
                ],
            ],
            'servicos' => [
                'titulo' => 'Serviços',
                'icone' => 'bx-wrench',
                'descricao' => 'Catálogo de serviços prestados.',
                'chaves' => [
                    permissoes_chave('vServico', 'Visualizar serviços', null, true),
                    permissoes_chave('aServico', 'Cadastrar serviço'),
                    permissoes_chave('eServico', 'Editar serviço', 'Inclui o valor cobrado.'),
                    permissoes_chave('dServico', 'Excluir serviço'),
                ],
            ],
            'os' => [
                'titulo' => 'Ordens de Serviço',
                'icone' => 'bx-file',
                'descricao' => 'Abertura, andamento, impressão e atribuição de OS.',
                'chaves' => [
                    permissoes_chave('vOs', 'Visualizar OS', 'Abre a lista de OS e o painel inicial.', true),
                    permissoes_chave('aOs', 'Abrir OS'),
                    permissoes_chave('eOs', 'Editar OS', 'Também libera a Central de Atendimento (atribuir técnico).'),
                    permissoes_chave('dOs', 'Excluir OS'),
                    permissoes_chave('vBtnAtendimento', 'Ver botões de iniciar/finalizar', 'Mostra os botões de check-in e check-out na OS.'),
                ],
            ],
            'tecnico' => [
                'titulo' => 'Área do Técnico',
                'icone' => 'bx-user-check',
                'descricao' => 'O que o técnico em campo pode fazer. Quem tem "Acessar a Área do Técnico" e não tem "Visualizar OS" entra direto nela.',
                'chaves' => [
                    permissoes_chave('vTecnicoDashboard', 'Acessar a Área do Técnico', 'Redireciona no login e troca o menu pelo do técnico.'),
                    permissoes_chave('vTecnicoOS', 'Ver só as OS atribuídas a mim', 'Restringe a lista às OS em que o usuário é o técnico responsável.'),
                    permissoes_chave('vTecnicoMapa', 'Ver o mapa dos técnicos', 'Localização em tempo real, para despacho.'),
                    permissoes_chave('eTecnicoCheckin', 'Iniciar atendimento (check-in)'),
                    permissoes_chave('eTecnicoCheckout', 'Finalizar atendimento (check-out)'),
                    permissoes_chave('eTecnicoFotos', 'Anexar fotos ao atendimento'),
                    permissoes_chave('aTecnicoAtividade', 'Abrir atividade não programada', 'Cria OS em campo, sem ver valores.'),
                    permissoes_chave('eTecnicoNaoRealizado', 'Marcar serviço não realizado', 'Registra o motivo e deixa a OS em espera para reagendar.'),
                    permissoes_chave('cMotivoNaoRealizado', 'Gerenciar motivos de não realizado', 'Adiciona e remove os motivos da lista.'),
                    permissoes_chave('eTecnico', 'Área do técnico (acesso legado)', 'Permissão antiga, usada só para aceitar a remoção de assinatura no check-in. A área do técnico hoje é liberada por "Acessar a Área do Técnico".'),
                    permissoes_chave('aTecnico', 'Esconder o botão "Voltar ao painel"', 'A permissão antiga que marcava o menu do técnico. Mantida para os perfis existentes; o menu do técnico não depende mais dela.'),
                ],
            ],
            'rh' => [
                'titulo' => 'Recursos Humanos',
                'icone' => 'bx-id-card',
                'descricao' => 'Colaboradores, jornadas, ponto e folha.',
                'chaves' => [
                    permissoes_chave('vRh', 'Acessar o módulo de RH', null, true),
                    permissoes_chave('eRh', 'Gerenciar colaboradores, unidades e jornadas'),
                    permissoes_chave('aprovarRh', 'Aprovar ocorrências e ausências'),
                    permissoes_chave('vRhFinanceiro', 'Ver o financeiro do RH'),
                    permissoes_chave('fecharFolha', 'Fechar a competência (folha)', 'Ação sem volta: fecha o mês para edição.'),
                    permissoes_chave('vAreaColaborador', 'Acessar a Área do Colaborador', 'Autoatendimento: contracheque, férias e ponto.'),
                    permissoes_chave('baterPonto', 'Registrar o próprio ponto'),
                ],
            ],
            'obras' => [
                'titulo' => 'Projetos e Obras',
                'icone' => 'bx-building-house',
                'descricao' => 'Cronograma, diário de obra, medições, material e equipe.',
                'chaves' => [
                    permissoes_chave('vObras', 'Visualizar projetos', null, true),
                    permissoes_chave('cObras', 'Cadastrar projeto'),
                    permissoes_chave('eObras', 'Editar projeto'),
                    permissoes_chave('dObras', 'Excluir projeto'),
                    permissoes_chave('vObraCronograma', 'Ver cronograma'),
                    permissoes_chave('eObraCronograma', 'Editar cronograma e etapas'),
                    permissoes_chave('vObraRdo', 'Ver diário de obra (RDO)'),
                    permissoes_chave('cObraRdo', 'Registrar RDO'),
                    permissoes_chave('vObraMedicao', 'Ver medições'),
                    permissoes_chave('cObraMedicao', 'Lançar medição'),
                    permissoes_chave('aObraMedicao', 'Aprovar medição'),
                    permissoes_chave('vObraCusto', 'Ver custos do projeto'),
                    permissoes_chave('vObraMaterial', 'Ver almoxarifado'),
                    permissoes_chave('cObraMaterial', 'Gerenciar material'),
                    permissoes_chave('rObraMaterial', 'Receber material (entrada)'),
                    permissoes_chave('sObraMaterial', 'Entregar material (saída)'),
                    permissoes_chave('cObraEquipe', 'Gerenciar equipes'),
                    permissoes_chave('vObraCampo', 'Área de Campo (encarregado)'),
                ],
            ],
            'contratos' => [
                'titulo' => 'Contratos',
                'icone' => 'bx-file-blank',
                'descricao' => 'Contratos de manutenção e faturamento recorrente.',
                'chaves' => [
                    permissoes_chave('vContrato', 'Visualizar contratos'),
                    permissoes_chave('cContrato', 'Cadastrar contrato'),
                    permissoes_chave('eContrato', 'Editar contrato'),
                    permissoes_chave('dContrato', 'Excluir contrato'),
                    permissoes_chave('fContrato', 'Faturar mensalidade', 'Gera a cobrança do mês do contrato.'),
                ],
            ],
            'vendas' => [
                'titulo' => 'Vendas e PDV',
                'icone' => 'bx-cart-alt',
                'descricao' => 'Vendas, orçamentos, PDV e caixa.',
                'chaves' => [
                    permissoes_chave('vVenda', 'Visualizar vendas', 'Também libera o Caixa.', true),
                    permissoes_chave('aVenda', 'Registrar venda', 'Também libera o PDV.'),
                    permissoes_chave('eVenda', 'Editar venda'),
                    permissoes_chave('dVenda', 'Excluir venda'),
                ],
            ],
            'cobrancas' => [
                'titulo' => 'Cobranças',
                'icone' => 'bx-credit-card-front',
                'descricao' => 'Boletos, PIX e integração com gateways de pagamento.',
                'chaves' => [
                    permissoes_chave('vCobranca', 'Visualizar cobranças', null, true),
                    permissoes_chave('aCobranca', 'Gerar cobrança'),
                    permissoes_chave('eCobranca', 'Editar cobrança'),
                    permissoes_chave('dCobranca', 'Cancelar cobrança'),
                ],
            ],
            'fiscal' => [
                'titulo' => 'Fiscal (NF-e / NFS-e / NFC-e)',
                'icone' => 'bx-receipt',
                'descricao' => 'Emissão de notas a partir de OS e vendas, e créditos de entrada.',
                'chaves' => [
                    permissoes_chave('vNfe', 'Visualizar notas fiscais', null, true),
                    permissoes_chave('eNfe', 'Emitir nota fiscal', 'Ação real: gera e transmite a nota à SEFAZ/prefeitura.'),
                    permissoes_chave('dNfe', 'Cancelar nota fiscal'),
                    permissoes_chave('cNfe', 'Configurar o módulo fiscal', 'Certificado, séries, ambiente e regime.'),
                    permissoes_chave('vEntrada', 'Visualizar entradas e créditos'),
                    permissoes_chave('cEntrada', 'Importar ou lançar entrada'),
                    permissoes_chave('dEntrada', 'Excluir entrada'),
                ],
            ],
            'garantias' => [
                'titulo' => 'Garantias',
                'icone' => 'bx-shield-quarter',
                'descricao' => 'Termos de garantia emitidos a partir da OS.',
                'chaves' => [
                    permissoes_chave('vGarantia', 'Visualizar garantias', null, true),
                    permissoes_chave('aGarantia', 'Emitir garantia'),
                    permissoes_chave('eGarantia', 'Editar garantia'),
                    permissoes_chave('dGarantia', 'Excluir garantia'),
                ],
            ],
            'arquivos' => [
                'titulo' => 'Arquivos',
                'icone' => 'bx-box',
                'descricao' => 'Documentos anexados ao sistema.',
                'chaves' => [
                    permissoes_chave('vArquivo', 'Visualizar arquivos', null, true),
                    permissoes_chave('aArquivo', 'Enviar arquivo'),
                    permissoes_chave('eArquivo', 'Editar arquivo'),
                    permissoes_chave('dArquivo', 'Excluir arquivo'),
                ],
            ],
            'financeiro' => [
                'titulo' => 'Financeiro',
                'icone' => 'bx-wallet',
                'descricao' => 'Lançamentos de receita e despesa, e conciliação bancária.',
                'chaves' => [
                    permissoes_chave('vLancamento', 'Visualizar lançamentos', 'O painel inicial mostra cobranças pendentes só para quem tem esta permissão.', true),
                    permissoes_chave('aLancamento', 'Lançar receita/despesa'),
                    permissoes_chave('eLancamento', 'Editar lançamento'),
                    permissoes_chave('dLancamento', 'Excluir lançamento'),
                ],
            ],
            'relatorios' => [
                'titulo' => 'Relatórios',
                'icone' => 'bx-bar-chart-alt-2',
                'descricao' => 'Cada permissão libera um relatório do menu.',
                'chaves' => [
                    permissoes_chave('rCliente', 'Relatório de clientes'),
                    permissoes_chave('rProduto', 'Relatório de produtos'),
                    permissoes_chave('rServico', 'Relatório de serviços'),
                    permissoes_chave('rOs', 'Relatório de OS', 'Também libera "OS realizadas", o ciclo de faturamento e o NPS.'),
                    permissoes_chave('rVenda', 'Relatório de vendas'),
                    permissoes_chave('rFinanceiro', 'Relatório financeiro', 'Também libera custo e margem por OS e as receitas brutas do MEI.'),
                    permissoes_chave('vRelatorioAtendimentos', 'Relatório de atendimentos', 'Tempos de deslocamento e de execução do técnico.'),
                ],
            ],
            'dashboard' => [
                'titulo' => 'Painel e exportação',
                'icone' => 'bx-line-chart',
                'descricao' => 'Visões gerenciais e saída de dados.',
                'chaves' => [
                    permissoes_chave('vDashboard', 'Ver o painel gerencial'),
                    permissoes_chave('vRelatorioCompleto', 'Ver relatório completo'),
                    permissoes_chave('vExportarDados', 'Exportar dados', 'Baixa planilhas com dados de clientes e financeiro.'),
                ],
            ],
            'sistema' => [
                'titulo' => 'Configurações e sistema',
                'icone' => 'bx-cog',
                'descricao' => 'Contas, perfis, dados da empresa e rotinas técnicas.',
                'chaves' => [
                    permissoes_chave('cUsuario', 'Gerenciar usuários', 'Cria contas, troca o perfil e redefine a senha.'),
                    permissoes_chave('cPermissao', 'Gerenciar perfis de permissão', 'Quem tem esta permissão pode ampliar o próprio acesso — conceda com cuidado.'),
                    permissoes_chave('cEmitente', 'Configurar o emitente', 'Dados da empresa que aparecem nos documentos e nas notas.'),
                    permissoes_chave('cSistema', 'Configurar o sistema', 'Tema, paginação, atualização do banco e da aplicação.'),
                    permissoes_chave('cEmail', 'Configurar e-mail e notificações'),
                    permissoes_chave('cBackup', 'Fazer backup do banco'),
                    permissoes_chave('cAuditoria', 'Consultar a auditoria'),
                    permissoes_chave('cAutomacao', 'Configurar a automação de faturamento', 'Liga a emissão de NFS-e e boleto no aceite da OS.'),
                    permissoes_chave('cFormularioAtendimento', 'Gerenciar formulários de atendimento'),
                ],
            ],
        ];
    }
}

if (! function_exists('permissoes_chave')) {
    /**
     * Normaliza uma entrada do catalogo. `descricao` nula vira string vazia para
     * a view nao precisar testar null em cada linha.
     *
     * @param array<int, string> $perfis perfis sugeridos que costumam receber a chave
     *
     * @return array{chave: string, rotulo: string, descricao: string, padrao: bool, perfis: array<int, string>}
     */
    function permissoes_chave($chave, $rotulo, $descricao = null, $padrao = false, array $perfis = [])
    {
        return [
            'chave' => (string) $chave,
            'rotulo' => (string) $rotulo,
            'descricao' => (string) ($descricao ?? ''),
            'padrao' => (bool) $padrao,
            'perfis' => $perfis,
        ];
    }
}

if (! function_exists('permissoes_todas_as_chaves')) {
    /**
     * Todas as chaves do catalogo, na ordem das secoes.
     *
     * @return array<int, string>
     */
    function permissoes_todas_as_chaves()
    {
        $chaves = [];
        foreach (permissoes_catalogo() as $secao) {
            foreach ($secao['chaves'] as $item) {
                $chaves[] = $item['chave'];
            }
        }

        return $chaves;
    }
}

if (! function_exists('permissoes_chaves_padrao')) {
    /**
     * Chaves marcadas por padrao em um perfil novo (as leituras essenciais).
     *
     * @return array<int, string>
     */
    function permissoes_chaves_padrao()
    {
        $chaves = [];
        foreach (permissoes_catalogo() as $secao) {
            foreach ($secao['chaves'] as $item) {
                if ($item['padrao']) {
                    $chaves[] = $item['chave'];
                }
            }
        }

        return $chaves;
    }
}

if (! function_exists('permissoes_indice')) {
    /**
     * Chave => entrada do catalogo, para rotulo e descricao rapidos.
     *
     * @return array<string, array{chave: string, rotulo: string, descricao: string, padrao: bool, perfis: array<int, string>}>
     */
    function permissoes_indice()
    {
        $indice = [];
        foreach (permissoes_catalogo() as $secao) {
            foreach ($secao['chaves'] as $item) {
                $indice[$item['chave']] = $item;
            }
        }

        return $indice;
    }
}

if (! function_exists('permissoes_do_post')) {
    /**
     * Le do POST só as chaves do catalogo. Chave que não está no catalogo é
     * descartada, então um campo a mais no formulário não vira permissão
     * fantasma no banco.
     *
     * @param callable $post recebe o nome do campo e devolve o valor (ex.: fn ($c) => $this->input->post($c))
     *
     * @return array<string, int>
     */
    function permissoes_do_post(callable $post)
    {
        $permissoes = [];
        foreach (permissoes_todas_as_chaves() as $chave) {
            $permissoes[$chave] = $post($chave) ? 1 : 0;
        }

        return $permissoes;
    }
}

if (! function_exists('permissoes_normalizar')) {
    /**
     * Deixa um blob lido do banco no formato do catalogo: todas as chaves
     * presentes, 0/1 inteiro, e nada fora do catalogo. E o que impede um perfil
     * salvo antes de uma permissao nova de virar "falso" no checkPermission
     * (o que redirecionaria o usuario) e o que faz o formulario mostrar o estado
     * real das caixas.
     *
     * Valor ausente vira 0 — nunca herda o "padrao" — para nao conceder acesso
     * que o administrador não marcou.
     *
     * @param mixed $salvas array serializado ja desserializado (ou lixo)
     *
     * @return array<string, int>
     */
    function permissoes_normalizar($salvas)
    {
        if (! is_array($salvas)) {
            $salvas = [];
        }

        $normalizadas = [];
        foreach (permissoes_todas_as_chaves() as $chave) {
            $valor = $salvas[$chave] ?? 0;
            $normalizadas[$chave] = (is_numeric($valor) && (int) $valor === 1) ? 1 : 0;
        }

        return $normalizadas;
    }
}

if (! function_exists('permissoes_resumo')) {
    /**
     * Quantas chaves do catalogo o perfil tem liberadas. Usado na lista de
     * perfis para dar uma ideia do alcance sem abrir cada um.
     *
     * @param mixed $salvas
     */
    function permissoes_resumo($salvas)
    {
        $normalizadas = permissoes_normalizar($salvas);

        return count(array_filter($normalizadas, static function ($v) {
            return $v === 1;
        }));
    }
}

if (! function_exists('permissoes_texto_resumo')) {
    /**
     * "12 de 97 permissões" para a coluna de resumo da lista.
     *
     * @param mixed $salvas
     */
    function permissoes_texto_resumo($salvas)
    {
        return permissoes_resumo($salvas) . ' de ' . count(permissoes_todas_as_chaves()) . ' permissões';
    }
}
