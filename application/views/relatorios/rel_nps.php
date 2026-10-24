<?php
// Relatorio de satisfacao (NPS) - ADR-007
$pct = fn ($n) => $resumo['total'] ? round($n * 100 / $resumo['total']) . '%' : '—';
$corNps = function ($nps) {
    if ($nps === null) {
        return '#7b8088';
    }

    return $nps >= 50 ? '#2e9e5b' : ($nps >= 0 ? '#f0a020' : '#d9534f');
};
?>

<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="bx bx-happy-beaming"></i></span>
                <h5>Satisfação dos clientes (NPS)</h5>
            </div>
            <div class="widget-content">
                <form action="<?= site_url('relatorios/nps') ?>" method="get" class="span12 well" style="margin-left:0">
                    <div class="span3">
                        <label>Avaliações de:</label>
                        <input type="date" name="dataInicial" class="span12" value="<?= html_escape($filtro['dataInicial']) ?>" />
                    </div>
                    <div class="span3">
                        <label>até:</label>
                        <input type="date" name="dataFinal" class="span12" value="<?= html_escape($filtro['dataFinal']) ?>" />
                    </div>
                    <div class="span3">
                        <label>&nbsp;</label>
                        <button class="button btn btn-inverse"><span class="button__icon"><i class="bx bx-filter-alt"></i></span><span class="button__text">Filtrar</span></button>
                    </div>
                </form>

                <div class="span12" style="margin-left:0">
                    <div class="span3 well" style="text-align:center">
                        <div style="font-size:36px;font-weight:700;color:<?= $corNps($resumo['nps']) ?>"><?= $resumo['nps'] === null ? '—' : $resumo['nps'] ?></div>
                        NPS (<?= (int) $resumo['total'] ?> avaliações)
                    </div>
                    <div class="span3 well" style="text-align:center"><div style="font-size:26px;color:#2e9e5b"><?= $pct($resumo['promotores']) ?></div>Promotores (9-10)</div>
                    <div class="span3 well" style="text-align:center"><div style="font-size:26px;color:#f0a020"><?= $pct($resumo['neutros']) ?></div>Neutros (7-8)</div>
                    <div class="span3 well" style="text-align:center"><div style="font-size:26px;color:#d9534f"><?= $pct($resumo['detratores']) ?></div>Detratores (0-6)</div>
                </div>

                <p class="muted span12" style="margin-left:0">
                    O cliente avalia pelo link de acompanhamento da OS (botão "Acompanhamento" na OS ou a tag
                    <code>{LINK_ACOMPANHAR}</code> nos modelos de WhatsApp), depois que a OS fica Finalizada ou Faturada.
                </p>

                <?php if ($resumo['porTecnico']) : ?>
                    <h5 class="span12" style="margin-left:0">Por técnico</h5>
                    <table class="table table-bordered">
                        <thead><tr><th>Técnico</th><th>Avaliações</th><th>Média</th><th>NPS</th></tr></thead>
                        <tbody>
                            <?php foreach ($resumo['porTecnico'] as $t) : ?>
                                <tr>
                                    <td><?= html_escape($t['tecnico']) ?></td>
                                    <td><?= (int) $t['total'] ?></td>
                                    <td><?= number_format($t['media'], 1, ',', '') ?></td>
                                    <td style="color:<?= $corNps($t['nps']) ?>;font-weight:600"><?= (int) $t['nps'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <h5>Avaliações</h5>
                <table class="table table-bordered">
                    <thead><tr><th>Data</th><th>OS</th><th>Cliente</th><th>Técnico</th><th>Nota</th><th>Comentário</th></tr></thead>
                    <tbody>
                        <?php if (! $resumo['avaliacoes']) : ?>
                            <tr><td colspan="6">Nenhuma avaliação no período.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($resumo['avaliacoes'] as $a) : ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($a->criado_em)) ?></td>
                                <td><a href="<?= site_url('os/visualizar/' . (int) $a->idOs) ?>">#<?= (int) $a->idOs ?></a></td>
                                <td><?= html_escape($a->nomeCliente) ?></td>
                                <td><?= html_escape($a->tecnico) ?></td>
                                <td style="color:<?= $corNps($a->nota >= 9 ? 100 : ($a->nota >= 7 ? 0 : -1)) ?>;font-weight:600"><?= (int) $a->nota ?></td>
                                <td><?= nl2br(html_escape($a->comentario)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
