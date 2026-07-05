<?php
/**
 * View: Modal de Assinatura Digital
 * Uso: $this->load->view('checkin/assinatura_modal', [
 *     'modal_id' => 'modal-assinatura-tecnico',
 *     'titulo' => 'Assinatura do Técnico',
 *     'btn_abrir_id' => 'btn-abrir-assinatura-tecnico',
 *     'preview_id' => 'preview-assinatura-tecnico',
 *     'input_destino' => 'input-assinatura-tecnico-salva',
 *     'mostrar_campos' => false,
 *     'campos' => ['nome' => false, 'documento' => false]
 * ]);
 */

// Define valores padrão
$modal_id = isset($modal_id) ? $modal_id : 'modal-assinatura';
$titulo = isset($titulo) ? $titulo : 'Assinatura Digital';
$btn_abrir_id = isset($btn_abrir_id) ? $btn_abrir_id : 'btn-abrir-assinatura';
$preview_id = isset($preview_id) ? $preview_id : 'preview-assinatura';
$input_destino = isset($input_destino) ? $input_destino : 'input-assinatura-salva';
$mostrar_campos = isset($mostrar_campos) ? $mostrar_campos : true;
$campos = isset($campos) ? $campos : ['nome' => true, 'documento' => true];

$canvas_id = $modal_id . '-canvas';
?>

<!-- Botão para abrir modal -->
<div class="assinatura-botao-container" id="<?php echo $btn_abrir_id; ?>-container">
    <button type="button" class="btn btn-primary btn-abrir-assinatura" id="<?php echo $btn_abrir_id; ?>" data-modal="<?php echo $modal_id; ?>">
        <i class="bx bx-pencil"></i> <?php echo $titulo; ?>
    </button>

    <!-- Preview da assinatura (aparece depois de assinar) -->
    <div class="assinatura-preview-salva" id="<?php echo $preview_id; ?>" style="display: none; margin-top: 10px;">
        <div style="border: 1px solid #ddd; padding: 5px; background: #fff; max-width: 300px;">
            <img src="" alt="Assinatura Salva" style="max-width: 100%; height: auto; display: block;">
            <div style="margin-top: 5px; text-align: center;">
                <button type="button" class="btn btn-small btn-warning btn-editar-assinatura" data-modal="<?php echo $modal_id; ?>">
                    <i class="bx bx-edit"></i> Editar
                </button>
                <button type="button" class="btn btn-small btn-danger btn-limpar-assinatura" data-preview="<?php echo $preview_id; ?>">
                    <i class="bx bx-trash"></i> Limpar
                </button>
            </div>
        </div>
    </div>

    <!-- Input hidden para armazenar a assinatura em base64 -->
    <input type="hidden" id="<?php echo $input_destino; ?>" name="<?php echo $input_destino; ?>">

    <!-- Inputs hidden para campos adicionais -->
    <?php if ($mostrar_campos): ?>
        <?php if (isset($campos['nome']) && $campos['nome']): ?>
        <input type="hidden" id="<?php echo $modal_id; ?>-nome-hidden" class="assinatura-nome-hidden">
        <?php endif; ?>
        <?php if (isset($campos['documento']) && $campos['documento']): ?>
        <input type="hidden" id="<?php echo $modal_id; ?>-documento-hidden" class="assinatura-documento-hidden">
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal de Assinatura -->
<div class="modal hide fade modal-assinatura" id="<?php echo $modal_id; ?>" tabindex="-1" role="dialog" aria-labelledby="<?php echo $modal_id; ?>Label" aria-hidden="true" style="width: 600px; margin-left: -300px;">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h5 id="<?php echo $modal_id; ?>Label"><i class="bx bx-pencil"></i> <?php echo $titulo; ?></h5>
    </div>
    <div class="modal-body" style="text-align: center;">
        <!-- Instrução -->
        <p class="text-info" style="margin-bottom: 15px;">
            <i class="bx bx-info-circle"></i> Desenhe sua assinatura na área abaixo usando o mouse ou dedo (touch).
        </p>

        <!-- Canvas -->
        <div class="assinatura-canvas-modal-wrapper" style="border: 2px solid #333; border-radius: 4px; margin: 0 auto 15px; background: #fff; display: inline-block; touch-action: none;">
            <canvas id="<?php echo $canvas_id; ?>" width="500" height="200" style="cursor: crosshair; touch-action: none; display: block;"></canvas>
        </div>

        <!-- Botões do canvas -->
        <div class="assinatura-botoes-modal" style="margin-bottom: 15px;">
            <button type="button" class="btn btn-small btn-limpar-canvas" data-canvas="<?php echo $canvas_id; ?>">
                <i class="bx bx-eraser"></i> Limpar Canvas
            </button>
        </div>

        <!-- Campos adicionais -->
        <?php if ($mostrar_campos): ?>
        <div class="assinatura-campos-modal" style="text-align: left; margin-top: 15px;">
            <?php if (isset($campos['nome']) && $campos['nome']): ?>
            <div style="margin-bottom: 10px;">
                <label for="<?php echo $modal_id; ?>-nome">Nome do Assinante:</label>
                <input type="text" class="span12 modal-assinatura-nome" id="<?php echo $modal_id; ?>-nome" placeholder="Digite o nome completo" style="margin-bottom: 0;">
            </div>
            <?php endif; ?>

            <?php if (isset($campos['documento']) && $campos['documento']): ?>
            <div>
                <label for="<?php echo $modal_id; ?>-documento">Documento (CPF/RG):</label>
                <input type="text" class="span12 modal-assinatura-documento" id="<?php echo $modal_id; ?>-documento" placeholder="Digite o CPF ou RG" style="margin-bottom: 0;">
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success btn-salvar-assinatura-modal"
                data-modal="<?php echo $modal_id; ?>"
                data-canvas="<?php echo $canvas_id; ?>"
                data-preview="<?php echo $preview_id; ?>"
                data-input="<?php echo $input_destino; ?>">
            <i class="bx bx-check"></i> Salvar Assinatura
        </button>
    </div>
