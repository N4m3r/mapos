<?php
$tokenName = $this->security->get_csrf_token_name();
$tokenHash = $this->security->get_csrf_hash();
$rotuloCanais = Notification_triggers_model::canaisDisponiveis();
?>

<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="fas fa-bell"></i></span>
        <h5>Disparo automático</h5>
    </div>
    <div class="widget-content" style="padding:16px">
        <p style="color:#6b7191; margin-top:0">
            O sistema envia a fila de e-mails sozinho, sem cron externo. Defina de quanto em quanto tempo
            ele reinicia o disparo.
        </p>
        <form action="<?= site_url('notificacoes/salvarConfig') ?>" method="post" style="display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap;">
            <input type="hidden" name="<?= $tokenName ?>" value="<?= $tokenHash ?>">
            <div class="control-group" style="margin:0">
                <label class="control-label" for="notif_intervalo_disparo">Intervalo de disparo (segundos)</label>
                <div class="controls">
                    <input id="notif_intervalo_disparo" type="number" min="30" step="10" name="notif_intervalo_disparo" value="<?= (int) $intervalo ?>" style="width:140px">
                    <span class="help-inline">mínimo 30s</span>
                </div>
            </div>
            <button type="submit" class="button btn btn-success" style="margin-bottom:10px">
                <span class="button__icon"><i class="bx bx-save"></i></span>
                <span class="button__text2">Salvar intervalo</span>
            </button>
        </form>
    </div>
</div>

<div class="widget-box">
    <div class="widget-title">
        <span class="icon"><i class="fas fa-bolt"></i></span>
        <h5>Gatilhos de notificação</h5>
    </div>
    <div class="widget-content nopadding">
        <div style="padding:14px 16px 0; display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
            <p style="color:#6b7191; margin:0;">
                Configure cada evento do sistema: se está ativo, por onde dispara (e-mail/WhatsApp), para quem,
                o que a mensagem deve conter e quais anexos incluir. Você pode ter mais de um gatilho por evento
                (no WhatsApp, todos os ativos disparam).
            </p>
            <a href="<?= site_url('notificacoes/novo') ?>" class="button btn btn-success btn-mini" style="white-space:nowrap">
                <span class="button__icon"><i class="bx bx-plus-circle"></i></span><span class="button__text2">Novo gatilho</span>
            </a>
        </div>
        <table class="table table-bordered" style="margin-top:10px">
            <thead>
                <tr>
                    <th>Evento</th>
                    <th>Canais</th>
                    <th style="width:110px; text-align:center">Status</th>
                    <th style="width:110px; text-align:center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)) { ?>
                    <tr><td colspan="4">Nenhum gatilho cadastrado. Rode a migration/SQL de notificações.</td></tr>
                <?php } ?>
                <?php $grupoAtual = null; ?>
                <?php foreach ($results as $r) { ?>
                    <?php if ($r->grupo !== $grupoAtual) { $grupoAtual = $r->grupo; ?>
                        <tr style="background:#eff6ff">
                            <td colspan="4" style="font-weight:700; color:#1e3a8a"><?= html_escape($grupoAtual) ?></td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td>
                            <strong><?= html_escape($r->nome) ?></strong><br>
                            <span style="color:#8a90a6; font-size:12px"><?= html_escape($r->descricao) ?></span>
                        </td>
                        <td>
                            <?php foreach (Notification_triggers_model::toList($r->canais) as $c) { ?>
                                <span class="badge badge-info"><?= html_escape($rotuloCanais[$c] ?? $c) ?></span>
                            <?php } ?>
                        </td>
                        <td style="text-align:center">
                            <?php if ((int) $r->ativo === 1) { ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php } else { ?>
                                <span class="badge badge-warning">Desativado</span>
                            <?php } ?>
                        </td>
                        <td style="text-align:center; white-space:nowrap">
                            <a href="<?= site_url('notificacoes/editar/' . $r->id) ?>" class="btn btn-primary btn-mini">
                                <i class="bx bx-edit"></i> Editar
                            </a>
                            <a href="<?= site_url('notificacoes/excluir/' . $r->id) ?>" class="btn btn-danger btn-mini"
                               onclick="return confirm('Excluir o gatilho \'<?= html_escape($r->nome) ?>\'?');">
                                <i class="bx bx-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
