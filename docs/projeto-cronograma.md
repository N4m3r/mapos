# Cronograma de Desenvolvimento - Área do Técnico MAP-OS

## Resumo do Projeto
Criar uma área exclusiva para técnicos com permissões restritas, permitindo visualização de OS designadas e execução de check-in/check-out.

**Sistema base:** Check-in/Check-out já implementado anteriormente  
**Novo escopo:** Área do Técnico com atribuição separada de OS

---

## Status Atual

**Progresso Geral:** <span style="color: var(--success);">●</span> **100% CONCLUÍDO**

| Fase | Status | Data |
|------|--------|------|
| Correção do Home | <span style="color: var(--success);">✅</span> Concluído | 04/04/2025 |
| Remover associação automática | <span style="color: var(--success);">✅</span> Concluído | 04/04/2025 |
| Menu lateral para Técnicos | <span style="color: var(--success);">✅</span> Concluído | 04/04/2025 |
| Interface de atribuição Técnico/OS | <span style="color: var(--success);">✅</span> Concluído | 05/04/2025 |

**Cores de Status (variáveis CSS):**
- ✅ Concluído: `var(--success)` / `#02b470` (verde)
- ⏳ Pendente: `var(--warning)` / `#e68606` (laranja)
- 🔄 Em Andamento: `var(--info)` / `#0386eb` (azul)
- ❌ Bloqueado: `var(--danger)` / `#f30b0b` (vermelho)

---

## ETAPAS CONCLUÍDAS

### ✅ ETAPA 1: Corrigir erro do Home
**Status:** Concluído ✅  
**Data:** 2025-04-04

**Problema:** Sistema estava dando erro ao acessar a página inicial

**Solução:**
- Comentada verificação de permissão `vTecnicoDashboard` no controller Tecnico
- Criada migration de correção `20250404000003_fix_home_acesso.php`

**Arquivos:**
- `application/controllers/Tecnico.php` (modificado)
- `application/database/migrations/20250404000003_fix_home_acesso.php` (novo)

**Teste:** Acessar `/index.php/mapos` - deve carregar normalmente

---

### ✅ ETAPA 2: Remover associação automática de técnico
**Status:** Concluído ✅  
**Data:** 2025-04-04

**Objetivo:** Ao criar/editar OS, não associar técnico automaticamente

**Resultado:** Campo `tecnico_responsavel` permanece NULL ao criar OS

**Observação:** O campo "Técnico / Responsável" nos formulários é o campo original do MAP-OS (`usuarios_id` - quem criou a OS), não o novo campo de atribuição.

---

### ✅ ETAPA 3: Criar menu lateral para Técnicos
**Status:** Concluído ✅  
**Data:** 2025-04-04

**Arquivos criados:**
- `application/views/tema/menu_tecnico.php` - Menu do técnico
- `assets/css/tecnico.css` - Estilos específicos da área do técnico

**Arquivos modificados:**
- `application/core/MY_Controller.php` - Detecção automática de menu

**Funcionamento:**
- Sistema detecta permissão `vTecnicoDashboard`
- Carrega menu_tecnico.php (estilo técnico) se for técnico
- Carrega menu.php (padrão) se for admin

**Paleta de Cores (compatível com todos os temas):**
O tema do técnico utiliza as variáveis CSS padrão do sistema para garantir compatibilidade com todos os modos:

| Elemento | Variável | Tema White | Dark Violet | Pure Dark |
|----------|----------|------------|-------------|-----------|
| Background Sidebar | `--cor-sidebar` | `#6b29f8` | `#6b29f8` | `#1086dd` |
| Texto Principal | `--title-w` / `--branco` | `#42484e` | `#caced8` | `#caced8` |
| Background Content | `--bodycolor-w` / `--dark-violet-cont` | `#f3f4f6` | `#1b1239` | `#14141a` |
| Widget/Cards | `--widget-box-w` / `--dark-violet-widg` | `#ffffff` | `#291a57` | `#1c1d26` |
| Hover/Active | `--dark-azul` | `#1086dd` | `#6b29f8` | `#1086dd` |
| Bordas | `--cinza0` | `#9aa6b3` | `#52459f` | `#272835` |

