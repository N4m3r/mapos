# ADR-017: Área do Técnico como app instalável e de verdade offline

- **Status**: Aceito
- **Date**: 2026-10-29
- **Deciders**: N4m3r
- **Tags**: tecnico, pwa, offline, service-worker, fila, projetos, celular
- **Branch**: `seguranca-23-10`

## Context

A Área do Técnico já é PWA desde o ADR-005 (F4): `sw-tecnico.js` na raiz, `assets/pwa/manifest.json`,
`assets/js/fila-offline.js` (fila em IndexedDB para foto, início e fim do atendimento, com hora do
aparelho) e `assets/js/pwa-tecnico.js`. No uso em campo apareceram quatro problemas:

- **Só fica offline o que já foi aberto.** O service worker guarda a tela quando o técnico a visita;
  a OS que ele não abriu antes de entrar no subsolo não existe no aparelho.
- **Formulários de projeto falham sem internet.** `tecnico/ponto_status`, `assumir_ponto`,
  `repassar_ponto` e `salvar_registro_projeto` (ADR-014, ADR-016) são POST com redirect; sem rede o
  navegador mostra erro e o que foi digitado (e as fotos) se perde.
- **Não há como instalar.** O técnico não sabe que dá para pôr o Map-OS na tela inicial; no iPhone
  não existe aviso nenhum.
- **Rede lenta trava a tela.** Com sinal fraco a requisição não falha, só demora; o service worker
  espera a rede indefinidamente antes de cair para a cópia guardada.

## Decision

Estender o PWA do ADR-005 F4 sem mudar rotas, regras de negócio nem nomes de campos.

1. **Instalar como app.** Botão "Instalar app" a partir do evento `beforeinstallprompt`; no iPhone,
   dica "Compartilhar > Adicionar à Tela de Início". O manifest ganha `id` e atalhos
   (Minhas OS, Projetos, Fila).
2. **Baixar para usar offline.** Novo endpoint GET `tecnico/offline_urls` devolve JSON com as telas
   do técnico (início, minhas OS, projetos, fila) e as URLs das OS abertas e projetos ativos dele.
   O `pwa-tecnico.js` busca essas páginas em segundo plano, no máximo a cada 15 min ou pelo botão
   "Baixar agora", e o service worker as guarda.
3. **Service worker v2.** Pré-cache passa a incluir jQuery 3.7.1 e migrate, `moderno.css`,
   `acoes.js`, `foto-compacta.js` e `bootstrap.min.js`. Telas continuam com rede primeiro, mas com
   limite de 6 s; passado o limite (ou sem rede), mostra a cópia guardada.
4. **Formulários de projeto e fila enviados por fetch.** Se a rede falhar, o envio vai para a mesma
   fila offline do F4, com `registrado_em`. Os campos são guardados como lista de pares
   (nome, valor), para aceitar campos repetidos e com índice (`fotos[]`, `material[id]`).
   Na sincronização:
   - o controller `Tecnico` responde JSON quando a requisição é AJAX (método `voltar()`, que
     substitui o redirect direto);
   - quando o envio vem da fila (cabeçalho `X-Fila-Offline`) não grava flashdata, para não
     aparecer mensagem velha na próxima tela;
   - o Registro do dia (RDO) usa a hora do aparelho, aceita nas últimas 72 h.

## Implementacao

Em andamento em paralelo a este ADR; a tabela indica onde cada parte entra e deve ser conferida
contra o commit final.

| Parte | Arquivos |
|---|---|
| Instalar | `assets/pwa/manifest.json` (`id`, `shortcuts`), `assets/js/pwa-tecnico.js`, `views/tecnico/_topo.php` |
| Baixar offline | `Tecnico::offline_urls`, `assets/js/pwa-tecnico.js` |
| Service worker | `sw-tecnico.js` (`VERSAO = 'v2'`, pré-cache, limite de 6 s) |
| Fila | `assets/js/fila-offline.js` (campos em pares), `views/tecnico/projeto.php`, `fila.php` |
| Servidor | `Tecnico::voltar`, `ponto_status`, `assumir_ponto`, `repassar_ponto`, `salvar_registro_projeto` |

## Verificação

- Com o DevTools em "Offline" depois de "Baixar agora": abrir uma OS e um projeto nunca visitados.
- Com "Slow 3G": a tela guardada aparece em até ~6 s.
- Mudar status de ponto, repassar e salvar RDO com foto offline; voltar a internet e conferir que
  entram uma vez, com a hora do aparelho e sem flashdata solta.
- Instalar no Android (botão) e no iPhone (dica); atalhos do manifest abrem as telas certas.

## Consequences

### Positive
- O técnico baixa o dia de trabalho antes de sair e trabalha sem sinal nas OS e projetos dele.
- Status de ponto, repasse e RDO deixam de se perder quando a internet cai.
- Rede lenta não trava mais a tela; o app aparece na tela inicial com atalhos.

### Negative
- Fotos já salvas no servidor e telas nunca baixadas não aparecem offline.
- Busca de clientes/produtos e "Nova atividade" continuam precisando de internet.
- Se a conexão cair no meio de um envio que o servidor já gravou, o reenvio pela fila pode duplicar
  o registro (não há chave de idempotência).
- Background Sync só funciona no Chrome/Android; no iPhone a fila sincroniza quando o app é aberto
  com internet.
- O login apaga as telas guardadas (aparelho compartilhado, como no F4): depois de entrar é preciso
  baixar de novo.
- Baixar em segundo plano gasta dados móveis; o limite de 15 min reduz, mas não elimina.

## Links
- **Amends**: ADR-005 (estende a F4 - PWA do técnico)
- **Depends on**: ADR-014 (repasse de pontos), ADR-016 (telas de projeto do técnico)
