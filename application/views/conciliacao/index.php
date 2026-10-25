<?php $csrf = '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '" />'; ?>
<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="ui-cabecalho">
            <div>
                <h1><i class="bx bx-transfer"></i> Conciliação bancária</h1>
                <p>Compare o extrato do banco com seus lançamentos e confirme o que já foi pago ou recebido.</p>
            </div>
        </div>

        <div class="ui-dica">
            <i class="bx bx-info-circle"></i>
            <div>Como obter o arquivo: no internet banking, abra <strong>Extrato</strong>, escolha o período e use <strong>Exportar / Baixar em OFX</strong> (tamanho máximo 2 MB). Depois envie o arquivo abaixo e confirme cada transação com o lançamento sugerido.</div>
        </div>

        <form action="<?= site_url('conciliacao/importar') ?>" method="post" enctype="multipart/form-data" class="ui-filtros">
            <?= $csrf ?>
            <div class="ui-campo">
                <label for="ofx">Extrato OFX (até 2 MB)</label>
                <input type="file" id="ofx" name="ofx" accept=".ofx" required />
            </div>
            <div class="ui-campo ui-campo-acao">
                <button type="submit" class="button btn btn-success"><span class="button__icon"><i class="bx bx-upload"></i></span><span class="button__text2">Importar</span></button>
            </div>
        </form>

        <div class="widget-box">
            <div class="widget-content nopadding ui-tabela-rolagem">
                <table class="table table-bordered">
                    <thead>
                        <tr><th>Data</th><th>Descrição</th><th>Valor</th><th>Lançamento sugerido</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                    <?php if (! $linhas) : ?>
                        <tr><td colspan="5"><div class="ui-vazio"><i class="bx bx-check-circle"></i><strong>Nenhuma transação pendente</strong>Importe um extrato OFX para começar a conciliar.</div></td></tr>
                    <?php endif; ?>
                    <?php foreach ($linhas as $l) : $i = $l['item']; ?>
                        <tr>
                            <td><?= html_escape(date('d/m/Y', strtotime($i->data))) ?></td>
                            <td><?= html_escape($i->descricao) ?></td>
                            <td><span class="ui-selo <?= $i->valor >= 0 ? 'sucesso' : 'perigo' ?>">R$ <?= html_escape(number_format($i->valor, 2, ',', '.')) ?></span></td>
                            <td>
                            <?php if ($l['candidatos']) : ?>
                                <form action="<?= site_url('conciliacao/conciliar') ?>" method="post" style="margin:0">
                                    <?= $csrf ?>
                                    <input type="hidden" name="item" value="<?= (int) $i->id ?>" />
                                    <select name="lancamento" aria-label="Lançamento correspondente">
                                    <?php foreach ($l['candidatos'] as $c) : ?>
                                        <option value="<?= (int) $c->idLancamentos ?>">#<?= (int) $c->idLancamentos ?> - <?= html_escape($c->descricao) ?> (<?= html_escape($c->cliente_fornecedor) ?>) venc. <?= html_escape(date('d/m/Y', strtotime($c->data_vencimento))) ?></option>
                                    <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="button btn btn-mini btn-success" title="Conciliar com o lançamento selecionado"><span class="button__icon"><i class="bx bx-check"></i></span><span class="button__text2">Conciliar</span></button>
                                </form>
                            <?php else : ?>
                                <span class="ui-selo">Sem correspondência</span>
                            <?php endif; ?>
                            </td>
                            <td>
                                <form action="<?= site_url('conciliacao/ignorar') ?>" method="post" style="margin:0">
                                    <?= $csrf ?>
                                    <input type="hidden" name="item" value="<?= (int) $i->id ?>" />
                                    <button type="submit" class="button btn btn-mini btn-warning" title="Ignorar esta transação"><span class="button__icon"><i class="bx bx-hide"></i></span><span class="button__text2">Ignorar</span></button>
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
