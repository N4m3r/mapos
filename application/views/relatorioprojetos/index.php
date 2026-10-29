<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css">
<?php
// Relatorio de acompanhamento de projetos - ADR-012
$e = fn ($v) => html_escape((string) ($v ?? ''));
$fmtData = fn ($d) => (! empty($d) && $d !== '0000-00-00') ? date('d/m/Y', strtotime($d)) : '—';
$fmtNum = fn ($v, $d = 0) => number_format((float) $v, $d, ',', '.');
$fmtQtd = function ($v) {
    $s = number_format((float) $v, 3, ',', '.');
    return rtrim(rtrim($s, '0'), ',');
};
$statusInfo = [
    'planejamento' => ['Planejamento', 'neutro'],
    'em_execucao' => ['Em execução', 'info'],
    'paralisada' => ['Paralisada', 'alerta'],
    'concluida' => ['Concluída', 'sucesso'],
    'entregue' => ['Entregue', 'sucesso'],
    'cancelada' => ['Cancelada', 'perigo'],
];
$etapaRotulo = ['pendente' => 'Pendente', 'em_andamento' => 'Em andamento', 'concluida' => 'Concluída', 'paralisada' => 'Paralisada'];
$fmtDataHora = fn ($d) => (! empty($d)) ? date('d/m/Y H:i', strtotime($d)) : '—';
$condicaoRotulo = ['praticavel' => 'Normal', 'parcial' => 'Impedimento parcial', 'impraticavel' => 'Sem acesso'];
// Classe do ui-selo de cada status de ponto (ADR-013, Obra_pontos_model::statusLista)
$classeStatusPonto = ['previsto' => 'neutro', 'infra' => 'info', 'cabeado' => 'info', 'instalado' => 'alerta', 'configurado' => 'alerta', 'testado' => 'sucesso'];
$tiposRotulo = ['diario' => 'Dia', 'semanal' => 'Semana', 'mensal' => 'Mês'];
$tot = $relatorio['totais'];
$fotosLimite = 6;

$obraNome = 'Todos';
foreach ($obras as $o) {
    if ((int) $filtro['obra_id'] === (int) $o->idObra) {
        $obraNome = $o->nome;
    }
}
$usuarioNome = 'Todos';
foreach ($usuarios as $u) {
    if ((int) $filtro['usuario_id'] === (int) $u->idUsuarios) {
        $usuarioNome = $u->nome;
    }
}

// Monta links do proprio relatorio mantendo os demais filtros.
$base = [
    'tipo' => $filtro['tipo'],
    'data' => $filtro['data'],
    'obra_id' => $filtro['obra_id'],
    'usuario_id' => $filtro['usuario_id'],
];
$link = function (array $over = []) use ($base) {
    $q = array_filter(array_merge($base, $over), fn ($v) => $v !== null && $v !== '');
    return site_url('relatorioprojetos') . ($q ? '?' . http_build_query($q) : '');
};
?>

