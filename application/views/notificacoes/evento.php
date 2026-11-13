<?php
/**
 * Configuracao de um evento de notificacao (Notificacoes::evento).
 *
 * Edita o gatilho principal do evento. Gatilhos extras do mesmo evento
 * (variacoes) continuam valendo e aparecem no rodape.
 *
 * Variaveis: $eventoSlug, $eventoInfo, $gatilho, $gatilhosEvento, $canais,
 * $destinatarios, $blocos, $anexos, $whatsappTemplates, $emailTemplates,
 * $clientesSelecionados, $projetosSelecionados, $ehOs, $ehProjeto, $podeSistema
 */
$e = function ($v) {
    return html_escape((string) ($v ?? ''));
};
$tokenName = $this->security->get_csrf_token_name();
$tokenHash = $this->security->get_csrf_hash();

$selCanais = notif_lista($gatilho->canais ?? null);
$selDest = notif_lista($gatilho->destinatarios ?? null);
$selBlocos = notif_lista($gatilho->blocos ?? null);
$selAnexos = notif_lista($gatilho->anexos ?? null);
$selGrupos = notif_lista($gatilho->whatsapp_grupos ?? null);
$emailDestRaw = implode("\n", notif_lista($gatilho->email_destinatarios ?? null));

$marcado = function ($valor, $lista) {
    return in_array((string) $valor, $lista, true) ? 'checked' : '';
};
?>
<link rel="stylesheet" href="<?= base_url('assets/css/notificacoes.css') ?>?v=<?= asset_versao('assets/css/notificacoes.css', '1') ?>" />
<link rel="stylesheet" href="<?= base_url() ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />

<div class="ui-cabecalho">
    <div>
        <h1 class="ui-titulo"><i class="bx <?= $e(notif_evento_icone($eventoSlug)) ?>"></i> <?= $e($eventoInfo['nome']) ?></h1>
        <p class="ui-subtitulo"><?= $e($eventoInfo['grupo']) ?> · por onde este aviso sai, para quem e com qual texto.</p>
    </div>
    <div class="ui-acoes">
        <a href="<?= site_url('notificacoes') ?>" class="button btn btn-warning">
            <span class="button__icon"><i class="bx bx-arrow-back"></i></span>
            <span class="button__text2">Voltar à central</span>
        </a>
    </div>
</div>

<?php if (! $podeSistema) { ?>
    <div class="ui-dica alerta">
        <i class="bx bx-lock-alt"></i>
        <div>Seu perfil pode consultar este evento, mas só quem configura o sistema pode alterá-lo.</div>
    </div>
<?php } ?>

