# ADR-002: Proposta de melhorias de performance e usabilidade do MapOS

- **Status**: accepted (parcialmente implementado - ver "Implementacao" no fim)
- **Date**: 2026-10-23
- **Backup**: tag e branch `backup_mudanca_23-10` (estado anterior a estas mudancas)
- **Deciders**: N4m3r
- **Tags**: performance, ux, banco-de-dados, frontend

## Context

Analise feita no codigo da branch `beta` (commit `f28442a`). Os pontos abaixo
foram encontrados no codigo, nao sao suposicoes genericas.

### Banco de dados

| # | Achado | Onde |
|---|--------|------|
| B1 | Faltam indices nas colunas mais filtradas: `os.status`, `vendas.status`, `lancamentos.data_pagamento`, `lancamentos.data_vencimento`, `lancamentos.baixado`/`tipo`, `produtos.codDeBarra`, `email_queue.status`, `anotacoes_os.os_id`. Sem indice, cada filtro le a tabela inteira. | `database/migrations/20260720000000_consolidado_completo.php` |
| B2 | As estatisticas do dashboard executam a **mesma query duas vezes** (`if ($this->db->query($sql…)) return $this->db->query($sql…)->row()`), em 4 lugares. | `models/Mapos_model.php` |
| B3 | Filtro `WHERE EXTRACT(YEAR FROM data_pagamento) = ?` impede o uso de indice. O equivalente `data_pagamento BETWEEN 'AAAA-01-01' AND 'AAAA-12-31'` usa indice. | `models/Mapos_model.php` (3 ocorrencias) |
| B4 | O dashboard (`Mapos::index`) dispara cerca de 15 consultas a cada carregamento, sem cache. As 6 listas de OS por status poderiam ser uma unica consulta. | `controllers/Mapos.php:25-40` |
| B5 | A busca global usa `LIKE '%termo%'` com varios `OR` em 4 tabelas, ou seja, varredura completa a cada tecla. | `Mapos_model::pesquisar` |
| B6 | `save_queries = true` guarda todas as queries na memoria de cada requisicao, inclusive em producao. | `config/database.php:26` |

### Servidor / PHP

| # | Achado | Onde |
|---|--------|------|
| S1 | A sessao fica no banco (`sess_driver = database`), entao cada pagina faz leitura e escrita em `ci_sessions`. A imagem Docker ja tem a extensao `redis` instalada, mas ela nao e usada. | `config/config.php:402` |
| S2 | `compress_output = false` e nenhuma compressao/cache de estaticos no `.htaccess`. | `config/config.php:490`, `.htaccess` |
| S3 | O cache do CodeIgniter nao e usado em nenhum lugar (`cache_path` vazio, nenhum `cache->save`). | `config/config.php:317` |
| S4 | O OPcache esta instalado no Docker, mas nao configurado no `php.ini` (sem `validate_timestamps`, `memory_consumption`, etc.). | `docker/etc/php/php.ini` |

### Frontend

| # | Achado | Onde |
|---|--------|------|
| F1 | **Os 6 CSS de tema** sao carregados em todas as paginas, embora so 1 seja usado. | `views/tema/topo.php` |
| F2 | 24 tags `<link>`/`<script>` bloqueantes no `<head>`, incluindo fontes do Google e Boxicons via `unpkg.com` (dependencia externa: se o CDN cair, o layout quebra). | `views/tema/topo.php` |
| F3 | Pacote de 5,7 MB de JS. jQuery **1.12.4** (sem suporte e com CVEs conhecidas), e 14 views que **recarregam o jQuery** (1.12.4 e ate 1.10.2) por cima do que ja foi carregado. | `assets/js`, `views/**` |
| F4 | Bootstrap 2 (`bootstrap-responsive.css`), que tem responsividade limitada no celular. | `views/tema/topo.php` |
| F5 | Ha 126 views com `<script>` inline. Isso dificulta cache, reaproveitamento e CSP. | `views/**` |
| F6 | Apenas 1 tela usa DataTables `serverSide`. As demais carregam tudo e paginam no navegador. | `views/**` |

### Codigo

| # | Achado | Onde |
|---|--------|------|
| C1 | Controllers muito grandes: `Os.php` (1990 linhas), `Rh.php` (1345), `Mine.php` (1260), `Tecnico.php`, `Checkin.php`... Sao dificeis de manter e testar. | `controllers/` |
| C2 | 33 consultas `SELECT *` trazem colunas `TEXT` grandes (`laudoTecnico`, `observacoes`) para listagens que nao as exibem. | `models/`, `controllers/` |

