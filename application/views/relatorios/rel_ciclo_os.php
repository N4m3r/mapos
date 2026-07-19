<?php
// Rótulos das etapas para o seletor de "período por".
$periodos = [
    'abertura' => 'Data de abertura',
    'aprovacao' => 'Data de aprovação',
    'aceite' => 'Data de aceite',
    'nf' => 'Data de emissão da NF',
];
$fmt = function ($d, $comHora = false) {
    if (empty($d) || $d === '0000-00-00' || $d === '0000-00-00 00:00:00') {
        return '—';
    }
    return date($comHora ? 'd/m/Y H:i' : 'd/m/Y', strtotime($d));
};
$money = function ($v) {
    return 'R$ ' . number_format((float) $v, 2, ',', '.');
};
?>

<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="fas fa-funnel-dollar"></i></span>
                <h5>Ciclo / Faturamento das OS — orçamentos por período</h5>
            </div>
            <div class="widget-content">
                <form action="<?php echo site_url('relatorios/cicloFaturamento'); ?>" method="get">
                    <div class="span12 well" style="margin-left:0">
                        <div class="span3">
                            <label>Data de:</label>
                            <input type="date" name="dataInicial" class="span12" value="<?php echo html_escape($filtro['dataInicial']); ?>" />
                        </div>
                        <div class="span3">
                            <label>até:</label>
                            <input type="date" name="dataFinal" class="span12" value="<?php echo html_escape($filtro['dataFinal']); ?>" />
                        </div>
                        <div class="span3">
                            <label>Período por:</label>
                            <select name="periodo" class="span12">
                                <?php foreach ($periodos as $val => $rot): ?>
                                    <option value="<?php echo $val; ?>" <?php echo $filtro['periodo'] === $val ? 'selected' : ''; ?>><?php echo $rot; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="span3">
                            <label>Cliente:</label>
                            <input type="text" id="cliente" class="span12" />
                            <input type="hidden" name="cliente" id="clienteHide" value="<?php echo html_escape($filtro['cliente']); ?>" />
                        </div>
                    </div>
                    <div class="span12" style="display:flex;justify-content:center;gap:8px">
                        <button class="button btn btn-inverse" type="submit">
                            <span class="button__icon"><i class="bx bx-search-alt"></i></span>
                            <span class="button__text">Gerar</span>
                        </button>
                        <?php if ($resultados !== null): ?>
                            <button class="button btn btn-primary" type="button" onclick="window.print()">
                                <span class="button__icon"><i class="bx bx-printer"></i></span>
                                <span class="button__text">Imprimir</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
                <p style="color:#6b7191;font-size:12px;margin:8px 4px 0">
                    <b>Em aberto</b> = OS ainda não faturada no financeiro (status ≠ Faturado e não faturada).
                    <b>Faturado</b> = status “Faturado” ou marcada como faturada.
                    Escolhendo o período por <b>aprovação</b>, <b>aceite</b> ou <b>emissão da NF</b>, só entram OS que já têm essa data.
                </p>
            </div>
        </div>
    </div>
</div>

