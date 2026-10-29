# ADR-012: Acompanhamento de projetos (relatório diário/semanal/mensal e painel de gestão)

- **Status**: accepted
- **Date**: 2026-10-29
- **Deciders**: N4m3r
- **Tags**: projetos, obras, rdo, tecnico, relatorios, painel
- **Branch**: `seguranca-23-10`

## Context

O módulo de Projetos (`obras`, ADR do `docs/PROJETO-OBRAS.md`) já guarda tudo o que o
técnico faz no campo:

- `obra_rdo`: o registro do dia (atividades, ocorrências, condição, efetivo, assinatura),
  gravado pelo "registro rápido" do técnico (`tecnico/salvar_registro_projeto`) ou pelo
  RDO completo do gestor (`obras/salvarRdo`, `obras/registrarExecucao`);
- `obra_rdo_foto`: fotos de cada registro;
- `obra_consumo`: material e serviço lançados em cada registro (`rdo_id`, `usuario_id`);
- `obra_apontamento`: horas/diárias por pessoa;
- `obra_etapas`: cronograma e percentual;
- `obra_os`: OS vinculadas.

Falta transformar isso em acompanhamento: não há relatório por período do que foi feito,
o painel só mostra contagens e os últimos 8 RDOs, e o gestor não vê de relance quais
projetos estão atrasados, parados ou sem registro. O técnico também não tem um resumo do
que ele mesmo registrou.

## Decision

Nenhuma tabela nova. Um model de leitura, `Projeto_acompanhamento_model`, alimenta três
telas:

1. **Relatório de acompanhamento** (gestão): diário, semanal ou mensal, por projeto e por
   técnico, com fotos, e impressão em A4.
2. **Painel de Projetos** melhorado: semáforo de saúde por projeto, atividade dos últimos
   7 dias, técnicos que ainda não registraram hoje e ocorrências recentes.
3. **Meu relatório** na Área do Técnico: o mesmo relatório filtrado pelo técnico logado.

### Contrato de dados

`application/models/Projeto_acompanhamento_model.php` (`extends CI_Model`). Todas as
consultas protegidas por `table_exists`, como no `Obras_model`.

#### `public static function periodo(string $tipo, string $dataRef): array`

Função pura. `$tipo` é `diario`, `semanal` ou `mensal` (outro valor vira `semanal`).
`$dataRef` é `Y-m-d` (inválida vira a data de hoje). Devolve:

| Chave | Conteúdo |
|-------|----------|
| `tipo` | o tipo normalizado |
| `inicio`, `fim` | `Y-m-d`. Diário: o próprio dia. Semanal: segunda a domingo da semana de `$dataRef`. Mensal: dia 1 ao último dia do mês |
| `rotulo` | `'29/10/2026'`, `'Semana de 26/10 a 01/11/2026'` ou `'Outubro/2026'` |
| `anterior`, `proximo` | `Y-m-d` de referência do período anterior e do próximo (para as setas ‹ ›) |

#### `public static function saude(array $f, string $hoje): array`

Função pura. Fatos de um projeto:

| Fato | Valor |
|------|-------|
| `status` | status da obra (`planejamento`, `em_execucao`, `paralisada`, `concluida`, `entregue`, `cancelada`) |
| `percentual_concluido` | float |
| `data_inicio_prevista`, `data_fim_prevista` | `Y-m-d`\|null |
| `ultimo_registro` | `Y-m-d`\|null (data do último RDO) |
| `etapas_atrasadas` | int |
| `ocorrencias_7d` | int (RDOs dos últimos 7 dias com ocorrência preenchida) |

Devolve `['nivel' => ..., 'rotulo' => ..., 'motivos' => string[], 'esperado' => float|null]`.
`esperado` é o percentual de prazo decorrido (0 a 100), `null` sem as duas datas.

Regras, nesta ordem:

| Situação | Nível | Rótulo / motivo |
|----------|-------|-----------------|
| `concluida`, `entregue`, `cancelada` | `neutro` | "Concluído", "Entregue", "Cancelado"; sem motivos |
| `paralisada` | `parado` | "Paralisado"; motivo "Projeto paralisado" |
| `planejamento` com início previsto já passado | `atencao` | motivo "Início previsto em dd/mm não aconteceu" |
| `planejamento` sem isso | `ok` | "Em planejamento" |
| `em_execucao` (e qualquer outro status): soma os motivos abaixo; o nível é o pior deles | | |
| — fim previsto < hoje | `critico` | "Prazo vencido há N dias" |
| — `esperado - percentual` ≥ 20 | `critico` | "Avanço N pontos abaixo do previsto" |
| — `esperado - percentual` ≥ 10 | `atencao` | idem |
| — nenhum registro | `critico` | "Nenhum registro de execução" |
| — último registro há mais de 7 dias | `critico` | "Sem registro há N dias" |
| — último registro há mais de 2 dias | `atencao` | idem |
| — `etapas_atrasadas` > 0 | `atencao` | "N etapa(s) atrasada(s)" |
| — `ocorrencias_7d` > 0 | `atencao` | "N ocorrência(s) nos últimos 7 dias" |
| — sem motivos | `ok` | "Em dia" |

