<?php
/**
 * Selo "Projeto > Etapa" da OS (ADR-015) + acoes de vincular/desvincular.
 * Variaveis: $vinculoProjeto, $podeVerProjeto (link), $podeVincularProjeto, $mostrarAcoes, $projetosVincular, $osIdProjeto.
 * Conversao em projeto (fase 3): $podeCriarProjeto, $osElegivelConversao, $tiposProjeto, $osNomeCliente.
 */
$tiposVinculo = ['origem' => 'Origem do projeto', 'execucao' => 'Execução', 'adicional' => 'Adicional', 'corretiva' => 'Corretiva'];
$telaComAcoes = ! empty($mostrarAcoes);
$mostrarAcoes = $telaComAcoes && ! empty($podeVincularProjeto);
$mostrarConverter = $telaComAcoes && empty($vinculoProjeto) && ! empty($podeCriarProjeto) && ! empty($osElegivelConversao);
if (! empty($vinculoProjeto) || $mostrarAcoes || $mostrarConverter):
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
                <?php if (! empty($v->fatura_no_projeto)): ?> &middot;
                    <?php if (! empty($podeVerProjeto)): ?>
                        <a href="<?= site_url('obras/visualizar/' . (int) $v->idObra) ?>#financeiro" class="label label-info" style="color:#fff">Cobrada pela medição do projeto</a>
                    <?php else: ?>
                        <span class="label label-info">Cobrada pela medição do projeto</span>
                    <?php endif; ?>
                <?php endif; ?>
            </small>
        </div>
        <?php if ($mostrarAcoes): ?>
            <a href="#" id="btnDesvincularProjeto" class="button btn btn-mini btn-danger" title="Desvincular do projeto"><span class="button__icon"><i class="bx bx-unlink"></i></span><span class="button__text2">Desvincular</span></a>
        <?php endif; ?>
    <?php else: ?>
        <i class="bx bx-buildings" style="font-size:20px;color:#8a8fa3"></i>
        <div style="flex:1;min-width:220px"><strong>Projeto:</strong> <span class="muted">esta OS não está vinculada a um projeto.</span></div>
        <?php if ($mostrarAcoes): ?>
        <a href="#modal-vincular-projeto" data-toggle="modal" class="button btn btn-mini btn-success"><span class="button__icon"><i class="bx bx-link"></i></span><span class="button__text2">Vincular a projeto</span></a>
        <?php endif; ?>
        <?php if ($mostrarConverter): ?>
        <a href="#modal-converter-projeto" id="btnConverterProjeto" data-toggle="modal" class="button btn btn-mini btn-primary"><span class="button__icon"><i class="bx bx-buildings"></i></span><span class="button__text2">Converter em projeto</span></a>
        <?php endif; ?>
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
    // Envia a requisicao; em falha de rede preserva o form, reabilita o botao e oferece "Tentar de novo".
    function enviar(url, dados, msgErro, $btn) {
        if ($btn) { $btn.prop('disabled', true); }
        $.post(url, dados, null, 'json')
            .done(function (r) {
                if (r && r.success) { location.reload(); return; }
                if ($btn) { $btn.prop('disabled', false); }
                erro((r && r.message) || msgErro);
            })
            .fail(function () {
                if ($btn) { $btn.prop('disabled', false); }
                var msg = 'Falha de comunicação. Os dados preenchidos foram mantidos.';
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Não foi possível', text: msg, showCancelButton: true,
                        confirmButtonText: 'Tentar de novo', cancelButtonText: 'Cancelar' })
                        .then(function (res) { if (res && res.isConfirmed) { enviar(url, dados, msgErro, $btn); } });
                } else if (confirm(msg + ' Tentar de novo?')) {
                    enviar(url, dados, msgErro, $btn);
                }
            });
    }
    $('#formVincularProjeto').on('submit', function (e) {
        e.preventDefault();
        enviar('<?= site_url('os/vincularProjetoAction') ?>', $(this).serialize(), 'Erro ao vincular.', $(this).find('button[type=submit]'));
    });
    $('#btnDesvincularProjeto').on('click', function (e) {
        e.preventDefault();
        var texto = 'Desvincular esta OS do projeto? A OS não é excluída.';
        var executar = function () {
            enviar('<?= site_url('os/desvincularProjetoAction') ?>', { os_id: <?= (int) $osIdProjeto ?> }, 'Erro ao desvincular.', null);
        };
        if (window.Swal) {
            Swal.fire({ icon: 'warning', title: 'Desvincular da OS?', text: texto, showCancelButton: true,
                confirmButtonText: 'Sim, desvincular', cancelButtonText: 'Cancelar' })
                .then(function (res) { if (res && res.isConfirmed) { executar(); } });
        } else if (confirm(texto)) {
            executar();
        }
    });
});
</script>
<?php endif; ?>

