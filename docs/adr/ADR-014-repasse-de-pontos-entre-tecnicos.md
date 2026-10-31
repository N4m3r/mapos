# ADR-014: Repasse de pontos entre técnicos (instala e fotografa → outro configura)

- **Status**: accepted
- **Date**: 2026-10-29
- **Deciders**: N4m3r
- **Tags**: projetos, cftv, tecnico, fotos, fila, repasse
- **Branch**: `seguranca-23-10`

## Context

Nos projetos de segurança eletrônica (ADR-013), quem instala nem sempre configura:

- O técnico de campo passa o cabo e instala a câmera. Ele fotografa o equipamento
  instalado e a etiqueta (modelo, série/MAC) e anota o local.
- Outro técnico, às vezes remoto ou no rack, configura (IP, canal do NVR, porta) e testa,
  com base nessas informações.

Hoje o status do ponto muda, mas não há foto por ponto, não se sabe com quem o ponto
está, e o segundo técnico não tem uma lista do que chegou para ele.

Análise do ruflo:

- `hooks pre-task`: complexidade alta, 2 a 4 h, divisão sugerida em coder, researcher e
  tester. A sugestão de agentes veio como padrão, sem histórico treinado para o tema.
- `hooks route` e `model-route` por parte: veja Implementação.

## Decision

### O fluxo

```
Instalador (celular)                      Configurador (celular ou escritório)
───────────────────                       ────────────────────────────────────
Ponto "Cabo passado"
  └─ "Instalei ✓"
       ├─ foto obrigatória (equipamento/etiqueta)
       ├─ modelo, série/MAC, local
       └─ "Quem vai configurar?"  ──────►  Fila "Comigo" (ou "Da equipe", livre)
            Eu mesmo | Equipe | Fulano       ├─ vê fotos, local, modelo, série, instrução
                                             ├─ "Assumir" (se for da equipe)
                                             ├─ preenche IP / canal / porta → "Configurei ✓"
                                             └─ repassa para teste, ou testa → "Testei ✓"
```

- Cada ponto tem um **responsável atual** (`responsavel_id`) e o que ele está
  **aguardando** (`aguardando` = a próxima etapa, ex.: `configurado`). Responsável nulo
  com `aguardando` preenchido = "da equipe": qualquer técnico com acesso ao projeto pode
  assumir.
- Ao mudar o status, quem fez continua responsável pela próxima etapa, a menos que
  repasse. Em `testado`, responsável e aguardando são limpos.
- Repasse, assumir e foto viram eventos no histórico do ponto.
- Foto é **obrigatória para marcar "Instalado"** no app do técnico. Pela gestão é
  opcional. As fotos são reduzidas no celular (lado maior 1600 px, JPEG 0,8) antes de
  subir.
- **Senhas de câmera/NVR não são guardadas no sistema**. O campo de instrução avisa
  isso.

### Banco (migração `20261029010000_add_ponto_repasse.php`, idempotente)

`obra_pontos`, novas colunas:

| Coluna | Tipo |
|--------|------|
| `responsavel_id` | INT UNSIGNED NULL |
| `aguardando` | VARCHAR(15) NULL (chave de `statusLista()`) |
| `repassado_por` | INT UNSIGNED NULL |
| `repassado_em` | DATETIME NULL |
| `instrucao` | VARCHAR(255) NULL |

Mais um índice (`responsavel_id`, `aguardando`).

`obra_ponto_historico`, novas colunas:

| Coluna | Tipo |
|--------|------|
| `evento` | VARCHAR(15) default `status` (`status`, `repasse`, `assumiu`) |
| `destinatario_id` | INT UNSIGNED NULL |

Nova tabela `obra_ponto_foto`:

| Coluna | Tipo |
|--------|------|
| `idFoto` | INT UNSIGNED AI PK |
| `ponto_id` | INT UNSIGNED, índice |
| `obra_id` | INT UNSIGNED, índice |
| `historico_id` | INT UNSIGNED NULL |
| `etapa` | VARCHAR(15) NULL (status do ponto quando a foto foi tirada) |
| `foto` | LONGBLOB |
| `legenda` | VARCHAR(150) NULL |
| `usuario_id` | INT UNSIGNED NULL |
| `data` | DATETIME |

