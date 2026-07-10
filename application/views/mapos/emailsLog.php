<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="fas fa-envelope-open-text"></i></span>
        <h5>Log de e-mails enviados</h5>
    </div>
    <div class="widget-content nopadding">
        <div style="padding:14px 16px 0; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div>
                <a href="<?= site_url('mapos/emailsLog') ?>" class="btn btn-mini <?= $statusFiltro === null ? 'btn-primary' : '' ?>">Todos</a>
                <a href="<?= site_url('mapos/emailsLog?status=enviado') ?>" class="btn btn-mini <?= $statusFiltro === 'enviado' ? 'btn-success' : '' ?>">Enviados (<?= (int) $totalEnviados ?>)</a>
                <a href="<?= site_url('mapos/emailsLog?status=falha') ?>" class="btn btn-mini <?= $statusFiltro === 'falha' ? 'btn-danger' : '' ?>">Falhas (<?= (int) $totalFalhas ?>)</a>
            </div>
            <a href="<?= site_url('mapos/emails') ?>" class="btn btn-mini"><i class="bx bx-list-ul"></i> Fila de e-mails</a>
        </div>

        <table class="table table-bordered" style="margin-top:10px">
            <thead>
                <tr>
                    <th style="width:140px">Data/Hora</th>
                    <th>Destino</th>
                    <th>Assunto</th>
                    <th style="width:80px">Tipo</th>
                    <th style="width:100px; text-align:center">Status</th>
                    <th>Erro</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($envios)) { ?>
                    <tr><td colspan="6">Nenhum envio registrado ainda. (Requer rodar <code>updates/update_email_envios.sql</code>.)</td></tr>
                <?php } else {
                    foreach ($envios as $e) {
                        $ok = ($e->status === 'enviado'); ?>
                        <tr>
                            <td><?= $e->data_envio ? date('d/m/Y H:i', strtotime($e->data_envio)) : '-' ?></td>
                            <td><?= html_escape($e->destino) ?></td>
                            <td><?= html_escape($e->assunto ?: '-') ?></td>
                            <td><?= html_escape($e->tipo ?: '-') ?></td>
                            <td style="text-align:center">
                                <?php if ($ok) { ?>
                                    <span class="badge badge-success">Enviado</span>
                                <?php } else { ?>
                                    <span class="badge badge-important" style="background:#CD0000">Falha</span>
                                <?php } ?>
                            </td>
                            <td style="font-size:11px; color:#b94a48; max-width:420px; word-break:break-word;">
                                <?= $ok ? '' : html_escape(mb_substr((string) $e->erro, 0, 400)) ?>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </tbody>
        </table>
        <p style="padding:0 16px 14px; color:#8a90a6; font-size:12px; margin:0">
            Mostrando os 100 envios mais recentes. "Enviado" = aceito pelo servidor SMTP.
        </p>
    </div>
</div>