<?php if ($mostrarConverter): ?>
<div id="modal-converter-projeto" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form id="formConverterProjeto" method="post" action="<?= site_url('obras/converterOs') ?>">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Converter OS #<?= (int) $osIdProjeto ?> em projeto</h5></div>
        <div class="modal-body">
            <input type="hidden" name="os_id" value="<?= (int) $osIdProjeto ?>">
            <div class="span12" style="margin-left:0"><label for="cp_nome">Nome do projeto</label>
                <input type="text" name="nome" id="cp_nome" class="span12" maxlength="150" value="<?= html_escape('Projeto - OS #' . (int) $osIdProjeto . ' - ' . ($osNomeCliente ?? '')) ?>">
            </div>
            <div class="span12" style="margin-left:0"><label for="cp_tipo">Tipo de projeto</label>
                <input type="text" name="tipo_obra" id="cp_tipo" class="span12" list="cp_tipos" maxlength="60" placeholder="Rede estruturada, CFTV IP...">
                <datalist id="cp_tipos">
                    <?php foreach ((array) $tiposProjeto as $tp): ?><option value="<?= html_escape($tp) ?>"><?php endforeach; ?>
                </datalist>
            </div>
            <div class="span6" style="margin-left:0"><label for="cp_ini">Início previsto</label><input type="date" name="data_inicio_prevista" id="cp_ini" class="span12"></div>
            <div class="span6"><label for="cp_fim">Fim previsto</label><input type="date" name="data_fim_prevista" id="cp_fim" class="span12"></div>
            <div class="span12" style="margin-left:0">
                <label>Etapas</label>
                <label class="radio"><input type="radio" name="etapas" value="servicos" checked> Uma etapa por serviço da OS (peso pelo valor)</label>
                <label class="radio"><input type="radio" name="etapas" value="padrao"> Etapas padrão do tipo (itens na primeira etapa)</label>
                <div id="cp_etapas" class="muted" style="margin:6px 0"><small>Carregando prévia...</small></div>
            </div>
            <div class="span12" style="margin-left:0">
                <label class="checkbox"><input type="checkbox" name="gerar_pontos" value="1" id="cp_gerar"> Gerar pontos dos produtos (câmeras, leitores, pontos de rede...)</label>
                <div id="cp_pontos" style="display:none;margin-top:6px"></div>
            </div>
            <div class="span12" style="margin-left:0"><small class="muted">O status da OS e o estoque não são alterados. O faturamento passa a ser pelo projeto.</small></div>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button type="submit" class="button btn btn-primary"><span class="button__text2">Criar projeto</span></button></div>
    </form>
</div>
<script nonce="<?= csp_nonce() ?>">
$(function () {
    function esc(t) { return $('<div>').text(t == null ? '' : String(t)).html(); }
    var previa = null;
    function pesos() {
        var $d = $('#cp_etapas');
        if (!previa || !previa.etapas.length) { $d.html('<small>Sem serviços na OS.</small>'); return; }
        if ($('input[name=etapas]:checked').val() === 'padrao') { $d.html('<small>As etapas padrão são criadas conforme o tipo escolhido.</small>'); return; }
        var h = '<table class="table table-condensed"><thead><tr><th>Etapa</th><th style="text-align:right">Peso</th></tr></thead><tbody>';
        $.each(previa.etapas, function (i, e) {
            h += '<tr><td>' + esc(e.codigo_eap + ' ' + e.nome) + '</td><td style="text-align:right">' + esc(Number(e.peso_percentual).toFixed(2)) + '%</td></tr>';
        });
        $d.html(h + '</tbody></table>');
    }
    function pontos() {
        var $p = $('#cp_pontos');
        if (!previa) { return; }
        var h = '<table class="table table-condensed"><thead><tr><th>Produto</th><th>Tipo de ponto</th><th style="text-align:right">Qtd</th></tr></thead><tbody>';
        $.each(previa.produtos, function (i, p) {
            var sel = '<select name="pontos_por_produto[' + Number(p.produtos_id) + ']" class="span12" aria-label="Tipo de ponto para ' + esc(p.descricao) + '"><option value="nenhum">— não gerar ponto —</option>';
            $.each(previa.tipos, function (j, t) {
                sel += '<option value="' + esc(t.id) + '"' + (t.id === p.tipo ? ' selected' : '') + '>' + esc(t.rotulo) + '</option>';
            });
            h += '<tr><td>' + esc(p.descricao) + '</td><td>' + sel + '</select></td><td style="text-align:right">' + esc(p.quantidade) + '</td></tr>';
        });
        $p.html(previa.produtos.length ? h + '</tbody></table>' : '<small>A OS não tem produtos.</small>');
    }
    $('#btnConverterProjeto').on('click', function () {
        if (previa) { return; }
        $.getJSON('<?= site_url('obras/previaConversaoOs') ?>', { os_id: <?= (int) $osIdProjeto ?> }, function (r) {
            if (r && r.ok) { previa = r; pesos(); pontos(); } else { $('#cp_etapas').text((r && r.erro) || 'Não foi possível carregar a prévia.'); }
        }).fail(function () { $('#cp_etapas').text('Falha ao carregar a prévia.'); });
    });
    $('input[name=etapas]').on('change', pesos);
    $('#cp_gerar').on('change', function () {
        $('#cp_pontos').toggle(this.checked).find('select').prop('disabled', !this.checked);
    });
    $('#formConverterProjeto').on('submit', function () {
        if (!$('#cp_gerar').is(':checked')) { $('#cp_pontos select').prop('disabled', true); }
    });
});
</script>
<?php endif; ?>
<?php endif; ?>
