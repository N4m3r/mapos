# ADR-020: Relatório de OS realizadas (período, painel e impressão)

- **Status**: accepted
- **Date**: 2026-10-31
- **Deciders**: N4m3r
- **Tags**: relatorios, projetos, checkin, atendimento, impressao, permissoes
- **Branch**: `master`

## Context

O relatório de atendimentos (`relatorioatendimentos`) lista os check-ins um a um. Não há visão agregada
por período, nem painel com indicadores, nem gráficos, nem versão para impressão. Para responder
"quantas OS foram atendidas nesta semana, por quem e com que resultado" era preciso montar a conta
manualmente a partir da lista. O relatório de projetos (ADR-012 a ADR-016) já tem painel com gráficos
SVG e fotos do período, e serve de modelo.

## Decision

Criar a página `relatorioos` com um relatório de **OS realizadas**: OS com atendimento (check-in) no período.

1. **Escopo**: uma OS entra no relatório se tiver pelo menos um atendimento (check-in) com data de
   entrada dentro do período. Diário, semanal e mensal são atalhos de período sobre o mesmo relatório.
2. **KPIs**: OS atendidas, atendimentos, concluídos, horas em atendimento, fotos, finalizadas,
   não realizadas e técnicos. Cada KPI mostra a comparação com o período anterior de mesmo tamanho.
3. **Gráficos**: SVG, reaproveitando `Grafico_svg` do relatório de projetos (sem biblioteca nova).
4. **Fotos**: fotos dos atendimentos do período, na tela e na impressão.
5. **Impressão**: versão própria para impressão, no mesmo padrão do relatório de projetos.
6. **Filtros**: técnico, cliente e status.
7. **Permissão**: `rOs`. Sem ela, a rota e o item de menu não aparecem.
8. **Menu e atalhos**: item "OS realizadas" no menu lateral (visível com `rOs`) e atalho na página de
   relatórios de OS (`relatorios/os`).

## Implementacao

| Parte | Arquivos |
|---|---|
| Menu lateral | `views/tema/menu.php` |
| Atalho na página de OS | `views/relatorios/rel_os.php` |
| Relatório | `controllers/Relatorioos.php`, `models/Relatorio_os_model.php`, `views/relatorioos/` |
| Gráficos | `libraries/Grafico_svg.php` (existente) |

## Consequences

### Positive
- Visão do período (diário, semanal, mensal) com comparação ao período anterior, sem montar planilha.
- Reuso de `Grafico_svg` e do padrão de impressão do relatório de projetos.
- Mesma permissão (`rOs`) na rota, no menu e no atalho.

### Negative
- Não existe histórico de status da OS. "Finalizada" usa o status atual da OS, então uma OS reaberta ou
  alterada depois do período aparece com o status de hoje.
- Horas em atendimento = saída − entrada do check-in. Check-ins sem saída não entram nessa soma.
- Uma OS com vários atendimentos no período conta uma vez em "OS atendidas" e várias em "atendimentos".

## Links
- **Depends on**: ADR-012, ADR-015, ADR-016
- **Related**: relatório de atendimentos (`relatorioatendimentos`)
