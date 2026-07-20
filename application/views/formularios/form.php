<?php
$isEdit = ! empty($formulario);
$comOpcoes = $tiposComOpcoes ?? ['select', 'radio', 'checkbox'];
?>
<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="fas fa-clipboard-list"></i></span>
        <h5><?= $isEdit ? 'Editar formulário' : 'Novo formulário' ?></h5>
    </div>
    <div class="widget-content" style="padding:18px">

        <form action="<?= site_url('formularios/salvar') ?>" method="post" id="form-formulario">
            <?php if ($isEdit) { ?>
                <input type="hidden" name="idFormulario" value="<?= (int) $formulario->idFormulario ?>">
            <?php } ?>

            <div class="row-fluid">
                <div class="span8">
                    <label><strong>Nome do formulário</strong></label>
                    <input type="text" name="nome" class="span12" maxlength="150" required
                           placeholder="Ex.: Vistoria de chegada"
                           value="<?= $isEdit ? html_escape($formulario->nome) : '' ?>">
                </div>
                <div class="span4">
                    <label><strong>Etapa do atendimento</strong></label>
                    <select name="etapa" class="span12">
                        <?php foreach ($etapas as $chave => $rotulo) { ?>
                            <option value="<?= $chave ?>" <?= ($isEdit && $formulario->etapa === $chave) ? 'selected' : '' ?>>
                                <?= html_escape($rotulo) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="row-fluid" style="margin-top:10px">
                <div class="span8">
                    <label>Descrição (opcional)</label>
                    <input type="text" name="descricao" class="span12" maxlength="255"
                           placeholder="Uma breve explicação exibida ao técnico"
                           value="<?= $isEdit ? html_escape($formulario->descricao) : '' ?>">
                </div>
                <div class="span2">
                    <label>Ordem</label>
                    <input type="number" name="ordem" class="span12" value="<?= $isEdit ? (int) $formulario->ordem : 0 ?>">
                </div>
                <div class="span2">
                    <label>&nbsp;</label>
                    <label class="checkbox">
                        <input type="checkbox" name="ativo" value="1" <?= (! $isEdit || (int) $formulario->ativo === 1) ? 'checked' : '' ?>> Ativo
                    </label>
                    <label class="checkbox">
                        <input type="checkbox" name="obrigatorio" value="1" <?= ($isEdit && (int) $formulario->obrigatorio === 1) ? 'checked' : '' ?>> Obrigatório
                    </label>
                </div>
            </div>

            <hr>
            <h5 style="margin-bottom:4px"><i class="bx bx-list-ul"></i> Campos do formulário</h5>
            <p class="text-muted" style="margin-top:0">
                Adicione os campos que o técnico vai preencher. Para <em>Seleção suspensa</em>,
                <em>Escolha única</em> e <em>Múltipla escolha</em>, informe uma opção por linha.
            </p>

            <div id="lista-campos"></div>

            <button type="button" class="btn" id="btn-add-campo" style="margin-top:8px">
                <i class="bx bx-plus"></i> Adicionar campo
            </button>

            <hr>
            <button type="submit" class="btn btn-success">
                <i class="bx bx-save"></i> Salvar formulário
            </button>
            <a href="<?= site_url('formularios') ?>" class="btn">Voltar</a>
        </form>
    </div>
</div>

<script>
    (function () {
        'use strict';
        var comOpcoes = <?= json_encode(array_values($comOpcoes)) ?>;
        var tiposMap = <?= json_encode($tipos, JSON_UNESCAPED_UNICODE) ?>;
        var idx = 0;
        var $lista = $('#lista-campos');

        function escAttr(v) {
            return String(v == null ? '' : v)
                .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                .replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        function escHtml(v) {
            return String(v == null ? '' : v)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function opcoesTipo(sel) {
            var html = '';
            Object.keys(tiposMap).forEach(function (k) {
                html += '<option value="' + k + '"' + (k === sel ? ' selected' : '') + '>' + escHtml(tiposMap[k]) + '</option>';
            });
            return html;
        }

        // Monta o HTML de um campo por concatenação de string (sem <template>,
        // compatível com o jQuery 1.12 do tema).
        function novoCampo(dados) {
            dados = dados || {};
            var i = idx++;
            var tipo = dados.tipo || 'texto';
            var p = 'campos[' + i + ']';
            var mostraOpcoes = comOpcoes.indexOf(tipo) !== -1;

            var html =
                '<div class="campo-item" style="border:1px solid #e2e5ef;border-radius:6px;padding:12px;margin-bottom:10px;background:#fafbff">'
                + '<div class="row-fluid">'
                +   '<div class="span5"><label>Rótulo do campo</label>'
                +     '<input type="text" name="' + p + '[label]" class="span12" maxlength="200" placeholder="Ex.: Nível do óleo" value="' + escAttr(dados.label) + '"></div>'
                +   '<div class="span4"><label>Tipo</label>'
                +     '<select name="' + p + '[tipo]" class="span12 campo-tipo">' + opcoesTipo(tipo) + '</select></div>'
                +   '<div class="span3"><label>&nbsp;</label><br>'
                +     '<label class="checkbox inline"><input type="checkbox" name="' + p + '[obrigatorio]" value="1"' + (dados.obrigatorio ? ' checked' : '') + '> Obrigatório</label>'
                +     '<button type="button" class="btn btn-danger btn-mini btn-remover-campo" style="margin-left:6px" title="Remover"><i class="bx bx-trash"></i></button>'
                +   '</div>'
                + '</div>'
                + '<div class="row-fluid campo-opcoes-wrap" style="margin-top:6px;' + (mostraOpcoes ? '' : 'display:none') + '"><div class="span12">'
                +   '<label>Opções (uma por linha)</label>'
                +   '<textarea name="' + p + '[opcoes]" class="span12" rows="3" placeholder="Opção 1&#10;Opção 2&#10;Opção 3">' + escHtml(dados.opcoes) + '</textarea>'
                + '</div></div>'
                + '<div class="row-fluid" style="margin-top:6px">'
                +   '<div class="span6"><label>Texto de exemplo (placeholder)</label>'
                +     '<input type="text" name="' + p + '[placeholder]" class="span12" maxlength="200" value="' + escAttr(dados.placeholder) + '"></div>'
                +   '<div class="span6"><label>Texto de ajuda</label>'
                +     '<input type="text" name="' + p + '[ajuda]" class="span12" maxlength="255" value="' + escAttr(dados.ajuda) + '"></div>'
                + '</div>'
                + '</div>';

            $lista.append(html);
        }

        $lista.on('change', '.campo-tipo', function () {
            var $item = $(this).closest('.campo-item');
            var mostra = comOpcoes.indexOf($(this).val()) !== -1;
            $item.find('.campo-opcoes-wrap').toggle(mostra);
        });

        $lista.on('click', '.btn-remover-campo', function () {
            $(this).closest('.campo-item').remove();
        });

        $('#btn-add-campo').on('click', function () { novoCampo(); });

        // Campos já salvos (modo edição)
        var existentes = <?= json_encode(array_map(function ($c) {
            $opcoes = $c->opcoes ? json_decode($c->opcoes, true) : [];
            return [
                'label' => $c->label,
                'tipo' => $c->tipo,
                'opcoes' => is_array($opcoes) ? implode("\n", $opcoes) : '',
                'placeholder' => $c->placeholder,
                'ajuda' => $c->ajuda,
                'obrigatorio' => (int) $c->obrigatorio,
            ];
        }, $campos), JSON_UNESCAPED_UNICODE) ?>;

        if (existentes.length) {
            existentes.forEach(novoCampo);
        } else {
            novoCampo();
        }

        $('#form-formulario').on('submit', function (e) {
            if ($lista.find('.campo-item').length === 0) {
                alert('Adicione ao menos um campo ao formulário.');
                e.preventDefault();
            }
        });
    })();
</script>
