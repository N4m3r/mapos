# Projeto: Módulo de Obras (Equipe de Obra) — MAP-OS

> Planejamento de um módulo de **gestão de obras** dentro do Mapos, cobrindo as
> lacunas do sistema atual (que é orientado a **OS individuais**) para dar conta
> de **projetos de construção de longa duração**, com **equipes**, **cronograma
> físico**, **diário de obra (RDO)**, **medição** e **faturamento por medição**.

- **Autor:** thailer
- **Data:** 2026-07-25
- **Base:** MAP-OS (CodeIgniter 3, PHP 8.3, MySQL 8)
- **Status:** 📋 Planejamento (nada implementado ainda)

---

## 1. Por que um módulo novo? (as lacunas)

O Mapos hoje gira em torno da **Ordem de Serviço (OS)**: um atendimento pontual,
com 1 técnico responsável (`os.tecnico_responsavel`), produtos/serviços somados,
e um ciclo curto (aberta → finalizada → faturada). Isso funciona muito bem para
assistência técnica e serviços avulsos, mas **não modela obra**. Faltam:

| Lacuna | O que a OS não resolve | O que a obra precisa |
|--------|------------------------|----------------------|
| **Duração** | OS é evento curto | Projeto de semanas/meses com fases |
| **Equipe** | 1 técnico por OS | Equipe (encarregado + pedreiros, serventes, eletricistas…) |
| **Cronograma físico** | Não existe | EAP/etapas com peso %, curva S, previsto × realizado |
| **Progresso** | status textual | % físico executado por etapa |
| **Diário de Obra (RDO)** | Não existe | Registro diário: clima, efetivo, atividades, ocorrências, fotos (exigência de contrato/legal) |
| **Medição** | Faturamento é do total | Medições parciais periódicas → faturamento por medição |
| **Materiais no canteiro** | Produto sai do estoque na OS | Requisição, entrega no canteiro, consumo por etapa |
| **Custo previsto × realizado** | Só preço de venda | Composição de custo (material, mão de obra, equipamento, BDI) |
| **Apontamento de mão de obra** | Não há | Efetivo por dia/etapa (diária/horas) |

**Decisão de arquitetura:** a **Obra** é um **contêiner de projeto**, uma entidade
nova (`obras`) — **não** um tipo de OS. A OS continua existindo e pode,
opcionalmente, ser vinculada a uma obra (ex.: um chamado específico dentro do
canteiro). A obra tem seu próprio ciclo, cronograma e faturamento.

---

## 2. O que dá para REAPROVEITAR do Mapos (não reinventar)

O módulo se apoia fortemente no que já está pronto — essa é a maior economia:

| Recurso existente | Uso na obra |
|-------------------|-------------|
| `clientes` | Cliente/contratante da obra (multi-CNPJ já suportado) |
| `usuarios` + **Módulo RH** (`rh_colaboradores`) | Membros da equipe; funções; diária/salário |
| **Ponto facial + GPS** (RH) | Apontamento de efetivo no canteiro (presença por dia) |
| **Localização em tempo real** (`localizacao_model`, Leaflet) | Equipe/encarregado no mapa do canteiro |
| **Fotos em BLOB** (`fotosatendimento_model`) | Fotos do RDO e da medição |
| **Assinaturas** (`assinaturas_model`, canvas base64) | Assinar RDO e boletim de medição |
| **Módulo Fiscal NFS-e + Boleto/PIX Cora** | Faturamento por medição (nota + cobrança) |
| **Portal do cliente** (multi-CNPJ) | Cliente acompanha % da obra, fotos e medições |
| **Notificações / WhatsApp / E-mail** (gatilhos) | Avisos de RDO, medição aprovada, atraso |
| **Modelos de e-mail configuráveis** | Envio de boletim de medição / relatório |
| **Área do Técnico (mobile-first)** | Base de UI para a **Área de Campo** do encarregado |
| **Formulários de Atendimento** (construtor por etapa) | Checklists de obra (início/durante/fim de etapa) |
| Padrão de **migrations idempotentes** + **permissões serializadas** | Instalação sem quebrar bases existentes |

---

## 3. Modelo de dados proposto

> Convenção do repo: PK `idNome`, InnoDB, migrations idempotentes
> (`if (! $this->db->table_exists(...))`), seed de permissões via
> `unserialize`/`serialize` na tabela `permissoes` (ver
> `20260722000000_add_nao_realizada.php` como referência).

