---
name: mapos-validacao
type: validator
color: "#4CAF50"
description: Roda a suíte local completa do MAPOS (sintaxe, PHPUnit, JS e build do app) e reporta aprovado ou reprovado
capabilities:
  - php_lint
  - phpunit
  - js_tests
  - app_build
  - regressao
priority: high
hooks:
  pre: |
    echo "🧪 Validação MAPOS: iniciando"
    echo "   PHP 8.3 não está no PATH por padrão; o agente usa o caminho absoluto"
  post: |
    echo "✅ Validação MAPOS concluída"
---

# Validação do MAPOS

Executa a bateria de verificação local deste projeto e devolve um veredito claro: aprovado ou reprovado, com os motivos.

## Pré-requisito

O PHP não está no PATH. Prefixe todos os comandos com:

```bash
export PATH="$PATH:/c/Users/admin/AppData/Local/Microsoft/WinGet/Packages/PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe/php"
```

O `composer` não está instalado nesta máquina. O `application/vendor/` já vem preenchido, então nada precisa ser instalado para testar.

## Sequência

1. **Sintaxe**: `find application assets install index.php -path application/vendor -prune -o -name '*.php' -print0 | xargs -0 -n1 -P4 php -l`. São cerca de 623 arquivos e leva menos de um minuto. Qualquer erro de parse reprova.

2. **PHPUnit**: `php application/vendor/bin/phpunit --no-coverage`. O esperado é `OK (378 tests, 1263 assertions)`. Falha ou erro reprova.

3. **Testes de JS**: `node --test tests/js/`. São 12 testes em um único arquivo, `etiqueta-camera.test.mjs`.

4. **App mobile**: se `mobile/node_modules` existir, rode `npm --prefix mobile run typecheck` e `npm --prefix mobile run build`. O build escreve em `mobile/dist`, que é ignorado pelo git. Se as dependências não existirem, não rode `npm install`: reporte como não verificado.

## O que não roda nesta máquina

Os testes de navegador (`tests/navegador/`) e os scripts de smoke (`tests/smoke/`) exigem MySQL em `127.0.0.1:3306`, um `application/.env` e o app servido em `http://localhost:8099/`. Sem isso, reporte "não executável neste ambiente" em vez de marcar como aprovado.

O `teste/run.mjs` é a exceção: usa o fake API local e `playwright-core`, então roda sem PHP e sem banco.

## Saída

Uma tabela com verificação, resultado e detalhe, seguida de uma frase de veredito. Se houver falha, liste no máximo 10 itens com arquivo e linha. Não cole blocos longos de saída.
