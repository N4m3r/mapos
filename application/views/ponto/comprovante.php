<?php
$labels = [
    'entrada' => 'Entrada', 'saida' => 'Saída',
    'inicio_intervalo' => 'Início do intervalo', 'fim_intervalo' => 'Fim do intervalo',
];
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprovante de Ponto</title>
    <style>
        body { font-family: 'Courier New', monospace; background:#f3f4f6; margin:0; padding:20px; }
        .ticket { max-width: 360px; margin: 0 auto; background:#fff; padding:20px; border-radius:12px;
            box-shadow:0 4px 16px rgba(0,0,0,.08); }
        .ticket h2 { text-align:center; font-size:16px; margin:0 0 4px; }
        .ticket .sub { text-align:center; color:#6b7280; font-size:12px; margin-bottom:14px; }
        .row { display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px dashed #e5e7eb; font-size:13px; }
        .row .k { color:#6b7280; }
        .big { text-align:center; font-size:30px; font-weight:bold; margin:12px 0; }
        .foot { text-align:center; font-size:11px; color:#9ca3af; margin-top:14px; }
        @media print { body { background:#fff; } .noprint { display:none; } }
        .btn { display:block; width:100%; text-align:center; padding:10px; margin-top:14px; border:none;
            border-radius:8px; background:#667eea; color:#fff; text-decoration:none; }
    </style>
</head>
<body>
    <div class="ticket">
        <h2>COMPROVANTE DE PONTO</h2>
        <div class="sub">Registro eletrônico</div>
        <div class="big"><?= date('H:i', strtotime($registro->data_hora)) ?></div>
        <div class="row"><span class="k">Tipo</span><span><?= $labels[$registro->tipo] ?? $registro->tipo ?></span></div>
        <div class="row"><span class="k">Colaborador</span><span><?= htmlspecialchars($colaborador->nome ?? '-') ?></span></div>
        <div class="row"><span class="k">Data</span><span><?= date('d/m/Y', strtotime($registro->data_hora)) ?></span></div>
        <div class="row"><span class="k">Registro nº</span><span>#<?= sprintf('%06d', $registro->id) ?></span></div>
        <?php if ($registro->dentro_geofence !== null): ?>
        <div class="row"><span class="k">Localização</span><span><?= $registro->dentro_geofence ? 'Dentro da área' : 'Fora ('.(int)$registro->distancia_metros.'m)' ?></span></div>
        <?php endif; ?>
        <?php if ($registro->face_score !== null): ?>
        <div class="row"><span class="k">Facial</span><span><?= number_format($registro->face_score, 2) ?></span></div>
        <?php endif; ?>
        <div class="foot">Emitido em <?= date('d/m/Y H:i') ?></div>
        <a href="javascript:window.print()" class="btn noprint">Imprimir</a>
    </div>
</body>
</html>
