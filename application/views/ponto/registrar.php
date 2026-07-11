<?php
$this->load->view('colaborador/_topo', [
    'titulo' => 'Registrar Ponto',
    'header_icone' => 'bx-fingerprint',
    'header_sub' => $colaborador->nome,
]);

// Unidade padrão do colaborador (para geofence)
$unidadePadrao = null;
foreach ($unidades as $u) {
    if ($u->id == $colaborador->unidade_id) { $unidadePadrao = $u; break; }
}
$labelsTipo = [
    'entrada' => 'Entrada', 'saida' => 'Saída',
    'inicio_intervalo' => 'Início do intervalo', 'fim_intervalo' => 'Fim do intervalo',
];
?>
<div class="ponto-wrap">

    <div class="ponto-clock">
        <div class="hora" id="relogio">--:--</div>
        <div class="data" id="data-hoje"></div>
    </div>

    <div class="ponto-cam" id="cam-box">
        <video id="cam" autoplay playsinline muted></video>
        <canvas id="canvas" style="display:none"></canvas>
        <div class="cam-placeholder" id="cam-off" style="display:none">
            <i class='bx bx-camera-off'></i>
            <div>Câmera indisponível.<br>A batida seguirá só com GPS.</div>
        </div>
        <span class="ponto-face-badge off" id="face-badge"><i class='bx bx-face'></i> Facial: —</span>
        <div class="ponto-geo" id="geo-badge"><i class='bx bx-map'></i> Localizando…</div>
    </div>

    <?php if (count($unidades) > 0): ?>
    <div style="margin-top:12px">
        <label style="font-size:13px;color:#6b7280">Local de trabalho</label>
        <select id="unidade" class="span12" style="width:100%">
            <?php foreach ($unidades as $u): ?>
                <option value="<?= $u->id ?>"
                    data-lat="<?= $u->latitude ?>" data-lng="<?= $u->longitude ?>" data-raio="<?= $u->raio_metros ?>"
                    <?= ($u->id == $colaborador->unidade_id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u->nome) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <div class="ponto-tipo">
        <div class="lbl">Próxima batida</div>
        <div class="val" id="proximo-tipo-label"><?= $labelsTipo[$proximo_tipo] ?? 'Entrada' ?></div>
    </div>

    <button type="button" class="btn-bater" id="btn-bater" data-tipo="<?= $proximo_tipo ?>">
        <i class='bx bx-check-circle'></i> Registrar <?= $labelsTipo[$proximo_tipo] ?? 'Entrada' ?>
    </button>

    <div class="ponto-timeline" id="timeline">
        <h4><i class='bx bx-time-five'></i> Batidas de hoje</h4>
        <div id="lista-batidas">
            <?php if (empty($batidas_hoje)): ?>
                <div style="color:#9ca3af;font-size:13px" id="sem-batidas">Nenhuma batida hoje.</div>
            <?php else: foreach ($batidas_hoje as $b): ?>
                <div class="ponto-batida <?= ($b->dentro_geofence === '0') ? 'fora' : '' ?>">
                    <span class="dot"></span>
                    <span class="tipo"><?= $labelsTipo[$b->tipo] ?? $b->tipo ?>
                        <?php if ($b->dentro_geofence === '0'): ?><small>fora da área (<?= (int)$b->distancia_metros ?>m)</small><?php endif; ?>
                    </span>
                    <span class="hora"><?= date('H:i', strtotime($b->data_hora)) ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php
$this->load->view('colaborador/_nav', ['nav_ativo' => 'ponto', 'pode_bater_ponto' => true]);
?>

