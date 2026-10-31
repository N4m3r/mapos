# ADR-021: Gestão de risco e compliance

- **Status**: proposed
- **Date**: 2026-10-31
- **Deciders**: N4m3r
- **Tags**: risco, compliance, lgpd, seguranca-do-trabalho, nr-10, nr-35, projetos, os, tecnico
- **Branch**: `master`

## Context

O sistema não tem gestão de risco nem de compliance. O mais próximo é o painel de SLA, que já sinaliza
OS "em risco" de estourar prazo. Os sinais de problema existem, mas estão espalhados: etapa atrasada,
custo acima do previsto, ponto parado (alerta de repasse, ADR-014), saldo de material negativo, medição
aprovada e não faturada, câmera reprovada no teste de imagem (ADR-019).

Para uma empresa de segurança eletrônica, o risco aparece em três frentes:

1. **Projeto/OS**: atraso, estouro de custo, falta de material, cliente sem pagar, ponto parado.
2. **Segurança do trabalho**: altura (NR-35), eletricidade (NR-10), espaço confinado; exige APR
   (Análise Preliminar de Risco), EPI e técnico habilitado.
3. **Cliente/contrato**: inadimplência, garantia vencendo, equipamento com falha recorrente.

E o compliance responde a outra pergunta: "estamos cumprindo o que a lei, as normas e os contratos
exigem, e conseguimos provar?". Cada obrigação não cumprida vira um risco (ex.: técnico com NR-35
vencida é falha de compliance e risco crítico de segurança ao mesmo tempo).

| Área | Exemplos de obrigação |
|---|---|
| **LGPD** | Câmeras gravam pessoas; o sistema guarda fotos de clientes, endereços e CPF. Base legal, controle de quem acessa, prazo de retenção, atendimento ao titular. |
| **Segurança do trabalho** | NR-10, NR-35, ASO, EPI entregue com assinatura, APR antes do serviço. |
| **Documentação técnica** | ART/RRT quando aplicável, as-built entregue, termo de entrega e garantia assinado pelo cliente. |
| **Empresa** | Alvarás, certidões, CND, seguro de responsabilidade, licenças (com validade). |
| **Contratos** | Prazos de SLA, cláusulas de garantia, contrato de manutenção, retenção de gravação combinada com o cliente. |
| **Fiscal** | NF-e para cada faturamento (já existe em parte). |

## Decision (proposta)

Um único módulo **"Riscos e Compliance"**, com três abas: **Obrigações**, **Riscos** e **Painel**.

### 1. Registro de riscos (projeto e OS)

- Aba "Riscos" no projeto e na OS. Cada risco: descrição, categoria (prazo, custo, segurança, técnico,
  financeiro, qualidade, suprimento), **probabilidade (1–5) × impacto (1–5)** = nível baixo / médio /
  alto / crítico.
- Responsável, plano de ação, prazo e status (aberto → em tratamento → mitigado/encerrado).
- Pode ser ligado a uma etapa, a um ponto ou a uma OS.

### 2. Riscos automáticos (a partir do que o sistema já mede)

| Sinal que já existe | Risco gerado |
|---|---|
| Etapa atrasada / projeto sem OS | Prazo |
| Custo real passando do previsto | Custo |
| Ponto parado (alerta de repasse, ADR-014) | Execução |
| OS perto de estourar o SLA | Prazo / contrato |
| Material com saldo negativo no almoxarifado | Suprimento |
| Medição aprovada e não faturada, cobrança vencida | Financeiro |
| Câmera reprovada no teste de imagem (ADR-019) | Qualidade |
| Obrigação de compliance vencida ou sem evidência | Compliance |

### 3. Catálogo de obrigações (compliance)

- Cada item: norma/lei, a quem se aplica (empresa, técnico, projeto, OS, cliente), periodicidade ou
  validade, evidência exigida (documento, foto, assinatura, checklist) e responsável.
- Vem com um modelo pronto para o ramo (NR-10, NR-35, ASO, LGPD, termo de entrega, ART), editável.

### 4. Evidências e vencimentos

