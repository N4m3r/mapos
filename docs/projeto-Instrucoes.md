# Instruções de Execução - Sistema de Check-in/Check-out

## Como Usar Este Projeto

### 1. Leia o Cronograma Completo
Abra o arquivo `projeto-Cronograma.md` para entender todo o escopo do projeto.

### 2. Acompanhe o Progresso
Abra o arquivo `projeto-Progresso.md` para ver qual etapa está em andamento.

### 3. Execute por Etapas
Cada etapa foi dividida para caber nos limites de tokens da versão gratuita.

---

## COMANDOS POR ETAPA

### ETAPA 1: JavaScript CheckinManager
```
/Crie o arquivo assets/js/checkin.js com o CheckinManager completo para integração com o controller Checkin.php existente. O CheckinManager deve ter métodos para: iniciarAtendimento(), finalizarAtendimento(), carregarStatus(), adicionarFoto(), removerFoto(). Deve integrar com AssinaturaManager já existente e fazer chamadas AJAX para os endpoints do controller Checkin.php
```

### ETAPA 2: Botões na Visualização OS
```
/Adicione os botões de checkin/checkout na visualização da OS em application/views/os/visualizarOs.php. Os botões devem aparecer na área de botões superiores (próximo aos botões de Imprimir, WhatsApp, etc). Use condições para mostrar "Iniciar Atendimento" quando não houver checkin ativo, e "Finalizar Atendimento" quando houver. Integre com o arquivo checkin.js criado na etapa anterior
```

### ETAPA 3: Modais de Checkin/Checkout
```
/Adicione os modais de checkin e checkout na visualizarOs.php com: 1) Modal de Checkin: canvas de assinatura do técnico, upload de fotos de entrada, campo de observação, botão de geolocalização. 2) Modal de Checkout: canvas de assinatura do técnico, canvas de assinatura do cliente, campos nome_cliente e documento_cliente, upload de fotos de saída, observação de saída. Use o componente de assinatura existente em application/views/checkin/assinatura_canvas.php
```

### ETAPA 4: Galeria de Fotos
```
/Crie a seção de galeria de fotos do atendimento na visualizarOs.php com organização por etapas (entrada, durante, saída). Deve mostrar miniaturas das fotos com descrição, botão para visualizar em tamanho maior, botão para download e botão para remover (com permissão). Mostrar apenas quando existir checkin para a OS
```

### ETAPA 5: Controller Dashboard
```
/Crie o controller application/controllers/Relatorioatendimentos.php com métodos: index() para dashboard, listar() para DataTable JSON, estatisticas() para dados dos gráficos JSON, exportar() para Excel. Crie também o model application/models/Relatorioatendimentos_model.php com métodos: getAtendimentosComFiltros(), getEstatisticasMensais(), getRankingTecnicos(), getTempoMedioAtendimento(). Use o Checkin_model existente como referência
```

### ETAPA 6: View Dashboard (Estrutura)
```
/Crie a view application/views/relatorioatendimentos/relatorio.php com: título "Relatório de Atendimentos", filtros de período (date range), filtro de técnico (dropdown), cards de estatísticas (total atendimentos, tempo médio, em andamento, finalizados), container para gráficos, DataTable para listagem dos atendimentos. Use o tema Matrix Admin existente do MAP-OS
```

### ETAPA 7: Gráficos do Dashboard
```
/Adicione os gráficos Chart.js no dashboard de atendimentos com: 1) Gráfico de linha - atendimentos por dia no período, 2) Gráfico de barras - atendimentos por técnico, 3) Gráfico de pizza - distribuição por status. Os dados devem vir via AJAX do método estatisticas() do controller. Use as cores do tema MAP-OS
```

### ETAPA 8: Impressão do Relatório
```
/Crie a view application/views/checkin/imprimirCheckin.php para impressão do relatório de atendimento. Deve conter: cabeçalho com dados da OS e cliente, dados do checkin (data/hora entrada, data/hora saída, tempo total), seção de assinaturas com as imagens, seção de fotos organizadas por etapa (entrada/durante/saída), observações de entrada e saída, geolocalização. Estilo clean para impressão A4. Adicione método imprimir($os_id) no controller Checkin.php
```

### ETAPA 9: Menu e Permissões
```
/Adicione o menu "Atendimentos" na seção de Relatórios em application/views/tema/menu.php. Configure as permissões necessárias: vRelatorioAtendimentos para visualizar. Use os ícones Boxicons (bx-time ou similar) conforme já utilizado no sistema. O menu deve respeitar as permissões do usuário logado
```

### ETAPA 10: Ajustes Finais
```
/Faça ajustes finais no sistema de checkin: 1) Validações de campos obrigatórios nos modais, 2) Responsividade mobile para os modais e assinaturas touch, 3) Tratamento de erros com mensagens amigáveis, 4) Loading/spinner durante requisições AJAX, 5) Confirmação antes de iniciar/finalizar atendimento. Teste e ajuste a integração completa
```

---

## COMANDOS ÚTEIS

### Verificar Status do Projeto
```
/Qual o progresso atual do projeto de checkin? Leia o arquivo projeto-Progresso.md e projeto-Cronograma.md
```

### Verificar Tabelas do Banco
```
Mostre-me a estrutura das tabelas os_checkin, os_assinaturas e os_fotos_atendimento. Verifique se a migration foi executada.
```

### Testar Funcionalidade
```
Verifique se o arquivo checkin.js está carregando corretamente e se o CheckinManager está disponível no console do navegador.
```

---

## FLUXO DE TRABALHO RECOMENDADO

1. **Inicie pela Etapa 1** - Copie o comando da Etapa 1 acima
2. **Teste antes de prosseguir** - Verifique se funcionou
3. **Atualize o progresso** - Marque a etapa como concluída em projeto-Progresso.md
4. **Execute a próxima etapa** - Copie o comando da próxima etapa
5. **Repita** até completar todas as etapas

---

## COMO RETOMAR DEPOIS DE PARAR

Se você parar e quiser continuar depois:

1. Abra o arquivo `projeto-Progresso.md`
2. Veja qual foi a última etapa marcada como concluída
3. Execute o comando da próxima etapa deste arquivo
4. Se não tiver certeza, pergunte: "Qual a próxima etapa do projeto de checkin?"

---

## ARQUIVOS IMPORTANTES

- `projeto-Cronograma.md` - Visão completa do projeto
- `projeto-Progresso.md` - Status atual das etapas
- `projeto-Instrucoes.md` - Este arquivo (comandos rápidos)

---

**Data de criação:** 2025-04-03
