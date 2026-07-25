<?php
$perm = function ($p) {
    return $this->permission->checkPermission($this->session->userdata('permissao'), $p);
};
$fmt = function ($v) {
    return number_format((float) $v, 2, ',', '.');
};
$data_br = function ($d) {
    return (! empty($d) && $d !== '0000-00-00') ? date('d/m/Y', strtotime($d)) : '—';
};
$statusLabels = ['planejamento' => 'Planejamento', 'em_execucao' => 'Em execução', 'paralisada' => 'Paralisada', 'concluida' => 'Concluída', 'entregue' => 'Entregue', 'cancelada' => 'Cancelada'];
?>
<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css">

<div class="obra-header widget-box">
    <div class="widget-content">
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;align-items:center">
            <div>
                <h4 style="margin:0"><?= html_escape($obra->nome) ?> <?= $obra->codigo ? '<small>#' . html_escape($obra->codigo) . '</small>' : '' ?></h4>
                <div><i class='bx bx-user'></i> <?= html_escape($obra->nomeCliente ?: '—') ?> &nbsp; <i class='bx bx-wrench'></i> <?= html_escape($obra->responsavel ?: '—') ?></div>
                <div><i class='bx bx-map'></i> <?= html_escape(trim($obra->logradouro . ', ' . $obra->numero . ' - ' . $obra->cidade . '/' . $obra->uf, ' ,-/')) ?: '—' ?></div>
                <span class="label"><?= $statusLabels[$obra->status] ?? $obra->status ?></span>
            </div>
            <div style="text-align:center;min-width:180px">
                <div class="obra-progress"><div class="obra-progress-bar" style="width:<?= (float) $obra->percentual_concluido ?>%"></div></div>
                <strong><?= $fmt($obra->percentual_concluido) ?>% concluído</strong>
            </div>
        </div>
        <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap">
            <?php if ($perm('eObras')): ?><a href="<?= site_url('obras/editar/' . $obra->idObra) ?>" class="button btn btn-mini btn-inverse"><span class="button__icon"><i class='bx bx-edit'></i></span><span class="button__text2">Editar</span></a><?php endif; ?>
            <?php if ($perm('rObraMaterial')): ?><a href="<?= site_url('obramaterial/receber/' . $obra->idObra) ?>" class="button btn btn-mini btn-success"><span class="button__icon"><i class='bx bx-down-arrow-circle'></i></span><span class="button__text2">Receber material</span></a><?php endif; ?>
            <?php if ($perm('sObraMaterial')): ?><a href="<?= site_url('obramaterial/entregar/' . $obra->idObra) ?>" class="button btn btn-mini btn-primary"><span class="button__icon"><i class='bx bx-up-arrow-circle'></i></span><span class="button__text2">Entregar material</span></a><?php endif; ?>
            <a href="<?= site_url('obras') ?>" class="button btn btn-mini btn-warning"><span class="button__icon"><i class='bx bx-undo'></i></span><span class="button__text2">Voltar</span></a>
        </div>
    </div>
</div>

<ul class="nav nav-tabs obra-tabs" id="obraTabs">
    <li class="active"><a href="#tab-geral" data-tab="geral">Visão geral</a></li>
    <li><a href="#tab-cronograma" data-tab="cronograma">Cronograma</a></li>
    <li><a href="#tab-material" data-tab="material">Material</a></li>
    <li><a href="#tab-rdo" data-tab="rdo">Diário de Obra</a></li>
    <li><a href="#tab-medicao" data-tab="medicao">Medição</a></li>
    <li><a href="#tab-equipe" data-tab="equipe">Equipe</a></li>
    <li><a href="#tab-maodeobra" data-tab="maodeobra">Mão de obra</a></li>
</ul>

