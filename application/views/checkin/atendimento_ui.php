<?php
/**
 * UI de Atendimento (check-in / check-out) reutilizavel.
 * Renderiza os modais de assinatura + foto e inicializa o CheckinManager.
 * Reaproveita a mesma logica (assinatura-canvas.js / checkin-fotos.js / checkin.js)
 * usada na tela administrativa (os/visualizarOs.php).
 *
 * Variaveis esperadas:
 *   $os_id             (int)     - id da OS
 *   $nome_cliente      (string)  - pre-preenche a assinatura do cliente
 *   $documento_cliente (string)  - opcional
 */
$os_id = isset($os_id) ? (int) $os_id : 0;
$nome_cliente = isset($nome_cliente) ? $nome_cliente : '';
$documento_cliente = isset($documento_cliente) ? $documento_cliente : '';
$nao_programada = !empty($nao_programada);
?>
<style>
    .hidden { display: none !important; }
    .checkin-section { margin-bottom: 18px; padding-bottom: 16px; border-bottom: 1px solid var(--tec-line,#e0e3ee); }
    .checkin-section:last-child { border-bottom: none; }
    .checkin-section h6 { color: var(--tec-muted,#555); margin: 0 0 10px; font-weight: 700; }
    .upload-area { border: 2px dashed var(--tec-input-line,#8a90a8); border-radius: 8px; padding: 18px; text-align: center; margin-bottom: 10px; background: var(--tec-surface-2,#fafafa); color: var(--tec-ink,inherit); }
    .preview-fotos-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 8px; margin-top: 10px; }
    .assinatura-canvas-wrapper canvas { touch-action: none; -webkit-user-select: none; user-select: none; }
    #modal-checkin .modal-body, #modal-checkout .modal-body { max-height: 74vh; overflow-y: auto; }
    @media (max-width: 768px) {
        #modal-checkin .modal-dialog, #modal-checkout .modal-dialog { margin: 10px auto; max-width: 94%; }
        #modal-checkout .row .col-md-6 { width: 100% !important; float: none !important; margin-left: 0 !important; margin-bottom: 14px; }
        .assinatura-canvas-wrapper canvas { width: 100% !important; height: auto !important; min-height: 200px !important; }
    }
    /* Estado e proxima acao do atendimento */
    .ui-acao-grande { display: flex; width: 100%; justify-content: center; align-items: center; gap: 8px; min-height: 52px; font-size: 16px; font-weight: 700; border-radius: 12px; box-sizing: border-box; }
    #modal-checkin .modal-footer .ui-acao-grande, #modal-checkout .modal-footer .ui-acao-grande { width: auto; min-height: 44px; font-size: 15px; }
    @media (max-width: 768px) {
        .modal-footer .btn { float: none; display: block; width: 100%; box-sizing: border-box; margin: 6px 0 0; }
        .modal-footer .ui-acao-grande { width: 100%; }
        .upload-area .btn { width: 100%; margin: 4px 0; box-sizing: border-box; }
    }
</style>


<!-- Modal Check-in -->
<div id="modal-checkin" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
        <h3 class="modal-title"><i class="bx bx-log-in"></i> Cheguei no local</h3>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
    <div class="modal-body">
        <div class="ui-dica"><i class="bx bx-info-circle"></i><div>Assine no quadro e toque em "Cheguei" para registrar sua chegada.</div></div>
        <form id="form-checkin">
            <div class="checkin-section">
                <h6><i class="bx bx-pen"></i> Assinatura do Técnico</h6>
                <?php $this->load->view('checkin/assinatura_canvas', [
                    'id' => 'assinatura-tecnico-entrada',
                    'titulo' => 'Assine no quadro abaixo',
                    'mostrar_campos' => false,
                ]); ?>
            </div>
            <div class="checkin-section secao-foto-entrada">
                <h6><i class="bx bx-camera"></i> Fotos de antes</h6>
                <div class="upload-area">
                    <input type="file" id="fotos-entrada-input" class="checkin-foto-input form-control" data-etapa="entrada" accept="image/*" style="display:none;">
                    <button type="button" class="btn btn-info btn-capturar-foto" data-etapa="entrada"><i class="bx bx-camera"></i> Tirar Foto</button>
                    <button type="button" class="btn btn-outline-secondary" data-acao="clicar" data-alvo="#fotos-entrada-input"><i class="bx bx-upload"></i> Escolher da Galeria</button>
                    <p class="text-muted" style="margin:10px 0 0;">Máximo 5MB por foto (JPG, PNG)</p>
                </div>
                <div id="preview-fotos-entrada" class="preview-fotos-container"></div>
            </div>
            <div class="checkin-section">
                <h6><i class="bx bx-note"></i> Observações de Entrada</h6>
                <textarea id="checkin-observacao" class="w-100 form-control" rows="3" placeholder="Estado inicial, equipamento recebido, etc."></textarea>
            </div>
            <!-- Localização capturada em segundo plano (não exibida no modal) -->
            <div class="checkin-section" style="display:none;">
                <input type="hidden" id="checkin-latitude">
                <input type="hidden" id="checkin-longitude">
                <span id="checkin-geo-status"></span>
            </div>
            <div id="formularios-iniciar" class="checkin-section"></div>
        </form>
    </div>
    <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" aria-hidden="true">Cancelar</button>
        <button class="btn btn-success ui-acao-grande" id="btn-confirmar-checkin"><i class="bx bx-log-in"></i> Cheguei</button>
    </div>
</div></div></div>

<!-- Modal Check-out -->
<div id="modal-checkout" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-nao-programada="<?= $nao_programada ? 1 : 0 ?>"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
        <h3 class="modal-title"><i class="bx bx-log-out"></i> Concluir atendimento</h3>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
    <div class="modal-body">
        <div class="ui-dica alerta"><i class="bx bx-info-circle"></i><div>Peça a assinatura do cliente, confira e toque em "Concluir". Depois disso o atendimento é encerrado.</div></div>
        <form id="form-checkout">
            <div class="checkin-section">
                <h6><i class="bx bx-pen"></i> Assinaturas</h6>
                <div class="row">
                    <div class="col-md-6">
                        <?php $this->load->view('checkin/assinatura_canvas', [
                            'id' => 'assinatura-tecnico-saida',
                            'titulo' => 'Assinatura do Técnico',
                            'mostrar_campos' => false,
                        ]); ?>
                    </div>
                    <div class="col-md-6">
                        <?php $this->load->view('checkin/assinatura_canvas', [
                            'id' => 'assinatura-cliente-saida',
                            'titulo' => $nao_programada ? 'Assinatura do cliente (opcional)' : 'Assinatura do Cliente',
                            'mostrar_campos' => true,
                            'campos' => ['nome' => true, 'documento' => true],
                        ]); ?>
                    </div>
                </div>
            </div>
            <div class="checkin-section secao-foto-saida">
                <h6><i class="bx bx-camera"></i> Fotos de depois</h6>
                <div class="upload-area">
                    <input type="file" id="fotos-saida-input" class="checkin-foto-input form-control" data-etapa="saida" accept="image/*" style="display:none;">
                    <button type="button" class="btn btn-info btn-capturar-foto" data-etapa="saida"><i class="bx bx-camera"></i> Tirar Foto</button>
                    <button type="button" class="btn btn-outline-secondary" data-acao="clicar" data-alvo="#fotos-saida-input"><i class="bx bx-upload"></i> Escolher da Galeria</button>
                    <p class="text-muted" style="margin:10px 0 0;">Máximo 5MB por foto (JPG, PNG)</p>
                </div>
                <div id="preview-fotos-saida" class="preview-fotos-container"></div>
            </div>
            <div class="checkin-section secao-foto-saida">
                <h6><i class="bx bx-images"></i> Fotos do atendimento</h6>
                <div id="lista-fotos-durante"><p class="text-muted">Carregando fotos...</p></div>
            </div>
            <div class="checkin-section">
                <h6><i class="bx bx-note"></i> Observações de Saída</h6>
                <textarea id="checkout-observacao" class="w-100 form-control" rows="3" placeholder="Serviço realizado, peças trocadas, recomendações, etc."></textarea>
            </div>
            <!-- Localização capturada em segundo plano (não exibida no modal) -->
            <div class="checkin-section" style="display:none;">
                <input type="hidden" id="checkout-latitude">
                <input type="hidden" id="checkout-longitude">
                <span id="checkout-geo-status"></span>
            </div>
            <div id="formularios-durante" class="checkin-section"></div>
            <div id="formularios-finalizar" class="checkin-section"></div>
        </form>
    </div>
    <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" aria-hidden="true">Cancelar</button>
        <button class="btn btn-danger ui-acao-grande" id="btn-confirmar-checkout"><i class="bx bx-log-out"></i> Concluir atendimento</button>
    </div>
</div></div></div>

<!-- Modal: Checklist do serviço (passo 3 do assistente) -->
<div id="modal-checklist" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
        <h3 class="modal-title"><i class="bx bx-list-check"></i> Checklist do serviço</h3>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
    <div class="modal-body">
        <div id="formularios-checklist" class="checkin-section"></div>
        <div id="checklist-erro" class="tec-aviso-msg tec-aviso-error" role="alert" style="display:none;"></div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" aria-hidden="true">Fechar</button>
        <button class="btn btn-success ui-acao-grande" id="btn-salvar-checklist"><i class="bx bx-check"></i> Salvar checklist</button>
    </div>
</div></div></div>

<!-- Modal: Enviar Foto do Serviço (etapa "durante") -->
<div id="modal-foto-servico" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
        <h3 class="modal-title"><i class="bx bx-camera"></i> Foto do serviço</h3>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
    <div class="modal-body">
        <div class="checkin-section">
            <div class="upload-area">
                <input class="form-control" type="file" id="fs-input" accept="image/*" multiple style="display:none;">
                <button type="button" class="btn btn-info" data-acao="clicar" data-alvo="#fs-input"><i class="bx bx-camera"></i> Tirar ou escolher fotos</button>
                <p class="text-muted" style="margin:10px 0 0;">Pode escolher várias. As fotos são reduzidas automaticamente antes do envio.</p>
            </div>
            <div id="fs-lista" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(96px,1fr)); gap:8px; margin-top:10px;"></div>
            <div id="fs-aviso" role="alert" style="display:none; color:var(--tec-danger,#c0392b); font-weight:600; margin-top:8px;"></div>
        </div>
        <div class="checkin-section">
            <h6><i class="bx bx-note"></i> Descrição (opcional)</h6>
            <textarea id="fs-descricao" class="w-100 form-control" rows="2" placeholder="Ex.: peça trocada, etapa concluída, etc."></textarea>
        </div>
        <div id="fs-enviadas-wrap" class="checkin-section" style="display:none;">
            <h6><i class="bx bx-check-circle"></i> Enviadas nesta sessão</h6>
            <div id="fs-enviadas"></div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" aria-hidden="true">Fechar</button>
        <button class="btn btn-success" id="btn-fs-enviar" disabled><i class="bx bx-upload"></i> Enviar fotos</button>
    </div>
</div></div></div>

<!-- Bibliotecas do fluxo de atendimento (mesmas da tela administrativa) -->
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/assinatura-canvas.js?v=4') ?>"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/assinatura-fullscreen.js?v=2') ?>"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/foto-compacta.js') ?>?v=<?= asset_versao('assets/js/foto-compacta.js', '2') ?>"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/checkin-fotos.js?v=4') ?>"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/rascunho-form.js') ?>?v=<?= asset_versao('assets/js/rascunho-form.js') ?>"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/checkin.js?v=8') ?>"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/checkin-formularios.js?v=2') ?>"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/csrf.js') ?>?v=<?= asset_versao('assets/js/csrf.js', '4') ?>"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/tecnico-localizacao.js?v=1') ?>"></script>
<script nonce="<?= csp_nonce() ?>">
    window.checkinConfig = { baseUrl: '<?= base_url() ?>', osId: <?= $os_id ?>, debug: false };

    (function () {
        'use strict';
        function isMobile() { return window.innerWidth <= 768 || 'ontouchstart' in window; }

        function criarAssinatura(id, canvasId) {
            if (typeof AssinaturaManager === 'undefined') { return; }
            if (AssinaturaManager.obter(id)) { AssinaturaManager.obter(id)._ajustarTamanhoCanvas(); return; }
            var canvas = document.getElementById(canvasId);
            if (!canvas) { return setTimeout(function () { criarAssinatura(id, canvasId); }, 120); }
            var largura = isMobile() ? Math.max(canvas.parentElement.clientWidth - 20, 280) : 400;
            var altura = isMobile() ? 220 : 150;
            try { AssinaturaManager.criar(id, canvasId, { cor: '#000', espessura: isMobile() ? 3 : 2, largura: largura, altura: altura }); }
            catch (e) { console.error('Erro assinatura ' + id, e); }
        }

        // Formulários de atendimento personalizados
        var FA = window.CheckinFormularios;
        function faReady() { return typeof FA !== 'undefined' && FA; }

        $(document).on('shown.bs.modal', '#modal-checkin', function () {
            setTimeout(function () { criarAssinatura('assinatura-tecnico-entrada', 'assinatura-tecnico-entrada-canvas'); }, isMobile() ? 300 : 100);
            if (faReady()) { FA.carregar('iniciar', '#formularios-iniciar', window.checkinConfig.osId); }
        });

        // Salva as respostas dos formulários ao confirmar cada etapa (independente do check-in).
        // Falha ao salvar NAO fica so no console: avisa o tecnico e deixa tentar de novo.
        function avisar(mensagem, tipo, acao) {
            if (window.CheckinManager) { CheckinManager.aviso(mensagem, tipo, acao ? { acao: acao } : {}); }
        }
        function salvarForm(etapa, container) {
            if (!faReady()) { return; }
            var checkinId = (window.CheckinManager && CheckinManager.config.checkinId) || '';
            FA.salvar(etapa, container, window.checkinConfig.osId, checkinId)
                .fail(function (msg) {
                    avisar((msg || 'Não foi possível salvar o formulário.') + ' Seus dados continuam na tela.', 'error',
                        { texto: 'Tentar de novo', fn: function () { salvarForm(etapa, container); } });
                });
        }

        $(document).on('click', '#btn-confirmar-checkin', function () {
            salvarForm('iniciar', '#formularios-iniciar');
        });

        $(document).on('click', '#btn-confirmar-checkout', function () {
            // Com o assistente, o checklist ("durante") ja foi salvo no passo 3
            if (!document.getElementById('tec-assistente')) { salvarForm('durante', '#formularios-durante'); }
            salvarForm('finalizar', '#formularios-finalizar');
        });

        // Passo 3 do assistente: checklist
        $(document).on('click', '#btn-salvar-checklist', function () {
            if (!faReady()) { return; }
            var $b = $(this).prop('disabled', true);
            var $erro = $('#checklist-erro').hide().text('');
            var checkinId = (window.CheckinManager && CheckinManager.config.checkinId) || '';
            FA.salvar('durante', '#formularios-checklist', window.checkinConfig.osId, checkinId)
                .done(function () {
                    $b.prop('disabled', false);
                    $('#modal-checklist').modal('hide');
                    $(document).trigger('tec:checklistSalvo');
                    avisar('Checklist salvo.', 'success');
                })
                .fail(function (msg) {
                    $b.prop('disabled', false).html('<i class="bx bx-refresh"></i> Tentar de novo');
                    $erro.text((msg || 'Não foi possível salvar.') + ' Verifique a internet e toque em "Tentar de novo".').show();
                });
        });

        $(document).on('shown.bs.modal', '#modal-checkout', function () {
            if (faReady()) {
                if (!document.getElementById('tec-assistente')) { FA.carregar('durante', '#formularios-durante', window.checkinConfig.osId); }
                FA.carregar('finalizar', '#formularios-finalizar', window.checkinConfig.osId);
            }
            var nome = <?= json_encode($nome_cliente) ?>;
            var doc = <?= json_encode($documento_cliente) ?>;
            if (nome && $('#assinatura-cliente-saida-nome').length) {
                $('#assinatura-cliente-saida-nome').val(nome).prop('readonly', true);
            }
            if (doc && $('#assinatura-cliente-saida-documento').length) {
                $('#assinatura-cliente-saida-documento').val(doc).prop('readonly', true);
            }
            setTimeout(function () {
                criarAssinatura('assinatura-tecnico-saida', 'assinatura-tecnico-saida-canvas');
                criarAssinatura('assinatura-cliente-saida', 'assinatura-cliente-saida-canvas');
            }, isMobile() ? 300 : 100);
        });

        // ---- Foto do serviço (etapa "durante"): varias fotos, envio uma a uma com progresso e nova tentativa ----
        var fsItens = [];      // { dataUrl, estado: 'pronta'|'enviando'|'ok'|'erro', msg, pct, el }
        var fsEnviadas = 0;
        var fsOcupado = false; // impede duplo envio
        var FS_MAX = 20;

        function fsAviso(msg) { $('#fs-aviso').text(msg || '').toggle(!!msg); }

        function fsAtualizarBotao() {
            var pendentes = fsItens.filter(function (i) { return i.estado === 'pronta' || i.estado === 'erro'; }).length;
            $('#btn-fs-enviar').prop('disabled', fsOcupado || pendentes === 0)
                .html('<i class="bx bx-upload"></i> ' + (fsOcupado ? 'Aguarde...' : (pendentes > 1 ? 'Enviar ' + pendentes + ' fotos' : 'Enviar foto')));
        }

        function fsDesenhar(item) {
            var el = item.el;
            if (!el) {
                el = item.el = $('<div class="fs-item" style="position:relative; border:1px solid var(--tec-line,#e0e3ee); border-radius:8px; overflow:hidden; background:var(--tec-surface-2,#fafafa);"></div>');
                $('#fs-lista').append(el);
            }
            var cor = item.estado === 'ok' ? 'var(--tec-success,#27ae60)' : (item.estado === 'erro' ? 'var(--tec-danger,#c0392b)' : 'var(--tec-info,#2d7ff9)');
            var rotulo = item.estado === 'ok' ? (item.msg || 'Enviada') : (item.estado === 'erro' ? (item.msg || 'Falhou') : (item.estado === 'enviando' ? Math.round(item.pct || 0) + '%' : 'Pronta'));
            el.empty()
                .append($('<img alt="Foto" style="width:100%; height:80px; object-fit:cover; display:block;">').attr('src', item.dataUrl))
                .append($('<div style="height:5px; background:var(--tec-line,#eee);"></div>').append($('<div></div>').css({ height: '5px', width: (item.estado === 'ok' ? 100 : (item.pct || 0)) + '%', background: cor })))
                .append($('<div style="font-size:11px; padding:3px 4px; text-align:center;"></div>').css('color', cor).text(rotulo));
            if (item.estado === 'pronta' || item.estado === 'erro') {
                el.append($('<button type="button" aria-label="Remover foto" style="position:absolute; top:2px; right:2px; width:26px; height:26px; border:0; border-radius:50%; background:var(--tec-danger,#c62828); color:var(--tec-on-primary,#fff); font-size:16px; line-height:24px; padding:0;">&times;</button>')
                    .on('click', function () {
                        if (fsOcupado) { return; }
                        fsItens.splice(fsItens.indexOf(item), 1);
                        el.remove();
                        fsAtualizarBotao();
                    }));
            }
        }

        function fsReset() {
            fsItens = [];
            fsOcupado = false;
            $('#fs-input').val('');
            $('#fs-descricao').val('');
            $('#fs-lista').empty();
            fsAviso('');
            fsAtualizarBotao();
        }

        $(document).on('click', '#btn-foto-servico', function () {
            fsReset();
            fsEnviadas = 0;
            $('#fs-enviadas').empty();
            $('#fs-enviadas-wrap').hide();
            $('#modal-foto-servico').modal('show');
        });

        $(document).on('change', '#fs-input', function () {
            var arquivos = Array.prototype.slice.call(this.files || []);
            var input = this;
            if (!arquivos.length || typeof CheckinFotos === 'undefined') { return; }
            fsAviso('');
            fsOcupado = true; fsAtualizarBotao();
            // Reduz uma de cada vez para nao estourar a memoria do celular
            arquivos.reduce(function (cadeia, arq) {
                return cadeia.then(function () {
                    return new Promise(function (ok) {
                        if (fsItens.length >= FS_MAX) { fsAviso('Limite de ' + FS_MAX + ' fotos por envio; as demais foram ignoradas.'); return ok(); }
                        var v = CheckinFotos.validarArquivo(arq);
                        if (!v.valido) { fsAviso(arq.name + ': ' + v.erro); return ok(); }
                        CheckinFotos.processarImagem(arq, function (dataUrl) {
                            if (!dataUrl) { fsAviso('Não consegui preparar uma das fotos. Tire de novo.'); return ok(); }
                            var item = { dataUrl: dataUrl, estado: 'pronta', pct: 0 };
                            fsItens.push(item); fsDesenhar(item); ok();
                        });
                    });
                });
            }, Promise.resolve()).then(function () {
                input.value = '';
                fsOcupado = false; fsAtualizarBotao();
            });
        });

        function fsEnviarItem(item, descricao) {
            return new Promise(function (ok) {
                item.estado = 'enviando'; item.pct = 0; fsDesenhar(item);
                CheckinFotos.uploadFoto(
                    { os_id: window.checkinConfig.osId, foto: item.dataUrl, descricao: descricao },
                    {
                        progress: function (f) { item.pct = f * 100; fsDesenhar(item); },
                        success: function (resp) {
                            if (resp && resp.success) {
                                item.estado = 'ok'; item.pct = 100; fsEnviadas++;
                                if (resp.offline) { item.msg = 'Guardada'; fsAviso('Sem internet: fotos guardadas no aparelho; serão enviadas quando a conexão voltar.'); }
                                $('#fs-enviadas-wrap').show();
                                if (resp.url) { $('#fs-enviadas').append($('<img alt="Foto enviada" style="width:70px; height:70px; object-fit:cover; border-radius:6px; border:1px solid var(--tec-line,#e0e3ee); margin:4px;">').attr('src', resp.url)); }
                            } else {
                                item.estado = 'erro'; item.msg = (resp && resp.message) || 'Falhou';
                            }
                            fsDesenhar(item); ok();
                        },
                        error: function (xhr) {
                            item.estado = 'erro';
                            item.msg = (xhr && (xhr.status === 403 || xhr.status === 401)) ? 'Sessão expirou' : 'Sem conexão';
                            fsDesenhar(item); ok();
                        }
                    }
                );
            });
        }

        $(document).on('click', '#btn-fs-enviar', function () {
            if (fsOcupado || typeof CheckinFotos === 'undefined') { return; }
            var fila = fsItens.filter(function (i) { return i.estado === 'pronta' || i.estado === 'erro'; });
            if (!fila.length) { return; }
            fsOcupado = true; fsAviso(''); fsAtualizarBotao();
            var descricao = $('#fs-descricao').val();
            fila.reduce(function (cadeia, item) {
                return cadeia.then(function () { return fsEnviarItem(item, descricao); });
            }, Promise.resolve()).then(function () {
                fsOcupado = false; fsAtualizarBotao();
                var falhas = fsItens.filter(function (i) { return i.estado === 'erro'; }).length;
                if (falhas) {
                    fsAviso(falhas + ' foto(s) não foram enviadas. Verifique a internet e toque em Enviar para tentar de novo.');
                } else if (typeof swal !== 'undefined' && !$('#fs-aviso').text()) {
                    swal('Enviadas!', 'Fotos do serviço enviadas.', 'success');
                }
            });
        });

        // Ao fechar o modal, recarrega a página se algo foi enviado (para refletir na galeria).
        $(document).on('hidden.bs.modal', '#modal-foto-servico', function () {
            if (fsEnviadas > 0) { window.location.reload(); }
        });

        $(document).ready(function () {
            if (typeof CheckinFotos !== 'undefined') { CheckinFotos.init({ baseUrl: window.checkinConfig.baseUrl }); }
            if (faReady()) { FA.init({ baseUrl: window.checkinConfig.baseUrl, osId: window.checkinConfig.osId }); }
            if (typeof CheckinManager !== 'undefined' && !CheckinManager._inicializado) {
                CheckinManager._inicializado = true;
                CheckinManager.init(window.checkinConfig);
            }
        });
    })();
</script>
