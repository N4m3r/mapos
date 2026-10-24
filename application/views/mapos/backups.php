<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="bx bx-data"></i></span>
        <h5>Backups do banco de dados</h5>
    </div>
    <div class="widget-content nopadding">
        <div style="padding:14px 16px 0; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div style="font-size:12px; color:#555; max-width:720px;">
                Os backups automáticos são gerados pelo comando <code>php index.php backup/executar</code>, agendado uma vez por dia
                (cron no Linux/docker ou Agendador de Tarefas no Windows). Ficam em <code><?= html_escape($pasta) ?></code>
                por <?= (int) $retencao ?> dia(s); o mais recente é sempre mantido.
            </div>
            <a href="<?= site_url('mapos/backup') ?>" class="btn btn-mini btn-inverse"><i class="bx bx-download"></i> Fazer backup agora</a>
        </div>

        <?php if (! empty($backups) && $backups[0]['data'] < time() - 2 * 86400) { ?>
            <div class="alert alert-error" style="margin:12px 16px 0">
                O último backup automático tem mais de 2 dias (<?= date('d/m/Y H:i', $backups[0]['data']) ?>). Verifique o agendamento.
            </div>
        <?php } ?>

        <table class="table table-bordered" style="margin-top:10px">
            <thead>
                <tr>
                    <th style="width:160px">Data/Hora</th>
                    <th>Arquivo</th>
                    <th style="width:110px; text-align:right">Tamanho</th>
                    <th style="width:110px; text-align:center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($backups)) { ?>
                    <tr><td colspan="4">Nenhum backup automático ainda. Agende o comando acima.</td></tr>
                <?php } else {
                    foreach ($backups as $b) { ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', $b['data']) ?></td>
                            <td><?= html_escape($b['nome']) ?></td>
                            <td style="text-align:right"><?= number_format($b['tamanho'] / 1024, 1, ',', '.') ?> KB</td>
                            <td style="text-align:center">
                                <a href="<?= site_url('mapos/baixarBackup/' . rawurlencode($b['nome'])) ?>" class="btn btn-mini btn-info" title="Baixar"><i class="bx bx-download"></i></a>
                            </td>
                        </tr>
                <?php }
                } ?>
            </tbody>
        </table>
    </div>
</div>
