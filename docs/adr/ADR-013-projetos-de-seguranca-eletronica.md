# ADR-013: Projetos de segurança eletrônica (pontos, etapas de instalação e as-built)

- **Status**: accepted
- **Date**: 2026-10-29
- **Deciders**: N4m3r
- **Tags**: projetos, cftv, controle-de-acesso, rede-estruturada, tecnico, relatorios
- **Branch**: `seguranca-23-10`

## Context

A empresa trabalha com segurança eletrônica: CFTV (câmeras, NVR), passagem de cabo,
montagem de rack, controle de acesso (leitores, fechaduras, botoeiras), alarme,
interfonia e rede estruturada. O módulo de Projetos (ADR-012) nasceu com o vocabulário
de obra civil: Diário de Obra (RDO) com clima e efetivo, condição "praticável" e
percentual de avanço digitado à mão nas etapas.

Nesse tipo de serviço o avanço é contado por **ponto**: a câmera CAM-07 teve o cabo
passado, foi instalada, configurada no NVR (canal 7, IP 192.168.1.107) e testada. O
gestor quer saber quantas câmeras já estão testadas, quantos pontos de rede faltam
certificar e quantos metros de cabo foram passados na semana. No fim, o cliente recebe a
lista dos pontos com local, IP, canal e porta (as-built).

## Decision

1. **Pontos do projeto**: cada dispositivo ou ponto de rede vira um registro com tipo,
   identificação, local, dados técnicos e status de instalação. Cada mudança de status
   fica no histórico, com técnico, data e metros de cabo.
2. **Progresso automático**: se o projeto tem pontos, o percentual do projeto é a média
   dos pesos do status dos pontos. Sem pontos, continua o cálculo pelas etapas.
3. **Etapas padrão por tipo de serviço**: um botão cria as etapas usuais (infraestrutura,
   passagem de cabos, rack, instalação, configuração, testes, entrega).
4. **Técnico no celular**: avança o status de um ponto com um toque e preenche IP, canal,
   porta e metros quando fizer sentido.
5. **Relatórios e painel** passam a mostrar os avanços dos pontos no período.
6. **Vocabulário**: "Diário de Obra/RDO" vira "Registro de execução"; clima sai da tela;
   "efetivo" vira "técnicos no local"; condição vira Normal / Impedimento parcial /
   Sem acesso. As colunas e chaves no banco não mudam.

### Banco (migração `20261029000000_add_obra_pontos.php`, idempotente como as outras)

`obra_pontos`:

| Coluna | Tipo |
|--------|------|
| `idPonto` | INT UNSIGNED AI PK |
| `obra_id` | INT UNSIGNED, índice |
| `tipo` | VARCHAR(20) |
| `identificacao` | VARCHAR(30) (ex.: `CAM-07`) |
| `local` | VARCHAR(150) NULL |
| `ip` | VARCHAR(45) NULL |
| `canal` | VARCHAR(10) NULL (canal do NVR / porta do controlador) |
| `porta` | VARCHAR(30) NULL (patch panel / porta do switch) |
| `modelo` | VARCHAR(80) NULL |
| `serie` | VARCHAR(60) NULL (série ou MAC) |
| `metragem_cabo` | DECIMAL(8,2) default 0 (total acumulado) |
| `status` | VARCHAR(15) default `previsto` |
| `observacao` | VARCHAR(255) NULL |
| `atualizado_por` | INT UNSIGNED NULL |
| `atualizado_em` | DATETIME NULL |
| `data_cadastro` | DATETIME NULL |

`obra_ponto_historico`:

| Coluna | Tipo |
|--------|------|
| `idHistorico` | INT UNSIGNED AI PK |
| `ponto_id` | INT UNSIGNED, índice |
| `obra_id` | INT UNSIGNED, índice composto com `data` |
| `status_anterior` | VARCHAR(15) NULL |
| `status_novo` | VARCHAR(15) |
| `metragem_cabo` | DECIMAL(8,2) default 0 (metros passados nesta mudança) |
| `usuario_id` | INT UNSIGNED NULL |
| `data` | DATETIME |
| `observacao` | VARCHAR(255) NULL |

