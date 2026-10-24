<?php
$fmt = fn ($v) => number_format((float) $v, 2, ',', '.');

$debTotal = (float) $debito['total'];
$credTotal = (float) $credito['total'];
$saldo = $debTotal - $credTotal; // >0 a recolher; <0 crédito a transportar
?>
<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="fas fa-calculator"></i></span>
                <h5>Apuração de IBS/CBS — <?= html_escape($competencia) ?></h5>
            </div>
            <div class="widget-content">

                <div style="display:flex;gap:10px;align-items:flex-end;margin-bottom:14px">
                    <form action="<?= site_url('entradas/apuracao') ?>" method="get" style="margin:0">
                        <label style="font-size:12px;color:#666;display:block">Competência</label>
                        <input type="month" name="competencia" value="<?= html_escape($competencia) ?>" onchange="this.form.submit()" />
                    </form>
                    <a href="<?= site_url('entradas?competencia=' . urlencode($competencia)) ?>" class="button btn btn-warning">
                        <span class="button__icon"><i class="bx bx-arrow-back"></i></span><span class="button__text2">Voltar às entradas</span>
                    </a>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:16px">
                    <!-- Débito -->
                    <div style="border:1px solid #e6cccc;border-radius:8px;padding:14px;background:#fbf4f4">
                        <div style="color:#a33;font-weight:bold;margin-bottom:6px">DÉBITO (saídas)</div>
                        <div>IBS: R$ <?= $fmt($debito['v_ibs']) ?></div>
                        <div>CBS: R$ <?= $fmt($debito['v_cbs']) ?></div>
                        <div style="margin-top:6px;font-size:18px;font-weight:bold">R$ <?= $fmt($debTotal) ?></div>
                        <small style="color:#999"><?= (int) ($debito['qtd'] ?? 0) ?> NF-e autorizada(s) no mês</small>
                    </div>
                    <!-- Crédito -->
                    <div style="border:1px solid #cce0cc;border-radius:8px;padding:14px;background:#f4fbf4">
                        <div style="color:#3a3;font-weight:bold;margin-bottom:6px">CRÉDITO (entradas)</div>
                        <div>IBS: R$ <?= $fmt($credito['v_ibs']) ?></div>
                        <div>CBS: R$ <?= $fmt($credito['v_cbs']) ?></div>
                        <?php if ($credito['v_cred_pres_zfm'] > 0) { ?><div>Créd. presumido ZFM: R$ <?= $fmt($credito['v_cred_pres_zfm']) ?></div><?php } ?>
                        <div style="margin-top:6px;font-size:18px;font-weight:bold">R$ <?= $fmt($credTotal) ?></div>
                    </div>
                    <!-- Saldo -->
                    <div style="border:2px solid <?= $saldo > 0 ? '#a33' : '#3a3' ?>;border-radius:8px;padding:14px;background:#fafafa">
                        <div style="font-weight:bold;margin-bottom:6px">SALDO</div>
                        <?php if ($saldo > 0) { ?>
                            <div style="color:#a33">A recolher</div>
                            <div style="font-size:22px;font-weight:bold;color:#a33">R$ <?= $fmt($saldo) ?></div>
                        <?php } else { ?>
                            <div style="color:#3a3">Crédito a transportar</div>
                            <div style="font-size:22px;font-weight:bold;color:#3a3">R$ <?= $fmt(abs($saldo)) ?></div>
                        <?php } ?>
                        <small style="color:#999">Débito − Crédito</small>
                    </div>
                </div>

                <div class="alert alert-info">
                    <strong>Como ler:</strong> o débito é o IBS/CBS destacado nas suas NF-e autorizadas no mês
                    (lido do XML). O crédito é o IBS/CBS das notas de compra que você marcou para aproveitar.
                    Saldo positivo = imposto a recolher; negativo = crédito que passa para o próximo período.
                    <br><small>Valores conforme registrado; confirme a apuração oficial com seu contador. A NFS-e (serviços) ainda não entra no débito automático.</small>
                </div>

            </div>
        </div>
    </div>
</div>
