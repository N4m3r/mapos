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
?>
<style>
    .hidden { display: none !important; }
    .checkin-section { margin-bottom: 18px; padding-bottom: 16px; border-bottom: 1px solid #eee; }
    .checkin-section:last-child { border-bottom: none; }
    .checkin-section h6 { color: #555; margin: 0 0 10px; font-weight: 700; }
    .upload-area { border: 2px dashed #ccc; border-radius: 8px; padding: 18px; text-align: center; margin-bottom: 10px; background: #fafafa; }
    .preview-fotos-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 8px; margin-top: 10px; }
    .assinatura-canvas-wrapper canvas { touch-action: none; -webkit-user-select: none; user-select: none; }
    #modal-checkin .modal-body, #modal-checkout .modal-body { max-height: 74vh; overflow-y: auto; }
    @media (max-width: 768px) {
        #modal-checkin, #modal-checkout { width: 94% !important; left: 3% !important; margin-left: 0 !important; top: 10px !important; }
        #modal-checkout .row-fluid .span6 { width: 100% !important; float: none !important; margin-left: 0 !important; margin-bottom: 14px; }
        .assinatura-canvas-wrapper canvas { width: 100% !important; height: auto !important; min-height: 200px !important; }
    }
</style>

<!-- Modal Check-in -->
<div id="modal-checkin" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3><i class="bx bx-log-in"></i> Iniciar Atendimento</h3>
    </div>
    <div class="modal-body">
        <form id="form-checkin">
            <div class="checkin-section">
                <h6><i class="bx bx-pen"></i> Assinatura do Técnico</h6>
                <?php $this->load->view('checkin/assinatura_canvas', [
                    'id' => 'assinatura-tecnico-entrada',
                    'titulo' => 'Assine no quadro abaixo',
                    'mostrar_campos' => false,
                ]); ?>
            </div>
            <div class="checkin-section">
                <h6><i class="bx bx-camera"></i> Fotos de Entrada</h6>
                <div class="upload-area">
                    <input type="file" id="fotos-entrada-input" class="checkin-foto-input" data-etapa="entrada" accept="image/*" style="display:none;">
                    <button type="button" class="btn btn-info btn-capturar-foto" data-etapa="entrada"><i class="bx bx-camera"></i> Tirar Foto</button>
                    <button type="button" class="btn" onclick="document.getElementById('fotos-entrada-input').click()"><i class="bx bx-upload"></i> Escolher da Galeria</button>
                    <p class="text-muted" style="margin:10px 0 0;">Máximo 5MB por foto (JPG, PNG)</p>
                </div>
                <div id="preview-fotos-entrada" class="preview-fotos-container"></div>
            </div>
            <div class="checkin-section">
                <h6><i class="bx bx-note"></i> Observações de Entrada</h6>
                <textarea id="checkin-observacao" class="span12" rows="3" placeholder="Estado inicial, equipamento recebido, etc."></textarea>
            </div>
            <div class="checkin-section">
                <h6><i class="bx bx-map"></i> Localização</h6>
                <input type="hidden" id="checkin-latitude">
                <input type="hidden" id="checkin-longitude">
                <button type="button" id="btn-geo-checkin" class="btn btn-small"><i class="bx bx-map"></i> Capturar Localização</button>
                <span id="checkin-geo-status" class="text-muted" style="margin-left:10px;"></span>
            </div>
            <div id="formularios-iniciar" class="checkin-section"></div>
        </form>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
        <button class="btn btn-success" id="btn-confirmar-checkin"><i class="bx bx-log-in"></i> Iniciar Atendimento</button>
    </div>
</div>

<!-- Modal Check-out -->
<div id="modal-checkout" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3><i class="bx bx-log-out"></i> Finalizar Atendimento</h3>
    </div>
    <div class="modal-body">
        <form id="form-checkout">
            <div class="checkin-section">
                <h6><i class="bx bx-pen"></i> Assinaturas</h6>
                <div class="row-fluid">
                    <div class="span6">
                        <?php $this->load->view('checkin/assinatura_canvas', [
                            'id' => 'assinatura-tecnico-saida',
                            'titulo' => 'Assinatura do Técnico',
                            'mostrar_campos' => false,
                        ]); ?>
                    </div>
                    <div class="span6">
                        <?php $this->load->view('checkin/assinatura_canvas', [
                            'id' => 'assinatura-cliente-saida',
                            'titulo' => 'Assinatura do Cliente',
                            'mostrar_campos' => true,
                            'campos' => ['nome' => true, 'documento' => true],
                        ]); ?>
                    </div>
                </div>
            </div>
            <div class="checkin-section">
                <h6><i class="bx bx-camera"></i> Fotos da Saída</h6>
                <div class="upload-area">
                    <input type="file" id="fotos-saida-input" class="checkin-foto-input" data-etapa="saida" accept="image/*" style="display:none;">
                    <button type="button" class="btn btn-info btn-capturar-foto" data-etapa="saida"><i class="bx bx-camera"></i> Tirar Foto</button>
                    <button type="button" class="btn" onclick="document.getElementById('fotos-saida-input').click()"><i class="bx bx-upload"></i> Escolher da Galeria</button>
                    <p class="text-muted" style="margin:10px 0 0;">Máximo 5MB por foto (JPG, PNG)</p>
                </div>
                <div id="preview-fotos-saida" class="preview-fotos-container"></div>
            </div>
            <div class="checkin-section">
                <h6><i class="bx bx-images"></i> Fotos do Atendimento</h6>
                <div id="lista-fotos-durante"><p class="text-muted">Carregando fotos...</p></div>
            </div>
            <div class="checkin-section">
                <h6><i class="bx bx-note"></i> Observações de Saída</h6>
                <textarea id="checkout-observacao" class="span12" rows="3" placeholder="Serviço realizado, peças trocadas, recomendações, etc."></textarea>
            </div>
            <div class="checkin-section">
                <h6><i class="bx bx-map"></i> Localização de Saída</h6>
                <input type="hidden" id="checkout-latitude">
                <input type="hidden" id="checkout-longitude">
                <button type="button" id="btn-geo-checkout" class="btn btn-small"><i class="bx bx-map"></i> Capturar Localização</button>
                <span id="checkout-geo-status" class="text-muted" style="margin-left:10px;"></span>
            </div>
            <div id="formularios-durante" class="checkin-section"></div>
            <div id="formularios-finalizar" class="checkin-section"></div>
        </form>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
        <button class="btn btn-danger" id="btn-confirmar-checkout"><i class="bx bx-log-out"></i> Finalizar Atendimento</button>
    </div>
</div>

<!-- Bibliotecas do fluxo de atendimento (mesmas da tela administrativa) -->
<script src="<?= base_url('assets/js/assinatura-canvas.js?v=3') ?>"></script>
<script src="<?= base_url('assets/js/checkin-fotos.js?v=3') ?>"></script>
<script src="<?= base_url('assets/js/checkin.js?v=4') ?>"></script>
<script src="<?= base_url('assets/js/checkin-formularios.js?v=1') ?>"></script>
<script src="<?= base_url('assets/js/csrf.js?v=3') ?>"></script>
<script src="<?= base_url('assets/js/tecnico-localizacao.js?v=1') ?>"></script>
<script>
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
        $(document).on('click', '#btn-confirmar-checkin', function () {
            if (!faReady()) { return; }
            FA.salvar('iniciar', '#formularios-iniciar', window.checkinConfig.osId, '')
                .fail(function (msg) { if (msg) { console.warn(msg); } });
        });

        $(document).on('click', '#btn-confirmar-checkout', function () {
            if (!faReady()) { return; }
            FA.salvar('durante', '#formularios-durante', window.checkinConfig.osId, '')
                .fail(function (msg) { if (msg) { console.warn(msg); } });
            FA.salvar('finalizar', '#formularios-finalizar', window.checkinConfig.osId, '')
                .fail(function (msg) { if (msg) { console.warn(msg); } });
        });

        $(document).on('shown.bs.modal', '#modal-checkout', function () {
            if (faReady()) {
                FA.carregar('durante', '#formularios-durante', window.checkinConfig.osId);
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
