# ADR-022: Planejamento tributário do IVA (CBS/IBS) no sistema

- **Status**: proposed
- **Date**: 2026-10-31
- **Deciders**: N4m3r
- **Tags**: fiscal, reforma-tributaria, ibs, cbs, simples-nacional, nfe, nfse, zfm, cobranca
- **Branch**: `master`

> **Aviso.** A pesquisa (31/10/2026) usou fontes secundárias (consultorias, escritórios, portais
> contábeis). Itens marcados **[CONFIRMAR]** precisam ser checados no texto oficial (Planalto,
> Portal Nacional da NF-e, gov.br/nfse) e com o contador **antes** de virar código.

## Context

### Onde a reforma está (out/2026)

| Tema | Situação | Fonte |
|---|---|---|
| 2026 = ano de teste | CBS 0,9% + IBS 0,1%, destacados na nota e não recolhidos por quem cumpre as obrigações acessórias. | barbieriadvogados.com, contabeis.com.br |
| LC 227/2026 (ex-PLP 108/2024) | Sancionada em 13/01/2026, cria o Comitê Gestor do IBS (CG-IBS). | cesconbarrieu.com.br |
| 2027 | CBS passa a ser cobrada; IBS continua 0,1% (0,05% UF + 0,05% município). Alíquota de referência da CBS **[CONFIRMAR]** (8,8% circula sem fonte). PIS/Cofins extintos e IPI zerado fora da ZFM **[CONFIRMAR]**. | econeteditora, jornalcontabil |
| 2029–2032 | ICMS e ISS caem em degraus; 2033 só IBS/CBS. Percentuais por ano **[CONFIRMAR]**. | barbieriadvogados.com |
| NF-e/NFC-e | NT 2025.002 RTC **v1.40** (20/05/2026). Rejeição 1115 (falta do grupo IBS/CBS) para regime normal (CRT 3): produção desde 03/08/2026 segundo várias fontes, suspensa segundo outra **[CONFIRMAR]**. | fiemg.com.br, noticias.iob.com.br |
| NFS-e nacional | NT 005 a 009 trazem IBS/CBS na DPS, NBS, `cIndOp` e o Anexo VIII (LC 116 × NBS × cIndOp × cClassTrib). **Sem rejeição até 31/12/2026**; a partir de 2027, a ausência do grupo passa a rejeitar **[CONFIRMAR]**. NT 008 mudou o DANFSe. | jettax.com.br, systax.com.br, fenacon.org.br |
| Simples Nacional | Fora do teste em 2026; a obrigação de preencher para Simples/MEI começa em 2027 **[CONFIRMAR]**. A partir de 2027 pode escolher **por semestre**: IBS/CBS dentro do DAS ou no regime regular ("por fora"). | fenacon.org.br, noticias.iob.com.br |
| Prazos da opção (Res. CGSN 194/2026) | 1º sem/2027: 01/09 a **30/10/2026** (encerrado). Cancelamento: 03/11 a 20/12/2026. 2º sem/2027: **01 a 31/03/2027**. | cesconbarrieu.com.br |
| Crédito do cliente | Fornecedor no DAS: o cliente credita só o que foi efetivamente recolhido (art. 47, §9º LC 214). Fornecedor no regime regular: crédito integral do destacado. | fenacon.org.br, barbieriadvogados.com |
| Crédito em geral | Crédito financeiro: só existe se o tributo da etapa anterior foi **pago** (art. 47 LC 214). | simtax.com.br |
| Zona Franca de Manaus | Incentivos mantidos até 2073. Remessas para a ZFM equiparadas a exportação, com crédito presumido (art. 450 LC 214). Percentuais e o caso de **serviço prestado por empresa local** **[CONFIRMAR]**. ISS segue com Manaus até a troca pelo IBS. | mattosfilho.com.br, mayerbrown.com |
| Split payment | Não fica pronto em jan/2027, sem data nova; o RAD é opcional em 2027. Nada obrigatório para boleto/PIX hoje. | netcpa.com.br, notagateway.com.br |
| Reduções | 30% para profissões intelectuais regulamentadas (art. 127, lista taxativa) e 60% para saúde/educação/cultura. Instalação de CFTV e TI **não** aparecem: alíquota padrão **[CONFIRMAR]**. | simtax.com.br |

### O que o sistema já tem

- **NF-e**: grupo IBS/CBS por item (PL_010) quando `reforma_ativa`, mas com **alíquota, CST e
  cClassTrib globais** de `configuracoes_nfe` (`NfeService.php:281-305`). PIS/Cofins, CSOSN e origem
  fixos no código (`NfeService.php:260-279`).
- **NFS-e**: `anexarIbsCbsDps` existe (`NfseService.php:108-141`), mas **não roda**:
  `nfseSuportaIbsCbs()` só liga quando a biblioteca `hadder/nfse-nacional` tiver o XSD com IBSCBS.
  **Hoje a DPS sai sem IBS/CBS.**
