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

<!-- Template de um campo (usado pelo JS) -->
<template id="tpl-campo">
    <div class="campo-item" style="border:1px solid #e2e5ef;border-radius:6px;padding:12px;margin-bottom:10px;background:#fafbff">
        <div class="row-fluid">
            <div class="span5">
                <label>Rótulo do campo</label>
                <input type="text" data-name="label" class="span12" maxlength="200" placeholder="Ex.: Nível do óleo">
            </div>
            <div class="span4">
                <label>Tipo</label>
                <select data-name="tipo" class="span12 campo-tipo">
                    <?php foreach ($tipos as $chave => $rotulo) { ?>
                        <option value="<?= $chave ?>"><?= html_escape($rotulo) ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="span3">
                <label>&nbsp;</label><br>
                <label class="checkbox inline">
                    <input type="checkbox" data-name="obrigatorio" value="1"> Obrigatório
                </label>
                <button type="button" class="btn btn-danger btn-mini btn-remover-campo" style="margin-left:6px" title="Remover">
                    <i class="bx bx-trash"></i>
                </button>
            </div>
        </div>
        <div class="row-fluid campo-opcoes-wrap" style="margin-top:6px;display:none">
            <div class="span12">
                <label>Opções (uma por linha)</label>
                <textarea data-name="opcoes" class="span12" rows="3" placeholder="Opção 1&#10;Opção 2&#10;Opção 3"></textarea>
            </div>
        </div>
        <div class="row-fluid" style="margin-top:6px">
            <div class="span6">
                <label>Texto de exemplo (placeholder)</label>
                <input type="text" data-name="placeholder" class="span12" maxlength="200">
            </div>
            <div class="span6">
                <label>Texto de ajuda</label>
                <input type="text" data-name="ajuda" class="span12" maxlength="255">
            </div>
        </div>
    </div>
</template>

<script>
    (function () {
        'use strict';
        var comOpcoes = <?= json_encode(array_values($comOpcoes)) ?>;
        var idx = 0;
        var $lista = $('#lista-campos');

        function novoCampo(dados) {
            dados = dados || {};
            var frag = document.getElementById('tpl-campo').content.cloneNode(true);
            var $item = $(frag).find('.campo-item');

            // Renomeia inputs com índice único: campos[idx][name]
            $item.find('[data-name]').each(function () {
                var name = $(this).data('name');
                this.name = 'campos[' + idx + '][' + name + ']';
            });
            idx++;

            // Preenche valores existentes
            if (dados.label != null) { $item.find('[data-name=label]').val(dados.label); }
            if (dados.placeholder != null) { $item.find('[data-name=placeholder]').val(dados.placeholder); }
            if (dados.ajuda != null) { $item.find('[data-name=ajuda]').val(dados.ajuda); }
            if (dados.opcoes != null) { $item.find('[data-name=opcoes]').val(dados.opcoes); }
            if (dados.tipo) { $item.find('[data-name=tipo]').val(dados.tipo); }
            if (dados.obrigatorio) { $item.find('[data-name=obrigatorio]').prop('checked', true); }

            atualizarOpcoes($item);
            $lista.append($item);
        }

        function atualizarOpcoes($item) {
            var tipo = $item.find('.campo-tipo').val();
            $item.find('.campo-opcoes-wrap').toggle(comOpcoes.indexOf(tipo) !== -1);
        }

        $lista.on('change', '.campo-tipo', function () {
            atualizarOpcoes($(this).closest('.campo-item'));
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