<form action="<?= site_url('notificacoes/salvarEvento') ?>" method="post" class="form-horizontal">
    <input type="hidden" name="<?= $tokenName ?>" value="<?= $tokenHash ?>">
    <input type="hidden" name="evento" value="<?= $e($eventoSlug) ?>">

    <div class="widget-box">
        <div class="widget-content">
            <div class="ui-opcoes ui-mb">
                <label class="ui-opcao">
                    <input type="checkbox" name="ativo" value="1" <?= (int) ($gatilho->ativo ?? 0) === 1 ? 'checked' : '' ?>>
                    Evento ligado
                </label>
                <span class="help-inline">Desligado, o sistema não avisa nada para este evento.</span>
            </div>

            <div class="ui-secao">
                <h4>Por onde disparar</h4>
                <div class="ui-opcoes">
                    <?php foreach ($canais as $valor => $rotulo) { ?>
                        <label class="ui-opcao">
                            <input type="checkbox" name="canais[]" value="<?= $e($valor) ?>" <?= $marcado($valor, $selCanais) ?>>
                            <i class="bx <?= $e(notif_canal_icone($valor)) ?>"></i> <?= $e($rotulo) ?>
                        </label>
                    <?php } ?>
                </div>
            </div>

            <div class="ui-secao">
                <h4>Para quem</h4>
                <div class="ui-opcoes">
                    <?php foreach ($destinatarios as $valor => $rotulo) { ?>
                        <label class="ui-opcao">
                            <input type="checkbox" name="destinatarios[]" value="<?= $e($valor) ?>" <?= $marcado($valor, $selDest) ?>>
                            <?= $e($rotulo) ?>
                        </label>
                    <?php } ?>
                </div>
            </div>

            <?php if (! empty($whatsappTemplates)) { ?>
                <div class="ui-secao">
                    <h4>Texto no WhatsApp</h4>
                    <div class="ui-grade">
                        <div class="control-group">
                            <label for="whatsapp_template">Modelo de mensagem</label>
                            <select id="whatsapp_template" name="whatsapp_template">
                                <option value="">— padrão do sistema —</option>
                                <?php foreach ($whatsappTemplates as $wt) { ?>
                                    <option value="<?= $e($wt->slug) ?>" <?= (string) ($gatilho->whatsapp_template ?? '') === (string) $wt->slug ? 'selected' : '' ?>><?= $e($wt->nome) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if (! empty($emailTemplates)) { ?>
                <div class="ui-secao">
                    <h4>Texto no e-mail</h4>
                    <div class="ui-grade">
                        <div class="control-group">
                            <label for="template_slug">Modelo de e-mail</label>
                            <select id="template_slug" name="template_slug">
                                <option value="">— padrão do sistema —</option>
                                <?php foreach ($emailTemplates as $et) { ?>
                                    <option value="<?= $e($et->slug) ?>" <?= (string) ($gatilho->template_slug ?? '') === (string) $et->slug ? 'selected' : '' ?>><?= $e($et->nome) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <div class="ui-secao">
                <h4>Grupos de WhatsApp</h4>
                <p style="font-size:12px;color:var(--ui-texto-2);margin:0 0 8px">
                    Além dos destinatários acima, envia também para os grupos escolhidos. Requer o canal WhatsApp e a Evolution API ativa.
                </p>
                <button type="button" id="nt-carregar-grupos" class="btn btn-mini btn-primary" data-url="<?= site_url('whatsapp/grupos') ?>">
                    <i class="bx bx-download"></i> Carregar grupos
                </button>
                <span id="nt-grupos-msg" style="margin-left:8px;font-size:12px"></span>
                <div id="nt-grupos" style="margin-top:10px">
                    <?php foreach ($selGrupos as $jid) { ?>
                        <label style="display:block;margin-bottom:6px">
                            <input type="checkbox" name="whatsapp_grupos[]" value="<?= $e($jid) ?>" checked> <?= $e($jid) ?>
                        </label>
                    <?php } ?>
                    <?php if (empty($selGrupos)) { ?>
                        <span style="color:var(--ui-texto-2);font-size:12px">Nenhum grupo selecionado.</span>
                    <?php } ?>
                </div>
            </div>

            <div class="ui-secao">
                <h4>Filtro de clientes <small style="font-weight:normal;color:var(--ui-texto-2)">— sem clientes, vale para todos</small></h4>
                <input type="text" id="nt-busca-cliente" class="span8" placeholder="Buscar cliente por nome, documento ou telefone..." autocomplete="off">
                <div id="nt-clientes" style="display:flex;flex-wrap:wrap;gap:6px;min-height:36px;padding:8px;border:1px solid var(--ui-borda);border-radius:6px;margin-top:8px">
                    <?php foreach ($clientesSelecionados as $c) : ?>
                        <?php $doc = trim((string) $c->documento); $rot = $c->nomeCliente . ($doc !== '' ? ' — ' . $doc : ''); ?>
                        <span class="nt-chip-cliente" style="display:inline-flex;align-items:center;gap:6px;background:var(--ui-suave);border:1px solid var(--ui-borda);border-radius:16px;padding:4px 10px;font-size:12px">
                            <?= $e($rot) ?>
                            <input type="hidden" name="whatsapp_clientes[]" value="<?= (int) $c->idClientes ?>">
                            <a href="#" class="nt-remover-cliente" style="color:var(--ui-perigo);text-decoration:none;font-weight:700">&times;</a>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($ehProjeto) { ?>
                <div class="ui-secao">
                    <h4>E-mails fixos de acompanhamento <small style="font-weight:normal;color:var(--ui-texto-2)">— um por linha</small></h4>
                    <textarea name="email_destinatarios_raw" class="span12" rows="3" placeholder="responsavel1@empresa.com"><?= $e($emailDestRaw) ?></textarea>
                    <label style="display:flex;align-items:flex-start;gap:8px;margin-top:12px">
                        <input type="checkbox" name="email_conversa" value="1" <?= (int) ($gatilho->email_conversa ?? 0) === 1 ? 'checked' : '' ?>>
                        <span>Manter os e-mails deste projeto como uma única conversa (thread)</span>
                    </label>
                </div>

                <div class="ui-secao">
                    <h4>Filtro de projetos <small style="font-weight:normal;color:var(--ui-texto-2)">— sem projetos, vale para todos</small></h4>
                    <input type="text" id="nt-busca-projeto" class="span8" placeholder="Buscar projeto por nome ou código..." autocomplete="off">
                    <div id="nt-projetos" style="display:flex;flex-wrap:wrap;gap:6px;min-height:36px;padding:8px;border:1px solid var(--ui-borda);border-radius:6px;margin-top:8px">
                        <?php foreach ($projetosSelecionados as $p) : ?>
                            <?php $cod = trim((string) $p->codigo); $rot = $p->nome . ($cod !== '' ? ' — ' . $cod : ''); ?>
                            <span class="nt-chip-projeto" style="display:inline-flex;align-items:center;gap:6px;background:var(--ui-suave);border:1px solid var(--ui-borda);border-radius:16px;padding:4px 10px;font-size:12px">
                                <?= $e($rot) ?>
                                <input type="hidden" name="projetos_id[]" value="<?= (int) $p->idObra ?>">
                                <a href="#" class="nt-remover-projeto" style="color:var(--ui-perigo);text-decoration:none;font-weight:700">&times;</a>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php } ?>

            <?php if (! empty($temReenvio)) { ?>
                <div class="ui-secao">
                    <h4>Reenvio do link até a assinatura <small style="font-weight:normal;color:var(--ui-texto-2)">— vazio ou 0 desliga</small></h4>
                    <p style="font-size:12px;color:var(--ui-texto-2);margin:0 0 8px">
                        Enquanto o cliente não aceitar ou recusar, o link é reenviado só para ele (modelo "Aceite: lembrete de assinatura").
                        Para ao assinar, ao recusar, se a OS for cancelada ou quando acabam as tentativas (evento "Aceite sem resposta").
                        Precisa do agendamento <code>php index.php lembretes/aceite</code> a cada 15 minutos.
                    </p>
                    <div class="ui-grade">
                        <div class="control-group">
                            <label for="reenvio_intervalo_horas">Reenviar a cada (horas)</label>
                            <input type="number" min="0" max="<?= (int) Aceite_reenvio::INTERVALO_MAX_HORAS ?>" id="reenvio_intervalo_horas" name="reenvio_intervalo_horas" value="<?= $e($gatilho->reenvio_intervalo_horas ?? '') ?>" placeholder="24">
                        </div>
                        <div class="control-group">
                            <label for="reenvio_max_tentativas">Máximo de reenvios</label>
                            <input type="number" min="0" max="<?= (int) Aceite_reenvio::TENTATIVAS_MAX ?>" id="reenvio_max_tentativas" name="reenvio_max_tentativas" value="<?= $e($gatilho->reenvio_max_tentativas ?? '') ?>" placeholder="5">
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($ehOs) { ?>
                <div class="ui-secao">
                    <h4>O que a notificação de OS deve conter</h4>
                    <div class="ui-opcoes">
                        <?php foreach ($blocos as $valor => $rotulo) { ?>
                            <label class="ui-opcao">
                                <input type="checkbox" name="blocos[]" value="<?= $e($valor) ?>" <?= $marcado($valor, $selBlocos) ?>>
                                <?= $e($rotulo) ?>
                            </label>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <div class="ui-opcoes ui-mb">
                <?php foreach ($anexos as $valor => $rotulo) { ?>
                    <label class="ui-opcao">
                        <input type="checkbox" name="anexos[]" value="<?= $e($valor) ?>" <?= $marcado($valor, $selAnexos) ?>>
                        Anexar <?= mb_strtolower($e($rotulo)) ?> no e-mail
                    </label>
                <?php } ?>
            </div>

            <div class="ui-rodape-form">
                <button type="submit" class="button btn btn-success" <?= $podeSistema ? '' : 'disabled' ?>>
                    <span class="button__icon"><i class="bx bx-save"></i></span>
                    <span class="button__text2">Salvar evento</span>
                </button>
            </div>
        </div>
    </div>
</form>

<?php if ($podeSistema && $gatilho) { ?>
<div class="widget-box">
    <div class="widget-content">
        <div class="ui-secao"><h4>Outras ações deste evento</h4></div>
        <p style="font-size:13px;color:var(--ui-texto-2);margin:0 0 10px">
            Este evento tem <?= count($gatilhosEvento) ?> configuração(ões). Uma cópia nasce desligada, para você ajustar antes de ativar.
        </p>
        <div class="ui-acoes">
            <a href="<?= site_url('notificacoes/clonar/' . rawurlencode($eventoSlug)) ?>" class="button btn"
               data-confirmar="Criar uma cópia desligada deste evento?">
                <span class="button__icon"><i class="bx bx-copy"></i></span>
                <span class="button__text2">Duplicar</span>
            </a>
            <a href="<?= site_url('notificacoes/excluir/' . rawurlencode($eventoSlug)) ?>" class="button btn btn-danger"
               data-confirmar="Excluir a configuração deste evento? Ele volta ao padrão do sistema (sem aviso).">
                <span class="button__icon"><i class="bx bx-trash"></i></span>
                <span class="button__text2">Excluir configuração</span>
            </a>
        </div>
    </div>
</div>
<?php } ?>

<script nonce="<?= csp_nonce() ?>" type="text/javascript" src="<?= base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/notificacoes.js') ?>?v=<?= asset_versao('assets/js/notificacoes.js', '1') ?>"></script>
<script nonce="<?= csp_nonce() ?>">
    $(function () {
        window.NotificacoesEvento({
            buscaCliente: '<?= site_url('notificacoes/autoCompleteCliente') ?>',
            buscaProjeto: '<?= site_url('notificacoes/autoCompleteProjeto') ?>'
        });
    });
</script>