<div class="obra-tab-content">

    <!-- VISÃO GERAL -->
    <div class="obra-pane" id="tab-geral">
        <div class="widget-box"><div class="widget-content">
            <div class="span4"><strong>Valor do contrato:</strong> R$ <?= $fmt($obra->valor_contrato) ?></div>
            <div class="span4"><strong>BDI:</strong> <?= $fmt($obra->bdi_percentual) ?>%</div>
            <div class="span4"><strong>Contrato nº:</strong> <?= html_escape($obra->contrato_numero ?: '—') ?></div>
            <div class="span3"><strong>Início previsto:</strong> <?= $data_br($obra->data_inicio_prevista) ?></div>
            <div class="span3"><strong>Fim previsto:</strong> <?= $data_br($obra->data_fim_prevista) ?></div>
            <div class="span3"><strong>Início real:</strong> <?= $data_br($obra->data_inicio_real) ?></div>
            <div class="span3"><strong>Fim real:</strong> <?= $data_br($obra->data_fim_real) ?></div>
            <div class="span12" style="margin-top:10px">
                <strong>Custo:</strong> Previsto R$ <?= $fmt($custo['previsto']) ?> &nbsp;|&nbsp; Realizado R$ <?= $fmt($custo['realizado']) ?>
            </div>
            <?php if ($obra->observacoes): ?><div class="span12" style="margin-top:10px"><strong>Observações:</strong><br><?= nl2br(html_escape($obra->observacoes)) ?></div><?php endif; ?>
        </div></div>
    </div>

    <!-- CRONOGRAMA -->
    <div class="obra-pane" id="tab-cronograma" style="display:none">
        <div class="widget-box"><div class="widget-content nopadding">
            <?php if ($perm('eObraCronograma')): ?>
                <div style="padding:8px"><a href="#modal-etapa" data-toggle="modal" class="button btn btn-mini btn-success" onclick="novaEtapa()"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Nova etapa</span></a></div>
            <?php endif; ?>
            <table class="table table-bordered">
                <thead><tr><th>EAP</th><th>Etapa</th><th>Peso %</th><th>Valor prev.</th><th>Início</th><th>Fim</th><th>%</th><?php if ($perm('eObraCronograma')): ?><th>Ações</th><?php endif; ?></tr></thead>
                <tbody>
                    <?php if (! $etapas) echo '<tr><td colspan="8">Nenhuma etapa cadastrada.</td></tr>'; ?>
                    <?php foreach ($etapas as $e): ?>
                        <tr>
                            <td><?= html_escape($e->codigo_eap) ?></td>
                            <td><?= html_escape($e->nome) ?></td>
                            <td><?= $fmt($e->peso_percentual) ?></td>
                            <td>R$ <?= $fmt($e->valor_previsto) ?></td>
                            <td><?= $data_br($e->data_inicio_prevista) ?></td>
                            <td><?= $data_br($e->data_fim_prevista) ?></td>
                            <td>
                                <div class="obra-progress mini"><div class="obra-progress-bar" style="width:<?= (float) $e->percentual_concluido ?>%"></div></div>
                                <small><?= $fmt($e->percentual_concluido) ?>%</small>
                            </td>
                            <?php if ($perm('eObraCronograma')): ?>
                                <td>
                                    <a href="#" class="btn-nwe3" title="Editar" onclick='editarEtapa(<?= json_encode($e, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>);return false;'><i class="bx bx-edit bx-xs"></i></a>
                                    <a href="#" class="btn-nwe4" title="Excluir" onclick="excluirEtapa(<?= $e->idEtapa ?>);return false;"><i class="bx bx-trash-alt bx-xs"></i></a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
    </div>

    <!-- MATERIAL -->
    <div class="obra-pane" id="tab-material" style="display:none">
        <div style="display:flex;gap:6px;margin-bottom:8px;flex-wrap:wrap">
            <?php if ($perm('rObraMaterial')): ?><a href="<?= site_url('obramaterial/receber/' . $obra->idObra) ?>" class="button btn btn-mini btn-success"><span class="button__icon"><i class='bx bx-down-arrow-circle'></i></span><span class="button__text2">Receber (do cliente)</span></a><?php endif; ?>
            <?php if ($perm('sObraMaterial')): ?><a href="<?= site_url('obramaterial/entregar/' . $obra->idObra) ?>" class="button btn btn-mini btn-primary"><span class="button__icon"><i class='bx bx-up-arrow-circle'></i></span><span class="button__text2">Entregar (à equipe)</span></a><?php endif; ?>
        </div>
        <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-cart-alt'></i> Compras da empresa</h5></div><div class="widget-content">
            <div class="obra-compras">
                <div class="k-icon" style="background:#e7f5ec;color:#2e7d32;width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px"><i class='bx bx-cart-alt'></i></div>
                <div>
                    <div class="c-total">R$ <?= $fmt($compras['total']) ?></div>
                    <div class="c-lbl">Material comprado pela empresa — <?= (int) $compras['recebimentos'] ?> recebimento(s) por compra própria. Entra no custo realizado do projeto.</div>
                </div>
            </div>
        </div></div>
        <div class="widget-box"><div class="widget-title"><h5>Almoxarifado — saldo atual</h5></div><div class="widget-content nopadding">
            <table class="table table-bordered">
                <thead><tr><th>Item</th><th>Cód. barras</th><th>Un.</th><th>Origem</th><th>Saldo</th></tr></thead>
                <tbody>
                    <?php if (! $saldos) echo '<tr><td colspan="5">Sem material em estoque.</td></tr>'; ?>
                    <?php foreach ($saldos as $s): ?>
                        <tr>
                            <td><?= html_escape($s->descricao) ?></td>
                            <td><?= html_escape($s->cod_barras) ?></td>
                            <td><?= html_escape($s->unidade) ?></td>
                            <td><?= $s->do_cliente ? '<span class="label label-info">Cliente</span>' : 'Próprio' ?></td>
                            <td><strong><?= rtrim(rtrim(number_format((float) $s->saldo, 3, ',', '.'), '0'), ',') ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
        <div class="span6" style="margin-left:0"><div class="widget-box"><div class="widget-title"><h5>Recebimentos</h5></div><div class="widget-content nopadding">
            <table class="table table-bordered"><thead><tr><th>#</th><th>Data</th><th>Origem</th><th>Doc.</th></tr></thead><tbody>
                <?php if (! $recebimentos) echo '<tr><td colspan="4">—</td></tr>'; ?>
                <?php foreach ($recebimentos as $r): ?><tr><td><?= $r->numero ?></td><td><?= $data_br($r->data) ?></td><td><?= html_escape($r->origem) ?></td><td><?= html_escape($r->documento) ?></td></tr><?php endforeach; ?>
            </tbody></table>
        </div></div></div>
        <div class="span6"><div class="widget-box"><div class="widget-title"><h5>Entregas à equipe</h5></div><div class="widget-content nopadding">
            <table class="table table-bordered"><thead><tr><th>#</th><th>Data</th><th>Equipe</th><th>Recebedor</th></tr></thead><tbody>
                <?php if (! $entregas) echo '<tr><td colspan="4">—</td></tr>'; ?>
                <?php foreach ($entregas as $e): ?><tr><td><?= $e->numero ?></td><td><?= $data_br($e->data) ?></td><td><?= html_escape($e->equipe ?: '—') ?></td><td><?= html_escape($e->recebedor_nome) ?></td></tr><?php endforeach; ?>
            </tbody></table>
        </div></div></div>
    </div>

    <!-- RDO -->
    <div class="obra-pane" id="tab-rdo" style="display:none">
        <div class="widget-box"><div class="widget-content nopadding">
            <?php if ($perm('cObraRdo')): ?>
                <div style="padding:8px"><a href="#modal-rdo" data-toggle="modal" class="button btn btn-mini btn-success"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Novo RDO</span></a></div>
            <?php endif; ?>
            <table class="table table-bordered">
                <thead><tr><th>#</th><th>Data</th><th>Clima (M/T/N)</th><th>Condição</th><th>Efetivo</th><th>Responsável</th></tr></thead>
                <tbody>
                    <?php if (! $rdos) echo '<tr><td colspan="6">Nenhum RDO registrado.</td></tr>'; ?>
                    <?php foreach ($rdos as $r): ?>
                        <tr>
                            <td><?= $r->numero ?></td>
                            <td><?= $data_br($r->data) ?></td>
                            <td><?= html_escape(($r->clima_manha ?: '-') . '/' . ($r->clima_tarde ?: '-') . '/' . ($r->clima_noite ?: '-')) ?></td>
                            <td><?= html_escape($r->condicao) ?></td>
                            <td><?= (int) $r->efetivo_total ?></td>
                            <td><?= html_escape($r->responsavel) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
    </div>

    <!-- MEDIÇÃO -->
    <div class="obra-pane" id="tab-medicao" style="display:none">
        <div class="widget-box"><div class="widget-content nopadding">
            <?php if ($perm('cObraMedicao') && $etapas): ?>
                <div style="padding:8px"><a href="#modal-medicao" data-toggle="modal" class="button btn btn-mini btn-success"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Nova medição</span></a></div>
            <?php elseif (! $etapas): ?>
                <div style="padding:8px" class="text-warning">Cadastre etapas no cronograma para poder medir.</div>
            <?php endif; ?>
            <table class="table table-bordered">
                <thead><tr><th>#</th><th>Período</th><th>% acum.</th><th>Valor</th><th>Status</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php if (! $medicoes) echo '<tr><td colspan="6">Nenhuma medição.</td></tr>'; ?>
                    <?php foreach ($medicoes as $m): ?>
                        <tr>
                            <td><?= $m->numero ?></td>
                            <td><?= $data_br($m->periodo_inicio) ?> a <?= $data_br($m->periodo_fim) ?></td>
                            <td><?= $fmt($m->percentual_acumulado) ?>%</td>
                            <td>R$ <?= $fmt($m->valor_medido) ?></td>
                            <td><span class="label"><?= html_escape($m->status) ?></span></td>
                            <td>
                                <?php if ($m->status === 'aberta' && $perm('aObraMedicao')): ?>
                                    <form action="<?= site_url('obras/aprovarMedicao') ?>" method="post" style="display:inline">
                                        <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
                                        <input type="hidden" name="idMedicao" value="<?= $m->idMedicao ?>">
                                        <button class="button btn btn-mini btn-success" onclick="return confirm('Aprovar medição?')"><span class="button__text2">Aprovar</span></button>
                                    </form>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
    </div>

    <!-- EQUIPE -->
    <div class="obra-pane" id="tab-equipe" style="display:none">
        <?php if ($perm('cObraEquipe')): ?>
            <div style="margin-bottom:8px"><a href="<?= site_url('obraequipe') ?>" class="button btn btn-mini btn-inverse"><span class="button__icon"><i class='bx bx-group'></i></span><span class="button__text2">Gerenciar equipes</span></a></div>
        <?php endif; ?>
        <div class="widget-box"><div class="widget-content nopadding">
            <table class="table table-bordered">
                <thead><tr><th>Equipe</th><th>Encarregado</th><th>Início</th><th>Fim</th><th>Ativa</th></tr></thead>
                <tbody>
                    <?php if (! $equipes) echo '<tr><td colspan="5">Nenhuma equipe alocada.</td></tr>'; ?>
                    <?php foreach ($equipes as $q): ?>
                        <tr><td><?= html_escape($q->nome) ?></td><td><?= html_escape($q->encarregado) ?></td><td><?= $data_br($q->data_inicio) ?></td><td><?= $data_br($q->data_fim) ?></td><td><?= $q->ativo ? 'Sim' : 'Não' ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
    </div>

    <!-- MÃO DE OBRA / EFETIVO -->
    <div class="obra-pane" id="tab-maodeobra" style="display:none">
        <?php $verValor = $perm('vObraCusto'); ?>
        <?php if ($perm('cObraRdo')): ?>
            <div class="widget-box"><div class="widget-content" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end">
                <form action="<?= site_url('obras/consolidarPonto') ?>" method="post" style="display:flex;gap:6px;align-items:flex-end;margin:0">
                    <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
                    <div><label>Gerar efetivo do dia (ponto facial/GPS)</label><input type="text" class="datepicker" name="data" value="<?= date('d/m/Y') ?>" style="width:130px"></div>
                    <button class="button btn btn-mini btn-primary" onclick="return confirm('Consolidar as batidas de ponto vinculadas a esta obra nesse dia?')"><span class="button__icon"><i class='bx bx-sync'></i></span><span class="button__text2">Consolidar ponto</span></button>
                </form>
                <span class="scanner-status">As batidas de ponto que o colaborador vincular a esta obra viram efetivo aqui (horas/diária e custo de mão de obra).</span>
            </div></div>
        <?php endif; ?>

        <div class="widget-box"><div class="widget-title"><h5>Efetivo apontado<?php if ($verValor): ?> — total R$ <?= $fmt($apontamento_total) ?><?php endif; ?></h5></div>
            <div class="widget-content nopadding">
                <table class="table table-bordered">
                    <thead><tr><th>Data</th><th>Colaborador</th><th>Função</th><th>Horas</th><th>Diária</th><th>Origem</th><?php if ($verValor): ?><th>Valor</th><?php endif; ?><?php if ($perm('cObraRdo')): ?><th></th><?php endif; ?></tr></thead>
                    <tbody>
                        <?php if (! $apontamentos) echo '<tr><td colspan="9">Nenhum apontamento. Consolide o ponto do dia ou lance manualmente.</td></tr>'; ?>
                        <?php foreach ($apontamentos as $ap): ?>
                            <tr>
                                <td><?= $data_br($ap->data) ?></td>
                                <td><?= html_escape($ap->nome ?: ('#' . $ap->colaborador_id)) ?></td>
                                <td><?= html_escape($ap->funcao) ?></td>
                                <td><?= $fmt($ap->horas, 2) ?></td>
                                <td><?= $ap->diaria ? '1' : '—' ?></td>
                                <td><span class="label"><?= $ap->origem === 'ponto_facial' ? 'Ponto' : 'Manual' ?></span></td>
                                <?php if ($verValor): ?><td>R$ <?= $fmt($ap->valor) ?></td><?php endif; ?>
                                <?php if ($perm('cObraRdo')): ?>
                                    <td><a href="#" class="btn-nwe4" title="Remover" onclick="excluirApont(<?= $ap->idApontamento ?>);return false;"><i class="bx bx-trash-alt bx-xs"></i></a></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($perm('cObraRdo')): ?>
                    <form action="<?= site_url('obras/salvarApontamento') ?>" method="post" style="padding:8px">
                        <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
                        <div class="span3" style="margin-left:0"><label>Data</label><input type="text" class="span12 datepicker" name="data" value="<?= date('d/m/Y') ?>"></div>
                        <div class="span3"><label>Colaborador</label><input type="text" class="span12" name="nome" placeholder="Nome"></div>
                        <div class="span2"><label>Função</label><input type="text" class="span12" name="funcao"></div>
                        <div class="span1"><label>Horas</label><input type="number" step="0.5" class="span12" name="horas" value="0"></div>
                        <div class="span1"><label>Diária</label><br><input type="checkbox" name="diaria" value="1"></div>
                        <div class="span2"><label>Valor (R$)</label><input type="number" step="0.01" class="span12" name="valor" value="0"></div>
                        <div class="span12" style="margin-left:0;margin-top:6px"><button class="button btn btn-mini btn-success"><span class="button__icon"><i class='bx bx-plus'></i></span><span class="button__text2">Lançar manual</span></button></div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($perm('cObraRdo')): ?>
