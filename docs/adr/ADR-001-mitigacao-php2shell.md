# ADR-001: Mitigacao PHP2Shell (CVE-2026-109618 / CVE-2026-109619)

- **Status**: accepted
- **Date**: 2026-10-23
- **Deciders**: N4m3r
- **Tags**: security, php, infra

## Context

Em 21/10/2026 o PHP publicou correcoes emergenciais para duas falhas criticas
(CVSS 9.2) de execucao remota de codigo sem autenticacao, apelidadas de
"PHP2Shell": CVE-2026-109618 e CVE-2026-109619. Afetam o interpretador PHP
de 5.4 ate 8.5.11; ha relatos de tentativas de exploracao ativa.

Versoes corrigidas: 8.5.12, 8.4.27, 8.3.36 e 8.2.35.

A falha esta no proprio PHP, nao no codigo do MapOS - nenhuma alteracao em
`application/` corrige o problema. O MapOS roda tanto via Docker
(`docker/etc/php/Dockerfile`) quanto em hospedagens/XAMPP de terceiros.

## Decision

1. Fixar a imagem Docker em `php:8.3.36-fpm` (antes `php:8.3-fpm`, que podia
   reaproveitar uma imagem em cache vulneravel).
2. Desativar `session.upload_progress.enabled` no `php.ini` do Docker e, via
   `.htaccess`, em servidores com `mod_php`. Isso bloqueia um vetor da
   CVE-2026-109619, mas **nao** e mitigacao completa.
3. Toda instalacao em producao fora do Docker deve atualizar o PHP para uma
   versao corrigida do seu ramo.

## Consequences

### Positive
- Builds Docker passam a usar PHP corrigido de forma deterministica.
- Reduz superficie de ataque mesmo em servidores ainda nao atualizados.

### Negative
- A barra de progresso de upload nativa do PHP (session upload progress) deixa
  de funcionar; o MapOS nao a utiliza.
- A imagem fixada precisa ser atualizada manualmente em futuros patches.

### Neutral
- Hospedagens sem `mod_php` (PHP-FPM/CGI) ignoram o bloco do `.htaccess`;
  nelas a mitigacao deve ser feita no `php.ini`/`.user.ini` do servidor.

## Links

- https://thehackernews.com/2026/10/php-issues-emergency-patches-for-two.html
- https://www.tenable.com/blog/php2shell-cve-2026-109618-cve-2026-109619-frequently-asked-questions-about-critical-php-remote
- https://cloud.google.com/blog/products/identity-security/responding-to-core-php-vulnerabilities-cve-2026-109618-cve-2026-109619
