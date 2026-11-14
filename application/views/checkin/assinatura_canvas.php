<?php
/**
 * View: Componente de Assinatura Digital
 * Uso: $this->load->view('checkin/assinatura_canvas', [
 *     'id' => 'assinatura-tecnico',
 *     'titulo' => 'Assinatura do Técnico',
 *     'mostrar_campos' => false,
 *     'campos' => ['nome' => true, 'documento' => false]
 * ]);
 */

// Define valores padrão
$id = isset($id) ? $id : 'assinatura-canvas';
$titulo = isset($titulo) ? $titulo : 'Assinatura Digital';
$mostrar_campos = isset($mostrar_campos) ? $mostrar_campos : true;
$campos = isset($campos) ? $campos : ['nome' => true, 'documento' => true];
$canvas_id = $id . '-canvas';
$btn_limpar_id = $id . '-limpar';
$container_preview_id = $id . '-preview';
?>

<div class="assinatura-container" id="<?php echo $id; ?>">
    <h5><?php echo $titulo; ?></h5>

    <!-- Canvas -->
    <div class="assinatura-canvas-wrapper" style="position: relative; border: 2px solid var(--tec-input-line,#8a90a8); border-radius: 4px; margin-bottom: 10px; background: #fff; touch-action: none;">
        <canvas id="<?php echo $canvas_id; ?>" width="400" height="150" style="width: 100%; height: auto; cursor: crosshair; touch-action: none; -webkit-touch-callout: none; user-select: none;"></canvas>
        <!-- Tarja de toque (visivel apenas no mobile via CSS) -->
        <div class="assinatura-tap-hint" data-titulo="<?php echo htmlspecialchars($titulo, ENT_QUOTES); ?>">
            <i class="bx bx-edit"></i> Toque para assinar em tela cheia
        </div>
    </div>

    <!-- Botões -->
    <style nonce="<?= csp_nonce() ?>">
        @media (max-width: 768px) { .assinatura-botoes .btn { min-height: 44px; font-size: 15px; padding: 8px 14px; margin-bottom: 6px; } }
    </style>
    <div class="assinatura-botoes" style="margin-bottom: 15px;">
        <button type="button" class="btn btn-sm btn-primary btn-assinatura-ampliar" data-target="<?php echo $id; ?>" data-titulo="<?php echo htmlspecialchars($titulo, ENT_QUOTES); ?>">
            <i class="bx bx-fullscreen"></i> Assinar em tela cheia
        </button>
        <button type="button" class="btn btn-sm" id="<?php echo $id; ?>-desfazer">
            <i class="bx bx-undo"></i> Desfazer
        </button>
        <button type="button" class="btn btn-sm" id="<?php echo $btn_limpar_id; ?>">
            <i class="bx bx-eraser"></i> Limpar
        </button>
        <button type="button" class="btn btn-sm btn-info btn-assinatura-preview" data-target="<?php echo $id; ?>">
            <i class="bx bx-show"></i> Pré-visualizar
        </button>
    </div>

    <!-- Preview da assinatura -->
    <div id="<?php echo $container_preview_id; ?>" style="display: none; margin-bottom: 15px;">
        <label>Preview:</label>
        <img src="" alt="Assinatura" style="background:#fff; border: 1px solid var(--tec-input-line,#8a90a8); max-width: 100%; height: auto;">
    </div>

    <!-- Campos adicionais -->
    <?php if ($mostrar_campos): ?>
    <div class="assinatura-campos row">
        <?php if (isset($campos['nome']) && $campos['nome']): ?>
        <div class="col-md-6">
            <label for="<?php echo $id; ?>-nome">Nome:</label>
            <input type="text" class="assinatura-nome w-100 form-control" id="<?php echo $id; ?>-nome" placeholder="Digite o nome">
        </div>
        <?php endif; ?>

        <?php if (isset($campos['documento']) && $campos['documento']): ?>
        <div class="col-md-6">
            <label for="<?php echo $id; ?>-documento">Documento:</label>
            <input type="text" class="assinatura-documento w-100 form-control" id="<?php echo $id; ?>-documento" placeholder="CPF/RG">
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script nonce="<?= csp_nonce() ?>">
(function() {
    // Inicializa o canvas de assinatura
    var canvasId = '<?php echo $canvas_id; ?>';
    var containerId = '<?php echo $id; ?>';

    // Aguarda o DOM estar pronto e dispara evento de inicialização
    $(document).ready(function() {
        // Pequeno delay para garantir que o elemento canvas esteja renderizado
        setTimeout(function() {
            $(document).trigger('assinatura-canvas-pronto', [containerId, canvasId]);
        }, 10);
    });

    // Botão limpar - usa event delegation para garantir que funcione
    $(document).on('click', '#<?php echo $btn_limpar_id; ?>', function(e) {
        e.preventDefault();
        if (typeof AssinaturaManager === 'undefined') {
            alert('Sistema de assinaturas não carregado. Recarregue a página.');
            return;
        }
        var assinatura = AssinaturaManager.obter(containerId);
        if (assinatura) {
            assinatura.limpar();
            $('#<?php echo $container_preview_id; ?>').hide().find('img').attr('src', '');
            // Reseta o estado visual da tarja de "assinar em tela cheia"
            $('#' + containerId).removeClass('assinado')
                .find('.assinatura-tap-hint')
                .html('<i class="bx bx-edit"></i> Toque para assinar em tela cheia');
        }
    });

    // Botão desfazer (remove o último traço)
    $(document).on('click', '#<?php echo $id; ?>-desfazer', function(e) {
        e.preventDefault();
        var assinatura = (typeof AssinaturaManager !== 'undefined') ? AssinaturaManager.obter(containerId) : null;
        if (assinatura && assinatura.desfazer) {
            assinatura.desfazer();
            $('#<?php echo $container_preview_id; ?>').hide().find('img').attr('src', '');
        }
    });

    // Botão preview - usa event delegation
    $(document).on('click', '.btn-assinatura-preview[data-target="<?php echo $id; ?>"]', function(e) {
        e.preventDefault();
        if (typeof AssinaturaManager === 'undefined') {
            alert('Sistema de assinaturas não carregado. Recarregue a página.');
            return;
        }
        var assinatura = AssinaturaManager.obter(containerId);
        if (assinatura) {
            var valid = assinatura.validar();
            if (!valid.ok) {
                alert(valid.motivo || 'Nenhuma assinatura foi feita ainda.');
                return;
            }
            var imagem = assinatura.obterImagem();
            $('#<?php echo $container_preview_id; ?>').show().find('img').attr('src', imagem);
        } else {
            alert('Assinatura não inicializada. Feche e reabra o modal.');
        }
    });
})();
</script>