<form id="formExcluirApont" action="<?= site_url('obras/excluirApontamento') ?>" method="post" style="display:none">
    <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>"><input type="hidden" name="idApontamento" id="del_apont">
</form>
<script>
    function excluirApont(id){ if(confirm('Remover apontamento?')){ document.getElementById('del_apont').value=id; document.getElementById('formExcluirApont').submit(); } }
</script>
<?php endif; ?>

<!-- Modal Etapa -->
<div id="modal-etapa" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obras/salvarEtapa') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Etapa do cronograma</h5></div>
        <div class="modal-body">
            <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
            <input type="hidden" name="idEtapa" id="et_id" value="">
            <div class="span5" style="margin-left:0"><label>Código EAP</label><input type="text" class="span12" name="codigo_eap" id="et_cod"></div>
            <div class="span7"><label>Nome *</label><input type="text" class="span12" name="nome" id="et_nome" required></div>
            <div class="span4" style="margin-left:0"><label>Peso %</label><input type="number" step="0.01" class="span12" name="peso_percentual" id="et_peso" value="0"></div>
            <div class="span4"><label>Valor previsto</label><input type="number" step="0.01" class="span12" name="valor_previsto" id="et_valor" value="0"></div>
            <div class="span4"><label>% concluído</label><input type="number" step="0.01" class="span12" name="percentual_concluido" id="et_pct" value="0"></div>
            <div class="span6" style="margin-left:0"><label>Início previsto</label><input type="text" class="span12 datepicker" name="data_inicio_prevista" id="et_ini"></div>
            <div class="span6"><label>Fim previsto</label><input type="text" class="span12 datepicker" name="data_fim_prevista" id="et_fim"></div>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button class="button btn btn-success"><span class="button__text2">Salvar</span></button></div>
    </form>
