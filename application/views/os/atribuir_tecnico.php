<style>
.tec-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;padding:12px}
.tec-card{border:1px solid #e5e7eb;border-left:4px solid var(--cor,#ccc);border-radius:10px;padding:12px 14px;background:#fff;display:flex;flex-direction:column;gap:8px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.tec-card-top{display:flex;align-items:center;justify-content:space-between;gap:8px}
.tec-card-os{font-weight:600;color:#374151;font-size:14px}
.tec-card-status{max-width:160px;padding:3px 6px;border-radius:6px;font-size:12px;margin:0}
.tec-card-cliente{font-size:14px;color:#111827;font-weight:500}
.tec-card-cliente small{display:block;color:#9ca3af;font-weight:400;font-size:12px}
.tec-card-desc{font-size:12px;color:#6b7280;line-height:1.4;min-height:16px}
.tec-card-meta{display:flex;flex-wrap:wrap;gap:12px;font-size:12px;color:#6b7280}
.tec-card-agenda{color:#2563eb;font-weight:500}
.tec-badge{display:inline-flex;align-items:center;gap:4px;color:#fff;padding:3px 9px;border-radius:20px;font-size:11px;white-space:nowrap}
.tec-badge-tec{background:#256}
.tec-badge-semtec{background:#FF7F00}
.tec-card-acoes{display:flex;flex-wrap:wrap;gap:6px;margin-top:2px}
.tec-vazio{padding:26px;text-align:center;color:#9ca3af}
@media (max-width:600px){
    .tec-cards{grid-template-columns:1fr;padding:8px}
    .tec-kpis{flex-wrap:wrap}
    .tec-kpi{flex:1 1 45%}
    .tec-filtros{flex-direction:column;align-items:stretch}
    .tec-filtro{width:100%}
    .tec-filtro-input,.tec-filtro-select,.tec-filtro-date{width:100%}
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
$aba = isset($aba) ? $aba : 'todos';
if (!isset($ordens) || !is_array($ordens)) {
    $ordens = [];
}
if (!isset($naoRealizadas) || !is_array($naoRealizadas)) {
    $naoRealizadas = [];
}
$kpis = isset($kpis) ? $kpis : ['total' => 0, 'sem_tecnico' => 0, 'em_atendimento' => 0, 'aguardando' => 0, 'nao_realizadas' => 0, 'finalizados' => 0];
$kpis['finalizados'] = isset($kpis['finalizados']) ? $kpis['finalizados'] : 0;
// Rótulos das situações do atendimento (usado na lista suspensa de filtro).
$abasDisponiveis = [
    'todos'          => 'Todos',
    'sem_tecnico'    => 'Sem Técnico',
    'em_atendimento' => 'Em Atendimento',
    'nao_realizadas' => 'Não Realizadas',
    'finalizados'    => 'Finalizados',
];
$statusDisponiveis = isset($statusDisponiveis) ? $statusDisponiveis : [];
$base = base_url() . 'index.php/os/atribuir';
?>

<div class="new122">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon">
            <i class="fas fa-headset"></i>
        </span>
        <h5>Central de Atendimento</h5>
    </div>

    <!-- ============ KPIs ============ -->
    <div class="tec-kpis">
        <a href="<?= $base ?>?aba=todos" class="tec-kpi <?= $aba === 'todos' ? 'ativo' : '' ?>" style="--kpi:#436eee">
            <span class="tec-kpi-num"><?= (int) $kpis['total'] ?></span>
            <span class="tec-kpi-lbl"><i class='bx bx-list-ul'></i> Chamados abertos</span>
        </a>
        <a href="<?= $base ?>?aba=sem_tecnico" class="tec-kpi <?= $aba === 'sem_tecnico' ? 'ativo' : '' ?>" style="--kpi:#FF7F00">
            <span class="tec-kpi-num"><?= (int) $kpis['sem_tecnico'] ?></span>
            <span class="tec-kpi-lbl"><i class='bx bx-user-x'></i> Sem técnico</span>
        </a>
        <a href="<?= $base ?>?aba=em_atendimento" class="tec-kpi <?= $aba === 'em_atendimento' ? 'ativo' : '' ?>" style="--kpi:#256">
            <span class="tec-kpi-num"><?= (int) $kpis['em_atendimento'] ?></span>
            <span class="tec-kpi-lbl"><i class='bx bx-user-check'></i> Em atendimento</span>
        </a>
        <span class="tec-kpi" style="--kpi:#B266FF; cursor:default">
            <span class="tec-kpi-num"><?= (int) $kpis['aguardando'] ?></span>
            <span class="tec-kpi-lbl"><i class='bx bx-time-five'></i> Aguardando</span>
        </span>
        <a href="<?= $base ?>?aba=nao_realizadas" class="tec-kpi <?= $aba === 'nao_realizadas' ? 'ativo' : '' ?>" style="--kpi:#CD0000">
            <span class="tec-kpi-num"><?= (int) $kpis['nao_realizadas'] ?></span>
            <span class="tec-kpi-lbl"><i class='bx bx-x-circle'></i> Não realizadas</span>
        </a>
        <a href="<?= $base ?>?aba=finalizados" class="tec-kpi <?= $aba === 'finalizados' ? 'ativo' : '' ?>" style="--kpi:#256">
            <span class="tec-kpi-num"><?= (int) $kpis['finalizados'] ?></span>
            <span class="tec-kpi-lbl"><i class='bx bx-check-circle'></i> Finalizados</span>
        </a>
    </div>

    <!-- ============ Filtros (situação / busca / período) ============ -->
    <?php
    $busca    = isset($busca) ? $busca : '';
    $data_de  = isset($data_de) ? $data_de : '';
    $data_ate = isset($data_ate) ? $data_ate : '';
    ?>
    <form method="get" action="<?= $base ?>" class="tec-filtros" id="formFiltros">
        <div class="tec-filtro">
            <label for="filtroAba"><i class='bx bx-filter-alt'></i> Situação:</label>
            <select name="aba" id="filtroAba" class="tec-filtro-select">
                <?php foreach ($abasDisponiveis as $valor => $rotulo) { ?>
                    <option value="<?= $valor ?>" <?= $aba === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                <?php } ?>
            </select>
        </div>
        <?php if ($aba !== 'nao_realizadas') { ?>
            <div class="tec-filtro tec-filtro-busca">
                <label for="filtroBusca"><i class='bx bx-search'></i> Busca:</label>
                <input type="text" name="busca" id="filtroBusca" class="tec-filtro-input"
                    value="<?= htmlspecialchars($busca) ?>" placeholder="Cliente, nº da OS ou descrição">
            </div>
            <div class="tec-filtro">
                <label for="filtroDe">Período:</label>
                <input type="date" name="data_de" id="filtroDe" class="tec-filtro-date" value="<?= htmlspecialchars($data_de) ?>" title="Data inicial (de)">
                <span style="color:#999">até</span>
                <input type="date" name="data_ate" id="filtroAte" class="tec-filtro-date" value="<?= htmlspecialchars($data_ate) ?>" title="Data final (até)">
            </div>
            <div class="tec-filtro">
                <button type="submit" class="button btn btn-mini btn-primary">
                    <span class="button__icon"><i class='bx bx-filter'></i></span><span class="button__text2">Filtrar</span>
                </button>
                <?php if ($busca !== '' || $data_de !== '' || $data_ate !== '') { ?>
                    <a href="<?= $base ?>?aba=<?= $aba ?>" class="button btn btn-mini btn-inverse" title="Limpar filtros">
                        <span class="button__icon"><i class='bx bx-x'></i></span><span class="button__text2">Limpar</span>
                    </a>
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
                                <tr><td colspan="6">Nenhuma OS em espera (não realizada).</td></tr>
                            <?php } else {
                                foreach ($naoRealizadas as $nr) { ?>
                                    <tr>
                                        <td><?= $nr->os_id ?></td>
                                        <td>
                                            <?= htmlspecialchars($nr->nomeCliente ?? '') ?>
                                            <?php if (!empty($nr->telefone)) { ?><br><small><?= $nr->telefone ?></small><?php } ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($nr->motivo_texto ?? '—') ?>
                                            <?php if (!empty($nr->observacao)) { ?>
                                                <br><small style="color:#888"><?= htmlspecialchars($nr->observacao) ?></small>
                                            <?php } ?>
                                        </td>
                                        <td><?= htmlspecialchars($nr->nome_tecnico ?? '—') ?></td>
                                        <td><?= !empty($nr->data_registro) ? date('d/m/Y H:i', strtotime($nr->data_registro)) : '—' ?></td>
                                        <td>
                                            <button class="button btn btn-mini btn-success btn-reagendar"
                                                data-ocorrencia="<?= $nr->idOcorrencia ?>" data-os="<?= $nr->os_id ?>">
                                                <span class="button__icon"><i class='bx bx-calendar'></i></span>
                                                <span class="button__text2">Reagendar</span>
                                            </button>
                                            <button class="button btn btn-mini btn-warning btn-reabrir"
                                                data-ocorrencia="<?= $nr->idOcorrencia ?>" data-os="<?= $nr->os_id ?>">
                                                <span class="button__icon"><i class='bx bx-revision'></i></span>
                                                <span class="button__text2">Reabrir</span>
                                            </button>
                                            <a href="<?= base_url() ?>index.php/os/visualizar/<?= $nr->os_id ?>" class="button btn btn-mini btn-inverse" title="Ver OS">
                                                <span class="button__icon"><i class='bx bx-show'></i></span>
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
        <!-- ============ Abas: Todos / Sem Técnico / Em Atendimento ============ -->
        <div class="widget-box" style="margin-top: 8px">
            <div class="widget-content nopadding">
                <?php if (empty($ordens)) { ?>
                    <div class="tec-vazio"><i class='bx bx-inbox'></i> Nenhuma OS encontrada</div>
                <?php } else { ?>
                    <div class="tec-cards">
                        <?php foreach ($ordens as $os) {
                            $cor = corStatusAtribuir($os->status);
                            $podeMudarStatus = in_array($os->status, $statusDisponiveis, true);
                            $chamadoConcluido = in_array($os->status, ['Finalizado', 'Faturado', 'Cancelado'], true);
                            $ag = ! empty($os->data_agendamento) ? date('d/m/Y H:i', strtotime($os->data_agendamento)) : null;
                            $agInput = ! empty($os->data_agendamento) ? date('Y-m-d\TH:i', strtotime($os->data_agendamento)) : '';
                        ?>
                            <div class="tec-card" style="--cor:<?= $cor ?>">
                                <div class="tec-card-top">
                                    <span class="tec-card-os">OS #<?= sprintf('%04d', $os->idOs) ?></span>
                                    <?php if ($podeMudarStatus) { ?>
                                        <select class="status-inline tec-card-status" data-os="<?= $os->idOs ?>" data-atual="<?= htmlspecialchars($os->status) ?>" style="border-left:4px solid <?= $cor ?>">
                                            <?php foreach ($statusDisponiveis as $st) { ?>
                                                <option value="<?= htmlspecialchars($st) ?>" <?= $os->status === $st ? 'selected' : '' ?>><?= $st ?></option>
                                            <?php } ?>
                                        </select>
                                    <?php } else { ?>
                                        <span class="tec-badge" style="background:<?= $cor ?>"><?= $os->status ?></span>
                                    <?php } ?>
                                </div>
                                <div class="tec-card-cliente"><i class='bx bxs-user'></i> <?= htmlspecialchars($os->nomeCliente) ?>
                                    <?php if (! empty($os->telefone)) { ?><small><?= $os->telefone ?></small><?php } ?>
                                </div>
                                <div class="tec-card-desc"><?= character_limiter(strip_tags($os->descricaoProduto), 70) ?></div>
                                <div class="tec-card-meta">
                                    <span><i class='bx bx-calendar'></i> <?= date('d/m/Y', strtotime($os->dataInicial)) ?></span>
                                    <?php if ($ag) { ?><span class="tec-card-agenda"><i class='bx bx-calendar-event'></i> <?= $ag ?></span><?php } ?>
                                </div>
                                <div class="tec-card-tec">
                                    <?php if ($os->tecnico_responsavel) { ?>
                                        <span class="tec-badge tec-badge-tec"><i class='bx bx-user'></i> <?= htmlspecialchars($os->nome_tecnico) ?></span>
                                    <?php } else { ?>
                                        <span class="tec-badge tec-badge-semtec"><i class='bx bx-user-x'></i> Sem técnico</span>
                                    <?php } ?>
                                </div>
                                <div class="tec-card-acoes">
                                    <?php if (! $chamadoConcluido) { ?>
                                        <button class="button btn btn-mini btn-success btn-atribuir"
                                            data-os="<?= $os->idOs ?>"
                                            data-cliente="<?= htmlspecialchars($os->nomeCliente) ?>"
                                            data-tecnico-atual="<?= $os->tecnico_responsavel ?>"
                                            data-tecnico-nome="<?= htmlspecialchars($os->nome_tecnico ?? '') ?>"
                                            data-agendamento="<?= $agInput ?>">
                                            <span class="button__icon"><i class='bx <?= $os->tecnico_responsavel ? 'bx-calendar-edit' : 'bx-user-plus' ?>'></i></span>
                                            <span class="button__text2"><?= $os->tecnico_responsavel ? 'Agendar / trocar' : 'Atribuir' ?></span>
                                        </button>
                                    <?php } ?>
                                    <a href="<?= base_url() ?>index.php/os/visualizar/<?= $os->idOs ?>" class="button btn btn-mini btn-inverse" title="Ver OS">
                                        <span class="button__icon"><i class='bx bx-show'></i></span>
                                    </a>
                                    <?php if (! $chamadoConcluido && $os->tecnico_responsavel) { ?>
                                        <button class="button btn btn-mini btn-danger btn-remover" data-os="<?= $os->idOs ?>" data-cliente="<?= htmlspecialchars($os->nomeCliente) ?>" title="Remover técnico">
                                            <span class="button__icon"><i class='bx bx-user-x'></i></span>
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

<script type="text/javascript">
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

<style>
.tec-kpis { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
.tec-kpi { flex:1 1 150px; min-width:140px; display:flex; flex-direction:column; gap:4px;
    padding:12px 14px; border-radius:8px; background:#fff; border:1px solid #e5e5e5;
    border-top:3px solid var(--kpi,#436eee); text-decoration:none; color:#333;
    box-shadow:0 1px 2px rgba(0,0,0,.05); transition:transform .1s ease, box-shadow .1s ease; }
.tec-kpi:hover { transform:translateY(-2px); box-shadow:0 3px 8px rgba(0,0,0,.12); color:#333; text-decoration:none; }
.tec-kpi.ativo { box-shadow:0 0 0 2px var(--kpi,#436eee) inset; }
.tec-kpi-num { font-size:26px; font-weight:700; line-height:1; color:var(--kpi,#436eee); }
.tec-kpi-lbl { font-size:12px; color:#666; }
.tec-kpi-lbl i { vertical-align:middle; }
.status-inline { height:auto; }
.tec-filtros { display:flex; align-items:flex-end; gap:14px; margin:14px 0 0; flex-wrap:wrap; }
.tec-filtro { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.tec-filtro label { margin:0; font-size:13px; color:#666; font-weight:600; white-space:nowrap; }
.tec-filtro label i { vertical-align:middle; }
.tec-filtro-select, .tec-filtro-input, .tec-filtro-date {
    height:auto; margin:0; padding:6px 10px; border:1px solid #ccc; border-radius:6px;
    background:#fff; font-size:13px; box-sizing:border-box; }
.tec-filtro-select { min-width:180px; max-width:260px; }
.tec-filtro-busca { flex:1 1 240px; }
.tec-filtro-input { flex:1 1 auto; min-width:200px; }
.tec-filtro-date { width:150px; }
</style>