### Contrato do model (`Obra_pontos_model`, extensão; nada do ADR-013 muda de assinatura)

Puras (PHPUnit):

- `aguardandoApos(string $status): ?string` → `proximoStatus($status)`.
- `rotuloAguardando(?string $aguardando): string`:

  | Valor | Rótulo |
  |-------|--------|
  | `infra` | "Aguardando infraestrutura" |
  | `cabeado` | "Aguardando passagem de cabo" |
  | `instalado` | "Aguardando instalação" |
  | `configurado` | "Aguardando configuração" |
  | `testado` | "Aguardando teste" |
  | `null` ou outro | `''` |

- `exigeFoto(string $status): bool` → `true` só para `instalado`.
- `fotoValida(string $binario): ?string` → `'image/jpeg'`, `'image/png'` ou
  `'image/webp'` pelos bytes iniciais. Devolve `null` se não for imagem ou se tiver mais
  de 4 MB.

Banco:

- `mudarStatus(...)` (mesma assinatura):
  - quando o status muda, grava `aguardando = aguardandoApos(novo)` e
    `responsavel_id = $usuario_id`;
  - em `testado`, limpa os dois e também `instrucao`;
  - o histórico ganha `evento = 'status'`.
- `ultimoHistoricoId(int $ponto_id): ?int`.
- `repassar(int $ponto_id, int $obra_id, ?int $destinatario_id, ?int $usuario_id, ?string $instrucao = null): bool`:
  - `null` = equipe;
  - recusa ponto `testado` e destinatário fora de `executores()`;
  - grava `responsavel_id`, `aguardando = aguardandoApos(status)`, `repassado_por`,
    `repassado_em` e `instrucao`;
  - histórico `evento = 'repasse'`, com `destinatario_id`, `observacao = instrucao` e
    `status_anterior = status_novo = status atual`.
- `repassarLote(int $obra_id, array $ponto_ids, ?int $destinatario_id, ?int $usuario_id, ?string $instrucao = null): int`
  → quantos repassou.
- `assumir(int $ponto_id, int $obra_id, int $usuario_id, bool $forcar = false): bool`:
  - só se o ponto estiver na equipe, ou com o próprio usuário, ou com `$forcar`;
  - grava `responsavel_id`;
  - histórico `evento = 'assumiu'`.
- `executores(int $obra_id): array` → `[['idUsuarios' => int, 'nome' => string]]`:
  vinculados em `obra_usuario` mais o `obras.responsavel_id`, só usuários ativos, por
  nome, sem repetir.
- `addFoto(int $ponto_id, int $obra_id, string $binario, ?int $usuario_id, ?string $etapa = null, ?int $historico_id = null, ?string $legenda = null): int`
  → id, ou 0 se `fotoValida()` recusar ou se o ponto já tiver 20 fotos.
- `getFotos(int $ponto_id): array` → objetos **sem o blob**, com
  `idFoto, ponto_id, etapa, legenda, usuario_id, usuario, data`, por data.
- `getFotosObra(int $obra_id): array` → `[ponto_id => [objetos sem blob]]`.
- `getFotoBinaria(int $idFoto): ?object` → `foto, obra_id, ponto_id`.
- `getPontos()` e `getPonto()` ganham `responsavel_nome`, `repassado_por_nome` e
  `qtd_fotos`. `getPontos()` aceita os filtros novos `aguardando` (chave) e
  `responsavel_id` (int, ou `'equipe'` para nulo com `aguardando` preenchido).
- `getFila(int $usuario_id): array` → pontos com `aguardando` preenchido e status
  diferente de `testado`, em projetos `planejamento` ou `em_execucao`, onde:
  - `responsavel_id = $usuario_id`; **ou**
  - `responsavel_id` é nulo e o usuário tem acesso ao projeto (vinculado ou
    responsável).

  Cada objeto tem os campos do ponto e mais:
  `obra_nome, cliente, repassado_por_nome, qtd_fotos, foto_id /* primeira foto ou null */, minha /* bool */`.
  Ordem: os do próprio usuário primeiro, depois `repassado_em` (ou `atualizado_em`)
  mais antigo.
