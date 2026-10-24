<?php
$fmt = fn ($v) => number_format((float) $v, 2, ',', '.');
?>
<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="fas fa-file-import"></i></span>
                <h5>Entradas / Créditos de IBS/CBS</h5>
            </div>
            <div class="widget-content">

                <?php if ($this->session->flashdata('success')) { ?>
                    <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
                <?php } ?>
                <?php if ($this->session->flashdata('error')) { ?>
                    <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
                <?php } ?>

                <div class="alert alert-info" style="margin-bottom:12px">
                    Registre aqui as <strong>notas de compra</strong> dos seus fornecedores. É delas que sai o
                    <strong>crédito de IBS/CBS</strong> que reduz o imposto a pagar. Veja o resultado em
                    <a href="<?= site_url('entradas/apuracao' . ($competencia ? '?competencia=' . urlencode($competencia) : '')) ?>"><strong>Apuração do período</strong></a>.
                </div>

                <!-- Barra de ações -->
                <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;margin-bottom:14px">
                    <form action="<?= site_url('entradas') ?>" method="get" style="margin:0">
                        <label style="font-size:12px;color:#666;display:block">Competência (AAAA-MM)</label>
                        <input type="month" name="competencia" value="<?= html_escape($competencia) ?>" onchange="this.form.submit()" />
                        <?php if ($competencia) { ?><a href="<?= site_url('entradas') ?>" class="hint" style="margin-left:6px">limpar</a><?php } ?>
                    </form>
                    <button type="button" class="button btn btn-success" onclick="toggleBox('boxImportar')">
                        <span class="button__icon"><i class="bx bx-upload"></i></span><span class="button__text2">Importar XML</span>
                    </button>
                    <button type="button" class="button btn btn-primary" onclick="toggleBox('boxManual')">
                        <span class="button__icon"><i class="bx bx-plus"></i></span><span class="button__text2">Lançar manual</span>
                    </button>
                    <a href="<?= site_url('entradas/apuracao' . ($competencia ? '?competencia=' . urlencode($competencia) : '')) ?>" class="button btn btn-warning">
                        <span class="button__icon"><i class="bx bx-calculator"></i></span><span class="button__text2">Apuração do período</span>
                    </a>
                </div>

                <!-- Importar XML -->
                <div id="boxImportar" style="display:none;border:1px solid #eee;padding:12px;border-radius:6px;margin-bottom:14px">
                    <?= form_open_multipart('entradas/importar') ?>
                        <h5 style="margin-top:0">Importar NF-e de compra (XML)</h5>
                        <input type="file" name="xml[]" accept=".xml" multiple required />
                        <span class="hint">Selecione um ou vários XMLs de NF-e recebidas. A chave duplicada é ignorada automaticamente.</span>
                        <div style="margin-top:10px">
                            <button type="submit" class="button btn btn-success">
                                <span class="button__icon"><i class="bx bx-save"></i></span><span class="button__text2">Importar</span>
                            </button>
                        </div>
                    <?= form_close() ?>
                </div>

                <!-- Lançamento manual -->
                <div id="boxManual" style="display:none;border:1px solid #eee;padding:12px;border-radius:6px;margin-bottom:14px">
                    <?= form_open('entradas/salvarManual') ?>
                        <h5 style="margin-top:0">Lançar entrada manual (ex.: serviço sem XML)</h5>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px">
                            <label>Fornecedor*<input type="text" name="emitente_nome" required /></label>
                            <label>CNPJ/CPF<input type="text" name="emitente_cnpj" /></label>
                            <label>Nº<input type="text" name="numero" /></label>
                            <label>Série<input type="text" name="serie" /></label>
                            <label>Data emissão*<input type="date" name="data_emissao" value="<?= date('Y-m-d') ?>" required /></label>
                            <label>Valor total<input type="text" name="valor_total" placeholder="0,00" /></label>
                            <label>Base IBS/CBS<input type="text" name="v_bc_ibscbs" placeholder="0,00" /></label>
                            <label>Crédito IBS<input type="text" name="v_ibs" placeholder="0,00" /></label>
                            <label>Crédito CBS<input type="text" name="v_cbs" placeholder="0,00" /></label>
                            <label>Créd. presumido ZFM<input type="text" name="v_cred_pres_zfm" placeholder="0,00" /></label>
                        </div>
                        <label style="display:inline-flex;align-items:center;gap:6px;margin-top:8px">
                            <input type="checkbox" name="credita" value="1" checked /> Aproveitar este crédito
                        </label>
                        <div style="margin-top:6px"><label>Observação<input type="text" name="observacao" style="width:100%" /></label></div>
                        <div style="margin-top:10px">
                            <button type="submit" class="button btn btn-primary">
                                <span class="button__icon"><i class="bx bx-save"></i></span><span class="button__text2">Salvar entrada</span>
                            </button>
                        </div>
                    <?= form_close() ?>
                </div>

                <!-- Resumo de crédito do período -->
                <?php if ($competencia && $credito) { ?>
                    <div class="alert" style="background:#f2f8f2;border:1px solid #cde6cd">
                        Crédito de <strong><?= html_escape($competencia) ?></strong>:
                        IBS R$ <?= $fmt($credito['v_ibs']) ?> + CBS R$ <?= $fmt($credito['v_cbs']) ?>
                        <?php if ($credito['v_cred_pres_zfm'] > 0) { ?> + ZFM R$ <?= $fmt($credito['v_cred_pres_zfm']) ?><?php } ?>
                        = <strong>R$ <?= $fmt($credito['total']) ?></strong>
                    </div>
                <?php } ?>

                <!-- Tabela -->
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Emissão</th>
                            <th>Fornecedor</th>
                            <th>Nº / Modelo</th>
                            <th style="text-align:right">Valor</th>
                            <th style="text-align:right">IBS</th>
                            <th style="text-align:right">CBS</th>
                            <th style="text-align:right">ZFM</th>
                            <th style="text-align:center">Credita?</th>
                            <th style="text-align:center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($results)) { ?>
                            <tr><td colspan="9" style="text-align:center;color:#999">Nenhuma entrada registrada<?= $competencia ? ' nesta competência' : '' ?>.</td></tr>
                        <?php } else { ?>
                            <?php foreach ($results as $e) { ?>
                                <tr<?= $e->credita ? '' : ' style="opacity:.5"' ?>>
                                    <td><?= $e->data_emissao ? date('d/m/Y', strtotime($e->data_emissao)) : '—' ?></td>
                                    <td><?= html_escape($e->emitente_nome ?: '—') ?><br><small style="color:#999"><?= html_escape($e->emitente_cnpj) ?></small></td>
                                    <td><?= html_escape($e->numero) ?> <small style="color:#999">/ <?= html_escape($e->modelo) ?></small></td>
                                    <td style="text-align:right"><?= $fmt($e->valor_total) ?></td>
                                    <td style="text-align:right"><?= $fmt($e->v_ibs) ?></td>
                                    <td style="text-align:right"><?= $fmt($e->v_cbs) ?></td>
                                    <td style="text-align:right"><?= $fmt($e->v_cred_pres_zfm) ?></td>
                                    <td style="text-align:center">
                                        <a href="<?= site_url('entradas/toggleCredita/' . $e->idEntrada) ?>" title="Alternar aproveitamento do crédito">
                                            <?= $e->credita ? '<span style="color:#3a3">Sim</span>' : '<span style="color:#999">Não</span>' ?>
                                        </a>
                                    </td>
                                    <td style="text-align:center">
                                        <a href="<?= site_url('entradas/excluir/' . $e->idEntrada) ?>" onclick="return confirm('Excluir esta entrada?')" title="Excluir"><i class="bx bx-trash" style="color:#c33"></i></a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function toggleBox(id) {
        var el = document.getElementById(id);
        el.style.display = (el.style.display === 'none' || !el.style.display) ? 'block' : 'none';
    }
</script>
