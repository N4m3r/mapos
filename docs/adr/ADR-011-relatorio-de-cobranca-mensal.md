# ADR-011: Relatório de cobrança do mês por cliente

- **Status**: accepted
- **Date**: 2026-10-25
- **Deciders**: N4m3r
- **Tags**: financeiro, cobranca, relatorios, impressao
- **Branch**: `seguranca-23-10`

## Context

No fim do mês é preciso saber o que cobrar de cada cliente. Hoje isso está espalhado
entre OS, vendas, contratos (mensalidades), cobranças (boleto/Pix) e lançamentos. Fica
difícil ver o que foi feito e não cobrado, o que não foi feito, o que ainda vai
acontecer, o que está aguardando pagamento e o que já foi recebido.

## Decision

Tela `relatoriocobranca` (permissão `rFinanceiro`), com filtros, tabelas por cliente e
impressão. Três partes feitas em paralelo, ligadas pelo contrato abaixo.

### Contrato de dados

`application/models/Relatorio_cobranca_model.php`:

- `itens(string $competencia /* 'YYYY-MM' */, array $filtros = []): array` devolve uma
  lista de itens. Cada item é um array com estas chaves:

  | Chave | Conteúdo |
  |-------|----------|
  | `origem` | `'os'` \| `'venda'` \| `'contrato'` \| `'lancamento'` |
  | `referencia_id` | int (idOs, idVendas, idContrato ou idLancamentos) |
  | `clientes_id` | int\|null |
  | `cliente` | string (nome; `'(sem cliente)'` se não houver) |
  | `descricao` | string curta (ex.: `'OS #12 - Notebook'`, `'Mensalidade Contrato #3 - 10/2026'`) |
  | `data_ref` | `'Y-m-d'`: data do serviço/venda ou vencimento da mensalidade |
  | `vencimento` | `'Y-m-d'`\|null: vencimento do lançamento/cobrança, se houver |
  | `valor` | float, valor a cobrar (com desconto) |
  | `situacao` | uma das 5 chaves da tabela de situações |
  | `detalhe` | string curta (ex.: `'Vencido há 5 dias'`, `'Agendada 28/10'`, `'Pago em 12/10'`, `'Boleto em aberto'`) |
  | `link` | string: caminho relativo para abrir o registro (ex.: `'os/visualizar/12'`) |

- `filtros`: `clientes_id` (int), `situacao` (string ou array de chaves) e `origem`
  (string). Todos opcionais.
- `public static function classificar(array $f, string $hoje): array` é uma função pura,
  sem banco, testável no PHPUnit. Recebe os fatos já levantados do item e devolve
  `['situacao' => ..., 'detalhe' => ...]`. Os fatos:

  | Fato | Valores |
  |------|---------|
  | `origem` | `'os'` \| `'venda'` \| `'contrato'` \| `'lancamento'` |
  | `status` | status da OS/venda (string) ou `''` |
  | `faturado` | bool |
  | `tem_lancamento` | bool |
  | `lancamento_pago` | bool |
  | `data_pagamento` | `'Y-m-d'`\|null |
  | `tem_cobranca_aberta` | bool |
  | `cobranca_paga` | bool |
  | `vencimento` | `'Y-m-d'`\|null |
  | `data_ref` | `'Y-m-d'` |

- `public function resumo(array $itens): array` devolve:
  - `totais`: situacao => ['qtd' => int, 'valor' => float];
  - `clientes`: lista ordenada por nome, cada um com `clientes_id`, `cliente`, `itens`
    (ordenados por `data_ref`) e `totais` (situacao => valor), mais `total_a_cobrar`
    (nao_cobrado + pendente).

### Situações (regras de `classificar`, nesta ordem)

| Chave | Rótulo | Regra |
|-------|--------|-------|
| `faturado` | Faturado e pago | lançamento pago **ou** cobrança paga |
| `pendente` | Pendente de pagamento | tem lançamento ou cobrança em aberto (não pago). Detalhe: "Vence dd/mm" ou "Vencido há N dias" |
| `nao_realizado` | Não realizado | OS com status `Não Realizado` ou `Cancelado`; venda `Cancelado` (informativo, não soma no "a cobrar") |
| `nao_cobrado` | Não cobrado | serviço concluído (OS `Finalizado`/`Faturado`, venda não cancelada) sem lançamento nem cobrança; ou mensalidade de contrato ativo vencida (`data_ref` <= hoje) sem lançamento |
| `a_realizar` | Será realizado | OS ainda em aberto (`Aberto`, `Orçamento`, `Negociação`, `Aprovado`, `Em Andamento`, `Aguardando Peças`) ou mensalidade futura (`data_ref` > hoje) sem lançamento. Detalhe: "Agendada dd/mm" quando houver agendamento |

