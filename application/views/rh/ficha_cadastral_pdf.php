<?php
$emp = $emitente ?? null;
$c = $colaborador;
$maskCpf = function ($cpf) {
    $n = preg_replace('/\D/', '', (string) $cpf);
    if (strlen($n) !== 11) {
        return $cpf ?: '—';
    }
    return substr($n, 0, 3) . '.' . substr($n, 3, 3) . '.' . substr($n, 6, 3) . '-' . substr($n, 9, 2);
};
?>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
    .cab { border-bottom: 2px solid #1e3a5f; padding-bottom: 8px; margin-bottom: 14px; }
    h1 { margin: 0; font-size: 16px; color: #1e3a5f; }
    h2 { font-size: 13px; margin: 14px 0 6px; color: #1e3a5f; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
    table.dados { width: 100%; border-collapse: collapse; }
    table.dados td { padding: 4px 6px; vertical-align: top; }
    table.dados td.lbl { width: 28%; color: #555; }
    .foto { width: 90px; height: 110px; border: 1px solid #ccc; object-fit: cover; }
    .rodape { margin-top: 28px; font-size: 9px; color: #888; text-align: center; }
    .aviso { background: #f0f4f8; border: 1px solid #c5d0dc; padding: 8px; margin-top: 16px; font-size: 10px; }
    .assin { margin-top: 36px; width: 100%; }
    .assin td { border: none; border-top: 1px solid #333; text-align: center; padding-top: 4px; width: 45%; }
</style>

<div class="cab">
    <table style="width:100%"><tr>
        <td style="width:70%">
            <h1><?= htmlspecialchars($emp->nome ?? 'Empresa') ?></h1>
            <?php if (! empty($emp->cnpj)): ?><div>CNPJ: <?= htmlspecialchars($emp->cnpj) ?></div><?php endif; ?>
            <?php if (! empty($emp->rua)): ?>
                <div style="font-size:10px;color:#555"><?= htmlspecialchars(trim(($emp->rua ?? '') . ', ' . ($emp->numero ?? '') . ' — ' . ($emp->cidade ?? '') . '/' . ($emp->uf ?? ''))) ?></div>
            <?php endif; ?>
        </td>
        <td style="text-align:right;vertical-align:top">
            <strong style="font-size:13px">FICHA CADASTRAL</strong><br>
            <span style="font-size:10px">Para liberação de entrada em cliente</span><br>
            <span style="font-size:9px;color:#888">Emitida em <?= date('d/m/Y H:i') ?></span>
        </td>
    </tr></table>
</div>

<table style="width:100%"><tr>
    <td style="width:100px;vertical-align:top">
        <?php if (! empty($c->foto_base64)): ?>
            <img class="foto" src="<?= $c->foto_base64 ?>">
        <?php else: ?>
            <div class="foto" style="background:#eee;text-align:center;line-height:110px;color:#999">SEM FOTO</div>
        <?php endif; ?>
    </td>
    <td style="vertical-align:top;padding-left:12px">
        <div style="font-size:15px;font-weight:bold"><?= htmlspecialchars($c->nome) ?></div>
        <div><?= htmlspecialchars($c->cargo ?: 'Cargo não informado') ?>
            <?= $c->departamento ? ' · ' . htmlspecialchars($c->departamento) : '' ?></div>
        <div style="margin-top:4px;color:#555">
            Situação: <?= $c->situacao ? '<strong style="color:#166534">Ativo</strong>' : '<strong style="color:#991b1b">Inativo</strong>' ?>
            · Contrato: <?= htmlspecialchars($c->tipo_contrato ?? '—') ?>
        </div>
    </td>
</tr></table>

<h2>Dados pessoais</h2>
<table class="dados">
    <tr><td class="lbl">CPF</td><td><?= htmlspecialchars($maskCpf($c->cpf)) ?></td>
        <td class="lbl">RG</td><td><?= htmlspecialchars($c->rg ?: '—') ?></td></tr>
    <tr><td class="lbl">Nascimento</td><td><?= $c->data_nascimento ? date('d/m/Y', strtotime($c->data_nascimento)) : '—' ?></td>
        <td class="lbl">Celular</td><td><?= htmlspecialchars($c->celular ?: '—') ?></td></tr>
    <tr><td class="lbl">E-mail</td><td colspan="3"><?= htmlspecialchars($c->email ?: '—') ?></td></tr>
</table>

<h2>Carteira de trabalho / documentos</h2>
<table class="dados">
    <tr><td class="lbl">CTPS nº</td><td><?= htmlspecialchars($c->ctps_numero ?? '—') ?></td>
        <td class="lbl">Série / UF</td><td><?= htmlspecialchars(trim(($c->ctps_serie ?? '') . ' / ' . ($c->ctps_uf ?? ''), ' /')) ?: '—' ?></td></tr>
    <tr><td class="lbl">Emissão CTPS</td><td><?= ! empty($c->ctps_data_emissao) ? date('d/m/Y', strtotime($c->ctps_data_emissao)) : '—' ?></td>
        <td class="lbl">PIS/PASEP</td><td><?= htmlspecialchars($c->pis_pasep ?? '—') ?></td></tr>
</table>

<h2>Vínculo empregatício</h2>
<table class="dados">
    <tr><td class="lbl">Admissão</td><td><?= $c->admissao ? date('d/m/Y', strtotime($c->admissao)) : '—' ?></td>
        <td class="lbl">Unidade</td><td><?= $unidade ? htmlspecialchars($unidade->nome) : '—' ?></td></tr>
    <?php if (! empty($c->demissao)): ?>
    <tr><td class="lbl">Demissão</td><td colspan="3"><?= date('d/m/Y', strtotime($c->demissao)) ?></td></tr>
    <?php endif; ?>
</table>

<div class="aviso">
    Documento destinado à identificação do colaborador junto a clientes/terceiros para liberação de acesso às dependências.
    Os dados pessoais devem ser tratados conforme a LGPD. Validade: sob demanda do solicitante.
</div>

<table class="assin">
    <tr><td>Responsável / RH</td><td>Cliente / Portaria</td></tr>
</table>
<div class="rodape">ID colaborador #<?= (int) $c->id ?> · <?= htmlspecialchars($emp->nome ?? '') ?></div>
