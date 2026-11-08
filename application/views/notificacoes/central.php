<?php
/**
 * Central de Notificacoes — tela unica (Notificacoes::index).
 *
 * Abas: Eventos (o que avisa), Pendencias (atividades nao programadas sem
 * assinatura ou sem chamado), Canais (por onde avisa), Modelos (os textos) e
 * Historico (o que foi enviado). Substitui a tela de gatilhos, o log de envios
 * e as abas de Notificacoes/E-mail de Configuracoes.
 *
 * Variaveis: $abaAtiva, $porEvento, $eventosSemGatilho,
 * $ordemGrupos, $intervalo, $smtp, $whatsapp, $whatsappAtivo, $osNotification,
 * $emailAutomatico, $notificaWhats, $podeSistema, $podeEmail, $modelosEmail,
 * $modelosWhatsapp, $canalFiltro, $statusFiltro, $historico, $totais,
 * $filaPendente
 */
$e = function ($v) {
    return html_escape((string) ($v ?? ''));
};

$abas = [
    'eventos' => ['Eventos', 'bx-bell'],
    'pendencias' => ['Pendências', 'bx-error-circle'],
    'canais' => ['Canais', 'bx-slider-alt'],
    'modelos' => ['Modelos', 'bx-comment-dots'],
    'historico' => ['Histórico', 'bx-history'],
];

$emailPronto = trim((string) ($smtp['EMAIL_SMTP_HOST'] ?? '')) !== ''
    && trim((string) ($smtp['EMAIL_SMTP_USER'] ?? '')) !== '';
$wppPronto = trim((string) ($whatsapp['WHATSAPP_EVOLUTION_URL'] ?? '')) !== ''
    && trim((string) ($whatsapp['WHATSAPP_EVOLUTION_APIKEY'] ?? '')) !== '';

$linkAba = function ($aba) {
    return site_url('notificacoes') . ($aba === 'eventos' ? '' : '?aba=' . $aba);
};
?>
<link rel="stylesheet" href="<?= base_url('assets/css/notificacoes.css') ?>?v=<?= asset_versao('assets/css/notificacoes.css', '1') ?>" />

<div class="nt">

    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-bell"></i> Central de notificações</h1>
            <p class="ui-subtitulo">Avisos automáticos do sistema: eventos, canais, textos e o histórico dos envios.</p>
        </div>
        <div class="ui-acoes">
            <span class="ui-selo <?= $emailPronto ? 'sucesso' : 'neutro' ?>"><i class="bx bx-envelope"></i> E-mail <?= $emailPronto ? 'pronto' : 'sem SMTP' ?></span>
            <span class="ui-selo <?= ($wppPronto && $whatsappAtivo) ? 'sucesso' : 'neutro' ?>"><i class="bx bxl-whatsapp"></i> WhatsApp <?= ($wppPronto && $whatsappAtivo) ? 'pronto' : 'desligado' ?></span>
        </div>
    </div>

    <nav class="nt-abas" aria-label="Seções da central de notificações">
        <?php foreach ($abas as $slug => $aba) { ?>
            <a class="nt-aba" href="<?= $linkAba($slug) ?>" aria-selected="<?= $abaAtiva === $slug ? 'true' : 'false' ?>">
                <i class="bx <?= $aba[1] ?>"></i><?= $aba[0] ?>
                <?php if ($slug === 'eventos') { ?>
                    <span class="nt-aba-num"><?= count($porEvento) ?></span>
                <?php } elseif ($slug === 'pendencias' && ! empty($pendencias)) { ?>
                    <span class="nt-aba-num" style="background:var(--ui-perigo, #c0392b);color:#fff"><?= count($pendencias) ?></span>
                <?php } elseif ($slug === 'modelos') { ?>
                    <span class="nt-aba-num"><?= count($modelosEmail) + count($modelosWhatsapp) ?></span>
                <?php } elseif ($slug === 'historico' && (int) $totais['falhas'] > 0) { ?>
                    <span class="nt-aba-num"><?= (int) $totais['falhas'] ?></span>
                <?php } ?>
            </a>
        <?php } ?>
    </nav>

    <div class="nt-painel">
        <?php
        $parcial = 'notificacoes/_' . $abaAtiva;
        $this->load->view($parcial);
        ?>
    </div>

</div>

<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/notificacoes.js') ?>?v=<?= asset_versao('assets/js/notificacoes.js', '1') ?>"></script>
