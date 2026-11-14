<link rel="stylesheet" href="<?php echo base_url() ?>assets/trumbowyg/ui/trumbowyg.css">
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/trumbowyg.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/langs/pt_br.js"></script>

<style>
    .ui-datepicker {
        z-index: 9999 !important;
    }

    .trumbowyg-box {
        margin-top: 0;
        margin-bottom: 0;
    }
</style>

<div class="row" style="margin-top:0">
    <div class="col-md-12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon">
                    <i class="fas fa-diagnoses"></i>
                </span>
                <h5>Detalhes OS</h5>
            </div>
            <div class="widget-content nopadding tab-content"><div class="row">


                <div class="col-md-12" id="divProdutosServicos" style=" margin-left: 0">
                    <ul class="nav nav-tabs">
                        <li class="nav-item" id="tabDetalhes"><a class="nav-link active" href="#tab1" data-bs-toggle="tab">Detalhes da OS</a></li>
                        <li class="nav-item" id="tabProdutos"><a class="nav-link" href="#tab2" data-bs-toggle="tab">Produtos</a></li>
                        <li class="nav-item" id="tabServicos"><a class="nav-link" href="#tab3" data-bs-toggle="tab">Serviços</a></li>
                        <li class="nav-item" id="tabAnexos"><a class="nav-link" href="#tab4" data-bs-toggle="tab">Anexos</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab1"><div class="row">

                            <div class="col-md-12 row" id="divCadastrarOs">


                                <div class="col-md-12 row" style="padding: 1%; margin-left: 0">


                                    <div class="col-md-6" style="margin-left: 0">
                                        <h3>#Protocolo:
                                            <?php echo $result->idOs ?>
                                        </h3>
                                        <input id="valorTotal" type="hidden" name="valorTotal" value="" />
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tecnico">Técnico / Responsável</label>
                                        <input disabled="disabled" id="tecnico" class="w-100 form-control" type="text" name="tecnico" value="<?php echo html_escape($result->nome); ?>" />

                                    </div>
                                </div>
                                <div class="col-md-12 row" style="padding: 1%; margin-left: 0">
                                    <div class="col-md-3">
                                        <label for="status">Status<span class="required"></span></label>
                                        <input class="form-control" disabled="disabled" type="text" name="status" id="status" value="<?php echo $result->status; ?>">

                                    </div>
                                    <div class="col-md-3">
                                        <label for="dataInicial">Data Inicial<span class="required">*</span></label>
                                        <input id="dataInicial" disabled="disabled" class="datepicker w-100 form-control" type="text" name="dataInicial" value="<?php echo date('d/m/Y', strtotime($result->dataInicial)); ?>" />
                                    </div>
                                    <div class="col-md-3">
                                        <label for="dataFinal">Data Final</label>
                                        <input id="dataFinal" disabled="disabled" class="datepicker w-100 form-control" type="text" name="dataFinal" value="<?php echo date('d/m/Y', strtotime($result->dataFinal)); ?>" />
                                    </div>

                                    <div class="col-md-3">
                                        <label for="garantia">Garantia</label>
                                        <input id="garantia" disabled="disabled" type="text" class="w-100 form-control" name="garantia" value="<?php echo $result->garantia ?>" />
                                    </div>
                                </div>


                                <div class="col-md-12" style="padding: 1%; margin-left: 0">
                                    <label for="descricaoProduto">Descrição Produto/Serviço</label>
                                    <textarea class="editor w-100 form-control" name="descricaoProduto" id="descricaoProduto" cols="30" rows="5" disabled><?php echo html_escape($result->descricaoProduto); ?></textarea>
                                </div>

                                <div class="col-md-12" style="padding: 1%; margin-left: 0">
                                    <label for="defeito">Defeito</label>
                                    <textarea class="editor w-100 form-control" name="defeito" id="defeito" cols="30" rows="5" disabled><?php echo html_escape($result->defeito); ?></textarea>
                                </div>

                                <div class="col-md-12" style="padding: 1%; margin-left: 0">
                                    <label for="observacoes">Observações</label>
                                    <textarea class="editor w-100 form-control" name="observacoes" id="observacoes" cols="30" rows="5" disabled><?php echo html_escape($result->observacoes); ?></textarea>
                                </div>

                                <div class="col-md-12" style="padding: 1%; margin-left: 0">
                                    <label for="laudoTecnico">Laudo Técnico</label>
                                    <textarea class="editor w-100 form-control" name="laudoTecnico" id="laudoTecnico" cols="30" rows="5" disabled><?php echo html_escape($result->laudoTecnico); ?></textarea>
                                </div>

                            </div>

                        </div></div>


                        <!--Produtos-->
                        <div class="tab-pane" id="tab2"><div class="row">

                            <div class="col-md-12" id="divProdutos" style="margin-left: 0">
                                <table class="table table-bordered" id="tblProdutos">
                                    <thead>
                                        <tr>
                                            <th>Produto</th>
                                            <th>Preço unit.</th>
                                            <th>Quantidade</th>
                                            <th>Sub-total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $total = 0;