## Decision

Implementar em **3 fases**, da menor para a maior relacao esforco/risco.
Cada item aprovado vira um ADR proprio (ADR-003, ADR-004...), registrado
com `ruflo-adr:adr-create` e conferido com `ruflo-adr:adr-review` antes do merge.

### Fase 1 - Ganhos rapidos (baixo risco, ~1 a 2 dias)

1. **Migration de indices** (B1). Uma unica migration nova:
   ```sql
   ALTER TABLE os          ADD INDEX idx_os_status (status);
   ALTER TABLE vendas      ADD INDEX idx_vendas_status (status);
   ALTER TABLE lancamentos ADD INDEX idx_lanc_pagto (data_pagamento, tipo, baixado),
                           ADD INDEX idx_lanc_venc (data_vencimento, baixado);
   ALTER TABLE produtos    ADD INDEX idx_prod_codbarra (codDeBarra);
   ALTER TABLE email_queue ADD INDEX idx_email_status (status);
   ALTER TABLE anotacoes_os ADD INDEX idx_anot_os (os_id);
   ```
2. **Remover a query duplicada** do dashboard (B2) e trocar `EXTRACT(YEAR…)`
   por `BETWEEN` (B3).
3. **`save_queries` so fora de producao** (B6):
   `'save_queries' => (ENVIRONMENT !== 'production')`.
4. **Carregar so o CSS do tema ativo** (F1), com um `if` no `topo.php` usando a
   configuracao de tema que ja existe.
5. **Remover os `<script src=jquery…>` duplicados** das 14 views (F3).
6. **Compressao e cache de estaticos no `.htaccess`** (S2), com `mod_deflate` e
   `mod_expires` (CSS/JS/imagens por 30 dias, usando o `?v=filemtime` que ja
   e usado no `rh.css` para invalidar o cache).
7. **OPcache configurado** no `php.ini` do Docker (S4).

### Fase 2 - Estrutura (risco medio, ~1 a 2 semanas)

8. **Dashboard em uma consulta + cache de 5 min** (B4, S3). Agrupar as 6
   listas de OS em uma consulta e guardar estatisticas financeiras no cache do
   CodeIgniter (driver `file` ou `redis`), limpando o cache ao salvar
   lancamentos.
9. **Sessao em Redis** no Docker e em `files` nas hospedagens (S1). E so
   configuracao via `.env` (`APP_SESS_DRIVER`), ja suportado no `config.php`.
10. **Busca global com indice FULLTEXT** em `clientes(nomeCliente)`,
    `produtos(descricao)` e `servicos(nome)`, usando `MATCH … AGAINST` com
    `debounce` de 300 ms no campo de busca (B5).
11. **Listagens grandes com DataTables `serverSide`** (OS, clientes,
    lancamentos, produtos) (F6).
12. **Fontes e Boxicons servidos localmente** em `assets/` (F2), sem depender
    do unpkg.
13. **Trocar `SELECT *` por colunas explicitas** nas listagens (C2).

### Fase 3 - Usabilidade e modernizacao (maior esforco, continuo)

14. **Atualizar jQuery 1.12 → 3.7** com `jquery-migrate` durante a transicao (F3).
15. **Paleta de comandos (Ctrl+K)**: busca unica para ir a qualquer tela,
    cliente ou OS. O `shortcut.js` ja esta carregado.
16. **Acoes rapidas na listagem de OS**: mudar status, imprimir e enviar por
    WhatsApp sem abrir a OS.
17. **Formularios com salvamento automatico de rascunho** (OS e check-in), para
    nao perder dados se a conexao do tecnico cair.
18. **Layout mobile-first para as telas do tecnico** (`menu_tecnico`,
    check-in), migrando gradualmente do Bootstrap 2 (F4).
19. **Mover scripts inline para arquivos `.js`** por modulo (F5).
20. **Quebrar controllers grandes** em services/libraries (C1), comecando por
    `Os.php`.

## Consequences

### Positive
- Fase 1 sozinha ja reduz consultas lentas (indices) e o peso de cada pagina
  (5 CSS e jQuery duplicado a menos), sem mudar nenhuma tela.
- Dashboard e listagens deixam de ficar mais lentos conforme a base cresce.
- Menos dependencia de CDNs externos; o sistema funciona mesmo se o unpkg cair.
- Tecnicos em campo ganham telas mais leves e rascunho automatico.

