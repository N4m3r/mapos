<?php
$this->load->view('tecnico/_topo', [
    'titulo'       => 'Assinatura do Solicitante',
    'header_icone' => 'bx-pen',
    'header_sub'   => 'OS #' . sprintf('%04d', $os->idOs),
    'voltar_url'   => site_url('tecnico/visualizar/' . $os->idOs),
]);

$fone = !empty($cliente->celular) ? $cliente->celular : (!empty($cliente->telefone) ? $cliente->telefone : '');
$wa = preg_replace('/\D/', '', $fone);
if ($wa !== '' && strlen($wa) <= 11) { $wa = '55' . $wa; }
$emailCliente = !empty($cliente->email) ? $cliente->email : '';
?>

<style>
    .asg-tabs { display:flex; gap:8px; margin-bottom:16px; }
    .asg-tab {
        flex:1; text-align:center; padding:12px; border-radius:12px; font-weight:700; font-size:14px;
        background:#eef0f7; color:#55596e; cursor:pointer; border:1.5px solid transparent;
    }
    .asg-tab.active { background:#fff; color:var(--tec-grad-1); border-color:var(--tec-grad-1); }
    .asg-pane { display:none; }
    .asg-pane.active { display:block; }
    .asg-input {
        width:100%; box-sizing:border-box; padding:12px 14px; font-size:15px; margin-bottom:12px;
        border:1.5px solid #e2e5f0; border-radius:12px; background:#fff; color:var(--tec-ink);
    }
    .asg-canvas-wrap { border:2px dashed #c7ccdd; border-radius:12px; background:#fff; touch-action:none; min-height:220px; padding:0; }
    #asg-canvas { display:block; width:100%; touch-action:none; cursor:crosshair; border-radius:10px; }
    .asg-link-box {
        display:flex; gap:8px; align-items:center; background:#eef0f7; border-radius:12px; padding:12px; margin-top:12px;
        word-break:break-all; font-size:13px;
    }
    .asg-msg { padding:12px; border-radius:12px; margin-bottom:12px; font-size:14px; display:none; }
    .asg-msg.ok { background:var(--tec-success-bg); color:var(--tec-success); }
    .asg-msg.err { background:var(--tec-danger-bg); color:var(--tec-danger); }
</style>

<div class="tec-container">

    <div class="asg-msg" id="asg-msg"></div>

    <!-- Assinaturas já coletadas -->
    <?php if (!empty($assinaturas)): ?>
        <div class="info-card">
            <h3><i class='bx bx-check-shield'></i> Assinaturas registradas</h3>
            <div class="row" style="display:flex; flex-wrap:wrap; gap:14px;">
                <?php foreach ($assinaturas as $a): ?>
                    <div style="flex:1 1 140px; text-align:center;">
                        <h5 style="font-size:12px; color:#8a8fa3; margin:0 0 8px;"><?= ucfirst(str_replace('_', ' ', $a->tipo)) ?></h5>
                        <div class="assinatura-box" style="border:1px solid #eee; background:#fff; padding:4px; border-radius:8px;">
                            <?php if (!empty($a->is_base64)): ?>
                                <img src="<?= $a->url_visualizacao ?>" alt="Assinatura" style="max-width:100%;">
                            <?php elseif (!empty($a->assinatura) && (file_exists($a->assinatura) || file_exists(FCPATH . $a->assinatura))): ?>
                                <img src="<?= base_url($a->assinatura) ?>" alt="Assinatura" style="max-width:100%;">
                            <?php else: ?>
                                <span style="color:#aaa; font-size:12px;">Indisponível</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="info-card">
        <h3><i class='bx bx-pen'></i> Coletar assinatura do solicitante</h3>

        <div class="asg-tabs">
            <div class="asg-tab active" data-pane="pane-local"><i class='bx bx-mobile'></i> Assinar aqui</div>
            <div class="asg-tab" data-pane="pane-link"><i class='bx bx-link'></i> Enviar link</div>
        </div>

        <!-- Pane: assinar no aparelho -->
        <div class="asg-pane active" id="pane-local">
            <input type="text" class="asg-input" id="asg-nome" placeholder="Nome do solicitante (opcional)" value="<?= html_escape($cliente->nomeCliente) ?>">
            <input type="text" class="asg-input" id="asg-doc" placeholder="Documento CPF/RG (opcional)">
            <p style="font-size:13px; color:var(--tec-muted); margin:0 0 8px;">
                <i class='bx bx-info-circle'></i> Entregue o aparelho ao solicitante para assinar abaixo.
            </p>
            <div class="asg-canvas-wrap">
                <canvas id="asg-canvas"></canvas>
            </div>
            <div style="display:flex; gap:8px; margin-top:12px;">
                <button type="button" class="btn-tec neutral" id="asg-limpar" style="flex:0 0 auto;">
                    <i class='bx bx-eraser'></i> Limpar
                </button>
                <button type="button" class="btn-tec success" id="asg-salvar" style="flex:1;">
                    <i class='bx bx-check'></i> Salvar assinatura
                </button>
            </div>
        </div>

        <!-- Pane: enviar link -->
        <div class="asg-pane" id="pane-link">
            <p style="font-size:13px; color:var(--tec-muted); margin:0 0 12px;">
                <i class='bx bx-info-circle'></i> Gere um link para o solicitante assinar no próprio celular.
            </p>

            <?php if (!$aceite_suportado): ?>
                <div class="asg-msg err" style="display:block;">Recurso de link não disponível neste sistema.</div>
            <?php else: ?>
                <button type="button" class="btn-tec primary block" id="asg-gerar-link">
                    <i class='bx bx-link-alt'></i> <?= $aceite_link ? 'Gerar novo link' : 'Gerar link de assinatura' ?>
                </button>

                <div id="asg-link-container" style="<?= $aceite_link ? '' : 'display:none;' ?>">
                    <div class="asg-link-box">
                        <span id="asg-link-text"><?= html_escape($aceite_link) ?></span>
                    </div>
                    <button type="button" class="btn-tec neutral block" id="asg-copiar" style="margin-top:10px;">
                        <i class='bx bx-copy'></i> Copiar link
                    </button>

                    <!-- Envio manual: o técnico digita o destino -->
                    <div style="margin-top:18px;">
                        <label style="display:block; font-size:13px; font-weight:700; color:#55596e; margin-bottom:6px;">
                            <i class='bx bxl-whatsapp'></i> Enviar por WhatsApp
                        </label>
                        <div style="display:flex; gap:8px;">
                            <input type="tel" class="asg-input" id="asg-wa-num" placeholder="Número com DDD" value="<?= html_escape($fone) ?>" style="margin-bottom:0; flex:1;">
                            <button type="button" class="btn-tec success" id="asg-whats" style="flex:0 0 auto;">
                                <i class='bx bxl-whatsapp'></i> Abrir
                            </button>
                        </div>
                    </div>

                    <div style="margin-top:12px;">
                        <label style="display:block; font-size:13px; font-weight:700; color:#55596e; margin-bottom:6px;">
                            <i class='bx bx-envelope'></i> Enviar por e-mail
                        </label>
                        <div style="display:flex; gap:8px;">
                            <input type="email" class="asg-input" id="asg-email" placeholder="email@exemplo.com" value="<?= html_escape($emailCliente) ?>" style="margin-bottom:0; flex:1;">
                            <button type="button" class="btn-tec primary" id="asg-enviar-email" style="flex:0 0 auto;">
                                <i class='bx bx-send'></i> Enviar
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="height:70px;"></div>
</div>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => 'os', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>

<script src="<?= base_url('assets/js/assinatura-canvas.js') ?>"></script>
<script src="<?= base_url('assets/js/csrf.js?v=3') ?>"></script>
<script>
(function () {
    var OS_ID = <?= (int) $os->idOs ?>;
    var BASE = '<?= site_url('tecnico') ?>/';
    var WA = '<?= $wa ?>';

    function msg(texto, ok) {
        var el = document.getElementById('asg-msg');
        el.textContent = texto;
        el.className = 'asg-msg ' + (ok ? 'ok' : 'err');
        el.style.display = 'block';
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // ---- Abas ------------------------------------------------------
    document.querySelectorAll('.asg-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.asg-tab').forEach(function (t) { t.classList.remove('active'); });
            document.querySelectorAll('.asg-pane').forEach(function (p) { p.classList.remove('active'); });
            tab.classList.add('active');
            document.getElementById(tab.dataset.pane).classList.add('active');
        });
    });

    // ---- Canvas de assinatura --------------------------------------
    var assinatura = null;
    // O AssinaturaManager cuida do dimensionamento interno do canvas.
    if (typeof AssinaturaManager !== 'undefined') {
        AssinaturaManager.criar('solicitante', 'asg-canvas');
        assinatura = AssinaturaManager.obter('solicitante');
    }

    document.getElementById('asg-limpar').addEventListener('click', function () {
        if (assinatura) assinatura.limpar();
    });

    document.getElementById('asg-salvar').addEventListener('click', function () {
        if (!assinatura || assinatura.estaVazio()) {
            msg('Peça para o solicitante assinar no quadro antes de salvar.', false);
            return;
        }
        var btn = this;
        btn.setAttribute('disabled', 'disabled');

        $.ajax({
            url: BASE + 'salvar_assinatura_solicitante',
            method: 'POST',
            dataType: 'json',
            data: {
                os_id: OS_ID,
                assinatura: assinatura.obterImagem(),
                nome: document.getElementById('asg-nome').value,
                documento: document.getElementById('asg-doc').value
            }
        }).done(function (res) {
            if (res && res.success) {
                msg(res.message || 'Assinatura registrada!', true);
                setTimeout(function () {
                    window.location.href = BASE + 'visualizar/' + OS_ID;
                }, 1200);
            } else {
                msg((res && res.message) || 'Erro ao salvar.', false);
                btn.removeAttribute('disabled');
            }
        }).fail(function () {
            msg('Erro de comunicação ao salvar a assinatura.', false);
            btn.removeAttribute('disabled');
        });
    });

    // ---- Link público ----------------------------------------------
    function linkAtual() {
        var el = document.getElementById('asg-link-text');
        return el ? el.textContent.trim() : '';
    }

    function mostrarLink(link) {
        document.getElementById('asg-link-text').textContent = link;
        document.getElementById('asg-link-container').style.display = 'block';
    }

    // WhatsApp: usa o número digitado manualmente (fallback: número do cliente).
    var whatsBtn = document.getElementById('asg-whats');
    if (whatsBtn) {
        whatsBtn.addEventListener('click', function () {
            var link = linkAtual();
            if (!link) { msg('Gere o link primeiro.', false); return; }
            var num = (document.getElementById('asg-wa-num').value || '').replace(/\D/g, '');
            if (num && num.length <= 11) { num = '55' + num; }
            if (!num) { msg('Informe um número de WhatsApp.', false); return; }
            var texto = 'Olá! Por favor, assine a confirmação do serviço neste link: ' + link;
            window.open('https://wa.me/' + num + '?text=' + encodeURIComponent(texto), '_blank');
        });
    }

    // E-mail: envia o link para o endereço digitado manualmente (via fila).
    var emailBtn = document.getElementById('asg-enviar-email');
    if (emailBtn) {
        emailBtn.addEventListener('click', function () {
            var email = (document.getElementById('asg-email').value || '').trim();
            if (!email) { msg('Informe um e-mail.', false); return; }
            var btn = this;
            btn.setAttribute('disabled', 'disabled');
            $.ajax({
                url: BASE + 'enviar_link_solicitante_email',
                method: 'POST',
                dataType: 'json',
                data: { os_id: OS_ID, email: email }
            }).done(function (res) {
                if (res && res.success) {
                    if (res.link) { mostrarLink(res.link); }
                    msg(res.message || 'E-mail enviado!', true);
                } else {
                    msg((res && res.message) || 'Erro ao enviar o e-mail.', false);
                }
            }).fail(function () {
                msg('Erro de comunicação ao enviar o e-mail.', false);
            }).always(function () {
                btn.removeAttribute('disabled');
            });
        });
    }

    var gerarBtn = document.getElementById('asg-gerar-link');
    if (gerarBtn) {
        gerarBtn.addEventListener('click', function () {
            var btn = this;
            btn.setAttribute('disabled', 'disabled');
            $.ajax({
                url: BASE + 'gerar_link_solicitante',
                method: 'POST',
                dataType: 'json',
                data: { os_id: OS_ID }
            }).done(function (res) {
                if (res && res.success) {
                    mostrarLink(res.link);
                    msg('Link gerado! Válido até ' + res.expira + '.', true);
                } else {
                    msg((res && res.message) || 'Erro ao gerar link.', false);
                }
            }).fail(function () {
                msg('Erro de comunicação ao gerar o link.', false);
            }).always(function () {
                btn.removeAttribute('disabled');
            });
        });
    }

    var copiarBtn = document.getElementById('asg-copiar');
    if (copiarBtn) {
        copiarBtn.addEventListener('click', function () {
            var texto = document.getElementById('asg-link-text').textContent.trim();
            if (navigator.clipboard) {
                navigator.clipboard.writeText(texto).then(function () { msg('Link copiado!', true); });
            } else {
                var tmp = document.createElement('textarea');
                tmp.value = texto; document.body.appendChild(tmp); tmp.select();
                document.execCommand('copy'); document.body.removeChild(tmp);
                msg('Link copiado!', true);
            }
        });
    }
})();
</script>
</body>
</html>