foreach ($produtos as $p) {
    $total = $total + $p->subTotal;
    echo '<tr>';
    echo '<td>' . $p->descricao . '</td>';
    echo '<td>R$ ' . number_format($p->preco, 2, ',', '.') . '</td>';
    echo '<td>' . $p->quantidade . '</td>';
    echo '<td>R$ ' . number_format($p->subTotal, 2, ',', '.') . '</td>';
    echo '</tr>';
} ?>

                                        <tr>
                                            <td colspan="3" style="text-align: right"><strong>Total:</strong></td>
                                            <td><strong>R$
                                                    <?php echo number_format($total, 2, ',', '.'); ?><input type="hidden" id="total-venda" value="<?php echo number_format($total, 2); ?>"></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div></div>

                        <!--Serviços-->
                        <div class="tab-pane" id="tab3"><div class="row">
                            <div class="col-md-12 row" style="padding: 1%; margin-left: 0">

                                <div class="col-md-12" id="divServicos" style="margin-left: 0">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Serviço</th>
                                                <th>Preço unit.</th>
                                                <th>Quantidade</th>
                                                <th>Sub-total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
    $total = 0;
foreach ($servicos as $s) {
    $total = $total + $s->subTotal;
    echo '<tr>';
    echo '<td>' . $s->nome . '</td>';
    echo '<td>R$ ' . number_format($s->preco, 2, ',', '.') . '</td>';
    echo '<td>' . $s->quantidade . '</td>';
    echo '<td>R$ ' . number_format($s->subTotal, 2, ',', '.') . '</td>';
    echo '</tr>';
} ?>

                                            <tr>
                                                <td colspan="3" style="text-align: right"><strong>Total:</strong></td>
                                                <td><strong>R$
                                                        <?php echo number_format($total, 2, ',', '.'); ?><input type="hidden" id="total-servico" value="<?php echo number_format($total, 2); ?>"></strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div></div>


                        <!--Anexos-->
                        <div class="tab-pane" id="tab4"><div class="row">
                            <div class="col-md-12 row" style="padding: 1%; margin-left: 0">

                                <?php if ($this->session->userdata('cliente_anexa')) { ?>
                                    <div class="col-md-12 card card-body" style="padding: 1%; margin-left: 0" id="form-anexos">
                                        <form class="row" id="formAnexos" enctype="multipart/form-data" action="#" data-bloquear-envio accept-charset="utf-8" s method="post">
                                            <div class="col-md-10">

                                                <input type="hidden" name="idOsServico" id="idOsServico" value="<?php echo $result->idOs ?>" />
                                                <label for="">Anexo</label>
                                                <input type="file" class="w-100 form-control" name="userfile[]" multiple="multiple" size="20" />
                                            </div>
                                            <div class="col-md-2 row">
                                                <label for="">.</label>
                                                <button class="btn btn-success col-md-12"><i class="fas fa-paperclip"></i> Anexar</button>
                                            </div>
                                        </form>
                                    </div>
                                <?php
                                } ?>

                                <div class="col-md-12" id="divAnexos" style="margin-left: 0">
                                    <?php foreach ($anexos as $a) {
                                        if ($a->thumb == null) {
                                            $thumb = base_url() . 'assets/img/icon-file.png';
                                            $link = base_url() . 'assets/img/icon-file.png';
                                        } else {
                                            $thumb = $a->url . '/thumbs/' . $a->thumb;
                                            $link = $a->url . '/' . $a->anexo;
                                        }
                                        echo '<div class="col-md-3" style="min-height: 150px; margin-left: 0">
                                            <a style="min-height: 150px;" href="#modal-anexo" imagem="' . $a->idAnexos . '" link="' . $link . '" role="button" class="btn anexo col-md-12" data-bs-toggle="modal">
                                            <img src="' . $thumb . '" alt="">
                                            </a>
                                            <span>' . $a->anexo . '</span>
                                            </div>';
                                    }?>
                                </div>

                            </div>
                        </div></div>



                    </div>

                </div>


                .

            </div></div>

        </div>
    </div>
