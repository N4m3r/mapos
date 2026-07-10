<div class="widget-box">
    <div class="widget-title">
        <span class="icon"><i class="fas fa-paper-plane"></i></span>
        <h5>Últimos envios de WhatsApp</h5>
    </div>
    <div class="widget-content nopadding">
        <div style="padding:14px 16px 0; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div>
                <a href="<?= site_url('notificacoes/envios') ?>" class="btn btn-mini <?= $statusFiltro === null ? 'btn-primary' : '' ?>">Todos</a>
                <a href="<?= site_url('notificacoes/envios?status=enviado') ?>" class="btn btn-mini <?= $statusFiltro === 'enviado' ? 'btn-success' : '' ?>">Enviados (<?= (int) $totalEnviados ?>)</a>
                <a href="<?= site_url('notificacoes/envios?status=falha') ?>" class="btn btn-mini <?= $statusFiltro === 'falha' ? 'btn-danger' : '' ?>">Falhas (<?= (int) $totalFalhas ?>)</a>
            </div>
            <a href="<?= site_url('notificacoes') ?>" class="btn btn-mini"><i class="bx bx-arrow-back"></i> Voltar aos gatilhos</a>
        </div>

        <table class="table table-bordered" style="margin-top:10px">
            <thead>
                <tr>
                    <th style="width:140px">Data/Hora</th>
                    <th>Destino</th>
                    <th style="width:110px">Tipo</th>
                    <th style="width:60px">OS</th>
                    <th style="width:130px">Evento</th>
                    <th style="width:100px; text-align:center">Status</th>
                    <th>Retorno / Erro</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($envios)) { ?>
                    <tr><td colspan="7">Nenhum envio registrado ainda. (Requer rodar <code>updates/update_whatsapp_envios.sql</code>.)</td></tr>
                <?php } else {
                    foreach ($envios as $e) {
                        $ok = ($e->status === 'enviado');
                        $ehGrupo = (strpos((string) $e->destino, '@g.us') !== false); ?>
                        <tr>
                            <td><?= $e->data_envio ? date('d/m/Y H:i', strtotime($e->data_envio)) : '-' ?></td>
                            <td>
                                <?= html_escape($e->destino) ?>
                                <?php if ($ehGrupo) { ?><span class="badge" style="background:#6b29f8">grupo</span><?php } ?>
                            </td>
                            <td><?= html_escape($e->tipo ?: '-') ?></td>
                            <td><?php if ($e->os_id) { ?><a href="<?= site_url('os/visualizar/' . (int) $e->os_id) ?>">#<?= (int) $e->os_id ?></a><?php } else { echo '-'; } ?></td>
                            <td><?= html_escape($e->evento ?: '-') ?></td>
                            <td style="text-align:center">
                                <?php if ($ok) { ?>
                                    <span class="badge badge-success">Enviado</span>
                                <?php } else { ?>
                                    <span class="badge badge-important" style="background:#CD0000">Falha</span>
                                <?php } ?>
                            </td>
                            <td style="font-size:12px; color:<?= $ok ? '#6b7191' : '#b94a48' ?>">
                                <?= html_escape($ok ? ($e->retorno ?: 'ok') : ($e->erro ?: '')) ?>
                            </td>
                        </tr>
                    <?php }
                } ?>
            </tbody>
        </table>
        <p style="padding:0 16px 14px; color:#8a90a6; font-size:12px; margin:0">
            Mostrando os 100 envios mais recentes. "Enviado" = aceito pela Evolution API; a entrega/leitura final depende do WhatsApp do destinatário.
        </p>
    </div>
</div>
