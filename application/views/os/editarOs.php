<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>
<script src="<?php echo base_url() ?>assets/js/sweetalert2.all.min.js"></script>
<link rel="stylesheet" href="<?php echo base_url() ?>assets/trumbowyg/ui/trumbowyg.css">
<script type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/trumbowyg.js"></script>
<script type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/langs/pt_br.js"></script>

<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/custom.css" />
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/assinatura.css" />

<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title" style="margin: -20px 0 0">
                <span class="icon"><i class="fas fa-diagnoses"></i></span>
                <h5>Editar Ordem de Serviço</h5>
                <div class="buttons">
                    <?php if ($result->faturado == 0) { ?>
                        <a href="#modal-faturar" id="btn-faturar" role="button" data-toggle="modal" class="button btn btn-mini btn-danger">
                            <span class="button__icon"><i class='bx bx-dollar'></i></span> <span class="button__text">Faturar</span>
                        </a>
                    <?php } ?>
                    <a title="Visualizar OS" class="button btn btn-primary" href="<?php echo site_url() ?>/os/visualizar/<?php echo $result->idOs; ?>">
                        <span class="button__icon"><i class="bx bx-show"></i></span><span class="button__text">Visualizar OS</span>
                    </a>
                    <div class="button-container">
                        <a target="_blank" title="Imprimir Ordem de Serviço" class="button btn btn-mini btn-inverse">
                            <span class="button__icon"><i class="bx bx-printer"></i></span><span class="button__text">Imprimir</span>
                        </a>
                        <div class="cascading-buttons">
                            <a target="_blank" title="Impressão em Papel A4" class="button btn btn-mini btn-inverse" href="<?php echo site_url() ?>/os/imprimir/<?php echo $result->idOs; ?>">
                                <span class="button__icon"><i class='bx bx-file'></i></span> <span class="button__text">Papel A4</span>
                            </a>
                            <a target="_blank" title="Impressão Cupom Não Fical" class="button btn btn-mini btn-inverse" href="<?php echo site_url() ?>/os/imprimirTermica/<?php echo $result->idOs; ?>">
                                <span class="button__icon"><i class='bx bx-receipt'></i></span> <span class="button__text">Cupom 80mm</span>
                            </a>
                            <?php if ($result->garantias_id) { ?>
                                <a target="_blank" title="Imprimir Termo de Garantia" class="button btn btn-mini btn-inverse" href="<?php echo site_url() ?>/garantias/imprimirGarantiaOs/<?php echo $result->garantias_id; ?>">
                                    <span class="button__icon"><i class="bx bx-paperclip"></i></span> <span class="button__text">Termo Garantia</span>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                    <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) {
                        $this->load->model('os_model');
                        $zapnumber = preg_replace("/[^0-9]/", "", $result->celular_cliente);
                        $troca = [$result->nomeCliente, $result->idOs, $result->status, 'R$ ' . ($result->desconto != 0 && $result->valor_desconto != 0 ? number_format($result->valor_desconto, 2, ',', '.') : number_format($totalProdutos + $totalServico, 2, ',', '.')), strip_tags($result->descricaoProduto), ($emitente ? $emitente->nome : ''), ($emitente ? $emitente->telefone : ''), strip_tags($result->observacoes), strip_tags($result->defeito), strip_tags($result->laudoTecnico), date('d/m/Y', strtotime($result->dataFinal)), date('d/m/Y', strtotime($result->dataInicial)), $result->garantia . ' dias'];
                        $texto_de_notificacao = $this->os_model->criarTextoWhats($texto_de_notificacao, $troca);
                        if (!empty($zapnumber)) {
                            echo '<a title="Via WhatsApp" class="button btn btn-mini btn-success" id="enviarWhatsApp" target="_blank" href="https://wa.me/send?phone=55' . $zapnumber . '&text=' . $texto_de_notificacao . '" ' . ($zapnumber == '' ? 'disabled' : '') . '>
                                <span class="button__icon"><i class="bx bxl-whatsapp"></i></span> <span class="button__text">WhatsApp</span>
                            </a>';
                        }
                    } ?>
                    <a title="Enviar por E-mail" class="button btn btn-mini btn-warning" href="<?php echo site_url() ?>/os/enviar_email/<?php echo $result->idOs; ?>">
                        <span class="button__icon"><i class="bx bx-envelope"></i></span> <span class="button__text">Via E-mail</span>
                    </a>
                </div>
            </div>
            <div class="widget-content nopadding tab-content">
                <div class="span12" id="divProdutosServicos" style=" margin-left: 0">
                    <ul class="nav nav-tabs">
                        <li class="active" id="tabDetalhes"><a href="#tab1" data-toggle="tab">Detalhes da OS</a></li>
                        <li id="tabDesconto"><a href="#tab2" data-toggle="tab">Desconto</a></li>
                        <li id="tabProdutos"><a href="#tab3" data-toggle="tab">Produtos</a></li>
                        <li id="tabServicos"><a href="#tab4" data-toggle="tab">Serviços</a></li>
                        <li id="tabAnexos"><a href="#tab5" data-toggle="tab">Anexos</a></li>
                        <li id="tabAnotacoes"><a href="#tab6" data-toggle="tab">Anotações</a></li>
                        <li id="tabCheckin"><a href="#tab7" data-toggle="tab">Check-in</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab1">
                            <div class="span12" id="divCadastrarOs">
                                <form action="<?php echo current_url(); ?>" method="post" id="formOs">
                                    <?php echo form_hidden('idOs', $result->idOs) ?>
                                    <div class="span12" style="padding: 1%; margin-left: 0">
                                        <h3>N° OS: <?php echo $result->idOs; ?></h3>
                                        <div class="span6" style="margin-left: 0">
                                            <label for="cliente">Cliente<span class="required">*</span></label>
                                            <input id="cliente" class="span12" type="text" name="cliente" value="<?php echo $result->nomeCliente ?>" />
                                            <input id="clientes_id" class="span12" type="hidden" name="clientes_id" value="<?php echo $result->clientes_id ?>" />
                                            <input id="valor" type="hidden" name="valor" value="" />
                                        </div>
                                        <div class="span6">
                                            <label for="tecnico">Técnico / Responsável<span class="required">*</span></label>
                                            <input id="tecnico" class="span12" type="text" name="tecnico" value="<?php echo $result->nome ?>" />
                                            <input id="usuarios_id" class="span12" type="hidden" name="usuarios_id" value="<?php echo $result->usuarios_id ?>" />
                                        </div>
                                    </div>
                                    <div class="span12" style="padding: 1%; margin-left: 0">
                                        <div class="span3">
                                            <label for="status">Status<span class="required">*</span></label>
                                            <select class="span12" name="status" id="status" value="">
                                                <option <?php if ($result->status == 'Aberto') { echo 'selected'; } ?> value="Aberto">Aberto</option>
                                                <option <?php if ($result->status == 'Orçamento') { echo 'selected'; } ?> value="Orçamento">Orçamento</option>
                                                <option <?php if ($result->status == 'Negociação') { echo 'selected'; } ?> value="Negociação">Negociação</option>
                                                <option <?php if ($result->status == 'Aprovado') { echo 'selected'; } ?> value="Aprovado">Aprovado</option>
                                                <option <?php if ($result->status == 'Aguardando Peças') { echo 'selected'; } ?> value="Aguardando Peças">Aguardando Peças</option>
                                                <option <?php if ($result->status == 'Em Andamento') { echo 'selected'; } ?> value="Em Andamento">Em Andamento</option>
                                                <option <?php if ($result->status == 'Finalizado') { echo 'selected'; } ?> value="Finalizado">Finalizado</option>
                                                <option <?php if ($result->status == 'Faturado') { echo 'selected'; } ?> value="Faturado">Faturado</option>
                                                <option <?php if ($result->status == 'Cancelado') { echo 'selected'; } ?> value="Cancelado">Cancelado</option>                                                          
                                            </select>
                                        </div>
                                        <div class="span3">
                                            <label for="dataInicial">Data Inicial<span class="required">*</span></label>
                                            <input id="dataInicial" autocomplete="off" class="span12 datepicker" type="text" name="dataInicial" value="<?php echo date('d/m/Y', strtotime($result->dataInicial)); ?>" />
                                        </div>
                                        <div class="span3">
                                            <label for="dataFinal">Data Final<span class="required">*</span></label>
                                            <input id="dataFinal" autocomplete="off" class="span12 datepicker" type="text" name="dataFinal" value="<?php echo date('d/m/Y', strtotime($result->dataFinal)); ?>" />
                                        </div>
                                        <div class="span3">
                                            <label for="garantia">Garantia (dias)</label>
                                            <input id="garantia" type="number" placeholder="Status s/g inserir nº/0" min="0" max="9999" class="span12" name="garantia" value="<?php echo $result->garantia ?>" />
                                            <?php echo form_error('garantia'); ?>
                                            <label for="termoGarantia">Termo Garantia</label>
                                            <input id="termoGarantia" class="span12" type="text" name="termoGarantia" value="<?php echo $result->refGarantia ?>" />
                                            <input id="garantias_id" class="span12" type="hidden" name="garantias_id" value="<?php echo $result->garantias_id ?>" />
                                        </div>
                                    </div>
                                    <div class="span6" style="padding: 1%; margin-left: 0">
                                        <label for="descricaoProduto"><h4>Descrição Produto/Serviço</h4></label>
                                        <textarea class="span12 editor" name="descricaoProduto" id="descricaoProduto" cols="30" rows="5"><?php echo $result->descricaoProduto ?></textarea>
                                    </div>
                                    <div class="span6" style="padding: 1%; margin-left: 0">
                                        <label for="defeito"><h4>Defeito</h4></label>
                                        <textarea class="span12 editor" name="defeito" id="defeito" cols="30" rows="5"><?php echo $result->defeito ?></textarea>
                                    </div>
                                    <div class="span6" style="padding: 1%; margin-left: 0">
                                        <label for="observacoes"><h4>Observações</h4></label>
                                        <textarea class="span12 editor" name="observacoes" id="observacoes" cols="30" rows="5"><?php echo $result->observacoes ?></textarea>
                                    </div>
                                    <div class="span6" style="padding: 1%; margin-left: 0">
                                        <label for="laudoTecnico"><h4>Laudo Técnico</h4></label>
                                        <textarea class="span12 editor" name="laudoTecnico" id="laudoTecnico" cols="30" rows="5"><?php echo $result->laudoTecnico ?></textarea>
                                    </div>
                                    <div class="span12" style="padding: 0; margin-left: 0">
                                        <div class="span6 offset3" style="display:flex;justify-content: center">
                                            <button class="button btn btn-primary" id="btnContinuar"><span class="button__icon"><i class="bx bx-sync"></i></span><span class="button__text2">Atualizar</span></button>
                                            <a href="<?php echo base_url() ?>index.php/os" class="button btn btn-mini btn-warning"><span class="button__icon"><i class="bx bx-undo"></i></span> <span class="button__text2">Voltar</span></a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!--Desconto-->
                        <?php $total = 0; foreach ($produtos as $p) {$total = $total + $p->subTotal;}?>
                        <?php $totals = 0; foreach ($servicos as $s) { $preco = $s->preco ?: $s->precoVenda; $subtotals = $preco * ($s->quantidade ?: 1); $totals = $totals + $subtotals;}?>
                        <div class="tab-pane" id="tab2">
                            <div class="span12 well" style="padding: 1%; margin-left: 0">
                                <form id="formDesconto" action="<?php echo base_url(); ?>index.php/os/adicionarDesconto" method="POST">
                                    <div id="divValorTotal">
                                        <div class="span2">
                                            <label for="">Valor Total Da OS:</label>
                                            <input class="span12 money" id="valorTotal" name="valorTotal" type="text" data-affixes-stay="true" data-thousands="" data-decimal="." name="valor" value="<?php echo number_format($totals + $total, 2, '.', ''); ?>" readonly />
                                        </div>
                                    </div>
                                    <div class="span1">
                                        <label for="">Tipo Desc.</label>
                                        <select style="width: 4em;" name="tipoDesconto" id="tipoDesconto">
                                            <option value="real">R$</option>
                                            <option value="porcento" <?= $result->tipo_desconto == "porcento" ? "selected" : "" ?>>%</option>
                                        </select>
                                        <strong><span style="color: red" id="errorAlert"></span></strong>
                                    </div>
                                    <div class="span3">
                                        <input type="hidden" name="idOs" id="idOs"
                                            value="<?php echo $result->idOs; ?>" />
                                        <label for="">Desconto</label>
                                        <input style="width: 4em;" id="desconto" name="desconto" type="text"
                                            placeholder="" maxlength="6" size="2" value="<?= $result->desconto ?>" />
                                        <strong><span style="color: red" id="errorAlert"></span></strong>
                                    </div>
                                    <div class="span2">
                                        <label for="">Total com Desconto</label>
                                        <input class="span12 money" id="resultado" type="text" data-affixes-stay="true" data-thousands="" data-decimal="." name="resultado" value="<?php echo $result->valor_desconto ?>" readonly />
                                    </div>
                                    <div class="span2">
                                        <label for="">&nbsp;</label>
                                        <button class="button btn btn-success" id="btnAdicionarDesconto">
                                            <span class="button__icon"><i class='bx bx-plus-circle'></i></span> <span class="button__text2">Aplicar</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!--Produtos-->
                        <div class="tab-pane" id="tab3">
                            <div class="span12 well" style="padding: 1%; margin-left: 0">
                                <form id="formProdutos" action="<?php echo base_url() ?>index.php/os/adicionarProduto" method="post">
                                    <div class="span6">
                                        <input type="hidden" name="idProduto" id="idProduto" />
                                        <input type="hidden" name="idOsProduto" id="idOsProduto" value="<?php echo $result->idOs; ?>" />
                                        <input type="hidden" name="estoque" id="estoque" value="" />
                                        <label for="">Produto</label>
                                        <input type="text" class="span12" name="produto" id="produto" placeholder="Digite o nome do produto" />
                                    </div>
                                    <div class="span2">
                                        <label for="">Preço</label>
                                        <input type="text" placeholder="Preço" id="preco" name="preco" class="span12 money" data-affixes-stay="true" data-thousands="" data-decimal="." />
                                    </div>
                                    <div class="span2">
                                        <label for="">Quantidade</label>
                                        <input type="text" placeholder="Quantidade" id="quantidade" name="quantidade"
                                            class="span12" />
                                    </div>
                                    <div class="span2">
                                        <label for="">&nbsp;</label>
                                        <button class="button btn btn-success" id="btnAdicionarProduto">
                                            <span class="button__icon"><i class='bx bx-plus-circle'></i></span> <span class="button__text2">Adicionar</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="widget-box" id="divProdutos">
                                <div class="widget_content nopadding">
                                    <table width="100%" class="table table-bordered" id="tblProdutos">
                                        <thead>
                                            <tr>
                                                <th>Produto</th>
                                                <th width="8%">Quantidade</th>
                                                <th width="10%">Preço unit.</th>
                                                <th width="6%">Ações</th>
                                                <th width="10%">Sub-total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $total = 0;
                                            foreach ($produtos as $p) {
                                                $total = $total + $p->subTotal;
                                                $precoProduto = $p->preco ?: $p->precoVenda;
                                                echo '<tr>';
                                                echo '<td>' . $p->descricao . '</td>';
                                                echo '<td><div align="center">' . $p->quantidade . '</td>';
                                                echo '<td><div align="center">R$: ' . $precoProduto . '</td>';
                                                if (strtolower($result->status) != "cancelado") {
                                                    echo '<td><div align="center">';
                                                    echo '<a href="#" class="btn-nwe4 editar-produto" data-id="' . $p->idProdutos_os . '" data-descricao="' . $p->descricao . '" data-quantidade="' . $p->quantidade . '" data-preco="' . $precoProduto . '" title="Editar Produto"><i class="bx bx-edit"></i></a>&nbsp;';
                                                    echo '<a href="" idAcao="' . $p->idProdutos_os . '" prodAcao="' . $p->idProdutos . '" quantAcao="' . $p->quantidade . '" title="Excluir Produto" class="btn-nwe4"><i class="bx bx-trash-alt"></i></a>';
                                                    echo '</td>';
                                                } else {
                                                    echo '<td></td>';
                                                }
                                                echo '<td><div align="center">R$: ' . number_format($p->subTotal, 2, ',', '.') . '</td>';
                                                echo '</tr>';
                                            } ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" style="text-align: right"><strong>Total:</strong>
                                                </td>
                                                <td>
                                                    <div align="center"><strong>R$
                                                            <?php echo number_format($total, 2, ',', '.'); ?><input
                                                                type="hidden" id="total-venda"
                                                                value="<?php echo number_format($total, 2); ?>"></strong>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!--Serviços-->
                        <div class="tab-pane" id="tab4">
                            <div class="span12 well" style="padding: 1%; margin-left: 0">
                                <form id="formServicos" action="<?php echo base_url() ?>index.php/os/adicionarServico"
                                    method="post">
                                    <div class="span6">
                                        <input type="hidden" name="idServico" id="idServico" />
                                        <input type="hidden" name="idOsServico" id="idOsServico"
                                            value="<?php echo $result->idOs; ?>" />
                                        <label for="">Serviço</label>
                                        <input type="text" class="span12" name="servico" id="servico"
                                            placeholder="Digite o nome do serviço" />
                                    </div>
                                    <div class="span2">
                                        <label for="">Preço</label>
                                        <input type="text" placeholder="Preço" id="preco_servico" name="preco"
                                            class="span12 money" data-affixes-stay="true" data-thousands=""
                                            data-decimal="." />
                                    </div>
                                    <div class="span2">
                                        <label for="">Quantidade</label>
                                        <input type="text" placeholder="Quantidade" id="quantidade_servico"
                                            name="quantidade" class="span12" />
                                    </div>
                                    <div class="span2">
                                        <label for="">&nbsp;</label>
                                        <button class="button btn btn-success">
                                            <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span
                                                class="button__text2">Adicionar</span></button>
                                    </div>
                                </form>
                            </div>
                            <div class="widget-box" id="divServicos">
                                <div class="widget_content nopadding">
                                    <table width="100%" class="table table-bordered" id="tblServicos">
                                        <thead>
                                            <tr>
                                                <th>Serviço</th>
                                                <th width="8%">Quantidade</th>
                                                <th width="10%">Preço</th>
                                                <th width="6%">Ações</th>
                                                <th width="10%">Sub-totals</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $totals = 0;
                                            foreach ($servicos as $s) {
                                                $preco = $s->preco ?: $s->precoVenda;
                                                $quantidade = $s->quantidade ?: 1;
                                                $subtotals = $preco * $quantidade;
                                                $totals = $totals + $subtotals;
                                                echo '<tr>';
                                                echo '<td>' . $s->nome . '</td>';
                                                echo '<td><div align="center">' . $quantidade . '</div></td>';
                                                echo '<td><div align="center">R$ ' . $preco . '</div></td>';
                                                echo '<td><div align="center">';
                                                echo '<span class="btn-nwe4 editar-servico" data-id="' . $s->idServicos_os . '" data-nome="' . $s->nome . '" data-quantidade="' . $quantidade . '" data-preco="' . $preco . '" title="Editar Serviço"><i class="bx bx-edit"></i></span>&nbsp;';
                                                echo '<span idAcao="' . $s->idServicos_os . '" title="Excluir Serviço" class="btn-nwe4 servico"><i class="bx bx-trash-alt"></i></span>';
                                                echo '</div></td>';
                                                echo '<td><div align="center">R$: ' . number_format($subtotals, 2, ',', '.') . '</div></td>';
                                                echo '</tr>';
                                            } ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" style="text-align: right"><strong>Total:</strong>
                                                </td>
                                                <td>
                                                    <div align="center"><strong>R$
                                                            <?php echo number_format($totals, 2, ',', '.'); ?><input
                                                                type="hidden" id="total-servico"
                                                                value="<?php echo number_format($totals, 2); ?>"></strong>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!--Anexos-->
                        <div class="tab-pane" id="tab5">
                            <div class="span12" style="padding: 1%; margin-left: 0">
                                <div class="span12 well" style="padding: 1%; margin-left: 0" id="form-anexos">
                                    <form id="formAnexos" enctype="multipart/form-data" action="javascript:;"
                                        accept-charset="utf-8" s method="post">
                                        <div class="span10">
                                            <input type="hidden" name="idOsServico" id="idOsServico"
                                                value="<?php echo $result->idOs; ?>" />
                                            <label for="">Anexo</label>
                                            <input type="file" class="span12" name="userfile[]" multiple="multiple"
                                                size="20" />
                                        </div>
                                        <div class="span2">
                                            <label for="">.</label>
                                            <button class="button btn btn-success">
                                                <span class="button__icon"><i class='bx bx-paperclip'></i></span><span
                                                    class="button__text2">Anexar</span></button>
                                        </div>
                                    </form>
                                </div>
                                <div class="span12 pull-left" id="divAnexos" style="margin-left: 0">
                                    <?php
                                    foreach ($anexos as $a) {
                                        if ($a->thumb == null) {
                                            $thumb = base_url() . 'assets/img/icon-file.png';
                                            $link = base_url() . 'assets/img/icon-file.png';
                                        } else {
                                            $thumb = $a->url . '/thumbs/' . $a->thumb;
                                            $link = $a->url . '/' . $a->anexo;
                                        }
                                        echo '<div class="span3" style="min-height: 150px; margin-left: 0">
                                                    <a style="min-height: 150px;" href="#modal-anexo" imagem="' . $a->idAnexos . '" link="' . $link . '" role="button" class="btn anexo span12" data-toggle="modal">
                                                        <img src="' . $thumb . '" alt="">
                                                    </a>
                                                </div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!--Anotações-->
                        <div class="tab-pane" id="tab6">
                            <div class="span12" style="padding: 1%; margin-left: 0">

                                <div class="span12" id="divAnotacoes" style="margin-left: 0">

                                    <a href="#modal-anotacao" id="btn-anotacao" role="button" data-toggle="modal"
                                        class="button btn btn-success" style="max-width: 160px">
                                        <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span
                                            class="button__text2">Adicionar anotação</span></a>
                                    <hr>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Data/Hora</th>
                                                <th>Anotação</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            foreach ($anotacoes as $a) {
                                                echo '<tr>';
                                                echo '<td>' . date('d/m/Y H:i:s', strtotime($a->data_hora)) . '</td>';
                                                echo '<td>' . $a->anotacao . '</td>';
                                                echo '<td><span idAcao="' . $a->idAnotacoes . '" title="Excluir Anotação" class="btn-nwe4 anotacao"><i class="bx bx-trash-alt"></i></span></td>';
                                                echo '</tr>';
                                            }
                                            if (!$anotacoes) {
                                                echo '<tr><td colspan="3">Nenhuma anotação cadastrada</td></tr>';
                                            }

                                            ?>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                        <!-- Fim tab anotações -->

                        <!-- Check-in -->
                        <div class="tab-pane" id="tab7">
                            <div class="span12" style="padding: 1%; margin-left: 0">
                                <!-- Status do Atendimento -->
                                <div id="checkin-status-container" class="span12 well" style="padding: 1%; margin-left: 0">
                                    <h4><i class="bx bx-time"></i> Status do Atendimento</h4>
                                    <div id="checkin-status-info">
                                        <div class="alert alert-info">
                                            <strong>Status:</strong> <span id="checkin-status-text">Aguardando início</span>
                                            <br>
                                            <span id="checkin-info-adicional"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botões de Ação -->
                                <div class="span12" style="padding: 1%; margin-left: 0; text-align: center;">
                                    <button type="button" class="btn btn-success btn-large btn-iniciar-atendimento" id="btn-iniciar-atendimento" style="display: none;">
                                        <i class="bx bx-play-circle"></i> Iniciar Atendimento
                                    </button>

                                    <button type="button" class="btn btn-danger btn-large btn-finalizar-atendimento" id="btn-finalizar-atendimento" style="display: none;">
                                        <i class="bx bx-check-circle"></i> Finalizar Atendimento
                                    </button>
                                </div>

                                <!-- Painel de Início (Check-in) -->
                                <div id="panel-checkin" class="span12" style="display: none;">
                                    <div class="span12 well" style="padding: 1%; margin-left: 0">
                                        <h5><i class="bx bx-log-in"></i> Check-in do Técnico</h5>

                                        <!-- Assinatura do Técnico -->
                                        <?php $this->load->view('checkin/assinatura_canvas', [
                                            'id' => 'assinatura-tecnico-entrada',
                                            'titulo' => 'Assinatura do Técnico (Entrada)',
                                            'mostrar_campos' => false
                                        ]); ?>

                                        <!-- Fotos da Entrada -->
                                        <div class="span12" style="margin-left: 0; margin-top: 20px;">
                                            <h6>Fotos da Entrada:</h6>
                                            <input type="file" id="fotos-entrada" accept="image/*" multiple class="span12">
                                            <div id="fotos-entrada-preview" class="row-fluid" style="margin-top: 10px;"></div>
                                        </div>

                                        <!-- Observação de Entrada -->
                                        <div class="span12" style="margin-left: 0; margin-top: 15px;">
                                            <label for="observacao-entrada">Observação de Entrada:</label>
                                            <textarea id="observacao-entrada" class="span12" rows="3" placeholder="Descreva o estado inicial do equipamento, local, etc."></textarea>
                                        </div>

                                        <div class="span12" style="margin-left: 0; margin-top: 15px; text-align: center;">
                                            <button type="button" class="btn btn-primary" id="btn-confirmar-inicio">
                                                <i class="bx bx-check"></i> Confirmar Início do Atendimento
                                            </button>
                                            <button type="button" class="btn btn-default" id="btn-cancelar-inicio">Cancelar</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Painel de Fotos Durante -->
                                <div id="panel-fotos-durante" class="span12" style="display: none;">
                                    <div class="span12 well" style="padding: 1%; margin-left: 0">
                                        <h5><i class="bx bx-camera"></i> Fotos durante o Atendimento</h5>

                                        <input type="file" id="fotos-durante" accept="image/*" multiple class="span12">
                                        <div id="fotos-durante-preview" class="row-fluid" style="margin-top: 10px;"></div>

                                        <div id="fotos-durante-container"></div>
                                    </div>
                                </div>

                                <!-- Painel de Finalização (Check-out) -->
                                <div id="panel-checkout" class="span12" style="display: none;">
                                    <div class="span12 well" style="padding: 1%; margin-left: 0">
                                        <h5><i class="bx bx-log-out"></i> Check-out - Finalização</h5>

                                        <div class="span12" style="margin-left: 0">
                                            <!-- Assinatura Técnico Saída -->
                                            <div class="span6">
                                                <h6>Assinatura do Técnico (Saída):</h6>
                                                <?php $this->load->view('checkin/assinatura_modal', [
                                                    'modal_id' => 'modal-assinatura-tecnico-saida',
                                                    'titulo' => 'Assinatura do Técnico (Saída)',
                                                    'btn_abrir_id' => 'btn-abrir-assinatura-tecnico-saida',
                                                    'preview_id' => 'preview-assinatura-tecnico-saida',
                                                    'input_destino' => 'input-assinatura-tecnico-saida',
                                                    'mostrar_campos' => false
                                                ]); ?>
                                            </div>

                                            <!-- Assinatura Cliente -->
                                            <div class="span6">
                                                <h6>Assinatura do Cliente:</h6>
                                                <?php $this->load->view('checkin/assinatura_modal', [
                                                    'modal_id' => 'modal-assinatura-cliente',
                                                    'titulo' => 'Assinatura do Cliente',
                                                    'btn_abrir_id' => 'btn-abrir-assinatura-cliente',
                                                    'preview_id' => 'preview-assinatura-cliente',
                                                    'input_destino' => 'input-assinatura-cliente',
                                                    'mostrar_campos' => true,
                                                    'campos' => ['nome' => true, 'documento' => true]
                                                ]); ?>
                                            </div>
                                        </div>

                                        <!-- Fotos da Saída -->
                                        <div class="span12" style="margin-left: 0; margin-top: 20px;">
                                            <h6>Fotos da Saída:</h6>
                                            <input type="file" id="fotos-saida" accept="image/*" multiple class="span12">
                                            <div id="fotos-saida-preview" class="row-fluid" style="margin-top: 10px;"></div>
                                        </div>

                                        <!-- Observação de Saída -->
                                        <div class="span12" style="margin-left: 0; margin-top: 15px;">
                                            <label for="observacao-saida">Observação de Saída:</label>
                                            <textarea id="observacao-saida" class="span12" rows="3" placeholder="Descreva o serviço realizado, estado final, etc."></textarea>
                                        </div>

                                        <div class="span12" style="margin-left: 0; margin-top: 15px; text-align: center;">
                                            <button type="button" class="btn btn-success" id="btn-confirmar-fim">
                                                <i class="bx bx-check-double"></i> Confirmar Finalização
                                            </button>
                                            <button type="button" class="btn btn-default" id="btn-cancelar-fim">Cancelar</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Histórico -->
                                <div id="checkin-historico" class="span12" style="margin-top: 20px;">
                                    <h5>Histórico de Atendimentos</h5>
                                    <div id="checkin-historico-conteudo">
                                        <p class="text-muted">Carregando histórico...</p>
                                    </div>
                                </div>

                                <!-- Assinaturas Salvas -->
                                <div id="assinaturas-salvas" class="span12" style="margin-top: 20px;">
                                    <h5>Assinaturas Registradas</h5>
                                    <div id="assinaturas-salvas-conteudo" class="row-fluid"></div>
                                </div>

                                <!-- Fotos Salvas -->
                                <div id="fotos-salvas" class="span12" style="margin-top: 20px;">
                                    <h5>Fotos do Atendimento</h5>
                                    <div id="fotos-salvas-conteudo">
                                        <div class="row-fluid">
                                            <div class="span4">
                                                <h6>Entrada</h6>
                                                <div id="fotos-entrada-salvas"></div>
                                            </div>
                                            <div class="span4">
                                                <h6>Durante</h6>
                                                <div id="fotos-durante-salvas"></div>
                                            </div>
                                            <div class="span4">
                                                <h6>Saída</h6>
                                                <div id="fotos-saida-salvas"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Fim tab check-in -->
                    </div>
                </div>
                &nbsp
            </div>
        </div>
    </div>
</div>

<!-- Modal visualizar anexo -->
<div id="modal-anexo" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel">Visualizar Anexo</h3>
    </div>
    <div class="modal-body">
        <div class="span12" id="div-visualizar-anexo" style="text-align: center">
            <div class='progress progress-info progress-striped active'>
                <div class='bar' style='width: 100%'></div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Fechar</button>
        <a href="" id-imagem="" class="btn btn-inverse" id="download">Download</a>
        <a href="" link="" class="btn btn-danger" id="excluir-anexo">Excluir Anexo</a>
    </div>
</div>

<!-- Modal cadastro anotações -->
<div id="modal-anotacao" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="true">
    <form action="#" method="POST" id="formAnotacao">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="myModalLabel">Adicionar Anotação</h3>
        </div>
        <div class="modal-body">
            <div class="span12" id="divFormAnotacoes" style="margin-left: 0"></div>
            <div class="span12" style="margin-left: 0">
                <label for="anotacao">Anotação</label>
                <textarea class="span12" name="anotacao" id="anotacao" cols="30" rows="3"></textarea>
                <input type="hidden" name="os_id" value="<?php echo $result->idOs; ?>">
            </div>
        </div>
        <div class="modal-footer" style="display:flex;justify-content: center">
            <button class="btn" data-dismiss="modal" aria-hidden="true" id="btn-close-anotacao">Fechar</button>
            <button class="btn btn-primary">Adicionar</button>
        </div>
    </form>
</div>

<!-- Modal Faturar-->
<div id="modal-faturar" class="modal hide fade " tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="true">
    <form id="formFaturar" action="<?php echo current_url() ?>" method="post">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="myModalLabel">Faturar OS</h3>
        </div>
        <div class="modal-body">
            <div class="span12 alert alert-info" style="margin-left: 0"> Obrigatório o preenchimento dos campos com
                asterisco.</div>
            <div class="span12" style="margin-left: 0">
                <label for="descricao">Descrição</label>
                <input class="span12" id="descricao" type="text" name="descricao"
                    value="Fatura de OS Nº: <?php echo $result->idOs; ?> " />
            </div>
            <div class="span12" style="margin-left: 0">
                <div class="span12" style="margin-left: 0">
                    <label for="cliente">Cliente*</label>
                    <input class="span12" id="cliente" type="text" name="cliente"
                        value="<?php echo $result->nomeCliente ?>" />
                    <input type="hidden" name="clientes_id" id="clientes_id" value="<?php echo $result->clientes_id ?>">
                    <input type="hidden" name="os_id" id="os_id" value="<?php echo $result->idOs; ?>">
                    <input type="hidden" name="tipoDesconto" id="tipoDesconto"
                        value="<?php echo $result->tipo_desconto; ?>">
                </div>
            </div>
            <div class="span12" style="margin-left: 0">
                <div class="span6" style="margin-left: 0">
                    <label for="valor">Valor*</label>
                    <input type="hidden" id="tipo" name="tipo" value="receita" />
                    <input class="span12 money" id="valor" type="text" data-affixes-stay="true" data-thousands=""
                        data-decimal="." name="valor"
                        value="<?php echo number_format($totals + $total, 2, '.', ''); ?>" />
                </div>
                <div class="span6" style="margin-left: 2;">
                    <label for="valor">Valor Com Desconto*</label>
                    <input class="span12 money" id="faturar-desconto" type="text" name="faturar-desconto"
                        value="<?php echo number_format($result->valor_desconto, 2, '.', ''); ?> " />
                    <strong><span style="color: red" id="resultado"></span></strong>
                </div>
            </div>
            <div class="span12" style="margin-left: 0">
                <div class="span4" style="margin-left: 0">
                    <label for="vencimento">Data Entrada*</label>
                    <input class="span12 datepicker" autocomplete="off" id="vencimento" type="text" name="vencimento" />
                </div>
            </div>
            <div class="span12" style="margin-left: 0">
                <div class="span4" style="margin-left: 0">
                    <label for="recebido">Recebido?</label>
                    &nbsp &nbsp &nbsp &nbsp <input id="recebido" type="checkbox" name="recebido" value="1" />
                </div>
                <div id="divRecebimento" class="span8" style=" display: none">
                    <div class="span6">
                        <label for="recebimento">Data Recebimento</label>
                        <input class="span12 datepicker" autocomplete="off" id="recebimento" type="text"
                            name="recebimento" />
                    </div>
                    <div class="span6">
                        <label for="formaPgto">Forma Pgto</label>
                        <select name="formaPgto" id="formaPgto" class="span12">
                            <option value="Dinheiro">Dinheiro</option>
                            <option value="Cartão de Crédito">Cartão de Crédito</option>
                            <option value="Cartão de Débito">Cartão de Débito</option>
                            <option value="Boleto">Boleto</option>
                            <option value="Depósito">Depósito</option>
                            <option value="Pix">Pix</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="display:flex;justify-content: center">
            <button class="button btn btn-warning" data-dismiss="modal" aria-hidden="true"
                id="btn-cancelar-faturar"><span class="button__icon"><i class="bx bx-x"></i></span><span
                    class="button__text2">Cancelar</span></button>
            <button class="button btn btn-danger"><span class="button__icon"><i class='bx bx-dollar'></i></span>
                <span class="button__text2">Faturar</span></button>
        </div>
    </form>
</div>

<script src="<?php echo base_url(); ?>assets/js/maskmoney.js"></script>

<script type="text/javascript">
    function calcDesconto(valor, desconto, tipoDesconto) {
        var resultado = 0;
        if (tipoDesconto == 'real') {
            resultado = valor - desconto;
        }
        if (tipoDesconto == 'porcento') {
            resultado = (valor - desconto * valor / 100).toFixed(2);
        }
        return resultado;
    }

    function validarDesconto(resultado, valor) {
        if (resultado == valor) {
            return resultado = "";
        } else {
            return resultado.toFixed(2);
        }
    }
    var valorBackup = $("#valorTotal").val();

    $("#quantidade").keyup(function () {
        this.value = this.value.replace(/[^0-9.]/g, '');
    });

    $("#quantidade_servico").keyup(function () {
        this.value = this.value.replace(/[^0-9.]/g, '');
    });
    $('#tipoDesconto').on('change', function () {
        if (Number($("#desconto").val()) >= 0) {
            $('#resultado').val(calcDesconto(Number($("#valorTotal").val()), Number($("#desconto").val()), $("#tipoDesconto").val()));
            $('#resultado').val(validarDesconto(Number($('#resultado').val()), Number($("#valorTotal").val())));
        }
    });
    $("#desconto").keyup(function () {
        this.value = this.value.replace(/[^0-9.]/g, '');
        if ($("#valorTotal").val() == null || $("#valorTotal").val() == '') {
            $('#errorAlert').text('Valor não pode ser apagado.').css("display", "inline").fadeOut(5000);
            $('#desconto').val('');
            $('#resultado').val('');
            $("#valorTotal").val(valorBackup);
            $("#desconto").focus();

        } else if (Number($("#desconto").val()) >= 0) {
            $('#resultado').val(calcDesconto(Number($("#valorTotal").val()), Number($("#desconto").val()), $("#tipoDesconto").val()));
            $('#resultado').val(validarDesconto(Number($('#resultado').val()), Number($("#valorTotal").val())));
        } else {
            $('#errorAlert').text('Erro desconhecido.').css("display", "inline").fadeOut(5000);
            $('#desconto').val('');
            $('#resultado').val('');
        }
    });

    $("#valorTotal").focusout(function () {
        $("#valorTotal").val(valorBackup);
        if ($("#valorTotal").val() == '0.00' && $('#resultado').val() != '') {
            $('#errorAlert').text('Você não pode apagar o valor.').css("display", "inline").fadeOut(6000);
            $('#resultado').val('');
            $("#valorTotal").val(valorBackup);
            $('#resultado').val(calcDesconto(Number($("#valorTotal").val()), Number($("#desconto").val())));
            $('#resultado').val(validarDesconto(Number($('#resultado').val()), Number($("#valorTotal").val())));
            $("#desconto").focus();
        } else {
            $('#resultado').val(calcDesconto(Number($("#valorTotal").val()), Number($("#desconto").val())));
            $('#resultado').val(validarDesconto(Number($('#resultado').val()), Number($("#valorTotal").val())));
        }
    });

    $('#resultado').focusout(function () {
        if (Number($('#resultado').val()) > Number($("#valorTotal").val())) {
            $('#errorAlert').text('Desconto não pode ser maior que o Valor.').css("display", "inline").fadeOut(6000);
            $('#resultado').val('');
        }
        if ($("#desconto").val() != "" || $("#desconto").val() != null) {
            $('#resultado').val(calcDesconto(Number($("#valorTotal").val()), Number($("#desconto").val())));
            $('#resultado').val(validarDesconto(Number($('#resultado').val()), Number($("#valorTotal").val())));
        }
    });
    $(document).ready(function () {

        // Função auxiliar para carregamento assíncrono de conteúdo (substitui .load() síncrono)
        function loadContentAsync(selector, url, targetSelector) {
            var $element = $(selector);
            if ($element.length === 0) return;

            $.ajax({
                url: url,
                method: 'GET',
                async: true,
                success: function(data) {
                    var $temp = $('<div>').html(data);
                    var $newContent = $temp.find(targetSelector);
                    if ($newContent.length > 0) {
                        $element.html($newContent.html());
                    } else {
                        $element.html(data);
                    }
                },
                error: function() {
                    console.error('Erro ao carregar conteúdo de:', url);
                }
            });
        }

        $(".money").maskMoney();

        $('#recebido').click(function (event) {
            var flag = $(this).is(':checked');
            if (flag == true) {
                $('#divRecebimento').show();
            } else {
                $('#divRecebimento').hide();
            }
        });

        $("#formFaturar").validate({
            rules: {
                descricao: {
                    required: true
                },
                cliente: {
                    required: true
                },
                valor: {
                    required: true
                },
                vencimento: {
                    required: true
                }

            },
            messages: {
                descricao: {
                    required: 'Campo Requerido.'
                },
                cliente: {
                    required: 'Campo Requerido.'
                },
                valor: {
                    required: 'Campo Requerido.'
                },
                vencimento: {
                    required: 'Campo Requerido.'
                }
            },
            submitHandler: function (form) {
                var dados = $(form).serialize();
                var qtdProdutos = $('#tblProdutos >tbody >tr').length;
                var qtdServicos = $('#tblServicos >tbody >tr').length;
                var qtdTotalProdutosServicos = qtdProdutos + qtdServicos;

                $('#btn-cancelar-faturar').trigger('click');

                if (qtdTotalProdutosServicos <= 0) {
                    Swal.fire({
                        type: "error",
                        title: "Atenção",
                        text: "Não é possível faturar uma OS sem serviços e/ou produtos"
                    });
                } else if (qtdTotalProdutosServicos > 0) {
                    $.ajax({
                        type: "POST",
                        url: "<?php echo base_url(); ?>index.php/os/faturar",
                        data: dados,
                        dataType: 'json',
                        success: function (data) {
                            if (data.result == true) {
                                window.location.reload(true);
                            } else {
                                Swal.fire({
                                    type: "error",
                                    title: "Atenção",
                                    text: "Ocorreu um erro ao tentar faturar OS."
                                });
                                $('#progress-fatura').hide();
                            }
                        }
                    });

                    return false;
                }
            }
        });
        $('#formDesconto').submit(function (e) {
            e.preventDefault();
            var form = $(this);
            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: form.serialize(),
                beforeSend: function () {
                    Swal.fire({
                        title: 'Processando',
                        text: 'Registrando desconto...',
                        icon: 'info',
                        showCloseButton: false,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    });
                },
                success: function (response) {
                    if (response.result) {
                        Swal.fire({
                            type: "success",
                            title: "Sucesso",
                            text: response.messages
                        });
                        setTimeout(function () {
                            window.location.href = window.BaseUrl + 'index.php/os/editar/' + <?php echo $result->idOs ?>;
                        }, 2000);
                    } else {
                        Swal.fire({
                            type: "error",
                            title: "Atenção",
                            text: response.messages
                        });
                    }

                },
                error: function (response) {
                    Swal.fire({
                        type: "error",
                        title: "Atenção",
                        text: response.responseJSON.messages
                    });
                }
            });
        });

        $("#formwhatsapp").validate({
            rules: {
                descricao: {
                    required: true
                },
                cliente: {
                    required: true
                },
                valor: {
                    required: true
                },
                vencimento: {
                    required: true
                }

            },
            messages: {
                descricao: {
                    required: 'Campo Requerido.'
                },
                cliente: {
                    required: 'Campo Requerido.'
                },
                valor: {
                    required: 'Campo Requerido.'
                },
                vencimento: {
                    required: 'Campo Requerido.'
                }
            },
            submitHandler: function (form) {
                var dados = $(form).serialize();
                $('#btn-cancelar-faturar').trigger('click');
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url(); ?>index.php/os/faturar",
                    data: dados,
                    dataType: 'json',
                    success: function (data) {
                        if (data.result == true) {

                            window.location.reload(true);
                        } else {
                            Swal.fire({
                                type: "error",
                                title: "Atenção",
                                text: "Ocorreu um erro ao tentar  OS."
                            });
                            $('#progress-fatura').hide();
                        }
                    }
                });

                return false;
            }
        });

        $("#produto").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteProduto",
            minLength: 2,
            select: function (event, ui) {
                $("#codDeBarra").val(ui.item.codbar);
                $("#idProduto").val(ui.item.id);
                $("#estoque").val(ui.item.estoque);
                $("#preco").val(ui.item.preco);
                $("#quantidade").focus();
            }
        });

        $("#servico").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteServico",
            minLength: 2,
            select: function (event, ui) {
                $("#idServico").val(ui.item.id);
                $("#preco_servico").val(ui.item.preco);
                $("#quantidade_servico").focus();
            }
        });


        $("#cliente").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteCliente",
            minLength: 2,
            select: function (event, ui) {
                $("#clientes_id").val(ui.item.id);
            }
        });

        $("#tecnico").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteUsuario",
            minLength: 2,
            select: function (event, ui) {
                $("#usuarios_id").val(ui.item.id);
            }
        });

        $("#termoGarantia").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteTermoGarantia",
            minLength: 1,
            select: function (event, ui) {
                if (ui.item.id) {
                    $("#garantias_id").val(ui.item.id);
                }
            }
        });

        $('#termoGarantia').on('change', function () {
            if (!$(this).val() && $("#garantias_id").val()) {
                $("#garantias_id").val('');
                Swal.fire({
                    type: "success",
                    title: "Sucesso",
                    text: "Termo de garantia removido"
                });
            }
        });

        $("#formOs").validate({
            rules: {
                cliente: {
                    required: true
                },
                tecnico: {
                    required: true
                },
                dataInicial: {
                    required: true
                }
            },
            messages: {
                cliente: {
                    required: 'Campo Requerido.'
                },
                tecnico: {
                    required: 'Campo Requerido.'
                },
                dataInicial: {
                    required: 'Campo Requerido.'
                }
            },
            errorClass: "help-inline",
            errorElement: "span",
            highlight: function (element, errorClass, validClass) {
                $(element).parents('.control-group').addClass('error');
            },
            unhighlight: function (element, errorClass, validClass) {
                $(element).parents('.control-group').removeClass('error');
                $(element).parents('.control-group').addClass('success');
            }
        });

        $("#formProdutos").validate({
            rules: {
                preco: {
                    required: true
                },
                quantidade: {
                    required: true
                }
            },
            messages: {
                preco: {
                    required: 'Inserir o preço'
                },
                quantidade: {
                    required: 'Insira a quantidade'
                }
            },
            submitHandler: function (form) {
                var quantidade = parseInt($("#quantidade").val());
                var estoque = parseInt($("#estoque").val());

                <?php if (!$configuration['control_estoque']) {
                    echo 'estoque = 1000000';
                }
                ; ?>

                if (estoque < quantidade) {
                    Swal.fire({
                        type: "error",
                        title: "Atenção",
                        text: "Você não possui estoque suficiente."
                    });
                } else {
                    var dados = $(form).serialize();
                    $("#divProdutos").html("<div class='progress progress-info progress-striped active'><div class='bar' style='width: 100%'></div></div>");
                    $.ajax({
                        type: "POST",
                        url: "<?php echo base_url(); ?>index.php/os/adicionarProduto",
                        data: dados,
                        dataType: 'json',
                        success: function (data) {
                            if (data.result == true) {
                                loadContentAsync("#divProdutos", "<?php echo current_url(); ?>", "#divProdutos");
                                $("#quantidade").val('');
                                $("#preco").val('');
                                $("#resultado").val('');
                                $("#desconto").val('');
                                loadContentAsync("#divValorTotal", "<?php echo current_url(); ?>", "#divValorTotal");
                                $("#produto").val('').focus();
                            } else {
                                Swal.fire({
                                    type: "error",
                                    title: "Atenção",
                                    text: "Ocorreu um erro ao tentar adicionar produto."
                                });
                            }
                        }
                    });
                    return false;
                }
            }
        });

        $("#formServicos").validate({
            rules: {
                servico: {
                    required: true
                },
                preco: {
                    required: true
                },
                quantidade: {
                    required: true
                },
            },
            messages: {
                servico: {
                    required: 'Insira um serviço'
                },
                preco: {
                    required: 'Insira o preço'
                },
                quantidade: {
                    required: 'Insira a quantidade'
                },
            },
            submitHandler: function (form) {
                var dados = $(form).serialize();

                $("#divServicos").html("<div class='progress progress-info progress-striped active'><div class='bar' style='width: 100%'></div></div>");
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url(); ?>index.php/os/adicionarServico",
                    data: dados,
                    dataType: 'json',
                    success: function (data) {
                        if (data.result == true) {
                            loadContentAsync("#divServicos", "<?php echo current_url(); ?>", "#divServicos");
                            $("#quantidade_servico").val('');
                            $("#preco_servico").val('');
                            $("#resultado").val('');
                            $("#desconto").val('');
                            loadContentAsync("#divValorTotal", "<?php echo current_url(); ?>", "#divValorTotal");
                            $("#servico").val('').focus();
                        } else {
                            Swal.fire({
                                type: "error",
                                title: "Atenção",
                                text: "Ocorreu um erro ao tentar adicionar serviço."
                            });
                        }
                    }
                });
                return false;
            }
        });

        $("#formAnotacao").validate({
            rules: {
                anotacao: {
                    required: true
                }
            },
            messages: {
                anotacao: {
                    required: 'Insira a anotação'
                }
            },
            submitHandler: function (form) {
                var dados = $(form).serialize();
                $("#divFormAnotacoes").html("<div class='progress progress-info progress-striped active'><div class='bar' style='width: 100%'></div></div>");

                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url(); ?>index.php/os/adicionarAnotacao",
                    data: dados,
                    dataType: 'json',
                    success: function (data) {
                        if (data.result == true) {
                            loadContentAsync("#divAnotacoes", "<?php echo current_url(); ?>", "#divAnotacoes");
                            $("#anotacao").val('');
                            $('#btn-close-anotacao').trigger('click');
                            $("#divFormAnotacoes").html('');
                        } else {
                            Swal.fire({
                                type: "error",
                                title: "Atenção",
                                text: "Ocorreu um erro ao tentar adicionar anotação."
                            });
                        }
                    }
                });
                return false;
            }
        });

        $("#formAnexos").validate({
            submitHandler: function (form) {
                //var dados = $( form ).serialize();
                var dados = new FormData(form);
                $("#form-anexos").hide('1000');
                $("#divAnexos").html("<div class='progress progress-info progress-striped active'><div class='bar' style='width: 100%'></div></div>");
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url(); ?>index.php/os/anexar",
                    data: dados,
                    mimeType: "multipart/form-data",
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: 'json',
                    success: function (data) {
                        if (data.result == true) {
                            loadContentAsync("#divAnexos", "<?php echo current_url(); ?>", "#divAnexos");
                            $("#userfile").val('');

                        } else {
                            $("#divAnexos").html('<div class="alert fade in"><button type="button" class="close" data-dismiss="alert">×</button><strong>Atenção!</strong> ' + data.mensagem + '</div>');
                        }
                    },
                    error: function () {
                        $("#divAnexos").html('<div class="alert alert-danger fade in"><button type="button" class="close" data-dismiss="alert">×</button><strong>Atenção!</strong> Ocorreu um erro. Verifique se você anexou o(s) arquivo(s).</div>');
                    }
                });
                $("#form-anexos").show('1000');
                return false;
            }
        });

        $(document).on('click', 'a', function (event) {
            var idProduto = $(this).attr('idAcao');
            var quantidade = $(this).attr('quantAcao');
            var produto = $(this).attr('prodAcao');
            var idOS = "<?php echo $result->idOs ?>"
            if ((idProduto % 1) == 0) {
                $("#divProdutos").html("<div class='progress progress-info progress-striped active'><div class='bar' style='width: 100%'></div></div>");
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url(); ?>index.php/os/excluirProduto",
                    data: "idProduto=" + idProduto + "&quantidade=" + quantidade + "&produto=" + produto + "&idOs=" + idOS,
                    dataType: 'json',
                    success: function (data) {
                        if (data.result == true) {
                            loadContentAsync("#divProdutos", "<?php echo current_url(); ?>", "#divProdutos");
                            loadContentAsync("#divValorTotal", "<?php echo current_url(); ?>", "#divValorTotal");
                            $("#resultado").val('');
                            $("#desconto").val('');

                        } else {
                            Swal.fire({
                                type: "error",
                                title: "Atenção",
                                text: "Ocorreu um erro ao tentar excluir produto."
                            });
                        }
                    }
                });
                return false;
            }

        });

        $(document).on('click', '.servico', function (event) {
            var idServico = $(this).attr('idAcao');
            var idOS = "<?php echo $result->idOs ?>"
            if ((idServico % 1) == 0) {
                $("#divServicos").html("<div class='progress progress-info progress-striped active'><div class='bar' style='width: 100%'></div></div>");
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url(); ?>index.php/os/excluirServico",
                    data: "idServico=" + idServico + "&idOs=" + idOS,
                    dataType: 'json',
                    success: function (data) {
                        if (data.result == true) {
                            loadContentAsync("#divServicos", "<?php echo current_url(); ?>", "#divServicos");
                            loadContentAsync("#divValorTotal", "<?php echo current_url(); ?>", "#divValorTotal");
                            $("#resultado").val('');
                            $("#desconto").val('');

                        } else {
                            Swal.fire({
                                type: "error",
                                title: "Atenção",
                                text: "Ocorreu um erro ao tentar excluir serviço."
                            });
                        }
                    }
                });
                return false;
            }
        });

        $(document).on('click', '.anexo', function (event) {
            event.preventDefault();
            var link = $(this).attr('link');
            var id = $(this).attr('imagem');
            var url = '<?php echo base_url(); ?>index.php/os/excluirAnexo/';
            $("#div-visualizar-anexo").html('<img src="' + link + '" alt="">');
            $("#excluir-anexo").attr('link', url + id);

            $("#download").attr('href', "<?php echo base_url(); ?>index.php/os/downloadanexo/" + id);

        });

        $(document).on('click', '#excluir-anexo', function (event) {
            event.preventDefault();
            var link = $(this).attr('link');
            var idOS = "<?php echo $result->idOs ?>"
            $('#modal-anexo').modal('hide');
            $("#divAnexos").html("<div class='progress progress-info progress-striped active'><div class='bar' style='width: 100%'></div></div>");

            $.ajax({
                type: "POST",
                url: link,
                dataType: 'json',
                data: "idOs=" + idOS,
                success: function (data) {
                    if (data.result == true) {
                        loadContentAsync("#divAnexos", "<?php echo current_url(); ?>", "#divAnexos");
                    } else {
                        Swal.fire({
                            type: "error",
                            title: "Atenção",
                            text: data.mensagem
                        });
                    }
                }
            });
        });

        $(document).on('click', '.anotacao', function (event) {
            var idAnotacao = $(this).attr('idAcao');
            var idOS = "<?php echo $result->idOs ?>"
            if ((idAnotacao % 1) == 0) {
                $("#divAnotacoes").html("<div class='progress progress-info progress-striped active'><div class='bar' style='width: 100%'></div></div>");
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url(); ?>index.php/os/excluirAnotacao",
                    data: "idAnotacao=" + idAnotacao + "&idOs=" + idOS,
                    dataType: 'json',
                    success: function (data) {
                        if (data.result == true) {
                            loadContentAsync("#divAnotacoes", "<?php echo current_url(); ?>", "#divAnotacoes");

                        } else {
                            Swal.fire({
                                type: "error",
                                title: "Atenção",
                                text: "Ocorreu um erro ao tentar excluir Anotação."
                            });
                        }
                    }
                });
                return false;
            }
        });

        $(".datepicker").datepicker({
            dateFormat: 'dd/mm/yy'
        });

        $('.editor').trumbowyg({
            lang: 'pt_br',
            semantic: { 'strikethrough': 's', }
        });

        // ============================================
        // INTEGRAÇÃO DO SISTEMA DE CHECK-IN
        // ============================================

        // Configuração
        var checkinConfig = {
            baseUrl: '<?php echo base_url(); ?>',
            os_id: <?php echo $result->idOs; ?>,
            usuario_id: '<?php echo $this->session->userdata('id_admin'); ?>',
            usuario_nome: '<?php echo $this->session->userdata('nome'); ?>'
        };

        // Variáveis de estado
        var checkinAtivo = false;
        var checkinId = null;
        var fotosEntrada = [];
        var fotosSaida = [];

        // Carrega status inicial
        carregarCheckinStatus();

        // Botão Iniciar Atendimento
        $('#btn-iniciar-atendimento').on('click', function() {
            $(this).hide();
            $('#panel-checkin').slideDown();
        });

        // Botão Cancelar Início
        $('#btn-cancelar-inicio').on('click', function() {
            $('#panel-checkin').slideUp();
            $('#btn-iniciar-atendimento').show();
        });

        // Botão Confirmar Início
        $('#btn-confirmar-inicio').on('click', function() {
            confirmarInicioAtendimento();
        });

        // Botão Finalizar Atendimento
        $('#btn-finalizar-atendimento').on('click', function() {
            $(this).hide();
            $('#panel-checkout').slideDown();
        });

        // Botão Cancelar Fim
        $('#btn-cancelar-fim').on('click', function() {
            $('#panel-checkout').slideUp();
            $('#btn-finalizar-atendimento').show();
        });

        // Botão Confirmar Fim
        $('#btn-confirmar-fim').on('click', function() {
            confirmarFimAtendimento();
        });

        // Preview de fotos de entrada
        $('#fotos-entrada').on('change', function() {
            previewFotos(this, '#fotos-entrada-preview', fotosEntrada);
        });

        // Preview de fotos de saída
        $('#fotos-saida').on('change', function() {
            previewFotos(this, '#fotos-saida-preview', fotosSaida);
        });

        // Upload de fotos durante
        $('#fotos-durante').on('change', function() {
            uploadFotosDurante(this);
        });

        // Função para carregar status
        function carregarCheckinStatus() {
            $.ajax({
                url: checkinConfig.baseUrl + 'index.php/checkin/status',
                type: 'POST',
                data: { os_id: checkinConfig.os_id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        atualizarInterfaceCheckin(response);
                    }
                }
            });
        }

        // Função para atualizar interface
        function atualizarInterfaceCheckin(dados) {
            checkinAtivo = dados.em_atendimento;
            checkinId = dados.checkin ? dados.checkin.idCheckin : null;

            // Atualiza texto de status
            if (checkinAtivo) {
                $('#checkin-status-text').text('Em Andamento');
                $('#checkin-status-text').removeClass().addClass('label label-warning');

                // Mostra botão de finalizar
                $('#btn-iniciar-atendimento').hide();
                $('#btn-finalizar-atendimento').show();

                // Esconde panel de início
                $('#panel-checkin').hide();

                // Mostra panel de fotos durante
                $('#panel-fotos-durante').show();

                // Esconde panel de finalização
                $('#panel-checkout').hide();

                // Carrega fotos durante
                carregarFotosDurante();
            } else {
                $('#checkin-status-text').text('Aguardando Início');
                $('#checkin-status-text').removeClass().addClass('label label-info');

                // Mostra botão de iniciar
                $('#btn-iniciar-atendimento').show();
                $('#btn-finalizar-atendimento').hide();

                // Esconde panels
                $('#panel-checkin').hide();
                $('#panel-fotos-durante').hide();
                $('#panel-checkout').hide();
            }

            // Renderiza assinaturas salvas
            if (dados.assinaturas) {
                renderizarAssinaturasSalvas(dados.assinaturas);
            }

            // Renderiza fotos salvas
            if (dados.fotos) {
                renderizarFotosSalvas(dados.fotos);
            }
        }

        // Confirma início do atendimento
        function confirmarInicioAtendimento() {
            var assinaturaTecnico = AssinaturaManager.obter('assinatura-tecnico-entrada');

            if (!assinaturaTecnico || assinaturaTecnico.estaVazio()) {
                Swal.fire({
                    type: 'warning',
                    title: 'Atenção',
                    text: 'A assinatura do técnico é obrigatória!'
                });
                return;
            }

            // Prepara dados
            var dados = {
                os_id: checkinConfig.os_id,
                observacao: $('#observacao-entrada').val(),
                assinatura: assinaturaTecnico.obterImagem(),
                fotos: fotosEntrada
            };

            // Obtém localização
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    dados.latitude = position.coords.latitude;
                    dados.longitude = position.coords.longitude;
                    enviarInicio(dados);
                }, function() {
                    enviarInicio(dados);
                });
            } else {
                enviarInicio(dados);
            }
        }

        function enviarInicio(dados) {
            // Usa o novo método com progresso circular para mobile
            CheckinFotos.enviarCheckinComProgresso(
                checkinConfig.baseUrl + 'index.php/checkin/iniciar',
                dados,
                {
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                type: 'success',
                                title: 'Sucesso',
                                text: 'Atendimento iniciado com sucesso!'
                            });
                            carregarCheckinStatus();
                        } else {
                            Swal.fire({
                                type: 'error',
                                title: 'Erro',
                                text: response.message || 'Erro ao iniciar atendimento'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            type: 'error',
                            title: 'Erro',
                            text: 'Erro na comunicação com o servidor'
                        });
                    }
                }
            );
        }

        // Confirma fim do atendimento
        function confirmarFimAtendimento() {
            // Obtém assinaturas dos inputs hidden (salvas pelos modais)
            var assinaturaTecnico = $('#input-assinatura-tecnico-saida').val();
            var assinaturaCliente = $('#input-assinatura-cliente').val();

            if (!assinaturaTecnico) {
                Swal.fire({
                    type: 'warning',
                    title: 'Atenção',
                    text: 'A assinatura do técnico na saída é obrigatória!'
                });
                return;
            }

            if (!assinaturaCliente) {
                Swal.fire({
                    type: 'warning',
                    title: 'Atenção',
                    text: 'A assinatura do cliente é obrigatória!'
                });
                return;
            }

            // Obtém o nome do cliente do campo ou usa o nome da OS (já vinculado)
            var nomeCliente = $('#modal-assinatura-cliente-nome').val() || $('#assinatura-cliente-nome-hidden').val() || '<?php echo addslashes($result->nomeCliente); ?>' || 'Cliente';

            // Prepara dados
            var dados = {
                os_id: checkinConfig.os_id,
                observacao: $('#observacao-saida').val(),
                assinatura_tecnico: assinaturaTecnico,
                assinatura_cliente: assinaturaCliente,
                nome_cliente: nomeCliente,
                documento_cliente: $('#modal-assinatura-cliente-documento').val() || $('#assinatura-cliente-documento-hidden').val(),
                fotos: fotosSaida
            };

            // Obtém localização
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    dados.latitude = position.coords.latitude;
                    dados.longitude = position.coords.longitude;
                    enviarFim(dados);
                }, function() {
                    enviarFim(dados);
                });
            } else {
                enviarFim(dados);
            }
        }

        function enviarFim(dados) {
            // Usa o novo método com progresso circular para mobile
            CheckinFotos.enviarCheckinComProgresso(
                checkinConfig.baseUrl + 'index.php/checkin/finalizar',
                dados,
                {
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                type: 'success',
                                title: 'Sucesso',
                                text: 'Atendimento finalizado com sucesso!'
                            });
                            carregarCheckinStatus();
                        } else {
                            Swal.fire({
                                type: 'error',
                                title: 'Erro',
                                text: response.message || 'Erro ao finalizar atendimento'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            type: 'error',
                            title: 'Erro',
                            text: 'Erro na comunicação com o servidor'
                        });
                    }
                }
            );
        }

        // Preview de fotos
        function previewFotos(input, container, arrayFotos) {
            $(container).empty();
            arrayFotos.length = 0;

            if (input.files && input.files.length > 0) {
                $.each(input.files, function(index, file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        arrayFotos.push(e.target.result);
                        $(container).append('<div class="span3" style="margin-bottom: 10px;"><img src="' + e.target.result + '" style="max-width: 100%; border: 1px solid #ddd;"></div>');
                    };
                    reader.readAsDataURL(file);
                });
            }
        }

        // Upload de fotos durante com progresso circular
        function uploadFotosDurante(input) {
            if (!input.files || input.files.length === 0) return;

            var arquivos = [];
            $.each(input.files, function(index, file) {
                arquivos.push(file);
            });

            // Usa o novo método com progresso circular
            CheckinFotos.uploadMultiplo({
                os_id: checkinConfig.os_id,
                checkin_id: checkinId,
                etapa: 'durante',
                arquivos: arquivos
            }, {
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            type: 'success',
                            title: 'Sucesso',
                            text: response.message
                        });
                        carregarFotosDurante();
                        $('#fotos-durante').val('');
                    } else {
                        Swal.fire({
                            type: 'error',
                            title: 'Erro',
                            text: response.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    var msg = 'Erro ao enviar fotos';
                    if (error && error.message) {
                        msg = error.message;
                    }
                    Swal.fire({
                        type: 'error',
                        title: 'Erro',
                        text: msg
                    });
                }
            });
        }

        // Carrega fotos durante
        function carregarFotosDurante() {
            CheckinFotos.listarFotos(checkinConfig.os_id, 'durante', {
                success: function(response) {
                    if (response.success) {
                        CheckinFotos.renderizarGaleria(response.fotos, '#fotos-durante-container', {
                            mostrarDescricao: false,
                            mostrarExcluir: true,
                            mostrarDownload: true,
                            colunas: 4
                        });
                    }
                }
            });
        }

        // Renderiza assinaturas salvas
        function renderizarAssinaturasSalvas(assinaturas) {
            var html = '';

            if (assinaturas.tecnico_entrada) {
                html += renderizarAssinaturaItem('Técnico (Entrada)', assinaturas.tecnico_entrada);
            }
            if (assinaturas.tecnico_saida) {
                html += renderizarAssinaturaItem('Técnico (Saída)', assinaturas.tecnico_saida);
            }
            if (assinaturas.cliente_saida) {
                html += renderizarAssinaturaItem('Cliente', assinaturas.cliente_saida);
            }

            $('#assinaturas-salvas-conteudo').html(html || '<p class="text-muted">Nenhuma assinatura registrada.</p>');
        }

        function renderizarAssinaturaItem(titulo, assinatura) {
            return '<div class="span4 assinatura-salva-item">' +
                '<h6>' + titulo + '</h6>' +
                '<img src="' + assinatura.url + '/' + assinatura.assinatura + '" style="max-width: 100%;">' +
                (assinatura.nome_assinante ? '<div class="assinatura-info"><strong>' + assinatura.nome_assinante + '</strong></div>' : '') +
                '</div>';
        }

        // Renderiza fotos salvas
        function renderizarFotosSalvas(fotos) {
            if (fotos.entrada && fotos.entrada.length > 0) {
                CheckinFotos.renderizarGaleria(fotos.entrada, '#fotos-entrada-salvas', {
                    mostrarDescricao: false,
                    mostrarExcluir: false,
                    mostrarDownload: true,
                    colunas: 2
                });
            } else {
                $('#fotos-entrada-salvas').html('<p class="text-muted">Nenhuma foto</p>');
            }

            if (fotos.durante && fotos.durante.length > 0) {
                CheckinFotos.renderizarGaleria(fotos.durante, '#fotos-durante-salvas', {
                    mostrarDescricao: false,
                    mostrarExcluir: false,
                    mostrarDownload: true,
                    colunas: 2
                });
            } else {
                $('#fotos-durante-salvas').html('<p class="text-muted">Nenhuma foto</p>');
            }

            if (fotos.saida && fotos.saida.length > 0) {
                CheckinFotos.renderizarGaleria(fotos.saida, '#fotos-saida-salvas', {
                    mostrarDescricao: false,
                    mostrarExcluir: false,
                    mostrarDownload: true,
                    colunas: 2
                });
            } else {
                $('#fotos-saida-salvas').html('<p class="text-muted">Nenhuma foto</p>');
            }
        }

        // Inicializa helper de fotos
        CheckinFotos.init({ baseUrl: checkinConfig.baseUrl });

        // ============================================
        // CONTROLE DOS MODAIS DE ASSINATURA
        // ============================================

        // Abrir modal de assinatura
        $(document).on('click', '.btn-abrir-assinatura', function(e) {
            e.preventDefault();
            var modalId = $(this).data('modal');

            // Pré-preenche o nome do cliente se for o modal de assinatura do cliente
            if (modalId === 'modal-assinatura-cliente') {
                var nomeCliente = '<?php echo addslashes($result->nomeCliente); ?>';
                var documentoCliente = '<?php echo addslashes($result->documento ?? $result->cpf ?? ''); ?>';

                // Se o campo estiver vazio, preenche com o nome da OS
                var campoNome = $('#' + modalId + ' .modal-assinatura-nome');
                if (campoNome.length && !campoNome.val()) {
                    campoNome.val(nomeCliente).prop('readonly', true).attr('title', 'Nome vinculado à OS');
                }

                var campoDocumento = $('#' + modalId + ' .modal-assinatura-documento');
                if (campoDocumento.length && !campoDocumento.val() && documentoCliente) {
                    campoDocumento.val(documentoCliente).prop('readonly', true).attr('title', 'Documento vinculado à OS');
                }
            }

            $('#' + modalId).modal('show');
        });

        // Editar assinatura (abre modal novamente)
        $(document).on('click', '.btn-editar-assinatura', function(e) {
            e.preventDefault();
            var modalId = $(this).data('modal');
            $('#' + modalId).modal('show');
        });

        // Limpar assinatura salva
        $(document).on('click', '.btn-limpar-assinatura', function(e) {
            e.preventDefault();
            var previewId = $(this).data('preview');
            var modalId = previewId.replace('preview-', 'modal-');

            // Limpa o preview
            $('#' + previewId).hide().find('img').attr('src', '');

            // Encontra o input hidden relacionado e limpa
            var inputId = previewId.replace('preview-', 'input-');
            $('#' + inputId).val('');

            // Limpa também os campos de nome e documento
            $('#' + modalId + '-nome-hidden').val('');
            $('#' + modalId + '-documento-hidden').val('');

            // Limpa os campos do modal
            $('#' + modalId + ' .modal-assinatura-nome').val('');
            $('#' + modalId + ' .modal-assinatura-documento').val('');

            // Também limpa o canvas se estiver aberto
            if (typeof AssinaturaManager !== 'undefined') {
                var assinatura = AssinaturaManager.obter(modalId);
                if (assinatura) {
                    assinatura.limpar();
                }
            }

            // Mostra o botão de abrir novamente
            var btnId = previewId.replace('preview-', 'btn-abrir-');
            $('#' + btnId).show();
        });

        // Salvar assinatura do modal
        $(document).on('click', '.btn-salvar-assinatura-modal', function(e) {
            e.preventDefault();

            var modalId = $(this).data('modal');
            var previewId = $(this).data('preview');
            var inputId = $(this).data('input');

            // Obtém o canvas do modal
            if (typeof AssinaturaManager === 'undefined') {
                Swal.fire({
                    type: 'error',
                    title: 'Erro',
                    text: 'Sistema de assinaturas não carregado.'
                });
                return;
            }

            var assinatura = AssinaturaManager.obter(modalId);
            if (!assinatura) {
                Swal.fire({
                    type: 'error',
                    title: 'Erro',
                    text: 'Canvas não inicializado. Feche e abra o modal novamente.'
                });
                return;
            }

            if (assinatura.estaVazio()) {
                Swal.fire({
                    type: 'warning',
                    title: 'Atenção',
                    text: 'Por favor, faça a assinatura antes de salvar.'
                });
                return;
            }

            // Obtém a imagem base64
            var imagemBase64 = assinatura.obterImagem();

            // Salva no input hidden
            $('#' + inputId).val(imagemBase64);

            // Salva campos adicionais (nome e documento) nos inputs hidden
            var nomeModal = $('#' + modalId + ' .modal-assinatura-nome').val();
            var documentoModal = $('#' + modalId + ' .modal-assinatura-documento').val();

            if (nomeModal) {
                $('#' + modalId + '-nome-hidden').val(nomeModal);
            }
            if (documentoModal) {
                $('#' + modalId + '-documento-hidden').val(documentoModal);
            }

            // Mostra no preview
            $('#' + previewId).show().find('img').attr('src', imagemBase64);

            // Esconde o botão de abrir
            var btnId = previewId.replace('preview-', 'btn-abrir-');
            $('#' + btnId).hide();

            // Fecha o modal
            $('#' + modalId).modal('hide');

            // Mostra mensagem de sucesso
            Swal.fire({
                type: 'success',
                title: 'Sucesso',
                text: 'Assinatura salva com sucesso!',
                timer: 1500,
                showConfirmButton: false
            });
        });

        // Quando o modal é fechado sem salvar, limpa o canvas mas mantém os campos
        $('.modal-assinatura').on('hidden', function() {
            var modalId = $(this).attr('id');

            // Limpa os campos do modal (mas não os hidden salvos)
            $('#' + modalId + ' .modal-assinatura-nome').val('');
            $('#' + modalId + ' .modal-assinatura-documento').val('');

            // Limpa o canvas
            if (typeof AssinaturaManager !== 'undefined') {
                var assinatura = AssinaturaManager.obter(modalId);
                if (assinatura) {
                    assinatura.limpar();
                }
            }
        });
    });