### 3.1 Núcleo

```
obras
  idObra, codigo, nome, clientes_id (FK clientes),
  tipo_obra, contrato_numero, valor_contrato, bdi_percentual,
  cep, logradouro, numero, complemento, bairro, cidade, uf,
  latitude, longitude,
  responsavel_id (FK usuarios — engenheiro/mestre),
  data_inicio_prevista, data_fim_prevista,
  data_inicio_real, data_fim_real,
  status ENUM('planejamento','em_execucao','paralisada','concluida','entregue','cancelada'),
  percentual_concluido (cache, 0-100),
  observacoes, data_cadastro

obra_etapas            -- EAP / cronograma físico (hierárquico via parent_id)
  idEtapa, obra_id, parent_id (self, null=raiz),
  codigo_eap ('1', '1.1', '1.2'...), nome, ordem,
  peso_percentual,           -- peso da etapa no total (soma dos filhos = pai)
  valor_previsto,
  data_inicio_prevista, data_fim_prevista,
  data_inicio_real, data_fim_real,
  percentual_concluido, status

obra_os                -- vínculo opcional OS <-> obra (N OS por obra)
  idVinculo, obra_id, os_id, etapa_id (null)
```

### 3.2 Equipe e mão de obra

```
equipes                -- equipe reutilizável (pode servir a várias obras)
  idEquipe, nome, encarregado_id (FK usuarios/colaboradores), ativo

equipe_membros
  idMembro, equipe_id, colaborador_id (FK rh_colaboradores/usuarios),
  funcao ('Pedreiro','Servente','Eletricista'...),
  valor_diaria, valor_hora, data_entrada, data_saida

obra_equipe_alocacao   -- qual equipe está em qual obra e período
  idAlocacao, obra_id, equipe_id, data_inicio, data_fim, ativo

obra_apontamento       -- efetivo/mão de obra por dia (integra ponto RH)
  idApontamento, obra_id, etapa_id (null), colaborador_id,
  data, horas, diaria (0/1), origem ('ponto_facial','manual'),
  valor, observacao
```

### 3.3 Diário de Obra (RDO)

```
obra_rdo
  idRdo, obra_id, numero (sequencial por obra), data,
  clima_manha, clima_tarde, clima_noite,   -- ensolarado/nublado/chuvoso
  condicao ENUM('praticavel','parcial','impraticavel'),
  responsavel_id, atividades (TEXT), observacoes,
  status ENUM('rascunho','finalizado'), assinatura (MEDIUMTEXT base64),
  data_registro

obra_rdo_efetivo       -- quantos de cada função no dia
  idEfetivo, rdo_id, funcao, quantidade

obra_rdo_ocorrencia    -- imprevistos, atrasos, acidentes
  idOcorrencia, rdo_id, tipo, descricao

obra_rdo_foto          -- BLOB, mesmo padrão de fotosatendimento
  idFoto, rdo_id, foto (LONGBLOB), legenda, data
```

### 3.4 Medição e faturamento

```
obra_medicao
  idMedicao, obra_id, numero, periodo_inicio, periodo_fim,
  percentual_periodo, percentual_acumulado,
  valor_medido, status ENUM('aberta','aprovada','faturada'),
  aprovado_por, data_aprovacao, cobrancas_id (FK, null), nfse_id (null),
  assinatura_cliente (base64, null), data_cadastro

obra_medicao_item      -- % medido por etapa nesta medição
  idItem, medicao_id, etapa_id,
  percentual_anterior, percentual_atual, valor
```

### 3.5 Gestão de material (almoxarifado de obra)

O canteiro funciona como um **almoxarifado próprio da obra**: material **entra**
(recebido do cliente ou por compra), fica em **saldo** e **sai** quando entregue
à equipe. Tudo com **foto** e **leitura de código de barras** (reaproveita o
campo `produtos.codDeBarra` já existente).

