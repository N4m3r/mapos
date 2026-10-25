# ADR-010: Central de Atendimento moderna e prática

- **Status**: accepted
- **Date**: 2026-10-25
- **Deciders**: N4m3r
- **Tags**: ui, atendimento, despacho, tecnico
- **Branch**: `seguranca-23-10`

## Context

A Central de Atendimento (`os/atribuir`) é onde se distribuem chamados aos técnicos. Ela
mostrava contagens e cartões, mas não respondia às perguntas de quem despacha: o que está
atrasado, o que está marcado para hoje e quem está sobrecarregado. Ao revisar as telas
ligadas a ela, apareceram defeitos antigos no relatório de atendimentos.

## Decision

1. **Central** (`os/atribuir`):
   - indicadores "Atrasados" (agendamento já passou e a OS não foi concluída) e
     "Agendados hoje", clicáveis (`aba=atrasados` / `aba=hoje`);
   - painel "Carga por técnico" (OS abertas e de hoje, com barra proporcional),
     filtrável por técnico (`tecnico=ID`);
   - cartões com selos "Atrasado"/"Hoje" e ações com texto;
   - filtros com rótulo e estado vazio explicativo.

   Ids, classes, `data-*`, modais, chamadas AJAX e permissões ficaram iguais. Os valores
   nos atributos passaram a usar `(int)` e `html_escape`.
2. **Relatório de atendimentos**: layout `ui-*` e correção de quatro defeitos antigos que
   impediam o uso da tela:

   | Defeito | Efeito | Correção |
   |---------|--------|----------|
   | Exigia a sessão `id_usuario`, que o login nunca grava | Ninguém abria a tela | Usa a mesma permissão do menu, `vRelatorioAtendimentos` |
   | `CASE WHEN` passava pelo escape do Query Builder | Erro 1054 nas estatísticas | Escape desligado nesse `select` |
   | Chart.js nunca era carregado | "Chart is not defined", gráficos vazios | Biblioteca incluída, mesma versão do painel |
   | Tradução do DataTables vinha de um CDN por `http` | Bloqueada pela CSP | Usa o arquivo local `assets/js/dataTable_pt-br.json` |

   Junto: nome do técnico escapado no JSON da tabela, duração no formato `HH:MM` e
   status em `ui-selo`.
3. **Check-in na OS e painel de SLA**: dicas nos modais, botões grandes, selos e
   destaque das linhas em risco ou estouradas. O agente tinha criado um cartão de estado
   em `checkin/atendimento_ui.php`, mas esse arquivo é incluído depois do layout, só para
   os modais, e o cartão aparecia fora da página do técnico. O cartão foi retirado; a
   barra de ações da própria tela já faz esse papel.
4. **Modelos** escolhidos pelo `hooks model-route` do ruflo, cada um com sua
   complexidade medida:

   | Tela | Modelo | Complexidade |
   |------|--------|--------------|
   | Central | Sonnet | 37% |
   | Relatório | Haiku | 22% |
   | Check-in/SLA | Haiku | 28% |

   Os três rodaram em paralelo; a sessão principal integrou, corrigiu e testou.

## Verificação

Testado no Edge com dados temporários, depois restaurados:
- Central:
  - indicadores 3 abertos, 1 atrasado, 1 hoje;
  - carga do técnico 2 abertas, 1 hoje;
  - filtros novos;
  - atribuição pelo modal ("Técnico atribuído à OS #2");
  - troca de status inline.
- Relatório: abre com os gráficos e a tabela.
- OS do técnico sem o cartão fora do lugar.
- Celular conferido.

Nenhuma view perdeu `id`, `name`, `action` ou atributo `data-*`. Nenhum erro de JS, CSP
ou PHP.

## Consequences

### Positive
- O despacho enxerga atrasos, a agenda do dia e a carga de cada técnico numa tela.
- O relatório de atendimentos passa a funcionar.

### Negative
- "Atrasado" usa o relógio do servidor; o fuso do PHP precisa estar correto.

## Links
- **Depends on**: ADR-009 (componentes ui-* e camada moderno.css)
