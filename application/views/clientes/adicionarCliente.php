<script nonce="<?= csp_nonce() ?>" src="<?php echo base_url() ?>assets/js/jquery.mask.min.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?php echo base_url() ?>assets/js/sweetalert2.all.min.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?php echo base_url() ?>assets/js/funcoes.js"></script>
<style>
    #imgSenha {
        width: 18px;
        cursor: pointer;
    }

    /* Hiding the checkbox, but allowing it to be focused */
    .badgebox {
        opacity: 0;
    }

    .badgebox+.badge {
        /* Move the check mark away when unchecked */
        text-indent: -999999px;
        /* Makes the badge's width stay the same checked and unchecked */
        width: 27px;
    }

    .badgebox:focus+.badge {
        /* Set something to make the badge looks focused */
        /* This really depends on the application, in my case it was: */
        /* Adding a light border */
        box-shadow: inset 0px 0px 5px;
        /* Taking the difference out of the padding */
    }

    .badgebox:checked+.badge {
        /* Move the check mark back when checked */
        text-indent: 0;
    }

    .control-group.error .form-text {
        display: flex;
    }

    .form-horizontal .control-group {
        border-bottom: 1px solid #ffffff;
    }

    .form-horizontal .controls {
        margin-left: 20px;
        padding-bottom: 8px 0;
    }

    .form-horizontal .control-label {
        text-align: left;
        padding-top: 15px;
    }

    .nopadding {
        padding: 0 20px !important;
        margin-right: 20px;
    }

    .widget-title h5 {
        padding-bottom: 30px;
        text-align-last: left;
        font-size: 2em;
        font-weight: 500;
    }

    @media (max-width: 480px) {
        form {
            display: contents !important;
        }

        .form-horizontal .control-label {
            margin-bottom: -6px;
        }

        .btn-xs {
            position: initial !important;
        }
    }