```
obra_material_recebimento     -- ENTRADA de material no canteiro
  idRecebimento, obra_id, numero (sequencial por obra),
  origem ENUM('cliente','compra','transferencia','doacao'),
  fornecedor_nome,            -- ou o próprio cliente, quando origem='cliente'
  documento,                  -- nº NF/romaneio/pedido de referência
  recebido_por (FK usuarios), -- quem conferiu no canteiro
  data, observacao,
  assinatura_entregador (MEDIUMTEXT base64, null),  -- quem entregou (motorista/cliente)
  status ENUM('conferido','divergente','cancelado'), data_registro

obra_material_recebimento_item
  idItem, recebimento_id,
  produto_id (FK produtos, null),   -- null = item avulso sem cadastro
  descricao,                        -- usado quando produto_id é null
  cod_barras,                       -- lido/digitado (casa com produtos.codDeBarra)
  unidade, quantidade_prevista, quantidade_conferida,
  divergencia (0/1)

obra_material_foto            -- fotos do recebido/entregue (BLOB, padrão fotosatendimento)
  idFoto, ref_tipo ENUM('recebimento','entrega'), ref_id,
  foto (LONGBLOB), legenda, data

obra_material_saldo           -- saldo atual por produto na obra (entrada - saída)
  idSaldo, obra_id, produto_id (null), descricao, cod_barras,
  unidade, saldo, UNIQUE(obra_id, produto_id/descricao)

obra_material_entrega         -- SAÍDA: entrega para a equipe no campo
  idEntrega, obra_id, numero, etapa_id (null),
  equipe_id (null), colaborador_id,   -- quem recebeu o material
  entregue_por (FK usuarios),         -- almoxarife/encarregado
  data, observacao,
  assinatura_recebedor (MEDIUMTEXT base64, null),  -- assina quem retira
  data_registro

obra_material_entrega_item
  idItem, entrega_id, produto_id (null), descricao, cod_barras,
  unidade, quantidade

obra_requisicao        -- (opcional) pedido formal antes da entrega
  idRequisicao, obra_id, etapa_id (null), solicitante_id,
  status ENUM('solicitada','aprovada','entregue','cancelada'), data
obra_requisicao_item
  idItem, requisicao_id, produto_id (null), descricao, quantidade, quantidade_entregue

obra_custo             -- previsto × realizado (custo do material entra aqui)
  idCusto, obra_id, etapa_id (null),
  categoria ENUM('material','mao_obra','equipamento','terceiros','outros'),
  descricao, valor_previsto, valor_realizado, data
```

**Regras de saldo:** cada `recebimento` conferido **credita** `obra_material_saldo`;
cada `entrega` **debita**. A UI bloqueia (ou apenas avisa) entrega acima do saldo,
conforme configuração. Material `origem='cliente'` é marcado como **propriedade do
cliente** — entra no controle de custódia, mas **não** no custo próprio da obra.

---

## 4. Permissões (padrão v/c/e/d do Mapos)

Adicionar via migration (seed no `permissoes`) e nos dois formulários de
`Permissoes` (adicionar + editar). Herança sugerida no seed:

| Chave | Descrição | Herda de |
|-------|-----------|----------|
| `vObras` | Ver obras | `vOs` |
| `cObras` | Cadastrar obra | `cOs` |
| `eObras` | Editar obra | `eOs` |
| `dObras` | Excluir obra | `dOs` |
| `vObraCronograma` / `eObraCronograma` | Cronograma/EAP | `vObras` |
| `vObraRdo` / `cObraRdo` | Diário de obra | `vObras` |
| `vObraMedicao` / `cObraMedicao` / `aObraMedicao` | Medição (a = aprovar) | financeiro/`eOs` |
| `vObraCusto` | Ver custos (dado sensível) | `vFinanceiro` |
| `vObraMaterial` / `cObraMaterial` | Almoxarifado da obra (ver/lançar) | `vProdutos` |
| `rObraMaterial` | Receber material (entrada, do cliente) | `cObraMaterial` |
| `sObraMaterial` | Entregar material à equipe (saída) | `cObraMaterial` |
| `vObraCampo` | **Área de Campo** (encarregado, mobile) | `vTecnicoDashboard` |
| `cObraEquipe` | Gerenciar equipes | `cSistema` |

---

## 5. Arquitetura de código (arquivos)

