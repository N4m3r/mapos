# ADR-023: Menu unificado com catálogo único

- **Status**: accepted
- **Date**: 2026-11-06
- **Deciders**: N4m3r
- **Tags**: navegacao, menu, ux, permissoes
- **Branch**: `notificacoes-central-e-menu-unificado`

## Context

A navegação tinha duas superfícies. A barra lateral (`views/tema/menu.php`) listava 12 telas de módulo mais o
Início, a busca e a saída, com o item aberto marcado por flags `$menu*` que cada controller definia. O topo
(`views/tema/topo.php`) tinha quatro dropdowns: Atalhos, Perfis, Relatórios e Configurações.

Isso produzia três problemas:

1. **Duplicação.** Sete rotas apareciam nas duas superfícies (`clientes`, `os`, `produtos`, `servicos`,
   `financeiro/lancamentos`, `mapos/pesquisar`, `login/sair`). Medido no commit anterior, contando alvos
   literais de `site_url`: 14 na lateral, contra 32 nos dropdowns de Perfis (2), Relatórios (16) e
   Configurações (14) e mais 7 no corpo do topo. O dropdown Atalhos é montado em laço a partir de
   `$ns_atalhos`, então não tem alvo literal para contar.
2. **Permissão desigual.** No dropdown de Relatórios, 15 dos 16 links apareciam sem nenhuma checagem; só
   `relatorioos` estava sob `rOs`. Na lateral, 12 chamadas de `checkPermission` cobriam 13 dos 14 links, e
   apenas `mapos/pesquisar` era sempre visível. Quem não tinha a permissão via o link no topo e só descobria
   no redirect.
3. **Duas listas para manter.** O ADR-020 chegou a registrar um item "no menu superior de atalhos"; mover uma
   tela exigia tocar em dois arquivos e num helper, e a paleta de comandos (Ctrl+K) precisava varrer os dois
   lugares para não perder telas.

## Decision

Uma superfície de navegação, alimentada por um catálogo.

1. **Fonte única.** `helpers/menu_navegacao_helper.php` declara os destinos em seis grupos (Operação,
   Cadastros, Comercial e Projetos, Financeiro, Relatórios e análise, Sistema). Cada item traz rótulo, dica,
   ícone, rota, permissões que o liberam e apelidos de rota.
2. **Filtro por permissão no catálogo.** `menu_navegacao_para($pode)` devolve só o que a permissão libera e
   descarta grupo que ficou vazio. A permissão deixa de ser responsabilidade da view.
3. **Item aberto derivado da URI.** `menu_navegacao_ativo($atual, $rota, $aliases)` compara rota e apelidos,
   ignorando `index.php/`, barra inicial e caixa. As flags `$menu*` dos controllers deixam de ser lidas pelo
   menu (continuam existindo; podem ser removidas depois, uma por vez).
4. **Topo sem navegação.** Saem os dropdowns Atalhos, Relatórios e Configurações. Ficam perfil, busca e o link
   de saída, que não são navegação de módulo.
5. **Barra lateral rola sozinha.** Não havia `overflow` em `#sidebar`/`.menu-bar`; com 48 destinos a lista não
   cabe em telas baixas. `assets/css/menu-navegacao.css` cuida da rolagem e do título de grupo, que some no
   modo recolhido (80px).
6. **Paleta de comandos lê um lugar só.** `assets/js/paleta-comandos.js` passa a varrer apenas `#sidebar`.

`menu-superior.css`, `helpers/menu_superior_helper.php` e `tests/Unit/MenuSuperiorTest.php` foram removidos.

## Implementacao

| Parte | Arquivos |
|---|---|
| Catálogo e filtros | `helpers/menu_navegacao_helper.php` |
| Renderização | `views/tema/menu.php` |
| Estilo do menu | `assets/css/menu-navegacao.css` |
| Topo (sem navegação) | `views/tema/topo.php` |
| Paleta Ctrl+K | `assets/js/paleta-comandos.js` |
| Testes | `tests/Unit/MenuNavegacaoTest.php` |

O menu do técnico (`views/tema/menu_tecnico.php`) é outro layout, com outro público, e não foi tocado.

## Consequences

### Positive
- Uma tela nova entra em um lugar só, e já nasce com permissão correta e busca por teclado.
- Quem não tem a permissão deixa de ver o link: menos redirect e menos dúvida.
- O topo fica curto; a navegação inteira está na lateral, agrupada por área.
- `MenuNavegacaoTest` cobre ordem dos grupos, filtro por permissão (inclusive permissão alternativa, como
  `cSistema` ou `cEmail`), ids e rotas sem repetição, a marcação de um item só por tela, a ausência de item
  gated por permissão que troca o layout (`vTecnicoDashboard`) e o alcance da configuração da Cora por quem
  tem apenas `cNfe`.

### Negative
- A barra lateral ficou longa (48 destinos). Em tela baixa depende da rolagem nova para ser alcançável.
- As flags `$menu*` dos controllers deixaram de ser lidas pelo menu administrativo e pelo topo, mas nada foi
  removido. Das 23 chaves que os controllers ainda definem, 11 continuam lidas por dois outros layouts:
  `tema/menu_tecnico.php` (Clientes, Produtos, Serviços, OS e Painel) e `conecte/template.php`, a área do
  cliente (Aprovações, Atendimentos, Cobranças, Conta, Notas, OS, Painel e Vendas). As outras 12 não têm leitor
  nenhum. A limpeza precisa separar os dois casos.
- Administradores que usavam o dropdown de Relatórios precisam reaprender o caminho (grupo "Relatórios e
  análise" na lateral).
- O menu do técnico segue separado, então duas telas que existem nos dois menus (Produtos, Serviços,
  Clientes) continuam descritas em dois arquivos.
- Telas que só existiam no dropdown do topo ganharam item no catálogo: a configuração da Cobrança Cora
  entrou no grupo Financeiro, com o mesmo `cNfe` que o link do topo exigia. Sem isso, um grupo com `cNfe` e
  sem `vCobranca`/`vNfe` só chegaria nela digitando a URL.
- O item "Área do Técnico" saiu do catálogo: `isTecnico()` troca o layout para quem tem `vTecnicoDashboard`, e
  um item gated por essa permissão nunca apareceria.

## Links
- **Substitui**: menu superior de atalhos (commit "Move atalhos de uso menos frequente para o menu superior")
- **Related**: ADR-020 (relatório de OS realizadas), ADR-002 (paleta de comandos), ADR-024 (central de notificações)
