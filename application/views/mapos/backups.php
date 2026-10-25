<div class="ui-cabecalho">
    <div>
        <h1 class="ui-titulo"><i class="bx bx-data"></i> Backups do banco de dados</h1>
        <p class="ui-subtitulo">Cópias de segurança do banco. Baixe um arquivo ou gere um backup agora.</p>
    </div>
    <div class="ui-acoes">
        <a href="<?= site_url('mapos/backup') ?>" class="button btn btn-inverse"><span class="button__icon"><i class="bx bx-download"></i></span><span class="button__text2">Fazer backup agora</span></a>
    </div>
</div>
<div class="widget-box">
    <div class="widget-content nopadding">
        <div class="ui-dica" style="margin:14px 16px 0">
            <i class="bx bx-info-circle"></i>
            <div>
                Os backups automáticos são gerados pelo comando <code>php index.php backup/executar</code>, agendado uma vez por dia
                (cron no Linux/docker ou Agendador de Tarefas no Windows). Ficam em <code><?= html_escape($pasta) ?></code>
                por <?= (int) $retencao ?> dia(s); o mais recente é sempre mantido.
            </div>
        </div>

        <?php if (! empty($backups) && $backups[0]['data'] < time() - 2 * 86400) { ?>
            <div class="alert alert-error" style="margin:12px 16px 0">
                O último backup automático tem mais de 2 dias (<?= date('d/m/Y H:i', $backups[0]['data']) ?>). Verifique o agendamento.
            </div>
        <?php } ?>

        <div class="ui-tabela-rolagem" style="margin-top:10px">
        <table class="table table-bordered">
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
                    <tr><td colspan="4"><div class="ui-vazio"><i class="bx bx-data"></i><strong>Nenhum backup automático ainda</strong>Agende o comando indicado acima para gerar os primeiros arquivos.</div></td></tr>
                <?php } else {
                    foreach ($backups as $b) { ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', $b['data']) ?></td>
                            <td><?= html_escape($b['nome']) ?></td>
                            <td style="text-align:right"><?= number_format($b['tamanho'] / 1024, 1, ',', '.') ?> KB</td>
                            <td style="text-align:center">
                                <a href="<?= site_url('mapos/baixarBackup/' . rawurlencode($b['nome'])) ?>" class="btn btn-mini btn-info" title="Baixar backup" aria-label="Baixar backup"><i class="bx bx-download"></i></a>
                            </td>
                        </tr>
                <?php }
                } ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
