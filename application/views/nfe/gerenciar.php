<div class="new122">
    <div class="ui-cabecalho">
        <div>
            <h1><i class="fas fa-file-invoice"></i> Notas Fiscais</h1>
            <p>Acompanhe NF-e e NFS-e emitidas, baixe XML, imprima e corrija notas rejeitadas.</p>
        </div>
        <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'cNfe')) { ?>
            <div class="ui-acoes">
                <a href="<?= site_url('nfe/configuracoes') ?>" class="button btn btn-info" title="Configurações fiscais">
                    <span class="button__icon"><i class='bx bx-cog'></i></span>
                    <span class="button__text2">Configurações Fiscais</span>
                </a>
                <a href="<?= site_url('cobrancas/configCora') ?>" class="button btn btn-success" title="Configurar cobrança Cora">
                    <span class="button__icon"><i class='bx bx-dollar-circle'></i></span>
                    <span class="button__text2">Configurar Cobrança Cora</span>
                </a>
            </div>
        <?php } ?>
    </div>

    <?php if (isset($configNfe) && (int) $configNfe->ambiente === 2) { ?>
        <div class="ui-dica">
            <i class="bx bx-error"></i>
            <div>Ambiente de <strong>HOMOLOGAÇÃO</strong>: as notas emitidas são apenas de teste e não têm valor fiscal. Mude para produção em Configurações Fiscais quando estiver pronto.</div>
        </div>
    <?php } ?>

    <form method="get" action="<?= site_url('nfe/gerenciar') ?>" class="ui-filtros">
        <div class="ui-campo">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Todos os status</option>
                <?php foreach (['autorizada', 'rejeitada', 'cancelada', 'substituida', 'erro', 'pendente'] as $st) { ?>
                    <option value="<?= $st ?>" <?= $this->input->get('status') == $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="ui-campo ui-campo-acao">
            <button class="button btn btn-warning" title="Filtrar">
                <span class="button__icon"><i class='bx bx-search-alt'></i></span><span class="button__text2">Filtrar</span>
            </button>
        </div>
    </form>

    <div class="widget-box">
        <div class="widget-content nopadding tab-content ui-tabela-rolagem">
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
                        echo '<tr><td colspan="9"><div class="ui-vazio"><i class="bx bx-file"></i><strong>Nenhuma nota fiscal encontrada</strong>As notas emitidas a partir de uma OS ou venda aparecerão aqui.</div></td></tr>';
                    }
                    foreach ($results as $nota) {
                        $classeStatus = match ($nota->status) {
                            'autorizada' => 'sucesso',
                            'rejeitada', 'cancelada', 'erro' => 'perigo',
                            'substituida' => 'info',
                            default => 'alerta',
                        };
                        $origem = $nota->tipo === 'nfe'
                            ? '<a href="' . site_url('vendas/visualizar/' . $nota->vendas_id) . '">Venda ' . $nota->vendas_id . '</a>'
                            : '<a href="' . site_url('os/visualizar/' . $nota->os_id) . '">OS ' . $nota->os_id . '</a>';

                        echo '<tr>';
                        echo '<td>' . $nota->numero . ' / ' . $nota->serie . '</td>';
                        echo '<td>' . ($nota->tipo === 'nfe' ? 'NF-e' : 'NFS-e') . ($nota->ambiente == 2 ? ' <span class="ui-selo alerta">Homolog.</span>' : '') . '</td>';
                        echo '<td>' . $origem . '</td>';
                        echo '<td>' . html_escape($nota->nomeCliente) . '</td>';
                        echo '<td>R$ ' . number_format($nota->valor_total, 2, ',', '.') . '</td>';
                        echo '<td>' . ($nota->data_emissao ? date('d/m/Y H:i', strtotime($nota->data_emissao)) : '-') . '</td>';
                        echo '<td><span class="ui-selo ' . $classeStatus . '">' . ucfirst($nota->status) . '</span></td>';
                        echo '<td style="max-width:260px;font-size:11px">' . html_escape(mb_substr((string) $nota->motivo, 0, 160)) . '</td>';
                        echo '<td><span class="ui-acoes-linha">';

                        if ($nota->status === 'autorizada' || $nota->status === 'cancelada') {
                            echo '<a href="' . site_url('nfe/xml/' . $nota->idNota) . '" class="btn-nwe6" title="Baixar XML" aria-label="Baixar XML"><i class="bx bx-code-alt bx-xs"></i></a>';
                            if ($nota->status === 'autorizada') {
                                echo '<a href="' . site_url('nfe/danfe/' . $nota->idNota) . '" target="_blank" class="btn-nwe6" title="Imprimir ' . ($nota->tipo === 'nfe' ? 'DANFE' : 'DANFSe') . '" aria-label="Imprimir ' . ($nota->tipo === 'nfe' ? 'DANFE' : 'DANFSe') . '"><i class="bx bx-printer bx-xs"></i></a>';
                                // Substituir NFS-e (só Padrão Nacional / serviços)
                                if ($nota->tipo === 'nfse' && $this->permission->checkPermission($this->session->userdata('permissao'), 'eNfe')) {
                                    echo '<a href="#modal-substituir-nota" role="button" data-toggle="modal" data-nota="' . $nota->idNota . '" data-numero="' . $nota->numero . '" class="btn-nwe3 btn-substituir-nota" title="Substituir NFS-e" aria-label="Substituir NFS-e"><i class="bx bx-transfer bx-xs"></i></a>';
                                }
                                if ($this->permission->checkPermission($this->session->userdata('permissao'), 'dNfe')) {
                                    echo '<a href="#modal-cancelar-nota" role="button" data-toggle="modal" data-nota="' . $nota->idNota . '" data-numero="' . $nota->numero . '" class="btn-nwe4 btn-cancelar-nota" title="Cancelar Nota" aria-label="Cancelar Nota"><i class="bx bx-x-circle bx-xs"></i></a>';
                                }
                            }
                        } elseif (in_array($nota->status, ['rejeitada', 'erro'])) {
                            // Editar a origem (OS ou Venda) para corrigir os dados
                            $editarUrl = ($nota->tipo === 'nfe' && !empty($nota->vendas_id))
                                ? site_url('vendas/editar/' . $nota->vendas_id)
                                : site_url('os/editar/' . $nota->os_id);
                            echo '<a href="' . $editarUrl . '" class="btn-nwe3" title="Editar origem para corrigir os dados" aria-label="Editar origem para corrigir os dados"><i class="bx bx-edit bx-xs"></i></a>';

                            // Retransmitir (mantém o mesmo número)
                            if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eNfe')) {
                                if ($nota->tipo === 'nfse') {
                                    $endpoint = site_url('nfe/emitirNfse/' . $nota->os_id);
                                } elseif (!empty($nota->os_id)) {
                                    $endpoint = site_url('nfe/emitirNfeOs/' . $nota->os_id);
                                } else {
                                    $endpoint = site_url('nfe/emitirNfe/' . $nota->vendas_id);
                                }
                                echo '<a href="#modal-retransmitir" role="button" data-toggle="modal" data-endpoint="' . $endpoint . '" data-numero="' . $nota->numero . '" class="btn-nwe5 btn-retransmitir" title="Retransmitir (mantém o nº ' . $nota->numero . ')" aria-label="Retransmitir (mantém o nº ' . $nota->numero . ')"><i class="bx bx-refresh bx-xs"></i></a>';
                            }
                        }
                        echo '</span></td>';
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

