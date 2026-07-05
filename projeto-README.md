# Sistema de Check-in/Check-out para MAP-OS

## Visão Geral

Este projeto adiciona um sistema completo de **registro de atendimento** às Ordens de Serviço (OS) do MAP-OS, permitindo:

- ✅ Check-in do técnico na chegada (com assinatura e fotos)
- ✅ Registro de fotos durante o atendimento
- ✅ Check-out na saída (com assinatura do técnico e cliente)
- ✅ Relatório impresso do atendimento completo
- ✅ Dashboard com estatísticas e gráficos

---

## O que Já Está Pronto (Instalação Automática)

O sistema já possui **tudo preparado** para funcionar automaticamente:

### Banco de Dados
A migration `application/database/migrations/20250403000001_add_checkin_tables.php` será executada automaticamente quando você:
- Acessar o sistema como administrador e clicar em "Atualizar Banco de Dados" (Configurações > Sistema)
- Ou executar: `php index.php tools migrate`

### Backend (PHP/CodeIgniter)
- ✅ `Checkin_model.php` - Gerencia checkins
- ✅ `Assinaturas_model.php` - Gerencia assinaturas digitais
- ✅ `Fotosatendimento_model.php` - Gerencia fotos
- ✅ `Checkin.php` (Controller) - Endpoints AJAX
- ✅ Componente `assinatura_canvas.php`

### O que Falta (Interface)
- ⏳ JavaScript para integração (`assets/js/checkin.js`)
- ⏳ Botões e modais na visualização da OS
- ⏳ Galeria de fotos
- ⏳ Dashboard de relatórios
- ⏳ Impressão do relatório
- ⏳ Menu de navegação

---

## Como Instalar

### Passo 1: Executar Migration (Instalação das Tabelas)

**Opção A - Pelo Sistema:**
1. Faça login como administrador
2. Vá em **Configurações > Sistema**
3. Clique no botão **"Atualizar Banco de Dados"**

**Opção B - Pelo Terminal:**
```bash
cd /caminho/do/mapos
php index.php tools migrate
```

### Passo 2: Executar as Etapas de Desenvolvimento

Siga o cronograma em `projeto-Cronograma.md` ou use os comandos em `projeto-Instrucoes.md`.

Cada etapa é independente e pode ser executada separadamente.

---

## Funcionalidades

### 1. Check-in de Atendimento
- Registra entrada do técnico na OS
- Captura assinatura digital do técnico
- Permite adicionar fotos de entrada
- Registra observações iniciais
- Captura geolocalização (GPS)

### 2. Durante o Atendimento
- Adicionar fotos ilimitadas
- Descrever cada foto
- Visualizar galeria organizada por etapa
- Download individual das fotos

### 3. Check-out de Atendimento
- Registra saída do técnico
- Captura assinatura do técnico na saída
- Captura assinatura do cliente
- Registra nome e documento do cliente
- Fotos de saída
- Observações finais

### 4. Relatório Impresso
- Relatório completo do atendimento
- Todas as assinaturas
- Todas as fotos organizadas
- Tempo total de atendimento
- Observações de entrada e saída

### 5. Dashboard
- Estatísticas de atendimentos
- Gráficos por período
- Ranking de técnicos
- Tempo médio de atendimento
- Taxa de conclusão

---

## Estrutura de Arquivos

```
mapos/
├── application/
│   ├── controllers/
│   │   ├── Checkin.php (✅ existente)
│   │   └── Relatorioatendimentos.php (⏳ criar)
│   ├── models/
│   │   ├── Checkin_model.php (✅ existente)
│   │   ├── Assinaturas_model.php (✅ existente)
│   │   ├── Fotosatendimento_model.php (✅ existente)
│   │   └── Relatorioatendimentos_model.php (⏳ criar)
│   ├── views/
│   │   ├── checkin/
│   │   │   ├── assinatura_canvas.php (✅ existente)
│   │   │   └── imprimirCheckin.php (⏳ criar)
│   │   ├── relatorioatendimentos/
│   │   │   └── relatorio.php (⏳ criar)
│   │   └── os/
│   │       └── visualizarOs.php (⏳ modificar)
│   └── database/
│       └── migrations/
│           └── 20250403000001_add_checkin_tables.php (✅ existente)
├── assets/
│   └── js/
│       └── checkin.js (⏳ criar)
└── projeto-*.md (documentação)
```

---

## Documentação do Projeto

| Arquivo | Descrição |
|---------|-----------|
| `projeto-Cronograma.md` | Cronograma completo com todas as etapas detalhadas |
| `projeto-Progresso.md` | Acompanhamento do progresso atual |
| `projeto-Instrucoes.md` | Comandos rápidos para cada etapa |
| `projeto-README.md` | Este arquivo - visão geral |

---

## Como Começar

### Se você quer desenvolver tudo de uma vez:
1. Leia `projeto-Cronograma.md`
2. Execute as etapas 1 a 10 em sequência
3. Teste o sistema completo

### Se você quer desenvolver por partes:
1. Leia `projeto-Instrucoes.md`
2. Execute uma etapa por vez
3. Teste antes de prosseguir
4. Atualize `projeto-Progresso.md`

### Se você quer continuar de onde parou:
1. Abra `projeto-Progresso.md`
2. Veja a última etapa concluída
3. Execute a próxima etapa do `projeto-Instrucoes.md`

---

## Requisitos Técnicos

- PHP >= 8.3
- MySQL >= 5.7 ou 8.0
- MAP-OS >= 4.52.0
- Navegador com suporte a Canvas (Chrome, Firefox, Safari, Edge)
- Para assinaturas touch: dispositivo com tela touch ou mouse

---

## Suporte

Em caso de dúvidas durante o desenvolvimento, consulte:
1. Os comentários nos arquivos existentes (models e controller)
2. O padrão de código do MAP-OS (CodeIgniter 3)
3. A documentação deste projeto

---

**Criado em:** 2025-04-03  
**Versão do MAP-OS:** 4.52.0  
**Status:** Pronto para desenvolvimento