```
application/
  controllers/
    Obras.php            -- CRUD obra, dashboard da obra, cronograma, custos
    Obrardo.php          -- Diário de obra (listar/criar/finalizar/imprimir)
    Obramedicao.php      -- Medições + aprovação + gerar faturamento
    Obraequipe.php       -- Equipes e alocação
    Obramaterial.php     -- Recebimento (entrada), almoxarifado/saldo, entrega (saída), busca por cód. barras
    Obracampo.php        -- Área de Campo do encarregado (mobile, reusa layout tecnico/)
  models/
    Obras_model.php
    Obra_etapa_model.php
    Obra_rdo_model.php
    Obra_medicao_model.php
    Obra_equipe_model.php
    Obra_material_model.php  -- recebimento, saldo, entrega, custo
  views/
    obras/               -- gerenciar, adicionar, editar, visualizar (abas), cronograma
    obrardo/             -- lista, formulario, imprimir
    obramedicao/         -- lista, boletim, imprimir
    obramaterial/        -- almoxarifado, receber, entregar, imprimir comprovante
    obracampo/           -- dashboard, rdo_rapido, apontamento, receber, entregar (mobile-first)
  database/migrations/
    20260726000000_add_modulo_obras.php          -- núcleo (obras, etapas, os, equipes) + permissões
    20260726000001_add_obra_rdo.php              -- RDO + efetivo + ocorrência + foto
    20260726000002_add_obra_medicao.php          -- medição + itens
    20260726000003_add_obra_material.php         -- recebimento, saldo, entrega, fotos, requisição, custos
assets/
  css/obras.css
  js/obras.js            -- cronograma (curva S via Chart.js já usado no dash), medição inline
  js/obra-scanner.js     -- leitura de código de barras via câmera (ZXing/html5-qrcode/Quagga)
```

Menu: adicionar grupo **"Obras"** em `application/views/tema/menu.php` (itens:
Obras, Diário de Obra, Medições, Equipes), com checagem `vObras`. Área de Campo
entra no menu mobile do técnico (`views/tecnico/_nav.php`) sob `vObraCampo`.

---

## 6. Fluxos principais

### 6.1 Ciclo da obra
```
Planejamento → Em execução → (Paralisada) → Concluída → Entregue
     │              │                            │
  cadastro     RDO diário +                  medição final +
  + EAP +      apontamento +                 faturamento +
  contrato     medições parciais             entrega/assinatura
```

### 6.2 Progresso físico (como o % sobe)
1. Encarregado registra **RDO** e atualiza `% concluído` das etapas do dia.
2. `obra_etapas.percentual_concluido` recalcula o `obras.percentual_concluido`
   ponderado pelo `peso_percentual` (rollup da EAP).
3. Curva S = previsto (do cronograma) × realizado (acumulado das medições).

### 6.3 Medição → faturamento (reuso do fiscal/Cora)
1. Abre medição do período → informa % atual por etapa → sistema calcula valor.
2. `aObraMedicao` aprova (com assinatura do cliente opcional).
3. Botão **"Faturar medição"** reaproveita o pipeline existente:
   emite **NFS-e** e gera **Boleto+PIX Cora** (ver memória `cora-boleto-pix`),
   gravando `nfse_id`/`cobrancas_id` na medição.

### 6.4 Área de Campo (mobile — encarregado)
Reusa o layout e o motor da Área do Técnico:
- Dashboard da obra do dia (etapas, efetivo previsto).
- **RDO rápido**: clima, efetivo, atividades, fotos, assinatura — 1 tela.
- Apontamento de presença (puxa do **ponto facial/GPS** quando disponível).
- **Recebimento** e **entrega** de material (ver 6.5) — com câmera e leitor.

### 6.5 Gestão de material: recebimento → saldo → entrega

**a) Recebimento de material do cliente (entrada)** — mobile-first:
1. Encarregado/almoxarife abre "Receber material" na obra.
2. **Lê o código de barras** de cada item pela câmera (ou digita). O sistema
   busca em `produtos.codDeBarra`; se não achar, permite item **avulso** (só
   descrição + quantidade).
3. Confere quantidade prevista × recebida (marca **divergência** se diferente).
4. **Tira fotos** do material/romaneio (BLOB, padrão `fotosatendimento`).
5. Quem entrega (motorista/cliente) **assina** na tela (canvas base64).
6. Ao confirmar → credita `obra_material_saldo`. Material do **cliente** fica
   como custódia (não entra no custo próprio).

**b) Entrega de material para a equipe no campo (saída)**:
1. Almoxarife/encarregado abre "Entregar material".
2. Seleciona quem recebe (equipe/colaborador) e a etapa (opcional).
3. **Lê o código de barras** dos itens e informa a quantidade (valida saldo).
4. Fotos opcionais + **assinatura do recebedor** (rastreabilidade de quem retirou).
5. Ao confirmar → debita `obra_material_saldo` e lança **custo realizado**
   (`obra_custo`, categoria `material`) quando o material for próprio.

