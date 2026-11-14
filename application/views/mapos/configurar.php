<div class="row" style="margin-top:0">
    <div class="col-md-12">
        <div class="ui-cabecalho">
            <div>
                <h1 class="ui-titulo"><i class="bx bx-wrench"></i> Configurações do Sistema</h1>
                <p class="ui-subtitulo">Escolha uma aba para ajustar esta parte do sistema. Salvar grava as abas desta tela; notificações, e-mail e WhatsApp ficam na Central de Notificações (menu Configurações).</p>
            </div>
        </div>
        <div class="widget-box">
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#home">Gerais</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#menu1">Financeiro</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#menu2">Produtos</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#menu4">Atualizações</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#menu5">OS</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#menu6">API</a></li>
            </ul>
            <form action="<?php echo current_url(); ?>" id="formConfigurar" method="post" class="form-horizontal">
                <div class="widget-content nopadding tab-content">
                    <?php echo $custom_error; ?>
                    <!-- Menu Gerais -->
                    <div id="home" class="tab-pane fade show active">
                        <div class="ui-dica"><i class="bx bx-info-circle"></i><div>Dados gerais: nome do sistema, tema, quantidade de linhas nas listas e tabelas dinâmicas.</div></div>
                        <div class="row mb-2">
                            <label for="app_name" class="col-sm-3 col-form-label">Nome do Sistema</label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" required name="app_name" value="<?= $configuration['app_name'] ?>">
                                <span class="form-text">Nome do sistema</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="app_theme" class="col-sm-3 col-form-label">Tema do Sistema</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="app_theme" id="app_theme">
                                    <option value="default">Escuro</option>
                                    <option value="white" <?= $configuration['app_theme'] == 'white' ? 'selected' : ''; ?>>Claro</option>
                                    <option value="puredark" <?= $configuration['app_theme'] == 'puredark' ? 'selected' : ''; ?>>Pure dark</option>
                                    <option value="darkorange" <?= $configuration['app_theme'] == 'darkorange' ? 'selected' : ''; ?>>Dark orange</option>
                                    <option value="darkviolet" <?= $configuration['app_theme'] == 'darkviolet' ? 'selected' : ''; ?>>Dark violet</option>
                                    <option value="whitegreen" <?= $configuration['app_theme'] == 'whitegreen' ? 'selected' : ''; ?>>White green</option>
                                    <option value="whiteblack" <?= $configuration['app_theme'] == 'whiteblack' ? 'selected' : ''; ?>>White black</option>
                                </select>
                                <span class="form-text">Selecione o tema que que deseja usar no sistema</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="per_page" class="col-sm-3 col-form-label">Registros por Página</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="per_page" id="theme">
                                    <option value="10">10</option>
                                    <option value="20" <?= $configuration['per_page'] == '20' ? 'selected' : ''; ?>>20</option>
                                    <option value="50" <?= $configuration['per_page'] == '50' ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?= $configuration['per_page'] == '100' ? 'selected' : ''; ?>>100</option>
                                </select>
                                <span class="form-text">Selecione quantos registros deseja exibir nas listas</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="control_datatable" class="col-sm-3 col-form-label">Visualização em DataTables</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="control_datatable" id="control_datatable">
                                    <option value="1">Sim</option>
                                    <option value="0" <?= $configuration['control_datatable'] == '0' ? 'selected' : ''; ?>>Não</option>
                                </select>
                                <span class="form-text">Ativar ou desativar a visualização em tabelas dinâmicas</span>
                            </div>
                        </div>
                        <div class="ui-rodape-form">
                            <div style="display:contents">
                                <div style="display:contents">
                                    <button type="submit" class="button btn btn-primary">
                                    <span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2">Salvar Alterações</span></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Menu Financeiro -->
                    <div id="menu1" class="tab-pane fade">
                        <div class="ui-dica"><i class="bx bx-info-circle"></i><div>Regras financeiras (baixa retroativa, alterações de OS e vendas faturadas) e as chaves de cobrança: Pix, EFI, Mercado Pago e Asaas.</div></div>
                        <div class="row mb-2">
                            <label for="control_baixa" class="col-sm-3 col-form-label">Controle de baixa retroativa</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="control_baixa" id="control_baixa">
                                    <option value="1">Ativar</option>
                                    <option value="0" <?= $configuration['control_baixa'] == '0' ? 'selected' : ''; ?>>Desativar</option>
                                </select>
                                <span class="form-text">Ativar ou desativar o controle de baixa financeira, com data retroativa.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="control_editos" class="col-sm-3 col-form-label">Controle de edição de OS</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="control_editos" id="control_editos">
                                    <option value="1" <?= $configuration['control_editos'] == '0' ? 'selected' : ''; ?>>Ativar</option>
                                    <option value="0" <?= $configuration['control_editos'] == '0' ? 'selected' : ''; ?>>Desativar</option>
                                </select>
                                <span class="form-text">Ativar ou desativar a permissão para alterar ou excluir OS faturada e/ou cancelada.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="control_edit_vendas" class="col-sm-3 col-form-label">Controle de edição de Vendas</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="control_edit_vendas" id="control_edit_vendas">
                                    <option value="1" <?= $configuration['control_edit_vendas'] == '0' ? 'selected' : ''; ?>>Ativar</option>
                                    <option value="0" <?= $configuration['control_edit_vendas'] == '0' ? 'selected' : ''; ?>>Desativar</option>
                                </select>
                                <span class="form-text">Ativar ou desativar a permissão para alterar ou excluir vendas faturada.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="pix_key" class="col-sm-3 col-form-label">Chave Pix para Recebimento de Pagamentos</label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="pix_key" value="<?= $configuration['pix_key'] ?>">
                                <span class="form-text">Chave Pix para Recebimento de Pagamentos</span>
                            </div>
                        </div>

                        <!-- Configrações do EFI -->
                        <hr>
                        <h5 style="margin-left:10px;">Configrações do EFI (antiga GerenciaNet)</h5>
                        <div class="row mb-2">
                            <label for="EFI_PRODUCTION" class="col-sm-3 col-form-label">Ambiente</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="PAYMENT_GATEWAYS_EFI_PRODUCTION" id="EFI_PRODUCTION">
                                    <option value="false" <?= !filter_var($_ENV['PAYMENT_GATEWAYS_EFI_PRODUCTION'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'selected' : ''; ?>>Sandbox</option>
                                    <option value="true" <?= filter_var($_ENV['PAYMENT_GATEWAYS_EFI_PRODUCTION'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'selected' : ''; ?>>Produção</option>
                                </select>
                                <span class="form-text">Sandbox é um ambiente para testes.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="EFI_CREDENTIAIS_CLIENT_ID" class="col-sm-3 col-form-label">CLIENT_ID</label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="PAYMENT_GATEWAYS_EFI_CREDENTIAIS_CLIENT_ID" value="<?= $_ENV['PAYMENT_GATEWAYS_EFI_CREDENTIAIS_CLIENT_ID'] ?>" id="EFI_CREDENTIAIS_CLIENT_ID">
                                <span class="form-text">Pode ser encontrado no menu <a href="https://app.sejaefi.com.br/api/aplicacoes" target="_blank" rel="noopener noreferrer">"API" -> "Aplicações"</a></span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="EFI_CREDENTIAIS_CLIENT_SECRET" class="col-sm-3 col-form-label">CLIENT_SECRET</label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="PAYMENT_GATEWAYS_EFI_CREDENTIAIS_CLIENT_SECRET" value="<?= $_ENV['PAYMENT_GATEWAYS_EFI_CREDENTIAIS_CLIENT_SECRET'] ?>" id="EFI_CREDENTIAIS_CLIENT_SECRET">
                                <span class="form-text">Pode ser encontrado no menu <a href="https://app.sejaefi.com.br/api/aplicacoes" target="_blank" rel="noopener noreferrer">"API" -> "Aplicações"</a></span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="EFI_BOLETO_EXPIRATION" class="col-sm-3 col-form-label">Dias para vencimento do boleto</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="PAYMENT_GATEWAYS_EFI_BOLETO_EXPIRATION" id="EFI_BOLETO_EXPIRATION">
                                    <?php for ($i = 1; $i <= 30; $i++) :
                                        $diasEFI = "P{$i}D";
                                    ?>
                                        <option value="<?= $diasEFI ?>" <?= $diasEFI == $_ENV['PAYMENT_GATEWAYS_EFI_BOLETO_EXPIRATION'] ? 'selected' : '' ?>><?= $i ?> dia<?= $i > 1 ? 's' : '' ?></option>
                                    <?php endfor; ?>
                                </select>
                                <span class="form-text">A quantidade de dias selecionado será somado a data que a cobrança for gerada.</span>
                            </div>
                        </div>

                        <!-- Configrações do Mercado Pago -->
                        <hr>
                        <h5 style="margin-left:10px;">Configrações do Mercado Pago</h5>
                        <div class="row mb-2">
                            <label for="MERCADO_PAGO_CREDENTIALS_PUBLIC_KEY" class="col-sm-3 col-form-label">PUBLIC_KEY</label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_PUBLIC_KEY" value="<?= $_ENV['PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_PUBLIC_KEY'] ?>" id="MERCADO_PAGO_CREDENTIALS_PUBLIC_KEY">
                                <span class="form-text">Pode ser encontrado no menu <a href="https://www.mercadopago.com.br/settings/account/credentials" target="_blank" rel="noopener noreferrer">"Seu Negócio" -> "Configurações" -> "Credenciais"</a></span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="MERCADO_PAGO_CREDENTIALS_ACCESS_TOKEN" class="col-sm-3 col-form-label">ACCESS_TOKEN</label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_ACCESS_TOKEN" value="<?= $_ENV['PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_ACCESS_TOKEN'] ?>" id="MERCADO_PAGO_CREDENTIALS_ACCESS_TOKEN">
                                <span class="form-text">Pode ser encontrado no menu <a href="https://www.mercadopago.com.br/settings/account/credentials" target="_blank" rel="noopener noreferrer">"Seu Negócio" -> "Configurações" -> "Credenciais"</a></span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="MERCADO_PAGO_CREDENTIALS_CLIENT_ID" class="col-sm-3 col-form-label">CLIENT_ID</label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CLIENT_ID" value="<?= $_ENV['PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CLIENT_ID'] ?>" id="MERCADO_PAGO_CREDENTIALS_CLIENT_ID">
                                <span class="form-text">Pode ser encontrado no menu <a href="https://www.mercadopago.com.br/settings/account/credentials" target="_blank" rel="noopener noreferrer">"Seu Negócio" -> "Configurações" -> "Credenciais"</a></span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="MERCADO_PAGO_CREDENTIALS_CLIENT_SECRET" class="col-sm-3 col-form-label">CLIENT_SECRET</label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CLIENT_SECRET" value="<?= $_ENV['PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CLIENT_SECRET'] ?>" id="MERCADO_PAGO_CREDENTIALS_CLIENT_SECRET">
                                <span class="form-text">Pode ser encontrado no menu <a href="https://www.mercadopago.com.br/settings/account/credentials" target="_blank" rel="noopener noreferrer">"Seu Negócio" -> "Configurações" -> "Credenciais"</a></span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="MERCADO_PAGO_BOLETO_EXPIRATION" class="col-sm-3 col-form-label">Dias para vencimento do boleto</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="PAYMENT_GATEWAYS_MERCADO_PAGO_BOLETO_EXPIRATION" id="MERCADO_PAGO_BOLETO_EXPIRATION">
                                    <?php for ($i = 1; $i <= 30; $i++) :
                                        $diasMP = "P{$i}D";
                                    ?>
                                        <option value="<?= $diasMP ?>" <?= $diasMP == $_ENV['PAYMENT_GATEWAYS_MERCADO_PAGO_BOLETO_EXPIRATION'] ? 'selected' : '' ?>><?= $i ?> dia<?= $i > 1 ? 's' : '' ?></option>
                                    <?php endfor; ?>
                                </select>
                                <span class="form-text">A quantidade de dias selecionado será somado a data que a cobrança for gerada.</span>
                            </div>
                        </div>

                        <!-- Configrações do ASAAS -->
                        <hr>
                        <h5 style="margin-left:10px;">Configrações do ASAAS</h5>
                        <div class="row mb-2">
                            <label for="ASAAS_PRODUCTION" class="col-sm-3 col-form-label">Ambiente</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="PAYMENT_GATEWAYS_ASAAS_PRODUCTION" id="ASAAS_PRODUCTION">
                                    <option value="false" <?= !filter_var($_ENV['PAYMENT_GATEWAYS_ASAAS_PRODUCTION'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'selected' : ''; ?>>Sandbox</option>
                                    <option value="true" <?= filter_var($_ENV['PAYMENT_GATEWAYS_ASAAS_PRODUCTION'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'selected' : ''; ?>>Produção</option>
                                </select>
                                <span class="form-text">Sandbox é um ambiente para testes.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="ASAAS_NOTIFY" class="col-sm-3 col-form-label">Notify</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="PAYMENT_GATEWAYS_ASAAS_NOTIFY" id="ASAAS_NOTIFY">
                                    <option value="false" <?= !filter_var($_ENV['PAYMENT_GATEWAYS_ASAAS_NOTIFY'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'selected' : ''; ?>>Desativado</option>
                                    <option value="true" <?= filter_var($_ENV['PAYMENT_GATEWAYS_ASAAS_NOTIFY'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'selected' : ''; ?>>Ativado</option>
                                </select>
                                <span class="form-text">Ativar ou desativar o Notify do Asaas.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="ASAAS_CREDENTIAIS_API_KEY" class="col-sm-3 col-form-label">API_KEY</label>
                            <div class="col-sm-9">
                                <input class="form-control" type="text" name="PAYMENT_GATEWAYS_ASAAS_CREDENTIAIS_API_KEY" value="<?= $_ENV['PAYMENT_GATEWAYS_ASAAS_CREDENTIAIS_API_KEY'] ?>" id="ASAAS_CREDENTIAIS_API_KEY">
                                <span class="form-text">Pode ser encontrado no menu "Minha Conta", clique em "Integração" e depois em "Gerar API Key"</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="ASAAS_BOLETO_EXPIRATION" class="col-sm-3 col-form-label">Dias para vencimento do boleto</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="PAYMENT_GATEWAYS_ASAAS_BOLETO_EXPIRATION" id="ASAAS_BOLETO_EXPIRATION">
                                    <?php for ($i = 1; $i <= 30; $i++) :
                                        $diasASAAS = "P{$i}D";
                                    ?>
                                        <option value="<?= $diasASAAS ?>" <?= $diasASAAS == $_ENV['PAYMENT_GATEWAYS_ASAAS_BOLETO_EXPIRATION'] ? 'selected' : '' ?>><?= $i ?> dia<?= $i > 1 ? 's' : '' ?></option>
                                    <?php endfor; ?>
                                </select>
                                <span class="form-text">A quantidade de dias selecionado será somado a data que a cobrança for gerada.</span>
                            </div>
                        </div>

                        <div class="ui-rodape-form">
                            <div style="display:contents">
                                <div style="display:contents">
                                  <button type="submit" class="button btn btn-primary">
                                  <span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2">Salvar Alterações</span></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Menu Produtos -->
                    <div id="menu2" class="tab-pane fade">
                        <div class="ui-dica"><i class="bx bx-info-circle"></i><div>Controle de estoque dos produtos.</div></div>
                        <div class="row mb-2">
                            <label for="control_estoque" class="col-sm-3 col-form-label">Controlar Estoque</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="control_estoque" id="control_estoque">
                                    <option value="1">Ativar</option>
                                    <option value="0" <?= $configuration['control_estoque'] == '0' ? 'selected' : ''; ?>>Desativar</option>
                                </select>
                                <span class="form-text">Ativar ou desativar o controle de estoque.</span>
                            </div>
                        </div>
                        <div class="ui-rodape-form">
                            <div style="display:contents">
                                <div style="display:contents">
                                  <button type="submit" class="button btn btn-primary">
                                  <span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2">Salvar Alterações</span></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Menu Atualização -->
                    <div id="menu4" class="tab-pane fade">
                        <div class="ui-dica"><i class="bx bx-info-circle"></i><div>Atualize o sistema ou o banco de dados. Faça um backup antes de qualquer atualização.</div></div>
                        <div class="ui-rodape-form">
                            <div style="display:contents">
                                <div style="display:contents">
                                    <button href="#modal-confirmabanco" data-bs-toggle="modal" type="button" class="button btn btn-warning">
                                      <span class="button__icon"><i class="bx bx-sync"></i></span><span class="button__text2">Banco de Dados</span></button>
                                    <button href="#modal-confirmaratualiza" data-bs-toggle="modal" type="button" class="button btn btn-danger">
                                      <span class="button__icon"><i class="bx bx-sync"></i></span><span class="button__text2">Atualizar Mapos</span></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Menu OS -->
                    <div id="menu5" class="tab-pane fade">
                        <div class="ui-dica"><i class="bx bx-info-circle"></i><div>Impressão das OS, check-in/check-out com GPS e anexos.</div></div>
                        <div class="row mb-2">
                            <div class="col-md-8" style="margin-left: 3em;">
                                <label for="control_2vias" class="form-label">Controle de Impressão em 2 Vias</label>
                                <div class="">
                                    <select class="form-select" name="control_2vias" id="control_2vias">
                                        <option value="1">Ativar</option>
                                        <option value="0" <?= $configuration['control_2vias'] == '0' ? 'selected' : ''; ?>>Desativar</option>
                                    </select>
                                    <span class="form-text">Ativar ou desativar impressão de OS em 2 vias.</span>
                                </div>
                            </div>
                            <div class="col-md-8" style="margin-left: 3em; margin-top: 1em;">
                                <label for="os_geofence_modo" class="form-label"><i class="bx bx-map-pin"></i> Geofence do Atendimento (fechar OS no local)</label>
                                <div class="">
                                    <?php $geoModo = $configuration['os_geofence_modo'] ?? 'off'; ?>
                                    <select class="form-select" name="os_geofence_modo" id="os_geofence_modo">
                                        <option value="off" <?= $geoModo == 'off' ? 'selected' : ''; ?>>Desativado — não valida localização</option>
                                        <option value="soft" <?= $geoModo == 'soft' ? 'selected' : ''; ?>>Aviso (soft) — deixa fechar, mas marca "fora da área"</option>
                                        <option value="hard" <?= $geoModo == 'hard' ? 'selected' : ''; ?>>Bloqueio (hard) — só fecha dentro da área</option>
                                    </select>
                                    <span class="form-text">No fechamento do atendimento (check-out), compara o GPS com o local da OS. Comece em "Aviso" e troque para "Bloqueio" depois da adaptação.</span>
                                    <div style="margin-top: .8em;">
                                        <label for="os_geofence_raio_metros" class="form-label">Raio da área (metros)</label>
                                        <input class="form-control" type="number" min="20" max="5000" step="10" name="os_geofence_raio_metros" id="os_geofence_raio_metros" value="<?= (int) ($configuration['os_geofence_raio_metros'] ?? 200) ?>">
                                        <span class="form-text">Distância máxima do local da OS considerada "dentro da área".</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8" style="margin-left: 3em; margin-top: 1em;">
                                <p class="mb-1"> Defina a vizualização padrão, onde o que ficar checado será exibida na listagem de OS por padrão. </p>
                                <?php $osStatusList = json_decode($configuration['os_status_list'] ?? '', true) ?: []; ?>
                                <div>
                                    <label class="form-check form-check-inline"> <input <?= in_array("Aberto", $osStatusList, true) ? 'checked' : ''; ?> name="os_status_list[]" class="marcar form-check-input" type="checkbox" value="Aberto"> <span class="lbl form-check-label"> Aberto</span> </label>
                                    <label class="form-check form-check-inline"> <input <?= in_array("Orçamento", $osStatusList, true) ? 'checked' : ''; ?> name="os_status_list[]" class="marcar form-check-input" type="checkbox" value="Orçamento"> <span class="lbl form-check-label"> Orçamento</span> </label>
                                    <label class="form-check form-check-inline"> <input <?= in_array("Negociação", $osStatusList, true) ? 'checked' : ''; ?> name="os_status_list[]" class="marcar form-check-input" type="checkbox" value="Negociação"> <span class="lbl form-check-label"> Negociação</span> </label>
                                    <label class="form-check form-check-inline"> <input <?= in_array("Aprovado", $osStatusList, true) ? 'checked' : ''; ?> name="os_status_list[]" class="marcar form-check-input" type="checkbox" value="Aprovado"> <span class="lbl form-check-label"> Aprovado </span> </label>
                                    <label class="form-check form-check-inline"> <input <?= in_array("Aguardando Peças", $osStatusList, true) ? 'checked' : ''; ?> name="os_status_list[]" class="marcar form-check-input" type="checkbox" value="Aguardando Peças"> <span class="lbl form-check-label"> Aguardando Peças </span> </label>
                                    <label class="form-check form-check-inline"> <input <?= in_array("Em Andamento", $osStatusList, true) ? 'checked' : ''; ?> name="os_status_list[]" class="marcar form-check-input" type="checkbox" value="Em Andamento"> <span class="lbl form-check-label"> Em Andamento</span> </label>
                                    <label class="form-check form-check-inline"> <input <?= in_array("Finalizado", $osStatusList, true) ? 'checked' : ''; ?> name="os_status_list[]" class="marcar form-check-input" type="checkbox" value="Finalizado"> <span class="lbl form-check-label"> Finalizado</span> </label>
                                    <label class="form-check form-check-inline"> <input <?= in_array("Faturado", $osStatusList, true) ? 'checked' : ''; ?> name="os_status_list[]" class="marcar form-check-input" type="checkbox" value="Faturado"> <span class="lbl form-check-label"> Faturado</span> </label>
                                    <label class="form-check form-check-inline"> <input <?= in_array("Cancelado", $osStatusList, true) ? 'checked' : ''; ?> name="os_status_list[]" class="marcar form-check-input" type="checkbox" value="Cancelado"> <span class="lbl form-check-label"> Cancelado</span> </label>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <label for="imprmirAnexos" class="form-label">Imprimir Anexos na A4?</label>
                                <div class="">
                                    <select class="form-select" name="imprmirAnexos" id="imprmirAnexos">
                                        <option value="true">Sim</option>
                                        <option value="false" <?= !filter_var($_ENV['IMPRIMIR_ANEXOS'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'selected' : ''; ?>>Não</option>
                                    </select>
                                    <span class="form-text">Gostaria de imprimir os Anexos na impressão A4?</span>
                                </div>
                            </div>
                        </div>
                        <div class="ui-rodape-form">
                            <div style="display:contents">
                                <div style="display:contents">
                                  <button type="submit" class="button btn btn-primary">
                                  <span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2">Salvar Alterações</span></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Menu API -->
                    <div id="menu6" class="tab-pane fade">
                        <div class="ui-dica"><i class="bx bx-info-circle"></i><div>Acesso externo à API do sistema, com tempo de sessão e token JWT.</div></div>
                        <div class="row mb-2">
                            <label for="apiEnabled" class="col-sm-3 col-form-label">Ativar acesso à API</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="apiEnabled" id="apiEnabled">
                                    <option value="true">Ativar</option>
                                    <option value="false" <?= !filter_var($_ENV['API_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'selected' : ''; ?>>Desativar</option>
                                </select>
                                <span class="form-text">Ativar ou desativar acesso à API.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="apiEnabled" class="col-sm-3 col-form-label">URL API</label>
                            <div class="col-sm-9">
                                <span class="form-control-plaintext" id="urlApi"><?= trim($_ENV['APP_BASEURL'], '/') . '/' ?>index.php/api/v1</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="apiExpireTime" class="col-sm-3 col-form-label">Tempo de expiração</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="apiExpireTime" id="apiExpireTime">
                                    <option value="60" <?= $_ENV['API_TOKEN_EXPIRE_TIME'] == 60 ? 'selected' : '' ?>>1 minuto</option>
                                    <option value="3600" <?= $_ENV['API_TOKEN_EXPIRE_TIME'] == 3600 ? 'selected' : '' ?>>1 hora</option>
                                    <option value="86400" <?= $_ENV['API_TOKEN_EXPIRE_TIME'] == 86400 ? 'selected' : '' ?>>1 dia</option>
                                    <option value="604800" <?= $_ENV['API_TOKEN_EXPIRE_TIME'] == 604800 ? 'selected' : '' ?>>1 semana</option>
                                    <option value="2592000" <?= $_ENV['API_TOKEN_EXPIRE_TIME'] == 2592000 ? 'selected' : '' ?>>1 mês</option>
                                </select>
                                <span class="form-text">Tempo de duração da sessão na API.</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label for="resetJwtToken" class="col-sm-3 col-form-label">Resetar token JWT</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="resetJwtToken" id="resetJwtToken">
                                    <option value="nao" selected>Não</option>
                                    <option value="sim">Sim</option>
                                </select>
                                <span class="form-text">Gerar um novo token JWT.</span>
                            </div>
                        </div>
                        <div class="ui-rodape-form">
                            <div style="display:contents">
                                <div style="display:contents">
                                  <button type="submit" class="button btn btn-primary">
                                  <span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2">Salvar Alterações</span></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal -->
<div id="modal-confirmaratualiza" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <form action="<?php echo base_url() ?>index.php/clientes/excluir" method="post">
        <div class="modal-header">
            
            <h5 id="myModalLabel">Atualização de sistema</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true" aria-label="Fechar"></button></div>
        <div class="modal-body">
            <h5 style="text-align: left">Deseja realmente fazer a atualização de sistema?</h5>
            <h7 style="text-align: left">Recomendamos que faça um backup antes de prosseguir!</h7>
            <h7 style="text-align: left"><br>Faça o backup dos seguintes arquivos pois os mesmo serão excluídos:</h7>
            <h7 style="text-align: left"><br>* ./assets/anexos</h7>
            <h7 style="text-align: left"><br>* ./assets/arquivos</h7>
        </div>
        <div class="modal-footer" style="display:flex;justify-content: center">
          <button class="button btn btn-sm btn-danger" data-bs-dismiss="modal" aria-hidden="true"><span class="button__icon"><i class='bx bx-x' ></i></span> <span class="button__text2">Cancelar</span></button>
          <button id="update-mapos" type="button" class="button btn btn-warning"><span class="button__icon"><i class="bx bx-sync"></i></span><span class="button__text2">Atualizar</span></button>
        </div>
    </form>
</div></div></div>
<!-- Modal -->
<div id="modal-confirmabanco" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <form action="<?php echo base_url() ?>index.php/clientes/excluir" method="post">
        <div class="modal-header">
            
            <h5 id="myModalLabel">Atualização de sistema</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true" aria-label="Fechar"></button></div>
        <div class="modal-body">
            <h5 style="text-align: left">Deseja realmente fazer a atualização do banco de dados?</h5>
            <h7 style="text-align: left">Recomendamos que faça um backup antes de prosseguir!
                <a target="_blank" title="Fazer Bakup" class="btn btn-sm btn-dark" href="<?php echo site_url() ?>/mapos/backup">Fazer Backup</a>
            </h7>
        </div>
        <div class="modal-footer" style="display:flex;justify-content: center">
          <button class="button btn btn-sm btn-danger" data-bs-dismiss="modal" aria-hidden="true"><span class="button__icon"><i class='bx bx-x' ></i></span> <span class="button__text2">Cancelar</span></button>
          <button id="update-database" type="button" class="button btn btn-warning"><span class="button__icon"><i class="bx bx-sync"></i></span><span class="button__text2">Atualizar</span></button>
        </div>
    </form>
</div></div></div>
<script nonce="<?= csp_nonce() ?>">
    $('#update-database').click(function() {
        window.location = "<?= site_url('mapos/atualizarBanco') ?>"
    });
    $('#update-mapos').click(function() {
        window.location = "<?= site_url('mapos/atualizarMapos') ?>"
    });
</script>