Rótulos dos níveis: `ok` "Em dia", `atencao` "Atenção", `critico` "Crítico",
`parado` "Paralisado", `neutro` conforme o status. Etapa atrasada: `data_fim_prevista` <
hoje e `percentual_concluido` < 100 e status diferente de `concluida`.

#### `public function relatorio(string $inicio, string $fim, array $filtros = []): array`

Filtros opcionais: `obra_id` (int), `usuario_id` (int: só registros do técnico, ou seja,
`obra_rdo.responsavel_id`, e consumo dos RDOs dele). Devolve:

```php
[
  'projetos' => [            // só projetos com registro no período (ou o obra_id filtrado), por nome
    [
      'obra' => ['idObra','nome','codigo','cliente','status','percentual_concluido',
                 'responsavel','data_fim_prevista'],
      'registros' => [       // RDOs do período, por data e idRdo
        ['idRdo','numero','data','responsavel','atividades','ocorrencias','condicao',
         'efetivo_total','fotos' => int[] /* idFoto */,
         'materiais' => [['descricao','quantidade']], 'servicos' => [['descricao','quantidade']]],
      ],
      'materiais' => [['descricao','quantidade']],   // somados no período
      'servicos'  => [['descricao','quantidade']],
      'horas'     => [['nome','horas','valor']],     // obra_apontamento.data no período; vazio com usuario_id
      'etapas'    => [['nome','percentual_concluido','status','data_fim_prevista','atrasada' => bool]],
      'os'        => [['idOs','status','descricao']],
      'totais'    => ['registros','fotos','ocorrencias','horas','dias_com_registro','tecnicos'],
    ],
  ],
  'sem_registro' => [['idObra','nome','cliente','ultimo_registro']], // em_execucao sem RDO no período (vazio com filtros)
  'tecnicos' => [['usuario_id','nome','registros','projetos','dias','ultimo']], // por responsavel_id dos RDOs
  'totais' => ['projetos','registros','fotos','ocorrencias','horas','tecnicos'],
]
```

Valores numéricos como `int`/`float`, datas `Y-m-d`, textos crus (a view escapa).

#### `public function painel(string $hoje): array`

```php
[
  'projetos' => [   // status planejamento, em_execucao, paralisada; ordem: critico, atencao, parado, ok, depois nome
    ['idObra','nome','cliente','responsavel','status','percentual_concluido','data_fim_prevista',
     'ultimo_registro','dias_sem_registro' /* int|null */,'etapas_atrasadas','ocorrencias_7d',
     'registros_7d','saude' => saude()],
  ],
  'contagem' => ['ok' => int, 'atencao' => int, 'critico' => int, 'parado' => int],
  'atividade_7d' => [['data' => 'Y-m-d', 'registros' => int]],  // 7 itens, de hoje-6 a hoje
  'tecnicos_hoje' => [   // usuários vinculados (obra_usuario) ou responsáveis de projetos em_execucao
    ['usuario_id','nome','projetos' => int,'registrou_hoje' => bool,'ultimo' => 'Y-m-d'|null],
  ],                     // primeiro quem não registrou hoje
  'ocorrencias_recentes' => [['idRdo','obra_id','obra_nome','data','responsavel','ocorrencias']], // 7 dias, até 10
]
```

### Telas

| Tela | Rota | Permissão | Arquivos |
|------|------|-----------|----------|
| Relatório de acompanhamento | `relatorioprojetos?tipo=semanal&data=Y-m-d&obra_id=&usuario_id=` | `vObras` | `controllers/Relatorioprojetos.php`, `views/relatorioprojetos/index.php`, aba "Relatório" em `views/obras/_nav.php` |
| Painel de Projetos | `obras/painel` | `vObras` | `Obras::painel()` e `views/obras/painel.php` |
| Meu relatório (técnico) | `tecnico/meu_relatorio?tipo=&data=` | login do técnico | `Tecnico::meu_relatorio()`, `views/tecnico/meu_relatorio.php`, atalhos em `tecnico/projetos.php` e `tecnico/projeto.php` |

Regras comuns:

- Seletor de período em botões (Dia / Semana / Mês) e setas ‹ › com `anterior`/`proximo`.
- Componentes `ui-*` existentes (ADR-009): `ui-cabecalho`, `ui-kpis`, `ui-selo`,
  `ui-vazio`, `ui-tabela-rolagem`.
