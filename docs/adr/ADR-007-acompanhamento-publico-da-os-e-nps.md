# ADR-007: Acompanhamento público da OS e pesquisa de satisfação (NPS)

- **Status**: accepted
- **Date**: 2026-10-25
- **Deciders**: N4m3r
- **Tags**: os, cliente, whatsapp, nps, roadmap
- **Branch**: `seguranca-23-10`

## Context

Roteiro de melhorias aprovado depois do ADR-006, em ordem de retorno:

1. Acompanhamento da OS pelo cliente (link público com linha do tempo) - **esta etapa**
2. Pesquisa de satisfação NPS ao concluir a OS - **esta etapa**
3. QR Code no equipamento do cliente (histórico, garantia, abrir chamado)
4. Manutenção preventiva automática por contrato/equipamento
5. Assistente com IA na OS (diagnóstico sugerido, laudo, resumo para o cliente)
6. Painel gerencial, custo e margem por OS, estoque com reserva/ponto de pedido,
   conciliação bancária, fila de envio e backup externo

Já existiam a linha do tempo da OS (`Os_model::getTimeline`), mas só na área do cliente
com login, e o envio automático de WhatsApp por mudança de status (`Notificador`). O
cliente não tinha como acompanhar a OS pelo celular sem senha, e não havia medida de
satisfação.

## Decision

1. **Link público de acompanhamento** `acompanhar/<token>`: token aleatório de 64
   caracteres (`os.acompanhar_token`), gerado na primeira vez que é pedido e sem
   expiração, porque o cliente acompanha a OS do início ao fim. O controller estende
   `CI_Controller`, como `Aprovacao` e `Aceite`. A página mostra só o andamento: status,
   etapas da linha do tempo, técnico responsável e contato do emitente. Não mostra
   valores, endereço nem documentos. Tem `noindex` e responde 404 para token inválido.
2. **Como o link chega ao cliente**: a tag `{LINK_ACOMPANHAR}` (link encurtado pelo
   `Encurtador`) nos modelos de WhatsApp. Ela entra nos envios automáticos por status que
   já existem, sem código novo de envio. Há também o botão "Acompanhamento" na OS, que
   exige `vOs`, para o atendente copiar o link.
3. **NPS**: com a OS Finalizada ou Faturada, a mesma página pede uma nota de 0 a 10 e um
   comentário opcional. A tabela `os_avaliacoes` aceita uma avaliação por OS (UNIQUE).
   O formulário é POST com CSRF e não usa JavaScript.
4. **Relatório** `relatorios/nps` (permissão `rOs`): NPS do período, promotores (9-10),
   neutros (7-8), detratores (0-6), resultado por técnico e lista das avaliações.
5. **Testes**: `tests/navegador/acompanhar.mjs` no Actions (link pela OS, página sem
   login, token inválido, avaliação única, relatório) e `relatorios/nps` incluído na
   varredura de telas com a CSP bloqueando.

## Implementacao

| Onde | O que |
|------|-------|
| `migrations/20261025000000_add_acompanhamento_nps.php` | `os.acompanhar_token` (índice) e `os_avaliacoes`; idempotente |
| `models/Acompanhamento_model.php` | token, link encurtado, avaliação, resumo NPS |
| `controllers/Acompanhar.php` + `views/acompanhar/publico.php` | página pública e POST da avaliação |
| `Os_model::montarNotificacaoOs` | tag `{LINK_ACOMPANHAR}`, que só gera o token quando a tag é usada |
| `Os::acompanhamento`, `os/visualizarOs.php` | botão para o atendente |
| `Relatorios::nps`, `relatorios/rel_nps.php`, menu Relatórios | relatório |
| `mapos/configurar.php` | tag disponível no seletor do modelo de WhatsApp |

Testado localmente (Edge): os 11 passos do `acompanhar.mjs` passam. O POST sem token
CSRF responde 403. A tag gera `l/<slug>` encurtado. PHPUnit: 50 testes OK.

## Consequences

### Positive
- Menos ligações perguntando pela OS; o cliente acompanha pelo link do WhatsApp.
- Medida de satisfação por técnico, com os comentários dos clientes.

### Negative
- Quem tem o link vê o andamento da OS. É aceitável porque o token é forte e a página não
  tem valores nem dados pessoais. Para revogar, basta limpar `os.acompanhar_token`, e o
  próximo pedido gera outro token.
- Um cliente com vários links curtos antigos continua acessando a mesma OS (o link curto
  aponta para o token).

### Neutral
- Os itens 3 a 6 do roteiro seguem em ADRs próprios.

## Links
- **Depends on**: ADR-005, ADR-006 (CSP com nonce e CSRF obrigatório nas rotas públicas; testes de navegador no CI)