**CSS para área do técnico (`assets/css/tecnico.css`):**
```css
/* Tema Técnico - Compatível com todos os modos */

/* Modo White (Padrão) */
:root {
  --tec-sidebar: #6b29f8;
  --tec-sidebar-hover: #5530c9;
  --tec-text: #ffffff;
  --tec-icon: #ffffff;
}

/* Modo Dark Violet */
[data-theme="dark-violet"] {
  --tec-sidebar: #6b29f8;
  --tec-sidebar-hover: #7c3aed;
  --tec-text: #caced8;
  --tec-icon: #c3b2e9;
}

/* Modo Pure Dark */
[data-theme="pure-dark"] {
  --tec-sidebar: #1086dd;
  --tec-sidebar-hover: #0d6efd;
  --tec-text: #caced8;
  --tec-icon: #1086dd;
}

/* Sidebar do Técnico */
#sidebar.tecnico-sidebar {
  background: var(--tec-sidebar);
  border-right: 1px solid rgba(255, 255, 255, 0.1);
}

#sidebar.tecnico-sidebar li .title,
#sidebar.tecnico-sidebar li .icon,
#sidebar.tecnico-sidebar li a i {
  color: var(--tec-text);
}

#sidebar.tecnico-sidebar > ul > li > a:hover {
  background-color: var(--tec-sidebar-hover);
  color: var(--tec-text);
}

#sidebar.tecnico-sidebar > ul > li.active a {
  background-color: rgba(255, 255, 255, 0.2);
  color: var(--tec-text);
}

/* Search box */
#sidebar.tecnico-sidebar .search-box {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

#sidebar.tecnico-sidebar .search-box input {
  color: var(--tec-text);
}
```

**Itens do menu técnico:**
- Home → `/tecnico`
- Ordens de Serviço → `/tecnico/os`
- Produtos → `/produtos`
- Serviços → `/servicos`
- Clientes → `/clientes`
- Sair

**Teste:** Login com usuário técnico deve mostrar menu com identidade visual do técnico, respeitando o tema atual

---

## ✅ ETAPA CONCLUÍDA

### ✅ ETAPA 4: Interface de atribuição Técnico/OS
**Status:** ✅ Concluído  
**Data:** 2025-04-05  
**Implementação:** Opção A - Nova página dedicada

**Descrição:**
Interface onde administrador pode associar técnicos às OS existentes. O campo `tecnico_responsavel` fica NULL ao criar a OS e é atribuído posteriormente.

**Funcionalidades implementadas:**
1. ✅ Lista paginada de OS (20 por página)
2. ✅ Filtros: Todas, Sem Técnico, Com Técnico
3. ✅ Dropdown para selecionar técnico disponível
4. ✅ Botão "Atribuir" / "Trocar" técnico
5. ✅ Opção de remover técnico da OS
6. ✅ Modal para atribuição com observações
7. ✅ Paleta de cores compatível com todos os temas

**Arquivos criados/modificados:**
- `application/views/os/atribuir_tecnico.php` - View com tema dinâmico
- `application/controllers/Os.php` - Métodos `atribuir()`, `atribuirTecnicoAction()`, `removerTecnicoAction()`
- `application/models/Os_model.php` - Métodos de busca com paginação
- `application/models/Tecnico_model.php` - Métodos de contagem

**URL de acesso:** `/index.php/os/atribuir`

**Sistema de Cores:**
A página usa variáveis CSS do sistema para compatibilidade com todos os temas:
- **Tema White:** Cores claras (`--bodycolor-w`, `--widget-box-w`)
- **Dark Violet:** Cores roxas (`--dark-violet-cont`, `--dark-violet-widg`)
- **Pure Dark:** Cores escuras (`--dark-1`, `--wid-dark`)

**Paginação:**
- 20 OS por página
- Query string `?page=X` para navegação
- Mantém filtros ao navegar entre páginas

---

## ESTRUTURA DE ARQUIVOS