- Fotos pela rota existente `obras/fotoRdo/{idFoto}`.
- Imprimir com `data-acao="imprimir"` (`assets/js/acoes.js`) e `@media print` (A4, sem
  menu), como no ADR-011.
- Sem JavaScript inline sem `nonce` (CSP); scripts com `nonce="<?= csp_nonce() ?>"`.
- Toda saída de texto com `html_escape`.

### Correções junto

- `Tecnico::salvar_registro_projeto` usa `$idOs`, que não existe, na origem da baixa de
  estoque. Passa a usar `['tipo' => 'projeto', 'referencia' => $obra_id]`.
- `Obras::fotoRdo` não confere permissão: passa a exigir `vObras` ou acesso ao projeto
  do RDO (`usuarioTemAcesso`), para o técnico ver as fotos no "Meu relatório".

## Implementacao

| Parte | Modelo (ruflo `model-route`) | Arquivos |
|-------|------------------------------|----------|
| Regras e consultas + testes | Sonnet (34%) | `models/Projeto_acompanhamento_model.php`, `tests/Unit/ProjetoAcompanhamentoTest.php` |
| Relatório de gestão e impressão | Haiku (26%) | `controllers/Relatorioprojetos.php`, `views/relatorioprojetos/index.php`, `views/obras/_nav.php` |
| Painel de Projetos | Haiku (26%) | `Obras::painel()`, `views/obras/painel.php` |
| Meu relatório do técnico + correção | Sonnet (26%) | `Tecnico.php`, `views/tecnico/meu_relatorio.php`, atalhos |

As quatro partes rodaram em paralelo contra o contrato acima; a sessão principal integrou,
corrigiu e testou. O resultado de cada modelo foi registrado no ruflo
(`hooks model-outcome`): os quatro com sucesso.

Ajustes da integração:

- **Navegação**: o relatório não incluía `obras/_nav`, embora o agente tenha dito que sim;
  a aba "Relatório" agora aparece ativa.
- **Rótulos**: status de etapa (`em_andamento` → "Em andamento") e condição do RDO
  (`praticavel` → "Praticável") no relatório e nos diários recentes do painel.
- **Botões**: "Relatório da semana" e "Relatório" do painel estavam sem classe de cor e
  quase invisíveis; agora `btn-info`.
- **Rótulo de estoque**: a origem `projeto` entrou nas telas de movimentação de produto.

Interpretações do model, mantidas:

- `anterior`/`proximo` apontam para o início do período vizinho (evita o estouro de
  31/03 menos 1 mês).
- `etapas[].atrasada` no `relatorio()` usa a data de hoje, não o fim do período.
- `sem_registro` só é calculado sem filtro de projeto ou técnico.

## Verificação

Dados temporários (4 projetos, 6 RDOs, consumo, horas, etapas, fotos), depois removidos:

| Projeto | Situação esperada | Painel |
|---------|-------------------|--------|
| CFTV, fim previsto 20/10, último registro 18/10 | Crítico | "Prazo vencido há 9 dias", "Avanço 20 pontos abaixo", "Sem registro há 11 dias" |
| Reforma, 30% com 47% do prazo, etapa vencida, ocorrência | Atenção | "17 pontos abaixo", "1 etapa atrasada", "1 ocorrência" |
| Rede, planejamento com início em 25/10 | Atenção | "Início previsto em 25/10 não aconteceu" |
| Galpão, paralisada | Paralisado | "Projeto paralisado" |

- Relatório semanal: 4 registros, 10 RJ45 e 8 serviços somados, 14 h, CFTV em "sem
  registro no período"; HTML digitado no RDO aparece escapado.
- Meu relatório (técnico): só os 3 registros dele, sem o do gestor.
- Fotos: o técnico abre a foto do projeto dele e recebe 404 na de outro projeto; sem
  login, redireciona.
- O técnico não abre `relatorioprojetos` nem `obras/painel`.
- `tests/navegador/projetos.mjs`: 19 de 19 (painel, períodos, setas, impressão sem
  filtros, PDF, celular sem rolagem horizontal, sem erro de JS/CSP). Passo no Actions.
- PHPUnit: 91 testes OK (25 novos).

## Consequences

### Positive
- O gestor vê numa tela o que foi feito em cada projeto no dia, na semana ou no mês, e
  imprime para o cliente ou para a reunião.
- O painel aponta onde agir: atrasados, parados, sem registro, com ocorrência.
- O técnico confere o próprio trabalho sem pedir ao escritório.

### Negative
- "Sem registro" depende de o técnico registrar no app; projetos acompanhados só por OS
  aparecem como sem registro.
- As horas por técnico vêm do apontamento, que usa colaborador/nome e não o usuário; no
  "Meu relatório" elas não aparecem.

## Links
- **Depends on**: ADR-009 (componentes `ui-*`), ADR-011 (padrão de relatório e impressão)
