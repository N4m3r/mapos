<?php
$this->load->view('colaborador/_topo', [
    'titulo' => 'Holerite',
    'header_icone' => 'bx-receipt',
    'header_sub' => 'Competência ' . date('m/Y', strtotime($competencia . '-01')),
    'voltar_url' => site_url('colaborador'),
]);
$labelTipo = [
    'hora_extra'=>'Horas extras','adicional'=>'Adicional','comissao'=>'Comissão','bonus'=>'Bônus',
    'adiantamento'=>'Adiantamento','desconto'=>'Desconto','falta'=>'Falta','vale'=>'Vale',
    'inss'=>'INSS','irrf'=>'IRRF','vale_transporte'=>'Vale-transporte','salario'=>'Salário base',
];
?>
<div class="ponto-wrap">
    <input type="month" id="competencia" value="<?= $competencia ?>" class="span12" style="width:100%;margin-bottom:12px">

    <?php if (! empty($holerite) && ! empty($holerite->arquivo_base64)): ?>
        <a href="<?= site_url('colaborador/baixarHolerite/'.$competencia) ?>" target="_blank" class="btn-bater" style="text-align:center;text-decoration:none;margin-bottom:12px;display:block">
            <i class='bx bx-download'></i> Ver / baixar holerite (PDF)
        </a>
        <?php if (! empty($holerite->liberado_em)): ?>
            <div style="font-size:12px;color:#065f46;text-align:center;margin-bottom:10px">
                Liberado em <?= date('d/m/Y H:i', strtotime($holerite->liberado_em)) ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="rh-card" style="margin-bottom:12px;text-align:center;color:#6b7280;font-size:13px">
            <i class='bx bx-lock-alt' style="font-size:22px;display:block;margin-bottom:4px"></i>
            O holerite oficial desta competência ainda não foi liberado pelo RH.
        </div>
    <?php endif; ?>

    <div class="rh-card">
        <h4 style="margin:0 0 10px"><i class='bx bx-money'></i> Demonstrativo</h4>
        <?php if (empty($resumo['itens'])): ?>
            <div style="color:#9ca3af;font-size:13px">Nenhum lançamento aprovado nesta competência.</div>
        <?php else: ?>
            <table style="width:100%;font-size:13px">
                <?php foreach ($resumo['itens'] as $it): ?>
                <tr>
                    <td style="padding:4px 0"><?= htmlspecialchars($it->descricao ?: ($labelTipo[$it->tipo] ?? $it->tipo)) ?></td>
                    <td style="text-align:right;color:<?= $it->natureza==='desconto' ? '#ef4444':'#065f46' ?>">
                        <?= $it->natureza==='desconto' ? '-' : '+' ?> R$ <?= number_format($it->valor, 2, ',', '.') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <hr style="margin:10px 0">
            <div style="display:flex;justify-content:space-between;font-size:13px"><span>Proventos</span><strong style="color:#065f46">R$ <?= number_format($resumo['proventos'],2,',','.') ?></strong></div>
            <div style="display:flex;justify-content:space-between;font-size:13px"><span>Descontos</span><strong style="color:#ef4444">R$ <?= number_format($resumo['descontos'],2,',','.') ?></strong></div>
            <div style="display:flex;justify-content:space-between;font-size:16px;margin-top:6px"><span><strong>Líquido</strong></span><strong>R$ <?= number_format($resumo['liquido'],2,',','.') ?></strong></div>
            <?php if (! empty($resumo['fgts']) && $resumo['fgts'] > 0): ?>
                <div style="display:flex;justify-content:space-between;font-size:12px;margin-top:8px;color:#6b7280">
                    <span>FGTS (informativo)</span><span>R$ <?= number_format($resumo['fgts'],2,',','.') ?></span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div style="font-size:12px;color:#9ca3af;margin-top:12px;text-align:center">
        Demonstrativo com descontos legais (CLT). O PDF oficial é liberado pelo RH.
    </div>
</div>
<script>
document.getElementById('competencia').addEventListener('change', function(){
    window.location = '<?= site_url('colaborador/holerite') ?>/' + this.value;
});
</script>
<?php $this->load->view('colaborador/_nav', ['nav_ativo' => '', 'pode_bater_ponto' => $pode_bater_ponto]); ?>
</body>
</html>
