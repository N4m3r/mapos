<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css">
<style>
    .pontos-tipos { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; padding: 12px; }
    .pontos-tipo { border: 1px solid var(--ui-borda, #e2e8f0); border-radius: 8px; padding: 10px 12px; }
    .pontos-tipo-topo { display: flex; justify-content: space-between; gap: 8px; font-size: 13px; margin-bottom: 6px; }
    .acomp-pontos small { color: #6b7280; }
    .fila-tabela tr.fila-alerta td { background: rgba(229, 72, 77, .08); }
    .fila-tabela .fila-alerta-ico { color: #c62828; margin-right: 4px; }
    @media (max-width: 767px) { .pontos-tipos { grid-template-columns: 1fr; } }
</style>
<?php
$fmt =function ($v, $d = 2) { return number_format((float) $v, $d, ',', '.'); };
$data_br = function ($x) { return (! empty($x) && $x !== '0000-00-00') ? date('d/m/Y', strtotime($x)) : '—'; };
$perm = function ($p) { return $this->permission->checkPermission($this->session->userdata('permissao'), $p); };
$statusLabels = ['planejamento' => 'Planejamento', 'em_execucao' => 'Em execução', 'paralisada' => 'Paralisada', 'concluida' => 'Concluída', 'entregue' => 'Entregue', 'cancelada' => 'Cancelada'];
$acomp = $acomp ?? ['projetos' => [], 'contagem' => [], 'atividade_7d' => [], 'tecnicos_hoje' => [], 'ocorrencias_recentes' => []];
$acomp_selo = ['critico' => 'perigo', 'atencao' => 'alerta', 'parado' => 'neutro', 'ok' => 'sucesso'];
$acomp_niveis = ['critico' => 'Crítico', 'atencao' => 'Atenção', 'parado' => 'Paralisado', 'ok' => 'Em dia'];
$acomp_dias = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom'];
$txt = function ($v, $vazio = '—') { $v = trim((string) ($v ?? '')); return html_escape($v !== '' ? $v : $vazio); };
$pct = function ($v) { return sprintf('%.1f', max(0, min(100, (float) $v))); };
$quando = function ($dias) { if ($dias === null) { return 'nunca'; } $d = (int) $dias; if ($d === 0) { return 'hoje'; } return 'há ' . $d . ($d === 1 ? ' dia' : ' dias'); };
?>

<?php $this->load->view('obras/_nav'); ?>

<div class="new122" style="margin-top:0">
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-tachometer"></i> Painel de Projetos</h1>
            <p class="ui-subtitulo">Resumo de execução, medições pendentes e registros de execução recentes.</p>
        </div>
        <div class="ui-acoes">
            <a href="<?= site_url('relatorioprojetos') ?>" class="button btn btn-mini btn-info">
                <span class="button__icon"><i class='bx bx-file'></i></span><span class="button__text2">Relatório da semana</span></a>
            <?php if ($perm('cObras')): ?>
                <a href="<?= site_url('obras/adicionar') ?>" class="button btn btn-mini btn-success">
                    <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Novo Projeto</span></a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPIs -->
    <div class="obra-kpis">
        <div class="obra-kpi"><div class="k-icon" style="background:#eef2ff;color:#4f46e5"><i class='bx bx-building-house'></i></div><div><div class="k-num"><?= (int) $kpis['total'] ?></div><div class="k-lbl">Projetos cadastrados</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#e7f5ec;color:#2e7d32"><i class='bx bx-loader-circle'></i></div><div><div class="k-num"><?= (int) $kpis['em_execucao'] ?></div><div class="k-lbl">Em execução</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#fff4e5;color:#b26a00"><i class='bx bx-pause-circle'></i></div><div><div class="k-num"><?= (int) $kpis['paralisadas'] ?></div><div class="k-lbl">Paralisadas</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#e6f7fb;color:#0277bd"><i class='bx bx-check-circle'></i></div><div><div class="k-num"><?= (int) $kpis['concluidas'] ?></div><div class="k-lbl">Concluídas</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#f3e8fd;color:#7b1fa2"><i class='bx bx-trending-up'></i></div><div><div class="k-num"><?= $fmt($kpis['progresso_medio'], 1) ?>%</div><div class="k-lbl">Progresso médio</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#fdeaea;color:#c62828"><i class='bx bx-receipt'></i></div><div><div class="k-num"><?= (int) $kpis['medicoes_abertas'] ?></div><div class="k-lbl">Medições a aprovar</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#e7f5ec;color:#2e7d32"><i class='bx bx-dollar-circle'></i></div><div><div class="k-num">R$ <?= $fmt($kpis['valor_contratos']) ?></div><div class="k-lbl">Em contratos</div></div></div>
        <div class="obra-kpi"><div class="k-icon" style="background:#eef2ff;color:#4f46e5"><i class='bx bx-box'></i></div><div><div class="k-num"><?= $fmt($kpis['custodia_cliente'], 0) ?></div><div class="k-lbl">Material do cliente (custódia)</div></div></div>
    </div>

    <!-- Saúde dos projetos (ADR-012) -->
    <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-heart'></i> Saúde dos projetos</h5></div>
        <div class="widget-content nopadding">
            <div class="acomp-chips">
                <?php $cont = $acomp['contagem'] ?? []; foreach ($acomp_niveis as $nk => $nl): ?>
                    <span class="ui-selo <?= $acomp_selo[$nk] ?>"><?= $nl ?> <strong><?= (int) ($cont[$nk] ?? 0) ?></strong></span>
                <?php endforeach; ?>
            </div>
            <div class="ui-tabela-rolagem">
                <table class="table table-bordered acomp-tabela">
                    <thead><tr><th>Semáforo</th><th>Projeto</th><th>Cliente</th><th>Responsável</th><th>Avanço</th><th>Pontos</th><th>Último registro</th><th>Registros 7d</th><th>Motivos</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($acomp['projetos'])) echo '<tr><td colspan="10"><div class="ui-vazio"><i class="bx bx-heart"></i><strong>Nenhum projeto em planejamento ou execução.</strong></div></td></tr>'; ?>
                        <?php foreach ($acomp['projetos'] as $p): $s = $p['saude'] ?? []; $nv = $s['nivel'] ?? 'ok'; $esp = $s['esperado'] ?? null; $mot = $s['motivos'] ?? []; ?>
                            <tr>
                                <td data-label="Semáforo"><span class="ui-selo <?= $acomp_selo[$nv] ?? 'neutro' ?>"><?= $txt($s['rotulo'] ?? '') ?></span></td>
                                <td data-label="Projeto"><a href="<?= html_escape(site_url('obras/visualizar/' . (int) $p['idObra'])) ?>"><?= $txt($p['nome'] ?? '') ?></a><br><small><?= html_escape($statusLabels[$p['status']] ?? (string) ($p['status'] ?? '')) ?></small></td>
                                <td data-label="Cliente"><?= $txt($p['cliente'] ?? null) ?></td>
                                <td data-label="Responsável"><?= $txt($p['responsavel'] ?? null) ?></td>
                                <td data-label="Avanço" class="acomp-avanco">
                                    <div class="acomp-prog">
                                        <div class="obra-progress mini"><div class="obra-progress-bar" style="width:<?= $pct($p['percentual_concluido'] ?? 0) ?>%"></div></div>
                                        <?php if ($esp !== null): ?><span class="acomp-marca" style="left:<?= $pct($esp) ?>%" title="Previsto: <?= $fmt($esp, 0) ?>%"></span><?php endif; ?>
                                    </div>
                                    <small><?= $fmt($p['percentual_concluido'] ?? 0, 1) ?>%<?= $esp !== null ? ' · previsto ' . $fmt($esp, 0) . '%' : '' ?></small>
                                </td>
                                <?php $ptTot = (int) ($p['pontos']['total'] ?? 0); $ptTes = (int) ($p['pontos']['testados'] ?? 0); ?>
                                <td data-label="Pontos" class="acomp-pontos"><?php if ($ptTot > 0): ?><strong><?= $ptTes ?>/<?= $ptTot ?></strong><br><small><?= $fmt($ptTes / $ptTot * 100, 0) ?>% testados</small><?php else: ?><small>—</small><?php endif; ?></td>
                                <td data-label="Último registro"><?= $quando($p['dias_sem_registro'] ?? null) ?><br><small><?= $data_br($p['ultimo_registro'] ?? null) ?></small></td>
                                <td data-label="Registros 7d"><?= (int) ($p['registros_7d'] ?? 0) ?></td>
                                <td data-label="Motivos">
                                    <?php if ($mot): ?>
                                        <ul class="acomp-motivos">
                                            <?php foreach (array_slice($mot, 0, 3) as $m): ?><li><?= html_escape((string) $m) ?></li><?php endforeach; ?>
                                            <?php if (count($mot) > 3): ?><li>+<?= count($mot) - 3 ?> outro(s)</li><?php endif; ?>
                                        </ul>
                                    <?php else: ?><small>—</small><?php endif; ?>
                                </td>
                                <td><a href="<?= html_escape(site_url('relatorioprojetos?tipo=semanal&obra_id=' . (int) $p['idObra'])) ?>" class="button btn btn-mini btn-info"><span class="button__icon"><i class='bx bx-file'></i></span><span class="button__text2">Relatório</span></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <!-- Atividade dos últimos 7 dias -->
        <div class="span6" style="margin-left:0">
            <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-bar-chart-alt-2'></i> Atividade dos últimos 7 dias</h5></div>
                <div class="widget-content">
                    <?php $ativ = $acomp['atividade_7d'] ?? []; $maxA = 0; foreach ($ativ as $a) { $maxA = max($maxA, (int) $a['registros']); } ?>
                    <?php if (! $ativ): ?>
                        <div class="ui-vazio"><i class="bx bx-bar-chart-alt-2"></i><strong>Sem atividade registrada.</strong></div>
                    <?php else: ?>
                        <div class="acomp-barras">
                            <?php foreach ($ativ as $a): $n = (int) $a['registros']; $alt = $maxA > 0 ? round($n / $maxA * 100, 1) : 0; ?>
                                <div class="acomp-barra-col" title="<?= html_escape($data_br($a['data']) . ': ' . $n . ($n === 1 ? ' registro' : ' registros')) ?>">
                                    <span class="acomp-barra-num"><?= $n ?></span>
                                    <div class="acomp-barra-area"><div class="acomp-barra" style="height:<?= $alt ?>%"></div></div>
                                    <span class="acomp-barra-dia"><?= $acomp_dias[(int) date('N', strtotime($a['data']))] ?? '' ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Técnicos hoje -->
        <div class="span6">
            <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-user-check'></i> Técnicos hoje</h5></div>
                <div class="widget-content nopadding">
                    <?php if (empty($acomp['tecnicos_hoje'])): ?>
                        <div class="ui-vazio"><i class="bx bx-user-check"></i><strong>Nenhum técnico vinculado aos projetos.</strong></div>
                    <?php else: ?>
                        <ul class="acomp-lista">
                            <?php foreach ($acomp['tecnicos_hoje'] as $t): $ok = ! empty($t['registrou_hoje']); ?>
                                <li class="acomp-tec">
                                    <i class="bx <?= $ok ? 'bx-check-circle' : 'bx-time' ?> acomp-ico <?= $ok ? 'sucesso' : 'alerta' ?>"></i>
                                    <div>
                                        <strong><?= $txt($t['nome'] ?? '') ?></strong>
                                        <span class="ui-selo <?= $ok ? 'sucesso' : 'alerta' ?>"><?= $ok ? 'Registrou hoje' : 'Sem registro hoje' ?></span>
                                        <br><small>Último: <?= ! empty($t['ultimo']) ? $data_br($t['ultimo']) : 'nunca' ?> · <?= (int) ($t['projetos'] ?? 0) ?> projeto(s)</small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Fila de repasses (ADR-014) -->
    <?php
    $fila = $acomp['fila'] ?? [];
    $filaAtraso = function ($h) { if ($h === null) { return '—'; } $h = (float) $h; if ($h > 48) { $d = (int) floor($h / 24); return 'há ' . $d . ($d === 1 ? ' dia' : ' dias'); } return 'há ' . (int) floor($h) . ' h'; };
    ?>
    <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-transfer'></i> Fila de repasses</h5></div>
        <div class="widget-content nopadding ui-tabela-rolagem">
            <?php if (! $fila): ?>
                <div class="ui-vazio"><i class="bx bx-transfer"></i><strong>Nenhum ponto aguardando repasse</strong></div>
            <?php else: ?>
                <table class="table table-bordered fila-tabela">
                    <thead><tr><th>Técnico</th><th>Aguardando configuração</th><th>Aguardando teste</th><th>Outros</th><th>Mais antigo</th></tr></thead>
                    <tbody>
                        <?php foreach ($fila as $f):
                            $ag = $f['aguardando'] ?? [];
                            $conf = (int) ($ag['configurado'] ?? 0);
                            $test = (int) ($ag['testado'] ?? 0);
                            $outros = max(0, (int) ($f['total'] ?? 0) - $conf - $test);
                            $hAtr = $f['horas_mais_antigo'] ?? null;
                            $alerta = $hAtr !== null && (float) $hAtr > 24;
                        ?>
                            <tr class="<?= $alerta ? 'fila-alerta' : '' ?>">
                                <td data-label="Técnico"><?php if ($alerta): ?><i class="bx bx-error fila-alerta-ico" title="Parado há mais de 24 h"></i><?php endif; ?><strong><?= $txt($f['nome'] ?? '') ?></strong></td>
                                <td data-label="Aguardando configuração"><?= $conf ?></td>
                                <td data-label="Aguardando teste"><?= $test ?></td>
                                <td data-label="Outros"><?= $outros ?></td>
                                <td data-label="Mais antigo"><?= html_escape($filaAtraso($hAtr)) ?><?php if (! empty($f['mais_antigo'])): ?><br><small><?= html_escape(date('d/m/Y H:i', strtotime($f['mais_antigo']))) ?></small><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pontos por tipo (ADR-013) -->
    <?php $pontosTipo = $acomp['pontos_por_tipo'] ?? []; ?>
    <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-devices'></i> Pontos por tipo</h5></div>
        <div class="widget-content">
            <?php if (! $pontosTipo): ?>
                <div class="ui-vazio"><i class="bx bx-devices"></i><strong>Nenhum ponto cadastrado nos projetos ativos.</strong></div>
            <?php else: ?>
                <div class="pontos-tipos">
                    <?php foreach ($pontosTipo as $pt): $ptT = (int) ($pt['total'] ?? 0); $ptS = (int) ($pt['testados'] ?? 0); ?>
                        <div class="pontos-tipo">
                            <div class="pontos-tipo-topo"><strong><?= $txt($pt['rotulo'] ?? '') ?></strong><span><?= $ptS ?>/<?= $ptT ?></span></div>
                            <div class="obra-progress mini"><div class="obra-progress-bar" style="width:<?= $ptT > 0 ? $pct($ptS / $ptT * 100) : '0.0' ?>%"></div></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ocorrências recentes -->
    <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-error-circle'></i> Ocorrências recentes (7 dias)</h5></div>
        <div class="widget-content nopadding ui-tabela-rolagem">
            <table class="table table-bordered acomp-tabela">
                <thead><tr><th>Data</th><th>Projeto</th><th>Responsável</th><th>Ocorrência</th></tr></thead>
                <tbody>
                    <?php if (empty($acomp['ocorrencias_recentes'])) echo '<tr><td colspan="4"><div class="ui-vazio"><i class="bx bx-error-circle"></i><strong>Nenhuma ocorrência nos últimos 7 dias.</strong></div></td></tr>'; ?>
                    <?php foreach ($acomp['ocorrencias_recentes'] as $oc): ?>
                        <tr>
                            <td data-label="Data"><?= $data_br($oc['data'] ?? null) ?></td>
                            <td data-label="Projeto"><a href="<?= html_escape(site_url('obras/visualizar/' . (int) $oc['obra_id']) . '#rdo') ?>"><?= $txt($oc['obra_nome'] ?? '') ?></a></td>
                            <td data-label="Responsável"><?= $txt($oc['responsavel'] ?? '') ?></td>
                            <td data-label="Ocorrência"><?= html_escape(mb_strimwidth(trim((string) ($oc['ocorrencias'] ?? '')), 0, 160, '...', 'UTF-8')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row-fluid">
        <!-- Obras em execução -->
        <div class="span7" style="margin-left:0">
            <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-loader-circle'></i> Projetos em execução</h5></div>
                <div class="widget-content nopadding ui-tabela-rolagem">
                    <table class="table table-bordered">
                        <thead><tr><th>Projeto</th><th>Cliente</th><th>Progresso</th><th></th></tr></thead>
                        <tbody>
                            <?php if (! $obras_execucao) echo '<tr><td colspan="4"><div class="ui-vazio"><i class="bx bx-loader-circle"></i><strong>Nenhum projeto em execução.</strong></div></td></tr>'; ?>
                            <?php foreach ($obras_execucao as $o): ?>
                                <tr>
                                    <td><?= html_escape($o->nome) ?></td>
                                    <td><?= html_escape($o->nomeCliente ?: '—') ?></td>
                                    <td style="min-width:120px">
                                        <div class="obra-progress mini"><div class="obra-progress-bar" style="width:<?= (float) $o->percentual_concluido ?>%"></div></div>
                                        <small><?= $fmt($o->percentual_concluido, 1) ?>%</small>
                                    </td>
                                    <td><a href="<?= site_url('obras/visualizar/' . $o->idObra) ?>" class="btn-nwe" title="Abrir" aria-label="Abrir projeto"><i class="bx bx-show bx-xs"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Medições a aprovar -->
        <div class="span5">
            <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-receipt'></i> Medições aguardando aprovação</h5></div>
                <div class="widget-content nopadding ui-tabela-rolagem">
                    <table class="table table-bordered">
                        <thead><tr><th>Projeto</th><th>Nº</th><th>Valor</th><th></th></tr></thead>
                        <tbody>
                            <?php if (! $medicoes_abertas) echo '<tr><td colspan="4"><div class="ui-vazio"><i class="bx bx-receipt"></i><strong>Nenhuma medição pendente.</strong></div></td></tr>'; ?>
                            <?php foreach ($medicoes_abertas as $m): ?>
                                <tr>
                                    <td><?= html_escape($m->obra_nome) ?></td>
                                    <td><?= (int) $m->numero ?></td>
                                    <td>R$ <?= $fmt($m->valor_medido) ?></td>
                                    <td><a href="<?= site_url('obras/visualizar/' . $m->obra_id) ?>#medicao" class="btn-nwe" title="Ver medição" aria-label="Ver medição"><i class="bx bx-right-arrow-alt bx-xs"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Registros de execução recentes -->
    <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-notepad'></i> Registros de execução recentes</h5></div>
        <div class="widget-content nopadding ui-tabela-rolagem">
            <table class="table table-bordered">
                <thead><tr><th>Data</th><th>Projeto</th><th>Nº</th><th>Condição</th><th>Responsável</th></tr></thead>
                <tbody>
                    <?php if (! $rdos_recentes) echo '<tr><td colspan="5"><div class="ui-vazio"><i class="bx bx-notepad"></i><strong>Nenhum registro de execução.</strong></div></td></tr>'; ?>
                    <?php foreach ($rdos_recentes as $r): ?>
                        <tr>
                            <td><?= $data_br($r->data) ?></td>
                            <td><a href="<?= site_url('obras/visualizar/' . $r->obra_id) ?>#rdo"><?= html_escape($r->obra_nome) ?></a></td>
                            <td><?= (int) $r->numero ?></td>
                            <td><?= html_escape(['praticavel' => 'Normal', 'parcial' => 'Impedimento parcial', 'impraticavel' => 'Sem acesso'][$r->condicao] ?? $r->condicao) ?></td>
                            <td><?= html_escape($r->responsavel) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