- `contarFila(int $usuario_id): int`.
- `getHistorico()` ganha `evento, destinatario_id, destinatario` (nome).
- `excluirPonto()` também apaga as fotos.

### Rotas

| Rota | Quem | O que faz |
|------|------|-----------|
| `GET obras/fotoPonto/{idFoto}` | `vObras` ou acesso ao projeto | devolve a imagem com o MIME de `fotoValida`, `Cache-Control: private, max-age=86400`; 404 sem acesso |
| `POST obras/repassarPontos` | `eObras` | `obra_id`, `ponto_ids[]`, `destinatario` (`''` = equipe, ou id) e `instrucao` → `repassarLote` |
| `POST obras/salvarPonto` | `eObras` | passa a aceitar `fotos[]` (data URL) |
| `GET tecnico/fila` | técnico | a fila |
| `POST tecnico/ponto_status` | acesso ao projeto | ganha `fotos[]` (até 6), `modelo`, `serie`, `local`, `repassar_para` (`''` = eu mesmo, `equipe`, ou id de `executores`) e `instrucao`; recusa `instalado` sem foto nova nem foto anterior da etapa `instalado` |
| `POST tecnico/assumir_ponto` | acesso | `obra_id`, `ponto_id` |
| `POST tecnico/repassar_ponto` | acesso | `obra_id`, `ponto_id`, `repassar_para`, `instrucao` |

Ordem em `ponto_status`:

1. valida;
2. `mudarStatus`;
3. `ultimoHistoricoId`;
4. `addFoto` de cada foto, com `etapa` = novo status;
5. `repassar`, se pedido.

O flash diz o que aconteceu, ex.: "CAM-07: Instalado · repassado para Maria".

### Telas e donos

| Parte | Arquivos (só estes) |
|-------|---------------------|
| **A. Banco e model** | migração, `models/Obra_pontos_model.php`, `tests/Unit/ObraPontosTest.php` |
| **B. Técnico** | `controllers/Tecnico.php`, `views/tecnico/projeto.php`, `views/tecnico/fila.php` (nova), `views/tecnico/dashboard.php`, `views/tecnico/_nav.php`, `views/tecnico/meu_relatorio.php`, `assets/js/foto-compacta.js` (novo) |
| **C. Gestão** | `controllers/Obras.php`, `views/obras/visualizar.php`, `views/obras/asbuilt.php`, `assets/css/obras.css` |
| **D. Painel e relatório** | `models/Projeto_acompanhamento_model.php`, `tests/Unit/ProjetoAcompanhamentoTest.php`, `views/obras/painel.php`, `views/relatorioprojetos/index.php` |

**B. Técnico**

- `assets/js/foto-compacta.js`:
  - liga em `input[type=file][data-foto-compacta]`;
  - reduz cada imagem com canvas (lado maior 1600 px, `image/jpeg` 0,8) e cria
    `<input type="hidden" name="fotos[]">` no form;
  - mostra miniaturas em `[data-foto-preview]`, com botão para remover;
  - bloqueia o envio de `form[data-exige-foto]` sem foto e mostra a mensagem do
    atributo;
  - sem dependências; carregado com `nonce`.
- `projeto.php`:
  - o cartão mostra a miniatura da primeira foto (com contador), "Com: Fulano" ou
    "Da equipe", o rótulo de aguardando e a instrução;
  - o form de "Instalei ✓" ganha câmera (`capture="environment"`) com foto obrigatória,
    modelo, série/MAC e local preenchidos com o que já existe, e "Quem vai configurar?"
    (Eu mesmo / Equipe / cada executor), mais a instrução, com o aviso "não escreva
    senhas aqui";
  - em "Mais": repassar e foto opcional;
  - âncora `id="ponto-{id}"`.
