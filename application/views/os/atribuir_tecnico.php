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
        default: return '#E0E4CC';
    }
}
$filtro = $this->input->get('filtro');
if (!isset($ordens) || !is_array($ordens)) {
    $ordens = [];
}
?>

<div class="new122">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon">
            <i class="fas fa-user-cog"></i>
        </span>
        <h5>Atribuir Técnico às OS</h5>
    </div>

    <div class="span12" style="margin-left: 0; margin-top: 10px;">
        <div class="span12" style="margin-left: 0">
            <a href="<?php echo base_url(); ?>index.php/os/atribuir" class="button btn btn-mini <?= !$filtro ? 'btn-primary' : 'btn-inverse' ?>">
                <span class="button__icon"><i class='bx bx-list-ul'></i></span><span class="button__text2">Todas</span>
            </a>
            <a href="<?php echo base_url(); ?>index.php/os/atribuir?filtro=sem_tecnico" class="button btn btn-mini <?= $filtro == 'sem_tecnico' ? 'btn-primary' : 'btn-inverse' ?>">
                <span class="button__icon"><i class='bx bx-user-x'></i></span><span class="button__text2">Sem Técnico</span>
            </a>
            <a href="<?php echo base_url(); ?>index.php/os/atribuir?filtro=com_tecnico" class="button btn btn-mini <?= $filtro == 'com_tecnico' ? 'btn-primary' : 'btn-inverse' ?>">
                <span class="button__icon"><i class='bx bx-user-check'></i></span><span class="button__text2">Com Técnico</span>
            </a>
        </div>
    </div>

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
                            <tr>
                                <td colspan="7">Nenhuma OS encontrada</td>
                            </tr>
                        <?php } else {
                            foreach ($ordens as $os) {
                                $cor = corStatusAtribuir($os->status); ?>
                                <tr>
                                    <td><?php echo $os->idOs; ?></td>
                                    <td>
                                        <?php echo $os->nomeCliente; ?>
                                        <?php if (!empty($os->telefone)) { ?><br><small><?php echo $os->telefone; ?></small><?php } ?>
                                    </td>
                                    <td><?php echo character_limiter(strip_tags($os->descricaoProduto), 50); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($os->dataInicial)); ?></td>
                                    <td><span class="badge" style="background-color: <?php echo $cor; ?>; border-color: <?php echo $cor; ?>"><?php echo $os->status; ?></span></td>
                                    <td>
                                        <?php if ($os->tecnico_responsavel) { ?>
                                            <span class="badge" style="background-color: #256; border-color: #256"><i class='bx bx-user'></i> <?php echo $os->nome_tecnico; ?></span>
                                        <?php } else { ?>
                                            <span class="badge" style="background-color: #FF7F00; border-color: #FF7F00"><i class='bx bx-user-x'></i> Não atribuído</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <button class="button btn btn-mini btn-success btn-atribuir"
                                            data-os="<?php echo $os->idOs; ?>"
                                            data-cliente="<?php echo htmlspecialchars($os->nomeCliente); ?>"
                                            data-tecnico-atual="<?php echo $os->tecnico_responsavel; ?>"
                                            data-tecnico-nome="<?php echo htmlspecialchars($os->nome_tecnico ?? ''); ?>">
                                            <span class="button__icon"><i class='bx bx-user-plus'></i></span>
                                            <span class="button__text2"><?php echo $os->tecnico_responsavel ? 'Trocar' : 'Atribuir'; ?></span>
                                        </button>
                                        <a href="<?php echo base_url(); ?>index.php/os/visualizar/<?php echo $os->idOs; ?>" class="button btn btn-mini btn-inverse" title="Ver OS">
                                            <span class="button__icon"><i class='bx bx-show'></i></span>
                                        </a>
                                        <?php if ($os->tecnico_responsavel) { ?>
                                            <button class="button btn btn-mini btn-danger btn-remover"
                                                data-os="<?php echo $os->idOs; ?>"
                                                data-cliente="<?php echo htmlspecialchars($os->nomeCliente); ?>">
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
        <div class="pagination alternate" style="text-align: center;"><?php echo $pagination; ?></div>
    <?php } ?>
</div>

<!-- Modal Atribuir Técnico -->
<div id="modalAtribuir" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modalAtribuirLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 id="modalAtribuirLabel"><i class='bx bx-user-plus'></i> Atribuir Técnico</h4>
    </div>
    <form action="<?php echo base_url(); ?>index.php/os/atribuirTecnicoAction" method="POST" id="formAtribuir">
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
                                    <option value="<?php echo $t->idUsuarios; ?>"><?php echo $t->nome; ?> (<?php echo $t->email; ?>)</option>
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
    <form action="<?php echo base_url(); ?>index.php/os/removerTecnicoAction" method="POST" id="formRemover">
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

<script type="text/javascript">
    $(document).ready(function() {
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
            var osId = $(this).data('os');
            var cliente = $(this).data('cliente');

            $('#os_id_remover').val(osId);
            $('#os_numero_remover').text(osId);
            $('#os_cliente_remover').text(cliente);

            $('#modalRemover').modal('show');
        });

        $('#formAtribuir').submit(function(e) {
            if (!$('#tecnico_id').val()) {
                e.preventDefault();
                alert('Selecione um técnico para atribuir.');
                return false;
            }
        });
    });
</script>
