<?php
$pt = $pend_tipo ?? ['ocorrencias' => 0, 'ausencias' => 0, 'lancamentos' => 0];
$kpis = [
    ['url' => 'rh/ocorrencias?status=pendente', 'rotulo' => 'Ocorrências pendentes', 'valor' => (int) $pt['ocorrencias'], 'extra' => 'Correções e justificativas a analisar', 'cor' => 'alerta', 'mostrar' => true],
    ['url' => 'rh/ausencias?status=pendente', 'rotulo' => 'Ausências pendentes', 'valor' => (int) $pt['ausencias'], 'extra' => 'Férias, folgas e atestados a analisar', 'cor' => 'alerta', 'mostrar' => true],
    ['url' => 'rh/lancamentos?pendentes=1', 'rotulo' => 'Horas extras a aprovar', 'valor' => (int) $pt['lancamentos'], 'extra' => 'Lançamentos aguardando aprovação', 'cor' => 'info', 'mostrar' => ! empty($pode_financeiro)],
    ['url' => 'rh/colaboradores', 'rotulo' => 'Sem batida hoje', 'valor' => (int) ($sem_batida_hoje ?? 0), 'extra' => 'Ativos sem ponto registrado hoje (exceto afastados)', 'cor' => 'perigo', 'mostrar' => true],
    ['url' => 'rh/folha/' . ($competencia ?? date('Y-m')) . '?holerite=nao', 'rotulo' => 'Holerites não liberados', 'valor' => (int) ($holerites_pendentes ?? 0), 'extra' => 'Competência ' . date('m/Y'), 'cor' => 'alerta', 'mostrar' => $holerites_pendentes !== null],
];
?>
<div class="new122">
    <?php $this->load->view('rh/_subnav', ['ativo' => 'painel']); ?>
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-tachometer"></i> RH — Painel</h1>
            <p class="ui-subtitulo">O que precisa de ação hoje. Clique em um quadro para abrir a lista já filtrada.</p>
        </div>
        <a href="<?= site_url('rh/colaboradores') ?>" class="button btn btn-success"><span class="button__icon"><i class='bx bx-group'></i></span><span class="button__text2"> Gerenciar colaboradores</span></a>
    </div>

    <div class="ui-kpis">
        <?php foreach ($kpis as $k): if (! $k['mostrar']) continue; ?>
            <a href="<?= site_url($k['url']) ?>" class="ui-kpi rh-kpi-link <?= $k['valor'] > 0 ? $k['cor'] : 'sucesso' ?>">
                <div class="ui-kpi-rotulo"><?= html_escape($k['rotulo']) ?></div>
                <div class="ui-kpi-valor"><?= $k['valor'] ?></div>
                <div class="ui-kpi-extra"><?= html_escape($k['valor'] > 0 ? $k['extra'] : 'Nada pendente') ?></div>
            </a>
        <?php endforeach; ?>
        <div class="ui-kpi sucesso">
            <div class="ui-kpi-rotulo">Colaboradores ativos</div>
            <div class="ui-kpi-valor"><?= (int) $total_colaboradores ?></div>
            <div class="ui-kpi-extra"><?= (int) $presentes_hoje ?> presentes agora</div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span8">
            <div class="widget-box">
                <div class="widget-title"><span class="icon"><i class="fas fa-clock"></i></span><h5>Últimas batidas</h5></div>
                <div class="widget-content nopadding ui-tabela-rolagem">
                    <table class="table table-bordered">
                        <thead><tr><th>Colaborador</th><th>Tipo</th><th>Data/Hora</th><th>Local</th></tr></thead>
                        <tbody>
                        <?php $lbl = ['entrada' => 'Entrada', 'saida' => 'Saída', 'inicio_intervalo' => 'Início do intervalo', 'fim_intervalo' => 'Fim do intervalo'];
                        if (empty($ultimos_registros)): ?>
                            <tr><td colspan="4"><div class="ui-vazio"><i class='bx bx-time-five'></i><strong>Nenhuma batida registrada</strong>As batidas de ponto aparecem aqui assim que os colaboradores registrarem.</div></td></tr>
                        <?php else: foreach ($ultimos_registros as $r): ?>
                            <tr>
                                <td><?= html_escape($r->nome_colaborador ?: '#' . $r->colaborador_id) ?></td>
                                <td><?= html_escape($lbl[$r->tipo] ?? $r->tipo) ?></td>
                                <td><?= date('d/m H:i', strtotime($r->data_hora)) ?></td>
                                <td><?php if ($r->dentro_geofence === '1') echo '<span class="ui-selo sucesso">Na área</span>';
                                    elseif ($r->dentro_geofence === '0') echo '<span class="ui-selo perigo">Fora da área (' . (int) $r->distancia_metros . ' m)</span>';
                                    else echo '—'; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="widget-box">
                <div class="widget-title"><span class="icon"><i class="fas fa-birthday-cake"></i></span><h5>Aniversariantes do mês</h5></div>
                <div class="widget-content">
                    <?php if (empty($aniversariantes)): ?>
                        <p>Nenhum aniversariante.</p>
                    <?php else: foreach ($aniversariantes as $a): ?>
                        <div class="rh-linha-lista">
                            <i class='bx bx-gift'></i> <?= html_escape($a->nome) ?>
                            <span class="rh-linha-lista-dir"><?= date('d/m', strtotime($a->data_nascimento)) ?></span>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <div class="widget-box">
                <div class="widget-title"><span class="icon"><i class="fas fa-bolt"></i></span><h5>Atalhos</h5></div>
                <div class="widget-content rh-atalhos">
                    <a href="<?= site_url('rh/ocorrencias?status=pendente') ?>" class="btn btn-block"><i class='bx bx-error-circle'></i> Ocorrências <?php if ($pt['ocorrencias'] > 0): ?><span class="rh-subnav-badge"><?= (int) $pt['ocorrencias'] ?></span><?php endif; ?></a>
                    <a href="<?= site_url('rh/ausencias?status=pendente') ?>" class="btn btn-block"><i class='bx bx-calendar-star'></i> Folgas e férias <?php if ($pt['ausencias'] > 0): ?><span class="rh-subnav-badge"><?= (int) $pt['ausencias'] ?></span><?php endif; ?></a>
                    <?php if (! empty($pode_financeiro)): ?>
                        <a href="<?= site_url('rh/lancamentos?pendentes=1') ?>" class="btn btn-block"><i class='bx bx-money'></i> Horas extras a aprovar <?php if ($pt['lancamentos'] > 0): ?><span class="rh-subnav-badge"><?= (int) $pt['lancamentos'] ?></span><?php endif; ?></a>
                        <a href="<?= site_url('rh/folha') ?>" class="btn btn-block"><i class='bx bx-file'></i> Folha e holerites <?php if (! empty($holerites_pendentes)): ?><span class="rh-subnav-badge"><?= (int) $holerites_pendentes ?></span><?php endif; ?></a>
                    <?php endif; ?>
                    <a href="<?= site_url('rh/unidades') ?>" class="btn btn-block"><i class='bx bx-buildings'></i> Unidades</a>
                    <a href="<?= site_url('rh/jornadas') ?>" class="btn btn-block"><i class='bx bx-time'></i> Jornadas</a>
                </div>
            </div>
        </div>
    </div>
</div>
