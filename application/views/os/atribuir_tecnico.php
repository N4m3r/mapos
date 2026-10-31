<style>
.ct-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;padding:12px}
.ct-card{border:1px solid var(--ui-borda);border-left:4px solid var(--cor,#ccc);border-radius:var(--ui-raio);padding:12px 14px;display:flex;flex-direction:column;gap:8px;box-shadow:var(--ui-sombra)}
.ct-card.atrasado{background:rgba(229,72,77,.05)}
.ct-topo{display:flex;align-items:center;justify-content:space-between;gap:8px}
.ct-os{font-weight:700;font-size:14px}
.ct-status{max-width:160px;padding:3px 6px;font-size:12px;margin:0;height:auto}
.ct-cliente{font-size:14px;font-weight:600}
.ct-cliente small{display:block;color:var(--ui-texto-2);font-weight:400;font-size:12px}
.ct-desc{font-size:12px;color:var(--ui-texto-2);line-height:1.4;min-height:16px}
.ct-meta{display:flex;flex-wrap:wrap;gap:6px 12px;font-size:12px;color:var(--ui-texto-2);align-items:center}
.ct-acoes{display:flex;flex-wrap:wrap;gap:6px;margin-top:2px}
.ct-acoes .btn{margin:0}
a.ui-kpi{display:block;color:inherit;text-decoration:none}
a.ui-kpi:hover{color:inherit;text-decoration:none;box-shadow:var(--ui-sombra-alta)}
.ui-kpi.ativo{box-shadow:0 0 0 2px var(--ui-primaria) inset}
.ct-carga{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;margin:0 0 16px}
.ct-tec{display:block;border:1px solid var(--ui-borda);border-radius:var(--ui-raio);padding:10px 12px;color:inherit;text-decoration:none}
.ct-tec:hover,.ct-tec.ativo{border-color:var(--ui-primaria);color:inherit;text-decoration:none}
.ct-tec.ativo{box-shadow:0 0 0 1px var(--ui-primaria) inset}
.ct-tec-nome{font-weight:600;font-size:13px;display:flex;justify-content:space-between;gap:6px}
.ct-tec-info{font-size:12px;color:var(--ui-texto-2);margin:2px 0 6px}
.ct-carga-tit{font-size:14px;font-weight:600;margin:0 0 8px}
.ui-campo-periodo{flex:2 1 300px}.ui-par{display:flex;gap:6px;align-items:center}.ui-par input{flex:1 1 0;min-width:0;width:auto!important}
.ui-filtros .ui-campo label{font-size:12px;font-weight:600;margin:0 0 3px;color:var(--ui-texto-2)}
.ui-filtros .ui-par{display:flex;gap:6px;align-items:center}
@media (max-width:600px){
    .ct-cards{grid-template-columns:1fr;padding:8px}
    .ui-kpis{grid-template-columns:repeat(2,1fr)}
    .ui-filtros .ui-campo{flex-basis:100%}
}
</style>

<?php if ($this->session->flashdata('success') != null) { ?>
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?php echo $this->session->flashdata('success'); ?>
    </div>
<?php } ?>

<?php if ($this->session->flashdata('error') != null) { ?>
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?php echo $this->session->flashdata('error'); ?>
    </div>
<?php } ?>

<?php
// Paleta de status padrão do sistema (mesma de os/os.php e do painel).
if (! function_exists('corStatusAtribuir')) {
    function corStatusAtribuir($status)
    {
        switch ($status) {
            case 'Aberto': return '#00cd00';
            case 'Em Andamento': return '#436eee';
            case 'Orçamento': return '#CDB380';
            case 'Negociação': return '#AEB404';
            case 'Cancelado': return '#CD0000';
            case 'Finalizado': return '#256';
            case 'Faturado': return '#B266FF';
            case 'Aguardando Peças': return '#FF7F00';
            case 'Aprovado': return '#808080';
            case 'Não Realizado': return '#CD0000';
            default: return '#E0E4CC';
        }
    }
}
$aba = isset($aba) ? $aba : 'todos';
if (!isset($ordens) || !is_array($ordens)) {
    $ordens = [];
}
if (!isset($naoRealizadas) || !is_array($naoRealizadas)) {
    $naoRealizadas = [];
}
$kpis = isset($kpis) ? $kpis : [];
$kpis += ['total' => 0, 'sem_tecnico' => 0, 'em_atendimento' => 0, 'aguardando' => 0, 'nao_realizadas' => 0, 'finalizados' => 0, 'atrasados' => 0, 'hoje' => 0];
$abasDisponiveis = [
    'todos'          => 'Todos abertos',
    'sem_tecnico'    => 'Sem Técnico',
    'em_atendimento' => 'Em Atendimento',
    'atrasados'      => 'Atrasados',
    'hoje'           => 'Agendados hoje',
    'nao_realizadas' => 'Não Realizadas',
    'finalizados'    => 'Finalizados',
];
$statusDisponiveis = isset($statusDisponiveis) ? $statusDisponiveis : [];
$tecnicos = (isset($tecnicos) && is_array($tecnicos)) ? $tecnicos : [];
$cargaTecnicos = isset($cargaTecnicos) ? $cargaTecnicos : [];
$tecnicoFiltro = isset($tecnicoFiltro) ? (int) $tecnicoFiltro : 0;
$busca    = isset($busca) ? $busca : '';
$data_de  = isset($data_de) ? $data_de : '';
$data_ate = isset($data_ate) ? $data_ate : '';
$base = base_url() . 'index.php/os/atribuir';
$temFiltro = ($busca !== '' || $data_de !== '' || $data_ate !== '' || $tecnicoFiltro > 0);
$qTec = $tecnicoFiltro > 0 ? '&tecnico=' . $tecnicoFiltro : '';
$podeAdicionar = $this->permission->checkPermission($this->session->userdata('permissao'), 'aOs');
$agoraTs = time();
$hojeIni = strtotime(date('Y-m-d') . ' 00:00:00');
$hojeFim = strtotime(date('Y-m-d') . ' 23:59:59');
$maxCarga = 1;
foreach ($cargaTecnicos as $c) {
    $maxCarga = max($maxCarga, (int) $c['abertas']);
}
$kpiLista = [
    ['todos', 'Chamados abertos', 'bx-list-ul', '', (int) $kpis['total']],
    ['sem_tecnico', 'Sem técnico', 'bx-user-x', 'alerta', (int) $kpis['sem_tecnico']],
    ['atrasados', 'Atrasados', 'bx-error-circle', 'perigo', (int) $kpis['atrasados']],
    ['hoje', 'Agendados hoje', 'bx-calendar-event', 'info', (int) $kpis['hoje']],
    ['em_atendimento', 'Em atendimento', 'bx-user-check', 'sucesso', (int) $kpis['em_atendimento']],
    ['nao_realizadas', 'Não realizadas', 'bx-x-circle', 'perigo', (int) $kpis['nao_realizadas']],
    ['finalizados', 'Finalizados', 'bx-check-circle', 'sucesso', (int) $kpis['finalizados']],
];
?>

<div class="new122">
    <div class="ui-cabecalho">
        <div>
            <h1><i class="fas fa-headset"></i> Central de Atendimento</h1>
            <p>Distribua chamados entre os técnicos, agende visitas e acompanhe o que está atrasado ou marcado para hoje.</p>
        </div>
        <div class="ui-acoes">
            <?php if ($podeAdicionar) { ?>
                <a href="<?= base_url() ?>index.php/os/adicionar" class="btn btn-success"><i class='bx bx-plus'></i> Nova OS</a>
            <?php } ?>
            <a href="<?= base_url() ?>index.php/agenda" class="btn"><i class='bx bx-calendar'></i> Agenda</a>
        </div>
    </div>

    <!-- ============ KPIs ============ -->
    <div class="ui-kpis">
        <?php foreach ($kpiLista as $k) { ?>
            <a href="<?= $base ?>?aba=<?= $k[0] ?><?= $qTec ?>" class="ui-kpi <?= $k[3] ?> <?= $aba === $k[0] ? 'ativo' : '' ?>">
                <div class="ui-kpi-rotulo"><i class='bx <?= $k[2] ?>'></i> <?= $k[1] ?></div>
                <div class="ui-kpi-valor"><?= $k[4] ?></div>
            </a>
        <?php } ?>
        <div class="ui-kpi" title="Orçamento, Negociação, Aprovado e Aguardando Peças">
            <div class="ui-kpi-rotulo"><i class='bx bx-time-five'></i> Aguardando</div>
            <div class="ui-kpi-valor"><?= (int) $kpis['aguardando'] ?></div>
        </div>
    </div>

    <!-- ============ Carga por técnico ============ -->
    <?php if (! empty($tecnicos)) { ?>
        <div>
            <h4 class="ct-carga-tit"><i class='bx bx-group'></i> Carga por técnico</h4>
            <div class="ct-carga">
                <?php foreach ($tecnicos as $t) {
                    if (! is_object($t)) { continue; }
                    $tid = (int) $t->idUsuarios;
                    $abertas = isset($cargaTecnicos[$tid]) ? $cargaTecnicos[$tid]['abertas'] : 0;
                    $hojeT = isset($cargaTecnicos[$tid]) ? $cargaTecnicos[$tid]['hoje'] : 0;
                    $pct = (int) round($abertas / $maxCarga * 100);
                    $ativoT = $tecnicoFiltro === $tid;
                    $hrefT = $ativoT ? $base . '?aba=' . $aba : $base . '?aba=' . $aba . '&tecnico=' . $tid;
                ?>
                    <a href="<?= html_escape($hrefT) ?>" class="ct-tec <?= $ativoT ? 'ativo' : '' ?>" title="<?= $ativoT ? 'Remover filtro deste técnico' : 'Ver chamados de ' . html_escape($t->nome) ?>">
                        <div class="ct-tec-nome"><span><i class='bx bx-user'></i> <?= html_escape($t->nome) ?></span><strong><?= $abertas ?></strong></div>
                        <div class="ct-tec-info"><?= $abertas ?> aberta(s) · <?= $hojeT ?> hoje</div>
                        <div class="ui-barra"><span style="width:<?= $pct ?>%"></span></div>
                    </a>
                <?php } ?>
            </div>
        </div>
    <?php } ?>

    <!-- ============ Filtros (situação / técnico / busca / período) ============ -->
    <form method="get" action="<?= $base ?>" class="ui-filtros" id="formFiltros">
        <div class="ui-campo">
            <label for="filtroAba">Situação</label>
            <select name="aba" id="filtroAba">
                <?php foreach ($abasDisponiveis as $valor => $rotulo) { ?>
                    <option value="<?= $valor ?>" <?= $aba === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                <?php } ?>
            </select>
        </div>
        <?php if ($aba !== 'nao_realizadas') { ?>
            <div class="ui-campo">
                <label for="filtroTecnico">Técnico</label>
                <select name="tecnico" id="filtroTecnico">
                    <option value="">Todos</option>
                    <?php foreach ($tecnicos as $t) {
                        if (! is_object($t)) { continue; } ?>
                        <option value="<?= (int) $t->idUsuarios ?>" <?= $tecnicoFiltro === (int) $t->idUsuarios ? 'selected' : '' ?>><?= html_escape($t->nome) ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="ui-campo" style="flex-grow:2">
                <label for="filtroBusca">Busca</label>
                <input type="text" name="busca" id="filtroBusca" value="<?= html_escape($busca) ?>" placeholder="Cliente, nº da OS ou descrição">
            </div>
            <div class="ui-campo ui-campo-periodo">
                <label for="filtroDe">Período (data da OS)</label>
                <div class="ui-par">
                    <input type="date" name="data_de" id="filtroDe" value="<?= html_escape($data_de) ?>" title="Data inicial (de)" aria-label="Data inicial">
                    <span>até</span>
                    <input type="date" name="data_ate" id="filtroAte" value="<?= html_escape($data_ate) ?>" title="Data final (até)" aria-label="Data final">
                </div>
            </div>
            <div class="ui-campo-acao">
                <button type="submit" class="btn btn-primary"><i class='bx bx-filter'></i> Filtrar</button>
                <?php if ($temFiltro) { ?>
                    <a href="<?= $base ?>?aba=<?= $aba ?>" class="btn" title="Limpar filtros"><i class='bx bx-x'></i> Limpar</a>
                <?php } ?>
            </div>
        <?php } ?>
    </form>

    <?php if ($aba === 'nao_realizadas') { ?>
        <!-- ============ Aba: Não Realizadas ============ -->
        <div class="widget-box" style="margin-top: 8px">
            <div class="widget-content nopadding">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Cliente</th>
                                <th>Motivo</th>
                                <th>Técnico</th>
                                <th>Registrado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($naoRealizadas)) { ?>
                                <tr><td colspan="6"><div class="ui-vazio"><i class='bx bx-check-circle'></i><strong>Nenhuma OS em espera</strong>Não há visitas marcadas como não realizadas.</div></td></tr>
                            <?php } else {
                                foreach ($naoRealizadas as $nr) { ?>
                                    <tr>
                                        <td><?= (int) $nr->os_id ?></td>
                                        <td>
                                            <?= html_escape($nr->nomeCliente ?? '') ?>
                                            <?php if (!empty($nr->telefone)) { ?><br><small><?= html_escape($nr->telefone) ?></small><?php } ?>
                                        </td>
                                        <td>
                                            <?= html_escape($nr->motivo_texto ?? '—') ?>
                                            <?php if (!empty($nr->observacao)) { ?>
                                                <br><small class="help-block"><?= html_escape($nr->observacao) ?></small>
                                            <?php } ?>
                                        </td>
                                        <td><?= html_escape($nr->nome_tecnico ?? '—') ?></td>
                                        <td><?= !empty($nr->data_registro) ? date('d/m/Y H:i', strtotime($nr->data_registro)) : '—' ?></td>
                                        <td>
                                            <button class="button btn btn-mini btn-success btn-reagendar"
                                                data-ocorrencia="<?= (int) $nr->idOcorrencia ?>" data-os="<?= (int) $nr->os_id ?>">
                                                <span class="button__icon"><i class='bx bx-calendar'></i></span>
                                                <span class="button__text2">Reagendar</span>
                                            </button>
                                            <button class="button btn btn-mini btn-warning btn-reabrir"
                                                data-ocorrencia="<?= (int) $nr->idOcorrencia ?>" data-os="<?= (int) $nr->os_id ?>">
                                                <span class="button__icon"><i class='bx bx-revision'></i></span>
                                                <span class="button__text2">Reabrir</span>
                                            </button>
                                            <a href="<?= base_url() ?>index.php/os/visualizar/<?= (int) $nr->os_id ?>" class="button btn btn-mini btn-inverse" title="Ver OS" aria-label="Ver OS">
                                                <span class="button__icon"><i class='bx bx-show'></i></span><span class="button__text2">Ver OS</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php }
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <!-- ============ Cartões de OS ============ -->
        <div class="widget-box" style="margin-top: 8px">
            <div class="widget-content nopadding">
                <?php if (empty($ordens)) { ?>
                    <div class="ui-vazio">
                        <i class='bx bx-inbox'></i>
                        <strong>Nenhuma OS encontrada</strong>
                        <?php if ($temFiltro || $aba !== 'todos') { ?>
                            Nada corresponde à situação "<?= html_escape($abasDisponiveis[$aba] ?? $aba) ?>"<?= $temFiltro ? ' com os filtros aplicados' : '' ?>.<br>
                            <a href="<?= $base ?>?aba=todos" class="btn btn-small ui-mt"><i class='bx bx-x'></i> Limpar filtros</a>
                        <?php } else { ?>
                            Não há chamados abertos no momento.
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div class="ct-cards">
                        <?php foreach ($ordens as $os) {
                            $cor = corStatusAtribuir($os->status);
                            $podeMudarStatus = in_array($os->status, $statusDisponiveis, true);
                            $chamadoConcluido = in_array($os->status, ['Finalizado', 'Faturado', 'Cancelado'], true);
                            $agTs = ! empty($os->data_agendamento) ? strtotime($os->data_agendamento) : false;
                            $ag = $agTs ? date('d/m/Y H:i', $agTs) : null;
                            $agInput = $agTs ? date('Y-m-d\TH:i', $agTs) : '';
                            $atrasado = $agTs && $agTs < $agoraTs && ! $chamadoConcluido;
                            $ehHoje = $agTs && $agTs >= $hojeIni && $agTs <= $hojeFim && ! $chamadoConcluido && ! $atrasado;
                            $temTec = ! empty($os->tecnico_responsavel);
                        ?>
                            <div class="ct-card <?= $atrasado ? 'atrasado' : '' ?>" style="--cor:<?= $cor ?>">
                                <div class="ct-topo">
                                    <span class="ct-os">OS #<?= sprintf('%04d', $os->idOs) ?></span>
                                    <?php if ($podeMudarStatus) { ?>
                                        <select class="status-inline ct-status" data-os="<?= (int) $os->idOs ?>" data-atual="<?= html_escape($os->status) ?>" style="border-left:4px solid <?= $cor ?>" aria-label="Status da OS <?= (int) $os->idOs ?>">
                                            <?php foreach ($statusDisponiveis as $st) { ?>
                                                <option value="<?= html_escape($st) ?>" <?= $os->status === $st ? 'selected' : '' ?>><?= html_escape($st) ?></option>
                                            <?php } ?>
                                        </select>
                                    <?php } else { ?>
                                        <span class="ui-selo neutro" style="border-left:4px solid <?= $cor ?>"><?= html_escape($os->status) ?></span>
                                    <?php } ?>
                                </div>
                                <div class="ct-cliente"><i class='bx bxs-user'></i> <?= html_escape($os->nomeCliente) ?>
                                    <?php if (! empty($os->telefone)) { ?><small><?= html_escape($os->telefone) ?></small><?php } ?>
                                </div>
                                <div class="ct-desc"><?= html_escape(character_limiter(strip_tags((string) $os->descricaoProduto), 70)) ?></div>
                                <div class="ct-meta">
                                    <span><i class='bx bx-calendar'></i> <?= date('d/m/Y', strtotime($os->dataInicial)) ?></span>
                                    <?php if ($ag) { ?>
                                        <span><i class='bx bx-calendar-event'></i> <?= $ag ?></span>
                                        <?php if ($atrasado) { ?><span class="ui-selo perigo"><i class='bx bx-error-circle'></i> Atrasado</span><?php } ?>
                                        <?php if ($ehHoje) { ?><span class="ui-selo info"><i class='bx bx-calendar-star'></i> Hoje</span><?php } ?>
                                    <?php } ?>
                                    <?php if ($temTec) { ?>
                                        <span class="ui-selo sucesso"><i class='bx bx-user'></i> <?= html_escape($os->nome_tecnico) ?></span>
                                    <?php } else { ?>
                                        <span class="ui-selo laranja"><i class='bx bx-user-x'></i> Sem técnico</span>
                                    <?php } ?>
                                </div>
                                <div class="ct-acoes">
                                    <?php if (! $chamadoConcluido) { ?>
                                        <button class="button btn btn-mini btn-success btn-atribuir"
                                            data-os="<?= (int) $os->idOs ?>"
                                            data-cliente="<?= html_escape($os->nomeCliente) ?>"
                                            data-tecnico-atual="<?= html_escape($os->tecnico_responsavel) ?>"
                                            data-tecnico-nome="<?= html_escape($os->nome_tecnico ?? '') ?>"
                                            data-agendamento="<?= $agInput ?>">
                                            <span class="button__icon"><i class='bx <?= $temTec ? 'bx-calendar-edit' : 'bx-user-plus' ?>'></i></span>
                                            <span class="button__text2"><?= $temTec ? 'Agendar ou trocar' : 'Atribuir' ?></span>
                                        </button>
                                    <?php } ?>
                                    <a href="<?= base_url() ?>index.php/os/visualizar/<?= (int) $os->idOs ?>" class="button btn btn-mini btn-inverse" title="Ver OS" aria-label="Ver OS <?= (int) $os->idOs ?>">
                                        <span class="button__icon"><i class='bx bx-show'></i></span><span class="button__text2">Ver OS</span>
                                    </a>
                                    <?php if (! $chamadoConcluido && $temTec) { ?>
                                        <button class="button btn btn-mini btn-danger btn-remover" data-os="<?= (int) $os->idOs ?>" data-cliente="<?= html_escape($os->nomeCliente) ?>" title="Remover técnico" aria-label="Remover técnico da OS <?= (int) $os->idOs ?>">
                                            <span class="button__icon"><i class='bx bx-user-x'></i></span><span class="button__text2">Remover técnico</span>
                                        </button>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>

        <?php if (isset($pagination) && $pagination) { ?>
            <div class="pagination alternate" style="text-align: center;"><?= $pagination ?></div>
        <?php } ?>
    <?php } ?>
</div>

<!-- Modal Atribuir Técnico -->
<div id="modalAtribuir" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modalAtribuirLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 id="modalAtribuirLabel"><i class='bx bx-user-plus'></i> Atribuir Técnico</h4>
    </div>
    <form action="<?= base_url() ?>index.php/os/atribuirTecnicoAction" method="POST" id="formAtribuir">
        <div class="modal-body">
            <input type="hidden" name="os_id" id="os_id_atribuir">

            <div class="control-group">
                <label class="control-label">OS #<span id="os_numero"></span> - <span id="os_cliente"></span></label>
            </div>

            <div class="control-group">
                <label class="control-label" for="tecnico_id">Técnico Responsável:</label>
                <div class="controls">
                    <select name="tecnico_id" id="tecnico_id" class="span12" required>
                        <option value="">Selecione um técnico...</option>
                        <?php if (!empty($tecnicos) && is_array($tecnicos)) {
                            foreach ($tecnicos as $t) {
                                if (is_object($t)) { ?>
                                    <option value="<?= $t->idUsuarios ?>"><?= htmlspecialchars($t->nome) ?> (<?= htmlspecialchars($t->email) ?>)</option>
                                <?php }
                            }
                        } else { ?>
                            <option value="" disabled>Nenhum técnico disponível</option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="data_agendamento"><i class='bx bx-calendar-event'></i> Agendar atendimento (data e hora):</label>
                <div class="controls">
                    <input type="datetime-local" name="data_agendamento" id="data_agendamento" class="span12">
                    <span class="help-inline">Opcional. Notifica o técnico com o horário marcado da visita.</span>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="observacao">Observação:</label>
                <div class="controls">
                    <textarea name="observacao" id="observacao" class="span12" rows="2" placeholder="Motivo da atribuição (opcional)"></textarea>
                </div>
            </div>

            <div id="tecnico-atual-info" class="alert alert-info hide">
                <strong>Técnico atual:</strong> <span id="tecnico-atual-nome"></span><br>
                <small>Ao atribuir um novo técnico, o atual será substituído.</small>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
            <button type="submit" class="btn btn-success"><i class='bx bx-check'></i> Confirmar Atribuição</button>
        </div>
    </form>
</div>

<!-- Modal Remover Técnico -->
<div id="modalRemover" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modalRemoverLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 id="modalRemoverLabel"><i class='bx bx-user-x'></i> Remover Técnico</h4>
    </div>
    <form action="<?= base_url() ?>index.php/os/removerTecnicoAction" method="POST" id="formRemover">
        <div class="modal-body">
            <input type="hidden" name="os_id" id="os_id_remover">

            <div class="alert alert-warning">
                <p>Tem certeza que deseja remover o técnico da OS #<strong id="os_numero_remover"></strong>?</p>
                <p>Cliente: <strong id="os_cliente_remover"></strong></p>
            </div>

            <div class="control-group">
                <label class="control-label" for="motivo">Motivo da remoção:</label>
                <div class="controls">
                    <textarea name="motivo" id="motivo" class="span12" rows="2" placeholder="Informe o motivo (opcional)"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
            <button type="submit" class="btn btn-danger"><i class='bx bx-trash'></i> Confirmar Remoção</button>
        </div>
    </form>
</div>

<!-- Modal Reagendar (Não Realizada) -->
<div id="modalReagendar" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4><i class='bx bx-calendar'></i> Reagendar OS #<span id="reag_os"></span></h4>
    </div>
    <div class="modal-body">
        <input type="hidden" id="reag_ocorrencia">
        <div class="control-group">
            <label class="control-label" for="reag_data">Nova data de atendimento:</label>
            <div class="controls">
                <input type="date" id="reag_data" class="span12">
            </div>
        </div>
        <small style="color:#888">A OS volta para a agenda como "Aberto" na data informada.</small>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
        <button type="button" class="btn btn-success" id="btnConfirmarReagendar"><i class='bx bx-check'></i> Confirmar</button>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        var BASE = '<?= base_url() ?>index.php/os/';

        // ---- Filtro por situação (submete o formulário de filtros) ----
        $('#filtroAba').change(function() {
            // Ao trocar a situação, zera busca/período para evitar combinações
            // sem resultados, e volta para a primeira página.
            $('#filtroBusca').val('');
            $('#filtroDe').val('');
            $('#filtroAte').val('');
            $('#formFiltros').submit();
        });

        // ---- Atribuir / Trocar técnico ----
        $('.btn-atribuir').click(function() {
            var osId = $(this).data('os');
            var cliente = $(this).data('cliente');
            var tecnicoAtual = $(this).data('tecnico-atual');
            var tecnicoNome = $(this).data('tecnico-nome');

            $('#os_id_atribuir').val(osId);
            $('#os_numero').text(osId);
            $('#os_cliente').text(cliente);
            $('#data_agendamento').val($(this).data('agendamento') || '');

            // Pré-seleciona o técnico atual para permitir só (re)agendar sem trocar.
            $('#tecnico_id').val(tecnicoAtual || '');

            if (tecnicoAtual) {
                $('#tecnico-atual-nome').text(tecnicoNome);
                $('#tecnico-atual-info').removeClass('hide');
                $('#modalAtribuirLabel').html('<i class="bx bx-calendar-edit"></i> Agendar / trocar técnico');
            } else {
                $('#tecnico-atual-info').addClass('hide');
                $('#modalAtribuirLabel').html('<i class="bx bx-user-plus"></i> Atribuir Técnico');
            }
            $('#modalAtribuir').modal('show');
        });

        $('.btn-remover').click(function() {
            $('#os_id_remover').val($(this).data('os'));
            $('#os_numero_remover').text($(this).data('os'));
            $('#os_cliente_remover').text($(this).data('cliente'));
            $('#modalRemover').modal('show');
        });

        $('#formAtribuir').submit(function(e) {
            if (!$('#tecnico_id').val()) {
                e.preventDefault();
                alert('Selecione um técnico para atribuir.');
                return false;
            }
        });

        // ---- Mudança de status inline (AJAX) ----
        $('.status-inline').change(function() {
            var $sel = $(this);
            var osId = $sel.data('os');
            var novo = $sel.val();
            var atual = $sel.data('atual');
            if (novo === atual) { return; }

            $sel.prop('disabled', true);
            $.ajax({
                url: BASE + 'alterarStatusAction',
                type: 'POST',
                dataType: 'json',
                data: { os_id: osId, status: novo },
                success: function(r) {
                    if (r && r.success) {
                        $sel.data('atual', novo);
                    } else {
                        alert((r && r.message) || 'Erro ao alterar status.');
                        $sel.val(atual);
                    }
                },
                error: function() {
                    alert('Falha de comunicação ao alterar status.');
                    $sel.val(atual);
                },
                complete: function() { $sel.prop('disabled', false); }
            });
        });

        // ---- Não realizadas: reagendar ----
        $('.btn-reagendar').click(function() {
            $('#reag_ocorrencia').val($(this).data('ocorrencia'));
            $('#reag_os').text($(this).data('os'));
            $('#reag_data').val('');
            $('#modalReagendar').modal('show');
        });

        $('#btnConfirmarReagendar').click(function() {
            var $btn = $(this);
            var oc = $('#reag_ocorrencia').val();
            var data = $('#reag_data').val();
            if (!data) { alert('Informe a nova data.'); return; }

            $btn.prop('disabled', true);
            $.ajax({
                url: BASE + 'resolverNaoRealizadaAction',
                type: 'POST',
                dataType: 'json',
                data: { ocorrencia_id: oc, acao: 'reagendar', nova_data: data },
                success: function(r) {
                    alert((r && r.message) || 'Concluído.');
                    if (r && r.success) { location.reload(); }
                },
                error: function() { alert('Falha de comunicação.'); },
                complete: function() { $btn.prop('disabled', false); }
            });
        });

        // ---- Não realizadas: reabrir ----
        $('.btn-reabrir').click(function() {
            if (!confirm('Reabrir a OS #' + $(this).data('os') + ' para refazer?')) { return; }
            var oc = $(this).data('ocorrencia');
            $.ajax({
                url: BASE + 'resolverNaoRealizadaAction',
                type: 'POST',
                dataType: 'json',
                data: { ocorrencia_id: oc, acao: 'reabrir' },
                success: function(r) {
                    alert((r && r.message) || 'Concluído.');
                    if (r && r.success) { location.reload(); }
                },
                error: function() { alert('Falha de comunicação.'); }
            });
        });
    });
</script>