- **Cadastros**: produto só tem NCM/CEST/CFOP; serviço só tem cTribNac/cTribMun; cliente só tem IE.
  Não existem cClassTrib, CST IBS/CBS, NBS, `cIndOp`, regime do cliente nem SUFRAMA.
- **Entradas**: `notas_entrada` guarda os totais IBS/CBS e `v_cred_pres_zfm`, e há uma apuração
  simples (débito NF-e − crédito marcado). Não lê itens nem a data de pagamento, e não soma a NFS-e.
- **Configuração e cobrança** (feito em 31/10/2026): perfil MEI/Simples/Normal, preset de 2026,
  `simples_aliquota_efetiva`, estimativa de DAS + IBS/CBS na cobrança. As alíquotas de 2026 estão fixas
  no `tributacao_helper`, sem tabela por ano.

## Decision

Implementar em 4 fases, priorizando **o que gera rejeição de nota**, depois **cadastro por item**,
depois **a virada de 2027** e por fim **apuração/crédito**. Tudo que depende de número legal fica em
**tabela versionada** (nunca constante no código), para atualizar sem deploy quando sair NT ou
resolução nova.

### Fase 0: não ter nota rejeitada (até 31/12/2026)

| # | Tarefa | Agente | Por quê |
|---|---|---|---|
| T1 | **IBS/CBS na DPS da NFS-e** (NT 005-009): atualizar `hadder/nfse-nacional` se já suportar; senão injetar o grupo no XML antes de assinar. Validar contra o XSD oficial. | sonnet + revisão opus | Sem isso, toda NFS-e de 2027 pode ser rejeitada. |
| T2 | **Conferir a NF-e na NT 2025.002 v1.40** (versão do `sped-nfe`, campos novos, rejeições 1023-1025 e 1115) e avisar na tela fiscal quando o emitente é regime normal e `reforma_ativa` está desligado. | haiku (checagem) + sonnet (ajuste) | Regime normal já pode ter rejeição 1115. |
| T3 | **DANFSe conforme NT 008** (CST, cClassTrib, indicador de operação, município de incidência). | haiku | Documento auxiliar oficial mudou. |

### Fase 1: cadastro fiscal por item (nov–dez/2026)

| # | Tarefa | Agente |
|---|---|---|
| T4 | **Tabelas oficiais versionadas**: CST IBS/CBS, cClassTrib, cCredPres e Anexo VIII (LC 116 × NBS × cIndOp × cClassTrib), com importação de CSV/JSON, data de vigência e tela "Atualizar tabelas". | sonnet |
| T5 | **Produto e serviço com classificação própria**: cClassTrib, CST, NBS, cIndOp e redução, herdando o padrão da config quando vazio. Sugestão automática pelo Anexo VIII a partir do código LC 116 do serviço. | haiku (migration) + sonnet (telas) |
| T6 | **Cliente com perfil fiscal**: contribuinte (indIEDest explícito), regime (MEI/Simples/Normal), órgão público e inscrição SUFRAMA/ZFM. | haiku |
| T7 | **Emissão por item**: NfeService/NfseService usam o cadastro do item (T5), e a config vira só o padrão. Pré-validação antes de transmitir (cClassTrib compatível com CST e modelo), para pegar 1023-1025 sem gastar número. | sonnet + revisão opus |

### Fase 2: virada de 2027 (dez/2026–jan/2027)

| # | Tarefa | Agente |
|---|---|---|
| T8 | **Parâmetros por ano** (`tributos_parametros`: ano, CBS, IBS UF, IBS mun, fonte, vigência). O helper lê o ano da emissão; 2026 e 2027 vêm pré-carregados, e o resto é editado quando sair a resolução. | haiku (migration + função pura + testes) |
| T9 | **Opção do Simples por semestre** (DAS ou regime regular) com vigência; define destaque, CST e o texto ao cliente sobre o crédito dele. Lembrete no painel da janela **01–31/03/2027**. | sonnet |
| T10 | **Preço com IVA visível** em orçamento, OS e cobrança: a estimativa passa a usar T8 + T9 e mostra "quanto o cliente pode creditar". | haiku |

### Fase 3: apuração e crédito (1º semestre/2027)

| # | Tarefa | Agente |
|---|---|---|
| T11 | **Entradas completas**: ler itens, CST/cClassTrib, cCredPres, se o fornecedor é do Simples e a **data de pagamento** (crédito só com pagamento). | sonnet |
| T12 | **Apuração assistida mensal**: débitos (NF-e + NFC-e + NFS-e) × créditos pagos, saldo, exportação CSV/PDF e conferência com a apuração da RFB/CG-IBS. | sonnet |
| T13 | **Simulador "DAS × regime regular"** com o faturamento real e o perfil dos clientes (quantos são empresas que creditam), para decidir a opção semestral com o contador. | sonnet |

### Fase 4: acompanhar regulamentação (contínuo)

