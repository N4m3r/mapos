# ADR-004: Correcoes dos problemas encontrados nos testes

- **Status**: accepted
- **Date**: 2026-10-23
- **Deciders**: N4m3r
- **Tags**: bugfix, migrations, frontend, notificacoes
- **Backup**: tag e branch `backup_mudanca_23-10_3`
- **Branch**: `correcoes-23-10`
- **Amends**: ADR-003 (resolve os itens de "Negative" e "Neutral" listados la)

## Context

Os testes da fase 2 (ADR-003) encontraram problemas que ja existiam no sistema
e uma ressalva da propria fase 2 (troca rapida de status sem notificacoes).

## Decision

| Problema | Causa encontrada | Correcao | Commit |
|----------|------------------|----------|--------|
| Erro de JS do DataTables em 5 telas vazias | Linha "Nenhum ... cadastrado" com `colspan`, que o DataTables nao aceita | Na inicializacao global (`rodape.php`), se for a unica linha ela sai e o texto vira a mensagem de tabela vazia | e802adf |
| Erro de sintaxe de JS na tela `/nfe` | O hook `compress.php` (minifica todo HTML) juntava as linhas de `<script>` que tinham `'<pre'`/`'<textarea'` em string; os `//` engoliam o codigo | Hook separa script/pre/textarea antes de comprimir e devolve intactos. Saida identica nas outras telas | b78def8 |
| "30 dias dias" no WhatsApp, e-mail, API e telas | Garantia e texto livre e o codigo sempre acrescentava " dias" | Helper `formatar_garantia()` usado nos 8 lugares | b945dc7 |
| `os_checkin`, `os_assinaturas`, `os_fotos_atendimento` nao criadas em instalacao nova | `'default' => 'CURRENT_TIMESTAMP'` vira texto entre aspas (erro 1067), engolido com `db_debug` desligado | Consolidada corrigida + nova migration `20261023010000` cria o que faltar em bancos ja instalados | 02ff020 |
| Troca rapida de status sem notificacao e sem SLA (ressalva do ADR-003) | O `alterarStatusAction` so gravava o status | `notificarEdicaoOs()` compartilhado com o `editar` e `Os_contrato::camposCronometroSla()` | 4dee590 |

## Consequences

### Positive
- Nenhuma das 23 telas varridas tem erro de JS (antes 6).
- Instalacoes novas e antigas passam a ter as tabelas de check-in/fotos/assinaturas.
- Troca de status pela listagem ou pela Central avisa o cliente e conta o SLA, como a edicao.

### Negative
- A troca rapida na Central de Atendimento agora tambem envia e-mail/WhatsApp
  (antes nao enviava). E o comportamento esperado, mas e uma mudanca visivel.

### Neutral
- Bancos ja instalados precisam rodar "Atualizar banco de dados" para receber
  as tabelas que faltavam.

## Links
- ADR-003: Fase 2 (amended por este ADR)
- ADR-002: Fase 1
