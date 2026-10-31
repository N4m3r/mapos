<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css">

<?php $this->load->view('obras/_nav'); ?>

<?php
$perm = function ($p) { return $this->permission->checkPermission($this->session->userdata('permissao'), $p); };
$coresStatus = ['planejamento' => 'info', 'em_execucao' => 'sucesso', 'paralisada' => 'alerta', 'concluida' => 'info', 'entregue' => 'sucesso', 'cancelada' => 'perigo'];
$rotulos = ['planejamento' => 'Planejamento', 'em_execucao' => 'Em execução', 'paralisada' => 'Paralisado', 'concluida' => 'Concluído', 'entregue' => 'Entregue', 'cancelada' => 'Cancelado'];
$contagem = $contagem_status ?? [];
$statusAtual = (string) $this->input->get('status');
$pesquisa = (string) $this->input->get('pesquisa');
$tipoAtual = (string) $this->input->get('tipo');
$respAtual = (int) $this->input->get('responsavel');
$atrasados = $this->input->get('atrasados') ? 1 : 0;
$ordemAtual = (string) $this->input->get('ordem');
$dirAtual = strtolower((string) $this->input->get('dir')) === 'desc' ? 'desc' : 'asc';
$ordens = ['' => 'Mais recentes', 'nome' => 'Nome', 'inicio' => 'Data de início', 'fim' => 'Data de fim prevista', 'progresso' => 'Progresso', 'status' => 'Status'];
if (! isset($ordens[$ordemAtual])) { $ordemAtual = ''; }
$filtrosExtras = array_filter(['tipo' => $tipoAtual, 'responsavel' => $respAtual ?: '', 'atrasados' => $atrasados ?: '', 'ordem' => $ordemAtual, 'dir' => $ordemAtual !== '' ? $dirAtual : ''], function ($v) { return $v !== '' && $v !== null; });
$temFiltro = $pesquisa !== '' || $statusAtual !== '' || $filtrosExtras;
$hoje = date('Y-m-d');
$prazo = function ($fim, $status) use ($hoje) {
    if (empty($fim) || $fim === '0000-00-00') { return ['sem prazo', '']; }
    if (in_array($status, ['concluida', 'entregue', 'cancelada'], true)) { return ['prazo ' . date('d/m/Y', strtotime($fim)), '']; }
    $d = (int) round((strtotime($fim) - strtotime($hoje)) / 86400);
    if ($d < 0) { return ['atrasado ' . abs($d) . (abs($d) === 1 ? ' dia' : ' dias'), 'atrasado']; }
    if ($d === 0) { return ['vence hoje', 'atencao']; }
    return ['faltam ' . $d . ($d === 1 ? ' dia' : ' dias'), $d <= 7 ? 'atencao' : ''];
};
$qs = function ($status) use ($pesquisa, $filtrosExtras) {
    $p = [];
    if ($pesquisa !== '') { $p['pesquisa'] = $pesquisa; }
    if ($status !== '') { $p['status'] = $status; }
    $p += $filtrosExtras;
    return site_url('obras/gerenciar') . ($p ? '?' . http_build_query($p) : '');
};
$totalGeral = array_sum($contagem);
$pCsv = array_filter(['pesquisa' => $pesquisa, 'status' => $statusAtual] + $filtrosExtras, function ($v) { return $v !== '' && $v !== null; });
$urlCsv = site_url('obras/exportarCsv') . ($pCsv ? '?' . http_build_query($pCsv) : '');
?>
<div class="new122" style="margin-top:0">
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-building-house"></i> Projetos</h1>
            <p class="ui-subtitulo">Abra um projeto para ver etapas, pontos e registros do dia.</p>
        </div>
    </div>

    <div class="pl-chips" role="group" aria-label="Filtrar por status">
        <a href="<?= html_escape($qs('')) ?>" class="pl-chip <?= $statusAtual === '' ? 'ativo' : '' ?>">Todos <strong><?= (int) $totalGeral ?></strong></a>
        <?php foreach ($rotulos as $k => $v): $n = (int) ($contagem[$k] ?? 0); if ($n === 0 && $statusAtual !== $k) { continue; } ?>
            <a href="<?= html_escape($qs($k)) ?>" class="pl-chip <?= $statusAtual === $k ? 'ativo' : '' ?>"><?= $v ?> <strong><?= $n ?></strong></a>
        <?php endforeach; ?>
    </div>

    <form action="<?= site_url('obras/gerenciar') ?>" method="get" class="pl-busca">
        <?php if ($statusAtual !== ''): ?><input type="hidden" name="status" value="<?= html_escape($statusAtual) ?>"><?php endif; ?>
        <label for="filtro-pesquisa" class="pl-sr">Buscar</label>
        <input type="text" id="filtro-pesquisa" name="pesquisa" placeholder="Buscar por nome, código ou cliente" value="<?= html_escape($pesquisa) ?>">
        <label for="filtro-tipo" class="pl-sr">Tipo</label>
        <select id="filtro-tipo" name="tipo">
            <option value="">Todos os tipos</option>
            <?php foreach (($tipos_projeto ?? []) as $t): ?>
                <option value="<?= html_escape($t) ?>" <?= $tipoAtual === $t ? 'selected' : '' ?>><?= html_escape($t) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="filtro-resp" class="pl-sr">Responsável</label>
        <select id="filtro-resp" name="responsavel">
            <option value="">Todos os responsáveis</option>
            <?php foreach (($responsaveis_projeto ?? []) as $u): ?>
                <option value="<?= (int) $u->idUsuarios ?>" <?= $respAtual === (int) $u->idUsuarios ? 'selected' : '' ?>><?= html_escape($u->nome) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="filtro-ordem" class="pl-sr">Ordenar por</label>
        <select id="filtro-ordem" name="ordem">
            <?php foreach ($ordens as $k => $v): ?>
                <option value="<?= html_escape($k) ?>" <?= $ordemAtual === $k ? 'selected' : '' ?>><?= html_escape($v) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="dir" aria-label="Sentido da ordenação">
            <option value="asc" <?= $dirAtual === 'asc' ? 'selected' : '' ?>>Crescente</option>
            <option value="desc" <?= $dirAtual === 'desc' ? 'selected' : '' ?>>Decrescente</option>
        </select>
        <label><input type="checkbox" name="atrasados" value="1" <?= $atrasados ? 'checked' : '' ?>> Só atrasados</label>
        <button class="button btn btn-mini pl-btn" type="submit"><span class="button__icon"><i class='bx bx-search'></i></span><span class="button__text2">Filtrar</span></button>
        <a href="<?= html_escape($urlCsv) ?>" class="button btn btn-mini pl-btn"><span class="button__icon"><i class='bx bx-download'></i></span><span class="button__text2">Exportar CSV</span></a>
    </form>

    <?php if (! $results): ?>
        <div class="ui-vazio">
            <i class="bx bx-building-house"></i>
            <?php if ($temFiltro): ?>
                <strong>Nenhum projeto encontrado com esses filtros.</strong>
                <a href="<?= site_url('obras/gerenciar') ?>" class="button btn btn-mini pl-btn"><span class="button__text2">Limpar filtros</span></a>
            <?php else: ?>
                <strong>Nenhum projeto ainda.</strong>
                <?php if ($perm('cObras')): ?>
                    <a href="<?= site_url('obras/adicionar') ?>" class="button btn btn-mini btn-primary"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Novo projeto</span></a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
    <ul class="pl-lista">
        <?php foreach ($results as $r):
            $rs = $resumo[(int) $r->idObra] ?? [];
            $pc = max(0, min(100, (float) $r->percentual_concluido));
            [$prazoTxt, $prazoCls] = $prazo($r->data_fim_prevista ?? null, $r->status);
            $ativoProj = in_array($r->status, ['planejamento', 'em_execucao', 'paralisada'], true);
            $avisos = [];
            if ($r->status === 'em_execucao') {
                $ds = $rs['dias_sem_registro'] ?? null;
                if ($ds === null) { $avisos[] = 'sem nenhum registro ainda'; }
                elseif ($ds >= 3) { $avisos[] = 'sem registro há ' . $ds . ' dias'; }
            }
            $sos = (int) ($rs['etapas_sem_os'] ?? 0);
            if ($ativoProj && $sos > 0) { $avisos[] = $sos . ($sos === 1 ? ' etapa sem OS' : ' etapas sem OS'); }
            $atr = (int) ($rs['etapas_atrasadas'] ?? 0);
            if ($ativoProj && $atr > 0) { $avisos[] = $atr . ($atr === 1 ? ' etapa atrasada' : ' etapas atrasadas'); }
            $url = site_url('obras/visualizar/' . $r->idObra);
        ?>
            <li class="pl-item">
                <div class="pl-principal">
                    <a href="<?= $url ?>" class="pl-nome"><?= html_escape($r->nome) ?></a>
                    <span class="pl-cod"><?= html_escape($r->codigo) ?></span>
                    <span class="ui-selo <?= $coresStatus[$r->status] ?? '' ?>"><?= html_escape($rotulos[$r->status] ?? $r->status) ?></span>
                </div>
                <div class="pl-cliente"><?= html_escape($r->nomeCliente ?: 'Sem cliente') ?></div>
                <div class="pl-prog">
                    <div class="obra-progress mini" role="progressbar" aria-valuenow="<?= (int) round($pc) ?>" aria-valuemin="0" aria-valuemax="100"><div class="obra-progress-bar" style="width:<?= $pc ?>%"></div></div>
                    <span class="pl-pct"><?= number_format($pc, 0, ',', '.') ?>%</span>
                </div>
                <div class="pl-prazo <?= $prazoCls ?>"><?= html_escape($prazoTxt) ?></div>
                <div class="pl-aviso"><?php if ($avisos): ?><i class="bx bx-error-circle"></i> <?= html_escape(implode(', ', array_slice($avisos, 0, 2))) ?><?php endif; ?></div>
                <div class="pl-acoes">
                    <?php if ($perm('eObras') || $perm('dObras')): ?>
                        <details class="pl-menu">
                            <summary aria-label="Mais ações"><i class="bx bx-dots-vertical-rounded"></i></summary>
                            <div class="pl-menu-itens">
                                <?php if ($perm('eObras')): ?><a href="<?= site_url('obras/editar/' . $r->idObra) ?>"><i class="bx bx-edit"></i> Editar</a><?php endif; ?>
                                <?php if ($perm('dObras')): ?><a href="#modal-excluir" role="button" data-toggle="modal" obra="<?= $r->idObra ?>"><i class="bx bx-trash-alt"></i> Excluir</a><?php endif; ?>
                            </div>
                        </details>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<?= $this->pagination->create_links(); ?>

<div id="modal-excluir" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obras/excluir') ?>" method="post">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h5>Excluir projeto</h5>
        </div>
        <div class="modal-body">
            <input type="hidden" id="idObra" name="idObra" value="" />
            <h5 style="text-align:center">Excluir este projeto e todos os seus dados (etapas, pontos, registros do dia, medições, material)?</h5>
        </div>
        <div class="modal-footer" style="display:flex;justify-content:center">
            <button class="button btn btn-warning" data-dismiss="modal" type="button"><span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span></button>
            <button class="button btn btn-danger"><span class="button__icon"><i class='bx bx-trash'></i></span><span class="button__text2">Excluir</span></button>
        </div>
    </form>
</div>

<script nonce="<?= csp_nonce() ?>">
    $(function () {
        $(document).on('click', 'a[obra]', function () { $('#idObra').val($(this).attr('obra')); });
        // Fecha o menu de ações ao clicar fora
        $(document).on('click', function (e) { if (!$(e.target).closest('.pl-menu').length) { $('.pl-menu[open]').removeAttr('open'); } });
    });
</script>
