# ADR-008: Gestão e financeiro - painel, margem por OS, compra sugerida e conciliação

- **Status**: accepted
- **Date**: 2026-10-25
- **Deciders**: N4m3r
- **Tags**: gestao, financeiro, estoque, relatorios, roadmap
- **Branch**: `seguranca-23-10`

## Context

Item 6 do roteiro do ADR-007 (gestão e financeiro). O sistema já registra OS, peças e
serviços, check-in do técnico (`os_checkin`), movimentação de estoque
(`estoque_movimentacoes`, ADR-005) e lançamentos financeiros (`lancamentos`). O que falta
são visões de gestão sobre esses dados:

1. Painel gerencial com indicadores (tempo de atendimento, SLA, retrabalho, ticket médio,
   faturamento por técnico e por serviço).
2. Custo real e margem por OS (custo das peças mais as horas do técnico).
3. Estoque: ponto de pedido e compra sugerida pelo consumo.
4. Conciliação bancária por arquivo OFX contra os lançamentos.

## Decision

Cada item é um módulo próprio (controller + model + view), sem alterar os módulos
existentes, no padrão do ADR-003. Só se lê o banco que já existe; tabela nova apenas
onde for preciso.

1. **Painel gerencial** `painelgerencial` (permissão `vDashboard` + `rOs`): KPIs do
   período com filtro de datas.
2. **Margem por OS** `margem` (`rFinanceiro`):
   - custo das peças = `produtos.precoCompra` x quantidade;
   - custo da mão de obra = horas de check-in x custo-hora configurável;
   - receita = total da OS com desconto;
   - visão por OS, por técnico e por cliente.
3. **Compra sugerida** `comprasugerida` (`rProduto`):
   - consumo diário médio das saídas em `estoque_movimentacoes` (janela configurável,
     padrão de 90 dias);
   - ponto de pedido = consumo diário x prazo de reposição + `estoqueMinimo`;
   - quantidade sugerida para cobrir o período;
   - exportação em CSV.
4. **Conciliação bancária** `conciliacao` (`eLancamento`):
   - importa OFX (parser próprio, testado no PHPUnit);
   - sugere o lançamento em aberto de mesmo valor e data próxima;
   - baixa o que o usuário confirmar;
   - guarda o FITID importado para não processar duas vezes.

Tudo em POST tem CSRF. Nada de JavaScript inline (CSP do ADR-005). As telas entram na
varredura de `tests/navegador/telas.mjs`.

## Implementacao

Os módulos foram feitos em paralelo por 4 agentes Sonnet, com arquivos separados e sem
editar arquivos compartilhados. A integração (menu Relatórios, varredura de telas) e os
testes ficaram com a sessão principal.

| Módulo | Arquivos | Teste local (Edge, dados temporários) |
|--------|----------|----------------------------------------|
| Painel | `Painelgerencial` (controller/model/view) | 1 aberta, 1 concluída, 2,0 h, 29 dias, ticket R$ 25 (confere com o SQL) |
| Margem | `Margem` + migration `custo_hora_tecnico` | receita 25, peças 5, 2 h x R$ 50, margem -80 (-320%) |
| Compra | `Comprasugerida` (+ CSV) | estoque 2, mínimo 1, 9 saídas/90 dias: ponto 2,5, sugere 4 (R$ 20) |
| Conciliação | `Conciliacao`, `Ofx_parser` + migration `conciliacao_itens` | importação de 2 itens, reimportação ignorada, conciliar baixa o lançamento na data do extrato, ignorar |

PHPUnit: 53 testes OK (3 do parser OFX). Nenhuma tela com erro de PHP, JS ou CSP.

## Consequences

### Positive
- Decisões com base em dados que o sistema já coleta, sem digitação extra.

### Negative
- A margem depende de `precoCompra` estar preenchido e do check-in do técnico. Sem esses
  dados o relatório mostra custo zero e avisa.

### Neutral
- Fila de envio e backup externo seguem para um ADR próprio.

## Links
- **Depends on**: ADR-003, ADR-005, ADR-007 (extracao em modulos; CSP com nonce e estoque_movimentacoes; roteiro de melhorias)
