# ADR-006: Segurança e qualidade - pendências pós ADR-005

- **Status**: accepted
- **Date**: 2026-10-24
- **Deciders**: N4m3r
- **Tags**: seguranca, csrf, csp, testes, qualidade
- **Branch**: `seguranca-23-10`

## Context

Ao fechar o ADR-005 ficaram pendências de segurança e qualidade encontradas durante o
trabalho:

1. A CSP com nonce (f865af0) está pronta, mas sai em modo `relatorio` por padrão.
2. `csrf_exclude_uris` isenta de CSRF seis rotas do check-in (`status`, `iniciar`,
   `finalizar`, `adicionarFoto`, `uploadArquivo`, `uploadMultiplo`) e
   `emailtemplates/preview`. Vêm do commit inicial, sem motivo registrado, e os scripts
   dessas telas (checkin.js, checkin-fotos.js, fila-offline.js, csrf.js) já enviam o token.
   Sem CSRF, um site de terceiros pode iniciar/finalizar atendimento ou anexar fotos em
   nome do técnico logado.
3. O `csrf.js` coloca o token em todos os formulários, inclusive GET: o token aparece na
   URL dos filtros (histórico, logs de proxy, cabeçalho Referer).
4. A pasta `install/` continua acessível depois da instalação (o `do_install.php` recusa
   reinstalar, mas a tela e os requisitos do servidor ficam expostos).
5. Os testes de tela desta fase foram feitos à mão (Edge headless); o CI só tem smoke HTTP
   e PHPUnit.
6. A área do técnico usa jQuery 1.12.4 (sem suporte); o painel e o login já usam 3.7.1 +
   Migrate.
7. A impressão térmica da OS carrega bootstrap.min.js e matrix.js sem jQuery (erro de JS).
8. Controllers grandes (Os.php ~2.200 linhas).

## Decision

1. **CSP**: `.env.example` passa a trazer `APP_CSP=bloquear` (instalações novas já
   protegidas). Sem a variável no `.env` (servidores existentes) continua `relatorio`,
   para quem atualiza conferir o log antes de bloquear.
2. **CSRF**: remover da lista de exceções as seis rotas do check-in e
   `emailtemplates/preview`. Ficam isentos só `api/*` (JWT), `webhook/cora` (chamada
   externa, valida reconsultando a Cora) e `csp/relatorio` (relatório do navegador).
3. **csrf.js**: injetar o token só em formulários POST.
4. **install/**: com o sistema instalado (`application/.env` existe) a pasta responde 404.
5. **Testes de navegador no CI**: `tests/navegador/` com puppeteer-core e o Chrome do
   runner do GitHub, cobrindo login, CSP sem violações, ações `data-*`, PDV e a abertura
   da área do técnico. A fila offline (exige derrubar o servidor no meio do teste) segue
   testada manualmente.
6. **jQuery 3.7.1 + Migrate** em todas as áreas (técnico, colaborador, área do cliente,
   páginas públicas de aceite/aprovação), por um helper único `jquery_tags()` que respeita
   `APP_JQUERY3=false` (volta ao 1.12) como o painel já fazia.
7. **Impressão térmica**: carregar jQuery antes dos scripts que dependem dele.
8. **Controllers**: extração incremental para libraries, sem mudar comportamento (padrão do
   ADR-003). Nesta etapa: anexos da OS (`anexar`, `excluirAnexo`, `downloadanexo`) para
   `libraries/Os_anexos.php`.

## Implementacao

| # | Onde | Notas |
|---|------|-------|
| 1 | `application/.env.example` | `APP_CSP=bloquear`; sem a variavel continua `relatorio` |
| 2 | `config.php` (`csrf_exclude_uris`) | Restam `api/*`, `csp/relatorio`, `webhook/cora`. Testado: as 6 rotas respondem 403 sem token; check-in, foto online e fila offline continuam enviando com token |
| 3 | `assets/js/csrf.js` | Token so em `form[method=post]` |
| 4 | `install/index.php`, `install/do_install.php`, `install/view/.htaccess`, nginx | 404 com `application/.env`; telas internas 403 |
| 5 | `tests/navegador/telas.mjs` + Actions | Login pela tela, 39 telas com CSP bloqueando, acoes data-*, venda no PDV |
| 6 | `jquery_tags()` (general_helper) em 12 views | Area do tecnico validada com 3.7.1 (fotos, fila offline) |
| 7 | `os/imprimirOsTermica.php` | jQuery antes do bootstrap; sem erro de JS |
| 8 | `libraries/Os_anexos.php` | Os.php -118 linhas. Correcoes junto: id da OS validado antes de virar pasta (antes `../` no POST gravava fora de `assets/anexos`); download exige `vOs` (antes qualquer logado baixava qualquer anexo); anexo inexistente responde 404/"nao encontrado" em vez de erro fatal |

Revisao contra os ADRs (skill ruflo-adr:adr-review) - achados corrigidos nesta etapa:

| Achado | Correcao |
|--------|----------|
| ADR-002 item 14 pede teste tela a tela na troca para jQuery 3.7 | Testadas no Edge: 11 telas do tecnico/colaborador, 14 da area do cliente, cadastro e paginas publicas de aceite/aprovacao, sem erro de JS. Nenhuma usa os plugins de risco citados (uniform, wizard, wysihtml5) |
| `conecte/visualizar_os.php`: o `acoes.js` (f865af0) foi inserido num `</head>` dentro de string JS da janela de impressao (erro de sintaxe na tela) | Linha original restaurada; a tela e fragmento do `conecte/template.php`, que ja carrega o `acoes.js` |
| `conecte/cadastrar.php`: `funcoesGlobal.js` e `csrf.js` no `<head>` antes do jQuery (no fim da pagina): `$ is not defined` | jQuery movido para o `<head>` |
| `conecte/minha_os.php` carregava `jquery-1.10.2.min.js`, que nao existe | `jquery_tags()` |
| ADR-003 pede extracao sem mudar comportamento | `Os_anexos` muda comportamento so nas correcoes de seguranca listadas no item 8 (excecao registrada aqui) |

## Consequences

### Positive
- Fecha CSRF em ações de campo do técnico e no preview de modelos de e-mail.
- Instalações novas já bloqueiam XSS pela CSP; token CSRF deixa de vazar em URL.
- Regressões de tela passam a ser pegas no CI.

### Negative
- Abas abertas antes da atualização ficam com token antigo: o primeiro envio do check-in
  pode responder 403 até recarregar a página (a fila offline marca o item com erro e o
  técnico reenvia depois de entrar de novo).
- O CI fica mais lento (Chrome + telas).

### Neutral
- Os demais controllers grandes seguem para próximas etapas, no mesmo padrão.

## Links
- **Amends**: ADR-005 (CSP: bloqueio por padrao em instalacoes novas; antes so depois de conferir o log)
- **Depends on**: ADR-002, ADR-003 (jQuery 1.12 -> 3.7 com Migrate e teste tela a tela; extracao de controllers para libraries)
