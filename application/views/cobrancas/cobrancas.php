<?php
$perm = function ($p) {
    return $this->permission->checkPermission($this->session->userdata('permissao'), $p);
};
$brl = function ($v) {
    return 'R$ ' . number_format((float) $v, 2, ',', '.');
};
$kpis = $kpis ?? [];
$formaNome = function ($r) {
    $m = strtolower((string) $r->payment_method);
    $mapa = ['boleto' => 'Boleto', 'pix' => 'PIX', 'link' => 'Link de pagamento', 'bolix' => 'Boleto + PIX'];

    return $mapa[$m] ?? ($r->payment_method !== '' ? ucfirst($r->payment_method) : '-');
};
$badge = ['pendente' => ['label-warning', 'A receber'], 'vencida' => ['label-important label-danger', 'Vencida'], 'paga' => ['label-success', 'Paga'], 'cancelada' => ['', 'Cancelada']];
$qs = function ($extra) use ($busca, $filtroStatus, $aba) {
    return site_url('cobrancas/cobrancas') . '?' . http_build_query(array_filter(array_merge(['q' => $busca], $extra)));
};
?>
<style>
    .cb-abas { display: flex; gap: 6px; margin: 0 0 12px; flex-wrap: wrap; }
    .cb-abas a { padding: 8px 14px; border: 1px solid rgba(128,128,128,.4); border-radius: 6px; text-decoration: none; font-weight: 600; }
    .cb-abas a.ativa { background: #2d8cf0; color: #fff; border-color: #2d8cf0; }
    .cb-filtros { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin: 0 0 12px; }
    .cb-filtros input[type=text] { margin: 0; }
    .cb-filtros select { width: auto; margin: 0; }
    .cb-venc small { display: block; opacity: .75; }
</style>
<div class="new122">
    <div class="ui-cabecalho">
        <div>
            <h1><i class="fas fa-cash-register"></i> Cobranças</h1>
            <p>Veja o que falta cobrar, o que está a receber e o que já foi pago.</p>
        </div>
        <?php if ($perm('cNfe')) { ?>
            <div class="ui-acoes">
                <a href="<?= site_url('cobrancas/configCora') ?>" class="button btn btn-success" title="Configurar Cobrança Cora" aria-label="Configurar Cobrança Cora">
                    <span class="button__icon"><i class='bx bx-dollar-circle'></i></span>
                    <span class="button__text2">Configurar Cobrança Cora</span>
                </a>
            </div>
        <?php } ?>
    </div>

    <div class="ui-kpis">
        <div class="ui-kpi"><div class="ui-kpi-rotulo">A receber</div><div class="ui-kpi-valor"><?= $brl($kpis['a_receber']['total'] ?? 0) ?></div><div class="ui-kpi-extra"><?= (int) ($kpis['a_receber']['qtd'] ?? 0) ?> cobrança(s) no prazo</div></div>
        <div class="ui-kpi"><div class="ui-kpi-rotulo">Vencidas</div><div class="ui-kpi-valor"><?= $brl($kpis['vencidas']['total'] ?? 0) ?></div><div class="ui-kpi-extra"><?= (int) ($kpis['vencidas']['qtd'] ?? 0) ?> cobrança(s) em atraso</div></div>
        <div class="ui-kpi"><div class="ui-kpi-rotulo">Pagas no mês</div><div class="ui-kpi-valor"><?= $brl($kpis['pagas_mes']['total'] ?? 0) ?></div><div class="ui-kpi-extra"><?= (int) ($kpis['pagas_mes']['qtd'] ?? 0) ?> cobrança(s) recebida(s)</div></div>
    </div>

    <div class="cb-abas">
        <a href="<?= $qs([]) ?>" class="<?= $aba === 'cobrancas' ? 'ativa' : '' ?>">Cobranças</a>
        <a href="<?= $qs(['aba' => 'acobrar']) ?>" class="<?= $aba === 'acobrar' ? 'ativa' : '' ?>">A cobrar (<?= (int) $totalACobrar ?>)</a>
    </div>

    <form method="get" action="<?= site_url('cobrancas/cobrancas') ?>" class="cb-filtros">
        <?php if ($aba === 'acobrar') { ?><input type="hidden" name="aba" value="acobrar"><?php } ?>
        <input type="text" name="q" value="<?= html_escape($busca) ?>" placeholder="Buscar por cliente" aria-label="Buscar por cliente">
        <?php if ($aba === 'cobrancas') { ?>
            <select name="status" aria-label="Filtrar por status">
                <option value="">Todos os status</option>
                <?php foreach (['pendente' => 'A receber', 'vencida' => 'Vencidas', 'paga' => 'Pagas', 'cancelada' => 'Canceladas'] as $k => $n) { ?>
                    <option value="<?= $k ?>" <?= $filtroStatus === $k ? 'selected' : '' ?>><?= $n ?></option>
                <?php } ?>
            </select>
        <?php } ?>
        <button class="button btn btn-mini btn-info" type="submit"><span class="button__icon"><i class="bx bx-search"></i></span><span class="button__text2">Filtrar</span></button>
    </form>

    <div class="widget-box">
        <div class="widget-content nopadding tab-content ui-tabela-rolagem">
            <?php if ($aba === 'acobrar') { ?>
            <table id="tabela" class="table table-bordered ">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Referência</th>
                        <th>Data</th>
                        <th>Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! $results) { ?>
                        <tr><td colspan="5"><div class="ui-vazio"><i class="bx bx-check-circle"></i><strong>Nada a cobrar</strong><br>Todas as OS finalizadas e vendas faturadas já têm cobrança.</div></td></tr>
                    <?php } ?>
                    <?php foreach ($results as $r) {
                        $rota = $r->tipo === 'os' ? 'os/visualizar/' : 'vendas/visualizar/';
                        $nome = ($r->tipo === 'os' ? 'Ordem de Serviço #' : 'Venda #') . $r->ref_id; ?>
                        <tr>
                            <td><?= html_escape($r->nomeCliente) ?></td>
                            <td><a href="<?= site_url($rota . $r->ref_id) ?>"><?= $nome ?></a></td>
                            <td><?= $r->data_ref ? date('d/m/Y', strtotime($r->data_ref)) : '-' ?></td>
                            <td><strong><?= $brl($r->valor) ?></strong></td>
                            <td>
                                <?php if ($perm('aCobranca')) { ?>
                                    <a href="<?= site_url($rota . $r->ref_id . '?cobrar=1') ?>" class="button btn btn-mini btn-primary" title="Cobrar">
                                        <span class="button__icon"><i class="bx bx-dollar"></i></span><span class="button__text2">Cobrar</span>
                                    </a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <?php } else { ?>
            <table id="tabela" class="table table-bordered ">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Referência</th>
                        <th>Forma</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!$results) {
                        echo '<tr>
                                <td colspan="7"><div class="ui-vazio"><i class="bx bx-receipt"></i><strong>Nenhuma cobrança encontrada</strong><br>Veja a aba "A cobrar" para gerar novas cobranças.</div></td>
                            </tr>';
                    }
                    foreach ($results as $r) {
                        try {
                            $descStatus = getCobrancaTransactionStatus($this->config->item('payment_gateways'), $r->payment_gateway, $r->status);
                        } catch (\Throwable $e) {
                            $descStatus = $r->status;
                        }
                        [$bcls, $btxt] = $badge[$r->classe];
                        $prazo = in_array($r->classe, ['pendente', 'vencida'], true) ? cobranca_prazo_texto($r->expire_at, $hoje) : '';

                        echo '<tr>';
                        echo '<td>' . html_escape($r->nomeCliente) . '</td>';
                        if ($r->os_id != '') {
                            echo '<td><a href="' . base_url() . 'index.php/os/visualizar/' . (int) $r->os_id . '">OS #' . (int) $r->os_id . '</a></td>';
                        } elseif ($r->vendas_id != '') {
                            echo '<td><a href="' . base_url() . 'index.php/vendas/visualizar/' . (int) $r->vendas_id . '">Venda #' . (int) $r->vendas_id . '</a></td>';
                        } else {
                            echo '<td>-</td>';
                        }
                        echo '<td>' . html_escape($formaNome($r)) . '</td>';
                        echo '<td class="cb-venc">' . ($r->expire_at ? date('d/m/Y', strtotime($r->expire_at)) : '-') . ($prazo ? '<small>' . $prazo . '</small>' : '') . '</td>';
                        echo '<td><strong>' . $brl($r->total / 100) . '</strong></td>';
                        echo '<td><span class="label ' . $bcls . '" title="' . html_escape($descStatus) . '">' . $btxt . '</span></td>';
                        echo '<td><span class="ui-acoes-linha">';
                        if ($perm('vCobranca')) {
                            echo '<a href="#modal-cancelar" role="button" data-toggle="modal" cancela_id="' . $r->idCobranca . '" class="btn-nwe4" title="Cancelar Cobrança" aria-label="Cancelar Cobrança"><i class="bx bx-x" ></i></a>';
                            echo '<a href="' . base_url() . 'index.php/cobrancas/atualizar/' . $r->idCobranca . '" class="btn-nwe" title="Atualizar Cobrança" aria-label="Atualizar Cobrança"><i class="bx bx-refresh"></i></a>';
                            echo '<a href="#modal-confirmar" role="button" data-toggle="modal" confirma_id="' . $r->idCobranca . '" class="btn-nwe3" title="Confirmar pagamento" aria-label="Confirmar pagamento"><i class="bx bx-check"></i></a>';
                            echo '<a href="' . base_url() . 'index.php/cobrancas/visualizar/' . $r->idCobranca . '" class="btn-nwe2" title="Ver mais detalhes" aria-label="Ver mais detalhes"><i class="bx bx-show" ></i></a>';
                            echo '<a href="' . base_url() . 'index.php/cobrancas/enviarEmail/' . $r->idCobranca . '" class="btn-nwe5" title="Enviar por E-mail" aria-label="Enviar por E-mail"><i class="bx bx-envelope" ></i></a>';
                        }
                        if ($perm('eCobranca') && $r->barcode != '') {
                            echo '<a href="' . html_escape($r->link) . '" target="_blank" class="btn-nwe" title="Visualizar boleto" aria-label="Visualizar boleto"><i class="bx bx-barcode" ></i></a>';
                        }
                        if ($perm('dCobranca')) {
                            echo '<a href="#modal-excluir" role="button" data-toggle="modal" excluir_id="' . $r->idCobranca . '" class="btn-nwe4" title="Excluir Cobrança" aria-label="Excluir Cobrança"><i class="bx bx-trash-alt"></i></a>';
                        }
                        echo '</span></td>';
                        echo '</tr>';
                    } ?>
                </tbody>
            </table>
            <?php } ?>
        </div>
    </div>
    <?php echo $this->pagination->create_links(); ?>


    <!-- Modal -->
    <div id="modal-excluir" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <form action="<?php echo base_url() ?>index.php/cobrancas/excluir" method="post">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h5 id="myModalLabel">Excluir cobrança</h5>
            </div>
            <div class="modal-body">
                <input type="hidden" id="excluir_id" name="excluir_id" value="" />
                <h5 style="text-align: center">Deseja realmente excluir esta cobrança? A cobrança será cancelada.</h5>
            </div>
            <div class="modal-footer" style="display:flex;justify-content: center">
                <button class="button btn btn-warning" data-dismiss="modal" aria-hidden="true"><span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span></button>
                <button class="button btn btn-danger"><span class="button__icon"><i class='bx bx-trash'></i></span> <span class="button__text2">Excluir</span></button>
            </div>
        </form>
    </div>

    <div id="modal-confirmar" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <form action="<?php echo base_url() ?>index.php/cobrancas/confirmarpagamento" method="post">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h5 id="myModalLabel">Confirmar pagamento</h5>
            </div>
            <div class="modal-body">
                <input type="hidden" id="confirma_id" name="confirma_id" value="" />
                <h5 style="text-align: center">Deseja realmente confirmar pagamento desta cobrança?</h5>
            </div>
            <div class="modal-footer">
                <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
                <button class="btn btn-success">Confirmar</button>
            </div>
        </form>
    </div>

    <div id="modal-cancelar" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <form action="<?php echo base_url() ?>index.php/cobrancas/cancelar" method="post">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h5 id="myModalLabel">Cancelar cobrança</h5>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cancela_id" name="cancela_id" value="" />
                <h5 style="text-align: center">Deseja realmente Cancelar esta cobrança?</h5>
            </div>
            <div class="modal-footer">
                <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
                <button class="btn btn-danger">Confirmar</button>
            </div>
        </form>
    </div>
</div>
    <script nonce="<?= csp_nonce() ?>" type="text/javascript">
        $(document).ready(function() {
            $(document).on('click', 'a', function(event) {
                var cobranca = $(this).attr('excluir_id');
                $('#excluir_id').val(cobranca);
            });

            $(document).on('click', 'a', function(event) {
                var cobranca = $(this).attr('confirma_id');
                $('#confirma_id').val(cobranca);
            });

            $(document).on('click', 'a', function(event) {
                var cobranca = $(this).attr('cancela_id');
                $('#cancela_id').val(cobranca);
            });
        });
    </script>