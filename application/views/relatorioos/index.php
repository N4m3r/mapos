<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css">
<link rel="stylesheet" href="<?= base_url('assets/css/relatorios-escuro.css') ?>?v=<?= asset_versao('assets/css/relatorios-escuro.css') ?>">
<?php
// Relatorio de OS realizadas (mesmo molde do relatorio de projetos)
$e = fn ($v) => html_escape((string) ($v ?? ''));
$fmtNum = fn ($v, $d = 0) => number_format((float) $v, $d, ',', '.');
$fmtDataHora = fn ($d) => (! empty($d)) ? date('d/m/Y H:i', strtotime($d)) : '—';
$tiposRotulo = ['diario' => 'Dia', 'semanal' => 'Semana', 'mensal' => 'Mês'];
$tot = $relatorio['totais'];

$usuarioNome = 'Todos';
foreach ($usuarios as $u) {
    if ((int) $filtro['usuario_id'] === (int) $u->idUsuarios) {
        $usuarioNome = $u->nome;
    }
}
$clienteNome = 'Todos';
foreach ($clientes as $c) {
    if ((int) $filtro['cliente_id'] === (int) $c->idClientes) {
        $clienteNome = $c->nomeCliente;
    }
}
$statusNome = $filtro['status'] ?? 'Todos';

