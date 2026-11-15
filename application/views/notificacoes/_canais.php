<?php
/**
 * Aba Canais da Central de Notificacoes.
 *
 * Credenciais de e-mail (SMTP) e de WhatsApp (Evolution API), intervalo do
 * disparo automatico e os avisos automaticos de OS que antes ficavam na aba
 * Notificacoes de Configuracoes.
 *
 * Variaveis: $smtp, $whatsapp, $intervalo, $osNotification, $emailAutomatico,
 * $notificaWhats, $podeSistema, $envDisponivel
 */
$e = function ($v) {
    return html_escape((string) ($v ?? ''));
};
$tokenName = $this->security->get_csrf_token_name();
$tokenHash = $this->security->get_csrf_hash();

$emailPronto = trim((string) ($smtp['EMAIL_SMTP_HOST'] ?? '')) !== ''
    && trim((string) ($smtp['EMAIL_SMTP_USER'] ?? '')) !== '';
$wppPronto = trim((string) ($whatsapp['WHATSAPP_EVOLUTION_URL'] ?? '')) !== ''
    && trim((string) ($whatsapp['WHATSAPP_EVOLUTION_APIKEY'] ?? '')) !== '';
$wppLigado = notif_env_booleano($whatsapp['WHATSAPP_EVOLUTION_ENABLED'] ?? 'false');
$envExiste = (bool) ($envDisponivel ?? is_file(env_arquivo()));
$editavel = $podeSistema && $envExiste;
?>

<?php if (! $podeSistema) { ?>
    <div class="ui-dica alerta">
        <i class="bx bx-lock-alt"></i>
        <div>Seu perfil pode consultar os canais, mas só quem configura o sistema pode alterar credenciais e disparar testes.</div>
    </div>
<?php } elseif (! $envExiste) { ?>
    <div class="ui-dica alerta">
        <i class="bx bx-error-circle"></i>
        <div>
            O arquivo <code>application/.env</code> não existe nesta instalação, então as credenciais não podem ser
            gravadas por aqui. Conclua a instalação (ou crie o .env) e volte. Os demais ajustes continuam funcionando.
        </div>
    </div>
<?php } ?>

