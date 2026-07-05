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

<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>

<style>
    /* Paleta de cores usando variáveis do tema do sistema */
    .tecnico-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    .tecnico-atribuido {
        background: var(--success-bg, #d4edda);
        color: var(--success-text, #155724);
        border: 1px solid var(--success-border, #c3e6cb);
    }
    .tecnico-pendente {
        background: var(--warning-bg, #fff3cd);
        color: var(--warning-text, #856404);
        border: 1px solid var(--warning-border, #ffeeba);
    }
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }
    .historico-item {
        border-left: 3px solid var(--dark-azul, #1086dd);
        padding-left: 10px;
        margin-bottom: 10px;
    }
    .filtro-btn {
        margin-right: 5px;
        margin-bottom: 5px;
        background: var(--widget-box-w, #ffffff);
        color: var(--title-w, #42484e);
        border: 1px solid var(--cinza0, #9aa6b3);
    }
    .filtro-btn:hover {
        background: var(--bodycolor-w, #f3f4f6);
        color: var(--title-w, #42484e);
    }
    .filtro-btn.active {
        background: var(--dark-azul, #1086dd);
        color: var(--white, #ffffff);
        border-color: var(--dark-azul, #1086dd);
    }
    /* Dark theme adjustments */
    body[data-theme="dark-violet"] .filtro-btn,
    body[data-theme="pure-dark"] .filtro-btn {
        background: var(--dark-violet-widg, #291a57);
        color: var(--branco, #caced8);
        border-color: var(--dark-violet-side, #6b29f8);
    }
    body[data-theme="dark-violet"] .filtro-btn.active,
    body[data-theme="pure-dark"] .filtro-btn.active {
        background: var(--dark-azul, #1086dd);
        color: var(--white, #ffffff);
    }
    /* Tabela com tema */
    .table-striped tbody tr:nth-child(odd) {
        background-color: var(--bodycolor-w, #f8f9fa);
    }
    body[data-theme="dark-violet"] .table-striped tbody tr:nth-child(odd) {
        background-color: var(--dark-violet-cont, #1b1239);
    }
    body[data-theme="pure-dark"] .table-striped tbody tr:nth-child(odd) {
        background-color: var(--dark-1, #14141a);
    }
    /* Header da tabela */
    .table thead th {
        background: var(--widget-box-w, #f8f9fa);
        color: var(--title-w, #42484e);
        font-weight: 600;
        border-bottom: 2px solid var(--cinza0, #9aa6b3);
    }
    body[data-theme="dark-violet"] .table thead th {
        background: var(--dark-violet-widg, #291a57);
        color: var(--dark-violet-tit2, #c3b2e9);
        border-bottom-color: var(--dark-violet-side, #6b29f8);
    }
    body[data-theme="pure-dark"] .table thead th {
        background: var(--wid-dark, #1c1d26);
        color: var(--branco, #caced8);
        border-bottom-color: var(--dark-2, #272835);
    }
    /* Paginação */
    .pagination a {
        background: var(--widget-box-w, #ffffff);
        color: var(--title-w, #42484e);
        border: 1px solid var(--cinza0, #9aa6b3);
    }
    .pagination a:hover {
        background: var(--dark-azul, #1086dd);
        color: var(--white, #ffffff);
    }
    .pagination .current {
        background: var(--dark-azul, #1086dd);
        color: var(--white, #ffffff);
        border-color: var(--dark-azul, #1086dd);
    }
    body[data-theme="dark-violet"] .pagination a {
        background: var(--dark-violet-widg, #291a57);
        color: var(--branco, #caced8);
        border-color: var(--dark-violet-side, #6b29f8);
    }
    body[data-theme="pure-dark"] .pagination a {
        background: var(--wid-dark, #1c1d26);
        color: var(--branco, #caced8);
        border-color: var(--dark-2, #272835);
    }
</style>

<div class="new122">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon">
            <i class="fas fa-user-cog"></i>
        </span>
        <h5>Atribuir Técnico às OS</h5>
    </div>

    <div class="span12" style="margin-left: 0; margin-top: 10px;">
        <!-- Filtros rápidos -->
        <div class="span12" style="margin-left: 0; margin-bottom: 10px;">
            <a href="<?php echo base_url(); ?>index.php/os/atribuir" class="button btn btn-mini <?= !isset($_GET['filtro']) ? 'filtro-btn active' : 'filtro-btn' ?>">
                <span class="button__icon"><i class='bx bx-list-ul'></i></span>
                <span class="button__text2">Todas</span>
            </a>
            <a href="<?php echo base_url(); ?>index.php/os/atribuir?filtro=sem_tecnico" class="button btn btn-mini <?= isset($_GET['filtro']) && $_GET['filtro'] == 'sem_tecnico' ? 'filtro-btn active' : 'filtro-btn' ?>">
                <span class="button__icon"><i class='bx bx-user-x'></i></span>
                <span class="button__text2">Sem Técnico</span>
            </a>
            <a href="<?php echo base_url(); ?>index.php/os/atribuir?filtro=com_tecnico" class="button btn btn-mini <?= isset($_GET['filtro']) && $_GET['filtro'] == 'com_tecnico' ? 'filtro-btn active' : 'filtro-btn' ?>">
                <span class="button__icon"><i class='bx bx-user-check'></i></span>
                <span class="button__text2">Com Técnico</span>
            </a>
        </div>

        <!-- Tabela de OS -->
        <div class="widget-box" style="margin-top: 8px">
            <div class="widget-content nopadding">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 60px;">N°</th>
                                <th>Cliente</th>
                                <th>Descrição</th>
                                <th style="width: 100px;">Data</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 150px;">Técnico Atual</th>
                                <th style="width: 180px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Garantir que $ordens seja um array
                            if (!isset($ordens) || !is_array($ordens)) {
                                $ordens = [];
                            }
                            ?>
                            <?php if (empty($ordens)): ?>
                                <tr>
                                    <td colspan="7" class="text-center" style="padding: 30px; color: var(--cinza0, #9aa6b3);">
                                        <i class='bx bx-inbox' style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                                        Nenhuma OS encontrada
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ordens as $os): ?>
                                    <tr>
                                        <td class="text-center">
                                            <strong>#<?php echo $os->idOs; ?></strong>
                                        </td>
                                        <td>
                                            <?php echo $os->nomeCliente; ?><br>
                                            <small style="color: var(--cinza0, #9aa6b3);"><?php echo $os->telefone; ?></small>
                                        </td>
                                        <td>
                                            <?php echo character_limiter(strip_tags($os->descricaoProduto), 50); ?>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($os->dataInicial)); ?>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            // Cores de status usando variáveis CSS
                                            $cor_status = 'var(--cinza0, #6c757d)';
                                            switch ($os->status) {
                                                case 'Aberto': $cor_status = 'var(--success, #02b470)'; break;
                                                case 'Em Andamento': $cor_status = 'var(--warning, #e68606)'; break;
                                                case 'Finalizado': $cor_status = 'var(--info, #0386eb)'; break;
                                                case 'Faturado': $cor_status = 'var(--success, #28a745)'; break;
                                                case 'Cancelado': $cor_status = 'var(--danger, #f30b0b)'; break;
                                                case 'Orçamento': $cor_status = 'var(--warning, #ffc107)'; break;
                                                case 'Aguardando Peças': $cor_status = 'var(--info, #17a2b8)'; break;
                                            }
                                            ?>
                                            <span class="status-badge" style="background: transparent; color: <?php echo $cor_status; ?>; border: 1px solid <?php echo $cor_status; ?>;">
                                                <?php echo $os->status; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($os->tecnico_responsavel): ?>
                                                <span class="tecnico-badge tecnico-atribuido" title="Técnico atual">
                                                    <i class='bx bx-user'></i> <?php echo $os->nome_tecnico; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="tecnico-badge tecnico-pendente">
                                                    <i class='bx bx-user-x'></i> Não atribuído
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button class="btn btn-mini btn-success btn-atribuir"
                                                        data-os="<?php echo $os->idOs; ?>"
                                                        data-cliente="<?php echo htmlspecialchars($os->nomeCliente); ?>"
                                                        data-tecnico-atual="<?php echo $os->tecnico_responsavel; ?>"
                                                        data-tecnico-nome="<?php echo htmlspecialchars($os->nome_tecnico ?? ''); ?>">
                                                    <i class='bx bx-user-plus'></i>
                                                    <?php echo $os->tecnico_responsavel ? 'Trocar' : 'Atribuir'; ?>
                                                </button>
                                                <a href="<?php echo base_url(); ?>index.php/os/visualizar/<?php echo $os->idOs; ?>"
                                                   class="btn btn-mini btn-info" title="Ver OS">
                                                    <i class='bx bx-show'></i>
                                                </a>
                                                <?php if ($os->tecnico_responsavel): ?>
                                                    <button class="btn btn-mini btn-danger btn-remover"
                                                            data-os="<?php echo $os->idOs; ?>"
                                                            data-cliente="<?php echo htmlspecialchars($os->nomeCliente); ?>">
                                                        <i class='bx bx-user-x'></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Paginação -->
        <?php if (isset($pagination) && $pagination): ?>
            <div class="pagination" style="text-align: center; margin-top: 15px; margin-bottom: 20px;">
                <?php echo $pagination; ?>
            </div>
        <?php endif; ?>

        <!-- Info de paginação -->
        <?php if (!empty($ordens)): ?>
            <div style="text-align: center; color: var(--cinza0, #9aa6b3); font-size: 12px; margin-top: 5px;">
                <i class='bx bx-info-circle'></i>
                Mostrando <?php echo count($ordens); ?> OS por página.
                <?php if (count($ordens) >= 20): ?>
                    Use os filtros acima ou navegue pelas páginas.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Atribuir Técnico -->
<div id="modalAtribuir" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modalAtribuirLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 id="modalAtribuirLabel">
            <i class='bx bx-user-plus'></i> Atribuir Técnico
        </h4>
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
                    <select name="tecnico_id" id="tecnico_id" class="span12" required style="background: var(--bodycolor-w, #ffffff); color: var(--title-w, #42484e); border: 1px solid var(--cinza0, #9aa6b3);">
                        <option value="">Selecione um técnico...</option>
                        <?php if (!empty($tecnicos) && is_array($tecnicos)): ?>
                            <?php foreach ($tecnicos as $t): ?>
                                <?php if (is_object($t)): ?>
                                    <option value="<?php echo $t->idUsuarios; ?>">
                                        <?php echo $t->nome; ?> (<?php echo $t->email; ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Nenhum técnico disponível</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="observacao">Observação:</label>
                <div class="controls">
                    <textarea name="observacao" id="observacao" class="span12" rows="2" placeholder="Motivo da atribuição (opcional)" style="background: var(--bodycolor-w, #ffffff); color: var(--title-w, #42484e); border: 1px solid var(--cinza0, #9aa6b3);"></textarea>
                </div>
            </div>

            <div id="tecnico-atual-info" class="alert alert-info hide" style="background: var(--info-bg, #d1ecf1); color: var(--info-text, #0c5460); border-color: var(--info-border, #bee5eb);">
                <strong>Técnico atual:</strong> <span id="tecnico-atual-nome"></span><br>
                <small>Ao atribuir um novo técnico, o atual será substituído.</small>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
            <button type="submit" class="btn btn-success">
                <i class='bx bx-check'></i> Confirmar Atribuição
            </button>
        </div>
    </form>
</div>

<!-- Modal Remover Técnico -->
<div id="modalRemover" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modalRemoverLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 id="modalRemoverLabel">
            <i class='bx bx-user-x'></i> Remover Técnico
        </h4>
    </div>
    <form action="<?php echo base_url(); ?>index.php/os/removerTecnicoAction" method="POST" id="formRemover">
        <div class="modal-body">
            <input type="hidden" name="os_id" id="os_id_remover">

            <div class="alert alert-warning" style="background: var(--warning-bg, #fff3cd); color: var(--warning-text, #856404); border-color: var(--warning-border, #ffeeba);">
                <p>Tem certeza que deseja remover o técnico da OS #<strong id="os_numero_remover"></strong>?</p>
                <p>Cliente: <strong id="os_cliente_remover"></strong></p>
            </div>

            <div class="control-group">
                <label class="control-label" for="motivo">Motivo da remoção:</label>
                <div class="controls">
                    <textarea name="motivo" id="motivo" class="span12" rows="2" placeholder="Informe o motivo (opcional)" style="background: var(--bodycolor-w, #ffffff); color: var(--title-w, #42484e); border: 1px solid var(--cinza0, #9aa6b3);"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
            <button type="submit" class="btn btn-danger">
                <i class='bx bx-trash'></i> Confirmar Remoção
            </button>
        </div>
    </form>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // Botão Atribuir/Trocar Técnico
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

        // Botão Remover Técnico
        $('.btn-remover').click(function() {
            var osId = $(this).data('os');
            var cliente = $(this).data('cliente');

            $('#os_id_remover').val(osId);
            $('#os_numero_remover').text(osId);
            $('#os_cliente_remover').text(cliente);

            $('#modalRemover').modal('show');
        });

        // Validação do formulário de atribuição
        $('#formAtribuir').submit(function(e) {
            if (!$('#tecnico_id').val()) {
                e.preventDefault();
                alert('Selecione um técnico para atribuir.');
                return false;
            }
        });
    });
</script>
