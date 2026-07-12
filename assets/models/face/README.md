# Modelos de reconhecimento facial (face-api.js)

O reconhecimento facial do ponto roda **no navegador** com o face-api.js
(`@vladmandic/face-api`). O carregamento é **local-first com fallback para CDN**:

1. Tenta carregar os modelos **desta pasta** (`assets/models/face/`).
2. Se não encontrar (pasta vazia / sem internet no servidor de arquivos),
   cai automaticamente para o **CDN** `https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model`.

Ou seja: **já funciona sem fazer nada** (via CDN, desde que o dispositivo do
colaborador tenha internet). Para uso **offline / rede local / mais rápido**,
baixe os 6 arquivos abaixo e coloque **nesta pasta**:

- `tiny_face_detector_model-weights_manifest.json` + `tiny_face_detector_model.bin` (~190 KB)
- `face_landmark_68_model-weights_manifest.json` + `face_landmark_68_model.bin` (~350 KB)
- `face_recognition_model-weights_manifest.json` + `face_recognition_model.bin` (~6,2 MB)

Baixe do mesmo CDN (mantém compatibilidade com a lib):
`https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/<arquivo>`

Enquanto os arquivos não estiverem aqui, o ponto continua funcionando com
**selfie + GPS** mesmo sem facial (o back-end trata o facial como opcional por
padrão). Para exigir facial, ligue `rh_face_obrigatorio` em Configurações e
ajuste `rh_face_score_minimo` (padrão 0.55).
