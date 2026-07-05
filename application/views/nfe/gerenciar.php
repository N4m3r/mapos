<div class="new122">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="fas fa-file-invoice"></i></span>
        <h5>Notas Fiscais</h5>
    </div>

    <div class="span12" style="margin-left: 0">
        <form method="get" action="<?= site_url('nfe/gerenciar') ?>">
            <div class="span3">
                <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'cNfe')) { ?>
                    <a href="<?= site_url('nfe/configuracoes') ?>" class="button btn btn-mini btn-info" style="max-width: 200px">
                        <span class="button__icon"><i class='bx bx-cog'></i></span>
                        <span class="button__text2">Configurações Fiscais</span>
                    </a>
                <?php } ?>
            </div>
            <div class="span2">
                <select name="status" class="span12">
                    <option value="">Todos os status</option>
                    <?php foreach (['autorizada', 'rejeitada', 'cancelada', 'erro', 'pendente'] as $st) { ?>
                        <option value="<?= $st ?>" <?= $this->input->get('status') == $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="span1">
                <button class="button btn btn-mini btn-warning" style="min-width: 30px">
                    <span class="button__icon"><i class='bx bx-search-alt'></i></span>
                </button>
            </div>
        </form>
    </div>

    <?php if (isset($configNfe) && (int) $configNfe->ambiente === 2) { ?>
        <div class="span12" style="margin-left: 0">
            <div class="alert alert-warning" style="margin-bottom: 8px">
                Ambiente de <strong>HOMOLOGAÇÃO</strong>: as notas emitidas não têm valor fiscal.
            </div>
        </div>
    <?php } ?>

    <div class="widget-box">
        <div class="widget-content nopadding tab-content">
            <table id="tabela" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nº / Série</th>
                        <th>Tipo</th>
                        <th>Origem</th>
                        <th>Cliente</th>
                        <th>Valor</th>
                        <th>Emissão</th>
                        <th>Status</th>
                        <th>Retorno</th>
                        <th style="text-align:center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!$results) {
                        echo '<tr><td colspan="9">Nenhuma nota fiscal emitida até o momento.</td></tr>';
                    }
                    foreach ($results as $nota) {
                        $corStatus = match ($nota->status) {
                            'autorizada' => '#4d9c79',
                            'rejeitada' => '#f24c6f',
                            'cancelada' => '#CD0000',
                            'erro' => '#FF7F00',
                            default => '#AEB404',
                        };
                        $origem = $nota->tipo === 'nfe'
                            ? '<a href="' . site_url('vendas/visualizar/' . $nota->vendas_id) . '">Venda ' . $nota->vendas_id . '</a>'
                            : '<a href="' . site_url('os/visualizar/' . $nota->os_id) . '">OS ' . $nota->os_id . '</a>';

                        echo '<tr>';
                        echo '<td>' . $nota->numero . ' / ' . $nota->serie . '</td>';
                        echo '<td>' . ($nota->tipo === 'nfe' ? 'NF-e' : 'NFS-e') . ($nota->ambiente == 2 ? ' <span class="badge" style="background:#AEB404">Homolog.</span>' : '') . '</td>';
                        echo '<td>' . $origem . '</td>';
                        echo '<td>' . html_escape($nota->nomeCliente) . '</td>';
                        echo '<td>R$ ' . number_format($nota->valor_total, 2, ',', '.') . '</td>';
                        echo '<td>' . ($nota->data_emissao ? date('d/m/Y H:i', strtotime($nota->data_emissao)) : '-') . '</td>';
                        echo '<td><span class="badge" style="background-color:' . $corStatus . ';border-color:' . $corStatus . '">' . ucfirst($nota->status) . '</span></td>';
                        echo '<td style="max-width:260px;font-size:11px">' . html_escape(mb_substr((string) $nota->motivo, 0, 160)) . '</td>';
                        echo '<td style="text-align:left;white-space:nowrap">';

                        if ($nota->status === 'autorizada' || $nota->status === 'cancelada') {
                            if (!empty($nota->xml_path)) {
                                echo '<a style="margin-right:1%" href="' . site_url('nfe/xml/' . $nota->idNota) . '" class="btn-nwe6" title="Baixar XML"><i class="bx bx-code-alt bx-xs"></i></a>';
                            }
                            if ($nota->status === 'autorizada') {
                                echo '<a style="margin-right:1%" href="' . site_url('nfe/danfe/' . $nota->idNota) . '" target="_blank" class="btn-nwe6" title="Imprimir ' . ($nota->tipo === 'nfe' ? 'DANFE' : 'DANFSe') . '"><i class="bx bx-printer bx-xs"></i></a>';
                                if ($this->permission->checkPermission($this->session->userdata('permissao'), 'dNfe')) {
                                    echo '<a href="#modal-cancelar-nota" role="button" data-toggle="modal" data-nota="' . $nota->idNota . '" data-numero="' . $nota->numero . '" class="btn-nwe4 btn-cancelar-nota" title="Cancelar Nota"><i class="bx bx-x-circle bx-xs"></i></a>';
                                }
                            }
                        }
                        echo '</td>';
                        echo '</tr>';
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php echo $this->pagination->create_links(); ?>
</div>

<!-- Modal de cancelamento -->
<div id="modal-cancelar-nota" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h5>Cancelar Nota Fiscal</h5>
    </div>
    <div class="modal-body">
        <input type="hidden" id="cancelarIdNota" value="" />
        <p>Cancelar a nota <strong id="cancelarNumeroNota"></strong>? Esta ação é registrada na SEFAZ/Sefin e não pode ser desfeita.</p>
        <label for="justificativa">Justificativa (mínimo 15 caracteres):</label>
        <textarea id="justificativa" rows="3" class="span12" maxlength="255"></textarea>
        <div id="cancelarRetorno"></div>
    </div>
    <div class="modal-footer" style="display:flex;justify-content:center">
        <button class="button btn btn-warning" data-dismiss="modal" aria-hidden="true">
            <span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Fechar</span>
        </button>
        <button id="btnConfirmarCancelamento" class="button btn btn-danger">
            <span class="button__icon"><i class='bx bx-x-circle'></i></span><span class="button__text2">Cancelar Nota</span>
        </button>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $(document).on('click', '.btn-cancelar-nota', function() {
            $('#cancelarIdNota').val($(this).data('nota'));
            $('#cancelarNumeroNota').text('nº ' + $(this).data('numero'));
            $('#cancelarRetorno').html('');
            $('#justificativa').val('');
        });

        $('#btnConfirmarCancelamento').on('click', function() {
            var justificativa = $('#justificativa').val();
            if (justificativa.length < 15) {
                $('#cancelarRetorno').html('<div class="alert alert-danger">A justificativa deve ter no mínimo 15 caracteres.</div>');
                return;
            }
            var btn = $(this);
            btn.attr('disabled', true);
            $('#cancelarRetorno').html('<div class="alert alert-info">Enviando cancelamento...</div>');

            $.post('<?= site_url('nfe/cancelar') ?>', {
                idNota: $('#cancelarIdNota').val(),
                justificativa: justificativa
            }, function(data) {
                btn.attr('disabled', false);
                if (data.success) {
                    $('#cancelarRetorno').html('<div class="alert alert-success">' + data.message + '</div>');
                    setTimeout(function() { window.location.reload(); }, 1500);
                } else {
                    $('#cancelarRetorno').html('<div class="alert alert-danger">' + data.message + '</div>');
                }
            }, 'json').fail(function() {
                btn.attr('disabled', false);
                $('#cancelarRetorno').html('<div class="alert alert-danger">Falha de comunicação com o servidor.</div>');
            });
        });
    });
</script>