<div class="nt-canais">

    <section class="nt-canal">
        <div class="nt-canal-cab">
            <i class="bx bx-envelope"></i>
            <span class="nt-canal-nome">E-mail (SMTP)</span>
            <span class="nt-canal-estado ui-selo <?= $emailPronto ? 'sucesso' : 'neutro' ?>"><?= $emailPronto ? 'configurado' : 'sem servidor' ?></span>
        </div>
        <div class="nt-canal-corpo">
            <?php if ($editavel) { ?>
            <form action="<?= site_url('notificacoes/salvarEmail') ?>" method="post">
                <input type="hidden" name="<?= $tokenName ?>" value="<?= $tokenHash ?>">
                <div class="nt-grade">
                    <div class="mb-3">
                        <label for="EMAIL_PROTOCOL">Protocolo</label>
                        <select class="form-select" id="EMAIL_PROTOCOL" name="EMAIL_PROTOCOL">
                            <option value="smtp" <?= ($smtp['EMAIL_PROTOCOL'] ?? '') === 'smtp' ? 'selected' : '' ?>>smtp</option>
                            <option value="mail" <?= ($smtp['EMAIL_PROTOCOL'] ?? '') === 'mail' ? 'selected' : '' ?>>mail (função do servidor)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="EMAIL_SMTP_HOST">Servidor (host)</label>
                        <input class="form-control" type="text" id="EMAIL_SMTP_HOST" name="EMAIL_SMTP_HOST" placeholder="smtp.suaempresa.com" value="<?= $e($smtp['EMAIL_SMTP_HOST'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="EMAIL_SMTP_CRYPTO">Criptografia</label>
                        <?php $cripto = (string) ($smtp['EMAIL_SMTP_CRYPTO'] ?? 'tls'); ?>
                        <select class="form-select" id="EMAIL_SMTP_CRYPTO" name="EMAIL_SMTP_CRYPTO">
                            <option value="tls" <?= $cripto === 'tls' ? 'selected' : '' ?>>tls (porta 587)</option>
                            <option value="ssl" <?= $cripto === 'ssl' ? 'selected' : '' ?>>ssl (porta 465)</option>
                            <option value="" <?= ($cripto === '' || $cripto === 'none') ? 'selected' : '' ?>>nenhuma (porta 25)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="EMAIL_SMTP_PORT">Porta</label>
                        <input class="form-control" type="text" id="EMAIL_SMTP_PORT" name="EMAIL_SMTP_PORT" inputmode="numeric" placeholder="587" value="<?= $e($smtp['EMAIL_SMTP_PORT'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="EMAIL_SMTP_USER">Usuário</label>
                        <input class="form-control" type="text" id="EMAIL_SMTP_USER" name="EMAIL_SMTP_USER" autocomplete="off" placeholder="conta@suaempresa.com" value="<?= $e($smtp['EMAIL_SMTP_USER'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="EMAIL_SMTP_PASS">Senha</label>
                        <input class="form-control" type="password" id="EMAIL_SMTP_PASS" name="EMAIL_SMTP_PASS" autocomplete="new-password" placeholder="deixe em branco para manter a atual" value="">
                    </div>
                </div>

                <div class="nt-teste">
                    <button type="submit" class="button btn btn-success">
                        <span class="button__icon"><i class="bx bx-save"></i></span>
                        <span class="button__text2">Salvar e-mail</span>
                    </button>
                    <input class="form-control" type="email" id="nt_teste_email" placeholder="e-mail para receber o teste">
                    <button type="button" class="button btn btn-dark" id="nt_testar_email" data-url="<?= site_url('mapos/testarEmailAnexo') ?>">
                        <span class="button__icon"><i class="bx bx-paper-plane"></i></span>
                        <span class="button__text2">Enviar teste</span>
                    </button>
                    <span class="nt-teste-saida" id="nt_testar_email_saida"></span>
                </div>
                <p class="form-text ui-m0 ui-mt">O teste usa as credenciais já salvas. A senha só é trocada quando você digita uma nova.</p>
            </form>
            <?php } else { ?>
                <p class="ui-m0">Servidor: <?= $e($smtp['EMAIL_SMTP_HOST'] ?? '—') ?><br>Usuário: <?= $e($smtp['EMAIL_SMTP_USER'] ?? '—') ?></p>
            <?php } ?>
        </div>
    </section>

    <section class="nt-canal">
        <div class="nt-canal-cab">
            <i class="bx bxl-whatsapp"></i>
            <span class="nt-canal-nome">WhatsApp (Evolution API)</span>
            <span class="nt-canal-estado ui-selo <?= $wppPronto && $wppLigado ? 'sucesso' : 'neutro' ?>">
                <?= $wppPronto ? ($wppLigado ? 'ligado' : 'desligado') : 'sem credenciais' ?>
            </span>
        </div>
        <div class="nt-canal-corpo">
            <?php if ($editavel) { ?>
            <form action="<?= site_url('notificacoes/salvarWhatsapp') ?>" method="post">
                <input type="hidden" name="<?= $tokenName ?>" value="<?= $tokenHash ?>">
                <div class="nt-grade">
                    <div class="mb-3">
                        <label for="WHATSAPP_EVOLUTION_ENABLED">Envio automático</label>
                        <select class="form-select" id="WHATSAPP_EVOLUTION_ENABLED" name="WHATSAPP_EVOLUTION_ENABLED">
                            <option value="true" <?= $wppLigado ? 'selected' : '' ?>>Ativado</option>
                            <option value="false" <?= ! $wppLigado ? 'selected' : '' ?>>Desativado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="WHATSAPP_EVOLUTION_URL">URL da instância</label>
                        <input class="form-control" type="text" id="WHATSAPP_EVOLUTION_URL" name="WHATSAPP_EVOLUTION_URL" placeholder="https://evo.suaempresa.com" value="<?= $e($whatsapp['WHATSAPP_EVOLUTION_URL'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="WHATSAPP_EVOLUTION_APIKEY">API Key</label>
                        <input class="form-control" type="password" id="WHATSAPP_EVOLUTION_APIKEY" name="WHATSAPP_EVOLUTION_APIKEY" autocomplete="new-password" value="<?= $e($whatsapp['WHATSAPP_EVOLUTION_APIKEY'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="WHATSAPP_EVOLUTION_INSTANCE">Instância</label>
                        <input class="form-control" type="text" id="WHATSAPP_EVOLUTION_INSTANCE" name="WHATSAPP_EVOLUTION_INSTANCE" value="<?= $e($whatsapp['WHATSAPP_EVOLUTION_INSTANCE'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="WHATSAPP_EVOLUTION_VERIFY_SSL">Verificar certificado</label>
                        <?php $ssl = notif_env_booleano($whatsapp['WHATSAPP_EVOLUTION_VERIFY_SSL'] ?? 'true'); ?>
                        <select class="form-select" id="WHATSAPP_EVOLUTION_VERIFY_SSL" name="WHATSAPP_EVOLUTION_VERIFY_SSL">
                            <option value="true" <?= $ssl ? 'selected' : '' ?>>Sim (recomendado)</option>
                            <option value="false" <?= ! $ssl ? 'selected' : '' ?>>Não (aceitar certificado inválido)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="WHATSAPP_EVOLUTION_AUTO_STATUS">Status da OS que avisam sozinhos</label>
                        <input class="form-control" type="text" id="WHATSAPP_EVOLUTION_AUTO_STATUS" name="WHATSAPP_EVOLUTION_AUTO_STATUS" placeholder="Ex.: Finalizado,Faturado" value="<?= $e($whatsapp['WHATSAPP_EVOLUTION_AUTO_STATUS'] ?? '') ?>">
                    </div>
                </div>

                <div class="nt-teste">
                    <button type="submit" class="button btn btn-success">
                        <span class="button__icon"><i class="bx bx-save"></i></span>
                        <span class="button__text2">Salvar WhatsApp</span>
                    </button>
                    <input class="form-control" type="tel" id="nt_teste_numero" placeholder="número com DDI e DDD">
                    <button type="button" class="button btn btn-dark" id="nt_testar_wpp" data-url="<?= site_url('whatsapp/testar') ?>">
                        <span class="button__icon"><i class="bx bx-plug"></i></span>
                        <span class="button__text2">Testar conexão</span>
                    </button>
                    <button type="button" class="button btn btn-primary" id="nt_enviar_wpp" data-url="<?= site_url('whatsapp/testar') ?>">
                        <span class="button__icon"><i class="bx bxl-whatsapp"></i></span>
                        <span class="button__text2">Enviar teste</span>
                    </button>
                    <span class="nt-teste-saida" id="nt_testar_wpp_saida"></span>
                </div>
                <p class="form-text ui-m0 ui-mt">Salve antes de testar. Sem número, "Testar conexão" só verifica a instância.</p>
            </form>
            <?php } else { ?>
                <p class="ui-m0">Instância: <?= $e($whatsapp['WHATSAPP_EVOLUTION_INSTANCE'] ?? '—') ?><br>URL: <?= $e($whatsapp['WHATSAPP_EVOLUTION_URL'] ?? '—') ?></p>
            <?php } ?>
        </div>
    </section>

