<?php
$emp = $emitente ?? null;
$c = $colaborador;
$maskCpf = function ($cpf) {
    $n = preg_replace('/\D/', '', (string) $cpf);
    if (strlen($n) !== 11) {
        return $cpf ? '***.***.***-**' : '—';
    }
    return '***.' . substr($n, 3, 3) . '.' . substr($n, 6, 3) . '-**';
};
?>
<style>
    body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 0; }
    .card {
        width: 320px;
        border: 2px solid #1e3a5f;
        border-radius: 10px;
        overflow: hidden;
        margin: 10px auto;
    }
    .header {
        background: #1e3a5f;
        color: #fff;
        text-align: center;
        padding: 10px 8px;
        font-size: 11px;
        font-weight: bold;
    }
    .body { padding: 12px; text-align: center; }
    .foto {
        width: 100px;
        height: 120px;
        object-fit: cover;
        border: 2px solid #e5e7eb;
        border-radius: 6px;
        margin: 0 auto 8px;
        display: block;
        background: #f3f4f6;
    }
    .nome { font-size: 14px; font-weight: bold; color: #111; margin: 4px 0; }
    .cargo { font-size: 11px; color: #4b5563; margin-bottom: 8px; }
    .meta { font-size: 10px; color: #6b7280; line-height: 1.5; text-align: left; padding: 0 8px; }
    .footer {
        background: #f3f4f6;
        border-top: 1px solid #e5e7eb;
        text-align: center;
        padding: 6px;
        font-size: 9px;
        color: #6b7280;
    }
    .badge-id {
        display: inline-block;
        background: #1e3a5f;
        color: #fff;
        font-size: 10px;
        padding: 2px 10px;
        border-radius: 10px;
        margin-top: 6px;
    }
</style>

<div class="card">
    <div class="header">
        <?= htmlspecialchars(mb_strtoupper($emp->nome ?? 'EMPRESA', 'UTF-8')) ?><br>
        <span style="font-weight:normal;font-size:9px">CRACHÁ DE IDENTIFICAÇÃO</span>
    </div>
    <div class="body">
        <?php if (! empty($c->foto_base64)): ?>
            <img class="foto" src="<?= $c->foto_base64 ?>">
        <?php else: ?>
            <div class="foto" style="line-height:120px;color:#9ca3af;font-size:10px">SEM FOTO</div>
        <?php endif; ?>
        <div class="nome"><?= htmlspecialchars($c->nome) ?></div>
        <div class="cargo"><?= htmlspecialchars($c->cargo ?: 'Colaborador') ?>
            <?= $c->departamento ? '<br>' . htmlspecialchars($c->departamento) : '' ?>
        </div>
        <div class="meta">
            CPF: <?= htmlspecialchars($maskCpf($c->cpf)) ?><br>
            <?php if (! empty($c->pis_pasep)): ?>PIS: <?= htmlspecialchars($c->pis_pasep) ?><br><?php endif; ?>
            Admissão: <?= $c->admissao ? date('d/m/Y', strtotime($c->admissao)) : '—' ?><br>
            <?php if ($unidade): ?>Unidade: <?= htmlspecialchars($unidade->nome) ?><br><?php endif; ?>
        </div>
        <div class="badge-id">ID <?= str_pad((string) $c->id, 5, '0', STR_PAD_LEFT) ?></div>
    </div>
    <div class="footer">
        <?= $c->situacao ? 'ATIVO' : 'INATIVO' ?> · <?= htmlspecialchars($c->tipo_contrato ?? 'CLT') ?>
        · Emitido <?= date('d/m/Y') ?>
    </div>
</div>
