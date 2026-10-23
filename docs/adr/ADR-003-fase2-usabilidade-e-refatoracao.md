# ADR-003: Fase 2 - usabilidade, busca, refatoracao do Os.php, jQuery 3 e area do tecnico

- **Status**: accepted
- **Date**: 2026-10-23
- **Deciders**: N4m3r
- **Tags**: ux, performance, refatoracao, frontend, mobile
- **Backup**: tag e branch `backup_mudanca_23-10_2` (estado anterior a esta fase)
- **Branch**: `melhorias-fase2-23-10`

## Context

Continuacao do ADR-002. Os 7 itens sugeridos depois da fase 1 foram
implementados. Desta vez havia um ambiente de teste local: copia do banco
(`mapos_teste_ruflo`, MariaDB do XAMPP na porta 3310), servidor PHP embutido e
Chrome headless (puppeteer-core) para testar as telas de verdade.

## Decision

| # | Item | Decisao | Commit |
|---|------|---------|--------|
| 3 | Dashboard financeiro | Em vez de cache (que custaria uma leitura de `lancamentos` para saber se mudou, quase o mesmo que economiza, e poderia mostrar valor velho), as 3 consultas do ano viraram 1 (`getEstatisticasFinanceiroAno`). Resultado conferido igual as funcoes antigas. | 862f06a |
| 1 | Acoes rapidas na lista de OS | Clicar no status abre a troca usando o `alterarStatusAction` ja existente (so status de fluxo, exige `eOs`; Finalizado/Faturado/Cancelado continuam so pela OS por causa de estoque e faturamento). Botao de WhatsApp com o mesmo texto da tela da OS. | ecb850b |
| 2 | Rascunho automatico | `assets/js/rascunho-form.js` (localStorage, 7 dias, nunca restaura sozinho, respeita validacao barrada). Nova OS, edicao da OS e observacoes do check-in/check-out. | 7e93694 |
| - | Bug encontrado nos testes | Painel do tecnico dava erro SQL (500) quando havia OS "Em Andamento" (aspas duplas no JOIN). Corrigido. | 5ec192f |
| 4 | Busca global | FULLTEXT descartado (deixaria de achar trechos no meio da palavra). Em vez disso: telefone/CPF/CNPJ encontrados sem pontuacao e resultados mais exatos primeiro, sem perder nenhum resultado antigo. | 3687e0c |
| 5 | Quebrar `Os.php` | E-mail da OS -> `libraries/Os_email.php`; contrato/SLA/equipe -> `libraries/Os_contrato.php`. Codigo movido sem alteracao (conferido linha a linha), rotas e chamadas iguais. | 7b15ae8 |
| 6 | jQuery 3 | jQuery 3.7.1 + Migrate 3.4.1 como opcao `APP_JQUERY3` (padrao continua 1.12). Testado com as duas versoes: varredura de 23 telas sem erro novo e interacoes passando. | fc6824e |
| 7 | Tecnico no celular | A area ja era mobile-first; corrigidos os problemas reais vistos em prints de 390px: fim da pagina escondido atras das barras fixas (causa: `body{height:0}` no bootstrap.min.css do projeto), campo de busca com 20px de altura e "30 dias dias". | 993a89b |

## Consequences

### Positive
- Menos consultas no painel; lista de OS e busca mais rapidas de usar.
- Tecnico nao perde texto se a conexao cair e consegue chegar aos botoes do fim da tela.
- `Os.php` menor e com duas partes testaveis isoladamente.
- Caminho seguro para o jQuery 3: liga em homologacao, olha o console, desliga se precisar.

### Negative
- A troca rapida de status (item 1) usa o mesmo endpoint da Central de Atendimento e
  herda o comportamento dele: **nao dispara** o e-mail/WhatsApp de "OS editada" e
  **nao marca** o cronometro de SLA (`sla_resposta_em`), que so o `Os::editar` faz.
  Isso contraria o alerta do ADR-002 ("pularia gatilhos/notificacoes") e foi aceito
  por consistencia com a Central; se as notificacoes forem necessarias, a mudanca
  deve ir no `alterarStatusAction` (vale para as duas telas).
- jQuery 3 so foi validado nas telas e fluxos testados (nao nas 216 views). Por isso fica desligado por padrao.
- O rascunho fica so no navegador do usuario (nao sincroniza entre aparelhos).
- `body.tec-body:has(.action-bar)` usa `:has()`; navegadores muito antigos ficam so com o espaco da bottom-nav.

### Neutral
- Problemas que ja existiam e continuam (nao fazem parte desta fase):
  - Erro de JS do DataTables (`_DT_CellIndex`) em servicos, vendas, garantias, arquivos e cobrancas quando a tabela esta vazia.
  - Erro de sintaxe de JS na tela `/nfe` ("missing ) after argument list").
  - Texto de WhatsApp da OS pode mostrar "30 dias dias" quando a garantia foi digitada com a palavra "dias".
  - A migration consolidada nao cria `os_checkin` num banco antigo no MariaDB do XAMPP (default `CURRENT_TIMESTAMP` entre aspas); no teste a tabela foi criada a mao.

## Links
- ADR-002: Fase 1 (indices, consultas do painel, compressao, Boxicons local, Ctrl+K)
- ADR-001: Mitigacao PHP2Shell