| # | Tarefa | Agente |
|---|---|---|
| T14 | **ZFM**: operações para destinatário com SUFRAMA (crédito presumido, IPI); só depois de confirmar com o contador o caso de serviço local. **Feito: ISUF + aviso. Pendente de contador: crédito presumido/CST para operações com ZFM.** | opus + contador |
| T15 | **Split payment**: guardar na cobrança o "valor do tributo" e o "líquido", prontos para quando sair o ato conjunto. Sem obrigação hoje. | haiku |
| T16 | **Monitor de mudanças**: rotina mensal (`/schedule`) que confere nova NT/tabela/resolução e abre tarefa no Ruflo. | haiku |

### Como os agentes foram escolhidos (economia de tokens)

- **Haiku**: migration, função pura com teste, importação de tabela, texto de tela, checagem de versão.
  É barato e o erro é fácil de ver no teste.
- **Sonnet**: telas com regra, serviços de emissão, parser de XML, relatórios. Recebe no prompt os
  arquivos e linhas já levantados neste ADR, para não reexplorar o código.
- **Opus (sessão principal)**: só a revisão do XML fiscal (T1, T7) e as decisões com risco legal (T14).
- **Uma frente por agente, sem arquivos em comum**, em paralelo quando não há dependência (ex.: T4 ∥ T6 ∥ T8).
- Pesquisa nova só para itens **[CONFIRMAR]**, com busca "standard" antes da "extended".

## Consequences

- **Positivo**: nota sem rejeição na virada de 2027; números legais trocados por tabela, sem deploy;
  o usuário leigo vê preço, imposto e crédito do cliente em linguagem simples; decisão do Simples
  baseada em dados reais.
- **Negativo**: cadastro por item dá trabalho inicial (mitigado pelo padrão herdado e pela sugestão do
  Anexo VIII); dependência de bibliotecas externas (`sped-nfe`, `hadder/nfse-nacional`) que podem
  atrasar o suporte às NTs.
- **Risco**: fontes secundárias. Nada da Fase 0 vai para produção sem homologação na SEFAZ/ADN e sem o
  contador validar CST/cClassTrib dos serviços de CFTV/TI.

## Ordem e dependências

```
T1 ─┐
T2 ─┼─> (homologação) ─> produção até 31/12/2026
T3 ─┘
T4 ─┬─> T5 ─> T7
T6 ─┘
T8 ─> T9 ─> T10
T11 ─> T12 ─> T13
T14, T15, T16: independentes, após confirmação legal
```

## Implementação (31/10/2026)

Todas as tarefas T1–T17 foram implementadas (sem commit), com testes de unidade e as telas abertas no
ambiente local. Nada foi transmitido à SEFAZ/ADN.

| Tarefa | Onde |
|---|---|
| T1 NFS-e IBS/CBS | `NfseService::injetarIbsCbsXml` (a DPS só declara CST/cClassTrib/cIndOp; o ADN calcula os valores). XSD oficial v1.01 (20260209) em `libraries/Fiscal/xsd/nfse/`. Sem `cIndOp` o grupo não vai. |
| T2 NF-e v1.40+ | sped-nfe 5.2.9 já suporta (cita v1.50). Regime normal sempre envia IBS/CBS; `tributacao_validar_ibscbs` antes de montar. |
| T3 DANFSe NT 008 | `danfse_dados_ibscbs` + bloco no `danfse_print.php`. |
| T4 Tabelas oficiais | `tributacao/tabelas` (importação CSV versionada). **O seed só tem o subconjunto mínimo: importar as tabelas oficiais.** |
| T5 Classificação por item | Produto/serviço com CST, cClassTrib, NBS, cIndOp, redução; sugestão `tributacao/sugerir/{LC116}`. |
| T6 Perfil fiscal do cliente | indIEDest, regime, órgão público, SUFRAMA. |
| T7 Emissão por item | `classificacao_efetiva` + `fiscal_prevalidar_itens` (também na prévia). NFS-e com serviços de classificação diferente é bloqueada. |
| T8 Parâmetros por ano | `tributos_parametros` (2026 confirmado; 2027 estimado). |
| T9 Opção do Simples | `simples_opcao_ibscbs` por semestre + aviso de janela no painel. |
| T10 IVA na cobrança | `cobranca_impostos_ano` + crédito do cliente no modal. |
| T11 Entradas | Itens, CRT do fornecedor, data de pagamento (crédito só se pago). |
| T12 Apuração | `entradas/apuracao` + CSV; NF-e, NFC-e e NFS-e; ZFM e IBS UF/Mun separados. |
| T13 Simulador | `tributacao/simulador`; crédito acima do débito vira saldo credor. Parcela de IBS/CBS no DAS = 30% ilustrativo **[CONFIRMAR]**. |
| T14 ZFM | ISUF na NF-e + aviso na prévia. Crédito presumido/CST: pendente de contador. |
| T15 Split payment | `cobrancas.v_ibs_estimado/v_cbs_estimado/v_liquido_estimado`. |
| T16 Monitor | `tributacao/central` + `php index.php tributacao verificar` (cron mensal). |
| T17 Regime normal | CRT 3, ICMS 00/40/41/50/60, PIS/COFINS cumulativo/não cumulativo, desconto rateado por item. ST, redução de base e DIFAL de não contribuinte não são suportados (bloqueiam com mensagem). |
