# Análise de melhorias do MAPOS

- **Data**: 2026-11-05
- **Base**: `master` em `fda792a` (PR #8, que mescla `feat/app-mobile-camera-hikvision`)
- **Escopo**: backend CodeIgniter 3, banco, configuração, CI e app mobile
- **Método**: leitura de código e schema, mais execução local do PHPUnit

## O que foi executado

| Verificação | Resultado |
|---|---|
| `phpunit` (PHP 8.3.36) | **OK — 378 testes, 1263 asserções** |

Não havia banco populado nem navegador nesta rodada. Os itens marcados com *verificar em execução*
dependem de uma instância real; os demais vêm de código lido diretamente, com arquivo e linha.

## Resumo

| # | Assunto | Gravidade | Onde |
|---|---|---|---|
| 1 | Instalação nova fica sem 59 tabelas | Crítico | `install/do_install.php` |
| 2 | Whoops mostra stack trace em produção | Alto | `hooks/whoops.php:8` |
| 3 | Auto-update troca o fork pelo upstream | Alto | `config/github_updater.php:10-40` |
| 4 | Chave do `.env` fraca e o 2FA depende dela | Alto | `install/do_install.php:100` |
| 5 | Uploads sem limite e sem guarda de execução | Alto | `Os_anexos.php:54` |
| 6 | Reenvio do código de aprovação zera o bloqueio | Alto | `Aprovacao_model.php:303` |
| 7 | Busca de produtos e serviços na API não funciona | Médio | `api/v1/ProdutosController.php:29` |
| 8 | `perPage` sem teto em toda a API | Médio | `api/v1/*Controller.php` |
| 9 | Fila de e-mail: retry infinito e cron sem trava | Médio | `MY_Email.php:150,221` |
| 10 | Sessões sem chave única em `id` | Médio | `banco.sql:10` |
| 11 | Listagem de OS pesada e sem índice por data | Médio | `Os_model.php:47-88` |
| 12 | Conexão `utf8` contra tabelas `utf8mb4` | Médio | `config/database.php:19` |
| 13 | Filtro XSS global, deprecado, com 24 contornos | Médio | `config/config.php:530` |
| 14 | Auditoria sem retenção e sem índice por data | Baixo | `Audit_model.php:46` |
| 15 | Lacuna de CI no app e nos testes de JS | Baixo (processo) | `.github/workflows/tests.yml` |
| 16 | 53 arquivos acima de 500 linhas | Baixo (manutenção) | `application/` |

## O que a mesclagem do app mobile já resolveu

O PR #8 trouxe junto quatro correções que estavam pendentes em outro branch. Elas **já estão no
`master`** e não aparecem como itens acima:

| Correção | Estado |
|---|---|
| `anexos_post` exige `logged_user()` e permissão `eOs` | resolvido |
| `Financeiro` monta o filtro com `db->escape()` e `http_build_query` | resolvido |
| Busca de clientes passa só o termo ao model | resolvido |
| Paginação da API de OS com offset 0-based | resolvido |

Também entraram `mobile/` (126 arquivos) e `teste/` (18), com regras de ignore para build,
`node_modules` e os artefatos publicados. O que sobrou desse bloco é só o CI, que virou o item 15.

---

## 1. Instalação nova fica sem 59 tabelas — Crítico

**Achado.** `install/do_install.php` importa só `banco.sql`, gera o `.env` e encerra com
`Instalação bem sucedida` (linha 113). Não executa migration nenhuma: a palavra `migrat` não aparece
no arquivo. O dump cria 27 tabelas e semeia a versão `20210125173741` (`banco.sql:660`). As 52
migrations criam **59 tabelas que o dump não tem**, então uma instalação nova fica com menos da
metade do schema.

Ficam de fora, entre outras: `os_checkin`, `os_assinaturas`, `os_fotos_atendimento`, `os_avaliacoes`,
`caixas`, `caixa_movimentos`, `inventarios`, `login_tentativas`, `notas_entrada`,
`tributos_parametros`, `conciliacao_itens` e as 20 tabelas de `obra*` (`obras`, `obra_pontos`,
`obra_rdo`, `obra_medicao`, `obra_material_*`). Também faltam colunas como
`os.tecnico_responsavel`, `os.data_agendamento` e `os.aceite_token`.

O `tests/smoke/instalar.sh` diz que reproduz os passos do instalador (`:3`), mas roda
`php index.php tools migrate` por conta própria (`:43`). O CI passa por um caminho que o instalador
não percorre, e é por isso que a divergência não aparece.

**Por que importa.** Quem instala pela assistente recebe um sistema incompleto. O PDV depende de
`caixas`, a área do técnico de `os_checkin` e `os_assinaturas`. A Central de Atendimento filtra por
`data_agendamento` sem checar se a coluna existe (`Os_model.php:716-722`). Parte dos usos tem guarda
(`Tecnico.php:238`, `Mine.php:946`, `Checkin.php:1123`), o que evita o erro fatal, mas transforma o
recurso em tela vazia.

**Correção.**

1. Regerar `banco.sql` como dump completo do schema atual e subir a versão semeada em `migrations`
   para a última. É o caminho de menor risco, porque mantém o dump como fonte de verdade.
2. Alternativa: rodar as migrations no fim de `do_install.php`, antes de responder sucesso.
3. Fazer o smoke usar o mesmo caminho do instalador, sem a chamada manual de `tools migrate`.

**Como conferir.** Instalar num banco vazio pela assistente e abrir PDV, Área do Técnico, Projetos e
Central de Atendimento. Nenhuma deve acusar tabela ou coluna ausente.

---

## 2. Whoops mostra stack trace em produção — Alto

**Achado.** `application/hooks/whoops.php:8` registra o `PrettyPageHandler` sem verificar o ambiente,
e o hook `pre_system` está ativo (`config/hooks.php:25-31`, com `enable_hooks = true` em
`config/config.php:124`). O `Run::register()` substitui os handlers de erro e de exceção do PHP
(`application/vendor/filp/whoops/src/Whoops/Run.php:216-217`), inclusive em produção, onde
`index.php:104-108` já desliga `display_errors`.

**Por que importa.** Qualquer erro não tratado passa a renderizar caminhos absolutos, trecho de
código e variáveis da requisição. Conforme o erro, aparecem dados do `.env`.

**Correção.** Registrar o Whoops apenas quando `ENVIRONMENT` for `development`. Em produção, usar
`log_message('error', ...)` e a página 500 que o próprio CI3 traz
(`application/views/errors/html/error_general.php`).

**Como conferir.** Com `APP_ENVIRONMENT=production`, provocar uma exceção e verificar que a resposta é
uma página 500 sem stack trace.

---

## 3. Auto-update troca o fork pelo código upstream — Alto

**Achado.** O atualizador aponta para o repositório de origem, não para este fork:
`github_user = 'RamonSilva20'` (`config/github_updater.php:10`), `github_repo = 'mapos'` (`:15`) e
`github_branch = 'master'` (`:20`). A lista `ignored_files` está vazia (`:40`), e `_is_ignored()`
só consulta essa lista (`libraries/Github_updater.php:128-139`). Como consequência, cada arquivo
alterado no upstream desde `current_commit` é copiado por cima do local (`:100`), os removidos são
apagados, e ao final roda-se `composer install --no-dev` (`:112`). O pedido de comparação desliga a
verificação de TLS (`:214`); o download do zipball usa `copy()` com o verificador padrão (`:187`).

**Por que importa.** Um clique em "Atualizar MAPOS" sobrescreve área do técnico, projetos, RH,
fiscal, WhatsApp, app mobile e `composer.json`, sem nenhuma exceção configurada. O `.env` não é
rastreado no upstream, então não entra no diff e não é sobrescrito.

**Correção.**

1. Apontar para o fork e para uma branch de release, nunca `master`.
2. Preencher `ignored_files` com `.env`, `application/config/`, `assets/uploads`,
   `assets/arquivos`, `assets/anexos`, `application/arquivos_fiscais`, `mobile/` e `docs/`.
3. Reativar a verificação de TLS e remover a chamada de `composer install`.
4. Preferir release assinado ao zipball da branch.

**Como conferir.** Em teste, acionar a atualização e conferir por diff que nenhum arquivo
customizado mudou.

---

## 4. Chave do `.env` fraca e o 2FA depende dela — Alto

**Achado.** O instalador gera a chave com `substr(md5(rand()), 0, 15)` (`install/do_install.php:100`),
enquanto a chave JWT, na linha seguinte, usa `openssl_random_pseudo_bytes(32)` (`:105`). O
`Dois_fatores` cifra e decifra os segredos TOTP com essa chave (`libraries/Dois_fatores.php:174`).

**Por que importa.** São 15 caracteres hexadecimais, mas a entropia real é a de `rand()`, que não é
criptograficamente seguro, e o `md5` não acrescenta entropia. Com o banco em mãos, o atacante tem os
segredos TOTP cifrados e uma chave viável de quebrar, o que anula o segundo fator.

**Correção.** Trocar por `base64_encode(random_bytes(32))`, oferecer rotação de chave recifrando os
segredos existentes e documentar no `.env.example` que ela protege dados sensíveis.

**Como conferir.** Instalar do zero e conferir o tamanho e a origem do valor gerado.

---

## 5. Uploads sem limite e sem guarda de execução — Alto

**Achado.** Os três pontos de upload usam `max_size => 0`: `libraries/Os_anexos.php:54`,
`controllers/Arquivos.php:237` e `api/v1/OsController.php:813`. O sistema não impõe teto próprio. No
Docker vale o `uploads.ini` com 10 MB (`docker/etc/php/uploads.ini:3`); fora dele, o limite é o
`php.ini` do servidor.

`assets/arquivos` e `assets/anexos` não têm `.htaccess`. Só `assets/assinaturas` e
`assets/fotos_atendimento` têm, e mesmo nelas o `index.php` existe, embora morra no guard de
`BASEPATH`. No nginx, todo `.php` vai para o PHP-FPM (`docker/etc/nginx/default.template.conf:54`),
sem exceção para `assets/`. O upload de logo aceita `svg` (`controllers/Mapos.php:276`).

Os anexos ficam em pasta pública e são servidos por URL direta: `Os_anexos.php:98` grava
`base_url($subpasta)` no banco e a view monta o link (`views/os/visualizarOs.php:816`). O nome do
arquivo é aleatório (`uniqid()`, `Os_anexos.php:79`), mas quem tem o link baixa sem passar por
checagem de permissão.

**Por que importa.** Uma sessão comprometida enche o disco. Um `.php` que caia na pasta servida
executa como código do servidor. SVG servido como imagem é vetor de XSS armazenado. O link direto
expõe anexo de cliente a quem tiver a URL.

**Correção.**

1. `max_size` explícito nos três pontos.
2. `.htaccess` em `assets/` negando execução de PHP, e no nginx
   `location ~ ^/assets/.*\.php$ { deny all; }`.
3. Remover os `index.php` das pastas de upload.
4. Recusar `svg` no logo e converter para PNG.
5. Servir anexos por controller com checagem de permissão.

**Como conferir.** Enviar um `.php` renomeado como imagem e abrir a URL; nunca deve executar.

---

## 6. Reenvio do código de aprovação zera o bloqueio — Alto

**Achado.** O código é de 6 dígitos, gerado por `random_int` e guardado como SHA-256
(`Aprovacao_model.php:290-305`), com bloqueio de 5 tentativas (`:40`) e validade de 15 minutos
(`:43`). Mas `gerarCodigo()` reinicia `aprovacao_codigo_tentativas = 0` (`:303`), e
`enviarCodigo()` (`controllers/Aprovacao.php:131-158`) não impõe intervalo mínimo nem limite de
pedidos: cada POST gera código novo e dispara mensagem.

**Por que importa.** O bloqueio vale por código, não por OS. A cada 5 tentativas o atacante pede um
código novo e reinicia o contador. O link de aprovação é público e circula por WhatsApp e e-mail, e
cada reenvio gera mensagem paga em nome da empresa.

**Correção.**

1. Guardar a data do último envio e impor intervalo mínimo, com limite por OS e por hora.
2. Não zerar as tentativas de imediato, ou aplicar bloqueio crescente por OS e por IP.
3. Registrar os pedidos para identificar abuso.

**Como conferir.** Pedir o código repetidamente para a mesma OS e observar a recusa após o limite.

---

## 7. Busca de produtos e serviços na API não funciona — Médio

**Achado.** `ProdutosController` (`api/v1/ProdutosController.php:29`) e `ServicosController`
(`:30`) montam uma string SQL e a entregam como termo de busca. O model aplica esse valor dentro de
`->like()` (`Produtos_model.php:17-18`, `Servicos_model.php:17-18`), e o `like()` do CodeIgniter
escapa o texto com `escape_like_str()` (`DB_query_builder.php:965`). O resultado é uma procura pelo
literal `codDeBarra LIKE '%termo%' OR descricao LIKE '%termo%'`, que não encontra nada.

Não é injeção, é bug funcional. O `ClientesController` já foi corrigido no HEAD: passa só o termo
digitado (`:35`) e o model aplica o `like`.

**Por que importa.** A busca desses dois endpoints é inútil na prática, e o app mobile depende dela
para montar orçamento.

**Correção.** Repetir o padrão de clientes nos dois: passar `$search` limpo ao model e deixar o
`like()` cuidar das colunas.

**Como conferir.** `GET /api/v1/produtos?search=cabo` deve devolver os produtos cuja descrição contém
"cabo".

---

## 8. `perPage` sem teto em toda a API — Médio

**Achado.** Seis controllers aceitam `perPage` direto da query string, sem limite superior:
`ApiController.php:153`, `ClientesController.php:30`, `OsController.php:54`,
`ProdutosController.php:31`, `ServicosController.php:32` e `UsuariosController.php:35`. Só clientes
e OS convertem para inteiro; os outros quatro repassam o valor cru.

**Por que importa.** `?perPage=1000000` tenta carregar a tabela inteira na memória do PHP e na
resposta. Em base grande, o pedido estoura o `memory_limit` e derruba o processo, afetando todos os
usuários.

**Correção.** Centralizar num helper, com padrão 20 e teto de 100:
`$perPage = min(max((int) $this->get('perPage'), 1), 100);`

**Como conferir.** `?perPage=999999` deve devolver no máximo 100 registros.

---

## 9. Fila de e-mail: retry infinito e cron sem trava — Médio

**Achado.** Três problemas no mesmo fluxo:

1. `retry_queue()` (`libraries/MY_Email.php:221-231`) devolve **todos** os registros `failed` para
   `pending` (`:228`), sem contador de tentativas. Um endereço inválido entra em laço permanente, e
   o cron roda o retry a cada 5 minutos (`docker/etc/php/cron-jobs:2`).
2. `send_queue()` (`:150`) busca todos os pendentes com `$this->get()` sem argumento (`:153`), embora
   `get()` aceite limite (`:85`). Fila grande vira execução longa e com muito consumo de memória.
3. O cron `email/process` (`cron-jobs:1`) chama o caminho de CLI (`controllers/Email.php:15`), que
   não usa `flock`. O hook `post_system` tem trava (`hooks/email_queue.php:57`) e um throttle próprio
   de 120 segundos, com piso de 30 (`:28` e `:48`). Os dois caminhos podem processar a mesma fila.

Sobre a tabela: `email_queue` tem índice em `status`
(`20261023000000_add_indices_performance.php:24`), mas nenhum em `date`, que é a coluna do filtro de
retry, e as linhas `sent` nunca são apagadas.

**Correção.**

1. Coluna `tentativas` com limite, e `failed` definitivo depois disso.
2. `limit` no `send_queue()`, em lotes de 50.
3. Mesma trava nos dois caminhos, ou desligar o hook quando o cron estiver ativo.
4. Índice composto em `(status, date)`.
5. Poda de `sent` com mais de 30 dias.

**Por que importa.** Um endereço inválido mantém a fila girando para sempre, e sem poda `email_queue`
cresce até virar uma das maiores tabelas do banco. Envio duplicado pelo cron e pelo hook mancha a
reputação do domínio remetente.

**Como conferir.** Colocar um endereço inválido em `email_queue` e verificar que, após N tentativas,
ele fica `failed` e não volta para `pending`. Rodar `email/process` por cron e o hook ao mesmo tempo
e conferir que cada mensagem sai uma única vez.

---

## 10. Sessões sem chave única em `id` — Médio

**Achado.** `ci_sessions` (`banco.sql:10-16`) tem apenas `KEY ci_sessions_timestamp (timestamp)`.
Não há `PRIMARY KEY` nem `UNIQUE` em `id`, e nenhuma migration corrige isso. O driver de sessão por
banco localiza a linha com `WHERE id = ?` (`Session_database_driver.php:248`), lê com `->row()`
(`:175`) e atualiza sem garantia de unicidade (`:262`).

**Por que importa.** O driver pressupõe `id` único. Havendo duplicatas, o `UPDATE` atinge várias
linhas de uma vez e a leitura devolve uma delas ao acaso, o que faz a sessão parecer instável (login
que cai, dados de outro usuário). A coleta de lixo apaga por `timestamp < time() - maxlifetime`
(`:335-343`), então ela limpa duplicatas vencidas, mas não corrige as ativas.

**Correção.** Migration com `PRIMARY KEY (id)`, removendo duplicatas antes. Se o volume crescer,
avaliar `sess_driver=redis`.

**Como conferir.** `SHOW CREATE TABLE ci_sessions` deve mostrar a chave primária em `id`.

---

## 11. Listagem de OS pesada e sem índice por data — Médio

**Achado.** A listagem usada pelo web (`controllers/Os.php:76`) e pela API
(`api/v1/OsController.php:59`) seleciona `os.*` com `LEFT JOIN` em `produtos_os` e `servicos_os`
(`Os_model.php:52-53`), com `GROUP BY os.idOs` (`:88`). Os filtros de período usam `dataInicial >=`
(`:71`) e `dataFinal <=` (`:75`), e a Central de Atendimento filtra por `data_agendamento`
(`:716-722`).

`os.status` tem índice (`idx_os_status`, em `20261023000000_add_indices_performance.php:18`), além
dos índices de tokens, `tecnico_responsavel`, `data_atribuicao` e `tipo_servico`. `dataInicial`,
`dataFinal` e `data_agendamento` não têm nenhum.

O `GROUP BY` com colunas de `clientes` e `usuarios` no `SELECT` é um risco teórico com o MySQL 8.4,
que usa `ONLY_FULL_GROUP_BY` por padrão. Como essas colunas são funcionalmente dependentes das chaves
primárias trazidas pelo equijoin, o MySQL tende a aceitar. *Verificar em execução*, com `EXPLAIN`, se
a consulta passa no modo estrito.

**Correção.**

1. Índice em `(status, dataInicial)` e outro em `data_agendamento`.
2. Trocar o `GROUP BY` por `EXISTS` ou subconsulta agregada, como a própria API já faz para
   `totalProdutos` e `totalServicos`, eliminando a duplicação de linhas.
3. Medir com `EXPLAIN` antes e depois.

**Por que importa.** É a tela mais usada do sistema. Com dois `LEFT JOIN` sem agregação, o banco monta
o produto cartesiano antes de colapsar no `GROUP BY`; sem índice por data, o filtro de período varre
a tabela inteira.

**Como conferir.** `EXPLAIN` da listagem com filtro de período deve mostrar acesso por índice
(`range`), não varredura completa (`ALL`). Comparar o tempo de `/os` antes e depois.

---

## 12. Conexão `utf8` contra tabelas `utf8mb4` — Médio

**Achado.** A conexão usa `utf8` e `utf8_general_ci` quando o `.env` não define outra coisa
(`config/database.php:19-20`, e `.env.example:55-56` traz esses mesmos valores). Do lado do schema,
oito arquivos de migration criam tabelas em `utf8mb4`, enquanto 12 declarações de `CHARACTER SET`
seguem em `utf8` puro.

Não há `.env` local neste checkout, então valem os padrões acima.

**Por que importa.** Com a conexão em `utf8`, o MySQL corta caracteres de 4 bytes, como emoji e
alguns símbolos. Descrições, observações e nomes gravam truncados ou provocam erro de string
inválida.

**Correção.** `DB_CHARSET=utf8mb4` e `DB_COLLATION=utf8mb4_unicode_ci` no `.env.example`, mais uma
migration convertendo as tabelas que ainda estão em `utf8`.

**Como conferir.** Gravar um emoji em `os.observacoes` e conferir que ele volta íntegro.

---

## 13. Filtro XSS global, deprecado e com 24 contornos — Médio

**Achado.** `$config['global_xss_filtering']` está ligado por padrão (`config/config.php:530`, que
cai em `true` quando a variável de ambiente não existe, e `.env.example:146` com `true`). O próprio
CodeIgniter marca esse filtro como deprecado. Ele também corrompe dados legítimos: há 24 chamadas a
`$this->input->post(..., false)` nos controllers apenas para escapar do filtro, e o helper
`dataurl_imagem` registra que o filtro troca `data:image/...;base64,` por `[removed]`
(`helpers/general_helper.php:300-302`).

**Por que importa.** O filtro altera o dado na entrada, não na saída. Isso dá falsa sensação de
segurança e provoca perda de informação. A proteção real já existe: `html_escape` nas views, CSP com
nonce e CSP em modo bloqueio (`.env.example:41`).

**Correção.** Desligar o filtro global, remover os 24 contornos e manter a CSP em `bloquear`,
conferindo tela a tela as áreas que dependiam do filtro.

**Como conferir.** Salvar um contrato com HTML legítimo e um campo com `<script>alert(1)</script>`:
o HTML do contrato deve persistir íntegro e o script deve aparecer escapado na tela, sem executar.
Repetir em OS, projetos e área do técnico, que usam `data:` de foto e assinatura.

---

## 14. Auditoria sem retenção e sem índice por data — Baixo

**Achado.** `log_info()` aparece 247 vezes nos controllers e 297 no projeto, e cada chamada grava uma
linha em `logs`. A tabela tem só `PRIMARY KEY (idLogs)` (`banco.sql:564-571`), sem índice por data. A
limpeza existe (`Audit_model::clean()`, `:46`, apaga o que tem mais de 30 dias), mas só é disparada
pelo botão da tela (`controllers/Auditoria.php:35`). Não há cron: `docker/etc/php/cron-jobs` não
menciona auditoria.

**Por que importa.** A tela ordena por `idLogs` sem filtro de data indexado, então fica lenta com o
tempo, e a tabela cresce sem limite em instalações movimentadas.

**Correção.** Cron diário chamando `Audit_model::clean()` e índice em `(data, hora)`.

**Como conferir.** Numa base de teste com `logs` antigos, rodar a poda e conferir que sobraram só os
últimos 30 dias. Medir a tela de auditoria com `EXPLAIN` antes e depois do índice.

---

## 15. Lacuna de CI no app e nos testes de JS — Baixo (processo)

**Achado.** O workflow `.github/workflows/tests.yml` tem dois jobs: `unidade` (sintaxe de todos os
arquivos PHP, `composer audit` e PHPUnit) e `smoke` (instalação em banco vazio, smoke, PDV, testes de
navegador em Chrome com CSP bloqueando, acompanhamento público com NPS, cobrança, projetos, backup e
lembretes).

Não roda o teste de JS do projeto, `tests/js/etiqueta-camera.test.mjs`, embora o ADR-019 preveja
`node --test tests/js/` (`docs/adr/ADR-019-etiqueta-camera-autopreenchimento.md:35`). Também não roda
o build do app (`tsc --noEmit` e `vite build`), que hoje só existe como comando local em `mobile/`.

O versionamento já foi resolvido pelo PR #8: `mobile/` (126 arquivos) e `teste/` (18) estão
rastreados, e o `.gitignore:58-64` cobre `node_modules`, `dist`, `mobile-app` e os artefatos `.apk` e
`.zip`.

**Por que importa.** Uma regressão no app ou no JS de etiquetas passa pelo CI sem ninguém notar. O
backend tem cobertura boa; o front do app não tem nenhuma no pipeline.

**Correção.** Acrescentar um job que rode `node --test tests/js/` e, em `mobile/`, `npm ci`,
`tsc --noEmit` e `vite build`.

**Como conferir.** Abrir um PR com um erro de tipo no app e outro com um teste de etiqueta quebrado:
o job novo deve falhar nos dois antes do merge.

---

## 16. 53 arquivos acima de 500 linhas — Baixo (manutenção)

**Achado.** 53 arquivos de `application/` passam de 500 linhas — 52 sem contar as migrations. No
app, 1 dos 55 arquivos TypeScript passa do limite (`TecnicoAtendimentoScreen.tsx`, 580 linhas);
a mediana do app é 157. Os maiores do backend:

| Linhas | Arquivo |
|---|---|
| 2555 | `application/views/os/visualizarOs.php` |
| 2518 | `application/views/os/editarOs.php` |
| 2377 | `application/controllers/Os.php` |
| 2343 | `application/libraries/REST_Controller.php` |
| 2214 | `application/models/Projeto_acompanhamento_model.php` |
| 2037 | `application/controllers/Tecnico.php` |

**Por que importa.** `Os.php` concentra listagem, edição, anexos, desconto, impressão e integrações.
Cada mudança carrega muito contexto e aumenta o risco de regressão. `REST_Controller.php` é uma cópia embutida do
codeigniter-restserver 3.0.0, mantida em `application/libraries` e fora do Composer, o que dificulta
acompanhar as correções da biblioteca.

**Correção.** Continuar a extração já iniciada nos ADRs 003 e 006, uma área por vez. Começar por
desconto e faturamento em `Os.php`, seguindo o modelo de `libraries/Os_anexos.php`.

**Como conferir.** Contar as linhas do arquivo antes e depois da extração e rodar o PHPUnit, que já
cobre as regras de desconto e faturamento. Um teste simples no CI pode falhar quando um arquivo novo
passar de 500 linhas; para os 53 atuais, registrar a dívida e reduzir por área, sem meta de prazo.

---

## O que já está maduro

Para não dar a impressão de que o quadro é só dívida:

- **Testes**: 44 arquivos de teste unitário, 378 testes e 1263 asserções passando; 5 scripts de
  navegador (`telas`, `acompanhar`, `cobranca`, `projetos`, `repasse`) e 3 de smoke (`instalar`,
  `smoke`, `pdv`).
- **CI** (`.github/workflows/tests.yml`): sintaxe de todos os arquivos PHP, `composer audit`, PHPUnit,
  instalação em banco vazio, smoke, PDV, testes de navegador com CSP bloqueando, acompanhamento
  público com NPS, cobrança, projetos, backup e lembretes.
- **Segurança já resolvida**: CSRF ligado com lista de exceções documentada
  (`config/config.php:447-452`), CSP com nonce (`helpers/general_helper.php:264`),
  `sess_regenerate(true)` no login (`controllers/Login.php:134`), senhas com `password_hash` e
  `password_verify` (20 usos), e bloqueio de força bruta por e-mail e por IP
  (`libraries/Limite_login.php:25-26`) no painel, na API e no portal do cliente.
- **Autorização da API**: dos 90 métodos REST, só os dois `login_post` não checam autenticação, e são
  públicos por natureza. Os demais usam `logged_user()`, `logged_client()` no portal do cliente, ou
  `isEditable()` e `exigirEdicaoOs()`, que chamam `logged_user()`. Restou apenas a dependência de
  `application/config/rest.php`, que não existe.

---

## Sequência sugerida

**Semana 1 — instalação e exposição**

1. Item 1 (schema da instalação): é o mais visível para quem instala.
2. Itens 2 e 3 (Whoops e auto-update): cada um custa menos de uma hora.
3. Itens 4 e 6 (chave do `.env` e reenvio do código).

**Mês 1 — robustez**

4. Itens 5, 7, 8 e 9 (uploads, busca, `perPage`, fila de e-mail).
5. Itens 10, 11 e 12, medindo com `EXPLAIN` antes e depois.
6. Item 13, desligando o filtro XSS com conferência tela a tela.

**Contínuo**

7. Itens 14, 15 e 16, uma área por vez.

## Como registrar as decisões

O `docs/adr/` está na numeração 022 e o **ADR-023 está livre**. Vale um ADR por bloco: instalação e
atualização, limites de upload e API, e retenção de dados. O formato do projeto (Status, Data,
Contexto, Decisão, Consequências) já está estabelecido e cabe bem nesses três temas.