$base = [
    'tipo' => $filtro['tipo'],
    'data' => $filtro['data'],
    'usuario_id' => $filtro['usuario_id'],
    'cliente_id' => $filtro['cliente_id'],
    'status' => $filtro['status'],
    'fotos' => $fotosModo !== '12' ? $fotosModo : null,
];
$link = function (array $over = []) use ($base) {
    $q = array_filter(array_merge($base, $over), fn ($v) => $v !== null && $v !== '');
    return site_url('relatorioos') . ($q ? '?' . http_build_query($q) : '');
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

    .rel-fotos-secao { margin-top: 14px; }
    .rel-fotos-cab { break-inside: avoid; page-break-inside: avoid; break-after: avoid; page-break-after: avoid; }
    .rel-fotos-grade { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .rel-fotos-item { margin: 0; break-inside: avoid; page-break-inside: avoid; font-size: 11px; line-height: 1.3; }
    .rel-fotos-item a { display: block; }
    .rel-fotos-img { width: 100%; height: 150px; object-fit: cover; border-radius: 4px; border: 1px solid var(--ui-borda); display: block; background: #eee; }
    .rel-fotos-item figcaption { margin-top: 3px; }
    .rel-fotos-item figcaption strong { display: block; }
    .rel-fotos-item figcaption span { color: var(--ui-texto-2); }
    @media (max-width: 900px) { .rel-fotos-grade { grid-template-columns: repeat(2, 1fr); } }

    /* Painel do periodo (graficos SVG) */
    .pn { margin-bottom: 18px; color: #0b0b0b; }
    .pn-titulo { font-size: 16px; margin: 0 0 10px; }
    .pn-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-bottom: 12px; }
    .pn-tile { background: #fcfcfb; border: 1px solid #e1e0d9; border-radius: var(--ui-raio); padding: 10px 12px; break-inside: avoid; page-break-inside: avoid; }
    .pn-tile-rot { font-size: 12px; color: #52514e; }
    .pn-tile-valor { font-size: 26px; font-weight: 600; line-height: 1.2; }
    .pn-tile-delta { font-size: 12px; color: #52514e; }
    .pn-tile-delta.pn-atencao { color: #8a5a00; font-weight: 600; }
    .pn-grade { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(520px, 100%), 1fr)); gap: 12px; }
    .pn-larga { grid-column: 1 / -1; }
    .pn-fig { margin: 0; background: #fcfcfb; border: 1px solid #e1e0d9; border-radius: var(--ui-raio); padding: 10px 12px; break-inside: avoid; page-break-inside: avoid; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .pn-fig figcaption { font-size: 13px; font-weight: 600; margin-bottom: 6px; }
    /* graficos desenhados em 640px de largura: nunca ampliar (o texto cresceria junto) */
    .pn-fig svg { display: block; width: 100%; max-width: 640px; height: auto; overflow: visible; }
    .pn-tabela { margin-top: 6px; font-size: 12px; }
    .pn-tabela summary { cursor: pointer; color: #52514e; }
    .pn-tabela table { margin-top: 6px; font-size: 12px; }
    .gsvg-marca { outline: none; }
    .gsvg-marca:hover .gsvg-barra, .gsvg-marca:focus .gsvg-barra, .gsvg-marca.gsvg-ativa .gsvg-barra { filter: brightness(1.12); }
    .gsvg-marca:focus-visible .gsvg-alvo { stroke: #0b0b0b; stroke-width: 2; }
    .gsvg-tip { position: absolute; z-index: 9999; pointer-events: none; background: #fcfcfb; border: 1px solid #c3c2b7; border-radius: 6px; padding: 6px 9px; box-shadow: 0 2px 8px rgba(0, 0, 0, .15); font-size: 12px; color: #52514e; max-width: 260px; }
    .gsvg-tip-t { font-weight: 600; color: #0b0b0b; }
    @media print { .gsvg-tip, .pn-tabela { display: none !important; } }

    @media print {
        #sidebar, #header, #user-nav, #userr, #footer, #breadcrumb, .breadcrumb, .ui-filtros, .ui-acoes, .ui-dica,
        .obra-tabs, .obra-mainnav, .projeto-nav, .ui-cabecalho, button, .btn { display: none !important; }
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
        .pn, .pn * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .pn-tile, .pn-tile *, .pn-fig { background: #fcfcfb !important; }
        .pn-tile { border: 1px solid #c3c2b7 !important; }
        .pn-tiles { display: grid !important; grid-template-columns: repeat(8, 1fr); gap: 4px; }
        .pn-tile-valor { font-size: 18px; }
        .pn-grade { display: block; }
        .pn-fig { margin-bottom: 8px; border: 1px solid #c3c2b7 !important; }
        .pn-titulo { color: #000 !important; }
        .rel-pontos, .rel-avancos { break-inside: avoid; page-break-inside: avoid; }
        .rel-registro { break-inside: avoid; page-break-inside: avoid; }
        .rel-alerta { border: 1px solid #000 !important; background: #fff !important; }
        .rel-alerta * { color: #000 !important; }
        .rel-foto { width: 40px; height: 40px; }
        .rel-fotos-secao { display: block !important; }
        .rel-fotos-grade { display: grid !important; grid-template-columns: repeat(3, 1fr); gap: 6px; }
        .rel-fotos-item { break-inside: avoid; page-break-inside: avoid; }
        .rel-fotos-img { height: 4.5cm; object-fit: cover; -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #000; }
        .rel-fotos-item figcaption, .rel-fotos-item figcaption * { color: #000 !important; }
        .rel-fotos-cab { break-inside: avoid; page-break-inside: avoid; break-after: avoid; page-break-after: avoid; }
        .ui-tabela-rolagem { overflow: visible !important; max-height: none !important; }
        table, th, td { border: 1px solid #000 !important; color: #000 !important; background: #fff !important; }
        table { border-collapse: collapse !important; width: 100% !important; font-size: 11px; }
        .ui-selo { background: #fff !important; color: #000 !important; border: 1px solid #000 !important; }
        .rel-ocorrencia-destaque { background: #fff !important; border-left: 3px solid #000 !important; }
        .ui-vazio { color: #000 !important; }
    }
</style>

<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="so-impressao">
            <h2>Relatório de OS realizadas · <?= $e($periodo['rotulo']) ?></h2>
            <p>
                Técnico: <?= $e($usuarioNome) ?> |
                Cliente: <?= $e($clienteNome) ?> |
                Status: <?= $e($statusNome) ?> |
                Período: <?= $e($tiposRotulo[$periodo['tipo']] ?? '') ?>
            </p>
        </div>

        <div class="ui-cabecalho">
            <div>
                <h1 class="ui-titulo"><i class="bx bx-file"></i> Relatório de OS realizadas</h1>
                <p class="ui-subtitulo"><?= $e($periodo['rotulo']) ?></p>
            </div>
            <div class="ui-acoes">
                <button type="button" class="button btn btn-mini btn-inverse" data-acao="imprimir"><span class="button__icon"><i class="bx bx-printer"></i></span><span class="button__text">Imprimir</span></button>
            </div>
        </div>

        <form action="<?= site_url('relatorioos') ?>" method="get" class="ui-filtros">
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
                <label for="usuario_id">Técnico</label>
                <select id="usuario_id" name="usuario_id">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u) : ?>
                        <option value="<?= (int) $u->idUsuarios ?>" <?= (int) $filtro['usuario_id'] === (int) $u->idUsuarios ? 'selected' : '' ?>><?= $e($u->nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ui-campo">
                <label for="cliente_id">Cliente</label>
                <select id="cliente_id" name="cliente_id">
                    <option value="">Todos</option>
                    <?php foreach ($clientes as $c) : ?>
                        <option value="<?= (int) $c->idClientes ?>" <?= (int) $filtro['cliente_id'] === (int) $c->idClientes ? 'selected' : '' ?>><?= $e($c->nomeCliente) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ui-campo">
                <label for="status">Status da OS</label>
                <select id="status" name="status">
                    <option value="">Todos</option>
                    <?php foreach ($statusLista as $s) : ?>
                        <option value="<?= $e($s) ?>" <?= ($filtro['status'] ?? null) === $s ? 'selected' : '' ?>><?= $e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ui-campo">
                <label for="fotos">Fotos</label>
                <select id="fotos" name="fotos">
                    <option value="12" <?= $fotosModo === '12' ? 'selected' : '' ?>>até 12 por OS</option>
                    <option value="todas" <?= $fotosModo === 'todas' ? 'selected' : '' ?>>todas</option>
                    <option value="0" <?= $fotosModo === '0' ? 'selected' : '' ?>>não mostrar</option>
                </select>
            </div>
            <div class="ui-campo ui-campo-acao">
                <button class="button btn btn-inverse"><span class="button__icon"><i class="bx bx-filter-alt"></i></span><span class="button__text">Filtrar</span></button>
                <a class="btn" href="<?= site_url('relatorioos') ?>" title="Limpar filtros"><i class="bx bx-x"></i> Limpar</a>
            </div>
        </form>

        <?php
        $dashboard = $dashboard ?? [];
        if (! empty($dashboard) && ! empty($dashboard['kpis'])) :
            require_once APPPATH . 'libraries/Grafico_svg.php';
            $gn = fn ($v, $d = 0) => Grafico_svg::num($v, $d);
            $dk = $dashboard['kpis'];
            $dka = $dashboard['kpis_anterior'] ?? [];
            $vsRotulo = ['diario' => 'dia anterior', 'semanal' => 'semana anterior', 'mensal' => 'mês anterior'][$periodo['tipo'] ?? ''] ?? 'período anterior';
            $igualA = (($periodo['tipo'] ?? '') === 'semanal' ? 'igual à ' : 'igual ao ') . $vsRotulo;
            $tiles = [
                ['os_atendidas', 'OS atendidas', 0, false], ['atendimentos', 'Atendimentos', 0, false], ['concluidos', 'Concluídos', 0, false],
                ['horas', 'Horas', 1, false], ['fotos', 'Fotos', 0, false], ['finalizadas', 'Finalizadas', 0, false],
                ['nao_realizadas', 'Não realizadas', 0, true], ['tecnicos', 'Técnicos', 0, false],
            ];
            $delta = function (string $k, int $dec, bool $ruim) use ($dk, $dka, $vsRotulo, $igualA, $gn) {
                if (! isset($dka[$k])) {
                    return ['→', 'sem comparação', ''];
                }
                $d = round((float) ($dk[$k] ?? 0) - (float) $dka[$k], $dec);
                if ($d == 0) {
                    return ['→', $igualA, ''];
                }

                return [$d > 0 ? '▲' : '▼', ($d > 0 ? '+' : '−') . $gn(abs($d), $dec) . ' vs ' . $vsRotulo, ($ruim && $d > 0) ? 'pn-atencao' : ''];
            };

            $porDia = $dashboard['por_dia'] ?? [];
            $diasSemana = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
            $pontosDia = [];
            foreach ($porDia as $d) {
                $ts = strtotime($d['dia']);
                $rotDia = ($periodo['tipo'] ?? '') === 'mensal' ? date('d', $ts) : $diasSemana[(int) date('w', $ts)] . ' ' . date('d', $ts);
                $pontosDia[] = [
                    'rotulo' => $rotDia, 'valor' => (int) $d['atendimentos'],
                    'dica' => date('d/m/Y', $ts) . "\n" . $gn($d['atendimentos']) . ' atendimentos' . "\n" . $gn($d['concluidos']) . ' concluídos · ' . $gn($d['horas'], 1) . ' h · ' . $gn($d['fotos']) . ' fotos',
                ];
            }

            $statusOs = $dashboard['status_os'] ?? [];
            $pontosStatus = [];
            foreach ($statusOs as $so) {
                $pontosStatus[] = ['rotulo' => (string) $so['status'], 'valor' => (int) $so['total'], 'cor' => '#2a78d6',
                    'dica' => $so['status'] . "\n" . $gn($so['total']) . ' OS'];
            }

            $pontosHoras = [];
            foreach ($dashboard['horas_tecnico'] ?? [] as $ht) {
                $pontosHoras[] = ['rotulo' => $ht['nome'], 'valor' => (float) $ht['horas'],
                    'dica' => $ht['nome'] . "\n" . $gn($ht['horas'], 1) . ' h em atendimento' . "\n" . $gn($ht['atendimentos']) . ' atendimentos'];
            }
            ?>
        <section class="pn" aria-labelledby="pn-titulo">
            <h2 class="pn-titulo" id="pn-titulo">Painel do período</h2>
            <div class="pn-tiles">
                <?php foreach ($tiles as [$k, $rot, $dec, $ruim]) :
                    [$seta, $txt, $cls] = $delta($k, $dec, $ruim); ?>
                    <div class="pn-tile">
                        <div class="pn-tile-rot"><?= $e($rot) ?></div>
                        <div class="pn-tile-valor"><?= $e($gn($dk[$k] ?? 0, $dec)) ?></div>
                        <div class="pn-tile-delta <?= $cls ?>"><span aria-hidden="true"><?= $seta ?></span> <?= $e($txt) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pn-grade">
                <figure class="pn-fig">
                    <figcaption>Atendimentos por dia</figcaption>
                    <?= Grafico_svg::colunas($pontosDia, ['titulo' => 'Atendimentos por dia', 'descricao' => 'Colunas com o número de atendimentos em cada dia do período.']) ?>
                    <details class="pn-tabela">
                        <summary>Ver como tabela</summary>
                        <table class="table table-bordered">
                            <thead><tr><th>Dia</th><th>Atendimentos</th><th>Concluídos</th><th>Horas</th><th>Fotos</th></tr></thead>
                            <tbody>
                                <?php foreach ($porDia as $d) : ?>
                                    <tr><td><?= $e(date('d/m/Y', strtotime($d['dia']))) ?></td><td><?= $e($gn($d['atendimentos'])) ?></td><td><?= $e($gn($d['concluidos'])) ?></td><td><?= $e($gn($d['horas'], 1)) ?></td><td><?= $e($gn($d['fotos'])) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </details>
                </figure>

                <?php if (! empty($pontosStatus)) : ?>
                    <figure class="pn-fig">
                        <figcaption>OS por status atual</figcaption>
                        <?= Grafico_svg::barrasHorizontais($pontosStatus, ['titulo' => 'OS por status atual', 'descricao' => 'Barras horizontais com a quantidade de OS atendidas no período em cada status atual.']) ?>
                        <details class="pn-tabela">
                            <summary>Ver como tabela</summary>
                            <table class="table table-bordered">
                                <thead><tr><th>Status</th><th>OS</th></tr></thead>
                                <tbody>
                                    <?php foreach ($statusOs as $so) : ?>
                                        <tr><td><?= $e($so['status']) ?></td><td><?= $e($gn($so['total'])) ?></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </details>
                    </figure>
                <?php endif; ?>

                <?php if (! empty($pontosHoras)) : ?>
                    <figure class="pn-fig">
                        <figcaption>Horas em atendimento por técnico</figcaption>
                        <?= Grafico_svg::barrasHorizontais($pontosHoras, ['titulo' => 'Horas em atendimento por técnico', 'descricao' => 'Barras horizontais com as horas em atendimento de cada técnico no período.', 'decimais' => 1, 'sufixo' => ' h']) ?>
                        <details class="pn-tabela">
                            <summary>Ver como tabela</summary>
                            <table class="table table-bordered">
                                <thead><tr><th>Técnico</th><th>Horas</th><th>Atendimentos</th></tr></thead>
                                <tbody>
                                    <?php foreach ($dashboard['horas_tecnico'] as $ht) : ?>
                                        <tr><td><?= $e($ht['nome']) ?></td><td><?= $e($gn($ht['horas'], 1)) ?></td><td><?= $e($gn($ht['atendimentos'])) ?></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </details>
                    </figure>
                <?php endif; ?>
            </div>
        </section>
        <script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/grafico-svg.js"></script>
        <?php endif; ?>

        <?php if (! empty($relatorio['tecnicos'])) : ?>
            <div class="rel-bloco">
                <div class="rel-bloco-titulo"><h5>Por técnico</h5></div>
                <div class="ui-tabela-rolagem">
                    <table class="table table-bordered">
                        <thead><tr><th>Técnico</th><th>Atendimentos</th><th>Horas</th></tr></thead>
                        <tbody>
                            <?php foreach ($relatorio['tecnicos'] as $t) : ?>
                                <tr><td><?= $e($t['nome']) ?></td><td><?= $fmtNum($t['atendimentos']) ?></td><td><?= $fmtNum($t['horas'], 1) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($relatorio['os'])) : ?>
            <div class="widget-box">
                <div class="ui-vazio"><i class="bx bx-calendar-x"></i><strong>Nenhuma OS atendida neste período</strong>Escolha outro período ou ajuste os filtros.</div>
            </div>
        <?php endif; ?>

        <?php
        $fotosPorOs = [];
        foreach ($fotosPeriodo as $fp) {
            $fotosPorOs[(int) $fp['idOs']] = $fp;
        }
        foreach ($relatorio['os'] as $os) : ?>
            <section class="rel-projeto">
                <div class="rel-projeto-cab">
                    <div>
                        <h5><a href="<?= site_url('os/visualizar/' . (int) $os['idOs']) ?>">OS #<?= (int) $os['idOs'] ?></a> <small><?= $e($os['cliente']) ?></small></h5>
                        <?php if (trim((string) ($os['descricao'] ?? '')) !== '') : ?><p><?= $e($os['descricao']) ?></p><?php endif; ?>
                    </div>
                    <div>
                        <span class="ui-selo neutro"><?= $e($os['status']) ?></span>
                        <?php if (! empty($os['concluida'])) : ?><span class="ui-selo sucesso">Concluída</span><?php endif; ?>
                        <?php if (! empty($os['finalizada'])) : ?><span class="ui-selo sucesso">Finalizada</span><?php endif; ?>
                    </div>
                </div>

                <div class="rel-projeto-corpo">
                    <div class="rel-totais">
                        <span>Atendimentos: <strong><?= $fmtNum(count($os['atendimentos'])) ?></strong></span>
                        <span>Horas: <strong><?= $fmtNum($os['horas'], 1) ?></strong></span>
                        <span>Fotos: <strong><?= $fmtNum($os['fotos']) ?></strong></span>
                    </div>

                    <div class="ui-tabela-rolagem">
                        <table class="table table-bordered">
                            <thead><tr><th>Entrada</th><th>Saída</th><th>Técnico</th><th>Horas</th><th>Observação</th></tr></thead>
                            <tbody>
                                <?php foreach ($os['atendimentos'] as $at) : ?>
                                    <tr class="rel-registro">
                                        <td><?= $e($fmtDataHora($at['entrada'])) ?></td>
                                        <td><?= ! empty($at['saida']) ? $e($fmtDataHora($at['saida'])) : '—' ?></td>
                                        <td><?= $e($at['tecnico']) ?></td>
                                        <td><?= $fmtNum($at['horas'], 1) ?></td>
                                        <td><?= trim((string) ($at['observacao'] ?? '')) !== '' ? nl2br($e($at['observacao'])) : '—' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php $fg = $fotosPorOs[(int) $os['idOs']] ?? null; ?>
                    <?php if ($fotosModo !== '0' && $fg && ! empty($fg['itens'])) : ?>
                        <div class="rel-fotos-secao">
                            <h6 class="rel-sub rel-fotos-cab">Fotos do período (<?= $fmtNum($fg['total']) ?>)</h6>
                            <div class="rel-fotos-grade">
                                <?php foreach ($fg['itens'] as $ft) :
                                    $urlFoto = (string) $ft['url'];
                                    ?>
                                    <figure class="rel-fotos-item">
                                        <a href="<?= $e($urlFoto) ?>" target="_blank" rel="noopener">
                                            <img class="rel-fotos-img" src="<?= $e($urlFoto) ?>" loading="eager" decoding="sync" alt="<?= $e($ft['legenda']) ?>" />
                                        </a>
                                        <figcaption><strong><?= $e($ft['legenda']) ?></strong></figcaption>
                                    </figure>
                                <?php endforeach; ?>
                            </div>
                            <?php if (($fg['restantes'] ?? 0) > 0) : ?>
                                <p class="rel-itens"><a href="<?= $e($link(['fotos' => 'todas'])) ?>">+<?= $fmtNum($fg['restantes']) ?> fotos</a> (mostrar todas)</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</div>
