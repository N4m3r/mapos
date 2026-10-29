<?php
/**
 * Selo "Projeto > Etapa" da OS (ADR-015) + acoes de vincular/desvincular.
 * Variaveis: $vinculoProjeto, $podeVerProjeto (link), $podeVincularProjeto, $mostrarAcoes, $projetosVincular, $osIdProjeto.
 */
$tiposVinculo = ['origem' => 'Origem do projeto', 'execucao' => 'Execução', 'adicional' => 'Adicional', 'corretiva' => 'Corretiva'];
$mostrarAcoes = ! empty($mostrarAcoes) && ! empty($podeVincularProjeto);
if (! empty($vinculoProjeto) || $mostrarAcoes):
?>
<div class="os-projeto-selo" style="margin:10px 0;padding:10px 14px;border:1px solid #d9e2ef;border-left:4px solid #436eee;border-radius:6px;background:#f6f8fd;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <?php if (! empty($vinculoProjeto)): $v = $vinculoProjeto; ?>
        <i class="bx bx-buildings" style="font-size:20px;color:#436eee"></i>
        <div style="flex:1;min-width:220px">
            <strong>Projeto:</strong>
            <?php if (! empty($podeVerProjeto)): ?>
                <a href="<?= site_url('obras/visualizar/' . (int) $v->idObra) ?>"><?= html_escape($v->obra_nome) ?></a>
            <?php else: ?>
                <?= html_escape($v->obra_nome) ?>
            <?php endif; ?>
            <?php if (! empty($v->etapa_nome)): ?>
                &rsaquo; Etapa <?= html_escape(trim(($v->codigo_eap ?: '') . ' ' . $v->etapa_nome)) ?>
            <?php endif; ?>
            <br>
            <small>
                Vínculo: <?= html_escape($tiposVinculo[$v->tipo_vinculo] ?? $v->tipo_vinculo) ?>
                <?php if (! empty($v->fatura_no_projeto)): ?> &middot; <span class="label label-info">fatura pelo projeto</span><?php endif; ?>
            </small>
        </div>
        <?php if ($mostrarAcoes): ?>
            <a href="#" id="btnDesvincularProjeto" class="button btn btn-mini btn-danger" title="Desvincular do projeto"><span class="button__icon"><i class="bx bx-unlink"></i></span><span class="button__text2">Desvincular</span></a>
        <?php endif; ?>
    <?php else: ?>
        <i class="bx bx-buildings" style="font-size:20px;color:#8a8fa3"></i>
        <div style="flex:1;min-width:220px"><strong>Projeto:</strong> <span class="muted">esta OS não está vinculada a um projeto.</span></div>
        <a href="#modal-vincular-projeto" data-toggle="modal" class="button btn btn-mini btn-success"><span class="button__icon"><i class="bx bx-link"></i></span><span class="button__text2">Vincular a projeto</span></a>
    <?php endif; ?>
</div>

<?php if ($mostrarAcoes): ?>
<?php if (empty($vinculoProjeto)): ?>
<div id="modal-vincular-projeto" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form id="formVincularProjeto" method="post" action="#">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Vincular OS a um projeto</h5></div>
        <div class="modal-body">
            <input type="hidden" name="os_id" value="<?= (int) $osIdProjeto ?>">
            <div class="span12" style="margin-left:0"><label for="vp_obra">Projeto do cliente *</label>
                <select name="obra_id" id="vp_obra" class="span12" required>
                    <option value=""><?= $projetosVincular ? 'Selecione o projeto...' : 'Nenhum projeto em aberto para este cliente' ?></option>
                    <?php foreach ($projetosVincular as $pj): ?>
                        <option value="<?= (int) $pj->idObra ?>"><?= html_escape($pj->nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="span12" style="margin-left:0"><label for="vp_etapa">Etapa do cronograma (opcional)</label>
                <select name="etapa_id" id="vp_etapa" class="span12"><option value="">— sem etapa —</option></select>
            </div>
            <div class="span12" style="margin-left:0"><label for="vp_tipo">Tipo de vínculo</label>
                <select name="tipo_vinculo" id="vp_tipo" class="span12">
                    <?php foreach ($tiposVinculo as $k => $nome): ?>
                        <option value="<?= $k ?>" <?= $k === 'execucao' ? 'selected' : '' ?>><?= html_escape($nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="span12" style="margin-left:0">
                <label class="checkbox"><input type="checkbox" name="fatura_no_projeto" value="1" checked> Faturar pelo projeto</label>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button type="submit" class="button btn btn-success"><span class="button__text2">Vincular</span></button></div>
    </form>
</div>
<?php endif; ?>
<script nonce="<?= csp_nonce() ?>">
$(function () {
    function erro(msg) {
        if (window.Swal) { Swal.fire({ icon: 'error', title: 'Não foi possível', text: msg }); } else { alert(msg); }
    }
    $('#vp_obra').on('change', function () {
        var $et = $('#vp_etapa').html('<option value="">— sem etapa —</option>');
        if (!this.value) { return; }
        $.getJSON('<?= site_url('os/etapasProjetoAction') ?>', { obra_id: this.value }, function (r) {
            if (r && r.success) {
                $.each(r.etapas, function (i, e) { $et.append($('<option>').val(e.id).text(e.nome)); });
            }
        });
    });
    $('#formVincularProjeto').on('submit', function (e) {
        e.preventDefault();
        $.post('<?= site_url('os/vincularProjetoAction') ?>', $(this).serialize(), null, 'json')
            .done(function (r) { if (r && r.success) { location.reload(); } else { erro((r && r.message) || 'Erro ao vincular.'); } })
            .fail(function () { erro('Falha de comunicação. Tente novamente.'); });
    });
    $('#btnDesvincularProjeto').on('click', function (e) {
        e.preventDefault();
        if (!confirm('Desvincular esta OS do projeto? A OS não é excluída.')) { return; }
        $.post('<?= site_url('os/desvincularProjetoAction') ?>', { os_id: <?= (int) $osIdProjeto ?> }, null, 'json')
            .done(function (r) { if (r && r.success) { location.reload(); } else { erro((r && r.message) || 'Erro ao desvincular.'); } })
            .fail(function () { erro('Falha de comunicação. Tente novamente.'); });
    });
});
</script>
<?php endif; ?>
<?php endif; ?>