<!-- Modal de retransmissão -->
<div id="modal-retransmitir" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h5>Retransmitir Nota Fiscal</h5>
    </div>
    <div class="modal-body">
        <input type="hidden" id="retransmitirEndpoint" value="" />
        <p>Retransmitir a nota <strong id="retransmitirNumero"></strong>? O <strong>mesmo número</strong> será reaproveitado. Corrija os dados na origem (OS/Venda) antes, se necessário.</p>
        <div id="retransmitirRetorno"></div>
        <div id="retransmitirAcoes" style="display:none;text-align:center;margin-top:10px">
            <a id="retransmitirBtnDanfe" href="#" target="_blank" class="button btn btn-inverse">
                <span class="button__icon"><i class='bx bx-printer'></i></span><span class="button__text2">Imprimir</span>
            </a>
        </div>
    </div>
    <div class="modal-footer" style="display:flex;justify-content:center">
        <button class="button btn btn-warning" data-dismiss="modal" aria-hidden="true">
            <span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Fechar</span>
        </button>
        <button id="btnConfirmarRetransmitir" class="button btn btn-success">
            <span class="button__icon"><i class='bx bx-send'></i></span><span class="button__text2">Retransmitir</span>
        </button>
    </div>
</div>

<!-- Modal de substituição de NFS-e -->
<div id="modal-substituir-nota" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h5>Substituir NFS-e</h5>
    </div>
    <div class="modal-body">
        <input type="hidden" id="substituirIdNota" value="" />
        <p>Substituir a NFS-e <strong id="substituirNumero"></strong> por uma <strong>nova</strong> NFS-e (com os dados atuais da OS). A original ficará marcada como <strong>substituída</strong>.</p>
        <label for="substituirMotivo">Motivo da substituição:</label>
        <select id="substituirMotivo" class="span12">
            <option value="99">99 - Outros (descreva abaixo)</option>
            <option value="1">01 - Desenquadramento de NFS-e do Simples Nacional</option>
            <option value="2">02 - Enquadramento de NFS-e no Simples Nacional</option>
            <option value="3">03 - Inclusão retroativa de imunidade/isenção</option>
            <option value="4">04 - Exclusão retroativa de imunidade/isenção</option>
            <option value="5">05 - Rejeição da NFS-e pelo tomador/intermediário</option>
        </select>
        <label for="substituirDescricao" style="margin-top:8px">Descrição do motivo (mín. 15 caracteres p/ "Outros"):</label>
        <textarea id="substituirDescricao" rows="2" class="span12" maxlength="255" placeholder="Ex.: correção de valor/descrição do serviço"></textarea>
        <div id="substituirRetorno" style="margin-top:6px"></div>
    </div>
    <div class="modal-footer" style="display:flex;justify-content:center">
        <button class="button btn btn-warning" data-dismiss="modal" aria-hidden="true">
            <span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Fechar</span>
        </button>
        <button id="btnConfirmarSubstituir" class="button btn btn-primary">
            <span class="button__icon"><i class='bx bx-transfer'></i></span><span class="button__text2">Substituir</span>
        </button>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        var retransmitirOk = false;
        var substituirOk = false;

        // Monta o alerta de erro fiscal com a mensagem legível e, se o servidor
        // devolver, um bloco expansível com o retorno técnico completo do Sefin.
        function erroFiscalHtml(data) {
            var msg = (data && data.message) ? data.message : 'Falha na operação.';
            var html = '<div class="alert alert-danger" style="white-space:pre-wrap">' + $('<div>').text(msg).html();
            if (data && data.detalhe) {
                var id = 'detFiscal' + Date.now();
                html += '<div style="margin-top:6px">'
                    + '<a href="#" class="toggle-det-fiscal" data-alvo="' + id + '" style="font-size:12px">▸ Ver detalhes técnicos</a>'
                    + '<pre id="' + id + '" style="display:none;margin-top:6px;max-height:260px;overflow:auto;font-size:11px;white-space:pre-wrap">'
                    + $('<div>').text(data.detalhe).html() + '</pre></div>';
            }
            return html + '</div>';
        }

        $(document).on('click', '.toggle-det-fiscal', function(e) {
            e.preventDefault();
            var alvo = $('#' + $(this).data('alvo'));
            alvo.toggle();
            $(this).html(alvo.is(':visible') ? '▾ Ocultar detalhes técnicos' : '▸ Ver detalhes técnicos');
        });

        $(document).on('click', '.btn-substituir-nota', function() {
            substituirOk = false;
            $('#substituirIdNota').val($(this).data('nota'));
            $('#substituirNumero').text('nº ' + $(this).data('numero'));
            $('#substituirRetorno').html('');
            $('#substituirDescricao').val('');
            $('#substituirMotivo').val('99');
            $('#btnConfirmarSubstituir').show().attr('disabled', false);
        });

        $('#btnConfirmarSubstituir').on('click', function() {
            var motivo = $('#substituirMotivo').val();
            var descricao = $('#substituirDescricao').val();
            if (motivo === '99' && descricao.trim().length < 15) {
                $('#substituirRetorno').html('<div class="alert alert-danger">Para "Outros" (99), a descrição precisa ter no mínimo 15 caracteres.</div>');
                return;
            }
            var btn = $(this);
            btn.attr('disabled', true);
            $('#substituirRetorno').html('<div class="alert alert-info">Substituindo, aguarde...</div>');

            $.post('<?= site_url('nfe/substituirNfse') ?>/' + $('#substituirIdNota').val(), {
                cMotivo: motivo,
                xMotivo: descricao
            }, function(data) {
                if (data.success) {
                    substituirOk = true;
                    $('#substituirRetorno').html('<div class="alert alert-success">' + data.message + '</div>');
                    btn.hide();
                } else {
                    $('#substituirRetorno').html(erroFiscalHtml(data));
                    btn.attr('disabled', false);
                }
            }, 'json').fail(function() {
                $('#substituirRetorno').html('<div class="alert alert-danger">Falha de comunicação com o servidor.</div>');
                btn.attr('disabled', false);
            });
        });

        $('#modal-substituir-nota').on('hidden hidden.bs.modal', function() {
            if (substituirOk) { window.location.reload(); }
        });

        $(document).on('click', '.btn-retransmitir', function() {
            retransmitirOk = false;
            $('#retransmitirEndpoint').val($(this).data('endpoint'));
            $('#retransmitirNumero').text('nº ' + $(this).data('numero'));
            $('#retransmitirRetorno').html('');
            $('#retransmitirAcoes').hide();
            $('#btnConfirmarRetransmitir').show().attr('disabled', false);
        });

        $('#btnConfirmarRetransmitir').on('click', function() {
            var btn = $(this);
            btn.attr('disabled', true);
            $('#retransmitirRetorno').html('<div class="alert alert-info">Retransmitindo, aguarde...</div>');

            $.post($('#retransmitirEndpoint').val(), {}, function(data) {
                if (data.success) {
                    retransmitirOk = true;
                    $('#retransmitirRetorno').html('<div class="alert alert-success">' + data.message + '</div>');
                    if (data.urlDanfe) {
                        $('#retransmitirBtnDanfe').attr('href', data.urlDanfe);
                        $('#retransmitirAcoes').show();
                    }
                    btn.hide();
                } else {
                    $('#retransmitirRetorno').html(erroFiscalHtml(data));
                    btn.attr('disabled', false);
                }
            }, 'json').fail(function() {
                $('#retransmitirRetorno').html('<div class="alert alert-danger">Falha de comunicação com o servidor.</div>');
                btn.attr('disabled', false);
            });
        });

        $('#modal-retransmitir').on('hidden hidden.bs.modal', function() {
            if (retransmitirOk) { window.location.reload(); }
        });

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
                    $('#cancelarRetorno').html(erroFiscalHtml(data));
                }
            }, 'json').fail(function() {
                btn.attr('disabled', false);
                $('#cancelarRetorno').html('<div class="alert alert-danger">Falha de comunicação com o servidor.</div>');
            });
        });
    });
</script>
