/**
 * rh-facial.js — camada de reconhecimento facial no navegador (client-side).
 *
 * Usa face-api.js (global `faceapi`) para gerar o "descriptor" (vetor de 128
 * floats) do rosto e comparar com o descriptor de referência do colaborador.
 * TUDO roda no navegador; o servidor só recebe o score final e a selfie.
 *
 * Degradação graciosa: se a lib/modelos não carregarem, `ready` fica false e
 * o ponto continua funcionando só com selfie + GPS (o back-end trata o facial
 * como opcional por padrão). Assim a batida nunca fica refém do facial.
 *
 * Modelos: baixe os pesos do face-api.js (tiny_face_detector,
 * face_landmark_68, face_recognition) e coloque em assets/models/face/.
 */
window.RhFacial = (function () {
    var state = { ready: false, loading: false, error: null };

    async function carregar(url) {
        await faceapi.nets.tinyFaceDetector.loadFromUri(url);
        await faceapi.nets.faceLandmark68Net.loadFromUri(url);
        await faceapi.nets.faceRecognitionNet.loadFromUri(url);
    }

    /**
     * Carrega os modelos. Tenta `modelsUrl` (tipicamente local) e, se falhar,
     * cai para `fallbackUrl` (CDN) automaticamente. Assim funciona out-of-the-box
     * e passa a usar os arquivos locais quando estiverem presentes.
     */
    async function init(modelsUrl, fallbackUrl) {
        if (state.ready || state.loading) return state.ready;
        if (typeof faceapi === 'undefined') {
            state.error = 'lib-indisponivel';
            return false;
        }
        state.loading = true;
        try {
            await carregar(modelsUrl);
            state.ready = true;
            state.origem = 'local';
        } catch (e) {
            if (fallbackUrl && fallbackUrl !== modelsUrl) {
                try {
                    await carregar(fallbackUrl);
                    state.ready = true;
                    state.origem = 'cdn';
                } catch (e2) {
                    state.error = 'modelos-indisponiveis';
                    if (window.console) console.warn('RhFacial: modelos não carregaram (local nem CDN) —', e2);
                }
            } else {
                state.error = 'modelos-indisponiveis';
                if (window.console) console.warn('RhFacial: modelos não carregaram —', e);
            }
        }
        state.loading = false;
        return state.ready;
    }

    /** Detecta 1 rosto e devolve o descriptor (Array de floats) ou null. */
    async function getDescriptor(input) {
        if (!state.ready) return null;
        try {
            var det = await faceapi
                .detectSingleFace(input, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
                .withFaceLandmarks()
                .withFaceDescriptor();
            if (!det) return null;
            return Array.from(det.descriptor);
        } catch (e) {
            if (window.console) console.warn('RhFacial.getDescriptor', e);
            return null;
        }
    }

    /**
     * Compara dois descriptors e devolve um score de similaridade 0..1
     * (1 = idêntico). Baseado na distância euclidiana; ~0.6 de distância é o
     * limiar clássico do face-api, aqui convertido para score.
     */
    function compare(a, b) {
        if (!a || !b || !a.length || a.length !== b.length) return 0;
        var soma = 0;
        for (var i = 0; i < a.length; i++) {
            var d = a[i] - b[i];
            soma += d * d;
        }
        var dist = Math.sqrt(soma);
        var score = 1 - dist; // dist 0 -> 1.0 ; dist 0.45 -> 0.55 ; dist 0.6 -> 0.4
        return Math.max(0, Math.min(1, score));
    }

    function isReady() { return state.ready; }
    function getError() { return state.error; }

    return { init: init, getDescriptor: getDescriptor, compare: compare, isReady: isReady, getError: getError };
})();
