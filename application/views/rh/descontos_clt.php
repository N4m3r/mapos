<?php
$this->load->view('rh/_subnav', ['ativo' => 'descontos']);
$cfg = $cfg ?? [];
$g = function ($k, $d = '') use ($cfg) {
    return htmlspecialchars(isset($cfg[$k]) && $cfg[$k] !== null && $cfg[$k] !== '' ? $cfg[$k] : $d);
};
$chk = function ($k) use ($cfg) {
    return ! empty($cfg[$k]) && $cfg[$k] !== '0';
};
?>
<div class="new122">
    <div class="widget-title" style="margin:0 0 10px">
        <span class="icon"><i class="fas fa-balance-scale"></i></span>
        <h5>Descontos CLT e Horas Extras</h5>
    </div>
    <p style="color:#6b7280;font-size:13px;margin-bottom:12px">
        Configuração conforme legislação trabalhista (CLT). Os valores e tabelas são <strong>editáveis</strong>
        — atualize anualmente as faixas de INSS/IRRF. Cálculo gerencial; o holerite contábil prevalece quando anexado.
    </p>

    <form method="post" action="<?= site_url('rh/descontosClt') ?>">
        <input type="hidden" name="salvar" value="1">

        <div class="widget-box">
            <div class="widget-title"><span class="icon"><i class="fas fa-percentage"></i></span><h5>Encargos e descontos legais</h5></div>
            <div class="widget-content">
                <div class="row-fluid">
                    <div class="span4">
                        <label style="font-weight:normal"><input type="checkbox" name="rh_clt_calcular_inss" value="1" <?= $chk('rh_clt_calcular_inss') ? 'checked' : '' ?>> Calcular INSS (progressivo)</label>
                    </div>
                    <div class="span4">
                        <label style="font-weight:normal"><input type="checkbox" name="rh_clt_calcular_irrf" value="1" <?= $chk('rh_clt_calcular_irrf') ? 'checked' : '' ?>> Calcular IRRF</label>
                    </div>
                    <div class="span4">
                        <label style="font-weight:normal"><input type="checkbox" name="rh_clt_mostrar_fgts" value="1" <?= $chk('rh_clt_mostrar_fgts') ? 'checked' : '' ?>> Exibir FGTS (informativo, 8%)</label>
                    </div>
                </div>
                <div class="row-fluid" style="margin-top:8px">
                    <div class="span3"><label>Alíquota FGTS (%)</label>
                        <input type="text" name="rh_clt_fgts_aliquota" class="span12" value="<?= $g('rh_clt_fgts_aliquota', '8') ?>">
                    </div>
                    <div class="span3"><label>Dedução por dependente IRRF (R$)</label>
                        <input type="text" name="rh_clt_dependente_deducao" class="span12" value="<?= $g('rh_clt_dependente_deducao', '189.59') ?>">
                    </div>
                    <div class="span3"><label>Outras deduções fixas (R$)</label>
                        <input type="text" name="rh_clt_outras_deducoes" class="span12" value="<?= $g('rh_clt_outras_deducoes', '0') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="widget-box">
            <div class="widget-title"><span class="icon"><i class="fas fa-bus"></i></span><h5>Vale-transporte (Dec. 95.247/87 — máx. 6%)</h5></div>
            <div class="widget-content">
                <label style="font-weight:normal"><input type="checkbox" name="rh_clt_vt_ativo" value="1" <?= $chk('rh_clt_vt_ativo') ? 'checked' : '' ?>> Aplicar desconto de vale-transporte</label>
                <div class="row-fluid" style="margin-top:8px">
                    <div class="span3"><label>Percentual do salário (máx. 6%)</label>
                        <input type="text" name="rh_clt_vt_percentual" class="span12" value="<?= $g('rh_clt_vt_percentual', '6') ?>">
                    </div>
                    <div class="span3"><label>Ou valor fixo (R$) <small>se &gt; 0, ignora %</small></label>
                        <input type="text" name="rh_clt_vt_valor_fixo" class="span12" value="<?= $g('rh_clt_vt_valor_fixo', '0') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="widget-box">
            <div class="widget-title"><span class="icon"><i class="fas fa-clock"></i></span><h5>Horas extras (CLT art. 59 — mín. 50%)</h5></div>
            <div class="widget-content">
                <label style="font-weight:normal"><input type="checkbox" name="rh_he_requer_aprovacao" value="1" <?= $chk('rh_he_requer_aprovacao') ? 'checked' : '' ?>>
                    Horas extras só entram no pagamento após aprovação do administrativo</label>
                <p style="font-size:12px;color:#9ca3af;margin:6px 0 10px">
                    Ao gerar extras no espelho ou lançar manualmente, o valor fica <strong>pendente</strong> até aprovação em Lançamentos.
                </p>
                <div class="row-fluid">
                    <div class="span3"><label>Adicional dia útil (%)</label>
                        <input type="text" name="rh_he_percentual_50" class="span12" value="<?= $g('rh_he_percentual_50', '50') ?>">
                    </div>
                    <div class="span3"><label>Adicional folga/domingo (%)</label>
                        <input type="text" name="rh_he_percentual_100" class="span12" value="<?= $g('rh_he_percentual_100', '100') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="widget-box">
            <div class="widget-title"><span class="icon"><i class="fas fa-table"></i></span><h5>Tabelas INSS e IRRF (JSON)</h5></div>
            <div class="widget-content">
                <div class="row-fluid">
                    <div class="span6">
                        <label>Tabela INSS (faixas progressivas)</label>
                        <textarea name="rh_clt_inss_tabela" rows="8" class="span12" style="font-family:monospace;font-size:12px"><?= $g('rh_clt_inss_tabela') ?></textarea>
                        <small style="color:#888">Formato: [{"ate":1518,"aliquota":7.5}, ...]</small>
                    </div>
                    <div class="span6">
                        <label>Tabela IRRF</label>
                        <textarea name="rh_clt_irrf_tabela" rows="8" class="span12" style="font-family:monospace;font-size:12px"><?= $g('rh_clt_irrf_tabela') ?></textarea>
                        <small style="color:#888">Formato: [{"ate":2428.80,"aliquota":0,"deducao":0}, ...]</small>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="button btn btn-success">
            <span class="button__icon"><i class='bx bx-save'></i></span>
            <span class="button__text2"> Salvar configurações</span>
        </button>
    </form>
</div>
