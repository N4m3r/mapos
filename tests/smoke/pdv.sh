#!/bin/sh
# Smoke test do PDV: abre caixa, vende com troco, confere recusas, faz sangria e fecha o caixa.
# Uso: BASE_URL=http://localhost:8099/index.php EMAIL=... SENHA=... sh tests/smoke/pdv.sh
# Precisa de um produto cadastrado com preco (id em PRODUTO, padrao 1).
set -u

BASE_URL=${BASE_URL:-http://localhost:8099/index.php}
EMAIL=${EMAIL:-admin@mapos.test}
SENHA=${SENHA:-admin123}
PRODUTO=${PRODUTO:-1}
TMP=$(mktemp -d)
J="$TMP/cookies.txt"
falhas=0

token() { awk '$6=="MAPOS_CSRF_COOKIE"{print $7}' "$J"; }
post() { # post <rota> <campo=valor>...
    rota=$1; shift
    set -- "$@" "MAPOS_CSRF_TOKEN=$(token)"
    args=""
    for kv in "$@"; do args="$args --data-urlencode $(printf '%s' "$kv" | sed "s/'/'\\\\''/g; s/^/'/; s/$/'/")"; done
    eval curl -s -b "$J" -c "$J" -X POST -H "'X-Requested-With: XMLHttpRequest'" $args "'$BASE_URL/$rota'"
}
confere() { # confere <descricao> <texto> <padrao>
    if printf '%s' "$2" | grep -q "$3"; then echo "OK    $1"; else echo "FALHA $1: $2"; falhas=$((falhas + 1)); fi
}

curl -s -c "$J" -b "$J" "$BASE_URL/login" > /dev/null
confere "login" "$(post login/verificarLogin "email=$EMAIL" "senha=$SENHA")" '"result":true'

curl -s -b "$J" -c "$J" -o /dev/null "$BASE_URL/pdv"
post pdv/abrirCaixa "valor_abertura=100,00" > /dev/null
confere "caixa aberto" "$(curl -s -b "$J" -c "$J" "$BASE_URL/pdv")" 'pdvBusca'

VENDA='{"itens":[{"produto":'"$PRODUTO"',"quantidade":2,"preco":0.01}],"desconto":0,"pagamentos":[{"forma":"dinheiro","valor":100000}]}'
R=$(post pdv/finalizar "venda=$VENDA")
confere "venda com troco (preco do cadastro, nao o enviado)" "$R" '"result":true'
confere "troco calculado" "$R" '"troco":'
confere "total pelo preco do cadastro (2 x 25)" "$R" '"total":50'

R=$(post pdv/finalizar 'venda={"itens":[{"produto":'"$PRODUTO"',"quantidade":1}],"pagamentos":[{"forma":"credito","valor":100000}]}')
confere "troco sem dinheiro e recusado" "$R" 'Troco s'
R=$(post pdv/finalizar 'venda={"itens":[{"produto":'"$PRODUTO"',"quantidade":1}],"pagamentos":[{"forma":"dinheiro","valor":0.01}]}')
confere "pagamento insuficiente e recusado" "$R" 'cobrem o total'

CAIXA=$(curl -s -b "$J" "$BASE_URL/pdv" | sed -n 's/.*PDV - caixa #\([0-9]*\).*/\1/p' | head -1)
post caixa/movimentar "caixa_id=$CAIXA" tipo=sangria valor=10 descricao=teste > /dev/null
confere "sangria registrada" "$(curl -s -b "$J" "$BASE_URL/caixa/ver/$CAIXA")" 'Sangria'
post caixa/fechar "caixa_id=$CAIXA" contado_dinheiro=0 observacao=smoke > /dev/null
confere "caixa fechado" "$(curl -s -b "$J" "$BASE_URL/caixa/ver/$CAIXA")" 'badge-success">Fechado'
confere "PDV pede novo caixa" "$(curl -s -b "$J" "$BASE_URL/pdv")" 'abrirCaixa'

rm -rf "$TMP"
[ "$falhas" -gt 0 ] && { echo "$falhas verificacao(oes) falharam"; exit 1; }
echo "PDV OK"
