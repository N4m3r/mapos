#!/bin/sh
# Smoke test HTTP: faz login no painel e abre as telas principais, falhando se
# alguma responder com erro HTTP ou mostrar erro de PHP/banco na pagina.
#
# Uso: BASE_URL=http://localhost:8099/index.php EMAIL=... SENHA=... sh tests/smoke/smoke.sh
set -u

BASE_URL=${BASE_URL:-http://localhost:8099/index.php}
EMAIL=${EMAIL:-admin@mapos.test}
SENHA=${SENHA:-admin123}
TMP=$(mktemp -d)
J="$TMP/cookies.txt"
falhas=0

# Tela: rota|tipo esperado (html ou pdf)
ROTAS="
mapos|html
clientes|html
clientes/adicionar|html
os|html
os/adicionar|html
agenda|html
sla|html
vendas|html
vendas/adicionar|html
pdv|html
caixa|html
nfe/configuracoes|html
produtos|html
produtos/movimentacoes|html
inventarios|html
servicos|html
garantias|html
arquivos|html
financeiro/lancamentos|html
cobrancas/cobrancas|html
contratos|html
obras|html
relatorioprojetos|html
relatorioos|html
usuarios|html
permissoes|html
mapos/emitente|html
mapos/configurar|html
mapos/minhaConta|html
mapos/backups|html
relatorios/clientes|html
relatorios/os|html
relatorios/clientesRapid|pdf
relatorios/produtosRapid|pdf
"

# Login (o token CSRF vem no cookie MAPOS_CSRF_COOKIE)
curl -s -c "$J" -b "$J" "$BASE_URL/login" > /dev/null
TOKEN=$(awk '$6=="MAPOS_CSRF_COOKIE"{print $7}' "$J")
LOGIN=$(curl -s -c "$J" -b "$J" -X POST -H "X-Requested-With: XMLHttpRequest" \
    --data-urlencode "email=$EMAIL" --data-urlencode "senha=$SENHA" --data-urlencode "MAPOS_CSRF_TOKEN=$TOKEN" \
    "$BASE_URL/login/verificarLogin")
case "$LOGIN" in
    *'"result":true'*) echo "OK    login" ;;
    *) echo "FALHA login: $LOGIN"; exit 1 ;;
esac

for linha in $ROTAS; do
    rota=${linha%|*}
    tipo=${linha#*|}
    saida="$TMP/pagina"
    info=$(curl -s -m 60 -c "$J" -b "$J" -o "$saida" -w "%{http_code} %{content_type}" "$BASE_URL/$rota")
    codigo=${info%% *}
    erro=$(grep -aoE "A PHP Error was encountered|Fatal error|Uncaught|Whoops\\\\[A-Za-z\\\\]+|A Database Error Occurred|Message: [^<]{0,160}" "$saida" | head -3 | tr '\n' ' ')

    if [ "$codigo" != "200" ]; then
        echo "FALHA $rota: HTTP $codigo $erro"; falhas=$((falhas + 1))
    elif [ -n "$erro" ]; then
        echo "FALHA $rota: $erro"; falhas=$((falhas + 1))
    elif [ "$tipo" = "pdf" ] && ! head -c 5 "$saida" | grep -q "%PDF"; then
        echo "FALHA $rota: esperava PDF, veio $info"; falhas=$((falhas + 1))
    elif [ "$tipo" = "html" ] && grep -q 'id="formLogin"' "$saida"; then
        echo "FALHA $rota: caiu na tela de login"; falhas=$((falhas + 1))
    else
        echo "OK    $rota"
    fi
done

# CSP: cabecalho presente e scripts da pagina com o mesmo nonce do cabecalho
cab=$(curl -s -D - -o "$TMP/pagina" -b "$J" "$BASE_URL/mapos" | grep -i '^content-security-policy')
nonce=$(printf '%s' "$cab" | sed -n "s/.*'nonce-\([A-Za-z0-9_-]*\)'.*/\1/p")
sem=$(grep -oiE '<script[^>]*>' "$TMP/pagina" | grep -vcF "nonce=\"$nonce\"")
if [ -z "$nonce" ] && [ "${APP_CSP:-relatorio}" != "desligado" ]; then
    echo "FALHA CSP: cabecalho sem nonce"; falhas=$((falhas + 1))
elif [ -n "$nonce" ] && [ "$sem" -gt 0 ]; then
    echo "FALHA CSP: $sem <script> sem o nonce da pagina"; falhas=$((falhas + 1))
else
    echo "OK    CSP (nonce nos scripts)"
fi

rm -rf "$TMP"
if [ "$falhas" -gt 0 ]; then
    echo "$falhas tela(s) com problema"
    exit 1
fi
echo "Todas as telas OK"
