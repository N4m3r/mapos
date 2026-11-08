<?php
/**
 * Aba Pendências da Central de Notificações.
 *
 * Atividades não programadas (últimos 90 dias) sem assinatura do solicitante
 * ou sem o Nº do chamado nos clientes que o exigem. A lista é recalculada a
 * cada abertura: resolveu na OS, a pendência some daqui.
 *
 * Variáveis: $pendencias (Atividade_pendencias_model::listar)
 */
$e = function ($v) {
    return html_escape((string) ($v ?? ''));
};

$semAssinatura = count(array_filter($pendencias, static function ($p) {
    return $p['falta_assinatura'];
}));
$semChamado = count(array_filter($pendencias, static function ($p) {
    return $p['falta_chamado'];
}));
?>

<div class="nt-visao-geral">
    <div class="nt-visao-item <?= $pendencias ? 'nt-falha' : 'nt-ok' ?>">
        <span>Atividades com pendência</span>
        <strong><?= count($pendencias) ?></strong>
    </div>
    <div class="nt-visao-item nt-pendente">
        <span>Sem assinatura</span>
        <strong><?= (int) $semAssinatura ?></strong>
    </div>
    <div class="nt-visao-item nt-pendente">
        <span>Sem Nº do chamado</span>
        <strong><?= (int) $semChamado ?></strong>
    </div>
</div>

<p class="ui-subtitulo" style="margin:8px 0 12px">
    Atividades não programadas dos últimos 90 dias. Falta de assinatura conta depois que a OS foi finalizada ou passou do dia;
    o chamado é cobrado dos clientes marcados com "Exige Nº do chamado" no cadastro.
</p>

<div class="widget-box">
    <div class="widget-content nopadding ui-tabela-rolagem">
        <table class="nt-tabela">
            <thead>
                <tr>
                    <th style="width:84px">OS</th>
                    <th>Cliente</th>
                    <th>Técnico</th>
                    <th style="width:92px">Data</th>
                    <th>Pendência</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pendencias)) { ?>
                    <tr>
                        <td colspan="5">
                            <div class="ui-vazio">
                                <i class="bx bx-check-shield"></i>
                                <strong>Nenhuma pendência</strong>
                                Todas as atividades não programadas estão assinadas e com chamado quando o cliente exige.
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                <?php foreach ($pendencias as $p) { ?>
                    <tr>
                        <td><a href="<?= site_url('os/visualizar/' . (int) $p['idOs']) ?>">#<?= sprintf('%04d', (int) $p['idOs']) ?></a></td>
                        <td><?= $e($p['cliente'] ?: '—') ?></td>
                        <td><?= $e($p['tecnico'] ?: '—') ?></td>
                        <td class="nt-dat"><?= $p['data'] ? $e(date('d/m/Y', strtotime($p['data']))) : '—' ?></td>
                        <td>
                            <?php if ($p['falta_assinatura']) { ?>
                                <span class="ui-selo alerta"><i class="bx bx-pen"></i> Sem assinatura</span>
                            <?php } ?>
                            <?php if ($p['falta_chamado']) { ?>
                                <span class="ui-selo perigo"><i class="bx bx-hash"></i> Sem Nº do chamado</span>
                            <?php } ?>
                            <span style="display:block;font-size:11px;opacity:.75"><?= $e($p['status']) ?></span>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
