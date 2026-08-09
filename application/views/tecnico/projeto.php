<?php
$this->load->view('tecnico/_topo', [
    'titulo'       => $obra->nome,
    'header_icone' => 'bx-network-chart',
    'header_sub'   => $obra->nomeCliente ?: null,
    'voltar_url'   => site_url('tecnico/projetos'),
]);
$statusLabels = ['planejamento' => 'Planejamento', 'em_execucao' => 'Em execução', 'paralisada' => 'Paralisada', 'concluida' => 'Concluída', 'entregue' => 'Entregue', 'cancelada' => 'Cancelada'];
$dataBr = function ($d) {
    return (!empty($d) && $d !== '0000-00-00') ? date('d/m/Y', strtotime($d)) : '—';
};
$end = trim(($obra->logradouro ?? '') . ', ' . ($obra->numero ?? '') . ' - ' . ($obra->bairro ?? '') . ' - ' . ($obra->cidade ?? '') . '/' . ($obra->uf ?? ''), ' ,-/');

// Produtos/serviços distintos das OS do projeto (para seleção no registro)
$matDistinct = [];
foreach ($os_produtos as $mp) {
    if (empty($mp->produtos_id)) { continue; }
    $matDistinct[(int) $mp->produtos_id] = $mp->nome ?: 'Produto';
}
$servDistinct = [];
foreach ($os_servicos as $ms) {
    if (empty($ms->servicos_id)) { continue; }
    $servDistinct[(int) $ms->servicos_id] = $ms->nome ?: 'Serviço';
}
?>

