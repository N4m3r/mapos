#!/bin/sh
# Instala o Map-OS num banco vazio para os testes (mesmos passos do install/do_install.php):
# importa o banco.sql com o administrador de teste, gera o application/.env e roda as migrations.
#
# Uso: DB_HOST=127.0.0.1 DB_PORT=3306 DB_USER=root DB_PASS=root DB_NAME=mapos_ci \
#      BASE_URL=http://localhost:8099/ sh tests/smoke/instalar.sh
# Atencao: sobrescreve application/.env (use so em ambiente de teste).
set -eu

DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS-root}
DB_NAME=${DB_NAME:-mapos_ci}
BASE_URL=${BASE_URL:-http://localhost:8099/}
ADMIN_EMAIL=${ADMIN_EMAIL:-admin@mapos.test}
ADMIN_SENHA=${ADMIN_SENHA:-admin123}
MYSQL=${MYSQL:-mysql}

RAIZ=$(cd "$(dirname "$0")/../.." && pwd)
mysql_cmd() { "$MYSQL" --protocol=TCP -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$@"; }

mysql_cmd -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

HASH=$(php -r 'echo password_hash($argv[1], PASSWORD_DEFAULT);' "$ADMIN_SENHA")
php -r '
    $sql = file_get_contents($argv[1]);
    $sql = strtr($sql, ["admin_name" => "Admin Teste", "admin_email" => $argv[2], "admin_password" => $argv[3], "admin_created_at" => date("Y-m-d H:i:s")]);
    echo $sql;
' "$RAIZ/banco.sql" "$ADMIN_EMAIL" "$HASH" | mysql_cmd "$DB_NAME"

php -r '
    $env = file_get_contents($argv[1]);
    $env = strtr($env, [
        "enter_db_hostname" => $argv[2], "enter_db_username" => $argv[3], "enter_db_password" => $argv[4],
        "enter_db_name" => $argv[5], "enter_baseurl" => $argv[6],
        "enter_encryption_key" => bin2hex(random_bytes(8)), "enter_jwt_key" => base64_encode(random_bytes(32)),
        "enter_token_expire_time" => "3600", "enter_api_enabled" => "true", "pre_installation" => "development",
    ]);
    file_put_contents($argv[7], $env);
' "$RAIZ/application/.env.example" "$DB_HOST:$DB_PORT" "$DB_USER" "$DB_PASS" "$DB_NAME" "$BASE_URL" "$RAIZ/application/.env"

(cd "$RAIZ" && php index.php tools migrate)
echo "Instalado em $DB_NAME (login: $ADMIN_EMAIL / $ADMIN_SENHA)"
