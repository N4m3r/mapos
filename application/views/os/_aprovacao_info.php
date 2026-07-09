<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Bloco de registro da decisão do cliente na OS (para tela e impressão):
 * quem APROVOU/reprovou o orçamento (via link de aprovação) e/ou quem
 * ACEITOU/recusou o serviço realizado (via link de aceite).
 *
 * Espera a variável $result (OS carregada por Os_model::getById). Os campos
 * podem não existir se as migrations ainda não foram aplicadas — por isso o
 * uso de empty()/isset() (não emitem warning para propriedade inexistente).
 */
$temAprov = ! empty($result->aprovacao_status) && in_array($result->aprovacao_status, ['aprovado', 'reprovado'], true);
$temAceite = ! empty($result->aceite_status) && in_array($result->aceite_status, ['aceito', 'recusado'], true);

if ($temAprov || $temAceite) : ?>
    <div style="margin-top:12px;border:1px solid #ddd;border-radius:6px;padding:10px 12px;font-size:13px">
        <strong style="display:block;margin-bottom:6px">Registro de aprovação do cliente</strong>

        <?php if ($temAprov) : ?>
            <div>
                Orçamento <strong><?= $result->aprovacao_status === 'aprovado' ? 'APROVADO' : 'REPROVADO' ?></strong>
                por <strong><?= html_escape($result->aprovacao_nome ?: '—') ?></strong>
                <?= ! empty($result->aprovacao_data) ? ' em ' . date('d/m/Y H:i', strtotime($result->aprovacao_data)) : '' ?>
                <?= ! empty($result->aprovacao_ip) ? ' (IP ' . html_escape($result->aprovacao_ip) . ')' : '' ?>
                <?php if (! empty($result->aprovacao_obs)) : ?><br><em>Motivo: <?= html_escape($result->aprovacao_obs) ?></em><?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($temAceite) : ?>
            <div style="margin-top:<?= $temAprov ? '6px' : '0' ?>">
                Serviço <strong><?= $result->aceite_status === 'aceito' ? 'ACEITO' : 'RECUSADO' ?></strong>
                por <strong><?= html_escape($result->aceite_nome ?: '—') ?></strong>
                <?= ! empty($result->aceite_data) ? ' em ' . date('d/m/Y H:i', strtotime($result->aceite_data)) : '' ?>
                <?= ! empty($result->aceite_ip) ? ' (IP ' . html_escape($result->aceite_ip) . ')' : '' ?>
                <?php if (! empty($result->aceite_obs)) : ?><br><em>Observação: <?= html_escape($result->aceite_obs) ?></em><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
