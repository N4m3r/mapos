# Projeto Dashboard MAP-OS
## Sistema de Relatórios e Métricas Visuais

**Data Início:** 2026-04-04  
**Status:** Em Planejamento  
**Versão:** 1.0

---

## RESUMO EXECUTIVO

Criar um módulo de Dashboard completo no menu lateral do MAP-OS, consolidando todas as métricas e relatórios disponíveis no sistema em uma interface visual com gráficos e indicadores.

### Objetivos
- Centralizar relatórios em um único local
- Visualização rápida de métricas críticas
- Gráficos interativos
- Filtros dinâmicos por período

---

## ESTRUTURA DO PROJETO (Divisão por Etapas)

### ETAPA 1: Estrutura Base e Menu
**Complexidade:** Baixa  
**Tokens estimados:** ~800

**Tarefas:**
1. Criar controller Dashboard.php
2. Criar view dashboard/index.php
3. Adicionar botão no menu lateral (menu.php)
4. Criar migration para permissão 'vDashboard'
5. Criar arquivo de rotas

**Arquivos:**
- `application/controllers/Dashboard.php`
- `application/views/dashboard/index.php`
- `application/views/dashboard/relatorios/`
- `application/models/Dashboard_model.php`

---

### ETAPA 2: Métricas Principais (KPIs)
**Complexidade:** Média  
**Tokens estimados:** ~1500

**Métricas a implementar:**
1. **Total de Atendimentos**
   - Hoje
   - Semana atual
   - Mês atual
   - Ano atual

2. **OS Pendentes**
   - Abertas
   - Em orçamento
   - Aguardando peças
   - Total geral

3. **OS Finalizadas**
   - Por período (dia/semana/mês/ano)
   - Taxa de conclusão

4. **Valores Acumulados**
   - Total em serviços
   - Total em produtos
   - Ticket médio
   - Projeção mensal/anual

**Queries necessárias:**
- COUNT de OS por status
- SUM de valores (produtos + serviços)
- AVG para ticket médio
- GROUP BY para agrupamentos

---

### ETAPA 3: Gráficos Visuais
**Complexidade:** Média  
**Tokens estimados:** ~1200

**Biblioteca recomendada:** Chart.js (já incluída no MAP-OS)

**Gráficos a criar:**
1. **Gráfico de OS por Status** (Pizza)
   - Aberto, Orçamento, Em Andamento, Finalizado, Cancelado

2. **Gráfico de OS por Período** (Linha)
   - Últimos 12 meses
   - Comparativo ano anterior

3. **Gráfico de Faturamento** (Barras)
   - Mensal
   - Por técnico
   - Por tipo de serviço

4. **Gráfico de Clientes** (Barras horizontais)
   - Novos clientes
   - Clientes recorrentes

5. **Gráfico de Produtos/Serviços** (Pizza)
   - Mais vendidos
   - Por categoria

---

### ETAPA 4: Relatórios Detalhados
**Complexidade:** Alta  
**Tokens estimados:** ~2000 (dividir em sub-etapas)

**Relatórios por categoria:**

#### 4.1 Relatório de Atendimentos
- Por técnico
- Por cliente
- Por período
- Com tempo médio
- Taxa de reincidência

#### 4.2 Relatório Financeiro
- Entradas e saídas
- Lucratividade por OS
- Inadimplência
- Projeção de receita

#### 4.3 Relatório de Produtos/Serviços
- Mais vendidos
- Estoque crítico
- Serviços mais lucrativos
- Rotatividade

#### 4.4 Relatório de Clientes
- Novos vs Recorrentes
- Ticket médio por cliente
- Satisfação (se houver)
- Tempo médio de resposta

---

### ETAPA 5: Filtros e Exportação
**Complexidade:** Média  
**Tokens estimados:** ~1000

**Funcionalidades:**
1. **Filtros Dinâmicos**
   - Date range picker
   - Seleção de técnico
   - Seleção de cliente
   - Tipo de OS
   - Status

2. **Exportação**
   - PDF (relatório formatado)
   - Excel/CSV (dados brutos)
   - Impressão otimizada

3. **Atualização em Tempo Real**
   - Auto-refresh opcional
   - Botão atualizar manual

---

## MODELO DE DADOS (Queries Principais)

### KPI - Total de OS
```sql
SELECT 
    COUNT(*) as total,
    status,
    DATE(dataInicial) as data
FROM os
WHERE dataInicial >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY status, DATE(dataInicial)
```

### KPI - Faturamento
```sql
SELECT 
    SUM(valorTotal) as total,
    SUM(desconto) as descontos,
    DATE(dataInicial) as data
FROM os
WHERE status IN ('Finalizado', 'Faturado')
AND dataInicial >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(dataInicial)
```

