# ADR-015: Ponte entre Ordem de Serviço e planejamento do projeto

- **Status**: accepted (Fases 1 a 4 implementadas; fase 5 implementada, alerta no painel pendente)
- **Date**: 2026-10-29
- **Deciders**: N4m3r
- **Tags**: projetos, os, cronograma, etapas, sincronizacao, faturamento
- **Branch**: `seguranca-23-10`

## Context

O projeto (`obras`) e a OS (`os`) já se ligam pela tabela `obra_os` (projeto, OS, etapa),
mas a ponte é rasa:

- O vínculo só é feito a partir do projeto. A OS não sabe a que projeto pertence
  (`Os.php`, `Os_model.php` e `views/os/` não falam de projeto).
- `obra_os` não tem UNIQUE em `os_id`: a mesma OS pode cair em dois projetos.
- Status, horas, material e pontos da OS não chegam ao projeto. O progresso da etapa é
  manual e o relatório do ADR-012 mostra "sem registro" para projetos tocados só por OS.
- O status da OS muda em cerca de 12 lugares (Os, Checkin, Tecnico, Não Realizado,
  portal do cliente), sem um ponto único para reagir à mudança.

## Decision

A ponte passa a funcionar nos dois sentidos e o projeto se atualiza sozinho.

1. **Dados**: `obra_os` ganha `tipo_vinculo` (origem, execucao, adicional, corretiva),
   `fatura_no_projeto` e `data_vinculo`, com UNIQUE em `os_id`. `os_id` opcional em
   `obra_apontamento`, `obra_consumo` e `obra_ponto_historico`. Novas tabelas
   `obra_etapa_itens` (itens previstos da etapa) e `obra_ponto_os` (pontos de cada OS).
2. **Status centralizado**: toda mudança de status de OS passa por
   `Os_model::alterarStatus()` ou `Os_model::notificarMudancaStatus()`, que chamam a
   library `Projeto_sync`. Erro na sincronização é registrado em log e nunca quebra a OS.
3. **Projeto → OS**: gerar OS a partir da etapa (cliente, agendamento, equipe e itens
   previstos) e em lote por etapas ou por pontos.
4. **OS → Projeto**: converter OS aprovada em projeto (serviços viram etapas com peso
   pelo valor, produtos viram pontos); vincular a projeto/etapa pela tela da OS.
5. **Sincronização**: OS em andamento inicia a etapa; check-out lança horas; produtos
   da OS baixam do almoxarifado do projeto; pontos marcados na OS entram no histórico.
   Quando todas as OS da etapa terminam, a etapa conclui sozinha **só se o projeto não
   tiver pontos pendentes**; caso contrário fica como sugestão para o gestor.
6. **Faturamento**: OS com `fatura_no_projeto` não fatura sozinha, entra na medição.
   OS `adicional` fatura separada, como hoje.

## Implementacao

| Fase | Parte | Situação |
|---|---|---|
| 1 | Migration, `Projeto_sync`, status centralizado, vínculo/selo/filtro na OS | feito |
| 2 | Itens previstos da etapa, gerar OS da etapa, em lote e por pontos (`Obra_planejamento_model`) | feito |
| 3 | Converter OS aprovada em projeto (etapas por servico ou padrao, itens e pontos) | feito |
| 4 | Horas do check-out, material do almoxarifado do projeto, pontos pela OS, sugestão de conclusão, relatório ADR-012 com atividade de OS | feito |
| 5 | Faturamento por medição (`Obra_medicao_model`, `Obras::faturarMedicao`, migration `application/database/migrations/20261031000000_add_medicao_os.php`) e alertas no painel | parcial: faturamento feito; alerta no painel pendente (`Obra_medicao_model::resumoFinanceiro()` devolve `os_aguardando`, mas nenhuma view o exibe) |

## Verificação

- PHPUnit: `tests/Unit/ProjetoSyncTest.php` (decisão da sincronização),
  `tests/Unit/ObraPlanejamentoTest.php` (descrição, totais e filtro do lote) e
  `tests/Unit/ObraMedicaoTest.php` (valor das OS, cálculo das etapas e medição).
- Navegador: vincular OS pela tela da OS, mudar status e ver a etapa no projeto.

## Consequences

### Positive
- O cronograma reflete o que acontece em campo sem lançamento duplo.
- Um único ponto para reagir à mudança de status (serve também para notificações).
- Custo realizado por etapa passa a incluir o que foi feito via OS.

### Negative
- Mais regras acopladas à OS: qualquer novo ponto de mudança de status precisa usar
  `alterarStatus()`, ou o projeto fica desatualizado.
- A migration remove vínculos duplicados de `os_id` (mantém o mais antigo).

## Links
- **Depends on**: ADR-012, ADR-013

## Notas da fase 4

- Horas: lancadas em `obra_apontamento` no check-out (painel e portal do tecnico, inclusive
  "nao realizado"); a marca `checkin:<id>` em `observacao` evita lancamento duplicado.
- Material: OS com `fatura_no_projeto` baixa de `obra_material_saldo` e registra `obra_consumo`
  com `os_id`; o estoque geral nao muda. Saldo insuficiente avisa e nao bloqueia.
- Cancelar, reabrir e excluir a OS: feito. `Os::editar` e `Os::excluir` (`application/controllers/Os.php`)
  consultam `Projeto_sync::decidirDestinoEstoque()` e, se a OS fatura no projeto, chamam
  `devolverMaterialDaOs()` / `debitarMaterialDaOs()` (almoxarifado do projeto); a exclusão também
  chama `removerVinculoDaOs()`.

## Notas da fase 5

- Medição: `Obra_medicao_model::criarMedicao()` trava o projeto e liga as OS (`obra_medicao_os`);
  uma OS entra em uma única medição. `faturarMedicao()` gera o lançamento e o valor das OS adicionais
  fatura separado, como antes.
- Pendente: exibir no painel do projeto as OS aguardando medição (hoje só existe no retorno de
  `resumoFinanceiro()`).
