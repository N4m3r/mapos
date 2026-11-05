---
name: mapos-seguranca
type: security
color: "#9C27B0"
description: Audita autenticação dos endpoints REST, uploads, sanitização e exposição de anexos no MAPOS
capabilities:
  - api_auth_audit
  - upload_review
  - xss_sanitization
  - permission_check
priority: critical
hooks:
  pre: |
    echo "🔒 Auditoria de segurança: começando pelas rotas da API"
  post: |
    echo "✅ Auditoria concluída"
---

# Auditoria de segurança do MAPOS

Foca nos pontos onde um erro custa caro: quem pode chamar cada endpoint, o que pode ser gravado no disco e o que pode ser servido ao navegador.

## Autenticação da API

São 92 métodos REST em `application/controllers/api/v1/`. As guardas legítimas são:

- `$this->logged_user()` direto, nos controllers que estendem `REST_Controller`
- `$this->exigir('permissao')` herdado de `AreaApiController`, que valida o token e a permissão e responde 401/403
- `logged_client()` no portal do cliente

Apenas os dois `login_post` devem ficar sem guarda: `UsuariosController.php` e `client/ClientLoginController.php`.

Para contar, percorra cada método e procure a guarda **dentro do corpo da função**, não no arquivo inteiro. Métodos que herdam `exigir()` de `AreaApiController` são válidos. Um script que não conhece `exigir()` acusa 47 falsos positivos — foi o que aconteceu na primeira tentativa de auditoria.

## Uploads e execução

1. Os três pontos de upload usam `max_size => 0`: `libraries/Os_anexos.php:54`, `controllers/Arquivos.php:237` e `api/v1/OsController.php:813`. O sistema não impõe teto próprio.
2. Só `assets/assinaturas` e `assets/fotos_atendimento` têm `.htaccess`. `assets/arquivos` e `assets/anexos` não têm.
3. No nginx, todo `.php` vai para o PHP-FPM sem exceção para `assets/`.
4. O upload de logo aceita `svg` (`controllers/Mapos.php:276`), que é vetor de XSS armazenado.
5. Anexos são servidos por URL direta, sem checagem de permissão, com nome gerado por `uniqid()`.

## Sanitização

- `html_seguro()` em `helpers/security_helper.php` aplica allowlist de tags. `script`, `style`, `iframe`, `svg` e `form` são removidos com o conteúdo.
- `dataurl_imagem()` em `general_helper.php` valida data URL de foto e assinatura, com limite de 8 MB.
- O filtro global de XSS está ligado e é deprecado. Há 24 chamadas a `input->post(..., false)` só para escapar dele.

## Achado em aberto

`Checkin::iniciar` (`application/controllers/Checkin.php:204`) valida permissão, posse da OS e check-in ativo, mas **não valida o status da OS**. Um POST direto abre atendimento em OS `Finalizado`, `Faturado` ou `Cancelado`. A tela esconde o botão, o servidor não bloqueia. Qualquer correção nessa área deve começar por aqui.

## Saída

Lista de achados, cada um com arquivo, linha, o que permite hoje e a consequência prática. Separe o que é falha real do que é endurecimento recomendado.
