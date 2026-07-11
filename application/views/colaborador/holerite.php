<?php
$this->load->view('colaborador/_topo', [
    'titulo' => 'Holerite',
    'header_icone' => 'bx-receipt',
    'header_sub' => 'Competência ' . date('m/Y', strtotime($competencia . '-01')),
    'voltar_url' => site_url('colaborador'),
]);
$labelTipo = ['hora_extra'=>'Horas extras','adicional'=>'Adicional','comissao'=>'Comissão','bonus'=>'Bônus',
    'adiantamento'=>'Adiantamento','desconto'=>'Desconto','falta'=>'Falta','vale'=>'Vale'];
?>
<div class="ponto-wrap">
    <input type="month" id="competencia" value="<?= $competencia ?>" class="span12" style="width:100%;margin-bottom:12px">

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
        <?php endif; ?>
    </div>

    <div style="font-size:12px;color:#9ca3af;margin-top:12px;text-align:center">
        Demonstrativo gerencial. O holerite oficial (com encargos) é emitido pela contabilidade.
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
