<?php
// Exibição de valores: definido no controller (Os::visualizar) como
// eOs && !técnico. Default defensivo caso a view seja renderizada sem ele.
$permissao_eOs = isset($permissao_eOs) ? $permissao_eOs : false;
?>
<link href="<?= base_url('assets/css/custom.css'); ?>" rel="stylesheet">

<!-- Scripts do Sistema de Check-in (carregados antes dos modais) -->
<script src="<?php echo base_url(); ?>assets/js/assinatura-canvas.js?v=3"></script>
<script src="<?php echo base_url(); ?>assets/js/checkin-fotos.js?v=3"></script>
<script src="<?php echo base_url(); ?>assets/js/checkin.js?v=3"></script>
<script src="<?php echo base_url(); ?>assets/js/checkin-formularios.js?v=1"></script>
<script src="<?php echo base_url(); ?>assets/js/csrf.js?v=3"></script>
<script>
    // Configuração do CheckinManager
    window.checkinConfig = {
        baseUrl: '<?php echo base_url(); ?>',
        osId: <?php echo $result->idOs; ?>,
        debug: false
    };
</script>

<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title" style="margin: 10px 0 0">
                <div class="buttons">
                    <?php if ($editavel) {
                        echo '<a title="Editar OS" class="button btn btn-mini btn-success" href="' . base_url() . 'index.php/os/editar/' . $result->idOs . '">
                            <span class="button__icon"><i class="bx bx-edit"></i> </span> <span class="button__text">Editar</span>
                        </a>';
                    } ?>

                    <div class="button-container">
                        <a target="_blank" title="Imprimir Ordem de Serviço" class="button btn btn-mini btn-inverse"> <span class="button__icon"><i class="bx bx-printer"></i></span><span class="button__text">Imprimir</span></a>
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

                    <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
                        $this->load->model('os_model');
                        // Carrega a lib de forma resiliente: se não estiver disponível
                        // (ex.: arquivos do módulo WhatsApp ainda não implantados), a
                        // página não quebra — cai no link click-to-chat.
                        $whatsappApiAtivo = false;
                        $ciWpp = &get_instance();
                        if (file_exists(APPPATH . 'libraries/Evolution_api.php')) {
                            $ciWpp->load->library('evolution_api');
                            if (isset($ciWpp->evolution_api) && method_exists($ciWpp->evolution_api, 'estaAtivo')) {
                                $whatsappApiAtivo = $ciWpp->evolution_api->estaAtivo();
                            }
                        }
                        $zapnumber = preg_replace("/[^0-9]/", "", $result->celular_cliente);
                        $troca = [$result->nomeCliente, $result->idOs, $result->status, 'R$ ' . ($result->desconto != 0 && $result->valor_desconto != 0 ? number_format($result->valor_desconto, 2, ',', '.') : number_format($totalProdutos + $totalServico, 2, ',', '.')), strip_tags($result->descricaoProduto), ($emitente ? $emitente->nome : ''), ($emitente ? $emitente->telefone : ''), strip_tags($result->observacoes), strip_tags($result->defeito), strip_tags($result->laudoTecnico), date('d/m/Y', strtotime($result->dataFinal)), date('d/m/Y', strtotime($result->dataInicial)), $result->garantia . ' dias'];
                        $texto_de_notificacao = $this->os_model->criarTextoWhats($texto_de_notificacao, $troca);
                        if (!empty($zapnumber)) {
                            if ($whatsappApiAtivo) {
                                // Envio direto (server-side) pela Evolution API.
                                echo '<a title="Enviar Por WhatsApp (Evolution API)" class="button btn btn-mini btn-success" id="enviarWhatsAppApi" href="#" data-os="' . $result->idOs . '">
                                <span class="button__icon"><i class="bx bxl-whatsapp"></i></span> <span class="button__text">WhatsApp</span>
                            </a>';
                            } else {
                                // Fallback: link click-to-chat (abre o WhatsApp).
                                echo '<a title="Enviar Por WhatsApp" class="button btn btn-mini btn-success" id="enviarWhatsApp" target="_blank" href="https://api.whatsapp.com/send?phone=55' . $zapnumber . '&text=' . $texto_de_notificacao . '">
                                <span class="button__icon"><i class="bx bxl-whatsapp"></i></span> <span class="button__text">WhatsApp</span>
                            </a>';
                            }
                        }
                    } ?>

                    <a title="Enviar OS por E-mail" class="button btn btn-mini btn-warning" href="<?php echo site_url() ?>/os/enviar_email/<?php echo $result->idOs; ?>">
                        <span class="button__icon"><i class="bx bx-envelope"></i></span> <span class="button__text">via E-mail</span>
                    </a>

                    <?php
                    $notaFiscal = isset($notaFiscal) ? $notaFiscal : null;
                    if ($notaFiscal && $notaFiscal->status === 'autorizada') { ?>
                        <a title="Imprimir DANFSe" target="_blank" class="button btn btn-mini btn-inverse" href="<?php echo site_url('nfe/danfe/' . $notaFiscal->idNota); ?>">
                            <span class="button__icon"><i class="bx bx-receipt"></i></span> <span class="button__text">NFS-e nº <?php echo $notaFiscal->numero; ?></span>
                        </a>
                    <?php } elseif (in_array($result->status, ['Finalizado', 'Faturado', 'Aprovado']) && $this->permission->checkPermission($this->session->userdata('permissao'), 'eNfe')) { ?>
                        <a title="Transmitir NFS-e (serviços)" href="#modal-nfse" role="button" data-toggle="modal" data-os="<?php echo $result->idOs; ?>" class="button btn btn-mini btn-success btn-transmitir-nfse">
                            <span class="button__icon"><i class="bx bx-receipt"></i></span> <span class="button__text">Emitir NFS-e</span>
                        </a>
                    <?php } ?>

                    <?php
                    $notaFiscalNfe = isset($notaFiscalNfe) ? $notaFiscalNfe : null;
                    if ($notaFiscalNfe && $notaFiscalNfe->status === 'autorizada') { ?>
                        <a title="Imprimir DANFE" target="_blank" class="button btn btn-mini btn-inverse" href="<?php echo site_url('nfe/danfe/' . $notaFiscalNfe->idNota); ?>">
                            <span class="button__icon"><i class="bx bx-box"></i></span> <span class="button__text">NF-e nº <?php echo $notaFiscalNfe->numero; ?></span>
                        </a>
                    <?php } elseif (in_array($result->status, ['Finalizado', 'Faturado', 'Aprovado']) && $this->permission->checkPermission($this->session->userdata('permissao'), 'eNfe')) { ?>
                        <a title="Transmitir NF-e (produtos)" href="#modal-nfe" role="button" data-toggle="modal" data-os="<?php echo $result->idOs; ?>" class="button btn btn-mini btn-primary btn-transmitir-nfe">
                            <span class="button__icon"><i class="bx bx-box"></i></span> <span class="button__text">Emitir NF-e</span>
                        </a>
                    <?php } ?>

                    <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vCobranca')): ?>
                        <a href="#modal-gerar-pagamento" id="btn-forma-pagamento" role="button" data-toggle="modal" class="button btn btn-mini btn-primary">
                            <span class="button__icon"><i class='bx bx-dollar'></i></span><span class="button__text">Gerar Pagamento</span>
                        </a>

                        <?php if ($qrCode): ?>
                            <a href="#modal-pix" id="btn-pix" role="button" data-toggle="modal" class="button btn btn-mini btn-info">
                                <span class="button__icon"><i class='bx bx-qr'></i></span><span class="button__text">Chave PIX</span>
                            </a>
                        <?php endif ?>
                    <?php endif; ?>

                    <?php
                    // Link público e temporário de aprovação da OS pelo cliente.
                    // Só aparece se a migration do módulo já foi aplicada (coluna aprovacao_token).
                    $aprovacaoSuportada = $this->db->field_exists('aprovacao_token', 'os');
                    if ($aprovacaoSuportada && $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) { ?>
                        <a title="Gerar link de aprovação para o cliente" href="#modal-aprovacao" role="button" data-toggle="modal" class="button btn btn-mini btn-primary">
                            <span class="button__icon"><i class="bx bx-check-shield"></i></span> <span class="button__text">Link Aprovação</span>
                        </a>
                    <?php } ?>

                    <?php
                    // Liga/desliga a automação (NFS-e + boleto) na aprovação, só desta OS.
                    $automacaoSuportada = $this->db->field_exists('automacao_override', 'os');
                    $automacaoGlobalAtiva = (($this->data['configuration']['automacao_aprovacao_ativa'] ?? '0') === '1');
                    $permUsuario = $this->session->userdata('permissao');
                    $podeAutomacao = $this->permission->checkPermission($permUsuario, 'cAutomacao') || $this->permission->checkPermission($permUsuario, 'cSistema');
                    if ($automacaoSuportada && $podeAutomacao) {
                        $override = $result->automacao_override; // null | 0 | 1
                        if ($override !== null && $override !== '') {
                            $automacaoNestaOs = ((int) $override === 1);
                        } else {
                            $automacaoNestaOs = ! empty($result->automacao_aprovacao); // herda do cliente
                        }
                        ?>
                        <a title="<?= $automacaoGlobalAtiva ? 'Ativar/desativar a automação de aprovação só nesta OS' : 'Automação global desligada (Configurações > Automação)' ?>"
                           href="#" id="btnToggleAutomacao" data-idos="<?= $result->idOs ?>" data-valor="<?= $automacaoNestaOs ? 0 : 1 ?>"
                           class="button btn btn-mini <?= $automacaoNestaOs ? 'btn-success' : 'btn-inverse' ?>">
                            <span class="button__icon"><i class="bx bx-slider"></i></span>
                            <span class="button__text">Automação: <?= $automacaoNestaOs ? 'ON' : 'OFF' ?></span>
                        </a>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var btn = document.getElementById('btnToggleAutomacao');
                                if (!btn) return;
                                btn.addEventListener('click', function (e) {
                                    e.preventDefault();
                                    var body = new URLSearchParams();
                                    body.append('idOs', btn.getAttribute('data-idos'));
                                    body.append('valor', btn.getAttribute('data-valor'));
                                    body.append('<?= $this->security->get_csrf_token_name() ?>', '<?= $this->security->get_csrf_hash() ?>');
                                    fetch('<?= site_url('os/toggleAutomacao') ?>', {
                                        method: 'POST',
                                        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
                                        body: body.toString()
                                    }).then(function (r) { return r.json(); }).then(function (j) {
                                        if (j && j.success) { location.reload(); }
                                        else { alert(j && j.message ? j.message : 'Falha ao alterar a automação.'); }
                                    }).catch(function () { alert('Falha ao alterar a automação.'); });
                                });
                            });
                        </script>
                    <?php } ?>

                    <?php
                    // Link público de ACEITE do serviço realizado (pós-execução, com assinatura).
                    // Só aparece com a migration aplicada (coluna aceite_token) e OS concluída.
                    $aceiteSuportado = $this->db->field_exists('aceite_token', 'os');
                    if ($aceiteSuportado && in_array($result->status, ['Finalizado', 'Faturado']) && $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) { ?>
                        <a title="Solicitar aceite do serviço realizado ao cliente" href="#modal-aceite" role="button" data-toggle="modal" class="button btn btn-mini btn-success">
                            <span class="button__icon"><i class="bx bx-badge-check"></i></span> <span class="button__text">Link Aceite</span>
                        </a>
                    <?php } ?>

                    <?php
                    // Botões de Check-in/Check-out do Atendimento
                    // Verifica permissão específica vBtnAtendimento OU permissão geral de editar OS (eOs)
                    // OU permissões de técnico específicas (eTecnicoCheckin/eTecnicoCheckout)
                    if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vBtnAtendimento') ||
                        $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs') ||
                        $this->permission->checkPermission($this->session->userdata('permissao'), 'eTecnicoCheckin') ||
                        $this->permission->checkPermission($this->session->userdata('permissao'), 'eTecnicoCheckout')) {
                        // Verifica se as variáveis existem, caso contrário inicializa com valores padrão
                        $checkinAtivo = isset($checkinAtivo) ? $checkinAtivo : null;
                        $checkins = isset($checkins) ? $checkins : array();

                        // Usa a variável checkinAtivo passada pelo controller
                        if (!$checkinAtivo) {
                            // Não há atendimento em andamento - mostrar botão Iniciar
                            echo '<button type="button" id="btn-iniciar-atendimento" class="button btn btn-mini" style="background-color: #28a745; border-color: #28a745; color: white;">';
                            echo '<span class="button__icon"><i class="bx bx-log-in"></i></span>';
                            echo '<span class="button__text">Iniciar Atendimento</span>';
                            echo '</button>';
                        } else {
                            // Há atendimento em andamento - mostrar botão Finalizar
                            echo '<button type="button" id="btn-finalizar-atendimento" class="button btn btn-mini" style="background-color: #dc3545; border-color: #dc3545; color: white;">';
                            echo '<span class="button__icon"><i class="bx bx-log-out"></i></span>';
                            echo '<span class="button__text">Finalizar Atendimento</span>';
                            echo '</button>';
                        }

                        // Botão de impressão do relatório de atendimento (se houver checkin)
                        if (!empty($checkins)) {
                            echo '<a target="_blank" title="Imprimir Relatório de Atendimento" class="button btn btn-mini" style="background-color: #6c757d; border-color: #6c757d; color: white;" href="' . site_url('checkin/imprimir/' . $result->idOs) . '">';
                            echo '<span class="button__icon"><i class="bx bx-time"></i></span>';
                            echo '<span class="button__text">Relatório Atendimento</span>';
                            echo '</a>';
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="widget-content" id="printOs">
                <div class="invoice-content">
                    <div class="invoice-head" style="margin-bottom: 0; margin-top:-30px">
                        <table class="table table-condensed">
                            <tbody>
                                <?php if ($emitente == null) { ?>
                                    <tr>
                                        <td colspan="3" class="alert">Você precisa configurar os dados do emitente. >>><a href="<?php echo base_url(); ?>index.php/mapos/emitente">Configurar <<< </a></td>
                                    </tr>
                                <?php } ?>
                                <h3><i class='bx bx-file'></i> Ordem de Serviço #<?php echo sprintf('%04d', $result->idOs) ?></h3>
                            </tbody>
                        </table>
                        <table class="table table-condensend">
                            <tbody>
                                <tr>
                                    <td style="width: 60%; padding-left: 0">
                                        <span>
                                            <h5><b>CLIENTE</b></h5>
                                            <span><i class='bx bxs-business'></i> <b><?php echo $result->nomeCliente ?></b></span><br />
                                            <?php if (!empty($result->celular_cliente) || !empty($result->telefone_cliente) || !empty($result->contato_cliente)): ?>
                                                <span><i class='bx bxs-phone'></i>
                                                    <?= !empty($result->contato_cliente) ? $result->contato_cliente . ' ' : "" ?>
                                                    <?php if ($result->celular_cliente == $result->telefone_cliente) { ?>
                                                        <?= $result->celular_cliente ?>
                                                    <?php } else { ?>
                                                        <?= !empty($result->telefone_cliente) ? $result->telefone_cliente : "" ?>
                                                        <?= !empty($result->celular_cliente) && !empty($result->telefone_cliente) ? ' / ' : "" ?>
                                                        <?= !empty($result->celular_cliente) ? $result->celular_cliente : "" ?>
                                                    <?php } ?>
                                                </span></br>
                                            <?php endif; ?>
                                            <?php
                                            $retorno_end = array_filter([$result->rua, $result->numero, $result->complemento, $result->bairro . ' - ']);
                                            $endereco = implode(', ', $retorno_end);
                                            echo '<i class="fas fa-map-marker-alt"></i> ';
                                            if (!empty($endereco)) {
                                                echo $endereco;
                                            }
                                            if (!empty($result->cidade) || !empty($result->estado) || !empty($result->cep)) {
                                                echo "<span> {$result->cep}, {$result->cidade}/{$result->estado}</span><br>";
                                            }
                                            ?>
                                            <?php if (!empty($result->email)): ?>
                                                <span><i class="fas fa-envelope"></i>
                                                    <?php echo $result->email ?></span><br>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td style="width: 40%; padding-left: 0">
                                        <ul>
                                            <li>
                                                <span>
                                                    <h5><b>RESPONSÁVEL</b></h5>
                                                </span>
                                                <span><b><i class="fas fa-user"></i>
                                                        <?php echo $result->nome ?></b></span><br />
                                                <span><i class="fas fa-phone"></i>
                                                    <?php echo $result->telefone_usuario ?></span><br />
                                                <span><i class="fas fa-envelope"></i>
                                                    <?php echo $result->email_usuario ?></span>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </div>

                    <div style="margin-top: 0; padding-top: 0">
                        <table class="table table-condensed">
                            <tbody>
                                <?php if ($result->dataInicial != null) { ?>
                                    <tr>
                                        <td>
                                            <b>STATUS OS: </b><br>
                                            <?php echo $result->status ?>
                                        </td>

                                        <td>
                                            <b>DATA INICIAL: </b><br>
                                            <?php echo date('d/m/Y', strtotime($result->dataInicial)); ?>
                                        </td>

                                        <td>
                                            <b>DATA FINAL: </b><br>
                                            <?php echo $result->dataFinal ? date('d/m/Y', strtotime($result->dataFinal)) : ''; ?>
                                        </td>

                                        <td>
                                            <?php if ($result->garantia) { ?>
                                                <b>GARANTIA: </b><br><?php echo $result->garantia . ' dia(s)'; ?>
                                            <?php } ?>
                                        </td>

                                        <?php if (in_array($result->status, ['Finalizado', 'Faturado', 'Orçamento', 'Aberto'])): ?>
                                            <td>
                                                <b>VENC. DA GARANTIA:</b><br>
                                                <?= dateInterval($result->dataFinal, $result->garantia); ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php } ?>

                                <?php if ($result->descricaoProduto != null) { ?>
                                    <tr>
                                        <td colspan="5">
                                            <b>DESCRIÇÃO: </b>
                                            <?php echo htmlspecialchars_decode($result->descricaoProduto) ?>
                                        </td>
                                    </tr>
                                <?php } ?>

                                <?php if ($result->defeito != null) { ?>
                                    <tr>
                                        <td colspan="5">
                                            <b>DEFEITO APRESENTADO: </b>
                                            <?php echo htmlspecialchars_decode($result->defeito) ?>
                                        </td>
                                    </tr>
                                <?php } ?>

                                <?php if ($result->observacoes != null) { ?>
                                    <tr>
                                        <td colspan="5">
                                            <b>OBSERVAÇÕES: </b>
                                            <?php echo htmlspecialchars_decode($result->observacoes) ?>
                                        </td>
                                    </tr>
                                <?php } ?>

                                <?php if ($result->laudoTecnico != null) { ?>
                                    <tr>
                                        <td colspan="5">
                                            <b>LAUDO TÉCNICO: </b>
                                            <?php echo htmlspecialchars_decode($result->laudoTecnico) ?>
                                        </td>
                                    </tr>
                                <?php } ?>

                                <?php if ($result->garantias_id != null) { ?>
                                    <tr>
                                        <td colspan="5">
                                            <strong>TERMO DE GARANTIA </strong><br>
                                            <?php echo htmlspecialchars_decode($result->textoGarantia) ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                        <!-- Galeria de Fotos do Atendimento -->
                        <?php
                        // Usa as variáveis passadas pelo controller - inicializa se não existirem
                        $checkins = isset($checkins) ? $checkins : array();
                        $assinaturas = isset($assinaturas) ? $assinaturas : array();
                        $fotosAtendimento = isset($fotosAtendimento) ? $fotosAtendimento : array();

                        $respostasFormularios = isset($respostasFormularios) && is_array($respostasFormularios) ? $respostasFormularios : [];
                        if (!empty($checkins) || !empty($assinaturas) || !empty($fotosAtendimento) || !empty($respostasFormularios)) {
                        ?>
                        <div class="widget-box" style="margin-top: 20px;">
                            <div class="widget-title">
                                <span class="icon"><i class="bx bx-time"></i></span>
                                <h5>Registro de Atendimento</h5>
                            </div>
                            <div class="widget-content">
                                <!-- Timeline do Atendimento -->
                                <?php if (!empty($checkins)) { ?>
                                <div class="checkin-timeline" style="margin-bottom: 20px;">
                                    <h6><i class="bx bx-calendar-check"></i> Histórico do Atendimento</h6>
                                    <div class="timeline">
                                        <?php foreach ($checkins as $checkin) { ?>
                                        <div class="timeline-item" style="border-left: 2px solid #2d335b; padding-left: 15px; margin-bottom: 15px; position: relative;">
                                            <div style="position: absolute; left: -6px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: #2d335b;"></div>
                                            <div class="timeline-content">
                                                <p><strong>Técnico:</strong> <?php echo $checkin->nome_tecnico; ?></p>
                                                <p><strong>Entrada:</strong> <?php echo date('d/m/Y H:i', strtotime($checkin->data_entrada)); ?>
                                                    <?php if ($checkin->latitude_entrada && $checkin->longitude_entrada) { ?>
                                                        <a href="https://www.google.com/maps?q=<?php echo $checkin->latitude_entrada; ?>,<?php echo $checkin->longitude_entrada; ?>" target="_blank" class="btn btn-mini" title="Ver no mapa">
                                                            <i class="bx bx-map"></i>
                                                        </a>
                                                    <?php } ?>
                                                </p>

                                                <?php if ($checkin->data_saida) { ?>
                                                <p><strong>Saída:</strong> <?php echo date('d/m/Y H:i', strtotime($checkin->data_saida)); ?>
                                                    <?php if ($checkin->latitude_saida && $checkin->longitude_saida) { ?>
                                                        <a href="https://www.google.com/maps?q=<?php echo $checkin->latitude_saida; ?>,<?php echo $checkin->longitude_saida; ?>" target="_blank" class="btn btn-mini" title="Ver no mapa">
                                                            <i class="bx bx-map"></i>
                                                        </a>
                                                    <?php } ?>
                                                </p>
                                                <p><strong>Tempo Total:</strong>
                                                    <?php
                                                    $entrada = new DateTime($checkin->data_entrada);
                                                    $saida = new DateTime($checkin->data_saida);
                                                    $intervalo = $entrada->diff($saida);
                                                    echo $intervalo->format('%h horas %i minutos');
                                                    ?>
                                                </p>
                                                <p><span class="label label-success">Finalizado</span></p>
                                                <?php } else { ?>
                                                <p><span class="label label-warning">Em Andamento</span></p>
                                                <?php } ?>

                                                <?php if ($checkin->observacao_entrada) { ?>
                                                <p><strong>Obs. Entrada:</strong> <?php echo nl2br($checkin->observacao_entrada); ?></p>
                                                <?php } ?>

                                                <?php if ($checkin->observacao_saida) { ?>
                                                <p><strong>Obs. Saída:</strong> <?php echo nl2br($checkin->observacao_saida); ?></p>
                                                <?php } ?>
                                            </div>
                                        </div>
                                        <?php } ?>
                                    </div>
                                </div>
                                <?php } ?>

                                <!-- Respostas dos Formulários de Atendimento -->
                                <?php if (!empty($respostasFormularios)) {
                                    $rotulosEtapaFA = [
                                        'iniciar' => 'Ao iniciar o atendimento',
                                        'durante' => 'Durante o atendimento',
                                        'finalizar' => 'Ao finalizar o atendimento',
                                        'outros' => 'Outros',
                                    ]; ?>
                                <div class="respostas-formularios" style="margin-bottom: 20px;">
                                    <h6><i class="bx bx-list-check"></i> Formulários de Atendimento</h6>
                                    <?php foreach (['iniciar', 'durante', 'finalizar', 'outros'] as $etapaFA) {
                                        if (empty($respostasFormularios[$etapaFA])) { continue; } ?>
                                        <p style="margin: 8px 0 4px; font-weight: bold; color: #2d335b;"><?php echo $rotulosEtapaFA[$etapaFA]; ?></p>
                                        <?php foreach ($respostasFormularios[$etapaFA] as $respostaFA) { ?>
                                            <div style="border: 1px solid #e2e5ef; border-radius: 6px; padding: 10px 12px; margin-bottom: 10px; background: #fafbff;">
                                                <div style="font-weight: 600; margin-bottom: 6px;"><?php echo htmlspecialchars($respostaFA->formulario_nome); ?></div>
                                                <table class="table table-condensed" style="margin-bottom: 0;">
                                                    <?php foreach ($respostaFA->itens as $itemFA) { ?>
                                                        <tr>
                                                            <td style="width: 40%; color: #555; border-top: none;"><?php echo htmlspecialchars($itemFA->label); ?></td>
                                                            <td style="border-top: none;"><?php echo ($itemFA->valor !== null && $itemFA->valor !== '') ? nl2br(htmlspecialchars($itemFA->valor)) : '<span style="color:#999">—</span>'; ?></td>
                                                        </tr>
                                                    <?php } ?>
                                                </table>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                                <?php } ?>

                                <!-- Galeria de Fotos -->
                                <?php if (!empty($fotosAtendimento)) { ?>
                                <div class="galeria-fotos">
                                    <h6><i class="bx bx-images"></i> Fotos do Atendimento</h6>

                                    <?php
                                    // Organiza fotos por etapa
                                    $fotosPorEtapa = [
                                        'entrada' => [],
                                        'durante' => [],
                                        'saida' => []
                                    ];
                                    foreach ($fotosAtendimento as $foto) {
                                        $fotosPorEtapa[$foto->etapa][] = $foto;
                                    }
                                    ?>

                                    <?php if (!empty($fotosPorEtapa['entrada'])) { ?>
                                    <div class="foto-etapa">
                                        <h6 style="color: #28a745;"><i class="bx bx-log-in"></i> Entrada</h6>
                                        <div class="fotos-grid">
                                            <?php foreach ($fotosPorEtapa['entrada'] as $foto) { ?>
                                            <div class="foto-item" id="foto-item-<?php echo $foto->idFoto; ?>">
                                                <a href="<?php echo $foto->url; ?>" target="_blank" class="foto-link">
                                                    <img src="<?php echo $foto->url; ?>" alt="Foto de entrada">
                                                </a>
                                                <?php if ($foto->descricao) { ?>
                                                <p class="foto-descricao"><?php echo $foto->descricao; ?></p>
                                                <?php } ?>
                                                <div class="foto-acoes">
                                                    <a href="<?php echo site_url('checkin/downloadFoto/' . $foto->idFoto); ?>" class="btn btn-mini" title="Download">
                                                        <i class="bx bx-download"></i>
                                                    </a>
                                                    <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) { ?>
                                                    <button type="button" class="btn btn-mini btn-danger btn-remover-foto" data-foto-id="<?php echo $foto->idFoto; ?>" title="Remover">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <?php } ?>

                                    <?php if (!empty($fotosPorEtapa['durante'])) { ?>
                                    <div class="foto-etapa">
                                        <h6 style="color: #007bff;"><i class="bx bx-camera"></i> Durante o Atendimento</h6>
                                        <div class="fotos-grid">
                                            <?php foreach ($fotosPorEtapa['durante'] as $foto) { ?>
                                            <div class="foto-item">
                                                <a href="<?php echo $foto->url; ?>" target="_blank" class="foto-link">
                                                    <img src="<?php echo $foto->url; ?>" alt="Foto durante atendimento">
                                                </a>
                                                <?php if ($foto->descricao) { ?>
                                                <p class="foto-descricao"><?php echo $foto->descricao; ?></p>
                                                <?php } ?>
                                                <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) { ?>
                                                <div class="foto-acoes">
                                                    <a href="<?php echo site_url('checkin/downloadFoto/' . $foto->idFoto); ?>" class="btn btn-mini" title="Download">
                                                        <i class="bx bx-download"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-mini btn-danger btn-remover-foto" data-foto-id="<?php echo $foto->idFoto; ?>" title="Remover">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </div>
                                                <?php } ?>
                                            </div>
                                            <?php } ?>
                                        </div>

                                        <!-- Botão para adicionar foto durante o atendimento -->
                                        <?php
                                        // Verifica se há checkin ativo - variável $checkinAtivo já foi passada pelo controller
                                        if (isset($checkinAtivo) && $checkinAtivo && $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) {
                                        ?>
                                        <div class="span12" style="margin-top: 10px;">
                                            <input type="file" id="foto-durante-input" class="checkin-foto-input" data-etapa="durante" accept="image/*" style="display: none;">
                                            <button type="button" class="btn btn-success" onclick="document.getElementById('foto-durante-input').click()">
                                                <i class="bx bx-plus"></i> Adicionar Foto
                                            </button>
                                        </div>
                                        <?php } ?>
                                    </div>
                                    <?php } ?>

                                    <?php if (!empty($fotosPorEtapa['saida'])) { ?>
                                    <div class="foto-etapa">
                                        <h6 style="color: #dc3545;"><i class="bx bx-log-out"></i> Saída</h6>
                                        <div class="fotos-grid">
                                            <?php foreach ($fotosPorEtapa['saida'] as $foto) { ?>
                                            <div class="foto-item" id="foto-item-<?php echo $foto->idFoto; ?>">
                                                <a href="<?php echo $foto->url; ?>" target="_blank" class="foto-link">
                                                    <img src="<?php echo $foto->url; ?>" alt="Foto de saída">
                                                </a>
                                                <?php if ($foto->descricao) { ?>
                                                <p class="foto-descricao"><?php echo $foto->descricao; ?></p>
                                                <?php } ?>
                                                <div class="foto-acoes">
                                                    <a href="<?php echo site_url('checkin/downloadFoto/' . $foto->idFoto); ?>" class="btn btn-mini" title="Download">
                                                        <i class="bx bx-download"></i>
                                                    </a>
                                                    <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) { ?>
                                                    <button type="button" class="btn btn-mini btn-danger btn-remover-foto" data-foto-id="<?php echo $foto->idFoto; ?>" title="Remover">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <?php } ?>
                                </div>
                                <?php } ?>

                                <!-- Assinaturas -->
                                <?php log_info('View VisualizarOS - Assinaturas recebidas: ' . count($assinaturas)); ?>
                                <?php if (!empty($assinaturas)) { ?>
                                <div class="assinaturas-section" style="margin-top: 20px;">
                                    <h6><i class="bx bx-pen"></i> Assinaturas</h6>
                                    <div class="row-fluid">
                                        <?php foreach ($assinaturas as $assinatura) { ?>
                                        <div class="span3" id="assinatura-item-<?php echo $assinatura->idAssinatura; ?>" style="text-align: center; margin-bottom: 15px;">
                                            <div style="border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                                                <?php
                                                // Verifica se é base64 ou arquivo
                                                if (isset($assinatura->is_base64) && $assinatura->is_base64) {
                                                    $img_src = $assinatura->url_visualizacao;
                                                } else {
                                                    $img_src = base_url($assinatura->assinatura);
                                                }
                                                ?>
                                                <img src="<?php echo $img_src; ?>" alt="Assinatura" style="max-width: 100%; height: auto; max-height: 100px;">
                                                <p style="margin-top: 10px; font-size: 12px;">
                                                    <strong>
                                                        <?php
                                                        switch($assinatura->tipo) {
                                                            case 'tecnico_entrada':
                                                                echo 'Técnico (Entrada)';
                                                                break;
                                                            case 'tecnico_saida':
                                                                echo 'Técnico (Saída)';
                                                                break;
                                                            case 'cliente_saida':
                                                                echo 'Cliente';
                                                                break;
                                                            default:
                                                                echo $assinatura->tipo;
                                                        }
                                                        ?>
                                                    </strong>
                                                </p>
                                                <?php if ($assinatura->nome_assinante) { ?>
                                                <p style="font-size: 11px; color: #666;">
                                                    <?php echo $assinatura->nome_assinante; ?>
                                                    <?php if ($assinatura->documento_assinante) { ?>
                                                    <br><small><?php echo $assinatura->documento_assinante; ?></small>
                                                    <?php } ?>
                                                </p>
                                                <?php } ?>
                                                <p style="font-size: 10px; color: #999;">
                                                    <?php echo date('d/m/Y H:i', strtotime($assinatura->data_assinatura)); ?>
                                                </p>
                                                <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) { ?>
                                                <p style="margin-top: 8px;">
                                                    <button type="button" class="btn btn-mini btn-danger btn-remover-assinatura" data-assinatura-id="<?php echo $assinatura->idAssinatura; ?>" title="Remover Assinatura">
                                                        <i class="bx bx-trash"></i> Excluir
                                                    </button>
                                                </p>
                                                <?php } ?>
                                            </div>
                                        </div>
                                        <?php } ?>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } ?>

                        <?php if ($anotacoes != null) { ?>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Anotação</th>
                                        <th>Data/Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($anotacoes as $a) {
                                        echo '<tr>';
                                        echo '<td>' . $a->anotacao . '</td>';
                                        echo '<td>' . date('d/m/Y H:i:s', strtotime($a->data_hora)) . '</td>';
                                        echo '</tr>';
                                    }
                                    if (!$anotacoes) {
                                        echo '<tr><td colspan="2">Nenhuma anotação cadastrada</td></tr>';
                                    } ?>
                                </tbody>
                            </table>
                        <?php } ?>

                        <?php if ($anexos != null) { ?>
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <th>Anexo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <th colspan="5">
                                        <?php foreach ($anexos as $a) {
                                            if ($a->thumb == null) {
                                                $thumb = base_url() . 'assets/img/icon-file.png';
                                                $link = base_url() . 'assets/img/icon-file.png';
                                            } else {
                                                $thumb = $a->url . '/thumbs/' . $a->thumb;
                                                $link = $a->url . '/' . $a->anexo;
                                            }
                                            echo '<div class="span3" style="min-height: 150px; margin-left: 0"><a style="min-height: 150px;" href="#modal-anexo" imagem="' . $a->idAnexos . '" link="' . $link . '" role="button" class="btn anexo span12" data-toggle="modal"><img src="' . $thumb . '" alt=""></a></div>';
                                        } ?>
                                    </th>
                                </tbody>
                            </table>
                        <?php } ?>

                        <?php if ($produtos != null) { ?>
                            <br />
                            <table class="table table-bordered table-condensed" id="tblProdutos">
                                <thead>
                                    <tr>
                                        <th>PRODUTO</th>
                                        <th>QTD</th>
                                        <?php if ($permissao_eOs) { ?>
                                        <th>UNT</th>
                                        <th>SUBTOTAL</th>
                                        <?php } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($produtos as $p) {
                                        echo '<tr>';
                                        echo '<td>' . $p->descricao . '</td>';
                                        echo '<td>' . $p->quantidade . '</td>';
                                        if ($permissao_eOs) {
                                            echo '<td>R$ ' . $p->preco ?: $p->precoVenda . '</td>';
                                            echo '<td>R$ ' . number_format($p->subTotal, 2, ',', '.') . '</td>';
                                        }
                                        echo '</tr>';
                                    } ?>
                                    <?php if ($permissao_eOs) { ?>
                                    <tr>
                                        <td></td>
                                        <td colspan="2" style="text-align: right"><strong>TOTAL:</strong></td>
                                        <td><strong>R$ <?php echo number_format($totalProdutos, 2, ',', '.'); ?></strong>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>
                        <?php if ($servicos != null) { ?>
                            <table class="table table-bordered table-condensed">
                                <thead>
                                    <tr>
                                        <th>SERVIÇO</th>
                                        <th>QTD</th>
                                        <?php if ($permissao_eOs) { ?>
                                        <th>UNT</th>
                                        <th>SUBTOTAL</th>
                                        <?php } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php setlocale(LC_MONETARY, 'en_US');
                                    foreach ($servicos as $s) {
                                        $preco = $s->preco ?: $s->precoVenda;
                                        $subtotal = $preco * ($s->quantidade ?: 1);
                                        echo '<tr>';
                                        echo '<td>' . $s->nome . '</td>';
                                        echo '<td>' . ($s->quantidade ?: 1) . '</td>';
                                        if ($permissao_eOs) {
                                            echo '<td>R$ ' . $preco . '</td>';
                                            echo '<td>R$ ' . number_format($subtotal, 2, ',', '.') . '</td>';
                                        }
                                        echo '</tr>';
                                    } ?>
                                    <?php if ($permissao_eOs) { ?>
                                    <tr>
                                        <td colspan="3" style="text-align: right"><strong>TOTAL:</strong></td>
                                        <td><strong>R$ <?php echo number_format($totalServico, 2, ',', '.'); ?></strong>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>
                        <?php if ($permissao_eOs) { ?>
                        <table class="table table-bordered table-condensed">
                            <?php if ($totalProdutos != 0 || $totalServico != 0) {
                                if ($result->valor_desconto != 0) {
                                    echo "<td>";
                                    echo "<h4 style='text-align: right'>SUBTOTAL: R$ " . number_format($totalProdutos + $totalServico, 2, ',', '.') . "</h4>";
                                    echo $result->valor_desconto != 0 ? "<h4 style='text-align: right'>DESCONTO: R$ " . number_format($result->valor_desconto != 0 ? $result->valor_desconto - ($totalProdutos + $totalServico) : 0.00, 2, ',', '.') . "</h4>" : "";
                                    echo "<h4 style='text-align: right'>TOTAL: R$ " . number_format($result->valor_desconto, 2, ',', '.') . "</h4>";
                                    echo "</td>";
                                } else {
                                    echo "<td>";
                                    echo "<h4 style='text-align: right'>TOTAL: R$ " . number_format($totalProdutos + $totalServico, 2, ',', '.') . "</h4>";
                                    echo "</td>";
                                }
                            } ?>
                        </table>
                        <?php } ?>

                        <?php $this->load->view('os/_aprovacao_info', ['result' => $result]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vNfe')) { ?>
<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="bx bx-file"></i></span>
                <h5>Notas Fiscais Emitidas</h5>
            </div>
            <div class="widget-content">
                <?php echo $this->load->view('os/_notas_fiscais', ['notas' => (isset($notasFiscais) ? $notasFiscais : []), 'boletos' => (isset($boletosPorNota) ? $boletosPorNota : []), 'coraStage' => (isset($coraStage) ? $coraStage : false)], true); ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<?= $modalGerarPagamento ?>

<!-- Modal visualizar anexo -->
<div id="modal-anexo" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
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

<!-- Modal PIX -->
<div id="modal-pix" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
        <h3 id="myModalLabel">Pagamento via PIX</h3>
    </div>
    <div class="modal-body">
        <div class="span12" id="div-pix" style="text-align: center">
            <td style="width: 15%; padding: 0;text-align:center;">
                <img src="<?php echo base_url(); ?>assets/img/logo_pix.png" alt="QR Code de Pagamento" /></br>
                <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) { ?>
                <img id="qrCodeImage" width="50%" src="<?= $qrCode ?>" alt="QR Code de Pagamento" /></br>
                <?php echo '<span>Chave PIX: ' . $chaveFormatada . '</span>'; ?></br>
                <?php if ($totalProdutos != 0 || $totalServico != 0) {
                    if ($result->valor_desconto != 0) {
                        echo "Valor Total: R$ " . number_format($result->valor_desconto, 2, ',', '.');
                    } else {
                        echo "Valor Total: R$ " . number_format($totalProdutos + $totalServico, 2, ',', '.');
                    }
                } ?>
                <?php } else { ?>
                <p class="text-muted">QR Code de pagamento disponível apenas para administradores.</p>
                <?php } ?>
            </td>
        </div>
    </div>
    <div class="modal-footer">
        <?php if (!empty($zapnumber)) {
            echo "<button id='pixWhatsApp' class='btn btn-success' data-dismiss='modal' aria-hidden='true' style='color: #FFF'><i class='bx bxl-whatsapp'></i> WhatsApp</button>";
        } ?>
        <button class="btn btn-primary" id="copyButton" style="margin:5px; color: #FFF"><i class="fas fa-copy"></i> Copia e Cola</button>
        <button class="btn btn-danger" data-dismiss="modal" aria-hidden="true" style="color: #FFF">Fechar</button>
    </div>
</div>
<?php
// ------- Modal: Link de aprovação da OS (temporário) -------
if (isset($aprovacaoSuportada) && $aprovacaoSuportada && $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
    $apToken = $result->aprovacao_token ?? null;
    $apStatus = $result->aprovacao_status ?? null;
    $apExpira = $result->aprovacao_expira ?? null;
    $apUrl = $apToken ? site_url('aprovacao/' . $apToken) : '';
    $zapAprov = preg_replace('/[^0-9]/', '', $result->celular_cliente ?? '');

    // Exigência de código de verificação. Como getById() traz os.* e clientes.*
    // e ambas têm a coluna aprovacao_exige_token, lemos os valores com alias
    // explícito para não haver ambiguidade (o clientes.* sobrescreveria).
    $apSuportaVerificacao = $this->db->field_exists('aprovacao_exige_token', 'os');
    $apExigeTokenOs = 0;
    $apExigeTokenCliente = 0;
    $apTokenNumeros = '';
    $apTemNumerosCol = $this->db->field_exists('aprovacao_token_numeros', 'os');
    if ($apSuportaVerificacao) {
        $selectFlags = 'os.aprovacao_exige_token AS os_flag, clientes.aprovacao_exige_token AS cli_flag';
        if ($apTemNumerosCol) {
            $selectFlags .= ', os.aprovacao_token_numeros AS os_numeros';
        }
        $this->db->select($selectFlags);
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id');
        $this->db->where('os.idOs', $result->idOs);
        if ($flagRow = $this->db->get()->row()) {
            $apExigeTokenOs = (int) ($flagRow->os_flag ?? 0);
            $apExigeTokenCliente = (int) ($flagRow->cli_flag ?? 0);
            $apTokenNumeros = (string) ($flagRow->os_numeros ?? '');
        }
    }
?>
    <div id="modal-aprovacao" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4><i class="bx bx-check-shield"></i> Link de Aprovação &mdash; OS #<?php echo $result->idOs; ?></h4>
        </div>
        <div class="modal-body">
            <p>Gere um link temporário para o cliente aprovar ou reprovar esta Ordem de Serviço. O link deixa de funcionar após a decisão do cliente ou ao expirar.</p>

            <?php if ($apStatus === 'aprovado' || $apStatus === 'reprovado') { ?>
                <div class="alert <?php echo $apStatus === 'aprovado' ? 'alert-success' : 'alert-error'; ?>">
                    OS <?php echo $apStatus === 'aprovado' ? 'APROVADA' : 'REPROVADA'; ?>
                    <?php echo $result->aprovacao_nome ? ' por ' . html_escape($result->aprovacao_nome) : ''; ?>
                    <?php echo $result->aprovacao_data ? ' em ' . date('d/m/Y H:i', strtotime($result->aprovacao_data)) : ''; ?>.
                    <?php echo (! empty($result->aprovacao_obs)) ? '<br>Motivo: ' . html_escape($result->aprovacao_obs) : ''; ?>
                </div>
            <?php } elseif ($apStatus === 'pendente') { ?>
                <div class="alert alert-info">Link ativo, aguardando decisão do cliente<?php echo $apExpira ? '. Válido até ' . date('d/m/Y', strtotime($apExpira)) : ''; ?>.</div>
            <?php } ?>

            <div class="control-group">
                <label class="control-label" for="apDias">Validade do link (dias)</label>
                <div class="controls">
                    <input type="number" id="apDias" value="7" min="1" max="90" style="width:80px">
                </div>
            </div>

            <?php if ($apSuportaVerificacao) { ?>
                <div class="control-group">
                    <label class="control-label">Segurança</label>
                    <div class="controls">
                        <label for="apExigeToken" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" id="apExigeToken" value="1" <?php echo $apExigeTokenOs ? 'checked' : ''; ?>>
                            Exigir código de verificação do cliente antes de aprovar
                        </label>
                        <?php if ($apExigeTokenCliente) { ?>
                            <span class="help-inline" style="color:#8a6d3b">Já exigido pelo cadastro deste cliente (vale mesmo desmarcado aqui).</span>
                        <?php } else { ?>
                            <span class="help-inline">Envia um código por WhatsApp/e-mail que o cliente digita na página. Aplicado ao gerar o link.</span>
                        <?php } ?>
                    </div>
                </div>
                <?php if ($apTemNumerosCol) { ?>
                    <div class="control-group">
                        <label class="control-label" for="apTokenNumeros">Números extras (WhatsApp)</label>
                        <div class="controls">
                            <textarea id="apTokenNumeros" rows="2" placeholder="Um número por linha, com DDD" style="width:95%"><?php echo html_escape($apTokenNumeros); ?></textarea>
                            <span class="help-inline">Além do celular do cliente e dos números do cadastro, envia o código para estes (avulsos desta OS). Salvo ao gerar o link.</span>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>

            <div id="apLinkBox" style="<?php echo $apUrl ? '' : 'display:none'; ?>">
                <label><strong>Link para o cliente</strong></label>
                <div class="input-append" style="width:100%">
                    <input type="text" id="apLink" readonly value="<?php echo html_escape($apUrl); ?>" style="width:75%">
                    <button class="btn" type="button" id="apCopiar"><i class="bx bx-copy"></i> Copiar</button>
                </div>
                <?php if (! empty($zapAprov)) { ?>
                    <a id="apWhats" target="_blank" class="btn btn-success" style="color:#fff;margin-top:8px" href="#">
                        <i class="bx bxl-whatsapp"></i> Enviar por WhatsApp
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" data-dismiss="modal" aria-hidden="true" style="color:#fff">Fechar</button>
            <?php if ($apToken) { ?>
                <button class="btn" type="button" id="apRevogar" style="color:#fff;background:#8a6d3b">Revogar link</button>
            <?php } ?>
            <button class="btn btn-primary" type="button" id="apGerar" style="color:#fff">
                <i class="bx bx-link"></i> <?php echo $apToken ? 'Gerar novo link' : 'Gerar link'; ?>
            </button>
        </div>
    </div>

    <script type="text/javascript">
        $(function() {
            var apIdOs = "<?php echo $result->idOs; ?>";
            var apZap = "<?php echo $zapAprov; ?>";
            var apCliente = <?php echo json_encode($result->nomeCliente); ?>;

            function apMontarWhats(url) {
                if (!apZap) return;
                var msg = 'Ola ' + apCliente + ', segue o link para aprovacao da sua Ordem de Servico #' + apIdOs + ': ' + url;
                $('#apWhats').attr('href', 'https://api.whatsapp.com/send?phone=55' + apZap + '&text=' + encodeURIComponent(msg));
            }
            apMontarWhats($('#apLink').val());

            $('#apGerar').on('click', function() {
                var btn = $(this).addClass('disabled').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: '<?php echo site_url('os/gerarLinkAprovacao'); ?>',
                    dataType: 'json',
                    data: {
                        idOs: apIdOs,
                        dias: $('#apDias').val(),
                        exige_token: $('#apExigeToken').is(':checked') ? 1 : 0,
                        token_numeros: $('#apTokenNumeros').val() || ''
                    },
                    success: function(data) {
                        if (data.result) {
                            $('#apLink').val(data.url);
                            $('#apLinkBox').show();
                            apMontarWhats(data.url);
                            swal({
                                type: 'success',
                                title: 'Link gerado!',
                                text: 'Válido até ' + data.expira + '. Copie e envie ao cliente.'
                            });
                        } else {
                            swal({
                                type: 'error',
                                title: 'Atenção',
                                text: data.mensagem || 'Erro ao gerar o link.'
                            });
                        }
                    },
                    error: function(xhr) {
                        var m = (xhr.responseJSON && xhr.responseJSON.mensagem) ? xhr.responseJSON.mensagem : 'Erro ao gerar o link.';
                        swal({
                            type: 'error',
                            title: 'Atenção',
                            text: m
                        });
                    },
                    complete: function() {
                        btn.removeClass('disabled').prop('disabled', false);
                    }
                });
            });

            $('#apRevogar').on('click', function() {
                swal({
                    title: 'Revogar link?',
                    text: 'O link atual deixará de funcionar.',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, revogar',
                    cancelButtonText: 'Cancelar'
                }, function() {
                    $.ajax({
                        type: 'POST',
                        url: '<?php echo site_url('os/revogarLinkAprovacao'); ?>',
                        dataType: 'json',
                        data: {
                            idOs: apIdOs
                        },
                        success: function(data) {
                            if (data.result) {
                                $('#apLink').val('');
                                $('#apLinkBox').hide();
                                swal('Revogado!', 'O link foi desativado.', 'success');
                            }
                        }
                    });
                });
            });

            $('#apCopiar').on('click', function() {
                var input = document.getElementById('apLink');
                input.select();
                input.setSelectionRange(0, 99999);
                try {
                    document.execCommand('copy');
                    $(this).html('<i class="bx bx-check"></i> Copiado');
                } catch (e) {}
            });
        });
    </script>
<?php } ?>

<?php if (! empty($whatsappApiAtivo)) { ?>
    <script type="text/javascript">
        $(function() {
            function enviarWhatsApp(url, botao, textoEnviando) {
                var $btn = $(botao);
                var htmlOriginal = $btn.html();
                $btn.addClass('disabled').prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> ' + textoEnviando);
                $.ajax({
                    type: 'POST',
                    url: url,
                    dataType: 'json'
                }).done(function(data) {
                    swal({
                        type: data.result ? 'success' : 'error',
                        title: data.result ? 'Enviado!' : 'Atenção',
                        text: data.mensagem || ''
                    });
                }).fail(function(xhr) {
                    var m = (xhr.responseJSON && xhr.responseJSON.mensagem) ? xhr.responseJSON.mensagem : 'Falha ao enviar pelo WhatsApp.';
                    swal({ type: 'error', title: 'Atenção', text: m });
                }).always(function() {
                    $btn.removeClass('disabled').prop('disabled', false).html(htmlOriginal);
                });
            }

            // Notificação da OS via Evolution API.
            $('#enviarWhatsAppApi').on('click', function(e) {
                e.preventDefault();
                enviarWhatsApp('<?php echo site_url('whatsapp/enviarOs'); ?>/' + $(this).data('os'), this, 'Enviando...');
            });

            // Link de aprovação via Evolution API (envio direto, sem abrir o WhatsApp).
            $('#apWhats').attr('target', '').on('click', function(e) {
                e.preventDefault();
                enviarWhatsApp('<?php echo site_url('whatsapp/enviarLinkAprovacao'); ?>/<?php echo $result->idOs; ?>', this, 'Enviando...');
            });
        });
    </script>
<?php } ?>

<?php
// ------- Modal: Link de aceite do serviço realizado -------
if ($this->db->field_exists('aceite_token', 'os') && in_array($result->status, ['Finalizado', 'Faturado']) && $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
    $acToken = $result->aceite_token ?? null;
    $acStatus = $result->aceite_status ?? null;
    $acExpira = $result->aceite_expira ?? null;
    $acUrl = $acToken ? site_url('aceite/' . $acToken) : '';
?>
    <div id="modal-aceite" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4><i class="bx bx-badge-check"></i> Aceite do Serviço &mdash; OS #<?php echo $result->idOs; ?></h4>
        </div>
        <div class="modal-body">
            <p>Gere um link para o cliente confirmar o aceite do serviço realizado e assinar digitalmente. O link deixa de funcionar após a decisão do cliente ou ao expirar.</p>

            <?php if ($acStatus === 'aceito' || $acStatus === 'recusado') { ?>
                <div class="alert <?php echo $acStatus === 'aceito' ? 'alert-success' : 'alert-error'; ?>">
                    Serviço <?php echo $acStatus === 'aceito' ? 'ACEITO' : 'RECUSADO'; ?>
                    <?php echo $result->aceite_nome ? ' por ' . html_escape($result->aceite_nome) : ''; ?>
                    <?php echo $result->aceite_data ? ' em ' . date('d/m/Y H:i', strtotime($result->aceite_data)) : ''; ?>.
                    <?php echo (! empty($result->aceite_obs)) ? '<br>Motivo: ' . html_escape($result->aceite_obs) : ''; ?>
                </div>
            <?php } elseif ($acStatus === 'pendente') { ?>
                <div class="alert alert-info">Link ativo, aguardando o aceite do cliente<?php echo $acExpira ? '. Válido até ' . date('d/m/Y', strtotime($acExpira)) : ''; ?>.</div>
            <?php } ?>

            <div class="control-group">
                <label class="control-label" for="acDias">Validade do link (dias)</label>
                <div class="controls">
                    <input type="number" id="acDias" value="7" min="1" max="90" style="width:80px">
                </div>
            </div>

            <div id="acLinkBox" style="<?php echo $acUrl ? '' : 'display:none'; ?>">
                <label><strong>Link para o cliente</strong></label>
                <div class="input-append" style="width:100%">
                    <input type="text" id="acLink" readonly value="<?php echo html_escape($acUrl); ?>" style="width:75%">
                    <button class="btn" type="button" id="acCopiar"><i class="bx bx-copy"></i> Copiar</button>
                </div>
                <button id="acWhats" class="btn btn-success" style="color:#fff;margin-top:8px" type="button">
                    <i class="bx bxl-whatsapp"></i> Enviar por WhatsApp
                </button>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" data-dismiss="modal" aria-hidden="true" style="color:#fff">Fechar</button>
            <?php if ($acToken) { ?>
                <button class="btn" type="button" id="acRevogar" style="color:#fff;background:#8a6d3b">Revogar link</button>
            <?php } ?>
            <button class="btn btn-primary" type="button" id="acGerar" style="color:#fff">
                <i class="bx bx-link"></i> <?php echo $acToken ? 'Gerar novo link' : 'Gerar link'; ?>
            </button>
        </div>
    </div>

    <script type="text/javascript">
        $(function() {
            var acIdOs = "<?php echo $result->idOs; ?>";

            $('#acGerar').on('click', function() {
                var btn = $(this).addClass('disabled').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: '<?php echo site_url('os/gerarLinkAceite'); ?>',
                    dataType: 'json',
                    data: { idOs: acIdOs, dias: $('#acDias').val() }
                }).done(function(data) {
                    if (data.result) {
                        $('#acLink').val(data.url);
                        $('#acLinkBox').show();
                        swal({ type: 'success', title: 'Link gerado!', text: 'Válido até ' + data.expira + '.' });
                    } else {
                        swal({ type: 'error', title: 'Atenção', text: data.mensagem || 'Erro ao gerar o link.' });
                    }
                }).fail(function(xhr) {
                    var m = (xhr.responseJSON && xhr.responseJSON.mensagem) ? xhr.responseJSON.mensagem : 'Erro ao gerar o link.';
                    swal({ type: 'error', title: 'Atenção', text: m });
                }).always(function() {
                    btn.removeClass('disabled').prop('disabled', false);
                });
            });

            $('#acWhats').on('click', function() {
                var btn = $(this).addClass('disabled').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: '<?php echo site_url('whatsapp/enviarLinkAceite'); ?>/' + acIdOs,
                    dataType: 'json'
                }).done(function(data) {
                    swal({ type: data.result ? 'success' : 'error', title: data.result ? 'Enviado!' : 'Atenção', text: data.mensagem || '' });
                    if (data.result && data.url) { $('#acLink').val(data.url); $('#acLinkBox').show(); }
                }).fail(function(xhr) {
                    var m = (xhr.responseJSON && xhr.responseJSON.mensagem) ? xhr.responseJSON.mensagem : 'Falha ao enviar pelo WhatsApp.';
                    swal({ type: 'error', title: 'Atenção', text: m });
                }).always(function() {
                    btn.removeClass('disabled').prop('disabled', false);
                });
            });

            $('#acRevogar').on('click', function() {
                swal({
                    title: 'Revogar link?', text: 'O link atual deixará de funcionar.', type: 'warning',
                    showCancelButton: true, confirmButtonText: 'Sim, revogar', cancelButtonText: 'Cancelar'
                }, function() {
                    $.ajax({
                        type: 'POST', url: '<?php echo site_url('os/revogarLinkAceite'); ?>', dataType: 'json',
                        data: { idOs: acIdOs }
                    }).done(function(data) {
                        if (data.result) { $('#acLink').val(''); $('#acLinkBox').hide(); swal('Revogado!', 'O link foi desativado.', 'success'); }
                    });
                });
            });

            $('#acCopiar').on('click', function() {
                var input = document.getElementById('acLink');
                input.select();
                input.setSelectionRange(0, 99999);
                try { document.execCommand('copy'); $(this).html('<i class="bx bx-check"></i> Copiado'); } catch (e) {}
            });
        });
    </script>
<?php } ?>

<script src="https://cdn.rawgit.com/cozmo/jsQR/master/dist/jsQR.js"></script>
<script type="text/javascript">
    // Função auxiliar para carregamento assíncrono de conteúdo
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

    // Usa event delegation para melhor performance - registra uma única vez
    $(document).on('click', '.anexo', function(event) {
        event.preventDefault();
        var link = $(this).attr('link');
        var id = $(this).attr('imagem');
        var url = '<?php echo base_url(); ?>index.php/os/excluirAnexo/';
        $("#div-visualizar-anexo").html('<img src="' + link + '" alt="">');
        $("#excluir-anexo").attr('link', url + id);
        $("#download").attr('href', "<?php echo base_url(); ?>index.php/os/downloadanexo/" + id);
    });

    $(document).on('click', '#excluir-anexo', function(event) {
        event.preventDefault();

        var link = $(this).attr('link');
        var idOS = "<?php echo $result->idOs; ?>"

        $('#modal-anexo').modal('hide');
        $("#divAnexos").html("<div class='progress progress-info progress-striped active'><div class='bar' style='width: 100%'></div></div>");

        $.ajax({
            type: "POST",
            url: link,
            dataType: 'json',
            data: "idOs=" + idOS,
            success: function(data) {
                if (data.result == true) {
                    // Usa carregamento assíncrono em vez de .load() síncrono
                    loadContentAsync("#divAnexos", "<?php echo current_url(); ?>", "#divAnexos");
                } else {
                    swal({
                        type: "error",
                        title: "Atenção",
                        text: data.mensagem
                    });
                }
            }
        });
    });

    $('#copyButton').on('click', function() {
        var $qrCodeImage = $('#qrCodeImage');
        var canvas = document.createElement('canvas');
        canvas.width = $qrCodeImage.width();
        canvas.height = $qrCodeImage.height();
        var context = canvas.getContext('2d');
        context.drawImage($qrCodeImage[0], 0, 0, $qrCodeImage.width(), $qrCodeImage.height());
        var imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        var code = jsQR(imageData.data, imageData.width, imageData.height);
        if (code) {
            navigator.clipboard.writeText(code.data).then(function() {
                $('#modal-pix').modal('hide');
                swal({
                    type: "success",
                    title: "Sucesso!",
                    text: "QR Code copiado com sucesso: " + code.data,
                    icon: "success",
                    timer: 3000,
                    showConfirmButton: false,
                });

            }).catch(function(err) {
                swal({
                    type: "error",
                    title: "Atenção",
                    text: "Erro ao copiar QR Code: ",
                    err
                });
            });
        } else {
            swal({
                type: "error",
                title: "Atenção",
                text: "Não foi possível decodificar o QR Code.",
            });
        }
    });

    $('#pixWhatsApp').on('click', function() {
        var $qrCodeImage = $('#qrCodeImage');
        var canvas = document.createElement('canvas');
        canvas.width = $qrCodeImage.width();
        canvas.height = $qrCodeImage.height();
        var context = canvas.getContext('2d');
        context.drawImage($qrCodeImage[0], 0, 0, $qrCodeImage.width(), $qrCodeImage.height());
        var imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        var code = jsQR(imageData.data, imageData.width, imageData.height);
        if (code) {
            var whatsappLink = 'https://api.whatsapp.com/send?phone=55' + <?= isset($zapnumber) ? $zapnumber : "" ?> + '&text=' + code.data;
            window.open(whatsappLink, '_blank');
        } else {
            swal({
                type: "error",
                title: "Atenção",
                text: "Não foi possível decodificar o QR Code.",
            });
        }
    });

    // Lazy loading para CheckinManager - inicializa apenas quando necessário
    (function() {
        var checkinConfig = {
            baseUrl: '<?php echo base_url(); ?>',
            osId: <?php echo $result->idOs; ?>,
            debug: false
        };

        // Função para inicializar o CheckinManager sob demanda
        function initCheckinManager() {
            // Evita dupla inicialização
            if (typeof CheckinManager !== 'undefined' && CheckinManager._inicializado) {
                console.log('CheckinManager já inicializado, ignorando...');
                return;
            }

            // Inicializa CheckinFotos primeiro
            if (typeof CheckinFotos !== 'undefined') {
                CheckinFotos.init({ baseUrl: checkinConfig.baseUrl });
                console.log('CheckinFotos inicializado');
            } else {
                console.warn('CheckinFotos não está disponível');
            }

            // Inicializa CheckinManager
            if (typeof CheckinManager !== 'undefined') {
                CheckinManager._inicializado = true;
                CheckinManager.init(checkinConfig);
                console.log('CheckinManager inicializado');
            } else {
                console.warn('CheckinManager não está disponível');
            }

            // Formulários de atendimento personalizados
            if (typeof CheckinFormularios !== 'undefined') {
                CheckinFormularios.init({ baseUrl: checkinConfig.baseUrl, osId: checkinConfig.osId });
            }
        }

        // Adia a inicialização do CheckinManager para não bloquear o carregamento
        // Inicializa após o carregamento completo da página (baixa prioridade)
        if (document.readyState === 'complete') {
            setTimeout(initCheckinManager, 100);
        } else {
            window.addEventListener('load', function() {
                setTimeout(initCheckinManager, 100);
            });
        }

        // Também inicializa quando o usuário interage com botões de checkin (apenas uma vez)
        $(document).one('mouseenter click touchstart', '#btn-iniciar-atendimento, #btn-finalizar-atendimento', function() {
            initCheckinManager();
        });

        // Listener para atualizar fotos após upload (sem recarregar a página)
        $(document).on('checkin:fotosAtualizadas', function(e, fotos, etapa) {
            console.log('Fotos atualizadas para etapa:', etapa, 'Total:', fotos.length);
            // Recarrega a página parcialmente para mostrar as novas fotos
            // Usa location.reload() apenas se não estiver em um modal
            if (!$('.modal:visible').length) {
                window.location.reload();
            } else {
                // Se estiver em um modal, apenas mostra mensagem e atualiza quando fechar
                console.log('Modal está aberto, aguardando fechamento para recarregar');
                // Adiciona um listener para recarregar quando o modal for fechado
                $(document).one('hidden.bs.modal', '.modal', function() {
                    window.location.reload();
                });
            }
        });
    })();
</script>

<!-- CSS para o sistema de Check-in -->
<style>
/* Preview de fotos */
.preview-fotos-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

.preview-foto-item {
    position: relative;
    width: 100px;
    height: 100px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.preview-foto-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-foto-item .btn-remover-preview {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 24px;
    height: 24px;
    padding: 0;
    line-height: 1;
    background: rgba(255, 0, 0, 0.8);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
}

.preview-foto-item .btn-remover-preview:hover {
    background: rgba(255, 0, 0, 1);
}

/* Galeria de fotos */
.galeria-secao {
    margin-bottom: 20px;
}

.galeria-secao h5 {
    border-bottom: 1px solid #eee;
    padding-bottom: 8px;
    margin-bottom: 15px;
    color: #333;
}

.fotos-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.foto-item {
    position: relative;
    width: 150px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    background: #fff;
}

.foto-item img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    display: block;
}

.foto-descricao {
    font-size: 11px;
    padding: 5px;
    margin: 0;
    color: #666;
    word-wrap: break-word;
}

.foto-acoes {
    display: flex;
    justify-content: space-around;
    padding: 5px;
    background: #f5f5f5;
    border-top: 1px solid #eee;
}

.foto-acoes .btn {
    padding: 2px 8px;
}

/* Botões de upload */
.upload-area {
    border: 2px dashed #ccc;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    margin-bottom: 10px;
    background: #fafafa;
}

.upload-area:hover {
    border-color: #999;
    background: #f5f5f5;
}

/* Modal de checkin */
.modal-checkin .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.checkin-section {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.checkin-section:last-child {
    border-bottom: none;
}

.checkin-section h6 {
    color: #555;
    margin-bottom: 10px;
    font-weight: 600;
}

/* Hidden */
.hidden {
    display: none !important;
}

/* Responsividade para assinatura */
@media (max-width: 768px) {
    .assinatura-canvas-wrapper canvas {
        width: 100% !important;
        height: auto !important;
        min-height: 200px !important;
        touch-action: none !important;
    }

    /* Ajustes no modal para mobile */
    .modal-checkin {
        width: 100% !important;
        left: 0 !important;
        margin-left: 0 !important;
        top: 10px !important;
    }

    .modal-checkin .modal-body {
        max-height: 80vh !important;
        padding: 10px !important;
    }

    /* Layout de assinaturas em coluna no mobile */
    .modal-checkin .row-fluid .span6 {
        width: 100% !important;
        float: none !important;
        margin-left: 0 !important;
        margin-bottom: 15px;
    }

    /* Botões maiores para touch */
    .modal-checkin .btn {
        padding: 10px 15px !important;
        font-size: 14px !important;
        min-height: 44px;
    }

    /* Área de upload mais amigável */
    .modal-checkin .upload-area {
        padding: 20px !important;
    }

    .modal-checkin .upload-area button {
        display: block;
        width: 100%;
        margin-bottom: 10px;
    }
}

/* Fix para canvas em dispositivos touch */
.assinatura-canvas-wrapper {
    position: relative;
    overflow: hidden;
}

.assinatura-canvas-wrapper canvas {
    touch-action: none;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -khtml-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}
</style>

<!-- Modal de Check-in (Início do Atendimento) -->
<div id="modal-checkin" class="modal hide fade modal-checkin" tabindex="-1" role="dialog" aria-labelledby="modalCheckinLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="modalCheckinLabel"><i class="bx bx-log-in"></i> Iniciar Atendimento</h3>
    </div>
    <div class="modal-body">
        <form id="form-checkin">
            <!-- Assinatura do Técnico -->
            <div class="checkin-section">
                <h6><i class="bx bx-pen"></i> Assinatura do Técnico</h6>
                <?php $this->load->view('checkin/assinatura_canvas', [
                    'id' => 'assinatura-tecnico-entrada',
                    'titulo' => 'Assinatura do Técnico (Entrada)',
                    'mostrar_campos' => false
                ]); ?>
            </div>

            <!-- Fotos de Entrada -->
            <div class="checkin-section">
                <h6><i class="bx bx-camera"></i> Fotos de Entrada</h6>
                <div class="upload-area">
                    <input type="file" id="fotos-entrada-input" class="checkin-foto-input" data-etapa="entrada" accept="image/*" style="display: none;">
                    <button type="button" class="btn" onclick="document.getElementById('fotos-entrada-input').click()">
                        <i class="bx bx-upload"></i> Selecionar Fotos
                    </button>
                    <button type="button" class="btn btn-info btn-capturar-foto" data-etapa="entrada">
                        <i class="bx bx-camera"></i> Tirar Foto
                    </button>
                    <p class="text-muted" style="margin-top: 10px; margin-bottom: 0;">Máximo 5MB por foto (JPG, PNG)</p>
                </div>
                <div id="preview-fotos-entrada" class="preview-fotos-container"></div>
            </div>

            <!-- Observações -->
            <div class="checkin-section">
                <h6><i class="bx bx-note"></i> Observações de Entrada</h6>
                <textarea id="checkin-observacao" class="span12" rows="3" placeholder="Descreva o estado inicial, equipamentos recebidos, etc."></textarea>
            </div>

            <!-- Geolocalização -->
            <div class="checkin-section">
                <h6><i class="bx bx-map"></i> Localização</h6>
                <input type="hidden" id="checkin-latitude">
                <input type="hidden" id="checkin-longitude">
                <button type="button" id="btn-geo-checkin" class="btn btn-small">
                    <i class="bx bx-map"></i> Capturar Localização
                </button>
                <span id="checkin-geo-status" class="text-muted" style="margin-left: 10px;"></span>
            </div>
            <div id="formularios-iniciar" class="checkin-section"></div>
        </form>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
        <button class="btn btn-success" id="btn-confirmar-checkin">
            <i class="bx bx-log-in"></i> Iniciar Atendimento
        </button>
    </div>
</div>

<!-- Modal de Confirmação Mobile (Tela Cheia) - Abre antes do checkout -->
<div id="modal-mobile-confirmacao" class="modal hide fade modal-mobile-fullscreen" tabindex="-1" role="dialog" aria-labelledby="modalMobileConfirmacaoLabel" aria-hidden="true">
    <div class="modal-header modal-mobile-header">
        <h3 id="modalMobileConfirmacaoLabel"><i class="bx bx-mobile"></i> Finalizar Atendimento - Mobile</h3>
    </div>
    <div class="modal-body modal-mobile-body">
        <!-- Dica de orientação - não bloqueia o uso -->
        <div class="mobile-orientation-msg">
            <i class='bx bx-rotate' style="font-size: 32px;"></i>
            <p><strong>Dica:</strong> Gire o dispositivo para modo paisagem (horizontal) para melhor visualização</p>
        </div>

        <div class="mobile-form-content">
            <!-- Resumo da OS -->
            <div class="mobile-os-info">
                <h4><i class="bx bx-file"></i> OS #<?= sprintf('%04d', $result->idOs) ?></h4>
                <p><strong>Cliente:</strong> <?= $result->nomeCliente ?></p>
                <p><strong>Status:</strong> <?= $result->status ?></p>
            </div>

            <!-- checklist de verificação -->
            <div class="mobile-checklist">
                <h5><i class="bx bx-check-square"></i> Checklist de Finalização</h5>

                <label class="mobile-checkbox-item">
                    <input type="checkbox" id="mobile-check-servico" class="mobile-check-item">
                    <span class="checkmark"></span>
                    <span class="label-text">Serviço foi realizado</span>
                </label>

                <label class="mobile-checkbox-item">
                    <input type="checkbox" id="mobile-check-testes" class="mobile-check-item">
                    <span class="checkmark"></span>
                    <span class="label-text">Testes realizados</span>
                </label>

                <label class="mobile-checkbox-item">
                    <input type="checkbox" id="mobile-check-cliente" class="mobile-check-item">
                    <span class="checkmark"></span>
                    <span class="label-text">Cliente foi informado</span>
                </label>

                <label class="mobile-checkbox-item">
                    <input type="checkbox" id="mobile-check-limpeza" class="mobile-check-item">
                    <span class="checkmark"></span>
                    <span class="label-text">Área de trabalho limpa</span>
                </label>
            </div>

            <!-- Observações rápidas -->
            <div class="mobile-observacoes">
                <h5><i class="bx bx-note"></i> Observações do Técnico</h5>
                <textarea id="mobile-observacao-rapida" class="mobile-textarea" rows="3" placeholder="Descreva brevemente o serviço realizado..."></textarea>
            </div>

            <!-- Assinatura rápida do técnico -->
            <div class="mobile-assinatura-preview">
                <h5><i class="bx bx-pen"></i> Confirmação do Técnico</h5>
                <div class="assinatura-aviso">
                    <i class="bx bx-info-circle"></i>
                    <span>As assinaturas do técnico e cliente serão coletadas na próxima etapa</span>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer modal-mobile-footer">
        <button class="btn btn-large" data-dismiss="modal" aria-hidden="true">
            <i class="bx bx-x"></i> Cancelar
        </button>
        <button class="btn btn-success btn-large" id="btn-salvar-mobile-confirmacao">
            <i class="bx bx-save"></i> Salvar e Continuar
        </button>
    </div>
</div>

<style>
/* Estilos do Modal Mobile Fullscreen */
.modal-mobile-fullscreen {
    width: 100% !important;
    height: 100% !important;
    max-height: 100% !important;
    margin: 0 !important;
    top: 0 !important;
    left: 0 !important;
    border-radius: 0 !important;
}

.modal-mobile-fullscreen .modal-body {
    max-height: calc(100vh - 120px) !important;
    height: calc(100vh - 120px) !important;
    overflow-y: auto;
    padding: 20px;
    background: #f5f6fa;
}

/* Orientação msg - mostra em modo retrato */
.mobile-orientation-msg {
    display: none;
    text-align: center;
    padding: 20px;
    color: #667eea;
}

/* Mensagem de orientação - apenas como dica, não bloqueia uso */
@media screen and (orientation: portrait) and (max-width: 768px) {
    .mobile-orientation-msg {
        display: block;
        background: #e3f2fd;
        border-radius: 8px;
        margin-bottom: 15px;
        border: 1px solid #1976d2;
    }
}

/* Ajustes para checklist em modo retrato */
@media screen and (max-width: 768px) {
    .mobile-checkbox-item {
        padding: 15px 0;
        min-height: 50px;
    }

    .mobile-checkbox-item input[type="checkbox"] {
        width: 28px;
        height: 28px;
        min-width: 28px;
        min-height: 28px;
        margin-right: 12px;
    }

    .mobile-checkbox-item .label-text {
        font-size: 16px;
        line-height: 1.4;
    }

    /* Garante que o formulário mobile seja clicável */
    .mobile-form-content {
        pointer-events: auto !important;
    }

    /* Aumenta área de toque dos elementos */
    .mobile-checkbox-item,
    .modal-mobile-footer .btn,
    .mobile-textarea {
        touch-action: manipulation;
        -webkit-tap-highlight-color: rgba(0,0,0,0.1);
    }
}

/* Estilos do conteúdo mobile */
.mobile-os-info {
    background: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.mobile-os-info h4 {
    margin: 0 0 10px 0;
    color: #667eea;
}

.mobile-os-info p {
    margin: 5px 0;
    color: #666;
}

/* Checklist mobile */
.mobile-checklist {
    background: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.mobile-checklist h5 {
    margin: 0 0 15px 0;
    color: #333;
}

.mobile-checkbox-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
    cursor: pointer;
}

.mobile-checkbox-item:last-child {
    border-bottom: none;
}

.mobile-checkbox-item input[type="checkbox"] {
    width: 24px;
    height: 24px;
    margin-right: 15px;
    cursor: pointer;
}

.mobile-checkbox-item .label-text {
    font-size: 16px;
    color: #333;
}

/* Observações mobile */
.mobile-observacoes {
    background: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.mobile-observacoes h5 {
    margin: 0 0 10px 0;
    color: #333;
}

.mobile-textarea {
    width: 100%;
    padding: 10px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    resize: vertical;
}

.mobile-textarea:focus {
    border-color: #667eea;
    outline: none;
}

/* Assinatura preview mobile */
.mobile-assinatura-preview {
    background: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.mobile-assinatura-preview h5 {
    margin: 0 0 10px 0;
    color: #333;
}

.assinatura-aviso {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #e3f2fd;
    border-radius: 8px;
    color: #1976d2;
}

/* Footer mobile */
.modal-mobile-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 15px;
    background: white;
    border-top: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
}

.modal-mobile-footer .btn {
    padding: 12px 30px;
    font-size: 16px;
}

/* Header mobile */
.modal-mobile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
}

.modal-mobile-header h3 {
    margin: 0;
    color: white;
}

.modal-mobile-header .close {
    color: white;
    opacity: 0.8;
}
</style>

<!-- Modal de Check-out (Finalização do Atendimento) -->
<div id="modal-checkout" class="modal hide fade modal-checkin" tabindex="-1" role="dialog" aria-labelledby="modalCheckoutLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="modalCheckoutLabel"><i class="bx bx-log-out"></i> Finalizar Atendimento</h3>
    </div>
    <div class="modal-body">
        <form id="form-checkout">
            <!-- Assinaturas -->
            <div class="checkin-section">
                <h6><i class="bx bx-pen"></i> Assinaturas</h6>
                <div class="row-fluid">
                    <div class="span6">
                        <?php $this->load->view('checkin/assinatura_canvas', [
                            'id' => 'assinatura-tecnico-saida',
                            'titulo' => 'Assinatura do Técnico (Saída)',
                            'mostrar_campos' => false
                        ]); ?>
                    </div>
                    <div class="span6">
                        <?php $this->load->view('checkin/assinatura_canvas', [
                            'id' => 'assinatura-cliente-saida',
                            'titulo' => 'Assinatura do Cliente',
                            'mostrar_campos' => true,
                            'campos' => ['nome' => true, 'documento' => true]
                        ]); ?>
                    </div>
                </div>
            </div>

            <!-- Fotos da Saída -->
            <div class="checkin-section">
                <h6><i class="bx bx-camera"></i> Fotos da Saída</h6>
                <div class="upload-area">
                    <input type="file" id="fotos-saida-input" class="checkin-foto-input" data-etapa="saida" accept="image/*" style="display: none;">
                    <button type="button" class="btn" onclick="document.getElementById('fotos-saida-input').click()">
                        <i class="bx bx-upload"></i> Selecionar Fotos
                    </button>
                    <button type="button" class="btn btn-info btn-capturar-foto" data-etapa="saida">
                        <i class="bx bx-camera"></i> Tirar Foto
                    </button>
                    <p class="text-muted" style="margin-top: 10px; margin-bottom: 0;">Máximo 5MB por foto (JPG, PNG)</p>
                </div>
                <div id="preview-fotos-saida" class="preview-fotos-container"></div>
            </div>

            <!-- Fotos Durante (Visualização) -->
            <div class="checkin-section">
                <h6><i class="bx bx-images"></i> Fotos do Atendimento</h6>
                <div id="lista-fotos-durante">
                    <p class="text-muted">Carregando fotos...</p>
                </div>
            </div>

            <!-- Observações -->
            <div class="checkin-section">
                <h6><i class="bx bx-note"></i> Observações de Saída</h6>
                <textarea id="checkout-observacao" class="span12" rows="3" placeholder="Descreva o serviço realizado, peças trocadas, recomendações, etc."></textarea>
            </div>

            <!-- Geolocalização -->
            <div class="checkin-section">
                <h6><i class="bx bx-map"></i> Localização de Saída</h6>
                <input type="hidden" id="checkout-latitude">
                <input type="hidden" id="checkout-longitude">
                <button type="button" id="btn-geo-checkout" class="btn btn-small">
                    <i class="bx bx-map"></i> Capturar Localização
                </button>
                <span id="checkout-geo-status" class="text-muted" style="margin-left: 10px;"></span>
            </div>
            <div id="formularios-durante" class="checkin-section"></div>
            <div id="formularios-finalizar" class="checkin-section"></div>
        </form>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
        <button class="btn btn-danger" id="btn-confirmar-checkout">
            <i class="bx bx-log-out"></i> Finalizar Atendimento
        </button>
    </div>
</div>

<!-- Script para inicializar assinaturas nos modais -->
<script>
// Lazy loading para inicialização de assinaturas - só executa quando os modais são abertos
(function() {
    'use strict';

    // Flag para controlar se os listeners já foram registrados
    var listenersRegistrados = false;

    // Detecta se é mobile
    function isMobile() {
        return window.innerWidth <= 768 || 'ontouchstart' in window;
    }

    // Função para inicializar assinatura com retry
    function inicializarAssinatura(id, canvasId, tentativas) {
        tentativas = tentativas || 0;
        if (tentativas > 10) {
            console.error('Falha ao inicializar assinatura ' + id + ' após 10 tentativas');
            return;
        }

        // Verifica se AssinaturaManager está disponível
        if (typeof AssinaturaManager === 'undefined') {
            console.error('AssinaturaManager não está carregado');
            return;
        }

        var canvas = document.getElementById(canvasId);
        if (!canvas) {
            // Canvas ainda não está no DOM, tenta novamente
            setTimeout(function() {
                inicializarAssinatura(id, canvasId, tentativas + 1);
            }, 100);
            return;
        }

        // Canvas encontrado, calcula dimensões responsivas
        var container = canvas.parentElement;
        var isMobileDevice = isMobile();
        var largura = isMobileDevice ? (container.clientWidth - 20) : 400;
        var altura = isMobileDevice ? 250 : 150;

        // Garante dimensões mínimas
        if (largura < 280) largura = 280;

        // Cria a assinatura com dimensões apropriadas
        try {
            AssinaturaManager.criar(id, canvasId, {
                cor: '#000000',
                espessura: isMobileDevice ? 3 : 2,
                largura: largura,
                altura: altura
            });
            console.log('Assinatura ' + id + ' inicializada com sucesso (mobile: ' + isMobileDevice + ', dimensões: ' + largura + 'x' + altura + ')');
        } catch (e) {
            console.error('Erro ao criar assinatura ' + id + ':', e);
        }
    }

    // Registra listeners de eventos - usando event delegation para melhor performance
    function registrarListeners() {
        if (listenersRegistrados) return;
        listenersRegistrados = true;

        // Salva as respostas dos formulários ao confirmar cada etapa (independente do check-in).
        $(document).on('click', '#btn-confirmar-checkin', function() {
            if (typeof CheckinFormularios !== 'undefined') {
                CheckinFormularios.salvar('iniciar', '#formularios-iniciar', checkinConfig.osId, '')
                    .fail(function(msg) { if (msg) { console.warn(msg); } });
            }
        });
        $(document).on('click', '#btn-confirmar-checkout', function() {
            if (typeof CheckinFormularios !== 'undefined') {
                CheckinFormularios.salvar('durante', '#formularios-durante', checkinConfig.osId, '')
                    .fail(function(msg) { if (msg) { console.warn(msg); } });
                CheckinFormularios.salvar('finalizar', '#formularios-finalizar', checkinConfig.osId, '')
                    .fail(function(msg) { if (msg) { console.warn(msg); } });
            }
        });

        // Inicializa assinaturas quando o modal de checkin é aberto
        $(document).on('shown.bs.modal', '#modal-checkin', function() {
            console.log('Modal checkin aberto');
            if (typeof CheckinFormularios !== 'undefined') {
                CheckinFormularios.carregar('iniciar', '#formularios-iniciar', checkinConfig.osId);
            }

            // Delay maior para mobile garantir que o modal esteja totalmente renderizado
            var delay = isMobile() ? 300 : 100;
            setTimeout(function() {
                if (typeof AssinaturaManager !== 'undefined') {
                    var assinaturaEntrada = AssinaturaManager.obter('assinatura-tecnico-entrada');
                    if (!assinaturaEntrada) {
                        inicializarAssinatura('assinatura-tecnico-entrada', 'assinatura-tecnico-entrada-canvas', 0);
                    } else {
                        // Reajusta o tamanho se já existir
                        assinaturaEntrada._ajustarTamanhoCanvas();
                    }
                }
            }, delay);
        });

        // Inicializa assinaturas quando o modal de checkout é aberto
        $(document).on('shown.bs.modal', '#modal-checkout', function() {
            console.log('Modal checkout aberto');
            if (typeof CheckinFormularios !== 'undefined') {
                CheckinFormularios.carregar('durante', '#formularios-durante', checkinConfig.osId);
                CheckinFormularios.carregar('finalizar', '#formularios-finalizar', checkinConfig.osId);
            }

            // Pré-preenche o nome do cliente com os dados da OS
            var nomeCliente = '<?php echo addslashes($result->nomeCliente); ?>';
            var documentoCliente = '<?php echo addslashes($result->documento ?? $result->cpf ?? ''); ?>';

            if (nomeCliente && $('#assinatura-cliente-saida-nome').length) {
                $('#assinatura-cliente-saida-nome').val(nomeCliente).prop('readonly', true);
                $('#assinatura-cliente-saida-nome').attr('title', 'Nome vinculado à OS');
            }
            if (documentoCliente && $('#assinatura-cliente-saida-documento').length) {
                $('#assinatura-cliente-saida-documento').val(documentoCliente).prop('readonly', true);
                $('#assinatura-cliente-saida-documento').attr('title', 'Documento vinculado à OS');
            }

            // Delay maior para mobile garantir que o modal esteja totalmente renderizado
            var delay = isMobile() ? 300 : 100;
            setTimeout(function() {
                if (typeof AssinaturaManager !== 'undefined') {
                    // Assinatura do técnico na saída
                    var assinaturaTecnicoSaida = AssinaturaManager.obter('assinatura-tecnico-saida');
                    if (!assinaturaTecnicoSaida) {
                        inicializarAssinatura('assinatura-tecnico-saida', 'assinatura-tecnico-saida-canvas', 0);
                    } else {
                        assinaturaTecnicoSaida._ajustarTamanhoCanvas();
                    }
                    // Assinatura do cliente
                    var assinaturaCliente = AssinaturaManager.obter('assinatura-cliente-saida');
                    if (!assinaturaCliente) {
                        inicializarAssinatura('assinatura-cliente-saida', 'assinatura-cliente-saida-canvas', 0);
                    } else {
                        assinaturaCliente._ajustarTamanhoCanvas();
                    }
                }
            }, delay);
        });

        // ESCUTA O EVENTO assinatura-canvas-pronto
        $(document).on('assinatura-canvas-pronto', function(event, containerId, canvasId) {
            console.log('Evento assinatura-canvas-pronto recebido:', containerId, canvasId);

            if (typeof AssinaturaManager === 'undefined') {
                console.error('AssinaturaManager não está disponível');
                return;
            }

            // Verifica se já existe uma instância para este container
            var assinaturaExistente = AssinaturaManager.obter(containerId);
            if (assinaturaExistente) {
                console.log('Assinatura já existe para:', containerId);
                return;
            }

            // Usa requestAnimationFrame para não bloquear a thread principal
            requestAnimationFrame(function() {
                var canvas = document.getElementById(canvasId);
                if (!canvas) {
                    console.error('Canvas não encontrado:', canvasId);
                    return;
                }

                // Calcula dimensões responsivas
                var container = canvas.parentElement;
                var isMobileDevice = isMobile();
                var largura = isMobileDevice ? (container.clientWidth - 20) : 400;
                var altura = isMobileDevice ? 250 : 150;

                if (largura < 280) largura = 280;

                try {
                    AssinaturaManager.criar(containerId, canvasId, {
                        cor: '#000000',
                        espessura: isMobileDevice ? 3 : 2,
                        largura: largura,
                        altura: altura
                    });
                    console.log('Assinatura criada com sucesso via evento:', containerId, '(mobile:', isMobileDevice + ')');
                } catch (e) {
                    console.error('Erro ao criar assinatura via evento:', e);
                }
            });
        });
    }

    // Registra listeners imediatamente (leve, não bloqueia)
    registrarListeners();

    // Verificação lazy do AssinaturaManager - não bloqueia o carregamento
    $(function() {
        // Usa setTimeout para não bloquear o ready event
        setTimeout(function() {
            if (typeof AssinaturaManager === 'undefined') {
                console.warn('Aviso: AssinaturaManager não está carregado. Verifique se assinatura-canvas.js está incluído.');
            } else {
                console.log('AssinaturaManager carregado com sucesso');
            }
        }, 0);
    });
})();

/**
 * CONTROLE DE FLUXO DE MODAIS MOBILE
 * Intercepta o botão de finalizar atendimento para abrir modal mobile primeiro
 */
(function() {
    'use strict';

    var mobileModalAberto = false;
    var dadosMobileConfirmacao = {};

    // Detectar se é dispositivo móvel
    function isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 768;
    }

    // Sobrescreve o método abrirModalCheckout do CheckinManager
    $(document).ready(function() {
        if (typeof CheckinManager !== 'undefined') {
            // Salva referência ao método original
            var abrirModalCheckoutOriginal = CheckinManager.abrirModalCheckout.bind(CheckinManager);

            // Substitui o método
            CheckinManager.abrirModalCheckout = function() {
                // Se for mobile e modal de confirmação ainda não foi aberto
                if (isMobile() && !mobileModalAberto) {
                    abrirModalMobileConfirmacao();
                } else {
                    // Desktop ou já passou pelo mobile, abre modal normal
                    abrirModalCheckoutOriginal();
                }
            };

            console.log('CheckinManager modificado para fluxo mobile');
        }

        // Handler para o botão Salvar do modal mobile
        $(document).on('click', '#btn-salvar-mobile-confirmacao', function(e) {
            e.preventDefault();

            // Valida se pelo menos um checkbox foi marcado
            var checksMarcados = $('.mobile-check-item:checked').length;
            if (checksMarcados === 0) {
                alert('Por favor, marque pelo menos um item do checklist');
                return;
            }

            // Salva os dados do formulário mobile
            dadosMobileConfirmacao = {
                checklist: {
                    servico: $('#mobile-check-servico').is(':checked'),
                    testes: $('#mobile-check-testes').is(':checked'),
                    cliente: $('#mobile-check-cliente').is(':checked'),
                    limpeza: $('#mobile-check-limpeza').is(':checked')
                },
                observacao: $('#mobile-observacao-rapida').val()
            };

            // Preenche automaticamente o campo de observações do checkout
            var observacaoCompleta = dadosMobileConfirmacao.observacao;
            var checklistTexto = [];

            if (dadosMobileConfirmacao.checklist.servico) checklistTexto.push('Serviço realizado');
            if (dadosMobileConfirmacao.checklist.testes) checklistTexto.push('Testes realizados');
            if (dadosMobileConfirmacao.checklist.cliente) checklistTexto.push('Cliente informado');
            if (dadosMobileConfirmacao.checklist.limpeza) checklistTexto.push('Área limpa');

            if (checklistTexto.length > 0) {
                observacaoCompleta = observacaoCompleta + '\n\nChecklist:\n- ' + checklistTexto.join('\n- ');
            }

            // Preenche o campo de observação do checkout se estiver vazio
            if ($('#checkout-observacao').val() === '') {
                $('#checkout-observacao').val(observacaoCompleta.trim());
            }

            // Marca que já passou pelo modal mobile
            mobileModalAberto = true;

            // Fecha modal mobile e abre modal de checkout
            $('#modal-mobile-confirmacao').modal('hide');

            // Aguarda o fechamento do modal mobile antes de abrir o checkout
            setTimeout(function() {
                // Abre o modal de checkout diretamente (sem verificar mobile novamente)
                $('#modal-checkout').modal('show');

                // Limpa os campos do formulário (exceto observação que já foi preenchida)
                $('#checkout-latitude, #checkout-longitude').val('');
                $('#checkout-geo-status').text('');

                // Limpa as assinaturas ao abrir o modal
                if (typeof AssinaturaManager !== 'undefined') {
                    const assinaturaTecnico = AssinaturaManager.obter('assinatura-tecnico-saida');
                    const assinaturaCliente = AssinaturaManager.obter('assinatura-cliente-saida');
                    if (assinaturaTecnico) assinaturaTecnico.limpar();
                    if (assinaturaCliente) assinaturaCliente.limpar();
                }

                // Limpa previews de fotos
                $('#preview-fotos-saida').empty();

            }, 300);
        });

        // Reseta o flag quando o modal mobile é fechado pelo botão cancelar
        $('#modal-mobile-confirmacao').on('hidden.bs.modal', function() {
            if (!mobileModalAberto) {
                // Se fechou sem salvar, reseta os dados
                dadosMobileConfirmacao = {};
            }
        });

        // Reseta o flag quando o modal checkout é fechado (finalizado ou cancelado)
        $('#modal-checkout').on('hidden.bs.modal', function() {
            mobileModalAberto = false;
            dadosMobileConfirmacao = {};
            // Limpa os checkboxes do mobile
            $('.mobile-check-item').prop('checked', false);
            $('#mobile-observacao-rapida').val('');
        });
    });

    function abrirModalMobileConfirmacao() {
        console.log('Abrindo modal mobile de confirmação');

        // Preenche informações da OS
        var osNumero = '<?php echo sprintf('%04d', $result->idOs); ?>';
        var clienteNome = '<?php echo addslashes($result->nomeCliente); ?>';

        // Abre o modal mobile
        $('#modal-mobile-confirmacao').modal('show');

        // Em dispositivos móveis, força orientação horizontal se possível
        if (screen.orientation && screen.orientation.lock) {
            screen.orientation.lock('landscape').catch(function(e) {
                console.log('Não foi possível travar a orientação:', e);
            });
        }
    }

    // Libera a orientação quando o modal mobile é fechado
    $('#modal-mobile-confirmacao').on('hidden.bs.modal', function() {
        if (screen.orientation && screen.orientation.unlock) {
            screen.orientation.unlock();
        }
    });

})();
</script>

<?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eNfe')) {
    echo $this->load->view('os/_modal_emissao_nfse', [], true);
    echo $this->load->view('os/_modal_emissao_nfe', ['configNfe' => (isset($configNfe) ? $configNfe : null)], true);
} ?>