### Negative
- Criar indices em tabelas grandes trava escrita por alguns segundos. Rodar a
  migration fora do horario de uso.
- A troca do jQuery (item 14) pode quebrar plugins antigos (`jquery.uniform`,
  `jquery.wizard`, `bootstrap-wysihtml5`). Por isso fica na Fase 3, com
  `jquery-migrate` e testes tela a tela.
- O cache do dashboard (item 8) pode mostrar numeros com ate 5 min de atraso,
  caso a invalidacao falhe.

### Neutral
- Nenhum ganho foi medido ainda. Antes da Fase 1, registrar uma linha de base
  com o Profiler do CodeIgniter (`$this->output->enable_profiler(TRUE)`) no
  dashboard e na listagem de OS, para comparar depois.

## Implementacao (2026-10-23, branch `melhorias-performance-23-10`)

Criterio: so entrou o que da para garantir que nao quebra sem rodar o
sistema (nao havia banco nem servidor disponiveis; validacao por `php -l`,
`node --check` e revisao do codigo).

### Correcoes da analise inicial
- **F1 estava errado**: o `topo.php` ja carrega so o CSS do tema ativo.
- **F3 (jQuery duplicado) estava errado**: as 13 views que incluem jQuery sao
  paginas independentes (login, portal do cliente, area do tecnico/colaborador)
  que nao usam o `topo.php`. Remover quebraria essas paginas.
- **F6 estava errado**: as listagens ja paginam no servidor (paginacao do
  CodeIgniter); o DataTables so atua sobre a pagina atual.
- **S2 no Docker**: o Docker usa nginx, entao a compressao foi para o nginx.

### Feito
| Item | Mudanca |
|------|---------|
| 1 (B1) | Migration `20261023000000_add_indices_performance` (8 indices, idempotente, com `down()`). Rodar em Configuracoes > Atualizar banco. |
| 2 (B2, B3) | `Mapos_model`: fim da query executada 2x (4 lugares) e filtro de ano por `BETWEEN` (mesmo resultado, usa indice). |
| 8 (B4, parcial) | Dashboard: removidas 3 consultas cujo resultado nenhuma view usava (`ordens_aguardando`, `ordens_andamento`, `os`). |
| 3 (B6) | `save_queries` desligado em producao. |
| 6 (S2) | `.htaccess`: gzip (`mod_deflate`) e cache de imagens/fontes (`mod_expires`), ambos em `IfModule`. nginx: gzip. CSS/JS sem cache longo de proposito. |
| 7 (S4) | OPcache configurado no `php.ini` do Docker (`validate_timestamps` ligado). |
| 9 (S1, doc) | `.env.example` documenta sessao em `files`/`redis`. Padrao continua `database`. |
| 12 (F2) | Boxicons 2.1.1 servido de `assets/boxicons/` nas 10 views (antes unpkg.com). |
| 15 | Paleta de comandos Ctrl+K (`assets/js/paleta-comandos.js`), usa os links do menu ja filtrados por permissao; Enter sem resultado cai na pesquisa global. |

### Nao feito (risco de quebrar sem teste em execucao)
| Item | Motivo |
|------|--------|
| 8 (cache do financeiro) | 13 pontos em 6 arquivos gravam em `lancamentos`; cache sem invalidacao completa mostraria valores desatualizados. |
| 10 (FULLTEXT) | Muda o comportamento da busca (deixa de achar trechos no meio da palavra e numeros curtos). |
| 13 (`SELECT *`) | Views podem usar qualquer coluna; exige testar tela a tela. |
| 14 (jQuery 3) | Plugins antigos (`uniform`, `wizard`, `wysihtml5`) podem quebrar. |
| 16 (acoes rapidas OS) | Mudar status fora do `Os::editar` pularia gatilhos/notificacoes. |
| 17 (rascunho automatico) | Restaurar formularios com linhas dinamicas (produtos/servicos) exige teste manual. |
| 18, 19, 20 | Migracao do Bootstrap 2, mover 126 scripts inline e quebrar controllers sao refatoracoes grandes; fazer por modulo, com testes. |
| Fontes Google | Ja tem fallback para fontes do sistema; nao quebram o layout se o CDN cair. |

## Links
- ADR-001: Mitigacao PHP2Shell (mesma infraestrutura Docker/`php.ini`)
- ADR-003: amends ADR-002 (implementa os itens 8, 10, 14, 16, 17, 18 e 20 que ficaram de fora aqui)
