# ADR-024: Central de notificações

- **Status**: accepted
- **Date**: 2026-11-06
- **Deciders**: N4m3r
- **Tags**: notificacoes, email, whatsapp, configuracoes, ux
- **Branch**: `notificacoes-central-e-menu-unificado`

## Context

Configurar um aviso do sistema exigia passar por sete lugares:

1. `notificacoes` — lista de gatilhos (evento → canais → destinatários).
2. `notificacoes/novo` e `notificacoes/editar` — formulários do gatilho.
3. `notificacoes/envios` — log de envios de WhatsApp.
4. `emailtemplates` — lista de modelos de e-mail.
5. `whatsapptemplates` — lista de modelos de WhatsApp.
6. `mapos/emailsLog` — log de e-mails enviados.
7. `mapos/configurar` — abas **Notificações** (avisos de OS e texto do link `wa.me`) e **E-mail** (SMTP e
   Evolution API).

Nada disso conversava. Para responder "o e-mail de OS aberta está saindo?" era preciso olhar o gatilho, o
modelo, as credenciais e o log em quatro telas. Credencial em `Environments` trecho de `mapos/configurar`
também tinha um risco concreto: a tela mandava todas as chaves do `.env` no POST, então salvar Configurações
com as abas fechadas podia gravar campos vazios por cima de credenciais válidas.

## Decision

Uma tela, quatro abas, uma biblioteca de credenciais.

1. **`notificacoes` vira a central**, com abas Eventos, Canais, Modelos e Histórico. Cada evento abre em
   `notificacoes/evento/{slug}`.
2. **Histórico unificado.** `Notificacoes_central_model::historico()` lê `email_envios` e `whatsapp_envios`,
   ordena junto e pagina. As colunas que nem toda instalação tem (`queue_id`, `evento`, `os_id`) entram por
   `field_exists`.
3. **Credenciais isoladas.** `libraries/Notificacoes_credenciais.php` lê e grava o `.env`. Senha e API key em
   branco mantêm o valor atual. Um `.env` inexistente **nunca** é criado: um arquivo parcial apagaria host,
   usuário e senha do banco e derrubaria a instalação.
4. **Configurações deixa de gravar notificação.** As chaves `EMAIL_*`, `WHATSAPP_EVOLUTION_*`,
   `os_notification`, `email_automatico` e `notifica_whats` saem de `Mapos::configurar` e passam a ser
   gravadas só pela central.
5. **Ações por evento agem no evento inteiro.** `alternar` e `excluir` percorrem todos os gatilhos do evento,
   não só o primeiro, porque a lista mostra o evento e não a variação.
6. **URLs antigas seguem respondendo** por redirect (`notificacoes/envios`, `notificacoes/novo`,
   `notificacoes/editar/{id}`, `mapos/emailsLog`, `emailtemplates`, `whatsapptemplates`).

## Implementacao

| Parte | Arquivos |
|---|---|
| Tela e ações | `controllers/Notificacoes.php`, `views/notificacoes/{central,evento,_eventos,_canais,_modelos,_historico}.php` |
| Dados | `models/Notificacoes_central_model.php` |
| Credenciais | `libraries/Notificacoes_credenciais.php`, `helpers/env_helper.php`, `helpers/notificacoes_helper.php` |
| Estilo e comportamento | `assets/css/notificacoes.css`, `assets/js/notificacoes.js` |
| Testes | `tests/Unit/{NotificacoesHelperTest,NotificacoesViewsTest,EnvHelperTest}.php` |

## Consequences

### Positive
- Um caminho para tudo: evento, canal, texto e resultado ficam a um clique.
- O histórico responde "saiu ou falhou" com e-mail e WhatsApp na mesma lista, com filtro por canal e status.
- Salvar Configurações não apaga mais credencial: as chaves saíram de lá.
- `NotificacoesViewsTest` renderiza as quatro abas e a tela de evento sem banco, então erro de variável
  aparece no PHPUnit e não em produção.

### Negative
- `Notificacoes_credenciais` precisa que `application/.env` exista; sem ele, a aba Canais avisa e esconde os
  formulários em vez de oferecer um jeito de criar o arquivo pela tela.
- Uma instalação sem `notification_triggers` (a tabela não está no `banco.sql`) só configura notificação
  depois de rodar a atualização do banco; a central avisa e não quebra.
- O histórico mostra "enviado" como aceite do servidor SMTP ou da Evolution API, não entrega confirmada.
- Dois eventos do catálogo (`nota_emitida`, `cliente_novo`) existem na tela mas ainda não têm ponto de
  disparo no código.

## Links
- **Related**: ADR-023 (menu unificado), ADR-006 (testes de tela)