### Fontes (o model levanta os fatos)

**OS**:
- entra na competência se `dataFinal`, `data_agendamento` ou `dataInicial` cair no mês
  (a primeira data preenchida, nesta ordem);
- valor com desconto pela regra de `Os_model::valorTotalOS` (`valor_desconto` quando
  `desconto` e `valor_desconto` forem diferentes de zero);
- lançamento: `os.lancamento` quando preenchido; senão o lançamento cuja descrição
  começa com `'Fatura de OS Nº: {id}'`;
- cobrança: `cobrancas.os_id`.

**Venda**:
- `dataVenda` no mês;
- lançamento por `vendas.lancamentos_id` ou `lancamentos.vendas_id`;
- cobrança por `cobrancas.vendas_id`.

**Contrato** ativo (`status = 'ativo'`):
- uma mensalidade por competência para o tipo `mensal`; os tipos
  trimestral/semestral/anual só no mês de vencimento, contado a partir de `data_inicio`;
- vencimento no dia `dia_vencimento` (1 a 28);
- lançamento pela descrição `Contratos_model::descricaoFatura($id, 'MM/AAAA')`.

**Lançamento avulso**:
- receitas com vencimento no mês que não pertencem a nenhum item acima;
- servem para mostrar pendentes e recebidos de outras origens.

**Cobrança paga**: status `PAID`, `RECEIVED`, `CONFIRMED`, `DUNNING_RECEIVED`, `paid` ou
`settled`. **Aberta**: qualquer outro status, exceto `CANCELLED`/`canceled`/`EXPIRED`.

### Tela

- Filtros:
  - mês (`competencia`, padrão: o mês atual);
  - cliente;
  - situação, em chips clicáveis com a contagem;
  - origem.
- Indicadores `ui-kpis` por situação.
- Uma tabela por cliente, com subtotal e o "Total a cobrar" do cliente.
- Botão **Imprimir** (`data-acao="imprimir"` do `acoes.js`) e CSS `@media print`: sem
  menu, cabeçalho com mês/filtros, quebra de página por cliente opcional.
- Sem JavaScript inline (CSP).

## Implementacao

| Parte | Modelo (ruflo `model-route`) | Arquivos |
|-------|------------------------------|----------|
| Regras e consultas | Sonnet (28%) | `models/Relatorio_cobranca_model.php` |
| Tela e impressão | Haiku (26%) | `controllers/Relatoriocobranca.php`, `views/relatoriocobranca/index.php` |
| Testes | Sonnet (34%) | `tests/Unit/RelatorioCobrancaClassificarTest.php` (13 casos), `tests/navegador/cobranca.mjs`, passo no Actions |

Ajustes da integração:

- **Contagens com filtro**: os cartões e chips contam o mês inteiro; o filtro de situação
  só recorta as tabelas, para as contagens não zerarem.
- **Filtros**: chips de situação numa linha própria, e "Limpar" visível.
- **Tabelas**: subtotais só das situações com valor; "(sem cliente)" no fim.
- **Impressão**: sem o endereço ao lado dos links, sem saudação e rodapé, A4 deitado,
  sem margem do layout e sem transições (a margem animada do tema deslocava o
  conteúdo).

O model aceita um fato extra opcional, `data_agendamento`, para o detalhe "Agendada
dd/mm".

## Verificação

Dados temporários do mês 10/2026, com todas as situações classificadas certo:

| Item | Situação |
|------|----------|
| OS concluída sem cobrança | Não cobrado |
| OS faturada e paga | Faturado e pago |
| OS faturada, vencida em 15/10 | Pendente, "Vencido há 10 dias" |
| OS não realizada | Não realizado |
| OS aguardando peças | Será realizado |
| Mensalidade do dia 5 sem lançamento | Não cobrado |
| Mensalidade do dia 28 | Será realizado |
| Lançamento avulso vencido | Pendente |

Testes:

- `cobranca.mjs`: 10 de 10 (filtro, mídia de impressão, PDF);
- PHPUnit: 66 testes OK.

## Consequences

### Positive
- O que cobrar no mês, por cliente, numa tela que também se imprime.

### Negative
- O vínculo OS → lançamento por descrição depende do texto padrão "Fatura de OS Nº:".
  Se alguém editar a descrição ao faturar, a OS conta como "não cobrada", a não ser que
  tenha `os.lancamento` ou uma cobrança ligada.

## Links
- **Depends on**: ADR-008, ADR-009 (financeiro e componentes ui-*)