**Leitura de código de barras (client-side):** biblioteca JS embarcada (ex.
ZXing/`html5-qrcode` ou QuaggaJS) usando `getUserMedia` da câmera do celular;
o valor lido é casado com `produtos.codDeBarra` via endpoint AJAX
(`obras/buscarProdutoPorCodBarra`). Fallback sempre disponível: **digitar** o
código ou escolher o produto na lista. Nada exige app nativo — roda no navegador
do canteiro, igual à Área do Técnico.

---

## 7. Roadmap por fases (incremental e testável)

Cada fase é entregável e independente — dá para parar e usar.

| Fase | Entrega | Depende de | Reuso-chave |
|------|---------|-----------|-------------|
| **0. Fundação** | Migration núcleo, CRUD de obra, menu, permissões, aba "Visão geral" | — | migrations/permissões |
| **1. Equipes & apontamento** | Equipes, alocação à obra, apontamento de efetivo | 0 | RH/ponto facial, GPS |
| **2. Cronograma físico (EAP)** | Etapas hierárquicas, pesos, datas, % e curva S | 0 | Chart.js do dashboard |
| **3. RDO (Diário de Obra)** | RDO web + Área de Campo mobile + impressão | 1,2 | layout técnico, fotos BLOB, assinaturas |
| **4. Medição + faturamento** | Medições parciais → NFS-e + boleto Cora | 2 | módulo fiscal, Cora |
| **5. Gestão de material** | Recebimento (do cliente) c/ foto + leitura de código de barras, almoxarifado/saldo, entrega à equipe no campo, custo previsto × realizado | 1 | produtos (`codDeBarra`), fotos BLOB, assinaturas |
| **6. Portal & relatórios** | Cliente acompanha obra; dashboards e relatórios gerenciais | 3,4 | portal cliente, notificações |
| **7. Segurança do trabalho (opcional)** | DDS, controle de EPI, ocorrências/acidentes | 3 | formulários de atendimento |

**Sugestão de MVP:** Fases **0 → 2 → 3** entregam o coração ("obra com
cronograma e diário de obra com fotos"). Fase **4** agrega o faturamento por
medição (maior valor financeiro). O resto é evolução.

---

## 8. Riscos e decisões em aberto

- **Obra × OS:** confirmado como entidades separadas com vínculo opcional
  (`obra_os`). Ponto a validar: uma etapa gera OS automaticamente? (sugiro
  **não** no MVP; manual).
- **Peso da EAP:** somatório dos pesos deve fechar 100% no nível — validar no
  backend e avisar na UI (não travar).
- **Apontamento:** integrar com ponto facial exige que os membros da equipe
  sejam `rh_colaboradores`. Fallback: apontamento manual por função/quantidade.
- **Faturamento por medição × faturamento agendado:** garantir que a medição
  respeite o dia de faturamento do cliente (memória `faturamento-agendado`).
- **Volume de fotos (BLOB):** RDO diário gera muitas fotos; avaliar limite de
  tamanho/compressão como já feito em `fotosatendimento`.

---

## 9. Como instalar (padrão do repo)

Depois de implementadas as migrations:

**Pelo sistema:** Configurações → Sistema → **Atualizar Banco de Dados**.

**Pelo terminal:**
```bash
php index.php tools migrate
```

As migrations são idempotentes (checam `table_exists` e chaves de permissão),
então rodam com segurança sobre bases já existentes.

---

## 10. Próximo passo

Recomendo começar pela **Fase 0 (Fundação)**: migration do núcleo
(`obras` + `obra_etapas` + `obra_os` + permissões), o controller/model `Obras`
com CRUD, o item de menu e a tela de "Visão geral" da obra. É a base sobre a
qual todo o resto encaixa, e já entrega valor (cadastro e listagem de obras).

> Documentos relacionados: `docs/PROJETO-TECNICO.md`, `docs/projeto-README.md`.
> Memórias relevantes: `modulo-rh`, `modulo-fiscal-nfe`, `cora-boleto-pix`,
> `portal-cliente-multi-cnpj`, `tecnico-area-mobile`, `formularios-atendimento`,
> `faturamento-agendado`, `notificacoes-gatilhos`.
</content>
</invoke>
