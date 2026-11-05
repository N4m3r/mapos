---
name: mapos-migracoes
type: validator
color: "#F44336"
description: Audita divergência entre banco.sql, as migrations e o instalador, que hoje deixa 81 tabelas fora de instalações novas
capabilities:
  - schema_audit
  - migration_inventory
  - install_check
  - drift_detection
priority: critical
hooks:
  pre: |
    echo "🗄️  Auditoria de schema: comparando banco.sql, migrations e instalador"
  post: |
    echo "✅ Inventário de schema concluído"
---

# Auditoria de schema e migrations

Este projeto tem uma divergência conhecida e crítica: o instalador importa apenas `banco.sql`, que tem 28 tabelas, enquanto as migrations criam 107, das quais 81 não existem no dump. Uma instalação feita pela assistente sobe incompleta.

## Método de contagem

Não confie em `grep -c`. O dump escreve `CREATE  TABLE` com dois espaços em pelo menos uma tabela (`emitente`), o que derruba contagens ingênuas. Use as duas formas juntas:

- `CREATE TABLE (IF NOT EXISTS)? \`nome\`` em SQL cru
- `$this->dbforge->create_table('nome', ...)` em migrations

O `notas_entrada` só aparece pela segunda forma. Um script Python que soma as duas é o caminho confiável:

```python
import glob, io, re
sql = re.compile(r'CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`([A-Za-z0-9_]+)`', re.I)
forge = re.compile(r"create_table\(\s*['\"]([A-Za-z0-9_]+)['\"]")
dump = set(sql.findall(io.open('banco.sql', encoding='utf-8', errors='replace').read()))
mig = set()
for f in glob.glob('application/database/migrations/*.php'):
    s = io.open(f, encoding='utf-8', errors='replace').read()
    mig |= set(sql.findall(s)) | set(forge.findall(s))
print(len(dump), len(mig), len(mig - dump))
```

## O que verificar

1. **Totais**: tabelas no dump, tabelas nas migrations, união e ausentes. O estado de referência em 2026-11-05 é 28, 107, 109 e 81.
2. **Versão semeada**: `INSERT IGNORE INTO migrations(version) VALUES (...)` no dump deve corresponder à última migration. Hoje está em `20210125173741`, muito atrás.
3. **Instalador**: `install/do_install.php` não contém a palavra `migrat`, então não roda migration nenhuma.
4. **CI**: `tests/smoke/instalar.sh` roda `php index.php tools migrate` por conta própria, no fim do arquivo. Isso faz o CI passar por um caminho que o instalador não percorre.
5. **Colunas**: `os.tecnico_responsavel`, `os.data_agendamento` e `os.aceite_token` não existem no dump. Verifique com `grep -c '\`coluna\`' banco.sql`.

## Saída

Números lado a lado e, para cada divergência, o arquivo e a linha que a causam. Termine com a lista objetiva do que precisaria mudar para a instalação ficar completa: regerar o dump, subir a versão semeada ou rodar migrations no instalador.
