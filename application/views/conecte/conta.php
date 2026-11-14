<div class="widget-box">
    <div class="widget-title">
        <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab1">Meus Dados</a></li>

            <div>
                <a title="Editar" class="button btn btn-success" style="max-width: 140px;margin: 5px" href="<?php echo base_url() ?>index.php/mine/editarDados/<?php echo $result->idClientes ?>">
                  <span class="button__icon"><i class="bx bx-edit"></i> </span> <span class="button__text2">Editar</span></a>
            </div>
        </ul>
    </div>
    <div class="widget-content tab-content">
        <div id="tab1" class="tab-pane active" style="min-height: 300px">

            <div class="accordion" id="collapse-group">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGOne" aria-expanded="true" aria-controls="collapseGOne">
                            <i class="bx bx-user me-2"></i> Dados Pessoais
                        </button>
                    </h2>
                    <div class="accordion-collapse collapse show" id="collapseGOne" data-bs-parent="#collapse-group">
                        <div class="accordion-body">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td style="text-align: right; width: 30%"><strong>Nome</strong></td>
                                        <td>
                                            <?php echo html_escape($result->nomeCliente); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: right; width: 30%"><strong>Contato</strong></td>
                                        <td>
                                            <?php echo html_escape($result->contato); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: right"><strong>Documento</strong></td>
                                        <td>
                                            <?php echo html_escape($result->documento); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: right"><strong>Data de Cadastro</strong></td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($result->dataCadastro)) ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGTwo" aria-expanded="false" aria-controls="collapseGTwo">
                            <i class="bx bx-phone me-2"></i> Contatos
                        </button>
                    </h2>
                    <div class="accordion-collapse collapse" id="collapseGTwo" data-bs-parent="#collapse-group">
                        <div class="accordion-body">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td style="text-align: right; width: 30%"><strong>Telefone</strong></td>
                                        <td>
                                            <?php echo html_escape($result->telefone); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: right"><strong>Celular</strong></td>
                                        <td>
                                            <?php echo html_escape($result->celular); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: right"><strong>Email</strong></td>
                                        <td>
                                            <?php echo html_escape($result->email); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGThree" aria-expanded="false" aria-controls="collapseGThree">
                            <i class="bx bx-map-alt me-2"></i> Endereço
                        </button>
                    </h2>
                    <div class="accordion-collapse collapse" id="collapseGThree" data-bs-parent="#collapse-group">
                        <div class="accordion-body">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td style="text-align: right; width: 30%"><strong>Rua</strong></td>
                                        <td>
                                            <?php echo html_escape($result->rua); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: right"><strong>Número</strong></td>
                                        <td>
                                            <?php echo html_escape($result->numero); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: right"><strong>Complemento</strong></td>
                                        <td>
                                            <?php echo html_escape($result->complemento); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: right"><strong>Bairro</strong></td>
                                        <td>
                                            <?php echo html_escape($result->bairro); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: right"><strong>Cidade</strong></td>
                                        <td>
                                            <?php echo html_escape($result->cidade); ?> -
                                            <?php echo html_escape($result->estado); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: right"><strong>CEP</strong></td>
                                        <td>
                                            <?php echo html_escape($result->cep); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
