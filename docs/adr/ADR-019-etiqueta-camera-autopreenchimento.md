# ADR-019: Etiqueta da câmera lida no aparelho (autopreenche modelo e série/MAC)

- **Status**: accepted
- **Date**: 2026-10-30
- **Deciders**: N4m3r
- **Tags**: projetos, pontos, cftv, qr, celular, offline
- **Branch**: `seguranca-23-10`

## Context

Ao marcar uma câmera como "Instalado", o técnico digita modelo e série/MAC lendo a etiqueta, o que é lento e
gera erro de digitação. A etiqueta costuma ter QR code (Hik-Connect) ou código de barras com o número de série.

## Decision

Ler o QR/código de barras da foto **no próprio aparelho**. Sem IA, sem OCR e sem enviar a imagem a nenhum
serviço externo (escolha do usuário). A foto da etiqueta também é salva junto (`data-foto-compacta`) e conta
como foto do equipamento instalado.

1. `assets/js/etiqueta-camera.js`, em duas partes:
   - `interpretar(textos)`: função pura (UMD, testada em Node) que devolve `{modelo, serie, mac, origem}`.
     Reconhece MAC (`AA:BB:..` ou `AA-BB-..`, ou 12 hex após "MAC"), número de série completo Hikvision
     (modelo + AAAAMMDD + 4 letras + resto, com ou sem lente `(2.8mm)`, prefixos DS-/iDS-), série curta
     (letra + 8 dígitos), linha de modelo (DS-…, VIP/VHD/MHDX/NVD/iM) e `S/N:`/`SN:`. O código de verificação
     de 6 letras nunca vira série. Sem nada reconhecível, usa o maior token alfanumérico (8+) que não seja URL.
     Limites: modelo 80; série 60 ("SÉRIE / MAC" se couber, senão só a série).
   - UI: em `input[type=file][data-etiqueta]` decodifica com `BarcodeDetector` (qr_code, code_128, code_39,
     data_matrix, ean_13) e, se não houver ou nada achar, com `jsQR` (carregado sob demanda com o nonce da CSP;
     imagem até 1600 px e recorte central ampliado). Preenche os campos do mesmo form (`data-etiqueta-modelo` /
     `data-etiqueta-serie`, padrão `[name=modelo]`/`[name=serie]`), pede confirmação antes de sobrescrever valor
     diferente e mostra o status ao lado. Nunca bloqueia o envio.
2. Botão "Foto da etiqueta (preenche modelo e série)" no formulário "Instalado" da área do técnico
   (`tecnico/projeto.php`, `tecnico/fila.php`) e no modal de ponto da gestão (`obras/visualizar.php`).
3. `sw-tecnico.js` (VERSAO `v5`) guarda `etiqueta-camera.js` e `jsQR.js` para funcionar offline.
4. Testes: `node --test tests/js/`.
5. **Foto da etiqueta obrigatória na câmera instalada.** A foto da etiqueta vai em `fotos_etiqueta[]` e é gravada
   com `legenda = 'etiqueta'` (`Obra_pontos_model::LEGENDA_ETIQUETA`). Câmera indo para "Instalado" pela área do
   técnico só passa com a foto da etiqueta, nova ou já salva no ponto (`exigeEtiqueta`, `temFotoEtiqueta`);
   o navegador bloqueia antes (`form[data-exige-etiqueta]` em `foto-compacta.js`) e o servidor confere de novo
   (`Tecnico::ponto_status`). A foto da etiqueta também cumpre a foto obrigatória do equipamento instalado.
   Na gestão a foto da etiqueta é opcional, mas é gravada como etiqueta e vale para a regra.

## Consequences

- MAC só é preenchido se estiver em um código (QR/barras); texto impresso sem código não é lido (sem OCR).
- Marcas sem QR nem código de barras na etiqueta não preenchem nada; o técnico digita como hoje.
- O formato do QR do Hik-Connect pode variar por modelo/lote: validar com etiquetas reais e ajustar o parser.
- Em iPhone (sem BarcodeDetector) só QR é lido, via jsQR; código de barras linear depende do navegador.
