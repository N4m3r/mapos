# ADR-028: Catálogo único de permissões, telas de perfil e usuário unificadas e dados fiscais do emitente

- **Status**: accepted
- **Date**: 2026-11-07
- **Deciders**: N4m3r
- **Tags**: permissoes, usuarios, emitente, fiscal, ux, seguranca
- **Branch**: `permissoes-usuarios-emitente-chamados`
- **Relacionado**: ADR-023 (menu unificado com catálogo único), ADR-009 (interface moderna), ADR-005 (segurança, CSP e CSRF)

## Context

Três telas de configuração cresceram em paralelo e divergiram do resto do sistema:

1. **A lista de permissões existia em quatro lugares.** As views de perfil eram duas
   cópias quase inteiras (918 e 1.322 linhas, cadastrar e editar), o array de cada action do controller
   repetia as chaves e o seed do banco tinha a sua própria versão. O resultado era
   medível: `editar()` gravava 103 chaves e `adicionar()` gravava 109, então **editar um
   perfil apagava as cinco permissões de Contrato**; `aPagamento`/`ePagamento`/
   `dPagamento`/`vPagamento` continuavam no formulário sem nenhum uso no código; e o
   seed do Administrador tinha 53 chaves enquanto o sistema já usava mais de cem — um
   perfil recém-instalado abria telas que o banco dizia que ele não podia abrir.

2. **Cadastrar e editar usuário eram duas telas** com os mesmos campos em ordens
   diferentes. A edição não mostrava quando a conta entrou pela última vez, não avisava
   quando o perfil estava inativo e não tratava validade em branco: a conta nascia sem
   expiração e o login a considerava expirada no mesmo instante, porque `new DateTime(null)`
   vira "agora" (e emite *deprecation* no PHP 8).

3. **O emitente não guardava o que a nota precisa.** Faltavam nome fantasia, inscrição
   municipal e complemento. A NFS-e lia a IM das configurações fiscais e ignorava o
   cadastro do emitente, a NF-e não enviava `xFant`/`IM`/`xCpl`, e o cabeçalho dos
   impressos — copiado em cinco views, com uma variação em cada — nunca mostrava IE nem IM.

## Decision

1. **Uma fonte para as permissões.** `helpers/permissoes_helper.php` passa a descrever
   cada chave uma única vez (104 chaves em 17 seções, com rótulo, descrição e `padrao`).
   A tela de perfis só desenha o catálogo; o controller só grava as chaves que estão
   nele. Chave fora do catálogo é ignorada ao salvar, e chave usada no código sem estar
   no catálogo quebra a suíte.

2. **Perfis existentes são completados por herança.** A migration
   `20261105000000_add_emitente_fiscal_e_acesso.php` acrescenta as chaves novas a cada
   perfil já gravado, herdando da permissão "mãe" equivalente (22 pares, como
   `eNfe <- eOs` e `vEntrada <- vNfe`). Nada é removido: quem configura o sistema
   continua configurando, quem emite nota continua emitindo. Das 13 chaves que o seed
   não carrega, 11 são exclusivas da Área do Técnico e ficam fora da herança — o
   Administrador nunca as teve, nem no seed antigo de 53 chaves, e continua sendo um
   perfil de escritório. As outras duas (`vAreaColaborador`, `baterPonto`) herdam de
   `vTecnicoDashboard`, que o Administrador também não tem, então nascem desligadas.

3. **O seed do Administrador acompanha o catálogo.** O `banco.sql` passa a semear 91
   chaves (as 104 menos as 13 da Área do Técnico e do Colaborador). A conta fecha:
   instalação nova grava 91, e base existente termina com 93 — as 91 mais as duas
   herdadas, que entram em 0 — com o mesmo acesso efetivo nos dois casos.

4. **Perfil e usuário com um formulário cada.** Cadastro e edição compartilham a mesma
   view, em abas. A lista de perfis mostra cobertura (quantas permissões liberadas, com
   busca e "marcar as visíveis") e a de usuários filtra por texto, perfil e situação na
   URL, com validade e **último acesso** na linha. Validade em branco passa a significar
   "sem expiração", e a comparação do login é por dia (quem vence hoje ainda entra).