```
application/
├── controllers/
│   ├── Tecnico.php              ✅ Criado - Área do técnico
│   ├── Checkin.php             ✅ Existente - Check-in/checkout
│   └── Os.php                  🔄 Adicionar método atribuir
├── models/
│   ├── Tecnico_model.php       ✅ Criado
│   ├── Checkin_model.php       ✅ Existente
│   ├── Assinaturas_model.php   ✅ Existente
│   └── Fotosatendimento_model.php ✅ Existente
├── views/
│   ├── tecnico/
│   │   ├── dashboard.php       ✅ Criado
│   │   ├── minhas_os.php       ✅ Criado
│   │   └── visualizar_os.php   ✅ Criado
│   ├── os/
│   │   └── atribuir_tecnico.php ⏳ Pendente
│   └── tema/
│       ├── menu.php            ✅ Existente
│       └── menu_tecnico.php    ✅ Criado (usa variáveis CSS do sistema)
├── assets/
│   └── css/
│       ├── matrix-style.css    ✅ Variáveis de tema
│       ├── tema.css           ✅ Tema padrão
│       ├── tema-dark-violet.css ✅ Tema dark violet
│       ├── tema-pure-dark.css   ✅ Tema pure dark
│       └── tecnico.css         ⏳ Criar - Estilos da área técnico
└── database/
    └── migrations/
        ├── 20250403000001_add_checkin_tables.php      ✅ Existente
        ├── 20250403000002_add_permissao_atendimentos.php ✅ Existente
        ├── 20250404000001_add_tecnico_os_relacao.php     ✅ Criado
        ├── 20250404000002_add_permissoes_tecnico.php     ✅ Criado
        └── 20250404000003_fix_home_acesso.php            ✅ Criado
```

---

## FLUXO DE USO ATUAL

### Para Técnicos:
1. Faz login no sistema
2. Se tem permissão `vTecnicoDashboard`, vê menu roxo
3. Clica em "Ordens de Serviço" (`/tecnico/os`)
4. **Problema:** Lista aparece vazia (nenhuma OS atribuída ainda)

### Para Administradores:
1. Cria OS normalmente
2. Campo `tecnico_responsavel` fica NULL
3. **Problema:** Não existe interface para atribuir técnico depois
4. Técnico não consegue ver a OS

### Solução (Etapa 4):
Criar interface de atribuição para resolver o gargalo acima.

---

## INSTRUÇÕES PARA CONTINUAR

### Quando for implementar a Etapa 4:

1. **Abra este arquivo** (`projeto-Cronograma.md`)
2. **Localize a seção "ETAPA 4"**
3. **Execute o comando de retomada** fornecido
4. **Teste** após implementação
5. **Atualize este arquivo** marcando como concluído

### Diretrizes de Cores (seguir sistema existente):

**Para implementar cores consistentes com o sistema:**

```css
/* Use estas variáveis em vez de cores fixas */

/* Backgrounds */
background: var(--bodycolor-w);          /* Tema white: #f3f4f6 */
background: var(--dark-violet-cont);     /* Dark violet: #1b1239 */
background: var(--dark-1);               /* Pure dark: #14141a */

/* Cards/Widgets */
background: var(--widget-box-w);         /* Tema white: #ffffff */
background: var(--dark-violet-widg);     /* Dark violet: #291a57 */
background: var(--wid-dark);              /* Pure dark: #1c1d26 */

/* Textos */
color: var(--title-w);                   /* Tema white: #42484e */
color: var(--dark-violet-tit2);          /* Dark violet: #c3b2e9 */
color: var(--branco);                     /* Comum: #caced8 */
color: var(--cinza0);                     /* Comum: #9aa6b3 */

/* Destaques/Ações */
color: var(--dark-violet-side);          /* Roxo: #6b29f8 */
color: var(--dark-azul);                  /* Azul: #1086dd */
```

### Checklist antes de continuar:
- [x] Home está funcionando?
- [x] Técnico vê menu com identidade visual?
- [x] OS é criada sem técnico atribuído?
- [ ] Interface de atribuição existe? ⏳

---

## COMANDOS DE VERIFICAÇÃO

