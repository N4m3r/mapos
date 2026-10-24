<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css">
<?php
$fmt = function ($v, $d = 2) { return number_format((float) $v, $d, ',', '.'); };
$perm = function ($p) { return $this->permission->checkPermission($this->session->userdata('permissao'), $p); };
$data_br = function ($x) { return (! empty($x) && $x !== '0000-00-00') ? date('d/m/Y', strtotime($x)) : '—'; };
$dt_br = function ($x) { return ! empty($x) ? date('d/m/Y H:i', strtotime($x)) : '—'; };
$tipos = ['mensal' => 'Mensal', 'trimestral' => 'Trimestral', 'semestral' => 'Semestral', 'anual' => 'Anual'];
$statusLabels = ['ativo' => 'Ativo', 'suspenso' => 'Suspenso', 'encerrado' => 'Encerrado'];

// Estado do SLA de solução de uma OS (para o badge da lista).
$slaBadge = function ($os) {
    $abertos = ! in_array($os->status, ['Finalizado', 'Faturado', 'Cancelado'], true);
    if (! empty($os->sla_solucao_em)) {
        $ok = empty($os->sla_solucao_prazo) || strtotime($os->sla_solucao_em) <= strtotime($os->sla_solucao_prazo);
        return $ok
            ? '<span class="label label-success">No prazo</span>'
            : '<span class="label label-important">Fora do prazo</span>';
    }
    if (! empty($os->sla_solucao_prazo)) {
        if ($abertos && strtotime($os->sla_solucao_prazo) < time()) {
            return '<span class="label label-important">Estourado</span>';
        }
        return '<span class="label label-warning">Em andamento</span>';
    }
    return '<span class="label">—</span>';
};
?>

