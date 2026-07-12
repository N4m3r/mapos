<div class="new122">
    <div class="widget-title" style="margin:-20px 0 10px">
        <span class="icon"><i class="fas fa-user-shield"></i></span>
        <h5>Biometria facial — <?= htmlspecialchars($colaborador->nome) ?></h5>
    </div>

    <div class="widget-box"><div class="widget-content" style="padding:18px">
        <p>Posicione o rosto do colaborador na câmera, com boa iluminação, e clique em <strong>Capturar rosto</strong>.
           O sistema gera um código facial (não guarda a imagem como "senha"); a selfie fica só como referência.</p>

        <?php if (! empty($tem_biometria)): ?>
            <div class="alert alert-info">Este colaborador já possui biometria cadastrada. Capturar novamente substitui a anterior.</div>
        <?php endif; ?>

        <div style="max-width:420px;margin:0 auto">
            <div style="position:relative;background:#0b1020;border-radius:14px;overflow:hidden;aspect-ratio:3/4">
                <video id="bio-cam" autoplay playsinline muted style="width:100%;height:100%;object-fit:cover"></video>
                <canvas id="bio-canvas" style="display:none"></canvas>
                <span id="bio-status" style="position:absolute;top:10px;left:10px;padding:5px 10px;border-radius:999px;background:rgba(17,24,39,.75);color:#fff;font-size:12px">Carregando modelos…</span>
            </div>
            <div style="display:flex;gap:8px;margin-top:12px">
                <button type="button" id="bio-capturar" class="button btn btn-primary" style="flex:1" disabled><span class="button__text2">Capturar rosto</span></button>
                <button type="button" id="bio-salvar" class="button btn btn-success" style="flex:1" disabled><span class="button__text2">Salvar</span></button>
            </div>
            <a href="<?= site_url('rh/editarColaborador/'.$colaborador->id) ?>" class="button btn btn-warning" style="margin-top:8px;display:block;text-align:center"><span class="button__text2">Voltar</span></a>
        </div>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>
<script src="<?= base_url('assets/js/rh-facial.js') ?>"></script>
<script>
(function(){
    var MODELS = '<?= base_url('assets/models/face') ?>';
    var MODELS_CDN = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
    var SALVAR = '<?= site_url('rh/salvarBiometria') ?>';
    var COLAB = <?= (int) $colaborador->id ?>;
    var video = document.getElementById('bio-cam'), canvas = document.getElementById('bio-canvas');
    var descriptor = null, selfie = null;
    function status(t){ document.getElementById('bio-status').textContent = t; }

    navigator.mediaDevices && navigator.mediaDevices.getUserMedia({video:{facingMode:'user'},audio:false})
        .then(function(s){ video.srcObject = s; }).catch(function(){ status('Câmera indisponível'); });

    (async function(){
        var ok = await RhFacial.init(MODELS, MODELS_CDN);
        if (!ok){ status('Modelos faciais indisponíveis (sem conexão e sem arquivos locais em assets/models/face)'); return; }
        status('Pronto — capture o rosto');
        document.getElementById('bio-capturar').disabled = false;
    })();

    document.getElementById('bio-capturar').addEventListener('click', async function(){
        status('Detectando rosto…');
        descriptor = await RhFacial.getDescriptor(video);
        if (!descriptor){ status('Nenhum rosto detectado, tente de novo'); return; }
        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);
        selfie = canvas.toDataURL('image/jpeg', 0.8);
        status('Rosto capturado ✓ — clique em Salvar');
        document.getElementById('bio-salvar').disabled = false;
    });

    document.getElementById('bio-salvar').addEventListener('click', function(){
        if (!descriptor){ return; }
        var nome = document.querySelector('meta[name="csrf-token-name"]').content;
        var cookieNome = document.querySelector('meta[name="csrf-cookie-name"]').content;
        var m = document.cookie.match(new RegExp('(^| )'+cookieNome+'=([^;]+)'));
        var fd = new FormData();
        fd.append(nome, m ? m[2] : '');
        fd.append('colaborador_id', COLAB);
        fd.append('descriptor', JSON.stringify(descriptor));
        if (selfie) fd.append('foto', selfie);
        status('Salvando…');
        fetch(SALVAR, {method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(j){ status(j.message); if (j.success){ setTimeout(function(){ window.location='<?= site_url('rh/colaboradores') ?>'; }, 900); } })
            .catch(function(){ status('Erro ao salvar.'); });
    });
})();
</script>