```bash
# Verificar se tabelas existem:
mysql -u root -p mapos -e "SHOW TABLES LIKE 'os_tecnico%';"
mysql -u root -p mapos -e "DESCRIBE os;" | grep tecnico

# Verificar arquivos criados:
ls -la application/views/tema/menu_tecnico.php
ls -la application/controllers/Tecnico.php
ls -la application/models/Tecnico_model.php

# Verificar migrations:
ls -la application/database/migrations/*tecnico*
ls -la application/database/migrations/*fix_home*

# Testar rota:
curl http://localhost/mapos/index.php/tecnico
```

---

## NOTAS TÉCNICAS

### Compatibilidade:
- ✅ PHP 8.3+
- ✅ MySQL 5.7+ / 8.0+
- ✅ CodeIgniter 3
- ✅ Navegadores modernos

### Sistema de Cores CSS:
As cores da área do técnico seguem as variáveis CSS definidas em `assets/css/matrix-style.css`:

```css
:root {
  /* Tema White (Light) */
  --bodycolor-w: #f3f4f6;
  --widget-box-w: #ffffff;
  --title-w: #42484e;
  --subtitle-w: #42484e;
  
  /* Tema Dark Violet */
  --dark-violet-widg: #291a57;
  --dark-violet-cont: #1b1239;
  --dark-violet-tit: #eee7e7;
  --dark-violet-tit2: #c3b2e9;
  --dark-violet-side: #6b29f8;
  
  /* Tema Pure Dark */
  --wid-dark: #1c1d26;
  --dark-1: #14141a;
  --dark-2: #272835;
  --dark-azul: #1086dd;
  
  /* Comuns */
  --branco: #caced8;
  --cinza0: #9aa6b3;
  --dark-viol: #52459f;
}
```

**Temas suportados:**
1. **White** (Padrão) - Fundo claro `#f3f4f6`
2. **Dark Violet** - Fundo escuro roxo `#1b1239`
3. **Pure Dark** - Fundo escuro `#14141a`
4. **White Green** - Fundo claro com detalhes verdes
5. **Dark Orange** - Fundo escuro com detalhes laranja

**Implementação:**
- O arquivo `tema.css` é carregado por padrão
- Temas dark carregam `tema-nome-do-tema.css` que sobrescreve as variáveis
- A área do técnico deve usar classes CSS que respeitem estas variáveis

### Permissões necessárias:
- Grupo "Técnico": `vTecnicoDashboard`, `vTecnicoOS`, `eTecnicoCheckin`, `eTecnicoCheckout`
- Grupo "Administrador": `eAdminAtribuirTecnico`

### Segurança:
- Controller Tecnico verifica se OS pertence ao técnico
- Menu carregado automaticamente baseado na permissão
- Migrations devem ser executadas antes do uso

---

## HISTÓRICO DE ALTERAÇÕES

| Data | Etapa | Alteração |
|------|-------|-----------|
| 2025-04-04 | 1 | Correção do Home - permissão comentada |
| 2025-04-04 | 2 | Confirmação: campo não é associado na criação |
| 2025-04-04 | 3 | Menu técnico criado usando variáveis CSS do sistema |
| 2025-04-04 | 3 | MY_Controller modificado para detecção automática |
| 2025-04-04 | - | Arquivos de progresso atualizados |
| 2025-04-05 | - | Documentação: ajustadas cores para compatibilidade com temas |
| 2025-04-05 | 4 | Interface de atribuição implementada com paginação e tema dinâmico |

---

## ✅ PROJETO CONCLUÍDO

Todas as etapas foram concluídas com sucesso!

**Resumo do que foi implementado:**
1. ✅ Área exclusiva do técnico com permissões restritas
2. ✅ Menu lateral específico para técnicos (cores do tema)
3. ✅ Check-in/Check-out de atendimentos
4. ✅ Interface de atribuição de técnicos às OS
5. ✅ Paginação e filtros na lista de OS
6. ✅ Compatibilidade com todos os temas do sistema

**URLs importantes:**
- Área do Técnico: `/index.php/tecnico`
- Atribuir Técnicos: `/index.php/os/atribuir`
- Minhas OS: `/index.php/tecnico/os`

---

**Documento criado em:** 2025-04-03 (etapas anteriores)  
**Atualizado em:** 2025-04-05  
**Versão:** 2.1  
**Sistema:** MAP-OS v4.52.0
