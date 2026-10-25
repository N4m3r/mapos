<div class="ui-cabecalho">
    <div>
        <h1 class="ui-titulo"><i class="bx bx-paper-plane"></i> Últimos envios de WhatsApp</h1>
        <p class="ui-subtitulo">Mensagens disparadas pelos gatilhos, com o retorno de cada envio.</p>
    </div>
    <div class="ui-acoes">
        <a href="<?= site_url('notificacoes') ?>" class="button btn"><span class="button__icon"><i class="bx bx-arrow-back"></i></span><span class="button__text2">Voltar aos gatilhos</span></a>
    </div>
</div>
<div class="widget-box">
    <div class="widget-content nopadding">
        <div class="ui-filtros" style="margin:14px 16px 0">
            <div class="ui-acoes">
                <a href="<?= site_url('notificacoes/envios') ?>" class="btn btn-mini <?= $statusFiltro === null ? 'btn-primary' : '' ?>">Todos</a>
                <a href="<?= site_url('notificacoes/envios?status=enviado') ?>" class="btn btn-mini <?= $statusFiltro === 'enviado' ? 'btn-success' : '' ?>">Enviados (<?= (int) $totalEnviados ?>)</a>
                <a href="<?= site_url('notificacoes/envios?status=falha') ?>" class="btn btn-mini <?= $statusFiltro === 'falha' ? 'btn-danger' : '' ?>">Falhas (<?= (int) $totalFalhas ?>)</a>
            </div>
        </div>

        <div class="ui-tabela-rolagem" style="margin-top:10px">
        <table class="table table-bordered">
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
                    <tr><td colspan="7"><div class="ui-vazio"><i class="bx bx-paper-plane"></i><strong>Nenhum envio registrado ainda</strong>Requer rodar <code>updates/update_whatsapp_envios.sql</code>.</div></td></tr>
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
                                    <span class="ui-selo sucesso">Enviado</span>
                                <?php } else { ?>
                                    <span class="ui-selo perigo">Falha</span>
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
        </div>
        <p style="padding:0 16px 14px; color:#8a90a6; font-size:12px; margin:0">
            Mostrando os 100 envios mais recentes. "Enviado" = aceito pela Evolution API; a entrega/leitura final depende do WhatsApp do destinatário.
        </p>
    </div>
</div>
