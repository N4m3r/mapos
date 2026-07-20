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
$kpis = isset($kpis) ? $kpis : ['total' => 0, 'sem_tecnico' => 0, 'em_atendimento' => 0, 'aguardando' => 0, 'nao_realizadas' => 0];
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
    </div>

    <!-- ============ Abas ============ -->
    <div class="span12" style="margin-left: 0; margin-top: 12px;">
        <a href="<?= $base ?>?aba=todos" class="button btn btn-mini <?= $aba === 'todos' ? 'btn-primary' : 'btn-inverse' ?>">
            <span class="button__icon"><i class='bx bx-list-ul'></i></span><span class="button__text2">Todos</span>
        </a>
        <a href="<?= $base ?>?aba=sem_tecnico" class="button btn btn-mini <?= $aba === 'sem_tecnico' ? 'btn-primary' : 'btn-inverse' ?>">
            <span class="button__icon"><i class='bx bx-user-x'></i></span><span class="button__text2">Sem Técnico</span>
        </a>
        <a href="<?= $base ?>?aba=em_atendimento" class="button btn btn-mini <?= $aba === 'em_atendimento' ? 'btn-primary' : 'btn-inverse' ?>">
            <span class="button__icon"><i class='bx bx-user-check'></i></span><span class="button__text2">Em Atendimento</span>
        </a>
        <a href="<?= $base ?>?aba=nao_realizadas" class="button btn btn-mini <?= $aba === 'nao_realizadas' ? 'btn-primary' : 'btn-inverse' ?>">
            <span class="button__icon"><i class='bx bx-x-circle'></i></span><span class="button__text2">Não Realizadas</span>
        </a>
    </div>

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
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Cliente</th>
                                <th>Descrição</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Técnico Atual</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ordens)) { ?>
                                <tr><td colspan="7">Nenhuma OS encontrada</td></tr>
                            <?php } else {
                                foreach ($ordens as $os) {
                                    $cor = corStatusAtribuir($os->status);
                                    $podeMudarStatus = in_array($os->status, $statusDisponiveis, true); ?>
                                    <tr>
                                        <td><?= $os->idOs ?></td>
                                        <td>
                                            <?= htmlspecialchars($os->nomeCliente) ?>
                                            <?php if (!empty($os->telefone)) { ?><br><small><?= $os->telefone ?></small><?php } ?>
                                        </td>
                                        <td><?= character_limiter(strip_tags($os->descricaoProduto), 50) ?></td>
                                        <td><?= date('d/m/Y', strtotime($os->dataInicial)) ?></td>
                                        <td>
                                            <?php if ($podeMudarStatus) { ?>
                                                <select class="status-inline" data-os="<?= $os->idOs ?>" data-atual="<?= htmlspecialchars($os->status) ?>"
                                                    style="border-left:4px solid <?= $cor ?>; padding:2px 4px; max-width:150px;">
                                                    <?php foreach ($statusDisponiveis as $st) { ?>
                                                        <option value="<?= htmlspecialchars($st) ?>" <?= $os->status === $st ? 'selected' : '' ?>><?= $st ?></option>
                                                    <?php } ?>
                                                </select>
                                            <?php } else { ?>
                                                <span class="badge" style="background-color: <?= $cor ?>; border-color: <?= $cor ?>"><?= $os->status ?></span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if ($os->tecnico_responsavel) { ?>
                                                <span class="badge" style="background-color: #256; border-color: #256"><i class='bx bx-user'></i> <?= htmlspecialchars($os->nome_tecnico) ?></span>
                                            <?php } else { ?>
                                                <span class="badge" style="background-color: #FF7F00; border-color: #FF7F00"><i class='bx bx-user-x'></i> Não atribuído</span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <button class="button btn btn-mini btn-success btn-atribuir"
                                                data-os="<?= $os->idOs ?>"
                                                data-cliente="<?= htmlspecialchars($os->nomeCliente) ?>"
                                                data-tecnico-atual="<?= $os->tecnico_responsavel ?>"
                                                data-tecnico-nome="<?= htmlspecialchars($os->nome_tecnico ?? '') ?>">
                                                <span class="button__icon"><i class='bx bx-user-plus'></i></span>
                                                <span class="button__text2"><?= $os->tecnico_responsavel ? 'Trocar' : 'Atribuir'; ?></span>
                                            </button>
                                            <a href="<?= base_url() ?>index.php/os/visualizar/<?= $os->idOs ?>" class="button btn btn-mini btn-inverse" title="Ver OS">
                                                <span class="button__icon"><i class='bx bx-show'></i></span>
                                            </a>
                                            <?php if ($os->tecnico_responsavel) { ?>
                                                <button class="button btn btn-mini btn-danger btn-remover"
                                                    data-os="<?= $os->idOs ?>"
                                                    data-cliente="<?= htmlspecialchars($os->nomeCliente) ?>">
                                                    <span class="button__icon"><i class='bx bx-user-x'></i></span>
                                                </button>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php }
                            } ?>
                        </tbody>
                    </table>
                </div>
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

        // ---- Atribuir / Trocar técnico ----
        $('.btn-atribuir').click(function() {
            var osId = $(this).data('os');
            var cliente = $(this).data('cliente');
            var tecnicoAtual = $(this).data('tecnico-atual');
            var tecnicoNome = $(this).data('tecnico-nome');

            $('#os_id_atribuir').val(osId);
            $('#os_numero').text(osId);
            $('#os_cliente').text(cliente);

            if (tecnicoAtual) {
                $('#tecnico-atual-nome').text(tecnicoNome);
                $('#tecnico-atual-info').removeClass('hide');
                $('#modalAtribuirLabel').html('<i class="bx bx-transfer"></i> Trocar Técnico');
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
</style>
