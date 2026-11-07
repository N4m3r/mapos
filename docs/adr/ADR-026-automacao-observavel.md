# ADR-026: Automação de faturamento observável e à prova de clique acidental

- **Status**: accepted
- **Date**: 2026-11-07
- **Deciders**: N4m3r
- **Tags**: automacao, fiscal, ux, responsividade, seguranca
- **Relacionado**: ADR-009 (interface moderna), ADR-023/025 (menu), ADR-005 (segurança e CSP)

## Context

A automação de faturamento existe desde o ADR-008 e funciona: quando o cliente aceita a
resolução do serviço, o sistema emite a NFS-e e gera o boleto, ou segura a emissão para o dia de
faturamento do cliente. O motor (`libraries/Autoaprovacao.php`) roda sozinho pelo hook
`post_system` a cada ~2 min, com lock de arquivo, sem cron externo.

A tela que configurava e acompanhava isso (`controllers/Automacao.php` e
`views/automacao/automacao.php`) tinha problemas que apareceram na revisão:

1. **Ações de escrita por GET.** `automacao/processarAgendados` emitia notas fiscais e
   `automacao/cancelarAgendado/{id}` cancelava um faturamento — as duas por link. O
   `data-confirmar` do projeto (`assets/js/acoes.js`) só pede confirmação; ele não impede que um
   prefetch, um e-mail com link ou um clique acidental dispare a ação. Emissão fiscal por GET é o
   pior lugar para esse padrão.
2. **Sem indicadores.** Para saber se a fila estava saudável era preciso ler a tabela linha a linha.
   Não havia contagem de itens vencidos, a vencer ou em erro, nem destaque do que precisa de
   decisão humana.
3. **Fila sem diferenciação.** Item "aguardando" e item "vencendo hoje" tinham o mesmo selo; item
   em erro só aparecia com contagem de tentativas no `title`, invisível no toque.
4. **Sem nova tentativa.** Um item que entrou em `erro` (5 tentativas) só podia ser cancelado. Para
   tentar de novo era preciso mexer no banco.
5. **Regra de data sem teste.** `diaFaturamento()` e `proximaDataFaturamento()` eram privados da
   biblioteca, sem cobertura. O cálculo de "próxima ocorrência do dia 5" é justamente o tipo de
   coisa que erra em fim de mês.
6. **Página pouco responsiva.** Formulário com `span4`/`span8` do Bootstrap 2, textos com cor fixa
   (`#1e3a8a`, `#6b7191`, `#8a6d3b`) que somem nos temas escuros, tabela de 6 colunas que só
   rolava na horizontal, e um `style="width:80px"` no campo do dia.
7. **Resultado do processamento invisível.** `processarPendentes()` não devolvia nada: ao clicar em
   "processar vencidos", a pessoa não sabia se emitiu 3, 30 ou nenhum.

## Decision

Uma tela com indicadores e abas, escrita por POST, e a regra de data em código puro testável.

1. **Regras puras em `helpers/automacao_regras_helper.php`** (`AutomacaoRegras`): dia de
   faturamento normalizado (1..28), próxima data, decisão de agendar, situação do item, permissões
   de cancelar/retentar e o resumo da fila. A biblioteca e o controller passam a chamar essas
   funções em vez de repetir a conta.
2. **Escrita só por POST com CSRF.** `processarAgendados`, `cancelarAgendado` e o novo
   `retentarAgendado` recusam GET com mensagem clara e redirecionam. As ações da tela são
   `<form method="post">` com o token, como já faz `Cobrancas::cancelar`.
3. **Nova tentativa para item em erro.** `retentarAgendado` devolve o item ao status `aguardando`
   com contador zerado — a fila volta a tentar sozinha no próximo pulso.
4. **`processarPendentes()` informa o que fez.** Passa a devolver
   `['processados','sucesso','falhas','cancelados']`, e a tela mostra o resultado. O hook que o
   chama ignora o retorno, então nada muda para ele.
5. **Indicadores e abas.** A tela ganha KPIs (aguardando, vencidos, em erro) e duas abas:
   *Configuração* (o formulário, agora em `ui-grade` responsiva) e *Fila de faturamento* (a
   tabela com abas/filtro por situação).
6. **Tabela que vira cartão no celular.** Componente novo `ui-tabela-empilha` em `moderno.css`:
   abaixo de 767px cada linha vira um cartão com o rótulo da coluna ao lado do valor, usando
   `data-rotulo`. Sem rolagem lateral e sem esconder coluna.
7. **Abas genéricas.** `ui-abas` / `ui-aba` em `moderno.css`, com a mesma linguagem visual do
   `nt-abas` da central de notificações, para o padrão deixar de ser exclusivo daquela tela.
8. **Cores do tema, não fixas.** O aviso passa a usar `ui-dica.alerta`, os textos usam
   `var(--ui-texto-2)` e o formulário usa `ui-grade`/`ui-secao`. Os seis temas continuam
   funcionando porque nada depende de cor fixa.

## Implementacao

| Parte | Arquivos |
|---|---|
| Regras puras | `helpers/automacao_regras_helper.php` (novo) |
| Regra aplicada no motor | `libraries/Autoaprovacao.php` |
| Escrita por POST e indicadores | `controllers/Automacao.php` |
| Tela com abas e cartões | `views/automacao/automacao.php`, `assets/css/automacao.css` (novo) |
| Componentes reaproveitáveis | `assets/css/moderno.css` (`ui-abas`, `ui-tabela-empilha`) |
| Testes | `tests/Unit/AutomacaoRegrasTest.php` (novo), `tests/Unit/AutomacaoViewTest.php` (novo) |
| Registro | este ADR |

## Consequences

### Positive
- Emissão fiscal e cancelamento deixam de acontecer por clique em link. O POST com CSRF fecha o
  caminho de prefetch e de link colado em outro lugar.
- A tela responde "como está a fila?" antes de abrir a tabela: quantos esperam, quantos vencem e
  quantos falharam.
- Item em erro tem saída: dá para tentar de novo sem tocar no banco.
- Ao processar manualmente, a pessoa vê quantos foram emitidos, quantos falharam e quantos foram
  cancelados.
- A conta de data virou função pura com teste, então fim de mês e dia 28 deixam de ser aposta.
- No celular a fila fica legível: cada faturamento é um cartão com OS, cliente, data e ações.

### Negative
- O componente `ui-tabela-empilha` exige `data-rotulo` em cada célula. Tabela sem esse atributo
  continua como estava — a migração das outras telas é opcional e pode ser feita aos poucos.
- Duas abas em vez de uma página longa: exige um clique para trocar de contexto. O estado vai na
  URL (`?aba=`), então link e recarregar preservam a aba.
- `retentarAgendado` zera o contador de tentativas. Se o problema for persistente, o item volta a
  falhar 5 vezes antes de parar em erro de novo — o que é o comportamento esperado, mas gasta
  tentativas de emissão fiscal.
- O POST exige JavaScript desligado? Não: são formulários HTML com botão de envio, não `fetch`.
  Funcionam sem JS; o `data-confirmar` apenas acrescenta a pergunta quando há JS.

## Links
- **Estende**: ADR-008 (gestão e financeiro), ADR-009 (interface moderna e responsiva)
- **Related**: ADR-005 (segurança, CSP e CSRF), ADR-023/025 (navegação)