Permissões: usa as existentes. Cadastro, lote, exclusão e etapas padrão: `eObras`.
Ver e imprimir o as-built: `vObras`. Mudar status: `eObras` **ou** acesso ao projeto
(`Obras_model::usuarioTemAcesso`).

### Contrato do model `application/models/Obra_pontos_model.php`

Funções puras (estáticas, sem banco, cobertas no PHPUnit):

- `tipos(): array` → chave => `['rotulo' => ..., 'prefixo' => ..., 'icone' => 'bx-...']`, nesta ordem:

  | Chave | Rótulo | Prefixo |
  |-------|--------|---------|
  | `camera` | Câmera | CAM |
  | `ponto_rede` | Ponto de rede | PR |
  | `leitor_acesso` | Leitor / controle de acesso | LEI |
  | `fechadura` | Fechadura / eletroímã | FEC |
  | `botoeira` | Botoeira | BOT |
  | `sensor` | Sensor de alarme | SEN |
  | `interfone` | Interfone / vídeo porteiro | INT |
  | `ap_wifi` | Access point | AP |
  | `rack` | Rack | RACK |
  | `switch` | Switch | SW |
  | `nvr` | NVR / DVR | NVR |
  | `outro` | Outro | PT |

- `statusLista(): array` → chave => `['rotulo', 'peso', 'acao', 'classe']`, nesta ordem:

  | Chave | Rótulo | Peso | Ação (botão do técnico) | Classe `ui-selo` |
  |-------|--------|------|-------------------------|------------------|
  | `previsto` | Previsto | 0 | — | `neutro` |
  | `infra` | Infraestrutura pronta | 20 | Infra pronta | `info` |
  | `cabeado` | Cabo passado | 40 | Cabo passado | `info` |
  | `instalado` | Instalado | 70 | Instalei | `alerta` |
  | `configurado` | Configurado | 90 | Configurei | `alerta` |
  | `testado` | Testado / certificado | 100 | Testei | `sucesso` |

- `proximoStatus(string $atual): ?string` → o seguinte na ordem; `null` em `testado` ou
  status desconhecido.
- `progresso(array $pontos): ?float` → média dos pesos (cada item com chave/propriedade
  `status`), 1 casa; `null` se vazio.
- `resumo(array $pontos): array` →
  `['total' => int, 'por_status' => [status => int] /* todas as chaves */, 'por_tipo' => [tipo => ['rotulo', 'total', 'testados', 'por_status' => [...]]] /* só tipos presentes, na ordem de tipos() */, 'progresso' => ?float, 'cabo_m' => float]`.
  Aceita arrays ou objetos.
- `gerarIdentificacoes(string $prefixo, int $inicio, int $qtd): array` → `['CAM-01', ...]`;
  2 dígitos, 3 se o último número passar de 99. `$qtd` limitado a 1..500.
- `etapasPadrao(string $tipoProjeto): array` → nomes de etapas, escolhidos pelo texto do
  tipo (sem acento e sem caixa):
  - contém `cftv` ou `camera`: Infraestrutura (eletroduto/eletrocalha); Passagem de
    cabos; Montagem do rack; Instalação das câmeras; Configuração do NVR e
    acesso remoto; Testes e ajuste de imagem; Entrega e treinamento;
  - contém `acesso`: Infraestrutura; Passagem de cabos; Instalação de leitores,
    fechaduras e botoeiras; Instalação e configuração da controladora; Cadastro de
    usuários e regras; Testes; Entrega e treinamento;
  - contém `rede` ou `fibra`: Infraestrutura; Passagem de cabos; Montagem do rack e
    patch panel; Conectorização; Certificação dos pontos; Identificação e documentação;
    Entrega;
  - contém `alarme`: Infraestrutura; Passagem de cabos; Instalação de sensores e
    central; Programação e monitoramento; Testes; Entrega e treinamento;
  - outro texto: Infraestrutura; Passagem de cabos; Instalação; Configuração; Testes;
    Entrega.
  - Se o tipo combinar mais de um (ex.: "Rede estruturada + CFTV IP"), junta as listas
    sem repetir nomes, mantendo a primeira ocorrência.