### KPI - Por Técnico
```sql
SELECT 
    u.nome as tecnico,
    COUNT(os.idOs) as total_os,
    SUM(os.valorTotal) as valor_total,
    AVG(os.valorTotal) as ticket_medio
FROM os
JOIN usuarios u ON os.tecnico_responsavel = u.idUsuarios
WHERE os.dataInicial >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY u.idUsuarios, u.nome
```

---

## INTERFACE VISUAL (Wireframe)

### Layout Proposto
```
+----------------------------------------------------------+
|  DASHBOARD - Visão Geral                    [Filtros] ⚙️  |
+----------------------------------------------------------+
|  [KPI 1]    [KPI 2]    [KPI 3]    [KPI 4]               |
|  Total OS   Pendentes  Faturado   Projeção              |
+----------------------------------------------------------+
|  +------------------+  +------------------+              |
|  | Gráfico Pizza    |  | Gráfico Linha   |              |
|  | OS por Status    |  | OS por Mês      |              |
|  +------------------+  +------------------+              |
|  +------------------+  +------------------+              |
|  | Gráfico Barras   |  | Gráfico Barras  |              |
|  | Faturamento      |  | Por Técnico     |              |
|  +------------------+  +------------------+              |
+----------------------------------------------------------+
|  [Tabela: Top 10 Clientes]  [Tabela: OS Recentes]        |
+----------------------------------------------------------+
```

---

## PERMISSÕES NECESSÁRIAS

Adicionar ao grupo de permissões:
- `vDashboard` - Visualizar dashboard
- `vRelatorioCompleto` - Acesso a todos os relatórios
- `vRelatorioFinanceiro` - Apenas relatórios financeiros (opcional)
- `vExportarDados` - Exportar relatórios

---

## DEPENDÊNCIAS

### Bibliotecas já existentes no MAP-OS:
- Chart.js (gráficos)
- jQuery (manipulação DOM)
- Bootstrap (layout)
- DataTables (tabelas)

### Possíveis adições:
- date-range-picker (filtros de data)
- html2canvas (exportação para imagem)
- jsPDF (exportação PDF)

---

## CRONOGRAMA SUGERIDO

| Etapa | Descrição | Tempo Est. | Prioridade |
|-------|-----------|------------|------------|
| 1 | Estrutura Base | 2h | Alta |
| 2 | Métricas Principais | 4h | Alta |
| 3 | Gráficos Visuais | 4h | Alta |
| 4.1 | Relatório Atendimentos | 3h | Média |
| 4.2 | Relatório Financeiro | 3h | Média |
| 4.3 | Relatório Produtos | 2h | Baixa |
| 4.4 | Relatório Clientes | 2h | Média |
| 5 | Filtros e Exportação | 4h | Média |

**Total estimado:** 24 horas de desenvolvimento

---

## PRÓXIMA ETAPA A EXECUTAR

### ETAPA 1: Estrutura Base e Menu

**Próximo passo:**
1. Criar migration `add_permissao_dashboard.php`
2. Criar controller `application/controllers/Dashboard.php`
3. Adicionar link no menu lateral
4. Criar view inicial do dashboard

**Comando para criar migration:**
```bash
php index.php tools migrate
```

**Arquivos a modificar:**
- `application/views/tema/menu.php`
- `application/database/migrations/`

---

## NOTAS IMPORTANTES

1. **Cache de Consultas:** Considerar cache de 5 minutos para queries pesadas
2. **Performance:** Usar índices nas colunas de data e status
3. **Mobile:** Layout responsivo para tablets (técnicos em campo)
4. **Segurança:** Verificar permissões em todas as funções

---

**Documento criado em:** 2026-04-04  
**Última atualização:** 2026-04-04  
**Próxima revisão:** Após Etapa 1

---

## CHECKLIST DE IMPLEMENTAÇÃO

### Fase 1 - Fundação
- [ ] Migration de permissões criada
- [ ] Controller Dashboard criado
- [ ] Menu lateral atualizado
- [ ] View base do dashboard criada
- [ ] Teste de acesso funcionando

### Fase 2 - Dados
- [ ] Model Dashboard_model criado
- [ ] Queries de KPIs testadas
- [ ] Dados aparecendo na view
- [ ] Filtros de data funcionando

### Fase 3 - Visualização
- [ ] Chart.js integrado
- [ ] Gráficos renderizando
- [ ] Cores e tema definidos
- [ ] Layout responsivo

### Fase 4 - Relatórios
- [ ] Relatório de atendimentos
- [ ] Relatório financeiro
- [ ] Relatório de produtos
- [ ] Relatório de clientes

### Fase 5 - Finalização
- [ ] Exportação PDF/Excel
- [ ] Testes completos
- [ ] Documentação atualizada
- [ ] Deploy para produção

---

**FIM DO DOCUMENTO - Iniciar Etapa 1 quando solicitado**
