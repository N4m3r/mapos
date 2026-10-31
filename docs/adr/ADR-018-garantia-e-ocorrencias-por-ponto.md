# ADR-018: Garantia e ocorrências pós-entrega por ponto do projeto

- **Status**: proposed
- **Date**: 2026-10-31
- **Deciders**: N4m3r
- **Tags**: projetos, pontos, garantia, manutencao, cftv
- **Branch**: `seguranca-23-10`

## Context

Depois que o projeto é entregue, o atendimento volta a ser OS solta: o cliente liga dizendo que "a câmera
da doca está sem imagem" e ninguém sabe, pela OS, qual ponto do projeto é, se ainda está na garantia, quem
instalou nem quantas vezes o mesmo equipamento já deu problema. O sistema já tem:

- `obra_pontos` com identificação, local, IP/canal, modelo e série (ADR-013) e histórico (`obra_ponto_historico`);
- a ponte OS ↔ projeto (`obra_os`, `obra_ponto_os`, ADR-015), com `tipo_vinculo` `corretiva`;
- `os.garantia` (texto livre) e a tabela `garantias` (termos de garantia, sem prazo estruturado);
- etiquetas com QR por ponto (as-built), que levam ao ponto na área do técnico.

Falta prazo de garantia estruturado e um jeito de abrir ocorrência a partir do ponto.

## Decision (proposta)

1. **Garantia por projeto e por ponto.** No projeto: `garantia_meses` (padrão do tipo de projeto) contada da
   `data_fim_real` (conclusão real). No ponto: `garantia_ate` calculada ao concluir o projeto, com override manual (equipamento
   trocado ganha nova data). Garantia do fabricante opcional por ponto (`garantia_fabricante_ate`).
2. **Ocorrência = OS corretiva ligada ao ponto.** Botão "Abrir ocorrência" no ponto (gestão e QR da etiqueta)
   cria uma OS com `tipo_vinculo = corretiva`, `obra_ponto_os` apontando para o ponto, defeito pré-preenchido
   com identificação/local/IP/modelo/série e o selo "Em garantia até dd/mm/aaaa" ou "Fora da garantia".
   Não cria tabela nova de ocorrência: a OS já tem status, técnico, fotos, assinatura e faturamento.
3. **Cobrança.** Em garantia: OS nasce com `fatura_no_projeto = 0` e aviso "não cobrar mão de obra"; fora
   da garantia, segue o fluxo normal da OS. A decisão final de cobrar continua com quem fecha a OS.
4. **Indicadores no ponto e no projeto.** Contagem de ocorrências por ponto (reincidência), MTBF simples
   (dias entre ocorrências) e lista "pontos com 2+ ocorrências em 90 dias" no painel de projetos.
5. **Contrato de manutenção (fase 2).** Projeto entregue pode ganhar um contrato recorrente
   (periodicidade, valor, pontos cobertos) que gera OS preventiva por ponto e conta no indicador.

## Perguntas em aberto (para decidir antes de implementar)

- Prazo padrão de garantia por tipo de projeto (CFTV, acesso, alarme, rede)? 12 meses para todos?
- Ocorrência em garantia cobra deslocamento? Há franquia de visitas?
- A ocorrência pode ser aberta pelo próprio cliente (portal `acompanhar/`) lendo o QR, ou só pela equipe?
- Contrato de manutenção entra agora ou só depois da garantia estar em uso?

## Consequences

- Positivo: histórico por equipamento, garantia visível no atendimento, menos mão de obra cobrada errado,
  base para vender contrato de manutenção.
- Negativo: mais campos no ponto e no projeto; datas de garantia antigas precisam de carga inicial
  (projetos já entregues ficam sem garantia até alguém informar `data_fim_real` (conclusão real)).

## Links

- ADR-013 (pontos), ADR-014 (repasse), ADR-015 (ponte OS ↔ projeto)