Consultas (com `table_exists`, Query Builder com binding):

- `getPontos(int $obra_id, array $filtros = []): array` (objetos; filtros `tipo`,
  `status`; ordem pela ordem de `tipos()` e depois `identificacao`).
- `getPonto(int $id): ?object`
- `addPonto(array $d): int`, `editPonto(int $id, int $obra_id, array $d): bool`,
  `excluirPonto(int $id, int $obra_id): bool` (apaga o histórico do ponto).
- `gerarLote(int $obra_id, string $tipo, string $prefixo, int $inicio, int $qtd, ?string $local = null): int`
  (cria os que não existem com a mesma identificação no projeto; devolve quantos criou).
- `mudarStatus(int $ponto_id, int $obra_id, string $status, ?int $usuario_id, array $extras = []): bool`
  - `$extras`: `ip`, `canal`, `porta`, `modelo`, `serie`, `observacao` (só grava os
    não vazios) e `metragem_cabo` (metros desta etapa, somados ao total do ponto e
    gravados no histórico);
  - grava o histórico mesmo se o status não mudar mas vierem metros ou dados;
  - depois chama `recalcularProgressoObra`.
- `getHistorico(int $obra_id, ?string $inicio = null, ?string $fim = null, ?int $usuario_id = null): array`
  → objetos com `idHistorico, ponto_id, identificacao, tipo, local, status_anterior,
  status_novo, metragem_cabo, usuario_id, usuario /* nome */, data, observacao`, ordem
  `data DESC`. `$inicio`/`$fim` em `Y-m-d`, inclusivos.
- `recalcularProgressoObra(int $obra_id): ?float` → se houver pontos, grava
  `obras.percentual_concluido` com `progresso()` e devolve; senão `null` sem gravar.
- `aplicarEtapasPadrao(int $obra_id, string $tipoProjeto): int` → cria em `obra_etapas`
  as etapas de `etapasPadrao()` que ainda não existem (por nome), com `ordem`
  sequencial após as existentes; devolve quantas criou.

### Telas e donos

| Parte | Arquivos (só estes) |
|-------|---------------------|
| **A. Banco e model** | migração, `models/Obra_pontos_model.php`, `tests/Unit/ObraPontosTest.php` |
| **B. Gestão e as-built** | `controllers/Obras.php` (métodos novos + dados em `visualizar()`), `views/obras/visualizar.php`, `views/obras/_form.php`, `views/obras/gerenciar.php`, `views/obras/asbuilt.php` (nova), `assets/css/obras.css` (seção nova no fim) |
| **C. Técnico** | `controllers/Tecnico.php`, `views/tecnico/projeto.php`, `views/tecnico/meu_relatorio.php`, `views/tecnico/projetos.php` |
| **D. Relatório, painel e textos** | `models/Projeto_acompanhamento_model.php`, `tests/Unit/ProjetoAcompanhamentoTest.php`, `views/relatorioprojetos/index.php`, `views/obras/painel.php`, `views/obras/imprimir_rdo.php`, `views/rdo/*.php`, `libraries/Notificador.php` (só textos), `views/notificacoes/*.php` e `models/Notification_triggers_model.php` (só rótulos) |

**B. Gestão** (`eObras` para alterar):

- Aba **Pontos**, logo depois de "Visão geral":
  - resumo por tipo com barra de progresso e "N de M testados";
  - filtros por tipo e status;
  - tabela com identificação, tipo, local, IP, canal, porta, metros, status (selo) e
    atualizado por/em;
  - ações: editar (modal), excluir, mudar status (select + metros);
  - botões "Novo ponto", "Gerar em lote" (tipo, prefixo preenchido pelo tipo, número
    inicial, quantidade, local) e "Imprimir as-built".
