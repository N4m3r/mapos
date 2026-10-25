<?php
// Relatorio de satisfacao (NPS) - ADR-007
$pct = fn ($n) => $resumo['total'] ? round($n * 100 / $resumo['total']) . '%' : '—';
$classeNps = function ($nps) {
    if ($nps === null) {
        return '';
    }

    return $nps >= 50 ? 'sucesso' : ($nps >= 0 ? 'alerta' : 'perigo');
};
?>

<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="ui-cabecalho">
            <div>
                <h1><i class="bx bx-happy-beaming"></i> Satisfação dos clientes (NPS)</h1>
                <p>Notas dadas pelos clientes após o atendimento: quanto mais promotores, melhor.</p>
            </div>
        </div>

        <form action="<?= site_url('relatorios/nps') ?>" method="get" class="ui-filtros">
            <div class="ui-campo">
                <label for="dataInicial">Avaliações de</label>
                <input type="date" id="dataInicial" name="dataInicial" value="<?= html_escape($filtro['dataInicial']) ?>" />
            </div>
            <div class="ui-campo">
                <label for="dataFinal">Até</label>
                <input type="date" id="dataFinal" name="dataFinal" value="<?= html_escape($filtro['dataFinal']) ?>" />
            </div>
            <div class="ui-campo ui-campo-acao">
                <button class="button btn btn-inverse"><span class="button__icon"><i class="bx bx-filter-alt"></i></span><span class="button__text">Filtrar</span></button>
            </div>
        </form>

        <div class="ui-kpis">
            <div class="ui-kpi <?= $classeNps($resumo['nps']) ?>">
                <div class="ui-kpi-rotulo">NPS</div>
                <div class="ui-kpi-valor"><?= $resumo['nps'] === null ? '—' : $resumo['nps'] ?></div>
                <div class="ui-kpi-extra"><?= (int) $resumo['total'] ?> avaliações</div>
            </div>
            <div class="ui-kpi sucesso"><div class="ui-kpi-rotulo">Promotores (9-10)</div><div class="ui-kpi-valor"><?= $pct($resumo['promotores']) ?></div></div>
            <div class="ui-kpi alerta"><div class="ui-kpi-rotulo">Neutros (7-8)</div><div class="ui-kpi-valor"><?= $pct($resumo['neutros']) ?></div></div>
            <div class="ui-kpi perigo"><div class="ui-kpi-rotulo">Detratores (0-6)</div><div class="ui-kpi-valor"><?= $pct($resumo['detratores']) ?></div></div>
        </div>

        <div class="ui-dica">
            <i class="bx bx-info-circle"></i>
            <div>O cliente avalia pelo link de acompanhamento da OS (botão "Acompanhamento" na OS ou a tag <code>{LINK_ACOMPANHAR}</code> nos modelos de WhatsApp), depois que a OS fica Finalizada ou Faturada.</div>
        </div>

        <?php if ($resumo['porTecnico']) : ?>
            <div class="widget-box">
                <div class="widget-title"><h5>Por técnico</h5></div>
                <div class="widget-content nopadding ui-tabela-rolagem">
                    <table class="table table-bordered">
                        <thead><tr><th>Técnico</th><th>Avaliações</th><th>Média</th><th>NPS</th></tr></thead>
                        <tbody>
                            <?php foreach ($resumo['porTecnico'] as $t) : ?>
                                <tr>
                                    <td><?= html_escape($t['tecnico']) ?></td>
                                    <td><?= (int) $t['total'] ?></td>
                                    <td><?= number_format($t['media'], 1, ',', '') ?></td>
                                    <td><span class="ui-selo <?= $classeNps($t['nps']) ?>"><?= (int) $t['nps'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="widget-box">
            <div class="widget-title"><h5>Avaliações</h5></div>
            <div class="widget-content nopadding ui-tabela-rolagem">
                <table class="table table-bordered">
                    <thead><tr><th>Data</th><th>OS</th><th>Cliente</th><th>Técnico</th><th>Nota</th><th>Comentário</th></tr></thead>
                    <tbody>
                        <?php if (! $resumo['avaliacoes']) : ?>
                            <tr><td colspan="6"><div class="ui-vazio"><i class="bx bx-message-square-dots"></i><strong>Nenhuma avaliação no período</strong>Ajuste as datas do filtro.</div></td></tr>
                        <?php endif; ?>
                        <?php foreach ($resumo['avaliacoes'] as $a) : ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($a->criado_em)) ?></td>
                                <td><a href="<?= site_url('os/visualizar/' . (int) $a->idOs) ?>" title="Abrir OS #<?= (int) $a->idOs ?>">#<?= (int) $a->idOs ?></a></td>
                                <td><?= html_escape($a->nomeCliente) ?></td>
                                <td><?= html_escape($a->tecnico) ?></td>
                                <td><span class="ui-selo <?= $classeNps($a->nota >= 9 ? 100 : ($a->nota >= 7 ? 0 : -1)) ?>"><?= (int) $a->nota ?></span></td>
                                <td><?= nl2br(html_escape($a->comentario)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
