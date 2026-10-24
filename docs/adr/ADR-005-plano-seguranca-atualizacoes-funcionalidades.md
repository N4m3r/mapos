# ADR-005: Plano de seguranca, atualizacoes e funcionalidades (varredura de 23/10/2026)

- **Status**: accepted (grupo 1 implementado - ver "Implementacao")
- **Date**: 2026-10-23
- **Deciders**: N4m3r
- **Tags**: seguranca, dependencias, qualidade, roadmap
- **Backup**: tag e branch `backup_mudanca_23-10_4`
- **Branch**: `seguranca-23-10`

## Context

Varredura completa do codigo (~90 mil linhas, 349 arquivos), das dependencias
(`composer audit` / `composer outdated`) e da configuracao.

Ja estava correto e nao deve ser mexido: senhas com `password_hash`, SQL
parametrizado (3 trechos suspeitos conferidos e seguros), webhook da Cora que
reconsulta a API antes de dar baixa, backup e telas sensiveis com permissao.

## Decision

### Grupo 1 - Seguranca (prioridade alta)
| # | Problema | Correcao |
|---|----------|----------|
| S1 | 16 vulnerabilidades conhecidas em 4 dependencias (Guzzle 7.9.2 com uma alta) | Atualizar guzzle, psr7, fpdi e symfony/cache dentro das faixas permitidas |
| S2 | Login do painel e da API sem limite de tentativas | Bloqueio temporario apos tentativas erradas (por e-mail+IP e por IP) |
| S3 | Cookies sem `Secure` por padrao | `Secure` automatico quando o acesso e HTTPS |
| S4 | 76 saidas de dados digitados sem escapar HTML | `html_escape()` nos campos de texto simples |
| S5 | 6 acoes de cobranca isentas de CSRF | Reativar o CSRF nelas |
| S6 | Sem cabecalhos de seguranca no Apache | `X-Frame-Options`, `nosniff`, `Referrer-Policy` |

### Grupo 2 - Atualizacoes e qualidade
- Atualizacoes seguras: efipay 1.11->1.19, mpdf 8.2.5->8.3.1, sped-nfe 5.2.8->5.2.9, asaas-sdk, whoops, phpdotenv.
- Grandes (exigem adaptar codigo): Mercado Pago 2->3, PHPWord 0.18->1.4, php-pix 2->3.
- CodeIgniter 3.1.13 e a ultima da serie 3; migracao para CI4 e projeto de longo prazo.
- Testes automaticos (navegador + PHPUnit) rodando no GitHub Actions.
- Reduzir controllers grandes (Os, Rh, Mine, Tecnico, Checkin), as 32 supressoes `@` e os scripts inline.

### Grupo 3 - Funcionalidades que nao existem
- Historico de movimentacao de estoque e inventario.
- Backup automatico agendado.
- Verificacao em duas etapas (2FA) para administradores.
- PWA do tecnico com funcionamento offline (fotos/assinaturas).
- PDV / controle de caixa e NFC-e.
- Painel de SLA, agenda da equipe e lembretes automaticos ao cliente.

## Implementacao

Grupo 1 concluido na branch `seguranca-23-10`:

| # | Commit | Resumo |
|---|--------|--------|
| S1 | 61b0190 | guzzle, psr7, fpdi e symfony/cache atualizados |
| S2 | fa0b19b | `Limite_login` + tabela `login_tentativas`: 5 erros por e-mail+IP ou 20 por IP em 15 min bloqueiam por 15 min (painel, API de usuarios e API do cliente) |
| S3 | fddda5d | `cookie_secure` ligado automaticamente em HTTPS (inclusive atras de proxy) |
| S4 | 5a99a66 | `html_escape()` em 373 saidas simples e 64 concatenacoes em 51 views; dados em JavaScript com `json_encode` em vez de `addslashes`; `etiquetaCode` (GET) escapado. Campos de editor rich-text continuam HTML de proposito |
| S5 | b38397e | CSRF reativado nas 6 acoes de cobranca; `csrf.js` corrigido (Content-Type e FormData) |
| S6 | 2db65bc | `X-Frame-Options SAMEORIGIN`, `nosniff` e `Referrer-Policy` no `.htaccess` (mod_headers) e no nginx do docker |

Observacao S4: o levantamento inicial contou 76 saidas; a varredura na
implementacao encontrou mais (concatenacoes, `implode` de endereco e dados
dentro de `<script>`), todas corrigidas.

## Consequences

### Positive
- Fecha as falhas exploraveis de forma remota antes das melhorias.

### Negative
- O bloqueio de login pode travar temporariamente um usuario legitimo que errar a senha varias vezes.

### Neutral
- Grupos 2 e 3 viram ADRs proprios quando forem implementados.

## Links
- ADR-001 (PHP2Shell), ADR-002, ADR-003, ADR-004