5. **O emitente vira a fonte dos dados fiscais.** Nome fantasia, inscrição municipal e
   complemento entram na tabela `emitente`. A NF-e envia `xFant`, `IM`, `xCpl` e o
   telefone; a NFS-e usa a IM do emitente e cai para a das configurações fiscais quando
   ela estiver vazia. Os e-mails ganham `empresa_fantasia`, `empresa_ie` e `empresa_im`,
   e o cabeçalho dos cinco impressos vira a partial `views/comum/_emitente_cabecalho.php`.

## Implementacao

| Parte | Arquivos |
|---|---|
| Catálogo e regras puras | `helpers/permissoes_helper.php` (novo), `tests/Unit/PermissoesCatalogoTest.php` (novo) |
| Perfis | `controllers/Permissoes.php`, `views/permissoes/formulario.php` (novo), `views/permissoes/permissoes.php`, `tests/Unit/PermissoesViewTest.php` (novo) |
| Usuários | `controllers/Usuarios.php`, `models/Usuarios_model.php` (`marcarAcesso`, filtros), `views/usuarios/formulario.php` (novo), `views/usuarios/usuarios.php`, `tests/Unit/UsuariosViewTest.php` (novo) |
| Emitente | `controllers/Mapos.php`, `models/Mapos_model.php` (gravação por array com colunas filtradas), `helpers/emitente_helper.php` (novo), `views/mapos/emitente.php`, `views/comum/_emitente_cabecalho.php` (novo), `tests/Unit/EmitenteHelperTest.php` e `tests/Unit/EmitenteViewTest.php` (novos) |
| Fiscal | `libraries/Fiscal/NfeService.php`, `libraries/Fiscal/NfseService.php`, `libraries/Emailtemplate.php`, `views/nfe/danfe_print.php` |
| Impressos | `views/os/imprimirOs.php`, `views/vendas/imprimirVenda.php`, `views/vendas/imprimirVendaOrcamento.php`, `views/conecte/imprimirOs.php`, `views/conecte/imprimirVenda.php`, `views/checkin/imprimirCheckin.php`, `assets/css/imprimir.css` |
| Banco | `database/migrations/20261105000000_add_emitente_fiscal_e_acesso.php` (novo), `database/seeds/Permissoes.php`, `banco.sql` |
| Removido | `views/usuarios/adicionarUsuario.php`, `views/usuarios/editarUsuario.php`, `views/permissoes/adicionarPermissao.php`, `views/permissoes/editarPermissao.php` |

## Consequences

**Ganhos.** Editar um perfil deixa de apagar permissões; instalação nova nasce coerente
com a tela que configura; a suíte cobre as três telas (52 casos novos) e falha se o
catálogo divergir do código — `testChavesUsadasNoCodigoExistemNoCatalogo` varre os
fontes atrás de `checkPermission`, `$pode`, `->exigir` e `'permissao' =>` e recusa
chave que não esteja no catálogo. A emissão passa a usar o cadastro do emitente, e o
cabeçalho dos documentos deixa de ser cinco cópias que precisavam ser corrigidas juntas.

**Custos e limites.** A migration completa perfis mas **não inventa acesso**: chave
nova sem "mãe" equivalente entra desligada, e quem administra precisa abrir a tela e
marcar. O seed `a:91` só vale para instalação nova — em base existente quem manda é a
migration. A tela do emitente continua dependendo de `cEmitente`, e o checklist fiscal
lê `configuracoes_nfe` só quando a tabela existe.

**Não coberto.** Os testes de navegador (`tests/navegador/telas.mjs`) e o smoke não
foram executados nesta rodada: o ambiente não tem MySQL nem servidor PHP no ar. A
validação foi a suíte de unidade (583 casos), o lint de todos os arquivos PHP e a
renderização das telas e dos seis impressos com um CodeIgniter mínimo em memória.

## Links

- **Estende**: ADR-023 (menu unificado, que introduziu o catálogo de navegação), ADR-009 (interface moderna, da qual estas telas seguem os componentes)
- **Related**: ADR-005 (segurança, CSP e CSRF), ADR-027 (numeração de chamados, no mesmo ciclo)
- **Testes**: `tests/Unit/PermissoesCatalogoTest.php`, `tests/Unit/PermissoesViewTest.php`, `tests/Unit/UsuariosViewTest.php`, `tests/Unit/EmitenteHelperTest.php`, `tests/Unit/EmitenteViewTest.php`