<div class="tec-container">

    <?php if ($this->session->flashdata('success')): ?>
        <div class="info-card" style="border-left:4px solid var(--tec-success, #2ecc71); color:var(--tec-success, #2ecc71);"><i class='bx bx-check-circle'></i> <?= html_escape($this->session->flashdata('success')) ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="info-card" style="border-left:4px solid var(--tec-danger, #e74c3c); color:var(--tec-danger, #e74c3c);"><i class='bx bx-error-circle'></i> <?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <!-- Info do projeto -->
    <div class="info-card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
            <span class="badge-status pendente"><?= html_escape($statusLabels[$obra->status] ?? $obra->status) ?></span>
            <strong><?= number_format((float) $obra->percentual_concluido, 1, ',', '.') ?>% concluído</strong>
        </div>
        <div style="background:#e9ecef;border-radius:6px;height:8px;overflow:hidden;margin:8px 0;">
            <div style="background:#2b7;height:8px;width:<?= (float) $obra->percentual_concluido ?>%;"></div>
        </div>
        <div style="color:#666;font-size:13px;"><i class='bx bx-map'></i> <?= html_escape($end ?: '—') ?></div>
    </div>

    <!-- REGISTRO RÁPIDO (one-click) -->
    <h2 class="tec-section-title"><i class='bx bx-check-circle'></i> Registrar o que foi feito</h2>
    <form action="<?= site_url('tecnico/salvar_registro_projeto') ?>" method="post" class="info-card" id="formRegistro">
        <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
        <label style="font-weight:600;display:block;margin-bottom:6px;">O que você fez hoje? (<?= date('d/m/Y') ?>)</label>
        <textarea name="atividades" rows="4" placeholder="Descreva a atividade realizada... (opcional)" style="width:100%;border:1px solid #ccc;border-radius:8px;padding:10px;font-size:15px;"></textarea>

        <?php if (!empty($matDistinct)): ?>
            <details style="margin-top:10px;border:1px solid #eee;border-radius:8px;padding:6px 10px;">
                <summary style="font-weight:600;cursor:pointer;"><i class='bx bx-package'></i> Material utilizado (opcional)</summary>
                <p style="color:#888;font-size:12px;margin:6px 0;">Informe a quantidade usada — dá baixa no estoque.</p>
                <?php foreach ($matDistinct as $pid => $pnome): ?>
                    <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f2f2f2;">
                        <span style="flex:1;"><?= html_escape($pnome) ?></span>
                        <input type="hidden" name="material_nome[<?= $pid ?>]" value="<?= html_escape($pnome) ?>">
                        <input type="number" name="material[<?= $pid ?>]" min="0" step="0.01" placeholder="0" style="width:80px;border:1px solid #ccc;border-radius:6px;padding:6px;text-align:center;">
                    </div>
                <?php endforeach; ?>
            </details>
        <?php endif; ?>

        <?php if (!empty($servDistinct)): ?>
            <details style="margin-top:10px;border:1px solid #eee;border-radius:8px;padding:6px 10px;">
                <summary style="font-weight:600;cursor:pointer;"><i class='bx bx-list-check'></i> Serviços realizados (opcional)</summary>
                <p style="color:#888;font-size:12px;margin:6px 0;">Informe a quantidade realizada de cada serviço.</p>
                <?php foreach ($servDistinct as $sid => $snome): ?>
                    <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f2f2f2;">
                        <span style="flex:1;"><?= html_escape($snome) ?></span>
                        <input type="hidden" name="servico_nome[<?= $sid ?>]" value="<?= html_escape($snome) ?>">
                        <input type="number" name="servico[<?= $sid ?>]" min="0" step="0.01" placeholder="0" style="width:80px;border:1px solid #ccc;border-radius:6px;padding:6px;text-align:center;">
                    </div>
                <?php endforeach; ?>
            </details>
        <?php endif; ?>

        <label class="btn-tec ghost block" style="margin-top:10px;cursor:pointer;">
            <i class='bx bx-camera'></i> Adicionar fotos (opcional)
            <input type="file" accept="image/*" capture="environment" multiple id="regFotos" style="display:none;">
        </label>
        <div id="regFotosHidden"></div>
        <div id="regFotosPrev" style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;"></div>
        <button type="submit" class="btn-tec success block lg" style="margin-top:12px;">
            <i class='bx bx-save'></i> Registrar execução
        </button>
    </form>

    <!-- O QUE EXECUTAR -->
    <h2 class="tec-section-title"><i class='bx bx-list-check'></i> O que executar</h2>
    <?php if (!empty($os_servicos)): ?>
        <div class="info-card" style="padding:0;">
            <?php foreach ($os_servicos as $s): ?>
                <div style="padding:10px 12px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;gap:8px;">
                    <span><i class='bx bx-wrench'></i> <?= html_escape($s->nome ?: '—') ?></span>
                    <span style="color:#888;">OS #<?= (int) $s->os_id ?> · <?= number_format((float) $s->quantidade, 0, ',', '.') ?>x</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state" style="padding:16px;"><i class='bx bx-list-check'></i><p>Nenhum serviço nas OS deste projeto.</p></div>
    <?php endif; ?>

    <!-- MATERIAL A UTILIZAR -->
    <h2 class="tec-section-title"><i class='bx bx-package'></i> Material a utilizar</h2>
    <?php if (!empty($os_produtos)): ?>
        <div class="info-card" style="padding:0;">
            <?php foreach ($os_produtos as $p): ?>
                <div style="padding:10px 12px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;gap:8px;">
                    <span><i class='bx bx-box'></i> <?= html_escape($p->nome ?: '—') ?></span>
                    <span style="color:#888;">OS #<?= (int) $p->os_id ?> · <?= number_format((float) $p->quantidade, 0, ',', '.') ?>x</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state" style="padding:16px;"><i class='bx bx-package'></i><p>Nenhum material nas OS deste projeto.</p></div>
    <?php endif; ?>

    <!-- ORDENS DE SERVIÇO -->
    <h2 class="tec-section-title"><i class='bx bx-clipboard'></i> Ordens de Serviço
        <?php if (!empty($os_vinculadas)): ?><span class="count"><?= count($os_vinculadas) ?></span><?php endif; ?>
    </h2>
    <?php if (!empty($os_vinculadas)): ?>
        <?php foreach ($os_vinculadas as $o): ?>
            <a href="<?= site_url('tecnico/visualizar/' . $o->idOs) ?>" style="text-decoration:none;color:inherit;display:block;">
                <div class="os-card">
                    <div class="os-head">
                        <span class="os-num">#OS <?= sprintf('%04d', $o->idOs) ?></span>
                        <span class="badge-status pendente"><?= html_escape($o->status ?: '—') ?></span>
                    </div>
                    <div class="os-cliente"><i class='bx bx-user'></i> <?= html_escape($o->nomeCliente ?: '—') ?></div>
                    <div class="os-foot">
                        <span class="os-meta"><span><i class='bx bx-wrench'></i> <?= html_escape($o->tecnico ?: 'Sem técnico') ?></span></span>
                        <span class="btn-tec primary">Abrir <i class='bx bx-right-arrow-alt'></i></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state" style="padding:16px;"><i class='bx bx-clipboard'></i><p>Nenhuma OS vinculada a este projeto.</p></div>
    <?php endif; ?>

    <!-- CONSUMO REGISTRADO -->
    <?php
    $consMat = array_filter($consumo, function ($c) { return $c->tipo === 'material'; });
    $consServ = array_filter($consumo, function ($c) { return $c->tipo === 'servico'; });
    ?>
    <?php if (!empty($consMat) || !empty($consServ)): ?>
        <h2 class="tec-section-title"><i class='bx bx-task'></i> Já registrado</h2>
        <?php if (!empty($consMat)): ?>
            <div class="info-card" style="padding:0;">
                <div style="padding:8px 12px;font-weight:600;background:#f7f7f7;"><i class='bx bx-package'></i> Material utilizado</div>
                <?php foreach ($consMat as $c): ?>
                    <div style="padding:8px 12px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;gap:8px;">
                        <span><?= html_escape($c->descricao ?: 'Produto') ?></span>
                        <span style="color:#888;"><?= number_format((float) $c->quantidade, 2, ',', '.') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($consServ)): ?>
            <div class="info-card" style="padding:0;">
                <div style="padding:8px 12px;font-weight:600;background:#f7f7f7;"><i class='bx bx-list-check'></i> Serviços realizados</div>
                <?php foreach ($consServ as $c): ?>
                    <div style="padding:8px 12px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;gap:8px;">
                        <span><i class='bx bx-check' style="color:#2b7;"></i> <?= html_escape($c->descricao ?: 'Serviço') ?></span>
                        <span style="color:#888;"><?= number_format((float) $c->quantidade, 2, ',', '.') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- DIÁRIO / MEUS REGISTROS -->
    <h2 class="tec-section-title"><i class='bx bx-book'></i> Registros (Diário de Obra)</h2>
    <?php if (!empty($rdos)): ?>
        <?php foreach ($rdos as $r): ?>
            <div class="info-card">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                    <strong>#<?= (int) $r->numero ?> · <?= $dataBr($r->data) ?></strong>
                    <a href="<?= site_url('obras/imprimirRdo/' . $r->idRdo) ?>" target="_blank" class="btn-tec ghost" style="padding:6px 10px;"><i class='bx bx-printer'></i></a>
                </div>
                <div style="color:#555;margin-top:4px;"><?= html_escape($r->atividades ?: '—') ?></div>
                <small style="color:#999;"><i class='bx bx-user'></i> <?= html_escape($r->responsavel ?: '—') ?></small>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state" style="padding:16px;"><i class='bx bx-book'></i><p>Nenhum registro ainda. Use o botão acima para registrar.</p></div>
    <?php endif; ?>
</div>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => 'projetos', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>
<script src="<?= base_url('assets/js/csrf.js?v=3') ?>"></script>
<script>
    (function () {
        var input = document.getElementById('regFotos');
        if (!input) return;
        input.addEventListener('change', function () {
            var hidden = document.getElementById('regFotosHidden');
            var prev = document.getElementById('regFotosPrev');
            hidden.innerHTML = '';
            prev.innerHTML = '';
            Array.prototype.forEach.call(this.files, function (file) {
                var reader = new FileReader();
                reader.onload = function (ev) {
                    var h = document.createElement('input');
                    h.type = 'hidden'; h.name = 'fotos[]'; h.value = ev.target.result;
                    hidden.appendChild(h);
                    var img = document.createElement('img');
                    img.src = ev.target.result;
                    img.style.cssText = 'width:70px;height:70px;object-fit:cover;border-radius:8px;border:1px solid #ccc;';
                    prev.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    })();
</script>
</body>
</html>
