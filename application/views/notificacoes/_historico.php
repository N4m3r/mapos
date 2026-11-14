<?php
/**
 * Aba Historico da Central de Notificacoes.
 *
 * Junta os envios de e-mail e de WhatsApp numa lista unica, com filtros por
 * canal e por resultado, e os totais em cartoes no topo.
 *
 * Variaveis: $historico, $totais, $filaPendente, $canalFiltro, $statusFiltro, $podeEmail
 */
$e = function ($v) {
    return html_escape((string) ($v ?? ''));
};

$link = function ($canal, $status, $pagina = 1) {
    $q = ['aba' => 'historico'];
    if ($canal) {
        $q['canal'] = $canal;
    }
    if ($status) {
        $q['status'] = $status;
    }
    if ($pagina > 1) {
        $q['pagina'] = $pagina;
    }

    return site_url('notificacoes') . '?' . http_build_query($q);
};

$itens = $historico['itens'];
$pagina = (int) $historico['pagina'];
$paginas = (int) $historico['paginas'];
$rotuloCanal = ['email' => 'E-mail', 'whatsapp' => 'WhatsApp'];
?>

<div class="nt-visao-geral">
    <div class="nt-visao-item nt-ok">
        <span>Enviados</span>
        <strong><?= (int) $totais['enviados'] ?></strong>
    </div>
    <div class="nt-visao-item nt-falha">
        <span>Falhas</span>
        <strong><?= (int) $totais['falhas'] ?></strong>
    </div>
    <div class="nt-visao-item nt-pendente">
        <span>Na fila de e-mail</span>
        <strong><?= (int) $filaPendente ?></strong>
    </div>
    <div class="nt-visao-item">
        <span>Total registrado</span>
        <strong><?= (int) $totais['total'] ?></strong>
    </div>
</div>

<div class="nt-historico-filtros">
    <a class="nt-chip" href="<?= $link($canalFiltro, null) ?>" <?= $statusFiltro === null ? 'aria-current="page"' : '' ?>>Todos</a>
    <a class="nt-chip" href="<?= $link($canalFiltro, 'enviado') ?>" <?= $statusFiltro === 'enviado' ? 'aria-current="page"' : '' ?>>Enviados</a>
    <a class="nt-chip nt-chip-falha" href="<?= $link($canalFiltro, 'falha') ?>" <?= $statusFiltro === 'falha' ? 'aria-current="page"' : '' ?>>Falhas</a>
    <span style="width:1px;height:18px;background:var(--ui-borda);display:inline-block;margin:0 4px"></span>
    <a class="nt-chip" href="<?= $link(null, $statusFiltro) ?>" <?= $canalFiltro === null ? 'aria-current="page"' : '' ?>>Todos os canais</a>
    <a class="nt-chip" href="<?= $link('email', $statusFiltro) ?>" <?= $canalFiltro === 'email' ? 'aria-current="page"' : '' ?>><i class="bx bx-envelope"></i> E-mail</a>
    <a class="nt-chip" href="<?= $link('whatsapp', $statusFiltro) ?>" <?= $canalFiltro === 'whatsapp' ? 'aria-current="page"' : '' ?>><i class="bx bxl-whatsapp"></i> WhatsApp</a>
</div>

<div class="widget-box">
    <div class="widget-content nopadding ui-tabela-rolagem">
        <table class="nt-tabela">
            <thead>
                <tr>
                    <th style="width:92px">Quando</th>
                    <th style="width:84px">Canal</th>
                    <th>Destino</th>
                    <th>Detalhe</th>
                    <th style="width:96px">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens)) { ?>
                    <tr>
                        <td colspan="5">
                            <div class="ui-vazio">
                                <i class="bx bx-history"></i>
                                <strong>Nenhum envio registrado</strong>
                                Os avisos aparecem aqui assim que algo for disparado, com o resultado de cada tentativa.
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                <?php foreach ($itens as $item) { ?>
                    <?php $ok = $item['status'] === 'enviado'; ?>
                    <tr>
                        <td class="nt-dat">
                            <?= $e(notif_quando($item['data_envio'])) ?>
                            <span style="display:block;font-size:11px;opacity:.75"><?= $item['data_envio'] ? $e(date('d/m H:i', strtotime($item['data_envio']))) : '' ?></span>
                        </td>
                        <td>
                            <span class="ui-selo <?= $item['canal'] === 'whatsapp' ? 'sucesso' : 'info' ?>">
                                <i class="bx <?= $item['canal'] === 'whatsapp' ? 'bxl-whatsapp' : 'bx-envelope' ?>"></i>
                                <?= $e($rotuloCanal[$item['canal']] ?? $item['canal']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="nt-destino"><?= $e($item['destino'] ?: '—') ?></span>
                            <?php if (! empty($item['os_id'])) { ?>
                                <a href="<?= site_url('os/visualizar/' . (int) $item['os_id']) ?>" style="font-size:11px">OS #<?= (int) $item['os_id'] ?></a>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ($item['canal'] === 'email') { ?>
                                <?= $e($item['assunto'] ?: $item['tipo'] ?: '—') ?>
                            <?php } else { ?>
                                <?= $e($item['evento'] ?: $item['tipo'] ?: '—') ?>
                            <?php } ?>
                            <?php if (! $ok && $item['erro'] !== '') { ?>
                                <span class="nt-erro"><?= $e(mb_substr($item['erro'], 0, 220)) ?></span>
                            <?php } ?>
                            <?php if ($item['canal'] === 'email' && ! $ok && ! empty($item['fila_id'])) { ?>
                                <a href="<?= site_url('mapos/reenviarEmail/' . (int) $item['id']) ?>" class="btn btn-sm btn-dark" style="margin-top:6px"
                                   data-confirmar="Reenviar este e-mail para <?= $e($item['destino']) ?> com os mesmos anexos?">
                                    <i class="bx bx-mail-send"></i> Reenviar
                                </a>
                            <?php } ?>
                        </td>
                        <td>
                            <span class="ui-selo <?= $ok ? 'sucesso' : 'perigo' ?>"><?= $ok ? 'Enviado' : 'Falha' ?></span>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($paginas > 1) { ?>
<div class="pagination alternate" style="text-align:center">
    <ul>
        <li class="<?= $pagina <= 1 ? 'disabled' : '' ?>">
            <a href="<?= $link($canalFiltro, $statusFiltro, max(1, $pagina - 1)) ?>">Anterior</a>
        </li>
        <li class="active"><a href="#"><?= $pagina ?> de <?= $paginas ?></a></li>
        <li class="<?= $pagina >= $paginas ? 'disabled' : '' ?>">
            <a href="<?= $link($canalFiltro, $statusFiltro, min($paginas, $pagina + 1)) ?>">Próxima</a>
        </li>
    </ul>
</div>
<?php } ?>

<p style="color:var(--ui-texto-2);font-size:12px;margin:10px 0 0">
    "Enviado" significa que o e-mail foi aceito pelo servidor SMTP ou a mensagem pela Evolution API.
    A entrega final depende do destinatário. E-mails ainda na fila aparecem no card acima<?php if ($podeEmail) { ?> e podem
    ser vistos em <a href="<?= site_url('mapos/emails') ?>">fila de e-mails</a><?php } ?>.
</p>