</div>
<form id="formExcluirEtapa" action="<?= site_url('obras/excluirEtapa') ?>" method="post" style="display:none">
    <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>"><input type="hidden" name="idEtapa" id="del_etapa">
</form>

<!-- Modal RDO -->
<div id="modal-rdo" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obras/salvarRdo') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Diário de Obra</h5></div>
        <div class="modal-body">
            <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
            <div class="span4" style="margin-left:0"><label>Data</label><input type="text" class="span12 datepicker" name="data" value="<?= date('d/m/Y') ?>"></div>
            <div class="span4"><label>Efetivo total</label><input type="number" class="span12" name="efetivo_total" value="0"></div>
            <div class="span4"><label>Condição</label><select name="condicao" class="span12"><option value="praticavel">Praticável</option><option value="parcial">Parcial</option><option value="impraticavel">Impraticável</option></select></div>
            <?php $climas = ['ensolarado' => 'Ensolarado', 'nublado' => 'Nublado', 'chuvoso' => 'Chuvoso']; ?>
            <?php foreach (['manha' => 'Manhã', 'tarde' => 'Tarde', 'noite' => 'Noite'] as $p => $lbl): ?>
                <div class="span4 <?= $p === 'manha' ? '' : '' ?>" style="<?= $p === 'manha' ? 'margin-left:0' : '' ?>"><label>Clima <?= $lbl ?></label><select name="clima_<?= $p ?>" class="span12"><option value="">—</option><?php foreach ($climas as $ck => $cv): ?><option value="<?= $ck ?>"><?= $cv ?></option><?php endforeach; ?></select></div>
            <?php endforeach; ?>
            <div class="span12" style="margin-left:0"><label>Atividades executadas</label><textarea class="span12" name="atividades" rows="3"></textarea></div>
            <div class="span6" style="margin-left:0"><label>Ocorrências</label><textarea class="span12" name="ocorrencias" rows="2"></textarea></div>
            <div class="span6"><label>Observações</label><textarea class="span12" name="observacoes" rows="2"></textarea></div>
            <div class="span12" style="margin-left:0"><label>Fotos</label><input type="file" accept="image/*" multiple id="rdoFotos" capture="environment"><div id="rdoFotosHidden"></div><div id="rdoFotosPrev" class="obra-fotos-prev"></div></div>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button class="button btn btn-success"><span class="button__text2">Registrar RDO</span></button></div>
    </form>
