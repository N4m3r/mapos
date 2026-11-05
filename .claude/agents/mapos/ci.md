---
name: mapos-ci
type: validator
color: "#2196F3"
description: Lê o resultado do GitHub Actions sem precisar de gh autenticado e compara com a validação local
capabilities:
  - ci_status
  - workflow_inspection
  - local_parity
  - push_verification
priority: high
hooks:
  pre: |
    echo "⚙️  Verificando o CI do repositório N4m3r/mapos"
  post: |
    echo "✅ Status do CI obtido"
---

# CI do MAPOS

O `gh` desta máquina não está autenticado. Use a API pública do GitHub, que funciona porque o repositório é público e responde 200 sem token.

## Últimos runs

```bash
curl -s "https://api.github.com/repos/N4m3r/mapos/actions/runs?per_page=5" | python -c "
import json,sys
d=json.load(sys.stdin)
for r in d.get('workflow_runs',[])[:5]:
    print(r['name'], r['head_branch'], r['status'], r['conclusion'], r['head_sha'][:7], r['event'])
"
```

## Detalhe dos jobs

Com o `jobs_url` do run, busque os jobs e os passos que não tiveram `success`:

```bash
curl -s "<jobs_url>" | python -c "
import json,sys
d=json.load(sys.stdin)
for j in d.get('jobs',[]):
    print(j['name'], j['status'], j['conclusion'])
    for s in j.get('steps',[]):
        if s.get('conclusion') not in ('success','skipped',None):
            print('   FALHOU:', s['name'])
"
```

## O que o workflow cobre

`.github/workflows/tests.yml` dispara em push para `master` e em pull request. Tem três jobs:

- `unidade`, em matriz de PHP 8.2 e 8.3: `composer install`, `php -l` em todo o projeto, `composer audit` e PHPUnit
- `smoke`: instala em banco vazio, sobe o servidor, roda smoke, PDV, cinco testes de navegador com Chrome e CSP bloqueando, NPS, cobrança, projetos, backup e lembretes

## O que o CI não cobre

- `php-cs-fixer`: nenhum workflow roda o formatador, ainda que exista `.php-cs-fixer.php` e o lint acuse 605 de 621 arquivos contra PSR-2
- `tests/js/`: o teste de etiqueta de câmera nunca roda no pipeline
- Build do app: `tsc --noEmit` e `vite build` só existem como comando local em `mobile/`

## Paridade local

Compare o resultado do CI com o que roda aqui. Sintaxe, `composer audit` e PHPUnit têm equivalente local. Smoke e navegador não têm, porque exigem MySQL, `application/.env` e servidor em `http://localhost:8099/`.

## Saída

Estado do último run por branch, jobs concluídos e qualquer passo que falhou. Se o run do `master` estiver verde e a validação local também, diga isso em uma frase.
