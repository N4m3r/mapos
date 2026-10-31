<?php
// Variaveis: $tipo ('produto'|'servico'), $item (objeto|null)
$this->load->helper('classificacao_fiscal');
$cfg = $this->db->table_exists('configuracoes_nfe') ? $this->db->get_where('configuracoes_nfe', ['id' => 1])->row() : null;
$pad = classificacao_efetiva([], $cfg ?: [], $tipo);
$v = function ($k) use ($item) {
    $x = set_value($k, $item->$k ?? '');

    return html_escape((string) $x);
};
$ehServ = $tipo === 'servico';
$aberto = $v('ibscbs_cst') !== '' || ($ehServ && ($v('nbs') !== '' || $v('cindop') !== '')) || (! $ehServ && $v('origem') !== '');
?>
<div class="ui-secao">
    <details class="reforma-item"<?= $aberto ? ' open' : '' ?>>
        <summary class="ui-secao-titulo" style="cursor:pointer">Reforma tributária <small style="font-weight:normal;opacity:.8">(opcional — o padrão da empresa é usado se ficar vazio)</small></summary>
        <div class="ui-grade" style="margin-top:10px">
            <?php if (! $ehServ) : ?>
            <div class="control-group">
                <label for="origem" class="control-label">Origem da mercadoria</label>
                <div class="controls">
                    <select id="origem" name="origem">
                        <?php $o = $v('origem'); ?>
                        <option value="">Padrão (0 - Nacional)</option>
                        <?php foreach (['0' => 'Nacional', '1' => 'Estrangeira, importação direta', '2' => 'Estrangeira, adquirida no mercado interno', '3' => 'Nacional, importado acima de 40%', '4' => 'Nacional, processos básicos', '5' => 'Nacional, importado até 40%', '6' => 'Estrangeira, importação direta sem similar', '7' => 'Estrangeira, mercado interno sem similar', '8' => 'Nacional, importado acima de 70%'] as $k => $t) : ?>
                            <option value="<?= $k ?>"<?= $o === (string) $k ? ' selected' : '' ?>><?= $k ?> - <?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php else : ?>
            <div class="control-group">
                <label for="nbs" class="control-label">NBS <small>(9 dígitos)</small></label>
                <div class="controls"><input id="nbs" type="text" name="nbs" maxlength="9" value="<?= $v('nbs') ?>" placeholder="Código da Nomenclatura Brasileira de Serviços" /></div>
            </div>
            <div class="control-group">
                <label for="cindop" class="control-label">Indicador da operação <small>(cIndOp, 6 dígitos)</small></label>
                <div class="controls"><input id="cindop" type="text" name="cindop" maxlength="6" value="<?= $v('cindop') ?>" placeholder="Padrão da empresa: <?= html_escape($pad['cindop'] ?: 'não definido') ?>" /></div>
            </div>
            <?php endif; ?>
            <div class="control-group">
                <label for="ibscbs_cst" class="control-label">CST do IBS/CBS <small>(3 dígitos)</small></label>
                <div class="controls"><input id="ibscbs_cst" type="text" name="ibscbs_cst" maxlength="3" value="<?= $v('ibscbs_cst') ?>" placeholder="Padrão da empresa: <?= html_escape($pad['cst'] ?: 'não definido') ?>" /></div>
            </div>
            <div class="control-group">
                <label for="ibscbs_cclasstrib" class="control-label">Classificação tributária <small>(cClassTrib, 6 dígitos)</small></label>
                <div class="controls"><input id="ibscbs_cclasstrib" type="text" name="ibscbs_cclasstrib" maxlength="6" value="<?= $v('ibscbs_cclasstrib') ?>" placeholder="Padrão da empresa: <?= html_escape($pad['cclasstrib'] ?: 'não definido') ?>" /></div>
            </div>
            <?php if (! $ehServ && $this->db->field_exists('icms_cst', 'produtos')) : ?>
            <div class="control-group">
                <label for="icms_cst" class="control-label">CST do ICMS <small>(regime normal)</small></label>
                <div class="controls">
                    <select id="icms_cst" name="icms_cst">
                        <?php $ic = $v('icms_cst'); ?>
                        <option value="">Padrão da empresa</option>
                        <?php foreach (['00' => 'Tributada integralmente', '40' => 'Isenta', '41' => 'Não tributada', '50' => 'Suspensão', '60' => 'ICMS cobrado antes por ST'] as $k => $t) : ?>
                            <option value="<?= $k ?>"<?= $ic === (string) $k ? ' selected' : '' ?>><?= $k ?> - <?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label for="icms_aliquota" class="control-label">Alíquota de ICMS <small>(%)</small></label>
                <div class="controls"><input id="icms_aliquota" type="text" name="icms_aliquota" maxlength="6" value="<?= $v('icms_aliquota') ?>" placeholder="Vazio = padrão da empresa" /></div>
            </div>
            <?php endif; ?>
            <?php if (! $ehServ) : ?>
            <div class="control-group">
                <label for="p_red_ibscbs" class="control-label">Redução da alíquota <small>(%)</small></label>
                <div class="controls"><input id="p_red_ibscbs" type="text" name="p_red_ibscbs" maxlength="8" value="<?= $v('p_red_ibscbs') ?>" placeholder="Só se houver redução, de 0 a 100" /></div>
            </div>
            <?php endif; ?>
        </div>
        <p class="help-block" style="margin:6px 0 0">Preencha o CST e a classificação juntos (a classificação começa pelo CST). Se um deles faltar ou não bater, vale o padrão da empresa.</p>
        <?php if ($ehServ) : ?>
            <p style="margin:8px 0 0">
                <button type="button" id="btnSugerirReforma" class="btn btn-mini btn-info"><i class="bx bx-bulb"></i> Sugerir pelo código do serviço</button>
                <span id="msgSugerirReforma" style="margin-left:8px"></span>
            </p>
            <script nonce="<?= csp_nonce() ?>">
            (function () {
                var btn = document.getElementById('btnSugerirReforma'), msg = document.getElementById('msgSugerirReforma');
                btn.addEventListener('click', function () {
                    var cod = (document.getElementById('codigo_servico_municipio').value || '').replace(/\D/g, '');
                    if (cod.length < 4) { msg.textContent = 'Informe antes o código de tributação nacional do serviço.'; return; }
                    var item = cod.substr(0, 2) + '.' + cod.substr(2, 2);
                    msg.textContent = 'Buscando...';
                    fetch('<?= site_url('tributacao/sugerir') ?>/' + encodeURIComponent(item), {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
                        .then(function (r) { return r.json(); })
                        .then(function (j) {
                            if (!j.ok || !j.sugestoes.length) { msg.textContent = j.mensagem || 'Nenhuma sugestão para este código.'; return; }
                            var s = j.sugestoes[0];
                            if (s.nbs) document.getElementById('nbs').value = s.nbs;
                            if (s.cindop) document.getElementById('cindop').value = s.cindop;
                            if (s.cclasstrib) {
                                document.getElementById('ibscbs_cclasstrib').value = s.cclasstrib;
                                document.getElementById('ibscbs_cst').value = s.cclasstrib.substr(0, 3);
                            }
                            msg.textContent = 'Sugestão aplicada (' + j.sugestoes.length + ' encontrada(s)). Confira antes de salvar.';
                        })
                        .catch(function () { msg.textContent = 'Não foi possível buscar agora.'; });
                });
            })();
            </script>
        <?php endif; ?>
    </details>
</div>