</div>

<!-- Modal Medição -->
<div id="modal-medicao" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obras/salvarMedicao') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Nova medição</h5></div>
        <div class="modal-body">
            <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
            <div class="span6" style="margin-left:0"><label>Período de</label><input type="text" class="span12 datepicker" name="periodo_inicio"></div>
            <div class="span6"><label>até</label><input type="text" class="span12 datepicker" name="periodo_fim"></div>
            <div class="span12" style="margin-left:0"><label>% concluído por etapa</label>
                <table class="table table-bordered"><thead><tr><th>Etapa</th><th>Atual</th><th>Novo %</th></tr></thead><tbody>
                    <?php foreach ($etapas as $e): ?>
                        <tr><td><?= html_escape($e->nome) ?></td><td><?= $fmt($e->percentual_concluido) ?>%</td>
                            <td><input type="number" step="0.01" min="<?= (float) $e->percentual_concluido ?>" max="100" name="pct_<?= $e->idEtapa ?>" value="<?= (float) $e->percentual_concluido ?>" style="width:80px"></td></tr>
                    <?php endforeach; ?>
                </tbody></table>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button class="button btn btn-success"><span class="button__text2">Registrar</span></button></div>
    </form>
</div>

<link rel="stylesheet" href="<?= base_url() ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script src="<?= base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script>
    $(function () {
        $('.datepicker').datepicker({ dateFormat: 'dd/mm/yy' });

        // abas por hash
        function ativar(tab) {
            $('.obra-pane').hide();
            $('#tab-' + tab).show();
            $('#obraTabs li').removeClass('active');
            $('#obraTabs a[data-tab="' + tab + '"]').parent().addClass('active');
        }
        $('#obraTabs a').on('click', function (e) {
            e.preventDefault();
            var t = $(this).data('tab');
            ativar(t);
            history.replaceState(null, '', '#' + t);
        });
        var h = (location.hash || '').replace('#', '');
        if (h && $('#tab-' + h).length) ativar(h);

        // fotos do RDO -> base64 em hidden
        $('#rdoFotos').on('change', function () {
            $('#rdoFotosHidden').empty(); $('#rdoFotosPrev').empty();
            Array.prototype.forEach.call(this.files, function (file) {
                var reader = new FileReader();
                reader.onload = function (ev) {
                    $('#rdoFotosHidden').append('<input type="hidden" name="fotos[]" value="' + ev.target.result + '">');
                    $('#rdoFotosPrev').append('<img src="' + ev.target.result + '">');
                };
                reader.readAsDataURL(file);
            });
        });
    });

    function novaEtapa() {
        $('#et_id,#et_cod,#et_nome,#et_ini,#et_fim').val('');
        $('#et_peso,#et_valor,#et_pct').val('0');
    }
    function editarEtapa(e) {
        $('#et_id').val(e.idEtapa); $('#et_cod').val(e.codigo_eap); $('#et_nome').val(e.nome);
        $('#et_peso').val(e.peso_percentual); $('#et_valor').val(e.valor_previsto); $('#et_pct').val(e.percentual_concluido);
        $('#et_ini').val(e.data_inicio_prevista && e.data_inicio_prevista !== '0000-00-00' ? e.data_inicio_prevista.split('-').reverse().join('/') : '');
        $('#et_fim').val(e.data_fim_prevista && e.data_fim_prevista !== '0000-00-00' ? e.data_fim_prevista.split('-').reverse().join('/') : '');
        $('#modal-etapa').modal('show');
    }
    function excluirEtapa(id) {
        if (confirm('Excluir esta etapa?')) { $('#del_etapa').val(id); $('#formExcluirEtapa').submit(); }
    }
</script>
