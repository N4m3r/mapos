<?php
/** Fragmento (sem layout) com os detalhes de um ponto. Variaveis: ver Obras::pontoDetalhes(). */
$vazio = '—';
$val = function ($v) use ($vazio) {
    $v = trim((string) $v);
    return $v === '' ? $vazio : html_escape($v);
};
$dt = function ($d) use ($vazio) {
    $ts = (! empty($d) && strpos((string) $d, '0000-00-00') !== 0) ? strtotime((string) $d) : false;
    return $ts ? date('d/m/Y H:i', $ts) : $vazio;
};
$tipo = $tipos[$ponto->tipo] ?? ['rotulo' => $ponto->tipo, 'icone' => 'bx-package'];
$st = $status_lista[$ponto->status] ?? ['rotulo' => $ponto->status, 'classe' => 'neutro'];
$rotAg = Obra_pontos_model::rotuloAguardando($ponto->aguardando ?? null);
$comNome = trim((string) ($ponto->responsavel_nome ?? ''));
$com = $comNome !== '' ? $comNome : (! empty($ponto->aguardando) ? 'Equipe' : '');
?>
<div class="ptd">
    <div class="ptd-cab">
        <h4 class="ptd-ident"><i class="bx <?= html_escape($tipo['icone'] ?? 'bx-package') ?>" aria-hidden="true"></i> <?= html_escape($ponto->identificacao) ?></h4>
        <span class="ptd-tipo"><?= html_escape($tipo['rotulo']) ?></span>
        <span class="ui-selo <?= html_escape($st['classe'] ?? '') ?>"><?= html_escape($st['rotulo']) ?></span>
        <?php if (trim((string) $ponto->local) !== ''): ?><span class="ptd-local"><i class="bx bx-map" aria-hidden="true"></i> <?= html_escape($ponto->local) ?></span><?php endif; ?>
    </div>

    <h5 class="ptd-sec">Dados da instalação</h5>
    <dl class="ptd-dl">
        <dt>IP</dt><dd><?= $val($ponto->ip) ?></dd>
        <dt>Canal</dt><dd><?= $val($ponto->canal) ?></dd>
        <dt>Porta</dt><dd><?= $val($ponto->porta) ?></dd>
        <dt>Modelo</dt><dd><?= $val($ponto->modelo ?? '') ?></dd>
        <dt>Série / MAC</dt><dd><?= $val($ponto->serie ?? '') ?></dd>
        <dt>Cabo</dt><dd><?= (float) $ponto->metragem_cabo > 0 ? html_escape(number_format((float) $ponto->metragem_cabo, 2, ',', '.')) . ' m' : $vazio ?></dd>
        <dt>Com quem</dt><dd><?= $val($com) ?><?php if ($rotAg !== ''): ?> <small>(<?= html_escape($rotAg) ?>)</small><?php endif; ?></dd>
        <?php if (trim((string) ($ponto->instrucao ?? '')) !== ''): ?><dt>Instrução</dt><dd><?= html_escape($ponto->instrucao) ?></dd><?php endif; ?>
        <?php if (trim((string) ($ponto->observacao ?? '')) !== ''): ?><dt>Observação</dt><dd><?= html_escape($ponto->observacao) ?></dd><?php endif; ?>
        <dt>Cadastrado em</dt><dd><?= $dt($ponto->data_cadastro ?? null) ?></dd>
        <dt>Última atualização</dt><dd><?= $dt($ponto->atualizado_em ?? null) ?><?= $atualizado_por_nome !== '' ? ' por ' . html_escape($atualizado_por_nome) : '' ?></dd>
    </dl>

    <h5 class="ptd-sec">Fotos</h5>
    <?php if (! $fotos_por_etapa): ?>
        <p class="ptd-vazio">Nenhuma foto ainda.</p>
    <?php else: foreach ($fotos_por_etapa as $etapa => $fotos): ?>
        <div class="ptd-etapa"><strong><?= html_escape($status_lista[$etapa]['rotulo'] ?? ($etapa !== '' ? $etapa : 'Sem etapa')) ?></strong></div>
        <ul class="ptd-fotos">
            <?php foreach ($fotos as $f):
                $etq = $f->legenda === Obra_pontos_model::LEGENDA_ETIQUETA;
                $quem = trim((string) $f->usuario);
                $url = site_url('obras/fotoPonto/' . (int) $f->idFoto); ?>
                <li class="<?= $etq ? 'ptd-foto-etq' : '' ?>">
                    <a href="<?= $url ?>" target="_blank" rel="noopener" title="Abrir foto inteira em nova aba">
                        <img src="<?= $url ?>" alt="Foto de <?= html_escape($ponto->identificacao) ?><?= $etq ? ' (etiqueta)' : '' ?>" width="96" height="96" loading="lazy">
                    </a>
                    <?php if ($etq): ?><span class="ui-selo info">Etiqueta</span><?php endif; ?>
                    <small><?= $quem !== '' ? html_escape($quem) . ' · ' : '' ?><?= $dt($f->data) ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; endif; ?>

    <?php if ($ponto->tipo === 'camera'): ?>
        <h5 class="ptd-sec">Teste de imagem</h5>
        <?php if (! $teste): ?>
            <p class="ptd-vazio">Ainda não testada.</p>
        <?php else: ?>
            <ul class="ptd-teste">
                <?php foreach (Obra_pontos_model::TESTE_ITENS as $k => $rot): $ok = ! empty($teste->itens[$k]); ?>
                    <li class="<?= $ok ? 'ptd-ok' : 'ptd-nok' ?>"><span aria-hidden="true"><?= $ok ? '✓' : '✗' ?></span> <?= html_escape($rot) ?><span class="ptd-sr"><?= $ok ? ' (ok)' : ' (não)' ?></span></li>
                <?php endforeach; ?>
            </ul>
            <p class="ptd-meta">Retenção: <?= $teste->retencao_dias ? (int) $teste->retencao_dias . ' dias' : $vazio ?> · Testado por <?= $val($teste->usuario ?? '') ?> em <?= $dt($teste->data) ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <h5 class="ptd-sec">Histórico</h5>
    <?php if (! $historico): ?>
        <p class="ptd-vazio">Sem registros.</p>
    <?php else: ?>
        <ul class="ptd-hist">
            <?php foreach ($historico as $h): ?>
                <li>
                    <span class="ptd-hist-q"><?= html_escape($h['quando']) ?></span>
                    <strong><?= html_escape($h['titulo']) ?></strong>
                    <?= $h['usuario'] !== '' ? ' · ' . html_escape($h['usuario']) : '' ?>
                    <?php if ($h['texto'] !== ''): ?><div class="ptd-hist-obs"><?= html_escape($h['texto']) ?></div><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h5 class="ptd-sec">OS vinculadas</h5>
    <?php if (! $oss): ?>
        <p class="ptd-vazio">Nenhuma OS vinculada.</p>
    <?php else: ?>
        <ul class="ptd-os">
            <?php foreach ($oss as $o): ?>
                <li><a href="<?= site_url('os/visualizar/' . (int) $o->idOs) ?>" target="_blank" rel="noopener">OS #<?= (int) $o->idOs ?></a> <span class="ui-selo neutro"><?= html_escape($o->status) ?></span></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
