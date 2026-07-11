# Modelos de reconhecimento facial (face-api.js)

O reconhecimento facial do ponto roda **no navegador** com o face-api.js.
Para ativá-lo, coloque **nesta pasta** os arquivos de pesos dos 3 modelos:

- `tiny_face_detector_model-weights_manifest.json` + `tiny_face_detector_model-shard1`
- `face_landmark_68_model-weights_manifest.json` + `face_landmark_68_model-shard1`
- `face_recognition_model-weights_manifest.json` + `face_recognition_model-shard1` (+ `-shard2`)

Onde baixar: repositório oficial do face-api.js, pasta `weights/`
(https://github.com/justadudewhohacks/face-api.js — diretório `weights`).

Enquanto os arquivos não estiverem aqui, o ponto continua funcionando
normalmente com **selfie + GPS** (o `face_score` fica em branco). Para tornar
o facial obrigatório, ligue `rh_face_obrigatorio` em Configurações e ajuste
`rh_face_score_minimo` (padrão 0.55).
