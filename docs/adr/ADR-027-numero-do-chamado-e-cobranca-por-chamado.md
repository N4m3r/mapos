# ADR-027: Número do chamado na OS e cobrança por chamado

- **Status**: accepted
- **Date**: 2026-11-07
- **Deciders**: N4m3r
- **Tags**: os, cobranca, aprovacao, financeiro, ux, seguranca
- **Relacionado**: ADR-007 (acompanhamento público e NPS), ADR-011 (relatório de cobrança), ADR-020 (OS realizadas), ADR-005 (segurança e CSP)

## Context

O atendimento chega pelo chamado do cliente (telefone, WhatsApp ou portal do contratante), mas a OS
não guardava essa referência. Três consequências apareceram na operação:

1. **A OS não podia ser encontrada pela referência que o cliente usa.** A equipe pedia o número da
   OS; o cliente só tinha o protocolo do chamado dele. A busca da listagem cobria cliente e
   documento, não o protocolo.
2. **O que ainda não estava cobrado ficava espalhado.** A aba "A cobrar" de Cobranças mostrava só
   documentos finalizados sem cobrança. Quem estava aguardando aprovação do cliente — o caso mais
   comum antes de emitir qualquer cobrança — não aparecia em lugar nenhum, e a decisão de cobrar
   dependia de abrir OS por OS.
3. **O cliente sem contato utilizável ficava sem saída.** O link de aprovação é enviado aos contatos
   cadastrados. Quando a OS foi aberta com e-mail errado ou celular incompleto, o cliente que tinha
   o chamado em mãos não tinha como pedir o link: a página de link inválido só mandava "fale com a
   empresa".

## Decision

1. **Campo `chamado_id` na OS**, opcional, com índice próprio e busca na listagem. Preenchido no
   cadastro e na edição, exibido na visualização e nas duas vias impressas. Nada é obrigatório: OS
   aberta por balcão continua funcionando sem chamado.

2. **Painel "Cobrança dos chamados"**, um só lugar com três origens reunidas: OS aguardando aprovação,
   documento sem cobrança (`Cobrancas_model::aCobrar`, sem duplicar a regra) e cobrança emitida com
   status classificado pelo gateway (`cobranca_helper`). Indicadores por situação, dois gráficos
   (valor por situação e maiores saldos em aberto), filtro por cliente, por chamado e por situação.

3. **Seleção múltipla com ação em lote**, sempre por POST com CSRF: solicitar aprovação, confirmar
   pagamento e cancelar cobrança. A seleção só aparece onde há ação: linha de venda não tem checkbox
   porque o servidor não aceita `vend:` em lote.

4. **Página pública `aprovacao/solicitar`**, onde o cliente informa chamado e CPF/CNPJ e recebe o
   link nos contatos cadastrados. A resposta é idêntica exista ou não a OS, e o envio passa pelo
   `Limite_login` por IP: sem isso o formulário viraria verificador de chamados alheios.

## Implementacao

| Parte | Arquivos |
|---|---|
| Campo e busca | `database/migrations/20261107000000_add_os_chamado_id.php` (novo), `models/Os_model.php`, `controllers/Os.php` |
| Formulários e impressão | `views/os/adicionarOs.php`, `editarOs.php`, `os.php`, `visualizarOs.php`, `imprimirOs.php` |
| Regras puras do painel | `helpers/chamado_cobranca_helper.php` (novo) |
| Dados do painel | `models/Chamado_cobranca_model.php` (novo) |
| Tela e ações | `controllers/Chamadoscobranca.php` (novo), `views/chamadoscobranca/index.php` (novo) |
| Pedido do cliente | `controllers/Aprovacao.php` (`solicitar`, `enviarLink`), `views/aprovacao/solicitar.php` (novo), `models/Aprovacao_model.php` |
| Navegação | `helpers/menu_navegacao_helper.php`, `views/cobrancas/cobrancas.php`, `config/routes.php` |
| Testes | `tests/Unit/ChamadoCobrancaTest.php`, `tests/Unit/ChamadoCobrancaViewTest.php` (novos) |
| Registro | este ADR, `CHANGELOG.md` |

## Consequences

### Positive
- A OS passa a ser encontrável pelo número que o cliente já tem em mãos.
- A pergunta "o que precisamos cobrar?" tem uma tela só, com valor por situação em vez de uma tabela
  para ler linha a linha.
- A cobrança em lote evita repetir o mesmo clique dezenas de vezes no fechamento do mês.
- O cliente que perdeu o link se resolve sozinho, sem abrir chamado para a empresa.
- O formulário público não revela se um chamado existe: a mesma mensagem cobre os dois casos.

### Negative
- O painel consulta as três origens e classifica em PHP, como a aba "A cobrar" já fazia. Em base
  muito grande a tela pesa mais que uma listagem paginada no banco; o limite de 2.000 cobranças
  recentes é a válvula atual.
- O campo de chamado é livre: dois clientes podem usar o mesmo protocolo. A busca então devolve mais
  de uma OS, e a conferência de documento na página pública existe justamente por isso.
- O envio do link depende de WhatsApp ou e-mail configurados. Sem canal ativo, o pedido do cliente é
  aceito mas não sai — o log registra a tentativa, e a tela responde com a mensagem neutra.
- O limite por IP usa a tabela de tentativas do login. Escritório com IP compartilhado pode ser
  bloqueado por erros de outra pessoa da mesma rede.

## Links
- **Estende**: ADR-007 (acompanhamento público), ADR-011 (cobrança por cliente), ADR-020 (relatório de OS realizadas)
- **Related**: ADR-005 (segurança, CSP e CSRF), ADR-023/025 (navegação)