- `fila.php`:
  - duas seções, "Comigo" e "Da equipe (livre)";
  - cartão com foto (toque abre em tamanho grande, em nova aba), identificação,
    projeto/cliente, local, modelo, série, instrução e "repassado por X há N h";
  - "Assumir" nos da equipe;
  - nos meus, o form da próxima etapa: em `configurado`, campos IP/canal/porta e
    "Configurei ✓"; em `testado`, "Testei ✓"; repassar em `<details>`;
  - "Abrir no projeto" leva a `tecnico/projeto/{obra}#ponto-{id}`.
- `dashboard.php`: cartão "Minha fila", com a contagem e o link.
- `_nav.php`: item "Fila".
- `meu_relatorio.php`: mostra eventos de repasse ("CAM-07 repassado para Maria") e
  "assumiu".

**C. Gestão**

- `Obras::fotoPonto` e `Obras::repassarPontos`; `salvarPonto` aceita fotos;
  `visualizar()` passa `executores` e `fotos_pontos` (`getFotosObra`).
- Aba Pontos:
  - coluna de foto (miniatura e contador; a galeria abre numa linha expansível ou em
    links);
  - coluna "Com / aguardando";
  - filtros "Aguardando" e "Com quem" (inclui "Equipe");
  - caixa de seleção por linha e barra "Repassar selecionados para [executor/Equipe]
    [instrução] OK".
- As-built: `?fotos=1` mostra a primeira foto de cada ponto, e há o botão "Imprimir com
  fotos".

**D. Painel e relatório** (`Projeto_acompanhamento_model`, mantém as chaves antigas)

- Pura: `tempoMedioHoras(array $pares): ?float`. Cada par é `[inicio, fim]` em
  `Y-m-d H:i:s`; ignora pares inválidos ou com fim antes do início; 1 casa; `null` se
  vazio.
- `painel()` ganha `fila`, nos projetos ativos, ordenada por `total` desc:
  `[['usuario_id' => ?int, 'nome' => string /* 'Equipe (livre)' se nulo */, 'aguardando' => [chave => int], 'total' => int, 'mais_antigo' => ?string, 'horas_mais_antigo' => ?float]]`.
- `relatorio()`:
  - os `avancos` ganham `evento`, `destinatario` e `fotos` (fotos ligadas ao
    `historico_id`);
  - os totais (projeto e geral) ganham `repasses` (int) e `tempo_medio_config_h`
    (`?float`): horas entre o `instalado` e o `configurado` seguinte do mesmo ponto,
    para configurações feitas no período.
- Painel: widget "Fila de repasses" com técnico, aguardando configuração, aguardando
  teste e mais antigo ("há 26 h"); alerta quando passa de 24 h.
- Relatório:
  - eventos com rótulo: "Repassado para Maria", "Assumiu";
  - ícone com o número de fotos;
  - KPI "Tempo médio até configurar" e "Repasses".

### Regras comuns
- CSRF: formulários POST como os existentes, que recebem o token pelo `assets/js/csrf.js`.
- Sem JavaScript inline sem `nonce`; sem `onclick`.
- Toda saída com `html_escape`.
- Os campos `modelo`, `serie`, `local`, `instrucao` e `legenda` são cortados no tamanho
  da coluna.

## Implementacao

| Parte | Agente sugerido (`route`) | Modelo (`model-route`) |
|-------|---------------------------|------------------------|
| A. Banco e model | tester (95%) | Sonnet (33%) |
| B. Técnico | coder (padrão) | Haiku (24%) |
| C. Gestão | swarm-specialist (56%) | Haiku (24%) |
| D. Painel e relatório | memory-specialist (42%) | Haiku (21%) |

- As sugestões de agente seguiram palavras-chave e não foram usadas; as quatro partes
  rodaram como agentes de código, em paralelo, com o modelo indicado.
- Resultados registrados no ruflo (`model-outcome` e `post-task`).

### Defeito encontrado e corrigido: o filtro de XSS corrompia imagens em base64

Com `global_xss_filtering` ligado, `$this->input->post()` troca
`data:image/...;base64,` por `[removed]`. Por isso:

- as fotos novas dos pontos eram descartadas. Era o defeito que fazia "Instalei ✓"
  recusar a foto;
