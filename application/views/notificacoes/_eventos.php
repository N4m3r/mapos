<?php
/**
 * Aba Eventos da Central de Notificacoes.
 *
 * Lista o catalogo agrupado por area, com o resumo do que cada evento dispara
 * hoje. Acoes: configurar o evento, ligar/desligar.
 *
 * Variaveis: $porEvento, $eventosSemGatilho, $ordemGrupos, $podeSistema
 */
$e = function ($v) {
    return html_escape((string) ($v ?? ''));
};

$grupos = [];
foreach ($porEvento as $slug => $bloco) {
    $grupo = $bloco['evento']['grupo'] ?? 'Outros';
    $grupos[$grupo][$slug] = $bloco;
}
foreach ($eventosSemGatilho as $slug => $info) {
    $grupos[$info['grupo']][$slug] = ['evento' => $info, 'gatilhos' => []];
}

$ordem = [];
foreach ($ordemGrupos as $g) {
    $ordem[] = $g;
}
foreach (array_keys($grupos) as $g) {
    if (! in_array($g, $ordem, true)) {
        $ordem[] = $g;
    }
}

$rotuloCanal = Notification_triggers_model::canaisDisponiveis();
$rotuloDestino = Notification_triggers_model::destinatariosDisponiveis();
?>

<?php if (empty($tabelaGatilhos)) { ?>
    <div class="ui-dica alerta">
        <i class="bx bx-error-circle"></i>
        <div>
            A tabela de gatilhos ainda não existe neste banco, então nenhum evento pode ser configurado.
            Rode a atualização do banco (Configurações &gt; Atualizações) e recarregue esta tela.
        </div>
    </div>
<?php } ?>

<?php if (! empty($tabelaGatilhos) && $podeSistema) { ?>
    <form class="nt-criar-evento" method="get" action="<?= site_url('notificacoes/novo') ?>">
        <label for="nt-novo-evento"><strong>Criar evento de notificação</strong></label>
        <select class="form-select" id="nt-novo-evento" name="evento" required>
            <option value="">Escolha quando o aviso deve sair…</option>
            <?php foreach ($ordem as $grupoSel) { ?>
                <?php if (empty($grupos[$grupoSel])) { continue; } ?>
                <optgroup label="<?= $e($grupoSel) ?>">
                    <?php foreach ($grupos[$grupoSel] as $slugSel => $itemSel) { ?>
                        <option value="<?= $e($slugSel) ?>">
                            <?= $e($itemSel['evento']['nome']) ?><?= empty($itemSel['gatilhos']) ? ' (não configurado)' : '' ?>
                        </option>
                    <?php } ?>
                </optgroup>
            <?php } ?>
        </select>
        <button type="submit" class="button btn btn-success">
            <span class="button__icon"><i class="bx bx-plus-circle"></i></span>
            <span class="button__text2">Criar evento</span>
        </button>
    </form>
<?php } ?>

<div class="ui-dica">
    <i class="bx bx-info-circle"></i>
    <div>
        Cada evento abaixo é um aviso automático do sistema. Use <strong>Configurar</strong> para escolher
        por onde ele sai (e-mail, WhatsApp), para quem e com qual texto. Evento sem gatilho ainda não avisa ninguém.
    </div>
</div>

<?php foreach ($ordem as $grupo) { ?>
    <?php if (empty($grupos[$grupo])) { continue; } ?>
    <?php
    $itens = $grupos[$grupo];
    $ativos = 0;
    foreach ($itens as $item) {
        foreach ($item['gatilhos'] as $g) {
            if ((int) $g->ativo === 1) {
                $ativos++;
                break;
            }
        }
    }
    ?>
    <section class="nt-grupo">
        <h3 class="nt-grupo-titulo">
            <?= $e($grupo) ?>
            <span class="ui-selo neutro"><?= count($itens) ?> evento(s)</span>
            <?php if ($ativos > 0) { ?>
                <span class="ui-selo sucesso"><?= $ativos ?> ativo(s)</span>
            <?php } ?>
        </h3>

        <div class="nt-eventos">
            <?php foreach ($itens as $slug => $item) { ?>
                <?php
                $info = $item['evento'];
                $resumo = notif_resumo_evento($item['gatilhos']);
                $principal = $item['gatilhos'][0] ?? null;
                $linkEvento = site_url('notificacoes/evento/' . rawurlencode($slug));
                ?>
                <article class="nt-evento<?= $resumo['ativo'] ? '' : ' nt-inativo' ?>">
                    <span class="nt-evento-icone"><i class="bx <?= $e(notif_evento_icone($slug)) ?>"></i></span>
                    <div class="nt-evento-corpo">
                        <p class="nt-evento-nome">
                            <?= $e($info['nome']) ?>
                            <?php if (empty($item['gatilhos'])) { ?>
                                <span class="ui-selo alerta">não configurado</span>
                            <?php } elseif (! $resumo['ativo']) { ?>
                                <span class="ui-selo neutro">desligado</span>
                            <?php } ?>
                        </p>
                        <p class="nt-evento-desc">
                            <?= $principal && ! empty($principal->descricao) ? $e($principal->descricao) : 'Sem descrição cadastrada.' ?>
                        </p>

                        <?php if (! empty($resumo['canais']) || ! empty($resumo['destinos'])) { ?>
                            <div class="nt-evento-meta">
                                <?php foreach ($resumo['canais'] as $canal) { ?>
                                    <span class="ui-selo info"><i class="bx <?= $e(notif_canal_icone($canal)) ?>"></i> <?= $e($rotuloCanal[$canal] ?? $canal) ?></span>
                                <?php } ?>
                                <?php foreach ($resumo['destinos'] as $destino) { ?>
                                    <span class="ui-selo neutro"><?= $e($rotuloDestino[$destino] ?? $destino) ?></span>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="nt-evento-acoes">
                        <a href="<?= $linkEvento ?>" class="btn btn-sm btn-primary" title="Configurar este evento" aria-label="Configurar <?= $e($info['nome']) ?>">
                            <i class="bx bx-slider-alt"></i>
                        </a>
                        <?php if ($podeSistema && $principal) { ?>
                            <a href="<?= site_url('notificacoes/alternar/' . rawurlencode($slug)) ?>"
                               class="btn btn-sm <?= $resumo['ativo'] ? 'btn-warning' : 'btn-success' ?>"
                               data-confirmar="<?= $resumo['ativo'] ? 'Desligar os avisos deste evento?' : 'Ligar os avisos deste evento?' ?>"
                               title="<?= $resumo['ativo'] ? 'Desligar' : 'Ligar' ?>"
                               aria-label="<?= $resumo['ativo'] ? 'Desligar' : 'Ligar' ?>">
                                <i class="bx <?= $resumo['ativo'] ? 'bx-pause' : 'bx-play' ?>"></i>
                            </a>
                        <?php } ?>
                    </div>
                </article>
            <?php } ?>
        </div>
    </section>
<?php } ?>