</script>

<!-- Modal Editar Produto -->
<div id="modal-editar-produto" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
        <h5 id="myModalLabel"><i class="bx bx-edit"></i> Editar Produto</h5>
    </div>
    <div class="modal-body">
        <input type="hidden" id="editar-idProdutoOs" />
        <input type="hidden" id="editar-idOsProduto" value="<?php echo $result->idOs; ?>" />
        <div class="span12" style="margin-left: 0">
            <label><strong>Produto:</strong></label>
            <span id="editar-descricao-produto" class="uneditable-input span12"></span>
        </div>
        <div class="span6" style="margin-left: 0">
            <label for="editar-quantidade-produto">Quantidade</label>
            <input type="text" id="editar-quantidade-produto" class="span12" />
        </div>
        <div class="span6" style="margin-left: 0">
            <label for="editar-preco-produto">Preço Unit.</label>
            <input type="text" id="editar-preco-produto" class="span12 money" data-affixes-stay="true" data-thousands="" data-decimal="." />
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
        <button class="btn btn-primary" id="btn-salvar-produto"><i class="bx bx-save"></i> Salvar</button>
    </div>
</div>

<!-- Modal Editar Serviço -->
<div id="modal-editar-servico" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
        <h5 id="myModalLabel"><i class="bx bx-edit"></i> Editar Serviço</h5>
    </div>
    <div class="modal-body">
        <input type="hidden" id="editar-idServicoOs" />
        <input type="hidden" id="editar-idOsServico" value="<?php echo $result->idOs; ?>" />
        <div class="span12" style="margin-left: 0">
            <label><strong>Serviço:</strong></label>
            <span id="editar-nome-servico" class="uneditable-input span12"></span>
        </div>
        <div class="span6" style="margin-left: 0">
            <label for="editar-quantidade-servico">Quantidade</label>
            <input type="text" id="editar-quantidade-servico" class="span12" />
        </div>
        <div class="span6" style="margin-left: 0">
            <label for="editar-preco-servico">Preço Unit.</label>
            <input type="text" id="editar-preco-servico" class="span12 money" data-affixes-stay="true" data-thousands="" data-decimal="." />
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
        <button class="btn btn-primary" id="btn-salvar-servico"><i class="bx bx-save"></i> Salvar</button>
    </div>