<div class="new122" style="margin-top:0">
    <div class="widget-title" style="margin:-6px 0 8px">
        <span class="icon"><i class="fas fa-file-signature"></i></span>
        <h5>Contrato: <?= html_escape($contrato->descricao) ?></h5>
    </div>

    <?php $this->load->view('contratos/_nav', ['contrato_menu' => 'contratos']); ?>

    <div class="btn-toolbar" style="margin:0 0 10px">
        <div class="btn-group">
            <?php if ($perm('eContrato')): ?>
                <a href="<?= site_url('contratos/editar/' . $contrato->idContrato) ?>" class="button btn btn-mini btn-primary"><span class="button__icon"><i class='bx bx-edit'></i></span><span class="button__text2">Editar</span></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row-fluid">
        <!-- Dados do contrato -->
        <div class="span5" style="margin-left:0">
            <div class="widget-box">
                <div class="widget-title"><h5><i class='bx bx-info-circle'></i> Dados do contrato</h5></div>
                <div class="widget-content">
                    <table class="table">
                        <tr><td><strong>Código</strong></td><td><?= html_escape($contrato->codigo ?: '#' . $contrato->idContrato) ?></td></tr>
                        <tr><td><strong>Cliente</strong></td><td><?= html_escape($contrato->nomeCliente ?: '—') ?></td></tr>
                        <tr><td><strong>Periodicidade</strong></td><td><?= $tipos[$contrato->tipo] ?? html_escape($contrato->tipo) ?></td></tr>
                        <tr><td><strong>Valor</strong></td><td>R$ <?= $fmt($contrato->valor) ?></td></tr>
                        <tr><td><strong>Vencimento</strong></td><td>Todo dia <?= (int) $contrato->dia_vencimento ?></td></tr>
                        <tr><td><strong>Vigência</strong></td><td><?= $data_br($contrato->data_inicio) ?> a <?= $data_br($contrato->data_fim) ?></td></tr>
                        <tr><td><strong>Status</strong></td><td><?= $statusLabels[$contrato->status] ?? html_escape($contrato->status) ?></td></tr>
                        <?php if (! empty($contrato->observacoes)): ?>
                            <tr><td><strong>Observações</strong></td><td><?= nl2br(html_escape($contrato->observacoes)) ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- SLA -->
            <div class="widget-box">
                <div class="widget-title"><h5><i class='bx bx-time-five'></i> SLA por prioridade</h5></div>
                <div class="widget-content nopadding">
                    <table class="table table-bordered">
                        <thead><tr><th>Prioridade</th><th>Resposta</th><th>Solução</th></tr></thead>
                        <tbody>
                            <?php if (! $slas) echo '<tr><td colspan="3">SLA não definido.</td></tr>'; ?>
                            <?php foreach ($slas as $s): ?>
                                <tr>
                                    <td><strong><?= html_escape($s->prioridade) ?></strong></td>
                                    <td><?= (int) $s->resposta_horas ?>h</td>
                                    <td><?= (int) $s->solucao_horas ?>h</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Faturamento recorrente -->
        <div class="span7">
            <div class="widget-box">
                <div class="widget-title"><h5><i class='bx bx-dollar-circle'></i> Faturamento (mensalidades)</h5></div>
                <div class="widget-content">
                    <?php if ($perm('fContrato')): ?>
                        <form action="<?= site_url('contratos/faturar') ?>" method="post" class="form-inline" style="margin-bottom:10px">
                            <input type="hidden" name="idContrato" value="<?= $contrato->idContrato ?>">
                            <label style="display:inline">Competência</label>
                            <input type="month" name="competencia" value="<?= date('Y-m') ?>" style="width:auto">
                            <button class="button btn btn-mini btn-success" <?= $jaFaturadoMes ? 'disabled title="Já faturado neste mês"' : '' ?>>
                                <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Gerar mensalidade</span></button>
                            <?php if ($jaFaturadoMes): ?>
                                <small style="color:#b26a00">Competência <?= html_escape($mesAtual) ?> já gerada.</small>
                            <?php endif; ?>
                        </form>
                        <p style="color:#888;font-size:12px;margin:0 0 8px">
                            A mensalidade entra como <strong>receita a receber</strong> em Lançamentos. De lá você pode emitir Boleto/PIX (Cora) normalmente.
                        </p>
                    <?php endif; ?>
                    <table class="table table-bordered">
                        <thead><tr><th>Descrição</th><th>Vencimento</th><th>Valor</th><th>Situação</th></tr></thead>
                        <tbody>
                            <?php if (! $faturas) echo '<tr><td colspan="4">Nenhuma mensalidade gerada ainda.</td></tr>'; ?>
                            <?php foreach ($faturas as $lc): ?>
                                <tr>
                                    <td><?= html_escape($lc->descricao) ?></td>
                                    <td><?= $data_br($lc->data_vencimento) ?></td>
                                    <td>R$ <?= $fmt($lc->valor) ?></td>
                                    <td><?= $lc->baixado ? '<span class="label label-success">Recebido</span>' : '<span class="label label-warning">Em aberto</span>' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- OS vinculadas -->
            <div class="widget-box">
                <div class="widget-title"><h5><i class='bx bx-wrench'></i> Ordens de Serviço do contrato</h5></div>
                <div class="widget-content nopadding">
                    <table class="table table-bordered">
                        <thead><tr><th>OS</th><th>Status</th><th>Prioridade</th><th>Prazo solução</th><th>SLA</th><th></th></tr></thead>
                        <tbody>
                            <?php if (! $ordens) echo '<tr><td colspan="6">Nenhuma OS vinculada. Amarre uma OS a este contrato na tela de edição da OS.</td></tr>'; ?>
                            <?php foreach ($ordens as $os): ?>
                                <tr>
                                    <td>#<?= (int) $os->idOs ?></td>
                                    <td><?= html_escape($os->status) ?></td>
                                    <td><?= html_escape($os->prioridade ?: '—') ?></td>
                                    <td><?= $dt_br($os->sla_solucao_prazo) ?></td>
                                    <td><?= $slaBadge($os) ?></td>
                                    <td><a href="<?= site_url('os/visualizar/' . $os->idOs) ?>" class="btn-nwe" title="Abrir OS"><i class="bx bx-show bx-xs"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
