<?php
$fmt = function ($min) { return $this->rh_calculo->minParaHoras($min); };
$brl = function ($v) { return number_format($v, 2, ',', '.'); };
$emp = $emitente ?? null;
$compLabel = date('m/Y', strtotime($competencia . '-01'));
<<<<<<< HEAD
$lblTipo = ['hora_extra'=>'Horas extras','adicional'=>'Adicional','comissao'=>'Comissão','bonus'=>'Bônus','adiantamento'=>'Adiantamento','desconto'=>'Desconto','falta'=>'Falta','vale'=>'Vale'];
=======
$lblTipo = ['hora_extra'=>'Horas extras','adicional'=>'Adicional','comissao'=>'Comissão','bonus'=>'Bônus','adiantamento'=>'Adiantamento','desconto'=>'Desconto','falta'=>'Falta','vale'=>'Vale','inss'=>'INSS','irrf'=>'IRRF','vale_transporte'=>'Vale-transporte'];
>>>>>>> 43f6f5a (correcao sintaxe)
$itens = $dados['resumo']['itens'];
$proventosItens = array_filter($itens, function ($i) { return $i->natureza !== 'desconto'; });
$descontosItens = array_filter($itens, function ($i) { return $i->natureza === 'desconto'; });
$h = $dados['horas'];
<<<<<<< HEAD
=======
$fgts = $dados['fgts'] ?? ($dados['resumo']['fgts'] ?? 0);
>>>>>>> 43f6f5a (correcao sintaxe)
?>
<style>
    body { font-family: sans-serif; font-size: 12px; color: #222; }
    .cab { border-bottom: 2px solid #333; padding-bottom: 6px; margin-bottom: 12px; }
    h2 { margin: 0; font-size: 16px; }
    .info td { padding: 2px 4px; }
    table.itens { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.itens th, table.itens td { border: 1px solid #999; padding: 4px 6px; }
    table.itens th { background: #eee; text-align: left; }
    .num { text-align: right; }
    .sec { background: #f2f2f2; font-weight: bold; }
    .tot { font-weight: bold; }
    .resumo-horas td { border: 1px solid #ccc; padding: 4px 6px; }
    .assinaturas { margin-top: 46px; width: 100%; }
    .assinaturas td { border: none; border-top: 1px solid #333; text-align: center; padding-top: 4px; width: 45%; }
</style>

<div class="cab">
    <table style="width:100%"><tr>
        <td style="text-align:left"><h2><?= htmlspecialchars($emp->nome ?? 'Empresa') ?></h2>
            <?php if (! empty($emp->cnpj)): ?><div>CNPJ: <?= htmlspecialchars($emp->cnpj) ?></div><?php endif; ?></td>
        <td style="text-align:right"><strong>RECIBO DE PAGAMENTO</strong><br>Competência: <?= $compLabel ?></td>
    </tr></table>
</div>

<table class="info" style="width:100%">
    <tr>
        <td><strong>Colaborador:</strong> <?= htmlspecialchars($colaborador->nome) ?></td>
        <td><strong>Cargo:</strong> <?= htmlspecialchars($colaborador->cargo ?: '-') ?></td>
    </tr>
    <tr>
        <td><strong>Admissão:</strong> <?= $colaborador->admissao ? date('d/m/Y', strtotime($colaborador->admissao)) : '-' ?></td>
        <td><strong>Contrato:</strong> <?= htmlspecialchars($colaborador->tipo_contrato ?? '-') ?></td>
    </tr>
</table>

<table class="itens">
    <thead><tr><th>Descrição</th><th class="num" style="width:120px">Proventos</th><th class="num" style="width:120px">Descontos</th></tr></thead>
    <tbody>
        <tr><td>Salário base</td><td class="num"><?= $brl($dados['salario_base']) ?></td><td class="num">—</td></tr>
        <?php foreach ($proventosItens as $it): ?>
            <tr><td><?= htmlspecialchars($it->descricao ?: ($lblTipo[$it->tipo] ?? $it->tipo)) ?></td>
                <td class="num"><?= $brl($it->valor) ?></td><td class="num">—</td></tr>
        <?php endforeach; ?>
        <?php foreach ($descontosItens as $it): ?>
            <tr><td><?= htmlspecialchars($it->descricao ?: ($lblTipo[$it->tipo] ?? $it->tipo)) ?></td>
                <td class="num">—</td><td class="num"><?= $brl($it->valor) ?></td></tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="sec"><td class="num">Totais</td><td class="num"><?= $brl($dados['proventos']) ?></td><td class="num"><?= $brl($dados['descontos']) ?></td></tr>
        <tr class="tot"><td colspan="2" class="num">LÍQUIDO A RECEBER</td><td class="num">R$ <?= $brl($dados['liquido']) ?></td></tr>
    </tfoot>
</table>

<div style="margin-top:12px"><strong>Resumo de horas (competência):</strong></div>
<table class="resumo-horas" style="width:100%;border-collapse:collapse;margin-top:4px">
    <tr>
        <td>Trabalhadas: <?= $fmt($h['minutos_trabalhados'] ?? 0) ?></td>
        <td>Extras 50%: <?= $fmt($h['minutos_extras_50'] ?? 0) ?></td>
        <td>Extras 100%: <?= $fmt($h['minutos_extras_100'] ?? 0) ?></td>
        <td>Faltas: <?= $fmt($h['minutos_faltas'] ?? 0) ?></td>
        <td>Saldo banco: <?= $fmt($h['saldo_banco_min'] ?? 0) ?></td>
    </tr>
</table>

<<<<<<< HEAD
=======
<?php if ($fgts > 0): ?>
<div style="margin-top:10px;font-size:11px">
    <strong>FGTS (depósito do empregador — não descontado do colaborador):</strong> R$ <?= $brl($fgts) ?>
</div>
<?php endif; ?>

>>>>>>> 43f6f5a (correcao sintaxe)
<table class="assinaturas">
    <tr><td>Colaborador</td><td><?= htmlspecialchars($emp->nome ?? 'Empresa') ?></td></tr>
</table>

<div style="text-align:right;margin-top:10px;font-size:9px;color:#888">
<<<<<<< HEAD
    Demonstrativo gerencial — não inclui encargos fiscais (INSS/IRRF/FGTS). Emitido em <?= date('d/m/Y H:i') ?>.
=======
    Demonstrativo com descontos legais configurados (CLT). Emitido em <?= date('d/m/Y H:i') ?>.
>>>>>>> 43f6f5a (correcao sintaxe)
</div>