<?php if ($resultados !== null): ?>
    <div class="row-fluid">
        <div class="span12">
            <div class="widget-box">
                <div class="widget-title">
                    <span class="icon"><i class="fas fa-chart-pie"></i></span>
                    <h5>Resumo do período (<?php echo $periodos[$filtro['periodo']]; ?>)</h5>
                </div>
                <div class="widget-content">
                    <div class="row-fluid" style="text-align:center">
                        <div class="span2"><h3 style="margin:0"><?php echo $resumo['total']; ?></h3><small>Total de OS</small></div>
                        <div class="span2"><h3 style="margin:0;color:#c09853"><?php echo $resumo['em_aberto']; ?></h3><small>Em aberto</small></div>
                        <div class="span2"><h3 style="margin:0;color:#468847"><?php echo $resumo['faturado']; ?></h3><small>Faturadas</small></div>
                        <div class="span2"><h3 style="margin:0;color:#3a87ad"><?php echo $resumo['com_nf']; ?></h3><small>Com NF</small></div>
                        <div class="span2"><h3 style="margin:0;color:#3a87ad"><?php echo $resumo['com_boleto']; ?></h3><small>Com boleto</small></div>
                        <div class="span2"><h3 style="margin:0;color:#468847"><?php echo $resumo['boleto_pago']; ?></h3><small>Boleto pago</small></div>
                    </div>
                    <hr>
                    <div class="row-fluid" style="text-align:center">
                        <div class="span4"><b>Valor total:</b> <?php echo $money($resumo['valor_total']); ?></div>
                        <div class="span4" style="color:#c09853"><b>Em aberto:</b> <?php echo $money($resumo['valor_em_aberto']); ?></div>
                        <div class="span4" style="color:#468847"><b>Faturado:</b> <?php echo $money($resumo['valor_faturado']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span12">
            <div class="widget-box">
                <div class="widget-title">
                    <span class="icon"><i class="fas fa-list"></i></span>
                    <h5>Detalhamento — <?php echo count($resultados); ?> OS</h5>
                </div>
                <div class="widget-content">
                    <table class="table table-bordered table-striped" style="font-size:12px">
                        <thead>
                            <tr>
                                <th>OS</th>
                                <th>Cliente</th>
                                <th>Situação</th>
                                <th>Abertura</th>
                                <th>Atribuição</th>
                                <th>Aprovação</th>
                                <th>Aceite</th>
                                <th>NF emitida</th>
                                <th>Boleto</th>
                                <th style="text-align:right">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($resultados)): ?>
                                <tr><td colspan="10" style="text-align:center">Nenhuma OS encontrada para o período.</td></tr>
                            <?php else: ?>
                                <?php foreach ($resultados as $l): ?>
                                    <?php
                                    $faturado = ($l->status === 'Faturado') || ((int) $l->faturado === 1);
                                    $cancelado = ($l->status === 'Cancelado');
                                    if ($cancelado) {
                                        $badge = '<span class="label label-important">Cancelada</span>';
                                    } elseif ($faturado) {
                                        $badge = '<span class="label label-success">Faturada</span>';
                                    } else {
                                        $badge = '<span class="label label-warning">Em aberto</span>';
                                    }
                                    if (! empty($l->data_boleto)) {
                                        $boleto = $fmt($l->data_boleto) . (! empty($l->boleto_pago)
                                            ? ' <span class="label label-success">pago</span>'
                                            : ' <span class="label">pendente</span>');
                                    } else {
                                        $boleto = '—';
                                    }
                                    ?>
                                    <tr>
                                        <td><a target="_blank" href="<?php echo site_url('os/visualizar/' . $l->idOs); ?>">#<?php echo $l->idOs; ?></a></td>
                                        <td><?php echo html_escape($l->nomeCliente); ?></td>
                                        <td><?php echo $badge; ?> <small><?php echo html_escape($l->status); ?></small></td>
                                        <td><?php echo $fmt($l->dataInicial); ?></td>
                                        <td><?php echo $fmt($l->data_atribuicao, true); ?></td>
                                        <td><?php echo $fmt($l->aprovacao_data, true); ?></td>
                                        <td><?php echo $fmt($l->aceite_data, true); ?></td>
                                        <td><?php echo $fmt($l->data_nf, true); ?></td>
                                        <td><?php echo $boleto; ?></td>
                                        <td style="text-align:right"><?php echo $money($l->valor_os); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $("#cliente").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteCliente",
            minLength: 2,
            select: function (event, ui) {
                $("#clienteHide").val(ui.item.id);
            }
        });
        // Limpa o id oculto do cliente se o campo de texto for apagado.
        $("#cliente").on('input', function () {
            if ($(this).val() === '') { $("#clienteHide").val(''); }
        });
    });
</script>