</div>

<script>
    // ============================================
    // EDIÇÃO DE PRODUTOS E SERVIÇOS
    // ============================================
    $(document).ready(function() {
        // Abrir modal editar produto
        $(document).on('click', '.editar-produto', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var descricao = $(this).data('descricao');
            var quantidade = $(this).data('quantidade');
            var preco = $(this).data('preco');

            $('#editar-idProdutoOs').val(id);
            $('#editar-descricao-produto').text(descricao);
            $('#editar-quantidade-produto').val(quantidade);
            $('#editar-preco-produto').val(preco);

            $('#modal-editar-produto').modal('show');
        });

        // Abrir modal editar serviço
        $(document).on('click', '.editar-servico', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var nome = $(this).data('nome');
            var quantidade = $(this).data('quantidade');
            var preco = $(this).data('preco');

            $('#editar-idServicoOs').val(id);
            $('#editar-nome-servico').text(nome);
            $('#editar-quantidade-servico').val(quantidade);
            $('#editar-preco-servico').val(preco);

            $('#modal-editar-servico').modal('show');
        });

        // Salvar edição do produto
        $('#btn-salvar-produto').on('click', function() {
            var idProdutoOs = $('#editar-idProdutoOs').val();
            var quantidade = $('#editar-quantidade-produto').val();
            var preco = $('#editar-preco-produto').val();
            var idOs = $('#editar-idOsProduto').val();

            if (!quantidade || quantidade <= 0) {
                Swal.fire({
                    type: 'warning',
                    title: 'Atenção',
                    text: 'Informe uma quantidade válida!'
                });
                return;
            }

            if (preco === '' || preco < 0) {
                Swal.fire({
                    type: 'warning',
                    title: 'Atenção',
                    text: 'Informe um preço válido!'
                });
                return;
            }

            $.ajax({
                type: "POST",
                url: "<?php echo base_url(); ?>index.php/os/editarProduto",
                data: {
                    idProdutoOs: idProdutoOs,
                    quantidade: quantidade,
                    preco: preco,
                    idOs: idOs
                },
                dataType: 'json',
                success: function(data) {
                    if (data.result == true) {
                        $('#modal-editar-produto').modal('hide');
                        Swal.fire({
                            type: 'success',
                            title: 'Sucesso',
                            text: 'Produto atualizado com sucesso!',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadContentAsync("#divProdutos", "<?php echo current_url(); ?>", "#divProdutos");
                        loadContentAsync("#divValorTotal", "<?php echo current_url(); ?>", "#divValorTotal");
                    } else {
                        Swal.fire({
                            type: 'error',
                            title: 'Erro',
                            text: 'Ocorreu um erro ao tentar atualizar o produto.'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        type: 'error',
                        title: 'Erro',
                        text: 'Erro na comunicação com o servidor'
                    });
                }
            });
        });

        // Salvar edição do serviço
        $('#btn-salvar-servico').on('click', function() {
            var idServicoOs = $('#editar-idServicoOs').val();
            var quantidade = $('#editar-quantidade-servico').val();
            var preco = $('#editar-preco-servico').val();
            var idOs = $('#editar-idOsServico').val();

            if (!quantidade || quantidade <= 0) {
                Swal.fire({
                    type: 'warning',
                    title: 'Atenção',
                    text: 'Informe uma quantidade válida!'
                });
                return;
            }

            if (preco === '' || preco < 0) {
                Swal.fire({
                    type: 'warning',
                    title: 'Atenção',
                    text: 'Informe um preço válido!'
                });
                return;
            }

            $.ajax({
                type: "POST",
                url: "<?php echo base_url(); ?>index.php/os/editarServico",
                data: {
                    idServicoOs: idServicoOs,
                    quantidade: quantidade,
                    preco: preco,
                    idOs: idOs
                },
                dataType: 'json',
                success: function(data) {
                    if (data.result == true) {
                        $('#modal-editar-servico').modal('hide');
                        Swal.fire({
                            type: 'success',
                            title: 'Sucesso',
                            text: 'Serviço atualizado com sucesso!',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadContentAsync("#divServicos", "<?php echo current_url(); ?>", "#divServicos");
                        loadContentAsync("#divValorTotal", "<?php echo current_url(); ?>", "#divValorTotal");
                    } else {
                        Swal.fire({
                            type: 'error',
                            title: 'Erro',
                            text: 'Ocorreu um erro ao tentar atualizar o serviço.'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        type: 'error',
                        title: 'Erro',
                        text: 'Erro na comunicação com o servidor'
                    });
                }
            });
        });

        // Inicializar máscara de dinheiro nos campos do modal
        $('.money').maskMoney();
    });
</script>

<!-- Scripts do Sistema de Check-in -->
<script src="<?php echo base_url(); ?>assets/js/assinatura-canvas.js"></script>
<script src="<?php echo base_url(); ?>assets/js/checkin-fotos.js"></script>