- o mesmo já acontecia em telas antigas: fotos do registro rápido do técnico, do RDO e
  do material eram perdidas sem aviso, e assinaturas eram gravadas corrompidas.

Correção:

- esses campos são lidos com `post($campo, false)`. O `Checkin` já fazia assim na
  assinatura;
- as **fotos** continuam com decodificação base64 estrita. As dos pontos ainda passam
  por `fotoValida()`;
- as **assinaturas**, que são gravadas como data URL e exibidas em `<img src>`, passam
  pelo novo helper `dataurl_imagem()` (`general_helper.php`). Ele só aceita
  `data:image/(png|jpeg|webp);base64,` seguido de base64 puro;
- no `Checkin`, cada foto é normalizada pelo mesmo helper antes de ir ao banco;
- arquivos: `Tecnico.php`, `Obras.php`, `Obramaterial.php`, `Aceite.php`,
  `Checkin.php`.

Outros ajustes da integração:

- `_topo.php` do técnico imprimia o título e o subtítulo sem escapar, e o título é o
  nome do projeto. Agora usa `html_escape`, e o `projeto.php` deixou de escapar antes
  (evita escape duplo).
- `mudarStatus` passou a aceitar `local`.
- "Avanços" conta só mudanças de status; repasse e "assumiu" ficam em `repasses`.

## Verificação

`tests/navegador/repasse.mjs` com instalador, configurador e gestor: 21 de 21.

- **Instalador**:
  - sem foto, o envio é barrado no celular;
  - a foto de 3000×2000 (2,9 MB) virou 395 KB;
  - "Instalado · repassado para Configurador Teste", com modelo, série e instrução
    gravados.
- **Configurador**:
  - "Minha fila" aparece no início, com a foto, a série e a instrução do instalador;
  - "Configurei ✓" com IP e canal; o ponto fica com ele, aguardando teste.
- **Gestor**:
  - repasse em lote para a equipe; o configurador vê o ponto em "Da equipe" e assume;
  - painel com a fila por técnico, relatório com "Repassado para" e tempo médio até
    configurar, as-built com fotos.
- **Registro rápido do técnico**: a foto agora é gravada. Assinatura válida é
  gravada inteira; uma com `onerror` injetado é descartada.
- **Testes automáticos**:
  - `projetos.mjs`: tudo certo. Ganhou `IGNORAR_CSP` para a logo do banco de teste,
    que aponta para outra instalação;
  - PHPUnit: 115 testes OK.

Pendente: `repasse.mjs` não está no Actions, porque precisa de dois técnicos com acesso
ao projeto no banco de CI.

## Consequences

### Positive
- Quem configura recebe o ponto com foto, etiqueta e local, sem precisar ligar para o
  instalador.
- A gestão vê onde o trabalho está parado (fila e tempo até configurar).
- As fotos entram no as-built.

### Negative
- Fotos em LONGBLOB aumentam o banco e o backup: cerca de 300 KB por foto depois da
  redução, ou seja, uns 6 MB por 20 câmeras. Se crescer, mover para disco depois.
- Sem notificação push imediata: o técnico vê a fila ao abrir o app. O alerta de ponto
  parado por WhatsApp existe (abaixo), mas só roda por cron.

### Alerta de ponto parado (WhatsApp)
`php index.php lembretes/pontos_parados [simular]` (biblioteca `Alerta_repasse`, agendar a
cada 1-2 h). Ponto com responsável e `aguardando` há mais de N horas (`repassado_em`, senão
`atualizado_em`; projetos não concluídos/entregues/cancelados, ponto não testado) gera um
WhatsApp ao técnico, agrupado por projeto, com link para `tecnico/fila`. Após 2N horas, resumo
ao `obras.responsavel_id`. Não repete antes de N horas (`obra_pontos.alertado_em` e
`alertado_gestor_em`, migration 20261031030000). Config no `.env`: `ALERTA_REPASSE_ATIVO`
(padrão false) e `ALERTA_REPASSE_HORAS` (padrão 24). Sem Evolution API ativa ou sem
`usuarios.telefone`, só registra no log e não marca como alertado.

## Links
- **Depends on**: ADR-013