</style>
<div class="row" style="margin-top:0">
    <div class="col-md-12">
        <div class="widget-box">
            <div class="ui-cabecalho" style="padding: 16px 16px 0">
                <div>
                    <h1 class="ui-titulo"><i class="fas fa-user"></i> Novo cliente ou fornecedor</h1>
                    <p class="ui-subtitulo">Preencha os dados principais. Os campos marcados com * são obrigatórios.</p>
                </div>
            </div>
            <?php if ($custom_error != '') {
                echo '<div class="alert alert-danger">' . $custom_error . '</div>';
            } ?>
            <form action="<?php echo current_url(); ?>" id="formCliente" method="post" class="form-horizontal">
                <div class="widget-content nopadding tab-content">
                    <div class="col-md-6 row">
                        <div class="ui-secao">
                        <h4 class="ui-secao-titulo">Dados gerais</h4>
                        <div class="row mb-2">
                            <label for="documento" class="col-sm-3 col-form-label">CPF/CNPJ</label>
                            <div class="col-sm-9">
                                <input id="documento" class="cpfcnpj form-control" type="text" name="documento" value="<?php echo set_value('documento'); ?>" />
                                <button id="buscar_info_cnpj" class="btn btn-sm" type="button">Buscar(CNPJ)</button>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="ie" class="col-sm-3 col-form-label">Inscrição Estadual</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="ie" type="text" name="ie" value="<?php echo set_value('ie'); ?>" placeholder="ISENTO se não contribuinte" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="im" class="col-sm-3 col-form-label">Inscrição Municipal</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="im" type="text" name="im" value="<?php echo set_value('im'); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="nomeCliente" class="col-sm-3 col-form-label">Nome/Razão Social<span class="required">*</span></label>
                            <div class="col-sm-9">
                                <input class="form-control" id="nomeCliente" type="text" name="nomeCliente" value="<?php echo set_value('nomeCliente'); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="contato" class="col-sm-3 col-form-label">Contato:</label>
                            <div class="col-sm-9">
                                <input class="contato form-control" type="text" name="contato" value="<?php echo set_value('contato'); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="telefone" class="col-sm-3 col-form-label">Telefone</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="telefone" type="text" name="telefone" value="<?php echo set_value('telefone'); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="celular" class="col-sm-3 col-form-label">Celular</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="celular" type="text" name="celular" value="<?php echo set_value('celular'); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="whatsapp_notificacao" class="col-sm-3 col-form-label">Nº de notificação (WhatsApp)</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="whatsapp_notificacao" type="text" name="whatsapp_notificacao" value="<?php echo set_value('whatsapp_notificacao'); ?>" />
                                <span class="form-text">Onde este cliente recebe as notificações. Se vazio, usa o Celular.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="email" class="col-sm-3 col-form-label">Email</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="email" type="text" name="email" value="<?php echo set_value('email'); ?>" autocomplete="off" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="email_secundario" class="col-sm-3 col-form-label">Email secundário (cobrança/financeiro)</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="email_secundario" type="text" name="email_secundario" value="<?php echo set_value('email_secundario'); ?>" autocomplete="off" placeholder="Recebe cópia de cobranças e boletos da NF" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-3 col-form-label">Automação na aprovação</label>
                            <div class="col-sm-9">
                                <label for="automacao_aprovacao" style="display:flex; align-items:center; gap:8px;">
                                    <input class="form-check-input" id="automacao_aprovacao" type="checkbox" name="automacao_aprovacao" value="1" <?php echo set_value('automacao_aprovacao') ? 'checked' : ''; ?> />
                                    Ao aprovar a OS, emitir NFS-e e gerar boleto automaticamente
                                </label>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-3 col-form-label">Aprovação com código</label>
                            <div class="col-sm-9">
                                <label for="aprovacao_exige_token" style="display:flex; align-items:center; gap:8px;">
                                    <input class="form-check-input" id="aprovacao_exige_token" type="checkbox" name="aprovacao_exige_token" value="1" <?php echo set_value('aprovacao_exige_token') ? 'checked' : ''; ?> />
                                    Exigir código de verificação (WhatsApp/e-mail) antes de aprovar a OS
                                </label>
                                <span class="form-text">Vale para todas as OS deste cliente. Também pode ser ligado avulso em cada OS.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-3 col-form-label" for="aprovacao_token_numeros">Números para o código (WhatsApp)</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="aprovacao_token_numeros" name="aprovacao_token_numeros" rows="3" placeholder="Um número por linha, com DDD. Ex.:&#10;(92) 99215-0107&#10;(92) 98888-7777"><?php echo set_value('aprovacao_token_numeros'); ?></textarea>
                                <span class="form-text">Além do celular do cliente, o código também será enviado para estes números (um por linha).</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-3 col-form-label">Faturamento agendado</label>
                            <div class="col-sm-9">
                                <label for="faturamento_agendado" style="display:flex; align-items:center; gap:8px;">
                                    <input class="form-check-input" id="faturamento_agendado" type="checkbox" name="faturamento_agendado" value="1" <?php echo set_value('faturamento_agendado') ? 'checked' : ''; ?> />
                                    Segurar a emissão (NFS-e + boleto) até o dia de faturamento
                                </label>
                                <span class="form-text">Ao aprovar no meio do mês, a nota fica em espera e é emitida no dia configurado (ex.: dia 01).</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="tp_ret_issqn" class="col-sm-3 col-form-label">ISS na NFS-e</label>
                            <div class="col-sm-9">
                                <select class="form-select" id="tp_ret_issqn" name="tp_ret_issqn">
                                    <option value="" <?php echo set_value('tp_ret_issqn') === '' ? 'selected' : ''; ?>>— padrão (config fiscal) —</option>
                                    <option value="1" <?php echo set_value('tp_ret_issqn') === '1' ? 'selected' : ''; ?>>Não retido</option>
                                    <option value="2" <?php echo set_value('tp_ret_issqn') === '2' ? 'selected' : ''; ?>>Retido</option>
                                </select>
                                <span class="form-text">Usado na emissão da nota de serviço deste cliente.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="senha" class="col-sm-3 col-form-label">Senha</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="senha" type="password" name="senha" autocomplete="new-password" value="<?php echo set_value('senha'); ?>" />
                                <img id="imgSenha" src="<?php echo base_url() ?>assets/img/eye.svg" alt="">
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-3 col-form-label">Tipo de Cliente</label>
                            <div class="col-sm-9">
                                <label for="fornecedor" class="btn btn-secondary">Fornecedor
                                    <input type="checkbox" id="fornecedor" name="fornecedor" class="badgebox" value="1">
                                    <span class="badge text-bg-secondary">&check;</span>
                                </label>
                            </div>
                        </div>
                        <?php if ($this->db->field_exists('exige_chamado', 'clientes')) { ?>
                        <div class="row mb-2">
                            <label class="col-sm-3 col-form-label">Atividade n&atilde;o programada</label>
                            <div class="col-sm-9">
                                <label for="exige_chamado" class="btn btn-secondary">Exige N&ordm; do chamado
                                    <input type="checkbox" id="exige_chamado" name="exige_chamado" class="badgebox" value="1">
                                    <span class="badge text-bg-secondary">&check;</span>
                                </label>
                                <span class="form-text">O t&eacute;cnico s&oacute; abre atividade n&atilde;o programada informando o chamado do cliente.</span>
                            </div>
                        </div>
                        <?php } ?>
                        <div class="ui-secao">
                        <h4 class="ui-secao-titulo">Dados fiscais (opcional)</h4>
                        <div class="row mb-2">
                            <label for="ind_ie_dest" class="col-sm-3 col-form-label">Indicador de IE (NF-e)</label>
                            <div class="col-sm-9">
                                <select class="form-select" id="ind_ie_dest" name="ind_ie_dest">
                                    <option value="" <?php echo (string) (set_value('ind_ie_dest')) === '' ? 'selected' : ''; ?>>— deduzir da Inscrição Estadual (padrão) —</option>
                                    <option value="1" <?php echo (string) (set_value('ind_ie_dest')) === '1' ? 'selected' : ''; ?>>Contribuinte de ICMS (tem IE)</option>
                                    <option value="2" <?php echo (string) (set_value('ind_ie_dest')) === '2' ? 'selected' : ''; ?>>Isento de Inscrição Estadual</option>
                                    <option value="9" <?php echo (string) (set_value('ind_ie_dest')) === '9' ? 'selected' : ''; ?>>Não contribuinte</option>
                                </select>
                                <span class="form-text">Se não souber, deixe no padrão. Define como a nota identifica o destinatário.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="regime_tributario" class="col-sm-3 col-form-label">Regime tributário</label>
                            <div class="col-sm-9">
                                <select class="form-select" id="regime_tributario" name="regime_tributario">
                                    <option value="" <?php echo (string) (set_value('regime_tributario')) === '' ? 'selected' : ''; ?>>— não informado —</option>
                                    <option value="mei" <?php echo (string) (set_value('regime_tributario')) === 'mei' ? 'selected' : ''; ?>>MEI</option>
                                    <option value="simples" <?php echo (string) (set_value('regime_tributario')) === 'simples' ? 'selected' : ''; ?>>Simples Nacional</option>
                                    <option value="normal" <?php echo (string) (set_value('regime_tributario')) === 'normal' ? 'selected' : ''; ?>>Regime normal (Lucro Presumido/Real)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-3 col-form-label">Órgão público</label>
                            <div class="col-sm-9">
                                <label for="orgao_publico" style="display:flex; align-items:center; gap:8px;">
                                    <input class="form-check-input" id="orgao_publico" type="checkbox" name="orgao_publico" value="1" <?php echo set_value('orgao_publico') ? 'checked' : ''; ?> />
                                    Prefeitura, governo, autarquia ou outro órgão público
                                </label>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="inscricao_suframa" class="col-sm-3 col-form-label">Inscrição SUFRAMA</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="inscricao_suframa" type="text" name="inscricao_suframa" maxlength="9" inputmode="numeric" value="<?php echo htmlspecialchars((string) (set_value('inscricao_suframa')), ENT_QUOTES, 'UTF-8'); ?>" />
                                <span class="form-text">Só para quem tem benefício da Zona Franca de Manaus. São 9 números, ou deixe em branco.</span>
                            </div>
                        </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="ui-secao">
                        <h4 class="ui-secao-titulo">Endereço</h4>
                        <div class="row mb-2" class="form-label">
                            <label for="cep" class="col-sm-3 col-form-label">CEP</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="cep" type="text" name="cep" value="<?php echo set_value('cep'); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2" class="form-label">
                            <label for="rua" class="col-sm-3 col-form-label">Rua</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="rua" type="text" name="rua" value="<?php echo set_value('rua'); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="numero" class="col-sm-3 col-form-label">Número</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="numero" type="text" name="numero" value="<?php echo set_value('numero'); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="complemento" class="col-sm-3 col-form-label">Complemento</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="complemento" type="text" name="complemento" value="<?php echo set_value('complemento'); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2" class="form-label">
                            <label for="bairro" class="col-sm-3 col-form-label">Bairro</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="bairro" type="text" name="bairro" value="<?php echo set_value('bairro'); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2" class="form-label">
                            <label for="cidade" class="col-sm-3 col-form-label">Cidade</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="cidade" type="text" name="cidade" value="<?php echo set_value('cidade'); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2" class="form-label">
                            <label for="estado" class="col-sm-3 col-form-label">Estado</label>
                            <div class="col-sm-9">
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
                <div class="ui-rodape-form">
                    <button type="submit" class="button btn btn-sm btn-success"><span class="button__icon"><i class='bx bx-save'></i></span> <span class="button__text2">Salvar</span></button>
                    <a title="Voltar" class="button btn btn-warning" href="<?php echo site_url() ?>/clientes"><span class="button__icon"><i class="bx bx-undo"></i></span> <span class="button__text2">Voltar</span></a>
                </div>
            </form>
        </div>
    </div>
</div>
<script nonce="<?= csp_nonce() ?>" src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        let container = document.querySelector('div');
        let input = document.querySelector('#senha');
        let icon = document.querySelector('#imgSenha');

        icon.addEventListener('click', function() {
            container.classList.toggle('visible');
            if (container.classList.contains('visible')) {
                icon.src = '<?php echo base_url() ?>assets/img/eye-off.svg';
                input.type = 'text';
            } else {
                icon.src = '<?php echo base_url() ?>assets/img/eye.svg'
                input.type = 'password';
            }
        });

        $.getJSON('<?php echo base_url() ?>assets/json/estados.json', function(data) {
            for (i in data.estados) {
                $('#estado').append(new Option(data.estados[i].nome, data.estados[i].sigla));
            }
            var curState = '<?php echo set_value('estado'); ?>';
            if (curState) {
                $("#estado option[value=" + curState + "]").prop("selected", true);
            }
        });
        $("#nomeCliente").focus();
        $('#formCliente').validate({
            rules: {
                nomeCliente: {
                    required: true
                },
            },
            messages: {
                nomeCliente: {
                    required: 'Campo Requerido.'
                },
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