</div>





<!-- Modal visualizar anexo -->
<div id="modal-anexo" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
        
        <h3 id="myModalLabel">Visualizar Anexo</h3>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true" aria-label="Fechar"></button></div>
    <div class="modal-body"><div class="row">
        <div class="col-md-12" id="div-visualizar-anexo" style="text-align: center">
            <div class='progress'>
                <div class='progress-bar bg-info progress-bar-striped progress-bar-animated' style='width: 100%'></div>
            </div>
        </div>
    </div></div>
    <div class="modal-footer">
        <button class="btn" data-bs-dismiss="modal" aria-hidden="true">Fechar</button>
        <a href="" id-imagem="" class="btn btn-dark" id="download">Download</a>
    </div>
</div></div></div>





<!-- Modal Faturar-->
<div id="modal-faturar" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <form id="formFaturar" action="<?php echo current_url() ?>" method="post">
        <div class="modal-header">
            
            <h3 id="myModalLabel">Faturar Venda</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true" aria-label="Fechar"></button></div>
        <div class="modal-body"><div class="row">

            <div class="col-md-12 alert alert-info" style="margin-left: 0"> Obrigatório o preenchimento dos campos com asterisco.</div>
            <div class="col-md-12" style="margin-left: 0">
                <label for="descricao">Descrição*</label>
                <input class="w-100 form-control" id="descricao" type="text" name="descricao" value="Fatura de Venda - #<?php echo $result->idOs; ?> " />

            </div>
            <div class="col-md-12 row" style="margin-left: 0">
                <div class="col-md-12" style="margin-left: 0">
                    <label for="cliente">Cliente*</label>
                    <input class="w-100 form-control" id="cliente" type="text" name="cliente" value="<?php echo html_escape($result->nomeCliente); ?>" />
                    <input type="hidden" name="clientes_id" id="clientes_id" value="<?php echo $result->clientes_id ?>">
                    <input type="hidden" name="os_id" id="os_id" value="<?php echo $result->idOs; ?>">
                </div>


            </div>
            <div class="col-md-12 row" style="margin-left: 0">
                <div class="col-md-4" style="margin-left: 0">
                    <label for="valor">Valor*</label>
                    <input type="hidden" id="tipo" name="tipo" value="receita" />
                    <input class="money w-100 form-control" id="valor" type="text" name="valor" value="<?php echo number_format($total, 2, '.', ''); ?> " />
                </div>
                <div class="col-md-4">
                    <label for="vencimento">Data Vencimento*</label>
                    <input class="datepicker w-100 form-control" id="vencimento" type="text" name="vencimento" />
                </div>

            </div>

            <div class="col-md-12 row" style="margin-left: 0">
                <div class="col-md-4" style="margin-left: 0">
                    <label for="recebido">Recebido?</label>
                    &nbsp &nbsp &nbsp &nbsp <input class="form-check-input" id="recebido" type="checkbox" name="recebido" value="1" />
                </div>
                <div id="divRecebimento" class="col-md-8 row" style=" display: none">
                    <div class="col-md-6">
                        <label for="recebimento">Data Recebimento</label>
                        <input class="datepicker w-100 form-control" id="recebimento" type="text" name="recebimento" />
                    </div>
                    <div class="col-md-6">
                        <label for="formaPgto">Forma Pgto</label>
                        <select name="formaPgto" id="formaPgto" class="w-100 form-select">
                            <option value="Dinheiro">Dinheiro</option>
                            <option value="Cartão de Crédito">Cartão de Crédito</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Boleto">Boleto</option>
                            <option value="Depósito">Depósito</option>
                            <option value="Débito">Débito</option>
                            <option value="Pix">Pix</option>
                        </select>
                    </div>

                </div>


            </div>
        </div></div>
        <div class="modal-footer">
            <button class="btn" data-bs-dismiss="modal" aria-hidden="true" id="btn-cancelar-faturar">Cancelar</button>
            <button class="btn btn-primary">Faturar</button>
        </div>
    </form>
</div></div></div>

<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        $('.editor').trumbowyg({
            lang: 'pt_br',
            semantic: { 'strikethrough': 's', }
        });
    });

    $(document).on('click', '.anexo', function(event) {
        event.preventDefault();
        var link = $(this).attr('link');
        var id = $(this).attr('imagem');
        $("#div-visualizar-anexo").html('<img src="' + link + '" alt="">');
        $("#download").attr('href', "<?php echo base_url(); ?>index.php/mine/downloadanexo/" + id);
    });
</script>