</div>

<?php if ($podeSistema) { ?>
<div class="widget-box ui-mt">
    <div class="widget-content">
        <div class="ui-secao"><h4><i class="bx bx-timer"></i> Disparo automático e avisos de OS</h4></div>
        <div class="row">
            <div class="col-md-5">
                <form action="<?= site_url('notificacoes/salvarDespacho') ?>" method="post" class="nt-intervalo">
                    <input type="hidden" name="<?= $tokenName ?>" value="<?= $tokenHash ?>">
                    <div class="mb-3">
                        <label for="notif_intervalo_disparo">Intervalo do disparo da fila (segundos)</label>
                        <input class="form-control" type="number" min="30" step="10" id="notif_intervalo_disparo" name="notif_intervalo_disparo" value="<?= (int) $intervalo ?>">
                    </div>
                    <button type="submit" class="button btn btn-primary">
                        <span class="button__icon"><i class="bx bx-save"></i></span>
                        <span class="button__text2">Salvar intervalo</span>
                    </button>
                </form>
                <p class="form-text ui-mt">O sistema envia a fila de e-mails sozinho, sem cron externo. Mínimo de 30 segundos.</p>
            </div>

            <div class="col-md-7">
                <form action="<?= site_url('notificacoes/salvarLegado') ?>" method="post">
                    <input type="hidden" name="<?= $tokenName ?>" value="<?= $tokenHash ?>">
                    <div class="nt-grade">
                        <div class="mb-3">
                            <label for="os_notification">E-mail automático ao abrir/editar a OS</label>
                            <select class="form-select" id="os_notification" name="os_notification">
                                <option value="todos" <?= $osNotification === 'todos' ? 'selected' : '' ?>>Todos (cliente, técnico e empresa)</option>
                                <option value="cliente" <?= $osNotification === 'cliente' ? 'selected' : '' ?>>Somente o cliente</option>
                                <option value="tecnico" <?= $osNotification === 'tecnico' ? 'selected' : '' ?>>Somente o técnico</option>
                                <option value="emitente" <?= $osNotification === 'emitente' ? 'selected' : '' ?>>Somente a empresa</option>
                                <option value="nenhum" <?= $osNotification === 'nenhum' ? 'selected' : '' ?>>Não enviar</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="email_automatico">Envio automático no cadastro de OS</label>
                            <select class="form-select" id="email_automatico" name="email_automatico">
                                <option value="1" <?= (string) $emailAutomatico === '1' ? 'selected' : '' ?>>Ativado</option>
                                <option value="0" <?= (string) $emailAutomatico !== '1' ? 'selected' : '' ?>>Desativado</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notifica_whats">Texto do botão "Enviar por WhatsApp" (link wa.me)</label>
                        <textarea class="form-control" id="notifica_whats" name="notifica_whats" rows="4" style="width:100%;box-sizing:border-box" placeholder="Prezado(a), {CLIENTE_NOME}..."><?= $e($notificaWhats) ?></textarea>
                        <span class="form-text">
                            Tags: {CLIENTE_NOME}, {NUMERO_OS}, {STATUS_OS}, {VALOR_OS}, {DESCRI_PRODUTOS}, {EMITENTE},
                            {TELEFONE_EMITENTE}, {OBS_OS}, {DEFEITO_OS}, {LAUDO_OS}, {DATA_FINAL}, {DATA_INICIAL},
                            {DATA_GARANTIA}, {LINK_ACOMPANHAR}. Formatação: *negrito*, _itálico_, ~riscado~.
                        </span>
                    </div>
                    <div class="ui-rodape-form">
                        <button type="submit" class="button btn btn-primary">
                            <span class="button__icon"><i class="bx bx-save"></i></span>
                            <span class="button__text2">Salvar avisos de OS</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php } ?>