</div>

<script>
(function() {
    // Inicializa o canvas quando o modal for aberto
    var modalId = '<?php echo $modal_id; ?>';
    var canvasId = '<?php echo $canvas_id; ?>';

    // Quando o modal for mostrado, inicializa o canvas
    $('#' + modalId).on('shown', function() {
        // Se a assinatura já existe no input hidden, carrega no canvas
        var inputDestino = '<?php echo $input_destino; ?>';
        var assinaturaSalva = $('#' + inputDestino).val();

        // Carrega nome e documento salvos
        var nomeHidden = $('#' + modalId + '-nome-hidden').val();
        var documentoHidden = $('#' + modalId + '-documento-hidden').val();

        if (nomeHidden) {
            $('#' + modalId + ' .modal-assinatura-nome').val(nomeHidden);
        }
        if (documentoHidden) {
            $('#' + modalId + ' .modal-assinatura-documento').val(documentoHidden);
        }

        // Inicializa ou reinicializa o canvas
        if (typeof AssinaturaManager !== 'undefined') {
            var assinatura = AssinaturaManager.obter(modalId);
            if (!assinatura) {
                AssinaturaManager.criar(modalId, canvasId);
                assinatura = AssinaturaManager.obter(modalId);
            }

            // Limpa o canvas primeiro
            assinatura.limpar();

            // Se já tem assinatura salva, carrega no canvas
            if (assinaturaSalva && assinaturaSalva.indexOf('data:image') === 0) {
                assinatura.carregarImagem(assinaturaSalva);
            }
        }
    });

    // Limpar canvas
    $(document).on('click', '.btn-limpar-canvas[data-canvas="<?php echo $canvas_id; ?>"]', function(e) {
        e.preventDefault();
        if (typeof AssinaturaManager !== 'undefined') {
            var assinatura = AssinaturaManager.obter(modalId);
            if (assinatura) {
                assinatura.limpar();
            }
        }
    });
})();
</script>
