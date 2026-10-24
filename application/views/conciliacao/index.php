<?php $csrf = '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '" />'; ?>
<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="bx bx-transfer"></i></span>
                <h5>Conciliação bancária (OFX)</h5>
            </div>
            <div class="widget-content">
                <form action="<?= site_url('conciliacao/importar') ?>" method="post" enctype="multipart/form-data" class="well">
                    <?= $csrf ?>
                    <label>Extrato OFX (até 2 MB):</label>
                    <input type="file" name="ofx" accept=".ofx" required />
                    <button type="submit" class="button btn btn-mini btn-success"><span class="button__text2">Importar</span></button>
                </form>

                <table class="table table-bordered">
                    <thead>
                        <tr><th>Data</th><th>Descrição</th><th>Valor</th><th>Lançamento sugerido</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                    <?php if (! $linhas) : ?>
                        <tr><td colspan="5">Nenhuma transação pendente.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($linhas as $l) : $i = $l['item']; ?>
                        <tr>
                            <td><?= html_escape(date('d/m/Y', strtotime($i->data))) ?></td>
                            <td><?= html_escape($i->descricao) ?></td>
                            <td style="color: <?= $i->valor >= 0 ? '#2e9e5b' : '#d9534f' ?>">R$ <?= html_escape(number_format($i->valor, 2, ',', '.')) ?></td>
                            <td>
                            <?php if ($l['candidatos']) : ?>
                                <form action="<?= site_url('conciliacao/conciliar') ?>" method="post" style="margin:0">
                                    <?= $csrf ?>
                                    <input type="hidden" name="item" value="<?= (int) $i->id ?>" />
                                    <select name="lancamento">
                                    <?php foreach ($l['candidatos'] as $c) : ?>
                                        <option value="<?= (int) $c->idLancamentos ?>">#<?= (int) $c->idLancamentos ?> - <?= html_escape($c->descricao) ?> (<?= html_escape($c->cliente_fornecedor) ?>) venc. <?= html_escape(date('d/m/Y', strtotime($c->data_vencimento))) ?></option>
                                    <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="button btn btn-mini btn-success"><span class="button__text2">Conciliar</span></button>
                                </form>
                            <?php else : ?>
                                <em>Sem correspondência</em>
                            <?php endif; ?>
                            </td>
                            <td>
                                <form action="<?= site_url('conciliacao/ignorar') ?>" method="post" style="margin:0">
                                    <?= $csrf ?>
                                    <input type="hidden" name="item" value="<?= (int) $i->id ?>" />
                                    <button type="submit" class="button btn btn-mini btn-warning"><span class="button__text2">Ignorar</span></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
