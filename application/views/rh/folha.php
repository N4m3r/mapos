<?php
$this->load->view('rh/_subnav', ['ativo' => 'folha']);
$fmt = function ($min) { return $this->rh_calculo->minParaHoras($min); };
$brl = function ($v) { return 'R$ ' . number_format($v, 2, ',', '.'); };
$compLabel = date('m/Y', strtotime($competencia . '-01'));
$holerites = $holerites ?? [];
$soNaoLiberados = $this->input->get('holerite') === 'nao';
$totalLinhas = count($linhas);
if ($soNaoLiberados) {
    $linhas = array_values(array_filter($linhas, function ($l) use ($holerites) {
        return ($holerites[(int) $l['colaborador']->id] ?? '') !== 'liberado';
    }));
}
$voltarFolha = '?voltar=' . rawurlencode('rh/folha/' . $competencia);
$selosHolerite = [
    '' => ['neutro', 'Sem holerite'],
    'anexado' => ['alerta', 'Anexado, não liberado'],
    'liberado' => ['sucesso', 'Liberado'],
];
?>
<div class="new122">
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-file"></i> Folha de pagamento</h1>
            <p class="ui-subtitulo">Competência: <?= $compLabel ?></p>
        </div>
    </div>

    <div class="span12" style="margin-left:0;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <input type="month" id="competencia" value="<?= html_escape($competencia) ?>" aria-label="Competência">
            <label style="margin:0;font-weight:normal"><input type="checkbox" id="so-nao-liberados" style="margin:0 4px 0 0" <?= $soNaoLiberados ? 'checked' : '' ?>> Só holerites não liberados</label>
        </div>
        <a href="<?= site_url("rh/folhaPdf/{$competencia}") ?>" target="_blank" class="button btn btn-mini btn-inverse">
            <span class="button__icon"><i class='bx bx-printer'></i></span><span class="button__text2"> Folha em PDF</span></a>
    </div>

    <div class="widget-box"><div class="widget-content nopadding ui-tabela-rolagem">
        <table class="table table-bordered">
            <thead><tr>
                <th>Colaborador</th><th>Trab.</th><th>Extras</th><th>Faltas</th>
                <th>Salário base</th><th>Proventos</th><th>Descontos</th><th>Líquido</th><th>Holerite</th><th>Ações</th>
            </tr></thead>
            <tbody>
            <?php if (empty($linhas)): ?>
                <tr><td colspan="10"><div class="ui-vazio"><i class='bx bx-file'></i><strong><?= $soNaoLiberados && $totalLinhas > 0 ? 'Todos os holerites desta competência já foram liberados' : 'Nenhum colaborador ativo' ?></strong><?php if ($soNaoLiberados): ?><a href="<?= site_url('rh/folha/' . $competencia) ?>" class="button btn btn-mini btn-warning"><span class="button__text2">Ver a folha completa</span></a><?php endif; ?></div></td></tr>
            <?php else: foreach ($linhas as $l): $c = $l['colaborador']; ?>
                <tr>
                    <td><a href="<?= site_url('rh/ficha/'.$c->id.$voltarFolha) ?>"><?= html_escape($c->nome) ?></a>
                        <br><small style="color:#9ca3af"><?= html_escape($c->cargo ?: '-') ?></small></td>
                    <td><?= $fmt($l['horas']['minutos_trabalhados'] ?? 0) ?></td>
                    <td><?= $fmt(($l['horas']['minutos_extras_50'] ?? 0) + ($l['horas']['minutos_extras_100'] ?? 0)) ?></td>
                    <td><?= $fmt($l['horas']['minutos_faltas'] ?? 0) ?></td>
                    <td><?= $brl($l['salario_base']) ?></td>
                    <td style="color:#16a34a"><?= $brl($l['proventos']) ?></td>
                    <td style="color:#dc2626"><?= $brl($l['descontos']) ?></td>
                    <td><strong><?= $brl($l['liquido']) ?></strong></td>
                    <?php $sh = $selosHolerite[$holerites[(int) $c->id] ?? '']; ?>
                    <td><span class="ui-selo <?= $sh[0] ?>"><?= $sh[1] ?></span></td>
                    <td style="white-space:nowrap">
                        <a href="<?= site_url("rh/holeritePdf/{$c->id}/{$competencia}") ?>" target="_blank" class="btn-nwe3" title="Holerite em PDF" aria-label="Holerite em PDF"><i class="bx bx-receipt bx-xs"></i></a>
                        <a href="<?= site_url("rh/holerite/{$c->id}/{$competencia}") . $voltarFolha ?>" class="btn-nwe3" title="Gerenciar holerite" aria-label="Gerenciar holerite"><i class="bx bx-edit bx-xs"></i></a>
                        <a href="<?= site_url("rh/espelho/{$c->id}/{$competencia}") . $voltarFolha ?>" class="btn-nwe3" title="Espelho de ponto" aria-label="Espelho de ponto"><i class="bx bx-calendar-check bx-xs"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <?php if (! empty($linhas)): ?>
            <tfoot><tr class="rh-linha-total" style="font-weight:700">
                <td>TOTAIS (<?= $totalLinhas ?>)<?= $soNaoLiberados ? ' - folha completa' : '' ?></td>
                <td><?= $fmt($tot['trab']) ?></td>
                <td><?= $fmt($tot['extras']) ?></td>
                <td><?= $fmt($tot['faltas']) ?></td>
                <td><?= $brl($tot['salario']) ?></td>
                <td><?= $brl($tot['proventos']) ?></td>
                <td><?= $brl($tot['descontos']) ?></td>
                <td><?= $brl($tot['liquido']) ?></td>
                <td></td>
                <td></td>
            </tr></tfoot>
            <?php endif; ?>
        </table>
    </div></div>

    <p style="font-size:12px;color:#9ca3af">
        Relatório gerencial. Proventos = salário base + lançamentos aprovados; não inclui encargos fiscais (INSS/IRRF/FGTS), que ficam com a contabilidade.
    </p>
</div>
<script nonce="<?= csp_nonce() ?>">
function irFolha(){
    var comp = document.getElementById('competencia').value;
    if (!comp) { return; }
    window.location = '<?= site_url('rh/folha') ?>/' + comp + (document.getElementById('so-nao-liberados').checked ? '?holerite=nao' : '');
}
document.getElementById('competencia').addEventListener('change', irFolha);
document.getElementById('so-nao-liberados').addEventListener('change', irFolha);
</script>