<style>
    .so-impressao { display: none; }
    .rel-periodos { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; flex: 1 1 100%; }
    .rel-periodos .btn.ativo { box-shadow: inset 0 0 0 2px currentColor; font-weight: 700; }
    .rel-setas { display: flex; gap: 6px; align-items: center; }
    .rel-bloco, .rel-projeto { margin-bottom: 18px; border: 1px solid var(--ui-borda); border-radius: var(--ui-raio); overflow: hidden; }
    .rel-bloco-titulo, .rel-projeto-cab { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; align-items: flex-start; padding: 10px 14px; border-bottom: 1px solid var(--ui-borda); }
    .rel-bloco-titulo h5, .rel-projeto-cab h5 { margin: 0; font-size: 16px; }
    .rel-projeto-cab p { margin: 2px 0 0; font-size: 12px; color: var(--ui-texto-2); }
    .rel-projeto-corpo { padding: 12px 14px; }
    .rel-progresso { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .rel-progresso .obra-progress { flex: 1 1 auto; }
    .rel-totais { display: flex; flex-wrap: wrap; gap: 6px 16px; font-size: 12px; color: var(--ui-texto-2); margin-bottom: 10px; }
    .rel-totais strong { color: inherit; font-size: 14px; }
    .rel-sub { font-size: 13px; margin: 14px 0 6px; }
    .rel-registro { break-inside: avoid; page-break-inside: avoid; }
    .rel-ocorrencia { color: #b07800; font-weight: 600; }
    .rel-ocorrencia-destaque { background: rgba(232, 163, 23, .14); border-left: 3px solid #b07800; padding: 4px 6px; border-radius: 4px; }
    .rel-itens { font-size: 12px; color: var(--ui-texto-2); margin-top: 4px; }
    .rel-fotos { display: flex; flex-wrap: wrap; gap: 4px; align-items: center; }
    .rel-foto { width: 56px; height: 56px; object-fit: cover; border-radius: 4px; border: 1px solid var(--ui-borda); display: block; }
    .rel-mais { font-size: 12px; font-weight: 600; color: var(--ui-texto-2); }
    .rel-fotos-cont { font-size: 12px; font-weight: 600; color: var(--ui-texto-2); white-space: nowrap; margin-left: 6px; }
    .rel-resumo { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; }
    .rel-resumo ul { list-style: none; margin: 0; padding: 0; font-size: 12px; }
    .rel-resumo li { padding: 3px 0; border-bottom: 1px dashed var(--ui-borda); display: flex; justify-content: space-between; gap: 8px; }
    .rel-alerta { border: 1px solid #e5484d; background: rgba(229, 72, 77, .08); border-radius: var(--ui-raio); padding: 12px 14px; margin-bottom: 18px; }
    .rel-alerta h5 { margin: 0 0 6px; font-size: 15px; color: var(--ui-perigo); display: flex; gap: 6px; align-items: center; }
    .rel-alerta ul { margin: 0; padding-left: 18px; font-size: 13px; }
    .rel-direita { text-align: right; }
    .rel-pontos, .rel-avancos { break-inside: avoid; page-break-inside: avoid; }
    .rel-pontos-lista { list-style: none; margin: 0 0 6px; padding: 0; font-size: 12px; }
    .rel-pontos-lista li { display: grid; grid-template-columns: minmax(140px, 1fr) auto; gap: 4px 10px; padding: 4px 0; border-bottom: 1px dashed var(--ui-borda); }
    .rel-pontos-lista .obra-progress { grid-column: 1 / -1; }
    .rel-pontos-valor { font-weight: 600; }

    @media print {
        #sidebar, #header, #user-nav, #userr, #footer, #breadcrumb, .breadcrumb, .ui-filtros, .ui-acoes, .ui-dica,
        .obra-tabs, .obra-mainnav, .ui-cabecalho, button, .btn { display: none !important; }
        a[href]:after { content: none !important; }
        html body #content, html body .container-fluid, .new122 { margin: 0 !important; padding: 0 !important; left: 0 !important; position: static !important; width: auto !important; max-width: none !important; }
        html body { padding: 0 !important; margin: 0 !important; }
        @page { size: A4; margin: 10mm; }
        .so-impressao { display: block !important; margin-bottom: 12px; }
        .so-impressao h2 { margin: 0 0 4px; font-size: 18px; color: #000 !important; }
        .so-impressao p { margin: 0; font-size: 12px; color: #000 !important; }
        body, #content, #content-header, .container-fluid, .row-fluid, .span12 {
            background: #fff !important; color: #000 !important; margin: 0 !important; padding: 0 !important;
            width: 100% !important; min-height: 0 !important;
        }
        #content { margin-left: 0 !important; }
        * { box-shadow: none !important; text-shadow: none !important; transition: none !important; }
        a { color: #000 !important; text-decoration: none; }
        .ui-kpis { display: flex; flex-wrap: wrap; gap: 6px; }
        .ui-kpi { flex: 1 1 110px; border: 1px solid #000 !important; }
        .ui-kpi, .ui-kpi * { color: #000 !important; background: #fff !important; }
        .rel-bloco, .rel-projeto { border: none !important; margin-bottom: 14px; break-inside: avoid; page-break-inside: avoid; }
        .rel-projeto-cab, .rel-bloco-titulo { border-bottom: 1px solid #000 !important; padding-left: 0; padding-right: 0; }
        .rel-projeto-cab *, .rel-bloco-titulo * { color: #000 !important; }
        .rel-projeto-corpo { padding: 0; }
        .rel-pontos, .rel-avancos { break-inside: avoid; page-break-inside: avoid; }
        .rel-registro { break-inside: avoid; page-break-inside: avoid; }
        .rel-alerta { border: 1px solid #000 !important; background: #fff !important; }
        .rel-alerta * { color: #000 !important; }
        .rel-foto { width: 40px; height: 40px; }
        .ui-tabela-rolagem { overflow: visible !important; max-height: none !important; }
        table, th, td { border: 1px solid #000 !important; color: #000 !important; background: #fff !important; }
        table { border-collapse: collapse !important; width: 100% !important; font-size: 11px; }
        .ui-selo { background: #fff !important; color: #000 !important; border: 1px solid #000 !important; }
        .rel-ocorrencia-destaque { background: #fff !important; border-left: 3px solid #000 !important; }
        .ui-vazio { color: #000 !important; }
    }
</style>

<?php $this->load->view('obras/_nav'); ?>

<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="so-impressao">
            <h2>Relatório de acompanhamento · <?= $e($periodo['rotulo']) ?></h2>
            <p>
                Projeto: <?= $e($obraNome) ?> |
                Técnico: <?= $e($usuarioNome) ?> |
                Período: <?= $e($tiposRotulo[$periodo['tipo']] ?? '') ?>
            </p>
        </div>

        <div class="ui-cabecalho">
            <div>
                <h1 class="ui-titulo"><i class="bx bx-file"></i> Relatório de acompanhamento</h1>
                <p class="ui-subtitulo"><?= $e($periodo['rotulo']) ?></p>
            </div>
            <div class="ui-acoes">
                <button type="button" class="button btn btn-mini btn-inverse" data-acao="imprimir"><span class="button__icon"><i class="bx bx-printer"></i></span><span class="button__text">Imprimir</span></button>
            </div>
        </div>

        <form action="<?= site_url('relatorioprojetos') ?>" method="get" class="ui-filtros">
            <input type="hidden" name="tipo" value="<?= $e($filtro['tipo']) ?>" />
            <div class="rel-periodos">
                <?php foreach ($tiposRotulo as $chave => $rotulo) : ?>
                    <a class="btn <?= $filtro['tipo'] === $chave ? 'btn-inverse ativo' : '' ?>" href="<?= $e($link(['tipo' => $chave])) ?>"><?= $e($rotulo) ?></a>
                <?php endforeach; ?>
                <span class="rel-setas">
                    <a class="btn" href="<?= $e($link(['data' => $periodo['anterior']])) ?>" title="Período anterior">&lsaquo;</a>
                    <a class="btn" href="<?= $e($link(['data' => $periodo['proximo']])) ?>" title="Próximo período">&rsaquo;</a>
                </span>
            </div>
            <div class="ui-campo">
                <label for="data">Data de referência</label>
                <input type="date" id="data" name="data" value="<?= $e($filtro['data']) ?>" />
            </div>
            <div class="ui-campo">
                <label for="obra_id">Projeto</label>
                <select id="obra_id" name="obra_id">
                    <option value="">Todos</option>
                    <?php foreach ($obras as $o) : ?>
                        <option value="<?= (int) $o->idObra ?>" <?= (int) $filtro['obra_id'] === (int) $o->idObra ? 'selected' : '' ?>><?= $e($o->nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ui-campo">
                <label for="usuario_id">Técnico</label>
                <select id="usuario_id" name="usuario_id">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u) : ?>
                        <option value="<?= (int) $u->idUsuarios ?>" <?= (int) $filtro['usuario_id'] === (int) $u->idUsuarios ? 'selected' : '' ?>><?= $e($u->nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ui-campo ui-campo-acao">
                <button class="button btn btn-inverse"><span class="button__icon"><i class="bx bx-filter-alt"></i></span><span class="button__text">Filtrar</span></button>
                <a class="btn" href="<?= site_url('relatorioprojetos') ?>" title="Limpar filtros"><i class="bx bx-x"></i> Limpar</a>
            </div>
        </form>

        <div class="ui-kpis">
            <div class="ui-kpi info"><div class="ui-kpi-rotulo">Projetos</div><div class="ui-kpi-valor"><?= $fmtNum($tot['projetos']) ?></div><div class="ui-kpi-extra">com registro no período</div></div>
            <div class="ui-kpi"><div class="ui-kpi-rotulo">Registros</div><div class="ui-kpi-valor"><?= $fmtNum($tot['registros']) ?></div><div class="ui-kpi-extra">registros de execução no período</div></div>
            <div class="ui-kpi"><div class="ui-kpi-rotulo">Fotos</div><div class="ui-kpi-valor"><?= $fmtNum($tot['fotos']) ?></div></div>
            <div class="ui-kpi <?= (int) $tot['ocorrencias'] > 0 ? 'alerta' : '' ?>"><div class="ui-kpi-rotulo">Ocorrências</div><div class="ui-kpi-valor"><?= $fmtNum($tot['ocorrencias']) ?></div></div>
            <div class="ui-kpi"><div class="ui-kpi-rotulo">Horas</div><div class="ui-kpi-valor"><?= $fmtNum($tot['horas'], 1) ?></div><div class="ui-kpi-extra">apontadas</div></div>
            <div class="ui-kpi"><div class="ui-kpi-rotulo">Técnicos</div><div class="ui-kpi-valor"><?= $fmtNum($tot['tecnicos']) ?></div></div>
            <div class="ui-kpi"><div class="ui-kpi-rotulo">Avanços</div><div class="ui-kpi-valor"><?= $fmtNum($tot['avancos'] ?? 0) ?></div><div class="ui-kpi-extra">pontos avançados</div></div>
            <div class="ui-kpi"><div class="ui-kpi-rotulo">Testados</div><div class="ui-kpi-valor"><?= $fmtNum($tot['testados'] ?? 0) ?></div><div class="ui-kpi-extra">pontos certificados</div></div>
            <div class="ui-kpi"><div class="ui-kpi-rotulo">Cabo (m)</div><div class="ui-kpi-valor"><?= $fmtNum($tot['cabo_m'] ?? 0, 1) ?></div><div class="ui-kpi-extra">metros passados</div></div>
            <div class="ui-kpi"><div class="ui-kpi-rotulo">Repasses</div><div class="ui-kpi-valor"><?= $fmtNum($tot['repasses'] ?? 0) ?></div><div class="ui-kpi-extra">pontos repassados entre técnicos</div></div>
            <div class="ui-kpi"><div class="ui-kpi-rotulo">Tempo médio até configurar</div><div class="ui-kpi-valor"><?= ($tot['tempo_medio_config_h'] ?? null) === null ? '—' : $fmtNum($tot['tempo_medio_config_h'], 1) . ' h' ?></div><div class="ui-kpi-extra">da instalação à configuração</div></div>
        </div>

        <?php if (! empty($relatorio['sem_registro'])) : ?>
            <div class="rel-alerta">
                <h5><i class="bx bx-error"></i> Projetos em execução sem registro no período</h5>
                <ul>
                    <?php foreach ($relatorio['sem_registro'] as $sr) : ?>
                        <li>
                            <a href="<?= site_url('obras/visualizar/' . (int) $sr['idObra']) ?>"><?= $e($sr['nome']) ?></a>
                            <?php if (! empty($sr['cliente'])) : ?> — <?= $e($sr['cliente']) ?><?php endif; ?>
                            — último registro: <?= $fmtData($sr['ultimo_registro'] ?? null) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (! empty($relatorio['tecnicos'])) : ?>
            <div class="rel-bloco">
                <div class="rel-bloco-titulo"><h5>Por técnico</h5></div>
                <div class="ui-tabela-rolagem">
                    <table class="table table-bordered">
                        <thead>
                            <tr><th>Técnico</th><th>Registros</th><th>Avanços</th><th>Projetos</th><th>Dias</th><th>Último registro</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relatorio['tecnicos'] as $t) : ?>
                                <tr>
                                    <td><?= $e($t['nome']) ?></td>
                                    <td><?= $fmtNum($t['registros']) ?></td>
                                    <td><?= $fmtNum($t['avancos'] ?? 0) ?></td>
                                    <td><?= $fmtNum($t['projetos']) ?></td>
                                    <td><?= $fmtNum($t['dias']) ?></td>
                                    <td><?= $fmtData($t['ultimo'] ?? null) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($relatorio['projetos']) && empty($relatorio['sem_registro'])) : ?>
            <div class="widget-box">
                <div class="ui-vazio"><i class="bx bx-calendar-x"></i><strong>Nenhum registro neste período</strong>Escolha outro período ou ajuste os filtros.</div>
            </div>
        <?php endif; ?>

        <?php foreach ($relatorio['projetos'] as $proj) :
            $o = $proj['obra'];
            $t = $proj['totais'];
            $st = $statusInfo[$o['status']] ?? [$o['status'], 'neutro'];
            $perc = max(0, min(100, (float) $o['percentual_concluido']));
            ?>
            <section class="rel-projeto">
                <div class="rel-projeto-cab">
                    <div>
                        <h5><a href="<?= site_url('obras/visualizar/' . (int) $o['idObra']) ?>"><?= $e($o['nome']) ?></a><?php if (! empty($o['codigo'])) : ?> <small>#<?= $e($o['codigo']) ?></small><?php endif; ?></h5>
                        <p>
                            Cliente: <?= $e($o['cliente'] ?? '—') ?> |
                            Responsável: <?= $e($o['responsavel'] ?? '—') ?> |
                            Previsão de término: <?= $fmtData($o['data_fim_prevista'] ?? null) ?>
                        </p>
                    </div>
                    <div><span class="ui-selo <?= $e($st[1]) ?>"><?= $e($st[0]) ?></span></div>
                </div>

                <div class="rel-projeto-corpo">
                    <div class="rel-progresso">
                        <div class="obra-progress"><div class="obra-progress-bar" style="width: <?= $perc ?>%"></div></div>
                        <strong><?= $fmtNum($perc, 1) ?>%</strong>
                    </div>

                    <div class="rel-totais">
                        <span>Registros: <strong><?= $fmtNum($t['registros']) ?></strong></span>
                        <span>Fotos: <strong><?= $fmtNum($t['fotos']) ?></strong></span>
                        <span>Ocorrências: <strong><?= $fmtNum($t['ocorrencias']) ?></strong></span>
                        <span>Horas: <strong><?= $fmtNum($t['horas'], 1) ?></strong></span>
                        <span>Dias com registro: <strong><?= $fmtNum($t['dias_com_registro']) ?></strong></span>
                        <span>Técnicos: <strong><?= $fmtNum($t['tecnicos']) ?></strong></span>
                        <span>Avanços: <strong><?= $fmtNum($t['avancos'] ?? 0) ?></strong></span>
                        <span>Testados: <strong><?= $fmtNum($t['testados'] ?? 0) ?></strong></span>
                        <span>Cabo: <strong><?= $fmtNum($t['cabo_m'] ?? 0, 1) ?> m</strong></span>
                    </div>

                    <?php
                    $pontos = $proj['pontos'] ?? [];
                    $porTipo = $pontos['por_tipo'] ?? [];
                    $avancos = $proj['avancos'] ?? [];
                    ?>
                    <div class="rel-pontos">
                        <h6 class="rel-sub">Pontos: <?= $fmtNum($pontos['por_status']['testado'] ?? 0) ?> de <?= $fmtNum($pontos['total'] ?? 0) ?> testados</h6>
                        <?php if ($porTipo) : ?>
                            <ul class="rel-pontos-lista">
                                <?php foreach ($porTipo as $pt) :
                                    $pctTipo = ((int) $pt['total']) > 0 ? (int) round((int) $pt['testados'] / (int) $pt['total'] * 100) : 0;
                                    ?>
                                    <li>
                                        <span><?= $e($pt['rotulo'] ?? '') ?></span>
                                        <span class="rel-pontos-valor"><?= $fmtNum($pt['testados']) ?>/<?= $fmtNum($pt['total']) ?> testados</span>
                                        <div class="obra-progress"><div class="obra-progress-bar" style="width: <?= $pctTipo ?>%"></div></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <div class="rel-itens">Nenhum ponto cadastrado neste projeto.</div>
                        <?php endif; ?>
                    </div>

                    <?php if ($avancos) : ?>
                        <div class="rel-avancos">
                            <h6 class="rel-sub">Avanços nos pontos no período</h6>
                            <div class="ui-tabela-rolagem">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr><th>Data/hora</th><th>Ponto</th><th>Tipo</th><th>Local</th><th>De → Para</th><th>Técnico</th><th>Metros</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($avancos as $av) : ?>
                                            <tr>
                                                <td><?= $e($fmtDataHora($av['data'])) ?></td>
                                                <td>
                                                    <?= $e($av['identificacao']) ?>
                                                    <?php if ((int) ($av['fotos'] ?? 0) > 0) : ?>
                                                        <span class="rel-fotos-cont" title="Fotos do ponto neste avanço"><i class="bx bx-camera"></i> <?= $fmtNum($av['fotos']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $e($av['tipo_rotulo']) ?></td>
                                                <td><?= $e($av['local']) !== '' ? $e($av['local']) : '—' ?></td>
                                                <td>
                                                    <?php if (($av['evento'] ?? 'status') === 'repasse') : ?>
                                                        <span class="ui-selo info">Repassado para <?= $e(($av['destinatario'] ?? '') !== '' ? $av['destinatario'] : 'Equipe') ?></span>
                                                    <?php elseif (($av['evento'] ?? 'status') === 'assumiu') : ?>
                                                        <span class="ui-selo neutro">Assumiu</span>
                                                    <?php else : ?>
                                                        <span class="ui-selo <?= $e($classeStatusPonto[$av['de']] ?? 'neutro') ?>"><?= $e($av['de_rotulo']) ?></span>
                                                        →
                                                        <span class="ui-selo <?= $e($classeStatusPonto[$av['para']] ?? 'neutro') ?>"><?= $e($av['para_rotulo']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $e($av['usuario']) !== '' ? $e($av['usuario']) : '—' ?></td>
                                                <td><?= $av['metragem_cabo'] > 0 ? $fmtNum($av['metragem_cabo'], 1) . ' m' : '—' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="ui-tabela-rolagem">
                        <table class="table table-bordered">
                            <thead>
                                <tr><th>Data</th><th>Nº</th><th>Responsável</th><th>Atividades</th><th>Ocorrências</th><th>Materiais e serviços</th><th>Fotos</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proj['registros'] as $r) : ?>
                                    <tr class="rel-registro">
                                        <td><?= $fmtData($r['data']) ?></td>
                                        <td><?= $e($r['numero']) ?></td>
                                        <td>
                                            <?= $e($r['responsavel'] ?? '—') ?>
                                            <div class="rel-itens">
                                                <?php if (! empty($r['condicao'])) : ?>Condição: <?= $e($condicaoRotulo[$r['condicao']] ?? $r['condicao']) ?><br><?php endif; ?>
                                                Técnicos no local: <?= $fmtNum($r['efetivo_total']) ?>
                                            </div>
                                        </td>
                                        <td><?= nl2br($e($r['atividades'])) ?></td>
                                        <td>
                                            <?php if (trim((string) ($r['ocorrencias'] ?? '')) !== '') : ?>
                                                <div class="rel-ocorrencia-destaque rel-ocorrencia"><?= nl2br($e($r['ocorrencias'])) ?></div>
                                            <?php else : ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php foreach ($r['materiais'] as $m) : ?>
                                                <div>Material: <?= $e($m['descricao']) ?> (<?= $fmtQtd($m['quantidade']) ?>)</div>
                                            <?php endforeach; ?>
                                            <?php foreach ($r['servicos'] as $sv) : ?>
                                                <div>Serviço: <?= $e($sv['descricao']) ?> (<?= $fmtQtd($sv['quantidade']) ?>)</div>
                                            <?php endforeach; ?>
                                            <?php if (empty($r['materiais']) && empty($r['servicos'])) : ?>—<?php endif; ?>
                                        </td>
                                        <td>
                                            <?php $fotos = $r['fotos'] ?? []; ?>
                                            <?php if ($fotos) : ?>
                                                <div class="rel-fotos">
                                                    <?php foreach (array_slice($fotos, 0, $fotosLimite) as $fid) : ?>
                                                        <a href="<?= site_url('obras/fotoRdo/' . (int) $fid) ?>" target="_blank" rel="noopener">
                                                            <img class="rel-foto" src="<?= site_url('obras/fotoRdo/' . (int) $fid) ?>" loading="lazy" alt="Foto do registro" />
                                                        </a>
                                                    <?php endforeach; ?>
                                                    <?php if (count($fotos) > $fotosLimite) : ?>
                                                        <span class="rel-mais">+<?= count($fotos) - $fotosLimite ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else : ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="rel-resumo">
                        <div>
                            <h6 class="rel-sub">Materiais no período</h6>
                            <?php if ($proj['materiais']) : ?>
                                <ul>
                                    <?php foreach ($proj['materiais'] as $m) : ?>
                                        <li><span><?= $e($m['descricao']) ?></span><strong><?= $fmtQtd($m['quantidade']) ?></strong></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <div class="rel-itens">Nenhum material lançado.</div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h6 class="rel-sub">Serviços no período</h6>
                            <?php if ($proj['servicos']) : ?>
                                <ul>
                                    <?php foreach ($proj['servicos'] as $sv) : ?>
                                        <li><span><?= $e($sv['descricao']) ?></span><strong><?= $fmtQtd($sv['quantidade']) ?></strong></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <div class="rel-itens">Nenhum serviço lançado.</div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h6 class="rel-sub">Horas por pessoa</h6>
                            <?php if ($proj['horas']) : ?>
                                <ul>
                                    <?php foreach ($proj['horas'] as $h) : ?>
                                        <li><span><?= $e($h['nome']) ?></span><strong><?= $fmtNum($h['horas'], 1) ?> h</strong></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <div class="rel-itens">Nenhuma hora apontada.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($proj['etapas']) : ?>
                        <h6 class="rel-sub">Etapas</h6>
                        <div class="ui-tabela-rolagem">
                            <table class="table table-bordered">
                                <thead>
                                    <tr><th>Etapa</th><th>Concluído</th><th>Situação</th><th>Fim previsto</th><th>Atraso</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proj['etapas'] as $et) : ?>
                                        <tr>
                                            <td><?= $e($et['nome']) ?></td>
                                            <td><?= $fmtNum($et['percentual_concluido'], 1) ?>%</td>
                                            <td><?= $e($etapaRotulo[$et['status']] ?? $et['status']) ?></td>
                                            <td><?= $fmtData($et['data_fim_prevista'] ?? null) ?></td>
                                            <td><?php if (! empty($et['atrasada'])) : ?><span class="ui-selo perigo">Atrasada</span><?php else : ?>—<?php endif; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if ($proj['os']) : ?>
                        <h6 class="rel-sub">OS vinculadas</h6>
                        <ul class="rel-itens">
                            <?php foreach ($proj['os'] as $os) : ?>
                                <li>
                                    <a href="<?= site_url('os/visualizar/' . (int) $os['idOs']) ?>">OS #<?= (int) $os['idOs'] ?></a>
                                    — <?= $e($os['status']) ?><?php if (! empty($os['descricao'])) : ?> — <?= $e($os['descricao']) ?><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</div>
