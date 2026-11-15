<div class="row" style="margin-top:0">
    <div class="col-md-12">
        <div class="widget-box">
            <div class="widget-content">
                <div class="ui-cabecalho">
                    <div>
                        <h1 class="ui-titulo"><i class="fas fa-shopping-bag"></i> Editar produto</h1>
                        <p class="ui-subtitulo">Altere os dados, o preço ou o estoque deste item. Campos com * são obrigatórios.</p>
                    </div>
                </div>

                <?php if (! empty($custom_error)) { ?>
                    <div class="alert alert-danger"><?php echo $custom_error; ?></div>
                <?php } ?>

                <form action="<?php echo current_url(); ?>" id="formProduto" method="post">
                    <?php echo form_hidden('idProdutos', $result->idProdutos) ?>

                    <div class="ui-secao">
                        <h4 class="ui-secao-titulo">Identificação</h4>
                        <div class="ui-grade">
                            <div class="mb-3">
                                <label for="codDeBarra" class="form-label">Código de Barras</label>
                                <div class="">
                                    <input class="form-control" id="codDeBarra" type="text" name="codDeBarra" value="<?php echo html_escape($result->codDeBarra); ?>" />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição<span class="required">*</span></label>
                                <div class="">
                                    <input class="form-control" id="descricao" type="text" name="descricao" value="<?php echo html_escape($result->descricao); ?>" />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="ncm" class="form-label">NCM <small>(fiscal)</small></label>
                                <div class="">
                                    <input class="form-control" id="ncm" type="text" name="ncm" maxlength="8" value="<?php echo $result->ncm ?? ''; ?>" placeholder="8 dígitos - obrigatório p/ NF-e" />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="cest" class="form-label">CEST <small>(fiscal)</small></label>
                                <div class="">
                                    <input class="form-control" id="cest" type="text" name="cest" maxlength="7" value="<?php echo $result->cest ?? ''; ?>" placeholder="Opcional - só p/ produtos com ST" />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="cfop" class="form-label">CFOP <small>(fiscal)</small></label>
                                <div class="">
                                    <input class="form-control" id="cfop" type="text" name="cfop" maxlength="4" value="<?php echo $result->cfop ?? ''; ?>" placeholder="Deixe em branco p/ usar o padrão da config" />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tipo de movimento</label>
                                <div class="">
                                    <div class="ui-opcoes">
                                        <label for="entrada" class="ui-opcao">
                                            <input class="form-check-input" type="checkbox" id="entrada" name="entrada" value="1" <?= ($result->entrada == 1) ? 'checked' : '' ?>>
                                            Entrada
                                        </label>
                                        <label for="saida" class="ui-opcao">
                                            <input class="form-check-input" type="checkbox" id="saida" name="saida" value="1" <?= ($result->saida == 1) ? 'checked' : '' ?>>
                                            Saída
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php $this->load->view('tributacao/_classificacao_item', ['tipo' => 'produto', 'item' => $result]); ?>

                    <div class="ui-secao">
                        <h4 class="ui-secao-titulo">Preço</h4>
                        <div class="ui-grade">
                            <div class="mb-3">
                                <label for="precoCompra" class="form-label">Preço de Compra<span class="required">*</span></label>
                                <div class="">
                                    <input id="precoCompra" class="money form-control" data-affixes-stay="true" data-thousands="" data-decimal="." type="text" name="precoCompra" value="<?php echo $result->precoCompra; ?>" />
                                    <div class="ui-combo">
                                        <span>Margem</span>
                                        <input class="form-control" id="margemLucro" name="margemLucro" type="text" placeholder="%" maxlength="3" inputmode="decimal" aria-label="Margem de lucro (%)" />
                                    </div>
                                    <span class="form-text ui-negativo" id="errorAlert"></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="Lucro" class="form-label">Lucro</label>
                                <div class="">
                                    <div class="ui-combo">
                                        <select class="form-select" id="selectLucro" name="selectLucro">
                                            <option value="markup">Markup</option>
                                            <option value="margemLucro">Margem de Lucro</option>
                                        </select>
                                        <input class="form-control" id="Lucro" name="Lucro" type="text" placeholder="%" maxlength="3" inputmode="decimal" />
                                    </div>
                                    <span class="form-text">Markup é aplicado ao preço de compra; margem de lucro, ao preço de venda.</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="precoVenda" class="form-label">Preço de Venda<span class="required">*</span></label>
                                <div class="">
                                    <input id="precoVenda" class="money form-control" data-affixes-stay="true" data-thousands="" data-decimal="." type="text" name="precoVenda" value="<?php echo $result->precoVenda; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ui-secao">
                        <h4 class="ui-secao-titulo">Estoque</h4>
                        <div class="ui-grade">
                            <div class="mb-3">
                                <label for="unidade" class="form-label">Unidade<span class="required">*</span></label>
                                <div class="">
                                    <select class="form-select" id="unidade" name="unidade"></select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="estoque" class="form-label">Estoque<span class="required">*</span></label>
                                <div class="">
                                    <input class="form-control" id="estoque" type="text" name="estoque" value="<?php echo $result->estoque; ?>" />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="estoqueMinimo" class="form-label">Estoque Mínimo</label>
                                <div class="">
                                    <input class="form-control" id="estoqueMinimo" type="text" name="estoqueMinimo" value="<?php echo $result->estoqueMinimo; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ui-rodape-form">
                        <button type="submit" class="button btn btn-sm btn-primary">
                            <span class="button__icon"><i class="bx bx-sync"></i></span><span class="button__text2">Atualizar</span></button>
                        <a href="<?php echo base_url() ?>index.php/produtos" class="button btn btn-sm btn-warning">
                            <span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>" src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?php echo base_url(); ?>assets/js/maskmoney.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    function calcLucro(precoCompra, Lucro) {
        var lucroTipo = $('#selectLucro').val();
        var precoVenda;

        if (lucroTipo === 'markup') {
            precoVenda = (precoCompra * (1 + Lucro / 100)).toFixed(2);
        } else if (lucroTipo === 'margemLucro') {
            precoVenda = (precoCompra / (1 - (Lucro / 100))).toFixed(2);
        }

        return precoVenda;
    }

    function atualizarPrecoVenda() {
        var precoCompra = Number($("#precoCompra").val());
        var lucro = Number($("#Lucro").val());

        if (precoCompra > 0 && lucro >= 0) {
            $('#precoVenda').val(calcLucro(precoCompra, lucro));
        }
    }

    $("#precoCompra, #Lucro, #selectLucro").on('input change', atualizarPrecoVenda);

    $("#precoCompra, #Lucro").on('input change', function() {
        if ($("#precoCompra").val() == '0.00' && $('#precoVenda').val() != '') {
            $('#errorAlert').text('Você não pode preencher valor de compra e depois apagar.').css("display", "inline").fadeOut(6000);
            $('#precoVenda').val('');
            $("#precoCompra").focus();
        } else if ($("#precoCompra").val() != '' && $("#Lucro").val() != '') {
            atualizarPrecoVenda();
        }
    });

    $("#Lucro").keyup(function() {
        this.value = this.value.replace(/[^0-9.]/g, '');
        if ($("#precoCompra").val() == null || $("#precoCompra").val() == '') {
            $('#errorAlert').text('Preencher valor da compra primeiro.').css("display", "inline").fadeOut(5000);
            $('#Lucro').val('');
            $('#precoVenda').val('');
            $("#precoCompra").focus();

        } else if (Number($("#Lucro").val()) >= 0) {
            $('#precoVenda').val(calcLucro(Number($("#precoCompra").val()), Number($("#Lucro").val())));
        } else {
            $('#errorAlert').text('Não é permitido número negativo.').css("display", "inline").fadeOut(5000);
            $('#Lucro').val('');
            $('#precoVenda').val('');
        }
    });

    $('#precoVenda').focusout(function () {
        if (Number($('#precoVenda').val()) < Number($("#precoCompra").val())) {
            $('#errorAlert').text('Preço de venda não pode ser menor que o preço de compra.').css("display", "inline").fadeOut(6000);
            $('#precoVenda').val('');
            if($("#margemLucro").val() != "" || $("#margemLucro").val() != null){
                $('#precoVenda').val(calcLucro(Number($("#precoCompra").val()), Number($("#margemLucro").val())));
            }
        }
    });

    $(document).ready(function() {
        $(".money").maskMoney();
        $.getJSON('<?php echo base_url() ?>assets/json/tabela_medidas.json', function(data) {
            for (i in data.medidas) {
                $('#unidade').append(new Option(data.medidas[i].descricao, data.medidas[i].sigla));
                $("#unidade option[value=" + '<?php echo html_escape($result->unidade); ?>' + "]").prop("selected", true);
            }
        });
        $('#formProduto').validate({
            rules: {
                descricao: {
                    required: true
                },
                unidade: {
                    required: true
                },
                precoCompra: {
                    required: true
                },
                precoVenda: {
                    required: true
                },
                estoque: {
                    required: true
                }
            },
            messages: {
                descricao: {
                    required: 'Campo Requerido.'
                },
                unidade: {
                    required: 'Campo Requerido.'
                },
                precoCompra: {
                    required: 'Campo Requerido.'
                },
                precoVenda: {
                    required: 'Campo Requerido.'
                },
                estoque: {
                    required: 'Campo Requerido.'
                }
            },

            errorClass: "invalid-feedback",
            errorElement: "span",
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
                $(element).addClass('is-valid');
            }
        });
    });
</script>
