<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Pagina publica de acompanhamento da OS + avaliacao NPS (ADR-007).
 * Sem JavaScript: a nota e escolhida por radio estilizado.
 */
$appName = $this->config->item('app_name') ?: 'Map-OS';
$data = fn ($d, $hora = false) => ($d && strpos($d, '0000') !== 0) ? date($hora ? 'd/m/Y H:i' : 'd/m/Y', strtotime($d)) : '';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Acompanhamento da OS #<?= (int) $os->idOs ?> - <?= html_escape($appName) ?></title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <link rel="stylesheet" href="<?= base_url() ?>assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?= base_url() ?>assets/boxicons/css/boxicons.min.css" />
    <link rel="shortcut icon" type="image/png" href="<?= base_url() ?>assets/img/favicon.png" />
    <style>
        body { background: #eef1f6; color: #33373d; font-family: 'Segoe UI', Tahoma, Arial, sans-serif; padding-bottom: 40px; }
        .ac-wrap { max-width: 640px; margin: 0 auto; padding: 16px; }
        .ac-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0, 0, 0, .08); margin-top: 16px; overflow: hidden; }
        .ac-head { background: #2d335b; color: #fff; padding: 20px 22px; }
        .ac-head h1 { font-size: 20px; line-height: 1.3; margin: 0; }
        .ac-head small { opacity: .8; }
        .ac-body { padding: 20px 22px; }
        .ac-status { display: inline-block; background: #e8ecfa; color: #2d335b; border-radius: 14px; padding: 3px 12px; font-weight: 600; }
        .ac-etapas { list-style: none; margin: 18px 0 0; padding: 0; }
        .ac-etapas li { position: relative; padding: 0 0 18px 38px; color: #9aa0a6; }
        .ac-etapas li:before { content: ''; position: absolute; left: 13px; top: 24px; bottom: 0; width: 2px; background: #dfe3ea; }
        .ac-etapas li:last-child:before { display: none; }
        .ac-etapas i { position: absolute; left: 0; top: 0; width: 28px; height: 28px; border-radius: 50%; background: #dfe3ea; color: #fff; font-size: 16px; line-height: 28px; text-align: center; }
        .ac-etapas .concluida { color: #33373d; }
        .ac-etapas .concluida i { background: #2e9e5b; }
        .ac-etapas .atual { color: #2d335b; font-weight: 600; }
        .ac-etapas .atual i { background: #f0a020; }
        .ac-etapas small { display: block; font-weight: normal; color: #7b8088; }
        .ac-nps { display: flex; flex-wrap: wrap; gap: 6px; margin: 10px 0; }
        .ac-nps input { position: absolute; opacity: 0; }
        .ac-nps label { margin: 0; width: 40px; height: 40px; line-height: 40px; text-align: center; border-radius: 6px; border: 1px solid #ccd2dc; cursor: pointer; font-weight: 600; }
        .ac-nps input:checked + label { background: #2d335b; border-color: #2d335b; color: #fff; }
        .ac-nps input:focus + label { outline: 2px solid #f0a020; }
        .ac-legenda { display: flex; justify-content: space-between; font-size: 12px; color: #7b8088; }
        textarea { width: 100%; box-sizing: border-box; }
        .ac-rodape { text-align: center; color: #7b8088; font-size: 13px; margin-top: 14px; }
    </style>
</head>

<body>
    <div class="ac-wrap">
        <?php if ($this->session->flashdata('success')) : ?>
            <div class="alert alert-success" style="margin-top:16px"><?= html_escape($this->session->flashdata('success')) ?></div>
        <?php endif; ?>
        <?php if ($this->session->flashdata('error')) : ?>
            <div class="alert alert-error" style="margin-top:16px"><?= html_escape($this->session->flashdata('error')) ?></div>
        <?php endif; ?>

        <div class="ac-card">
            <div class="ac-head">
                <small><?= html_escape($emitente->nome ?? $appName) ?></small>
                <h1>Ordem de Serviço #<?= (int) $os->idOs ?></h1>
                <small><?= html_escape($os->nomeCliente) ?> · aberta em <?= $data($os->dataInicial) ?></small>
            </div>
            <div class="ac-body">
                Situação atual: <span class="ac-status"><?= html_escape($os->status) ?></span>
                <?php if ($os->tecnico) : ?>
                    <p style="margin-top:10px">Responsável: <?= html_escape($os->tecnico) ?></p>
                <?php endif; ?>

                <?php if ($timeline && ($timeline['cancelado'] || $timeline['nao_realizado'])) : ?>
                    <div class="alert" style="margin-top:16px">Esta OS está como <b><?= html_escape($os->status) ?></b>. Em caso de dúvida, fale conosco.</div>
                <?php elseif ($timeline) : ?>
                    <ul class="ac-etapas">
                        <?php foreach ($timeline['stages'] as $e) : ?>
                            <li class="<?= $e['estado'] ?>">
                                <i class="bx <?= $e['estado'] === 'concluida' ? 'bx-check' : html_escape($e['icone']) ?>"></i>
                                <?= html_escape($e['label']) ?>
                                <?php if ($e['estado'] === 'concluida' && $data($e['data'], true)) : ?>
                                    <small><?= $data($e['data'], true) ?></small>
                                <?php elseif ($e['estado'] === 'atual') : ?>
                                    <small>próxima etapa</small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($avaliacao) : ?>
            <div class="ac-card">
                <div class="ac-body">
                    <b>Sua avaliação: <?= (int) $avaliacao->nota ?>/10</b>
                    <?php if ($avaliacao->comentario) : ?>
                        <p class="muted" style="margin:8px 0 0"><?= nl2br(html_escape($avaliacao->comentario)) ?></p>
                    <?php endif; ?>
                    <p style="margin:8px 0 0">Obrigado por nos ajudar a melhorar.</p>
                </div>
            </div>
        <?php elseif ($podeAvaliar) : ?>
            <div class="ac-card">
                <div class="ac-body">
                    <form method="post" action="<?= site_url('acompanhar/avaliar') ?>">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                        <input type="hidden" name="token" value="<?= html_escape($token) ?>">
                        <b>De 0 a 10, quanto você recomendaria nosso serviço a um amigo?</b>
                        <div class="ac-nps">
                            <?php for ($n = 0; $n <= 10; $n++) : ?>
                                <input type="radio" name="nota" id="nota<?= $n ?>" value="<?= $n ?>" required>
                                <label for="nota<?= $n ?>"><?= $n ?></label>
                            <?php endfor; ?>
                        </div>
                        <div class="ac-legenda"><span>Nada provável</span><span>Muito provável</span></div>
                        <label for="comentario" style="margin-top:14px">Quer deixar um comentário? (opcional)</label>
                        <textarea name="comentario" id="comentario" rows="3" maxlength="1000"></textarea>
                        <button type="submit" class="btn btn-primary" style="margin-top:10px">Enviar avaliação</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="ac-rodape">
            <?= html_escape($emitente->nome ?? $appName) ?>
            <?php if (! empty($emitente->telefone)) : ?> · <?= html_escape($emitente->telefone) ?><?php endif; ?>
            <?php if (! empty($emitente->email)) : ?> · <?= html_escape($emitente->email) ?><?php endif; ?>
        </div>
    </div>
</body>

</html>
