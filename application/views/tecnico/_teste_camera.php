<?php
// Checklist de teste de imagem da camera (Obra_pontos_model::TESTE_ITENS). Incluir dentro de um <form>.
defined('BASEPATH') or exit('No direct script access allowed');
?>
<fieldset class="ponto-teste" style="border:1px solid #ccc;border-radius:8px;padding:8px 10px;margin-top:8px;">
    <legend style="font-size:13px;font-weight:700;padding:0 6px;">Teste de imagem (obrigatório)</legend>
    <?php foreach (Obra_pontos_model::TESTE_ITENS as $k => $rotuloTeste): ?>
        <label style="display:flex;align-items:center;gap:8px;min-height:40px;">
            <input type="checkbox" name="teste_itens[<?= html_escape($k) ?>]" value="1" style="width:22px;height:22px;"<?= in_array($k, Obra_pontos_model::TESTE_OBRIGATORIOS, true) ? ' required' : '' ?>>
            <?= html_escape($rotuloTeste) ?>
        </label>
    <?php endforeach; ?>
    <label class="ponto-campo">Retenção da gravação (dias)
        <input type="number" name="retencao_dias" min="1" max="<?= (int) Obra_pontos_model::TESTE_RETENCAO_MAX ?>" step="1" inputmode="numeric" required class="ponto-input">
    </label>
</fieldset>