- Cada obrigação recebe sua prova (PDF de certificado, foto, assinatura do cliente no celular,
  checklist), com data, quem enviou e validade.
- Situação: ✓ em dia, ⚠ vencendo em 30 dias, ✗ vencida/faltando. Alerta por WhatsApp/e-mail,
  reaproveitando o mecanismo do alerta de repasse (`php index.php lembretes ...`).

### 5. Regras que travam o processo

- Técnico sem NR-35/NR-10 válida não pode ser escalado para OS ou etapa marcada como "altura" ou
  "elétrico". Habilitações (NR-10, NR-35, ASO) com validade no cadastro do técnico.
- **APR no check-in**: antes do check-in de OS/etapa marcada como risco, o técnico preenche no celular
  uma APR curta (riscos do local, EPIs conferidos, foto do local). Fica na OS e sai na impressão.
- Projeto só pode ser marcado como "Entregue" com as-built e termo de entrega assinados.
- Mesmo padrão da foto obrigatória da etiqueta (ADR-019): bloqueio no navegador e conferência no
  servidor.

### 6. LGPD

- Registro de acesso: quem viu fotos de atendimento e de pontos, quando e de onde (as rotas de foto já
  foram fechadas por permissão; falta o registro).
- Retenção configurável para fotos e dados de clientes inativos, com aviso antes de anonimizar/apagar.
- Pedidos do titular: exportar ou apagar os dados de um cliente, com registro do pedido.
- Termo de ciência no portal do cliente sobre o uso de imagens e dados.

### 7. Painel e auditoria

- Matriz probabilidade × impacto, riscos por projeto e categoria, críticos abertos, riscos vencidos sem
  tratamento, % de conformidade por área, itens vencidos / a vencer / sem evidência, por técnico e
  projeto. Mesmo molde dos relatórios com painel (gráficos SVG, impressão).
- A trilha de auditoria existente (menu "Auditoria") vira prova: quem fez o quê e quando.
- Relatório de conformidade pronto para imprimir/enviar a cliente ou fiscalização.

## Fases sugeridas

1. Habilitações dos técnicos (NR-10, NR-35, ASO) com validade e bloqueio de escala + APR no check-in.
2. Catálogo de obrigações da empresa e dos contratos, com evidências, vencimentos e alertas.
3. Registro de riscos e riscos automáticos.
4. LGPD: registro de acesso, retenção e pedidos do titular.
5. Painel e relatório de conformidade.

Cada fase entregue e testada separadamente; tarefas divididas pelo Ruflo entre agentes de menor custo,
com integração e testes no fim (como nos relatórios, ADR-020).

## Perguntas em aberto (decidir antes de implementar)

1. Qual frente é prioridade: segurança do trabalho (NR/APR), documentação e vencimentos, LGPD ou riscos
   de projeto?
2. Bloqueios devem impedir o trabalho (técnico vencido não é escalado; sem APR não faz check-in) ou só
   avisar no começo?
3. Riscos automáticos são criados sozinhos ou só sugeridos para alguém confirmar?
4. Quem vê e edita riscos: só a gestão, ou também os técnicos (registrar risco encontrado em campo)?
5. Algum cliente exige comprovação de conformidade (supermercado, indústria, banco)? Se sim, o relatório
   de conformidade por cliente entra mais cedo.
6. Certificados dos técnicos ficam guardados no sistema (PDF/foto) ou só a data de validade?

## Consequences

- Positivo: problemas que hoje estão espalhados viram uma lista única priorizada; prova de conformidade
  para clientes e fiscalização; menos risco de acidente e de multa (NR, LGPD).
- Negativo: mais cadastro (habilitações, evidências) e regras que podem travar o trabalho se os dados
  não estiverem em dia — por isso a decisão sobre "bloquear ou avisar" vem antes.

## Links

- ADR-012/013/014 (projetos, pontos, repasse), ADR-015 (OS ↔ projeto), ADR-019 (etiqueta e teste de
  imagem), ADR-020 (relatório de OS), painel de SLA, auditoria.
