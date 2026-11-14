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
                    <h1 class="ui-titulo"><i class="fas fa-user"></i> Editar cliente ou fornecedor</h1>
                    <p class="ui-subtitulo">Altere os dados do cadastro. Os campos marcados com * são obrigatórios.</p>
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
                                <input id="documento" class="cpfcnpj form-control" type="text" name="documento" value="<?php echo html_escape($result->documento); ?>" />
                                <button id="buscar_info_cnpj" class="btn btn-sm" type="button">Buscar(CNPJ)</button>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="ie" class="col-sm-3 col-form-label">Inscrição Estadual</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="ie" type="text" name="ie" value="<?php echo $result->ie ?? ''; ?>" placeholder="ISENTO se não contribuinte" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="im" class="col-sm-3 col-form-label">Inscrição Municipal</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="im" type="text" name="im" value="<?php echo $result->im ?? ''; ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <?php echo form_hidden('idClientes', $result->idClientes) ?>
                            <label for="nomeCliente" class="col-sm-3 col-form-label">Nome/Razão Social<span class="required">*</span></label>
                            <div class="col-sm-9">
                                <input class="form-control" id="nomeCliente" type="text" name="nomeCliente" value="<?php echo html_escape($result->nomeCliente); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="contato" class="col-sm-3 col-form-label">Contato:</label>
                            <div class="col-sm-9">
                                <input class="contato form-control" type="text" name="contato" value="<?php echo html_escape($result->contato); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="telefone" class="col-sm-3 col-form-label">Telefone</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="telefone" type="text" name="telefone" value="<?php echo html_escape($result->telefone); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="celular" class="col-sm-3 col-form-label">Celular</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="celular" type="text" name="celular" value="<?php echo html_escape($result->celular); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="whatsapp_notificacao" class="col-sm-3 col-form-label">Nº de notificação (WhatsApp)</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="whatsapp_notificacao" type="text" name="whatsapp_notificacao" value="<?php echo isset($result->whatsapp_notificacao) ? html_escape($result->whatsapp_notificacao) : ''; ?>" />
                                <span class="form-text">Onde este cliente recebe as notificações. Se vazio, usa o Celular.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="email" class="col-sm-3 col-form-label">Email</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="email" type="text" name="email" value="<?php echo html_escape($result->email); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="email_secundario" class="col-sm-3 col-form-label">Email secundário (cobrança/financeiro)</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="email_secundario" type="text" name="email_secundario" value="<?php echo isset($result->email_secundario) ? $result->email_secundario : ''; ?>" placeholder="Recebe cópia de cobranças e boletos da NF" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-3 col-form-label">Automação na aprovação</label>
                            <div class="col-sm-9">
                                <label for="automacao_aprovacao" style="display:flex; align-items:center; gap:8px;">
                                    <input class="form-check-input" id="automacao_aprovacao" type="checkbox" name="automacao_aprovacao" value="1" <?php echo (isset($result->automacao_aprovacao) && $result->automacao_aprovacao) ? 'checked' : ''; ?> />
                                    Ao aprovar a OS, emitir NFS-e e gerar boleto automaticamente
                                </label>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-3 col-form-label">Aprovação com código</label>
                            <div class="col-sm-9">
                                <label for="aprovacao_exige_token" style="display:flex; align-items:center; gap:8px;">
                                    <input class="form-check-input" id="aprovacao_exige_token" type="checkbox" name="aprovacao_exige_token" value="1" <?php echo (isset($result->aprovacao_exige_token) && $result->aprovacao_exige_token) ? 'checked' : ''; ?> />
                                    Exigir código de verificação (WhatsApp/e-mail) antes de aprovar a OS
                                </label>
                                <span class="form-text">Vale para todas as OS deste cliente. Também pode ser ligado avulso em cada OS.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-3 col-form-label" for="aprovacao_token_numeros">Números para o código (WhatsApp)</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="aprovacao_token_numeros" name="aprovacao_token_numeros" rows="3" placeholder="Um número por linha, com DDD. Ex.:&#10;(92) 99215-0107&#10;(92) 98888-7777"><?php echo isset($result->aprovacao_token_numeros) ? set_value('aprovacao_token_numeros', $result->aprovacao_token_numeros) : ''; ?></textarea>
                                <span class="form-text">Além do celular do cliente, o código também será enviado para estes números (um por linha).</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-3 col-form-label">Faturamento agendado</label>
                            <div class="col-sm-9">
                                <label for="faturamento_agendado" style="display:flex; align-items:center; gap:8px;">
                                    <input class="form-check-input" id="faturamento_agendado" type="checkbox" name="faturamento_agendado" value="1" <?php echo (isset($result->faturamento_agendado) && $result->faturamento_agendado) ? 'checked' : ''; ?> />
                                    Segurar a emissão (NFS-e + boleto) até o dia de faturamento
                                </label>
                                <span class="form-text">Ao aprovar no meio do mês, a nota fica em espera e é emitida no dia configurado (ex.: dia 01).</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="tp_ret_issqn" class="col-sm-3 col-form-label">ISS na NFS-e</label>
                            <div class="col-sm-9">
                                <?php $issCliente = isset($result->tp_ret_issqn) ? (string) $result->tp_ret_issqn : ''; ?>
                                <select class="form-select" id="tp_ret_issqn" name="tp_ret_issqn">
                                    <option value="" <?php echo $issCliente === '' ? 'selected' : ''; ?>>— padrão (config fiscal) —</option>
                                    <option value="1" <?php echo $issCliente === '1' ? 'selected' : ''; ?>>Não retido</option>
                                    <option value="2" <?php echo $issCliente === '2' ? 'selected' : ''; ?>>Retido</option>
                                </select>
                                <span class="form-text">Usado na emissão da nota de serviço deste cliente.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="senha" class="col-sm-3 col-form-label">Senha</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="senha" type="password" name="senha" value="" placeholder="Não preencha se não quiser alterar." />
                                <img id="imgSenha" src="<?php echo base_url() ?>assets/img/eye.svg" alt="">
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-3 col-form-label">Tipo de Cliente</label>
                            <div class="col-sm-9">
                                <label for="fornecedor" class="btn btn-secondary">Fornecedor
                                    <input type="checkbox" id="fornecedor" name="fornecedor" class="badgebox" value="1" <?= ($result->fornecedor == 1) ? 'checked' : '' ?>>
                                    <span class="badge text-bg-secondary">&check;</span>
                                </label>
                            </div>
                        </div>
                        <?php if ($this->db->field_exists('exige_chamado', 'clientes')) { ?>
                        <div class="row mb-2">
                            <label class="col-sm-3 col-form-label">Atividade n&atilde;o programada</label>
                            <div class="col-sm-9">
                                <label for="exige_chamado" class="btn btn-secondary">Exige N&ordm; do chamado
                                    <input type="checkbox" id="exige_chamado" name="exige_chamado" class="badgebox" value="1" <?= !empty($result->exige_chamado) ? 'checked' : '' ?>>
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
                                    <option value="" <?php echo (string) ((isset($result->ind_ie_dest) ? (string) $result->ind_ie_dest : '')) === '' ? 'selected' : ''; ?>>— deduzir da Inscrição Estadual (padrão) —</option>
                                    <option value="1" <?php echo (string) ((isset($result->ind_ie_dest) ? (string) $result->ind_ie_dest : '')) === '1' ? 'selected' : ''; ?>>Contribuinte de ICMS (tem IE)</option>
                                    <option value="2" <?php echo (string) ((isset($result->ind_ie_dest) ? (string) $result->ind_ie_dest : '')) === '2' ? 'selected' : ''; ?>>Isento de Inscrição Estadual</option>
                                    <option value="9" <?php echo (string) ((isset($result->ind_ie_dest) ? (string) $result->ind_ie_dest : '')) === '9' ? 'selected' : ''; ?>>Não contribuinte</option>
                                </select>
                                <span class="form-text">Se não souber, deixe no padrão. Define como a nota identifica o destinatário.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="regime_tributario" class="col-sm-3 col-form-label">Regime tributário</label>
                            <div class="col-sm-9">
                                <select class="form-select" id="regime_tributario" name="regime_tributario">
                                    <option value="" <?php echo (string) ((isset($result->regime_tributario) ? (string) $result->regime_tributario : '')) === '' ? 'selected' : ''; ?>>— não informado —</option>
                                    <option value="mei" <?php echo (string) ((isset($result->regime_tributario) ? (string) $result->regime_tributario : '')) === 'mei' ? 'selected' : ''; ?>>MEI</option>
                                    <option value="simples" <?php echo (string) ((isset($result->regime_tributario) ? (string) $result->regime_tributario : '')) === 'simples' ? 'selected' : ''; ?>>Simples Nacional</option>
                                    <option value="normal" <?php echo (string) ((isset($result->regime_tributario) ? (string) $result->regime_tributario : '')) === 'normal' ? 'selected' : ''; ?>>Regime normal (Lucro Presumido/Real)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-3 col-form-label">Órgão público</label>
                            <div class="col-sm-9">
                                <label for="orgao_publico" style="display:flex; align-items:center; gap:8px;">
                                    <input class="form-check-input" id="orgao_publico" type="checkbox" name="orgao_publico" value="1" <?php echo (!empty($result->orgao_publico)) ? 'checked' : ''; ?> />
                                    Prefeitura, governo, autarquia ou outro órgão público
                                </label>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="inscricao_suframa" class="col-sm-3 col-form-label">Inscrição SUFRAMA</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="inscricao_suframa" type="text" name="inscricao_suframa" maxlength="9" inputmode="numeric" value="<?php echo htmlspecialchars((string) ((isset($result->inscricao_suframa) ? (string) $result->inscricao_suframa : '')), ENT_QUOTES, 'UTF-8'); ?>" />
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
                                <input class="form-control" id="cep" type="text" name="cep" value="<?php echo html_escape($result->cep); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2" class="form-label">
                            <label for="rua" class="col-sm-3 col-form-label">Rua</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="rua" type="text" name="rua" value="<?php echo html_escape($result->rua); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="numero" class="col-sm-3 col-form-label">Número</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="numero" type="text" name="numero" value="<?php echo html_escape($result->numero); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="complemento" class="col-sm-3 col-form-label">Complemento</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="complemento" type="text" name="complemento" value="<?php echo html_escape($result->complemento); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2" class="form-label">
                            <label for="bairro" class="col-sm-3 col-form-label">Bairro</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="bairro" type="text" name="bairro" value="<?php echo html_escape($result->bairro); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2" class="form-label">
                            <label for="cidade" class="col-sm-3 col-form-label">Cidade</label>
                            <div class="col-sm-9">
                                <input class="form-control" id="cidade" type="text" name="cidade" value="<?php echo html_escape($result->cidade); ?>" />
                            </div>
                        </div>
                        <div class="row mb-2" class="form-label">
                            <label for="estado" class="col-sm-3 col-form-label">Estado</label>
                            <div class="col-sm-9">
                                <select id="estado" name="estado" class="form-select">
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>

                <?php if (! empty($vinculosSuportado)) { ?>
                    <div class="row mb-2">
                        <label class="col-sm-3 col-form-label">Acesso multi-CNPJ no portal</label>
                        <div class="col-sm-9">
                            <input type="text" id="buscaCnpjVinculo" class="w-100 form-control" placeholder="Buscar por nome ou CNPJ/CPF..." autocomplete="off" style="margin-bottom:6px">
                            <div style="margin-bottom:4px">
                                <a href="#" id="marcarTodosVinculo" style="font-size:12px">Marcar visíveis</a> &nbsp;|&nbsp;
                                <a href="#" id="desmarcarTodosVinculo" style="font-size:12px">Desmarcar visíveis</a>
                                <span id="contadorVinculo" style="float:right;font-size:12px;color:#888"></span>
                            </div>
                            <div id="listaCnpjVinculo" style="max-height:230px;overflow-y:auto;border:1px solid #ddd;border-radius:4px;padding:6px">
                                <?php foreach ($clientesDisponiveis as $c) {
                                    $checked = in_array((int) $c->idClientes, array_map('intval', (array) $vinculosAtuais)) ? 'checked' : '';
                                    $doc = trim((string) $c->documento);
                                    $texto = htmlspecialchars($c->nomeCliente) . ($doc !== '' ? ' — ' . htmlspecialchars($doc) : '');
                                    ?>
                                    <label class="cnpj-vinculo-item" style="display:block;padding:4px 4px;cursor:pointer;border-bottom:1px solid #f2f2f2" data-busca="<?= htmlspecialchars(strtolower($c->nomeCliente . ' ' . $doc)) ?>">
                                        <input class="form-check-input" type="checkbox" name="clientes_vinculados[]" value="<?= $c->idClientes ?>" <?= $checked ?>> <?= $texto ?>
                                    </label>
                                <?php } ?>
                            </div>
                            <span class="form-text">Marque os clientes/CNPJs cujos dados (OS, cobranças, notas, aprovações) este login também verá na Área do Cliente. Use a busca para filtrar.</span>
                        </div>
                    </div>

                    <script nonce="<?= csp_nonce() ?>">
                        (function () {
                            var busca = document.getElementById('buscaCnpjVinculo');
                            var lista = document.getElementById('listaCnpjVinculo');
                            var contador = document.getElementById('contadorVinculo');
                            if (!busca || !lista) return;
                            var itens = lista.querySelectorAll('.cnpj-vinculo-item');

                            function atualizarContador() {
                                var n = lista.querySelectorAll('input[type=checkbox]:checked').length;
                                contador.textContent = n + ' selecionado(s)';
                            }
                            function visiveis() {
                                return Array.prototype.filter.call(itens, function (el) { return el.style.display !== 'none'; });
                            }
                            busca.addEventListener('input', function () {
                                var q = this.value.toLowerCase().trim();
                                itens.forEach(function (el) {
                                    el.style.display = (q === '' || el.getAttribute('data-busca').indexOf(q) !== -1) ? 'block' : 'none';
                                });
                            });
                            lista.addEventListener('change', atualizarContador);
                            document.getElementById('marcarTodosVinculo').addEventListener('click', function (e) {
                                e.preventDefault();
                                visiveis().forEach(function (el) { el.querySelector('input').checked = true; });
                                atualizarContador();
                            });
                            document.getElementById('desmarcarTodosVinculo').addEventListener('click', function (e) {
                                e.preventDefault();
                                visiveis().forEach(function (el) { el.querySelector('input').checked = false; });
                                atualizarContador();
                            });
                            atualizarContador();
                        })();
                    </script>
                <?php } ?>

                <div class="ui-rodape-form">
                    <button type="submit" class="button btn btn-primary">
                        <span class="button__icon"><i class="bx bx-sync"></i></span><span class="button__text2">Atualizar</span></button>
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
            var curState = <?php echo json_encode((string) $result->estado); ?>;
            if (curState) {
                $("#estado option[value=" + curState + "]").prop("selected", true);
            }

        });
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