- POST em `Obras`: `salvarPonto`, `gerarPontos`, `excluirPonto`, `statusPonto`; GET
  `asbuilt/{id}`. Todos redirecionam para `obras/visualizar/{id}#pontos`.
- Na aba Cronograma, botão "Criar etapas padrão" (`aplicarEtapasPadrao`) quando houver
  `tipo_obra`.
- `asbuilt`: página de impressão A4 com cabeçalho do emitente (como `imprimir_rdo`), dados
  do projeto, resumo por tipo e tabela por tipo (identificação, local, IP, canal, porta,
  modelo, série, metros, status, data do teste). Botão Imprimir.
- Vocabulário em `visualizar.php`:
  - aba "Diário de Obra" → "Registros de execução";
  - "Novo RDO" → "Novo registro";
  - no modal, sem clima; "Efetivo total" → "Técnicos no local";
  - condição: `praticavel` → "Normal", `parcial` → "Impedimento parcial",
    `impraticavel` → "Sem acesso".
- `_form.php`: incluir nas opções de tipo "Alarme", "Interfonia / vídeo porteiro",
  "Controle de acesso + CFTV IP" e "Infraestrutura (eletrocalha/eletroduto)".

**C. Técnico** (acesso por `usuarioTemAcesso`):

- `Tecnico::projeto()` passa `pontos`, `pontos_resumo`, `tipos` e `status_lista`.
- `POST tecnico/ponto_status`:
  - campos `obra_id`, `ponto_id`, `status`, `ip`, `canal`, `porta`, `metragem_cabo` e
    `observacao`;
  - confere o acesso e se o ponto é do projeto; chama `mudarStatus`;
  - flash "CAM-07: Cabo passado" e redireciona para `tecnico/projeto/{id}#pontos`.
- Em `tecnico/projeto.php`, seção **Pontos** (`id="pontos"`) antes de "Registrar o que foi
  feito":
  - progresso geral e por tipo;
  - lista agrupada por tipo (`<details>`, aberto o primeiro tipo com pendência), cada
    ponto com identificação, local e selo;
  - botão grande com a ação do próximo status ("Cabo passado ✓");
  - "Mais" abre o formulário para escolher outro status e preencher IP/canal/porta/metros;
  - pontos testados ficam recolhidos ou esmaecidos.
- "Registros (Diário de Obra)" → "Meus registros".
- `meu_relatorio.php` mostra, por projeto, os `avancos` do período (contrato da parte D) e
  os totais `avancos` e `cabo_m`.

**D. Acompanhamento** (extensão do contrato do ADR-012, mantendo as chaves antigas):

- `relatorio()`:
  - cada projeto ganha:
    - `pontos` = `Obra_pontos_model::resumo()` dos pontos atuais do projeto;
    - `avancos` = histórico do período,
      `[['data' /* Y-m-d H:i:s */, 'identificacao', 'tipo', 'tipo_rotulo', 'local', 'de', 'de_rotulo', 'para', 'para_rotulo', 'usuario', 'metragem_cabo']]`,
      filtrado por `usuario_id` quando houver;
  - `totais` do projeto e o geral ganham `avancos` (int), `cabo_m` (float) e `testados`
    (int, avanços para `testado` no período);
  - projeto com avanço e sem registro também entra;
  - `tecnicos[]` ganha `avancos` e inclui quem só avançou pontos;
  - `sem_registro` considera os avanços.
- `painel()`:
  - `projetos[]` ganha `pontos` = `['total', 'testados', 'progresso']`;
  - `ultimo_registro` passa a ser a data mais recente entre RDO e avanço de ponto;
  - `registros_7d` soma RDOs e avanços;
  - `atividade_7d[].registros` também;
  - nova chave `pontos_por_tipo` = `resumo()['por_tipo']` somando os projetos ativos.
