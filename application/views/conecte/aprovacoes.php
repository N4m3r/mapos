<div class="span12" style="margin-left: 0">
    <div class="widget-box">
        <div class="widget-title">
            <span class="icon">
                <i class="fas fa-check-double"></i>
            </span>
            <h5>Ordens de Serviço pendentes de aprovação</h5>
        </div>

        <div class="widget-content nopadding tab-content">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nº</th>
                        <th>Cliente / CNPJ</th>
                        <th>Descrição</th>
                        <th>Data</th>
                        <th style="width: 320px">Decisão</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)) { ?>
                        <tr>
                            <td colspan="5">Nenhuma OS pendente de aprovação.</td>
                        </tr>
                    <?php } else {
                        foreach ($results as $r) {
                            $doc = trim((string) $r->documento);
                            $data = $r->dataInicial ? date('d/m/Y', strtotime($r->dataInicial)) : '-';
                            $desc = $r->descricaoProduto ? mb_substr(strip_tags($r->descricaoProduto), 0, 80) : '';
                            ?>
                            <tr>
                                <td><?= $r->idOs ?></td>
                                <td><?= htmlspecialchars($r->nomeCliente) ?><?= $doc !== '' ? '<br><small>' . htmlspecialchars($doc) . '</small>' : '' ?></td>
                                <td>
                                    <?= htmlspecialchars($desc) ?>
                                    <a href="<?= base_url() ?>index.php/mine/visualizarOs/<?= $r->idOs ?>" title="Ver OS"><i class="bx bx-show-alt"></i></a>
                                </td>
                                <td><?= $data ?></td>
                                <td>
                                    <form method="post" action="<?= site_url('mine/aprovarOs') ?>" class="form-aprovacao" style="margin:0">
                                        <input type="hidden" name="idOs" value="<?= $r->idOs ?>">
                                        <input type="text" name="obs" placeholder="Observação (opcional)" style="width:100%;margin-bottom:6px">
                                        <button type="submit" name="decisao" value="aprovado" class="button btn btn-success btn-mini">
                                            <span class="button__icon"><i class="bx bx-check"></i></span><span class="button__text2">Aprovar</span>
                                        </button>
                                        <button type="submit" name="decisao" value="reprovado" class="button btn btn-danger btn-mini btn-reprovar">
                                            <span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Reprovar</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php }
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(function() {
        $('.btn-reprovar').on('click', function(e) {
            if (!confirm('Confirma a REPROVAÇÃO desta OS?')) {
                e.preventDefault();
            }
        });
    });
</script>
