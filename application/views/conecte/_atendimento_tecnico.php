<?php
/**
 * Aba "Atendimento do Técnico" dentro da OS do portal do cliente.
 * Mostra os check-ins, formulários, assinaturas e fotos registrados pelo
 * técnico durante o atendimento. Recebe do controller:
 *   $result, $checkins, $assinaturas (por tipo), $fotosPorEtapa, $respostasPorEtapa
 */
$checkins = isset($checkins) && is_array($checkins) ? $checkins : [];
$assinaturas = isset($assinaturas) && is_array($assinaturas) ? $assinaturas : [];
$fotosPorEtapa = isset($fotosPorEtapa) && is_array($fotosPorEtapa) ? $fotosPorEtapa : ['entrada' => [], 'durante' => [], 'saida' => []];
$respostasPorEtapa = isset($respostasPorEtapa) && is_array($respostasPorEtapa) ? $respostasPorEtapa : [];

$temFotos = ! empty($fotosPorEtapa['entrada']) || ! empty($fotosPorEtapa['durante']) || ! empty($fotosPorEtapa['saida']);
$rotulosEtapa = [
    'iniciar' => 'Ao iniciar o atendimento',
    'durante' => 'Durante o atendimento',
    'finalizar' => 'Ao finalizar o atendimento',
    'outros' => 'Outros',
];
$rotulosFoto = ['entrada' => 'Fotos de Entrada', 'durante' => 'Fotos Durante o Atendimento', 'saida' => 'Fotos de Saída'];
$rotulosAssinatura = [
    'tecnico_entrada' => 'Técnico - Entrada',
    'tecnico_saida' => 'Técnico - Saída',
    'cliente_saida' => 'Cliente - Saída',
];
?>
<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title" style="margin: -20px 0 0">
                <span class="icon"><i class="fas fa-user-cog"></i></span>
                <h5>Atendimento do Técnico</h5>
                <div class="buttons" style="padding-left:5px;">
                    <a target="_blank" title="Imprimir Relatório de Atendimento" class="button btn btn-mini btn-inverse" href="<?php echo site_url('mine/relatorioAtendimento/' . $result->idOs); ?>">
                        <span class="button__icon"><i class="bx bx-printer"></i></span> <span class="button__text">Imprimir Relatório</span></a>
                </div>
            </div>
            <div class="widget-content">

                <!-- Histórico de check-ins -->
                <h6 style="color:#2d335b; margin:4px 0 8px"><i class="bx bx-calendar-check"></i> Histórico de Atendimento</h6>
                <?php if (! empty($checkins)) { ?>
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Técnico</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                                <th>Tempo</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($checkins as $i => $c) {
                                $tempo = '-';
                                if (! empty($c->data_saida)) {
                                    $ent = new DateTime($c->data_entrada);
                                    $sai = new DateTime($c->data_saida);
                                    $tempo = $ent->diff($sai)->format('%hh %imin');
                                } ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= html_escape($c->nome_tecnico) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($c->data_entrada)) ?></td>
                                    <td><?= ! empty($c->data_saida) ? date('d/m/Y H:i', strtotime($c->data_saida)) : '-' ?></td>
                                    <td><?= $tempo ?></td>
                                    <td>
                                        <?php if (! empty($c->data_saida)) { ?>
                                            <span class="label label-success">Finalizado</span>
                                        <?php } else { ?>
                                            <span class="label label-warning">Em andamento</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php if (! empty($c->observacao_entrada) || ! empty($c->observacao_saida)) { ?>
                                    <tr>
                                        <td colspan="6" style="background:#fafafa">
                                            <?php if (! empty($c->observacao_entrada)) { ?>
                                                <strong>Obs. Entrada:</strong> <?= nl2br(html_escape($c->observacao_entrada)) ?><br>
                                            <?php } ?>
                                            <?php if (! empty($c->observacao_saida)) { ?>
                                                <strong>Obs. Saída:</strong> <?= nl2br(html_escape($c->observacao_saida)) ?>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p style="color:#888; margin:6px 0">Nenhum check-in registrado nesta OS.</p>
                <?php } ?>

                <!-- Respostas dos formulários -->
                <?php if (! empty($respostasPorEtapa)) { ?>
                    <h6 style="color:#2d335b; margin:16px 0 8px"><i class="bx bx-list-check"></i> Formulários de Atendimento</h6>
                    <?php foreach (['iniciar', 'durante', 'finalizar', 'outros'] as $etapa) {
                        if (empty($respostasPorEtapa[$etapa])) { continue; } ?>
                        <p style="font-weight:bold; color:#555; margin:8px 0 4px"><?= html_escape($rotulosEtapa[$etapa]) ?></p>
                        <?php foreach ($respostasPorEtapa[$etapa] as $resposta) { ?>
                            <div style="border:1px solid #e2e6ef; border-radius:6px; padding:10px 12px; margin-bottom:10px">
                                <div style="font-weight:bold; margin-bottom:6px"><?= html_escape($resposta->formulario_nome) ?></div>
                                <table class="table table-condensed" style="margin-bottom:0">
                                    <?php foreach ($resposta->itens as $item) { ?>
                                        <tr>
                                            <td style="width:40%; color:#555; border-top:none"><?= html_escape($item->label) ?></td>
                                            <td style="border-top:none"><?= $item->valor !== null && $item->valor !== '' ? nl2br(html_escape($item->valor)) : '<span style="color:#999">—</span>' ?></td>
                                        </tr>
                                    <?php } ?>
                                </table>
                            </div>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>

                <!-- Assinaturas -->
                <?php
                $assinaturasValidas = array_filter($assinaturas, fn ($a) => is_object($a) && ! empty($a->assinatura));
                if (! empty($assinaturasValidas)) { ?>
                    <h6 style="color:#2d335b; margin:16px 0 8px"><i class="bx bx-pen"></i> Assinaturas</h6>
                    <div class="row-fluid">
                        <?php foreach ($assinaturasValidas as $tipo => $a) {
                            $imgSrc = (isset($a->is_base64) && $a->is_base64) ? $a->url_visualizacao : base_url($a->assinatura); ?>
                            <div class="span4" style="text-align:center; margin-bottom:12px">
                                <div style="border:1px solid #e2e6ef; border-radius:6px; padding:8px; background:#fafafa; min-height:90px; display:flex; align-items:center; justify-content:center">
                                    <img src="<?= html_escape($imgSrc) ?>" alt="Assinatura" style="max-width:100%; max-height:90px">
                                </div>
                                <div style="font-weight:bold; margin-top:4px"><?= html_escape($rotulosAssinatura[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo))) ?></div>
                                <?php if (! empty($a->nome_assinante)) { ?>
                                    <div style="font-size:11px; color:#666"><?= html_escape($a->nome_assinante) ?></div>
                                <?php } ?>
                                <?php if (! empty($a->data_assinatura)) { ?>
                                    <div style="font-size:11px; color:#999"><?= date('d/m/Y H:i', strtotime($a->data_assinatura)) ?></div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>

                <!-- Fotos -->
                <?php if ($temFotos) { ?>
                    <h6 style="color:#2d335b; margin:16px 0 8px"><i class="bx bx-images"></i> Registro Fotográfico</h6>
                    <?php foreach (['entrada', 'durante', 'saida'] as $etapa) {
                        if (empty($fotosPorEtapa[$etapa])) { continue; } ?>
                        <p style="font-weight:bold; color:#555; margin:8px 0 4px"><?= html_escape($rotulosFoto[$etapa]) ?></p>
                        <div class="row-fluid">
                            <?php foreach ($fotosPorEtapa[$etapa] as $foto) {
                                $imgUrl = ! empty($foto->imagem_base64)
                                    ? base_url('index.php/checkin/verFotoDB/' . $foto->idFoto)
                                    : $foto->url; ?>
                                <div class="span3" style="margin-bottom:12px">
                                    <a href="<?= html_escape($imgUrl) ?>" target="_blank" style="display:block; border:1px solid #e2e6ef; border-radius:6px; overflow:hidden">
                                        <img src="<?= html_escape($imgUrl) ?>" alt="Foto do atendimento" style="width:100%; height:120px; object-fit:cover; display:block">
                                        <?php if (! empty($foto->descricao)) { ?>
                                            <div style="padding:5px; font-size:11px; color:#666; background:#f5f5f5"><?= html_escape($foto->descricao) ?></div>
                                        <?php } ?>
                                    </a>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                <?php } ?>

            </div>
        </div>
    </div>
</div>
