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
    <div class="assinatura-canvas-wrapper" style="border: 2px solid #ddd; border-radius: 4px; margin-bottom: 10px; background: #fff; touch-action: none;">
        <canvas id="<?php echo $canvas_id; ?>" width="400" height="150" style="width: 100%; height: auto; cursor: crosshair; touch-action: none; -webkit-touch-callout: none; user-select: none;"></canvas>
    </div>

    <!-- Botões -->
    <div class="assinatura-botoes" style="margin-bottom: 15px;">
        <button type="button" class="btn btn-small" id="<?php echo $btn_limpar_id; ?>">
            <i class="bx bx-eraser"></i> Limpar
        </button>
        <button type="button" class="btn btn-small btn-info btn-assinatura-preview" data-target="<?php echo $id; ?>">
            <i class="bx bx-show"></i> Pré-visualizar
        </button>
    </div>

    <!-- Preview da assinatura -->
    <div id="<?php echo $container_preview_id; ?>" style="display: none; margin-bottom: 15px;">
        <label>Preview:</label>
        <img src="" alt="Assinatura" style="border: 1px solid #ddd; max-width: 100%; height: auto;">
    </div>

    <!-- Campos adicionais -->
    <?php if ($mostrar_campos): ?>
    <div class="assinatura-campos row-fluid">
        <?php if (isset($campos['nome']) && $campos['nome']): ?>
        <div class="span6">
            <label for="<?php echo $id; ?>-nome">Nome:</label>
            <input type="text" class="span12 assinatura-nome" id="<?php echo $id; ?>-nome" placeholder="Digite o nome">
        </div>
        <?php endif; ?>

        <?php if (isset($campos['documento']) && $campos['documento']): ?>
        <div class="span6">
            <label for="<?php echo $id; ?>-documento">Documento:</label>
            <input type="text" class="span12 assinatura-documento" id="<?php echo $id; ?>-documento" placeholder="CPF/RG">
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
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
            if (assinatura.estaVazio()) {
                alert('Nenhuma assinatura foi feita ainda.');
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