<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>
<script src="<?= base_url('assets/js/rh-facial.js') ?>"></script>
<script>
(function () {
    var CFG = {
        geofenceObrigatorio: <?= (int) $cfg['geofence_obrigatorio'] ?>,
        faceObrigatorio: <?= (int) $cfg['face_obrigatorio'] ?>,
        faceScoreMinimo: <?= (float) $cfg['face_score_minimo'] ?>,
        modelsUrl: '<?= base_url('assets/models/face') ?>',
        registrarUrl: '<?= site_url('ponto/registrar') ?>',
        descriptorUrl: '<?= site_url('ponto/descriptor') ?>'
    };
    var LABELS = <?= json_encode($labelsTipo) ?>;

    var geo = { lat: null, lng: null, ok: null };
    var refDescriptor = null;
    var faceScore = null;

    // ---- Relógio ----
    function tick() {
        var d = new Date();
        document.getElementById('relogio').textContent =
            ('0'+d.getHours()).slice(-2)+':'+('0'+d.getMinutes()).slice(-2);
        document.getElementById('data-hoje').textContent =
            d.toLocaleDateString('pt-BR', { weekday:'long', day:'2-digit', month:'long' });
    }
    tick(); setInterval(tick, 1000 * 20);

    // ---- CSRF (lê nome/valor das metatags + cookie) ----
    function csrf() {
        var nome = document.querySelector('meta[name="csrf-token-name"]').content;
        var cookieNome = document.querySelector('meta[name="csrf-cookie-name"]').content;
        var m = document.cookie.match(new RegExp('(^| )'+cookieNome+'=([^;]+)'));
        return { nome: nome, valor: m ? m[2] : '' };
    }

    // ---- Câmera ----
    var video = document.getElementById('cam');
    var canvas = document.getElementById('canvas');
    var camOk = false;
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 480, height: 640 }, audio: false })
            .then(function (stream) { video.srcObject = stream; camOk = true; })
            .catch(function () { mostrarCamOff(); });
    } else { mostrarCamOff(); }

    function mostrarCamOff() {
        camOk = false;
        video.style.display = 'none';
        document.getElementById('cam-off').style.display = 'block';
        setFaceBadge('off', 'sem câmera');
    }

    function capturarSelfie() {
        if (!camOk || !video.videoWidth) return null;
        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        return canvas.toDataURL('image/jpeg', 0.8);
    }

    // ---- GPS ----
    function atualizarGeo() {
        if (!navigator.geolocation) { setGeo('warn', 'GPS indisponível'); return; }
        navigator.geolocation.getCurrentPosition(function (pos) {
            geo.lat = pos.coords.latitude; geo.lng = pos.coords.longitude;
            avaliarGeofence();
        }, function () { setGeo('warn', 'Sem permissão de localização'); },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
    }
    function avaliarGeofence() {
        var sel = document.getElementById('unidade');
        if (!sel || geo.lat === null) { setGeo('', 'GPS obtido'); return; }
        var opt = sel.options[sel.selectedIndex];
        var ulat = parseFloat(opt.getAttribute('data-lat')), ulng = parseFloat(opt.getAttribute('data-lng'));
        var raio = parseInt(opt.getAttribute('data-raio')||'150', 10);
        if (isNaN(ulat) || isNaN(ulng)) { setGeo('', 'GPS obtido (unidade sem geofence)'); geo.ok = null; return; }
        var dist = haversine(geo.lat, geo.lng, ulat, ulng);
        geo.ok = dist <= raio;
        if (geo.ok) setGeo('ok', 'Dentro da área ('+Math.round(dist)+'m)');
        else setGeo('warn', 'Fora da área ('+Math.round(dist)+'m)');
    }
    function haversine(la1, lo1, la2, lo2) {
        var R = 6371000, dLa = (la2-la1)*Math.PI/180, dLo = (lo2-lo1)*Math.PI/180;
        var a = Math.sin(dLa/2)*Math.sin(dLa/2) +
                Math.cos(la1*Math.PI/180)*Math.cos(la2*Math.PI/180)*Math.sin(dLo/2)*Math.sin(dLo/2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }
    function setGeo(cls, txt) {
        var el = document.getElementById('geo-badge');
        el.className = 'ponto-geo ' + cls;
        el.innerHTML = "<i class='bx bx-map'></i> " + txt;
    }
    function setFaceBadge(cls, txt) {
        var el = document.getElementById('face-badge');
        el.className = 'ponto-face-badge ' + cls;
        el.innerHTML = "<i class='bx bx-face'></i> Facial: " + txt;
    }
    atualizarGeo();
    var selU = document.getElementById('unidade');
    if (selU) selU.addEventListener('change', avaliarGeofence);

    // ---- Facial (assíncrono, não bloqueia a tela) ----
    (async function () {
        try {
            var ok = await RhFacial.init(CFG.modelsUrl);
            if (!ok) { setFaceBadge('off', CFG.faceObrigatorio ? 'indisponível' : 'opcional'); return; }
            var resp = await fetch(CFG.descriptorUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            var j = await resp.json();
            if (j.success && j.tem_biometria) { refDescriptor = j.descriptor; setFaceBadge('warn', 'pronto'); }
            else setFaceBadge('off', 'sem cadastro');
        } catch (e) { setFaceBadge('off', 'indisponível'); }
    })();

    async function avaliarFace() {
        faceScore = null;
        if (!RhFacial.isReady() || !refDescriptor || !camOk) return;
        var desc = await RhFacial.getDescriptor(video);
        if (!desc) { setFaceBadge('warn', 'rosto não detectado'); return; }
        faceScore = RhFacial.compare(refDescriptor, desc);
        if (faceScore >= CFG.faceScoreMinimo) setFaceBadge('ok', 'confere ('+faceScore.toFixed(2)+')');
        else setFaceBadge('warn', 'baixa ('+faceScore.toFixed(2)+')');
    }

    // ---- Bater ponto ----
    var btn = document.getElementById('btn-bater');
    btn.addEventListener('click', async function () {
        btn.disabled = true;
        var tipo = btn.getAttribute('data-tipo');

        atualizarGeo();
        await avaliarFace();

        if (CFG.faceObrigatorio && (faceScore === null || faceScore < CFG.faceScoreMinimo)) {
            alerta('Reconhecimento facial obrigatório e não confirmado. Ajuste a iluminação e tente novamente.', 'error');
            btn.disabled = false; return;
        }
        if (CFG.geofenceObrigatorio && geo.ok === false) {
            alerta('Você está fora da área permitida. Aproxime-se do local.', 'error');
            btn.disabled = false; return;
        }

        var selfie = capturarSelfie();
        var sel = document.getElementById('unidade');
        var c = csrf();
        var fd = new FormData();
        fd.append(c.nome, c.valor);
        fd.append('tipo', tipo);
        if (selfie) fd.append('foto', selfie);
        if (geo.lat !== null) { fd.append('latitude', geo.lat); fd.append('longitude', geo.lng); }
        if (faceScore !== null) fd.append('face_score', faceScore.toFixed(4));
        if (sel) fd.append('unidade_id', sel.value);

        try {
            var r = await fetch(CFG.registrarUrl, {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin'
            });
            var j = await r.json();
            if (j.success) {
                adicionarBatida(tipo, j.hora, j.fora_area, j.distancia);
                if (j.proximo_tipo) {
                    btn.setAttribute('data-tipo', j.proximo_tipo);
                    btn.innerHTML = "<i class='bx bx-check-circle'></i> Registrar " + (LABELS[j.proximo_tipo]||'Entrada');
                    document.getElementById('proximo-tipo-label').textContent = LABELS[j.proximo_tipo]||'Entrada';
                }
                alerta(j.message, 'success');
            } else {
                alerta(j.message || 'Erro ao registrar.', 'error');
            }
        } catch (e) {
            alerta('Falha de conexão ao registrar o ponto.', 'error');
        }
        btn.disabled = false;
    });

    function adicionarBatida(tipo, hora, fora) {
        var semBat = document.getElementById('sem-batidas');
        if (semBat) semBat.remove();
        var div = document.createElement('div');
        div.className = 'ponto-batida' + (fora ? ' fora' : '');
        div.innerHTML = '<span class="dot"></span><span class="tipo">' + (LABELS[tipo]||tipo) +
            (fora ? ' <small>fora da área</small>' : '') + '</span><span class="hora">' + hora + '</span>';
        document.getElementById('lista-batidas').appendChild(div);
    }

    function alerta(msg, tipo) {
        if (window.Swal) { Swal.fire({ title: tipo === 'success' ? 'Pronto!' : 'Atenção', text: msg, icon: tipo }); }
        else { alert(msg); }
    }
})();
</script>
<script src="<?= base_url('assets/js/sweetalert2.all.min.js') ?>"></script>
</body>
</html>
