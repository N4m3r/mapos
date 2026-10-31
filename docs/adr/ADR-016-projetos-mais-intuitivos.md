# ADR-016: Área de projetos mais intuitiva (assistente, próximo passo e painel de pontos)

- **Status**: accepted
- **Date**: 2026-10-30
- **Deciders**: N4m3r
- **Tags**: projetos, ux, design, assistente, pontos, celular
- **Branch**: `seguranca-23-10`

## Context

A auditoria de usabilidade (capturas em 1366 e 390 px do projeto demo) mostrou:

- Montar um projeto completo levava mais de 20 cliques em 5 abas, sem nada indicando o próximo passo.
- Novo projeto era um formulário de ~21 campos numa tela só.
- A página do projeto tinha 9 abas e 14 modais; a "Visão geral" mostrava só dados soltos do contrato.
- "Gerar OS" aparecia em todas as etapas, mesmo nas que já tinham OS; botões azuis largos para tudo.
- Jargão na interface (EAP, Itens prev., RDO, Repasse) e "Obras" no breadcrumb enquanto a tela dizia "Projetos".
- O painel empilhava 9 blocos sem priorizar o que precisa de ação; no celular o cabeçalho ocupava ~400 px.

## Decision

Reorganizar a área de projetos sem mudar regras de negócio, rotas nem nomes de campos, usando os
tokens do ADR-009 (`moderno.css`). Um único elemento visual de destaque: o painel de pontos.

1. **Novo projeto em 3 passos** (cliente e tipo → escopo → prazo e equipe): etapas padrão do tipo já
   marcadas, pontos por tipo com "− N +", técnicos; tudo criado numa transação
   (`Obra_planejamento_model::montarProjetoInicial`). Editar mantém o formulário, com "Mais detalhes" recolhido.
2. **Página do projeto**: trilho Montar → Planejar → Executar → Entregar, faixa "Próximo passo" com um botão
   (`Projeto_guia::proximoPasso`, função pura), ação primária contextual e menu "Mais". Abas de 9 para 6:
   Resumo, Planejamento, Pontos, Execução, Material, Financeiro (hashes antigos redirecionam).
3. **Painel de pontos**: os pontos desenhados como patch panel de rack, cor por status, agrupados por tipo.
4. **Lista e painel**: chips de status com contagem, progresso, prazo e aviso por projeto
   (`Projeto_acompanhamento_model::resumoLista`, sem N+1); "Precisa de atenção" no topo do painel e demais
   blocos recolhíveis.
5. **Linguagem**: Nº, Materiais e serviços, Registro do dia, Passar para outro técnico, dicas em Medição e BDI;
   breadcrumb diz "Projetos".

## Implementacao

| Parte | Arquivos |
|---|---|
| Assistente | `views/obras/adicionar.php`, `_form.php`, `assets/js/projeto-assistente.js`, `assets/css/projeto-assistente.css`, `Obras::adicionar`, `Obras::etapasPadraoTipo` |
| Página | `views/obras/visualizar.php`, `libraries/Projeto_guia.php`, `assets/css/projeto-pagina.css`, `Obras::visualizar` |
| Lista e painel | `views/obras/gerenciar.php`, `painel.php`, `_nav.php`, `assets/css/projeto-lista.css`, `obras.css` (tokens) |
| Breadcrumb | `views/tema/conteudo.php` |

## Verificação

- PHPUnit: 144 testes (novos: `ProjetoGuiaTest`, casos de `ObraPlanejamentoTest`).
- Navegador: `tests/navegador/projetos.mjs` (28 verificações, vocabulário e seletores atualizados).
- Capturas antes/depois em 1366 e 390 px.

## Consequences

### Positive
- Um projeto completo (etapas, pontos, equipe) sai em 3 passos e um clique.
- O gestor vê o que falta e o atalho para resolver; o técnico tem cartões e botões de 44 px no celular.

### Negative
- `visualizar.php` foi reescrito: mudanças futuras precisam respeitar os ids usados por JS e testes.
- `repasse.mjs` não foi rodado nesta mudança (precisa de dois técnicos e foto).
- Projetos sem pontos (tipo genérico) usam o próximo passo sem a etapa de pontos.

## Links
- **Depends on**: ADR-009, ADR-013, ADR-015
