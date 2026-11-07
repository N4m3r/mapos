# ADR-025: Categorias recolhíveis no menu lateral

- **Status**: accepted
- **Date**: 2026-11-07
- **Deciders**: N4m3r
- **Tags**: navegacao, menu, ux, acessibilidade
- **Relacionado**: ADR-023 (menu unificado com catálogo único), ADR-002 (paleta de comandos)

## Context

O ADR-023 unificou a navegação numa barra lateral só, alimentada pelo catálogo de
`helpers/menu_navegacao_helper.php`. A decisão resolveu a duplicação (sete rotas apareciam na
lateral e nos dropdowns do topo) e a permissão desigual, mas deixou registrado o custo:

> A barra lateral ficou longa (48 destinos). Em tela baixa depende da rolagem nova para ser
> alcançável.

Os seis grupos existiam só como título decorativo: `<li class="nv-grupo">` em maiúsculas, sem
nenhuma interação. Para chegar em "Conciliação bancária" era preciso rolar por quase a lista
inteira, porque os 48 destinos ficavam todos abertos ao mesmo tempo. Os grupos organizavam
visualmente, mas não reduziam o caminho.

## Decision

Cada grupo do catálogo vira uma categoria que abre e fecha.

1. **O catálogo ganha ícone por grupo.** `menu_navegacao_catalogo()` passa a trazer `icone` em cada
   grupo, e `menu_navegacao_para()` repassa esse campo para a view (antes ele montava o grupo com
   apenas `titulo` e `itens`).
2. **A view renderiza categoria, não título solto.** `views/tema/menu.php` gera
   `<li class="nv-cat" data-cat="...">` com um `<button class="nv-cat-btn" aria-expanded aria-controls>`
   e, logo abaixo, `<ul class="nv-sub" id="nv-sub-<chave>">` com os itens. O `<button>` foi escolhido
   em vez de `<a href="#">` porque não navega — e porque `matrix.js` já trata `.submenu > a` com
   `preventDefault`, o que criaria conflito.
3. **A categoria da tela atual nasce aberta.** A view marca `nv-cat-atual` no grupo que contém o item
   ativo (o mesmo que o menu já destacava) e omite `hidden` na lista dele. Sem isso o item marcado
   ficaria escondido atrás de um clique, e o usuário perderia a referência de onde está.
4. **A escolha do usuário é lembrada.** `assets/js/menu-lateral.js` guarda o estado em
   `localStorage` (`menuCategorias`), no mesmo espírito do rascunho de formulário do ADR-003. Sem
   storage (modo privado, cota cheia) o menu continua abrindo e fechando: só não persiste. Quando
   não há nada gravado, abre a categoria atual ou, sem tela marcada, a primeira.
5. **Acessibilidade explícita.** `aria-expanded` acompanha o estado, `aria-controls` aponta para a
   lista, e o fechamento usa o atributo `hidden`, que remove do alcance de leitores de tela em vez de
   apenas esconder visualmente.
6. **O script não vaza para os outros menus.** `menu_tecnico.php` e `conecte/template.php` usam a
   mesma `#sidebar`, mas não têm `.nv-cat`; o script sai na primeira verificação e eles seguem
   exatamente como estavam.
7. **O modo recolhido (80px) mantém a lista plana.** No desktop recolhido não há largura para o
   submenu, então as categorias se abrem e os rótulos somem, restando a coluna de ícones. Essa parte
   do CSS ficou escopada em `min-width: 769px`: no celular `#sidebar.open` significa a gaveta
   visível com 240px, e ali as categorias precisam continuar recolhíveis —justamente onde a lista é
   mais longa.

## Implementacao

| Parte | Arquivos |
|---|---|
| Ícone por grupo no catálogo | `helpers/menu_navegacao_helper.php` |
| Renderização das categorias | `views/tema/menu.php` |
| Estilo da categoria e do submenu | `assets/css/menu-navegacao.css` |
| Abrir, fechar e lembrar | `assets/js/menu-lateral.js` (novo), carregado em `views/tema/rodape.php` |
| Testes | `tests/Unit/MenuNavegacaoTest.php`, `tests/Unit/MenuViewTest.php` |

`MenuViewTest` ganhou dois casos: um confere que cada grupo vira uma categoria com botão e lista
(a mesma quantidade dos dois lados) e outro que confere a categoria atual aberta e as demais com
`hidden`.

## Consequences

### Positive
- A lateral abre com uma categoria em vez de 48 linhas. O caminho até uma tela de outro grupo passa
  a ser um clique no grupo e outro no item.
- O item da tela atual continua visível: a categoria dele nasce aberta e o cabeçalho fica destacado.
- A escolha sobrevive à navegação, então quem trabalha sempre em Financeiro não reabre o grupo a cada
  página.
- A paleta (Ctrl+K) continua alcançando tudo: `hidden` não remove os links do DOM, e o script varre
  `a[href]` dentro de `#sidebar`. Menu recolhido por categoria não esconde nada da busca.
- Nenhum item saiu do catálogo nem mudou de permissão: os 48 destinos e o filtro por permissão do
  ADR-023 seguem iguais.

### Negative
- A categoria fechada esconde telas do olho: quem não sabe em que grupo está "Compra sugerida"
  precisa abrir grupos ou usar a busca. O agrupamento atual é por área do sistema, não por
  frequência de uso.
- Passa a existir estado de interface no navegador. Um `localStorage` limpo volta ao padrão
  (categoria atual ou primeira), o que é aceitável, mas é mais uma coisa a explicar em suporte.
- O botão de categoria é um alvo novo no menu; qualquer regra futura de CSS que estilize `button`
  dentro de `#sidebar` precisa considerá-lo.
- A classe `nv-grupo` deixou de existir na marcação. Nada no repositório dependia dela além do
  próprio teste, que passou a verificar `nv-cat`.

## Links
- **Estende**: ADR-023 (menu unificado), cujo custo registrado era justamente a lista longa
- **Related**: ADR-002 (paleta de comandos), ADR-003 (estado de interface em localStorage)
