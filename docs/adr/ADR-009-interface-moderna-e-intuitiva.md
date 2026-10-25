# ADR-009: Interface moderna e mais intuitiva em todas as telas

- **Status**: accepted
- **Date**: 2026-10-25
- **Deciders**: N4m3r
- **Tags**: ui, ux, css, acessibilidade
- **Branch**: `seguranca-23-10`

## Context

As 237 views usam o layout Matrix (Bootstrap 2) com estilos inline espalhados. Cada tela
tem um jeito próprio de mostrar título, busca e ações. Muitos botões só têm ícone, não
há estado vazio nas listas e o celular rola na horizontal. Os 6 temas de cor
(`tema-*.css`) precisam continuar funcionando.

## Decision

1. **Camada única `assets/css/moderno.css`**, carregada depois do tema. O tema continua
   dando as cores; a camada cuida de forma, espaço, tipografia (Inter), foco visível,
   tabelas, formulários e modais. Usa rgba neutro para valer nos temas claros e escuros.
2. **Componentes `ui-*` sem JavaScript**, com uma classe para cada elemento:

   | Classe | Para que serve |
   |--------|----------------|
   | `ui-cabecalho` | título, descrição e ações da página |
   | `ui-filtros` | busca e filtros com rótulo |
   | `ui-kpis` / `ui-kpi` | indicadores |
   | `ui-vazio` | estado vazio |
   | `ui-dica` | ajuda curta |
   | `ui-selo` | status |
   | `ui-secao` / `ui-grade` | formulário em seções |
   | `ui-rodape-form` | botões no fim do formulário |
   | `ui-acoes-linha` | ações por linha de tabela |
   | `ui-tabela-rolagem` | tabela que rola no celular |

3. **Aplicação por área, em paralelo**, só na marcação das views. Não muda nenhum `id`,
   `name`, `action`, atributo `data-*`, script carregado, regra PHP nem permissão, e não
   adiciona JavaScript inline (CSP do ADR-005). Telas de impressão/PDF e modelos de
   e-mail ficam fora.
4. **Modelo por tarefa**: o ruflo (`hooks model-route`) mediu complexidade baixa (21-33%)
   para todas as áreas. Ficou Haiku nas áreas mecânicas (cadastros, configurações,
   obras/RH) e Sonnet nas que pedem critério (OS/vendas/PDV, financeiro/relatórios, área
   do cliente/técnico).

## Implementacao

| Área | Modelo | Telas |
|------|--------|-------|
| OS, vendas, caixa, PDV | Sonnet | 8 (visualizar/editar OS e venda ficaram de fora: arquivos grandes com JS no título) |
| Cadastros (clientes, produtos, serviços, garantias, inventário, entradas, arquivos) | Haiku | 24 |
| Financeiro, cobranças, NF-e, relatórios, módulos do ADR-008 | Sonnet | 20 |
| Área do cliente, técnico, colaborador, páginas públicas | Sonnet | 25 (camada incluída nos heads próprios) |
| Configurações e administração | Haiku | 31 |
| Obras, contratos, RH, ponto, formulários, check-in | Haiku | 35 |

A sessão principal criou a camada `moderno.css` e as variantes pedidas pelos agentes
(`ui-dica.alerta/perigo`, `ui-linha-*`, cores extras de `ui-selo`). Depois conferiu:

- script que compara cada view com o HEAD (id, name, action, method, data-*, script,
  CSRF, permissões, on*=): 139 views e uma diferença, `#content-bemv` do painel, que só
  tinha uso no CSS;
- varredura de 67 telas sem erro de PHP, JS ou CSP (exceção já conhecida da base local:
  `nfe/configuracoes`, coluna `op_simp_nac`);
- `telas.mjs` com venda no PDV pelo F4;
- capturas em desktop e celular.

O resultado de cada modelo foi registrado no ruflo (`hooks model-outcome`) para o
roteamento aprender.

## Consequences

### Positive
- Mesma linguagem visual e mesmo lugar para título, busca e ações em todas as telas.
- Listas vazias explicam o que fazer; botões de ícone ganham rótulo ou dica.
- Tabelas utilizáveis no celular.

### Negative
- Temas antigos com regras muito específicas podem precisar de ajuste fino.

### Neutral
- Bootstrap 2 continua; a migração para Bootstrap 5 é outra decisão.

## Links
- **Depends on**: ADR-002, ADR-005 (usabilidade; CSP sem JavaScript inline)