- Views:
  - relatório: bloco "Avanços nos pontos" por projeto (tabela) e cartões de totais
    (Avanços, Testados, Cabo);
  - painel: coluna "Pontos" (`testados/total`) e o cartão "Pontos por tipo" com barra por
    tipo;
  - "Diários de Obra recentes" → "Registros de execução recentes";
  - na impressão do RDO e na página pública, "Diário de Obra"/"RDO" → "Registro de
    execução", sem clima e com os novos rótulos de condição;
  - nas notificações, o mesmo nos textos visíveis, sem trocar chaves de gatilho.

### Regras comuns
- Sem JavaScript inline sem `nonce` (CSP); sem `onclick`. Formulários POST com o token
  CSRF que o CodeIgniter injeta via `form_open` ou o campo usado nas outras telas.
- Toda saída com `html_escape`.
- Componentes `ui-*` (ADR-009).

## Implementacao

| Parte | Modelo (ruflo `model-route`) | Resultado |
|-------|------------------------------|-----------|
| A. Banco e model | Sonnet (34%) | 22 checagens no banco de teste, PHPUnit OK |
| B. Gestão e as-built | Sonnet (28%) | ajuste de CSS na coluna de ações |
| C. Técnico | Haiku (30%) | sem ajuste |
| D. Relatório, painel e textos | Haiku (28%) | sem ajuste |

As quatro partes rodaram em paralelo; a sessão principal integrou e testou. Resultados
registrados no ruflo (`hooks model-outcome`).

Decisões dos agentes, mantidas:

- `addPonto`, `editPonto`, `excluirPonto` e `gerarLote` também recalculam o progresso do
  projeto (não só `mudarStatus`).
- `mudarStatus` sem mudança de status, metros ou dados não grava histórico.
- `avancos` conta toda linha do histórico no período, inclusive as que só somaram metros.
- Na saúde do painel, a coluna Pontos mostra `testados/total`; o percentual do projeto
  continua sendo a média dos pesos.

Ajuste da integração: a coluna Ações da tabela de pontos estourava a largura (select,
metros e OK grandes); agora os controles são compactos e editar/excluir aparecem.

## Verificação

Dados temporários (CFTV + rede com 12 pontos em todos os status, controle de acesso com
leitores/fechadura/botoeira, rede estruturada e alarme), depois removidos:

- **Técnico no celular**:
  - "Instalei ✓" no CAM-05 gravou IP e canal, somou 12,5 m e registrou o histórico;
  - o projeto foi recalculado para 50,8%;
  - ponto de outro projeto, projeto sem acesso e status inválido foram recusados, sem
    alterar o banco.
- **Gestão**:
  - mudança de status pela tabela;
  - "Criar etapas padrão" em "Rede estruturada" criou 7 etapas e não repetiu na segunda
    vez;
  - lote PR-01..24 seguido de 20..29 resultou em 29 pontos, sem duplicar.
- **As-built**: resumo por tipo, cabo total e PDF em A4.
- **Relatório semanal**: 6 avanços, 2 testados, 84,5 m de cabo, coluna Avanços por
  técnico. O projeto de controle de acesso aparece em "sem registro".
- **Painel**: coluna Pontos, "Pontos por tipo" com os 7 tipos presentes e "Registros de
  execução recentes" com condição "Normal".
- **Testes automáticos**:
  - `tests/navegador/projetos.mjs`: 27 de 28. A falha é a logo do emitente do banco de
    teste, que aponta para outra instalação (`localhost/Mapos_38`), também presente na
    impressão de RDO anterior;
  - o passo do Actions agora cria pontos;
  - PHPUnit: 103 testes OK.

## Consequences

### Positive
- O andamento passa a ser contado em pontos reais: "12 de 16 câmeras testadas".
- O técnico atualiza do celular com um toque, e o gestor vê na hora.
- O as-built sai pronto para entregar ao cliente.

### Negative
- O percentual do projeto com pontos deixa de vir das etapas: quem preencher etapas à
  mão vai ver o valor substituído quando o primeiro ponto mudar de status.
- O peso de cada status é fixo (0/20/40/70/90/100) e vale para todos os tipos de ponto.

## Links
- **Depends on**: ADR-012
