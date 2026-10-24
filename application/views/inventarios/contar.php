<?php
$numero = static function ($valor) {
    return $valor === null ? '' : rtrim(rtrim(number_format((float) $valor, 3, ',', ''), '0'), ',');
};
$aberto = $inventario->status === 'aberto';
?>
<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="bx bx-list-check"></i></span>
        <h5>Inventário #<?= (int) $inventario->id ?> - <?= html_escape($inventario->descricao) ?></h5>
    </div>
    <div class="widget-content nopadding">
        <div style="padding:12px 14px 0; display:flex; gap:12px; flex-wrap:wrap; align-items:center">
            <a href="<?= site_url('inventarios') ?>" class="btn btn-mini"><i class="bx bx-arrow-back"></i> Inventários</a>
            <span class="badge <?= $aberto ? 'badge-warning' : ($inventario->status === 'finalizado' ? 'badge-success' : '') ?>"><?= html_escape(ucfirst($inventario->status)) ?></span>
            <input type="search" id="filtroInventario" placeholder="Filtrar por nome ou código..." style="margin:0; width:260px">
            <label style="margin:0"><input type="checkbox" id="soDiferencas"> Só diferenças</label>
            <?php if ($aberto) { ?>
                <span style="color:#777; font-size:12px">"Sistema" é o estoque na abertura; a diferença é calculada sobre o estoque atual ao finalizar.</span>
            <?php } ?>
        </div>

        <form action="<?= site_url('inventarios/salvar/' . (int) $inventario->id) ?>" method="post" id="formInventario">
            <table class="table table-bordered table-condensed" style="margin-top:12px" id="tabelaInventario">
                <thead>
                    <tr>
                        <th style="width:110px">Código</th>
                        <th>Produto</th>
                        <th style="width:60px">Un.</th>
                        <th style="width:90px; text-align:right">Sistema</th>
                        <th style="width:90px; text-align:right">Atual</th>
                        <th style="width:110px; text-align:center">Contado</th>
                        <th style="width:90px; text-align:right"><?= $aberto ? 'Diferença' : 'Ajuste' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item) {
                        $diferenca = $aberto
                            ? ($item->contado === null ? null : (float) $item->contado - (float) $item->estoque_atual)
                            : ($item->ajuste === null ? null : (float) $item->ajuste); ?>
                        <tr data-busca="<?= html_escape(mb_strtolower($item->descricao . ' ' . $item->codDeBarra)) ?>" data-diferenca="<?= $diferenca ? 1 : 0 ?>">
                            <td><?= html_escape($item->codDeBarra) ?></td>
                            <td><?= html_escape($item->descricao) ?></td>
                            <td><?= html_escape($item->unidade) ?></td>
                            <td style="text-align:right"><?= $numero($item->estoque_sistema) ?></td>
                            <td style="text-align:right"><?= $numero($item->estoque_atual) ?></td>
                            <td style="text-align:center">
                                <?php if ($podeEditar) { ?>
                                    <input type="text" inputmode="decimal" name="contado[<?= (int) $item->produtos_id ?>]" value="<?= $numero($item->contado) ?>"
                                           data-atual="<?= (float) $item->estoque_atual ?>" class="campo-contado" style="width:80px; margin:0; text-align:right">
                                <?php } else {
                                    echo $item->contado === null ? '<span style="color:#aaa">-</span>' : $numero($item->contado);
                                } ?>
                            </td>
                            <td style="text-align:right; font-weight:600" class="celula-diferenca"><?php
                                if ($diferenca !== null && $diferenca != 0) {
                                    echo '<span style="color:' . ($diferenca < 0 ? '#c0392b' : '#27ae60') . '">' . ($diferenca > 0 ? '+' : '') . $numero($diferenca) . '</span>';
                                } elseif ($diferenca !== null) {
                                    echo '<span style="color:#aaa">0</span>';
                                } ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <?php if ($podeEditar) { ?>
                <div style="padding:0 14px 14px; display:flex; gap:10px; flex-wrap:wrap">
                    <button type="submit" class="button btn btn-primary"><span class="button__icon"><i class="bx bx-save"></i></span><span class="button__text2">Salvar contagem</span></button>
                    <button type="submit" name="finalizar" value="1" class="button btn btn-success"
                            onclick="return confirm('Finalizar o inventário? O estoque dos produtos contados será ajustado para o valor contado.');">
                        <span class="button__icon"><i class="bx bx-check-double"></i></span><span class="button__text2">Finalizar e ajustar estoque</span></button>
                </div>
            <?php } ?>
        </form>

        <?php if ($podeEditar) { ?>
            <form action="<?= site_url('inventarios/cancelar/' . (int) $inventario->id) ?>" method="post" style="padding:0 14px 14px"
                  onsubmit="return confirm('Cancelar este inventário? Nenhum estoque será alterado.');">
                <button type="submit" class="btn btn-mini btn-danger">Cancelar inventário</button>
            </form>
        <?php } ?>
    </div>
</div>

<script>
    (function () {
        var filtro = document.getElementById('filtroInventario');
        var soDiferencas = document.getElementById('soDiferencas');
        var linhas = document.querySelectorAll('#tabelaInventario tbody tr');

        function aplicar() {
            var termo = filtro.value.trim().toLowerCase();
            linhas.forEach(function (tr) {
                var visivel = (!termo || tr.dataset.busca.indexOf(termo) !== -1) && (!soDiferencas.checked || tr.dataset.diferenca === '1');
                tr.style.display = visivel ? '' : 'none';
            });
        }

        filtro.addEventListener('input', aplicar);
        soDiferencas.addEventListener('change', aplicar);

        // Diferenca ao vivo enquanto digita
        document.querySelectorAll('.campo-contado').forEach(function (campo) {
            campo.addEventListener('input', function () {
                var tr = campo.closest('tr');
                var celula = tr.querySelector('.celula-diferenca');
                var valor = campo.value.replace(',', '.').trim();
                if (valor === '' || isNaN(valor)) {
                    celula.innerHTML = '';
                    tr.dataset.diferenca = '0';
                    return;
                }
                var dif = Math.round((parseFloat(valor) - parseFloat(campo.dataset.atual)) * 1000) / 1000;
                celula.innerHTML = dif === 0 ? '<span style="color:#aaa">0</span>'
                    : '<span style="color:' + (dif < 0 ? '#c0392b' : '#27ae60') + '">' + (dif > 0 ? '+' : '') + String(dif).replace('.', ',') + '</span>';
                tr.dataset.diferenca = dif === 0 ? '0' : '1';
            });
        });

        // Enter no campo vai para o proximo (leitor de codigo / digitacao rapida) em vez de enviar
        document.getElementById('formInventario').addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.classList.contains('campo-contado')) {
                e.preventDefault();
                var campos = Array.prototype.filter.call(document.querySelectorAll('.campo-contado'), function (c) { return c.offsetParent !== null; });
                var i = campos.indexOf(e.target);
                if (campos[i + 1]) { campos[i + 1].focus(); campos[i + 1].select(); }
            }
        });
    })();
</script>
