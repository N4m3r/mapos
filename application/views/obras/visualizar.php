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
$condicoesRdo = ['praticavel' => 'Normal', 'parcial' => 'Impedimento parcial', 'impraticavel' => 'Sem acesso'];
$statusLabels = ['planejamento' => 'Planejamento', 'em_execucao' => 'Em execução', 'paralisada' => 'Paralisada', 'concluida' => 'Concluída', 'entregue' => 'Entregue', 'cancelada' => 'Cancelada'];
?>
<?php
$coresStatus = ['planejamento' => 'info', 'em_execucao' => 'sucesso', 'paralisada' => 'alerta', 'concluida' => 'info', 'entregue' => 'sucesso', 'cancelada' => 'perigo'];
?>
<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css">

<div class="ui-cabecalho">
    <div>
        <h1 class="ui-titulo"><i class="bx bx-building-house"></i> Projeto: <?= html_escape($obra->nome) ?></h1>
        <p class="ui-subtitulo">Visão geral do projeto, cronograma, material, registros de execução, medição, equipe, mão de obra e OS.</p>
    </div>
    <div class="ui-acoes">
        <a href="<?= site_url('obras') ?>" class="button btn btn-mini btn-warning"><span class="button__icon"><i class='bx bx-undo'></i></span><span class="button__text2">Voltar</span></a>
    </div>
</div>

<div class="obra-header widget-box">
    <div class="widget-content">
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;align-items:center">
            <div>
                <h4 style="margin:0"><?= html_escape($obra->nome) ?> <?= $obra->codigo ? '<small>#' . html_escape($obra->codigo) . '</small>' : '' ?></h4>
                <div><i class='bx bx-user'></i> <?= html_escape($obra->nomeCliente ?: '—') ?> &nbsp; <i class='bx bx-wrench'></i> <?= html_escape($obra->responsavel ?: '—') ?></div>
                <div><i class='bx bx-map'></i> <?= html_escape(trim($obra->logradouro . ', ' . $obra->numero . ' - ' . $obra->cidade . '/' . $obra->uf, ' ,-/')) ?: '—' ?></div>
                <span class="ui-selo <?= $coresStatus[$obra->status] ?? '' ?>"><?= $statusLabels[$obra->status] ?? $obra->status ?></span>
            </div>
            <div style="text-align:center;min-width:180px">
                <div class="obra-progress"><div class="obra-progress-bar" style="width:<?= (float) $obra->percentual_concluido ?>%"></div></div>
                <strong><?= $fmt($obra->percentual_concluido) ?>% concluído</strong>
            </div>
        </div>
        <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap">
            <?php if (! empty($pode_executar)): ?><a href="#modal-registro" data-toggle="modal" class="button btn btn-success" style="font-weight:bold"><span class="button__icon"><i class='bx bx-check-circle'></i></span><span class="button__text2">Registrar execução de hoje</span></a><?php endif; ?>
            <?php if ($perm('eObras')): ?><a href="<?= site_url('obras/editar/' . $obra->idObra) ?>" class="button btn btn-mini btn-inverse"><span class="button__icon"><i class='bx bx-edit'></i></span><span class="button__text2">Editar</span></a><?php endif; ?>
            <?php if ($perm('rObraMaterial')): ?><a href="<?= site_url('obramaterial/receber/' . $obra->idObra) ?>" class="button btn btn-mini btn-success"><span class="button__icon"><i class='bx bx-down-arrow-circle'></i></span><span class="button__text2">Receber material</span></a><?php endif; ?>
            <?php if ($perm('sObraMaterial')): ?><a href="<?= site_url('obramaterial/entregar/' . $obra->idObra) ?>" class="button btn btn-mini btn-primary"><span class="button__icon"><i class='bx bx-up-arrow-circle'></i></span><span class="button__text2">Entregar material</span></a><?php endif; ?>
        </div>
    </div>
</div>

<ul class="nav nav-tabs obra-tabs" id="obraTabs">
    <li class="active"><a href="#tab-geral" data-tab="geral">Visão geral</a></li>
    <li><a href="#tab-pontos" data-tab="pontos">Pontos</a></li>
    <li><a href="#tab-cronograma" data-tab="cronograma">Cronograma</a></li>
    <li><a href="#tab-material" data-tab="material">Material</a></li>
    <li><a href="#tab-rdo" data-tab="rdo">Registros de execução</a></li>
    <li><a href="#tab-medicao" data-tab="medicao">Medição</a></li>
    <li><a href="#tab-equipe" data-tab="equipe">Equipe</a></li>
    <li><a href="#tab-maodeobra" data-tab="maodeobra">Mão de obra</a></li>
    <li><a href="#tab-os" data-tab="os">Ordens de Serviço</a></li>
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

    <!-- PONTOS -->
    <?php
    $ptEdita = $perm('eObras');
    $ptMuda = $ptEdita || ! empty($pode_executar);
    $ptFiltrado = ($pontos_filtro['tipo'] !== '' || $pontos_filtro['status'] !== ''
        || ($pontos_filtro['aguardando'] ?? '') !== '' || ($pontos_filtro['resp'] ?? '') !== '');
    $ptCols = 11 + ($ptEdita ? 1 : 0) + ($ptMuda ? 1 : 0);
    ?>
    <div class="obra-pane" id="tab-pontos" style="display:none">
        <div class="pt-barra">
            <?php if ($ptEdita): ?>
                <a href="#modal-ponto" data-toggle="modal" class="button btn btn-mini btn-success" data-chamar="novoPonto"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Novo ponto</span></a>
                <a href="#modal-lote" data-toggle="modal" class="button btn btn-mini btn-primary"><span class="button__icon"><i class='bx bx-layer-plus'></i></span><span class="button__text2">Gerar em lote</span></a>
            <?php endif; ?>
            <a href="<?= site_url('obras/asbuilt/' . (int) $obra->idObra) ?>" target="_blank" rel="noopener" class="button btn btn-mini btn-inverse"><span class="button__icon"><i class='bx bx-printer'></i></span><span class="button__text2">Imprimir as-built</span></a>
        </div>

        <?php if (! empty($pontos_resumo['por_tipo'])): ?>
            <div class="pt-cartoes">
                <?php foreach ($pontos_resumo['por_tipo'] as $tk => $tr):
                    $pct = $tr['total'] > 0 ? round($tr['testados'] * 100 / $tr['total']) : 0; ?>
                    <div class="pt-cartao">
                        <div class="pt-cartao-titulo"><i class="bx <?= html_escape($pontos_tipos[$tk]['icone'] ?? 'bx-chip') ?>"></i> <?= html_escape($tr['rotulo']) ?></div>
                        <div class="pt-cartao-num"><?= (int) $tr['testados'] ?> de <?= (int) $tr['total'] ?> testados</div>
                        <div class="obra-progress mini"><div class="obra-progress-bar" style="width:<?= (int) $pct ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="pt-resumo-linha">
                <strong><?= (int) $pontos_resumo['total'] ?></strong> ponto(s)
                <?php if ($pontos_resumo['progresso'] !== null): ?> &middot; progresso <strong><?= $fmt($pontos_resumo['progresso']) ?>%</strong><?php endif; ?>
                <?php if ((float) $pontos_resumo['cabo_m'] > 0): ?> &middot; cabo passado <strong><?= $fmt($pontos_resumo['cabo_m']) ?> m</strong><?php endif; ?>
            </p>
        <?php endif; ?>

        <?php if ($pontos_resumo['total'] > 0): ?>
        <form action="<?= site_url('obras/visualizar/' . (int) $obra->idObra) ?>#pontos" method="get" class="pt-filtros">
            <select name="pt_tipo" aria-label="Filtrar por tipo">
                <option value="">Todos os tipos</option>
                <?php foreach ($pontos_tipos as $k => $t): ?>
                    <option value="<?= html_escape($k) ?>" <?= $pontos_filtro['tipo'] === $k ? 'selected' : '' ?>><?= html_escape($t['rotulo']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="pt_status" aria-label="Filtrar por status">
                <option value="">Todos os status</option>
                <?php foreach ($pontos_status as $k => $st): ?>
                    <option value="<?= html_escape($k) ?>" <?= $pontos_filtro['status'] === $k ? 'selected' : '' ?>><?= html_escape($st['rotulo']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="pt_aguardando" aria-label="Filtrar por etapa aguardando">
                <option value="">Qualquer etapa</option>
                <?php foreach ($pontos_status as $k => $st):
                    $rotAg = $k === 'previsto' ? '' : Obra_pontos_model::rotuloAguardando($k);
                    if ($rotAg === '') continue; ?>
                    <option value="<?= html_escape($k) ?>" <?= ($pontos_filtro['aguardando'] ?? '') === $k ? 'selected' : '' ?>><?= html_escape($rotAg) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="pt_resp" aria-label="Filtrar por quem está com o ponto">
                <option value="">Com quem: todos</option>
                <option value="equipe" <?= ($pontos_filtro['resp'] ?? '') === 'equipe' ? 'selected' : '' ?>>Equipe (livre)</option>
                <?php foreach ($executores as $ex): $exId = (string) (int) $ex['idUsuarios']; ?>
                    <option value="<?= html_escape($exId) ?>" <?= ($pontos_filtro['resp'] ?? '') === $exId ? 'selected' : '' ?>><?= html_escape($ex['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button btn btn-mini btn-primary"><span class="button__icon"><i class='bx bx-filter-alt'></i></span><span class="button__text2">Filtrar</span></button>
            <?php if ($ptFiltrado): ?><a href="<?= site_url('obras/visualizar/' . (int) $obra->idObra) ?>#pontos" class="button btn btn-mini btn-warning"><span class="button__text2">Limpar</span></a><?php endif; ?>
        </form>
        <?php endif; ?>

        <?php if ($ptEdita): ?>
            <form id="form-repasse-lote" action="<?= site_url('obras/repassarPontos') ?>" method="post" class="pt-lote">
                <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
                <span class="pt-lote-rotulo"><i class="bx bx-transfer-alt"></i> Repassar selecionados para</span>
                <select name="destinatario" aria-label="Quem recebe os pontos selecionados">
                    <option value="">Equipe (livre)</option>
                    <?php foreach ($executores as $ex): ?>
                        <option value="<?= (int) $ex['idUsuarios'] ?>"><?= html_escape($ex['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="instrucao" maxlength="255" class="pt-lote-instrucao" placeholder="Instrução — não escreva senhas" aria-label="Instrução para quem recebe">
                <button class="button btn btn-mini btn-primary" title="Repassar os pontos marcados"><span class="button__icon"><i class='bx bx-check'></i></span><span class="button__text2">Repassar selecionados</span></button>
            </form>
            <?php if (! empty($plan_ativo) && $etapas): ?>
                <div class="pt-lote">
                    <span class="pt-lote-rotulo"><i class="bx bx-receipt"></i> Pontos sem OS marcados</span>
                    <a href="#modal-os-pontos" data-toggle="modal" class="button btn btn-mini btn-primary js-os-pontos" title="Gerar uma OS com os pontos marcados"><span class="button__icon"><i class='bx bx-receipt'></i></span><span class="button__text2">Gerar OS para selecionados</span></a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="widget-box"><div class="widget-content nopadding ui-tabela-rolagem">
            <table class="table table-bordered pt-tabela">
                <thead><tr><?php if ($ptEdita): ?><th class="pt-col-sel"><input type="checkbox" id="pt-selecionar-todos" title="Selecionar todos" aria-label="Selecionar todos os pontos"></th><?php endif; ?><th class="pt-col-foto">Foto</th><th>Identificação</th><th>Tipo</th><th>Local</th><th>IP</th><th>Canal</th><th>Porta</th><th>Metros</th><th>Status</th><th>Com / aguardando</th><th>Atualizado em</th><?php if ($ptMuda): ?><th style="min-width:260px">Ações</th><?php endif; ?></tr></thead>
                <tbody>
                    <?php if (! $pontos): ?>
                        <tr><td colspan="<?= (int) $ptCols ?>">
                            <?php if ($ptFiltrado): ?>
                                <div class="ui-vazio"><i class="bx bx-filter-alt"></i><strong>Nenhum ponto com esse filtro.</strong></div>
                            <?php else: ?>
                                <div class="ui-vazio"><i class="bx bx-cctv"></i><strong>Nenhum ponto cadastrado.</strong>Gere os pontos em lote: ex. 16 câmeras CAM-01 a CAM-16. Depois cada um avança (cabo passado, instalado, configurado, testado) e o progresso do projeto é calculado sozinho.</div>
                            <?php endif; ?>
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($pontos as $pt):
                        $st = $pontos_status[$pt->status] ?? null; ?>
                        <?php
                        $fotosPt = array_values($fotos_pontos[$pt->idPonto] ?? []);
                        $nFotosPt = count($fotosPt);
                        $rotAguardando = Obra_pontos_model::rotuloAguardando($pt->aguardando ?? null);
                        $instrucaoPt = (string) ($pt->instrucao ?? '');
                        ?>
                        <tr>
                            <?php if ($ptEdita): ?>
                                <td class="pt-col-sel"><input type="checkbox" name="ponto_ids[]" value="<?= (int) $pt->idPonto ?>" form="form-repasse-lote" data-sem-os="<?= isset($pontosSemOs[(int) $pt->idPonto]) ? 1 : 0 ?>" aria-label="Selecionar <?= html_escape($pt->identificacao) ?>"></td>
                            <?php endif; ?>
                            <td data-label="Foto" class="pt-col-foto">
                                <?php if ($nFotosPt): ?>
                                    <div class="pt-foto-wrap">
                                        <a href="<?= site_url('obras/fotoPonto/' . (int) $fotosPt[0]->idFoto) ?>" target="_blank" rel="noopener" title="Abrir foto de <?= html_escape($pt->identificacao) ?> em nova aba">
                                            <img class="pt-foto" src="<?= site_url('obras/fotoPonto/' . (int) $fotosPt[0]->idFoto) ?>" alt="Foto de <?= html_escape($pt->identificacao) ?>" width="48" height="48" loading="lazy">
                                        </a>
                                        <?php if ($nFotosPt > 1): ?><span class="pt-foto-mais" title="<?= (int) $nFotosPt ?> fotos">+<?= (int) ($nFotosPt - 1) ?></span><?php endif; ?>
                                    </div>
                                    <?php if ($nFotosPt > 1): ?>
                                        <div class="pt-foto-links">
                                            <?php foreach ($fotosPt as $i => $fp): ?>
                                                <a href="<?= site_url('obras/fotoPonto/' . (int) $fp->idFoto) ?>" target="_blank" rel="noopener" title="Foto <?= (int) ($i + 1) ?> de <?= html_escape($pt->identificacao) ?>"><?= (int) ($i + 1) ?></a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="pt-sem-foto">—</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Identificação"><strong><?= html_escape($pt->identificacao) ?></strong></td>
                            <td data-label="Tipo"><?= html_escape($pontos_tipos[$pt->tipo]['rotulo'] ?? $pt->tipo) ?></td>
                            <td data-label="Local"><?= html_escape($pt->local ?: '—') ?></td>
                            <td data-label="IP"><?= html_escape($pt->ip ?: '—') ?></td>
                            <td data-label="Canal"><?= html_escape($pt->canal ?: '—') ?></td>
                            <td data-label="Porta"><?= html_escape($pt->porta ?: '—') ?></td>
                            <td data-label="Metros"><?= (float) $pt->metragem_cabo > 0 ? $fmt($pt->metragem_cabo) : '—' ?></td>
                            <td data-label="Status"><span class="ui-selo <?= html_escape($st['classe'] ?? '') ?>"><?= html_escape($st['rotulo'] ?? $pt->status) ?></span></td>
                            <td data-label="Com / aguardando" class="pt-col-com">
                                <?php $comNome = trim((string) ($pt->responsavel_nome ?? '')); ?>
                                <?php if ($comNome !== ''): ?>
                                    <span class="pt-com-nome"><?= html_escape($comNome) ?></span>
                                <?php elseif (! empty($pt->aguardando)): ?>
                                    <span class="pt-com-nome pt-com-equipe">Equipe</span>
                                <?php else: ?>
                                    <span class="pt-sem-foto">—</span>
                                <?php endif; ?>
                                <?php if ($rotAguardando !== ''): ?>
                                    <div class="pt-aguard" title="<?= html_escape($instrucaoPt !== '' ? 'Instrução: ' . $instrucaoPt : $rotAguardando) ?>"><?= html_escape($rotAguardando) ?></div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Atualizado em"><?= ! empty($pt->atualizado_em) ? date('d/m/Y H:i', strtotime($pt->atualizado_em)) : '—' ?></td>
                            <?php if ($ptMuda): ?>
                                <td class="pt-acoes">
                                    <form action="<?= site_url('obras/statusPonto') ?>" method="post" class="pt-status-form">
                                        <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
                                        <input type="hidden" name="ponto_id" value="<?= (int) $pt->idPonto ?>">
                                        <select name="status" aria-label="Novo status de <?= html_escape($pt->identificacao) ?>">
                                            <?php foreach ($pontos_status as $k => $s2): ?>
                                                <option value="<?= html_escape($k) ?>" <?= $pt->status === $k ? 'selected' : '' ?>><?= html_escape($s2['rotulo']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" step="0.01" min="0" name="metragem_cabo" placeholder="m" title="Metros de cabo passados agora" aria-label="Metros de cabo">
                                        <button class="btn btn-mini btn-success" title="Atualizar status">OK</button>
                                    </form>
                                    <?php if ($ptEdita): ?>
                                        <a href="#" class="btn-nwe3" title="Editar" aria-label="Editar" data-chamar="editarPonto" data-args="<?= html_escape(json_encode([$pt])) ?>" data-cancelar><i class="bx bx-edit bx-xs"></i></a>
                                        <a href="#" class="btn-nwe4" title="Excluir" aria-label="Excluir" data-chamar="excluirPonto" data-args="[<?= (int) $pt->idPonto ?>]" data-cancelar><i class="bx bx-trash-alt bx-xs"></i></a>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
    </div>

    <!-- CRONOGRAMA -->
    <div class="obra-pane" id="tab-cronograma" style="display:none">
        <?php
        $planOk = ! empty($plan_ativo) && $perm('eObras');
        $etapasSemOs = [];
        foreach ($etapas as $_e) {
            if (empty($osPorEtapa[$_e->idEtapa])) {
                $etapasSemOs[] = $_e;
            }
        }
        $coresOs = ['Aberto' => 'info', 'Orçamento' => 'neutro', 'Em Andamento' => 'alerta', 'Aguardando peças' => 'alerta', 'Finalizado' => 'sucesso', 'Faturado' => 'sucesso', 'Cancelado' => 'perigo'];
        ?>
        <?php if ($planOk && $etapas): ?>
            <div style="padding:0 0 8px"><a href="#modal-os-lote" data-toggle="modal" class="button btn btn-mini btn-primary"><span class="button__icon"><i class='bx bx-receipt'></i></span><span class="button__text2">Gerar OS das etapas sem OS</span></a></div>
        <?php endif; ?>
        <div class="widget-box"><div class="widget-content nopadding ui-tabela-rolagem">
            <?php if ($perm('eObraCronograma')): ?>
                <div style="padding:8px"><a href="#modal-etapa" data-toggle="modal" class="button btn btn-mini btn-success" data-chamar="novaEtapa"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Nova etapa</span></a></div>
            <?php endif; ?>
            <?php if ($perm('eObras') && trim((string) $obra->tipo_obra) !== ''): ?>
                <form action="<?= site_url('obras/aplicarEtapasPadrao') ?>" method="post" style="padding:0 8px 8px;margin:0">
                    <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
                    <button class="button btn btn-mini btn-primary" data-confirmar="Criar as etapas padrão para &quot;<?= html_escape($obra->tipo_obra) ?>&quot;? As que já existem não são repetidas."><span class="button__icon"><i class='bx bx-list-plus'></i></span><span class="button__text2">Criar etapas padrão</span></button>
                </form>
            <?php endif; ?>
            <table class="table table-bordered">
                <thead><tr><th>EAP</th><th>Etapa</th><th>Peso %</th><th>Valor prev.</th><?php if (! empty($plan_ativo)): ?><th>Itens prev.</th><th>OS</th><?php endif; ?><th>Início</th><th>Fim</th><th>%</th><?php if ($perm('eObraCronograma')): ?><th>Ações</th><?php endif; ?></tr></thead>
                <tbody>
                    <?php if (! $etapas) echo '<tr><td colspan="8"><div class="ui-vazio"><i class="bx bx-list-ul"></i><strong>Nenhuma etapa cadastrada.</strong></div></td></tr>'; ?>
                    <?php foreach ($etapas as $e): ?>
                        <tr>
                            <td><?= html_escape($e->codigo_eap) ?></td>
                            <td><?= html_escape($e->nome) ?></td>
                            <td><?= $fmt($e->peso_percentual) ?></td>
                            <td>R$ <?= $fmt($e->valor_previsto) ?></td>
                            <?php if (! empty($plan_ativo)):
                                $osEt = $osPorEtapa[$e->idEtapa] ?? [];
                                $itEt = $itensPorEtapa[$e->idEtapa] ?? []; ?>
                                <td>
                                    R$ <?= $fmt($totaisPrevistos[$e->idEtapa] ?? 0) ?>
                                    <?php if ($planOk || $itEt): ?><br><a href="#" class="js-itens-toggle" data-alvo="itens-etapa-<?= (int) $e->idEtapa ?>"><small><i class="bx bx-list-ul"></i> Itens (<?= count($itEt) ?>)</small></a><?php endif; ?>
                                </td>
                                <td>
                                    <small><?= count($osEt) ?> OS</small>
                                    <?php foreach ($osEt as $oe): $oe = (object) $oe; ?>
                                        <br><a href="<?= site_url('os/visualizar/' . (int) $oe->idOs) ?>" class="ui-selo <?= $coresOs[$oe->status] ?? 'neutro' ?>" title="<?= html_escape(($oe->status ?? '') . (! empty($oe->tecnico_nome) ? ' - ' . $oe->tecnico_nome : '')) ?>">#<?= (int) $oe->idOs ?> <?= html_escape($oe->status ?? '') ?></a>
                                    <?php endforeach; ?>
                                    <?php if ($planOk): ?><br><a href="#modal-os-etapa" data-toggle="modal" class="button btn btn-mini btn-primary js-gerar-os" data-etapa="<?= (int) $e->idEtapa ?>" data-nome="<?= html_escape($e->nome) ?>" data-inicio="<?= html_escape($data_br($e->data_inicio_prevista) === '—' ? '' : $data_br($e->data_inicio_prevista)) ?>"><span class="button__icon"><i class='bx bx-receipt'></i></span><span class="button__text2">Gerar OS</span></a><?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td><?= $data_br($e->data_inicio_prevista) ?></td>
                            <td><?= $data_br($e->data_fim_prevista) ?></td>
                            <td>
                                <div class="obra-progress mini"><div class="obra-progress-bar" style="width:<?= (float) $e->percentual_concluido ?>%"></div></div>
                                <small><?= $fmt($e->percentual_concluido) ?>%</small>
                            </td>
                            <?php if ($perm('eObraCronograma')): ?>
                                <td>
                                    <a href="#" class="btn-nwe3" title="Editar" aria-label="Editar" data-chamar="editarEtapa" data-args="<?= html_escape(json_encode([$e])) ?>" data-cancelar><i class="bx bx-edit bx-xs"></i></a>
                                    <a href="#" class="btn-nwe4" title="Excluir" aria-label="Excluir" data-chamar="excluirEtapa" data-args="[<?= (int) $e->idEtapa ?>]" data-cancelar><i class="bx bx-trash-alt bx-xs"></i></a>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php if (! empty($plan_ativo) && ($planOk || ! empty($itensPorEtapa[$e->idEtapa]))): ?>
                        <tr id="itens-etapa-<?= (int) $e->idEtapa ?>" class="js-itens-row" style="display:none">
                            <td colspan="10">
                                <strong><i class="bx bx-package"></i> Itens previstos de &quot;<?= html_escape($e->nome) ?>&quot;</strong>
                                <table class="table table-bordered" style="margin-top:6px">
                                    <thead><tr><th>Tipo</th><th>Descrição</th><th>Qtd</th><th>Preço</th><th>Subtotal</th><?php if ($planOk): ?><th></th><?php endif; ?></tr></thead>
                                    <tbody>
                                    <?php if (empty($itensPorEtapa[$e->idEtapa])): ?><tr><td colspan="6"><small>Nenhum item previsto.</small></td></tr><?php endif; ?>
                                    <?php foreach (($itensPorEtapa[$e->idEtapa] ?? []) as $it): $it = (object) $it; ?>
                                        <tr>
                                            <td><?= $it->tipo === 'servico' ? 'Serviço' : 'Produto' ?></td>
                                            <td><?= html_escape($it->descricao) ?></td>
                                            <td><?= $fmt($it->quantidade) ?></td>
                                            <td>R$ <?= $fmt($it->preco) ?></td>
                                            <td>R$ <?= $fmt($it->subtotal) ?></td>
                                            <?php if ($planOk): ?>
                                                <td>
                                                    <form action="<?= site_url('obras/excluirItemEtapa') ?>" method="post" style="margin:0" class="js-excluir-item">
                                                        <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
                                                        <input type="hidden" name="idItem" value="<?= (int) $it->idItem ?>">
                                                        <button class="btn-nwe4" style="border:0;background:none" title="Excluir item" aria-label="Excluir item"><i class="bx bx-trash-alt bx-xs"></i></button>
                                                    </form>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php if ($planOk): ?>
                                <form action="<?= site_url('obras/salvarItemEtapa') ?>" method="post" class="js-form-item" style="margin:0;display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                                    <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
                                    <input type="hidden" name="etapa_id" value="<?= (int) $e->idEtapa ?>">
                                    <input type="hidden" name="referencia_id" class="js-ref">
                                    <select name="tipo" class="js-tipo" style="width:110px;margin:0" aria-label="Tipo do item"><option value="produto">Produto</option><option value="servico">Serviço</option></select>
                                    <input type="text" class="js-busca" placeholder="Buscar produto/serviço..." autocomplete="off" style="width:260px;margin:0" aria-label="Buscar item">
                                    <input type="text" name="quantidade" value="1" style="width:70px;margin:0" aria-label="Quantidade">
                                    <input type="text" name="preco" class="js-preco" placeholder="Preço" style="width:90px;margin:0" aria-label="Preço (opcional)">
                                    <button class="button btn btn-mini btn-success"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Adicionar</span></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
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
        <div class="widget-box"><div class="widget-title"><h5>Almoxarifado — saldo atual</h5></div><div class="widget-content nopadding ui-tabela-rolagem">
            <table class="table table-bordered">
                <thead><tr><th>Item</th><th>Cód. barras</th><th>Un.</th><th>Origem</th><th>Saldo</th></tr></thead>
                <tbody>
                    <?php if (! $saldos) echo '<tr><td colspan="5"><div class="ui-vazio"><i class="bx bx-package"></i><strong>Sem material em estoque.</strong></div></td></tr>'; ?>
                    <?php foreach ($saldos as $s): ?>
                        <tr>
                            <td><?= html_escape($s->descricao) ?></td>
                            <td><?= html_escape($s->cod_barras) ?></td>
                            <td><?= html_escape($s->unidade) ?></td>
                            <td><?= $s->do_cliente ? '<span class="ui-selo info">Cliente</span>' : 'Próprio' ?></td>
                            <td><strong><?= rtrim(rtrim(number_format((float) $s->saldo, 3, ',', '.'), '0'), ',') ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
        <div class="span6" style="margin-left:0"><div class="widget-box"><div class="widget-title"><h5>Recebimentos</h5></div><div class="widget-content nopadding ui-tabela-rolagem">
            <table class="table table-bordered"><thead><tr><th>#</th><th>Data</th><th>Origem</th><th>Doc.</th></tr></thead><tbody>
                <?php if (! $recebimentos) echo '<tr><td colspan="4">—</td></tr>'; ?>
                <?php foreach ($recebimentos as $r): ?><tr><td><?= html_escape($r->numero) ?></td><td><?= $data_br($r->data) ?></td><td><?= html_escape($r->origem) ?></td><td><?= html_escape($r->documento) ?></td></tr><?php endforeach; ?>
            </tbody></table>
        </div></div></div>
        <div class="span6"><div class="widget-box"><div class="widget-title"><h5>Entregas à equipe</h5></div><div class="widget-content nopadding ui-tabela-rolagem">
            <table class="table table-bordered"><thead><tr><th>#</th><th>Data</th><th>Equipe</th><th>Recebedor</th></tr></thead><tbody>
                <?php if (! $entregas) echo '<tr><td colspan="4">—</td></tr>'; ?>
                <?php foreach ($entregas as $e): ?><tr><td><?= html_escape($e->numero) ?></td><td><?= $data_br($e->data) ?></td><td><?= html_escape($e->equipe ?: '—') ?></td><td><?= html_escape($e->recebedor_nome) ?></td></tr><?php endforeach; ?>
            </tbody></table>
        </div></div></div>
    </div>

    <!-- RDO -->
    <div class="obra-pane" id="tab-rdo" style="display:none">
        <div class="widget-box"><div class="widget-content nopadding ui-tabela-rolagem">
            <?php if ($perm('cObraRdo')): ?>
                <div style="padding:8px"><a href="#modal-rdo" data-toggle="modal" class="button btn btn-mini btn-success"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Novo registro</span></a></div>
            <?php endif; ?>
            <table class="table table-bordered">
                <thead><tr><th>#</th><th>Data</th><th>O que foi feito</th><th>Condição</th><th>Técnicos</th><th>Responsável</th><th style="width:60px">Ações</th></tr></thead>
                <tbody>
                    <?php if (! $rdos) echo '<tr><td colspan="7"><div class="ui-vazio"><i class="bx bx-notepad"></i><strong>Nenhum registro de execução.</strong>Registre o que foi feito no dia para aparecer aqui.</div></td></tr>'; ?>
                    <?php foreach ($rdos as $r): ?>
                        <tr>
                            <td><?= html_escape($r->numero) ?></td>
                            <td><?= $data_br($r->data) ?></td>
                            <td><?= html_escape(mb_strimwidth((string) $r->atividades, 0, 80, '…')) ?: '—' ?></td>
                            <td><?= html_escape($condicoesRdo[$r->condicao] ?? (string) $r->condicao) ?></td>
                            <td><?= (int) $r->efetivo_total ?></td>
                            <td><?= html_escape($r->responsavel) ?></td>
                            <td><a href="<?= site_url('obras/imprimirRdo/' . $r->idRdo) ?>" target="_blank" class="btn-nwe" title="Imprimir registro" aria-label="Imprimir registro"><i class="bx bx-printer bx-xs"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
    </div>

    <!-- MEDIÇÃO -->
    <div class="obra-pane" id="tab-medicao" style="display:none">
        <div class="widget-box"><div class="widget-content nopadding ui-tabela-rolagem">
            <?php if ($perm('cObraMedicao') && $etapas): ?>
                <div style="padding:8px"><a href="#modal-medicao" data-toggle="modal" class="button btn btn-mini btn-success"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Nova medição</span></a></div>
            <?php elseif (! $etapas): ?>
                <div style="padding:8px" class="text-warning">Cadastre etapas no cronograma para poder medir.</div>
            <?php endif; ?>
            <table class="table table-bordered">
                <thead><tr><th>#</th><th>Período</th><th>% acum.</th><th>Valor</th><th>Status</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php if (! $medicoes) echo '<tr><td colspan="6"><div class="ui-vazio"><i class="bx bx-receipt"></i><strong>Nenhuma medição.</strong></div></td></tr>'; ?>
                    <?php foreach ($medicoes as $m): ?>
                        <tr>
                            <td><?= html_escape($m->numero) ?></td>
                            <td><?= $data_br($m->periodo_inicio) ?> a <?= $data_br($m->periodo_fim) ?></td>
                            <td><?= $fmt($m->percentual_acumulado) ?>%</td>
                            <td>R$ <?= $fmt($m->valor_medido) ?></td>
                            <td><span class="label"><?= html_escape($m->status) ?></span></td>
                            <td>
                                <?php if ($m->status === 'aberta' && $perm('aObraMedicao')): ?>
                                    <form action="<?= site_url('obras/aprovarMedicao') ?>" method="post" style="display:inline">
                                        <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
                                        <input type="hidden" name="idMedicao" value="<?= $m->idMedicao ?>">
                                        <button class="button btn btn-mini btn-success" data-confirmar="Aprovar medição?"><span class="button__text2">Aprovar</span></button>
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
        <div class="widget-box"><div class="widget-content nopadding ui-tabela-rolagem">
            <table class="table table-bordered">
                <thead><tr><th>Equipe</th><th>Encarregado</th><th>Início</th><th>Fim</th><th>Ativa</th></tr></thead>
                <tbody>
                    <?php if (! $equipes) echo '<tr><td colspan="5"><div class="ui-vazio"><i class="bx bx-group"></i><strong>Nenhuma equipe alocada.</strong></div></td></tr>'; ?>
                    <?php foreach ($equipes as $q): ?>
                        <tr><td><?= html_escape($q->nome) ?></td><td><?= html_escape($q->encarregado) ?></td><td><?= $data_br($q->data_inicio) ?></td><td><?= $data_br($q->data_fim) ?></td><td><?= $q->ativo ? 'Sim' : 'Não' ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>

        <!-- Usuários com acesso de execução -->
        <div class="widget-box"><div class="widget-title"><h5 style="padding-left:8px"><i class='bx bx-user-check'></i> Usuários com acesso ao projeto</h5></div>
            <div class="widget-content nopadding ui-tabela-rolagem">
                <p style="padding:8px 8px 0;margin:0;color:#888">Apenas estes usuários (e o responsável do projeto) podem registrar a execução das atividades.</p>
                <table class="table table-bordered">
                    <thead><tr><th>Usuário</th><th>E-mail</th><?php if ($perm('eObras')): ?><th style="width:60px">Ações</th><?php endif; ?></tr></thead>
                    <tbody>
                        <?php if (! $usuarios_projeto) echo '<tr><td colspan="3"><div class="ui-vazio"><i class="bx bx-user-check"></i><strong>Nenhum usuário vinculado.</strong>O responsável do projeto ainda pode registrar.</div></td></tr>'; ?>
                        <?php foreach ($usuarios_projeto as $u): ?>
                            <tr>
                                <td><?= html_escape($u->nome ?: '—') ?></td>
                                <td><?= html_escape($u->email ?: '—') ?></td>
                                <?php if ($perm('eObras')): ?>
                                    <td><a href="#" class="btn-nwe4" title="Remover acesso" aria-label="Remover acesso" data-chamar="desvincularUsuario" data-args="[<?= (int) $u->idObraUsuario ?>]" data-cancelar><i class="bx bx-user-x bx-xs"></i></a></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($perm('eObras')): ?>
                    <form action="<?= site_url('obras/vincularUsuario') ?>" method="post" style="padding:8px;display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
                        <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
                        <div style="flex:1;min-width:220px"><label>Adicionar usuário</label>
                            <select name="usuario_id" class="span12" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($usuarios_disponiveis as $ud): ?>
                                    <option value="<?= (int) $ud->idUsuarios ?>"><?= html_escape($ud->nome) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="button btn btn-mini btn-success"><span class="button__icon"><i class='bx bx-plus'></i></span><span class="button__text2">Dar acesso</span></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MÃO DE OBRA / EFETIVO -->
    <div class="obra-pane" id="tab-maodeobra" style="display:none">
        <?php $verValor = $perm('vObraCusto'); ?>
        <?php if ($perm('cObraRdo')): ?>
            <div class="widget-box"><div class="widget-content" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end">
                <form action="<?= site_url('obras/consolidarPonto') ?>" method="post" style="display:flex;gap:6px;align-items:flex-end;margin:0">
                    <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
                    <div><label>Gerar efetivo do dia (ponto facial/GPS)</label><input type="text" class="datepicker" name="data" value="<?= date('d/m/Y') ?>" style="width:130px"></div>
                    <button class="button btn btn-mini btn-primary" data-confirmar="Consolidar as batidas de ponto vinculadas a esta obra nesse dia?"><span class="button__icon"><i class='bx bx-sync'></i></span><span class="button__text2">Consolidar ponto</span></button>
                </form>
                <span class="scanner-status">As batidas de ponto que o colaborador vincular a esta obra viram efetivo aqui (horas/diária e custo de mão de obra).</span>
            </div></div>
        <?php endif; ?>

        <div class="widget-box"><div class="widget-title"><h5>Efetivo apontado<?php if ($verValor): ?> — total R$ <?= $fmt($apontamento_total) ?><?php endif; ?></h5></div>
            <div class="widget-content nopadding ui-tabela-rolagem">
                <table class="table table-bordered">
                    <thead><tr><th>Data</th><th>Colaborador</th><th>Função</th><th>Horas</th><th>Diária</th><th>Origem</th><?php if ($verValor): ?><th>Valor</th><?php endif; ?><?php if ($perm('cObraRdo')): ?><th></th><?php endif; ?></tr></thead>
                    <tbody>
                        <?php if (! $apontamentos) echo '<tr><td colspan="9"><div class="ui-vazio"><i class="bx bx-time-five"></i><strong>Nenhum apontamento.</strong>Consolide o ponto do dia ou lance manualmente.</div></td></tr>'; ?>
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
                                    <td><a href="#" class="btn-nwe4" title="Remover" aria-label="Remover" data-chamar="excluirApont" data-args="[<?= (int) $ap->idApontamento ?>]" data-cancelar><i class="bx bx-trash-alt bx-xs"></i></a></td>
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

    <!-- ORDENS DE SERVIÇO -->
    <?php
    $osStatusLabel = function ($s) {
        return $s ? html_escape($s) : '—';
    };
    ?>
    <div class="obra-pane" id="tab-os" style="display:none">
        <?php if ($perm('eObras')): ?>
            <div style="padding:8px 0">
                <a href="#modal-vincular-os" data-toggle="modal" class="button btn btn-mini btn-success"><span class="button__icon"><i class='bx bx-link'></i></span><span class="button__text2">Vincular OS ao projeto</span></a>
            </div>
        <?php endif; ?>

        <div class="widget-box"><div class="widget-content nopadding ui-tabela-rolagem">
            <table class="table table-bordered">
                <thead><tr><th>OS</th><th>Cliente</th><th>Técnico</th><th>Status</th><th>Etapa</th><th style="min-width:190px">Ações</th></tr></thead>
                <tbody>
                    <?php if (! $os_vinculadas) echo '<tr><td colspan="6"><div class="ui-vazio"><i class="bx bx-wrench"></i><strong>Nenhuma OS vinculada.</strong>Use "Vincular OS ao projeto" para trazer o que será executado.</div></td></tr>'; ?>
                    <?php foreach ($os_vinculadas as $o): ?>
                        <tr>
                            <td>#<?= (int) $o->idOs ?></td>
                            <td><?= html_escape($o->nomeCliente ?: '—') ?></td>
                            <td><?= html_escape($o->tecnico ?: '—') ?></td>
                            <td><span class="label"><?= $osStatusLabel($o->status) ?></span></td>
                            <td><?= html_escape($o->etapa_nome ?: '—') ?></td>
                            <td>
                                <a href="<?= site_url('os/visualizar/' . $o->idOs) ?>" class="btn-nwe" title="Abrir OS" aria-label="Abrir OS"><i class="bx bx-show bx-xs"></i></a>
                                <a href="<?= site_url('tecnico/visualizar/' . $o->idOs) ?>" class="button btn btn-mini btn-primary" style="margin:0 4px" title="Executar em campo"><span class="button__icon"><i class='bx bx-play-circle'></i></span><span class="button__text2">Executar</span></a>
                                <?php if ($perm('eObras')): ?>
                                    <a href="#" class="btn-nwe4" title="Desvincular" aria-label="Desvincular" data-chamar="desvincularOs" data-args="[<?= (int) $o->idVinculo ?>]" data-cancelar><i class="bx bx-unlink bx-xs"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>

        <?php if ($os_vinculadas): ?>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
            <div class="widget-box" style="flex:1;min-width:280px"><div class="widget-title"><h5 style="padding-left:8px"><i class='bx bx-list-check'></i> O que executar (serviços)</h5></div>
                <div class="widget-content nopadding ui-tabela-rolagem">
                    <table class="table table-bordered">
                        <thead><tr><th>OS</th><th>Serviço</th><th>Qtd.</th></tr></thead>
                        <tbody>
                            <?php if (! $os_servicos) echo '<tr><td colspan="3"><div class="ui-vazio"><strong>Sem serviços lançados nas OS.</strong></div></td></tr>'; ?>
                            <?php foreach ($os_servicos as $s): ?>
                                <tr><td>#<?= (int) $s->os_id ?></td><td><?= html_escape($s->nome ?: '—') ?></td><td><?= $fmt($s->quantidade) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="widget-box" style="flex:1;min-width:280px"><div class="widget-title"><h5 style="padding-left:8px"><i class='bx bx-package'></i> Material a utilizar (produtos)</h5></div>
                <div class="widget-content nopadding ui-tabela-rolagem">
                    <table class="table table-bordered">
                        <thead><tr><th>OS</th><th>Produto</th><th>Qtd.</th></tr></thead>
                        <tbody>
                            <?php if (! $os_produtos) echo '<tr><td colspan="3"><div class="ui-vazio"><strong>Sem produtos lançados nas OS.</strong></div></td></tr>'; ?>
                            <?php foreach ($os_produtos as $p): ?>
                                <tr><td>#<?= (int) $p->os_id ?></td><td><?= html_escape($p->nome ?: '—') ?></td><td><?= $fmt($p->quantidade) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($perm('eObras')): ?>
<!-- Modal Vincular OS -->
<div id="modal-vincular-os" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obras/vincularOs') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Vincular Ordem de Serviço</h5></div>
        <div class="modal-body">
            <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
            <div class="span12" style="margin-left:0"><label>Ordem de Serviço *</label>
                <select name="os_id" class="span12" required>
                    <option value="">Selecione a OS...</option>
                    <?php foreach ($os_disponiveis as $d): ?>
                        <option value="<?= (int) $d->idOs ?>">#<?= (int) $d->idOs ?> — <?= html_escape($d->nomeCliente ?: 'sem cliente') ?><?= $d->status ? ' (' . html_escape($d->status) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (! $os_disponiveis): ?><small>Nenhuma OS livre para vincular (todas já estão em algum projeto).</small><?php endif; ?>
            </div>
            <div class="span12" style="margin-left:0"><label>Etapa do cronograma (opcional)</label>
                <select name="etapa_id" class="span12">
                    <option value="">— sem etapa —</option>
                    <?php foreach ($etapas as $e): ?>
                        <option value="<?= (int) $e->idEtapa ?>"><?= html_escape($e->nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button class="button btn btn-success"><span class="button__text2">Vincular</span></button></div>
    </form>
</div>
<?php if (! empty($plan_ativo)):
    $camposOs = function ($com_data) use ($tecnicos_os, $equipes_os) { ob_start(); ?>
        <div class="span6" style="margin-left:0"><label>Técnico</label>
            <select name="tecnico_id" class="span12"><option value="">— nenhum —</option>
                <?php foreach ($tecnicos_os as $t): ?><option value="<?= (int) $t->idUsuarios ?>"><?= html_escape($t->nome) ?></option><?php endforeach; ?>
            </select></div>
        <div class="span6"><label>Equipe</label>
            <select name="equipe_id" class="span12"><option value="">— nenhuma —</option>
                <?php foreach ($equipes_os as $q): ?><option value="<?= (int) $q->idEquipe ?>"><?= html_escape($q->nome) ?></option><?php endforeach; ?>
            </select></div>
        <?php if ($com_data): ?>
        <div class="span6" style="margin-left:0"><label>Data de agendamento</label>
            <input type="text" name="data_agendamento" class="span12 datepicker js-os-data" autocomplete="off" placeholder="dd/mm/aaaa"></div>
        <?php endif; ?>
        <div class="span6" <?= $com_data ? '' : 'style="margin-left:0"' ?>><label>Tipo de vínculo</label>
            <select name="tipo_vinculo" class="span12"><option value="execucao">Execução</option><option value="complementar">Complementar</option><option value="garantia">Garantia</option></select></div>
        <div class="span12" style="margin-left:0"><label><input type="checkbox" name="fatura_no_projeto" value="1" checked style="margin:0 6px 0 0"> Faturar no projeto</label></div>
    <?php return ob_get_clean(); }; ?>
<!-- Modal Gerar OS da etapa -->
<div id="modal-os-etapa" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obras/gerarOsEtapa') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Gerar OS da etapa: <span id="os_etapa_nome"></span></h5></div>
        <div class="modal-body">
            <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
            <input type="hidden" name="etapa_id" id="os_etapa_id">
            <?= $camposOs(true) ?>
            <small>Os itens previstos da etapa são copiados para a OS.</small>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button class="button btn btn-success"><span class="button__text2">Gerar OS</span></button></div>
    </form>
</div>
<!-- Modal Gerar OS em lote -->
<div id="modal-os-lote" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obras/gerarOsLote') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Gerar OS das etapas sem OS</h5></div>
        <div class="modal-body">
            <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
            <div class="span12" style="margin-left:0"><label>Etapas (nenhuma marcada = todas sem OS)</label>
                <?php foreach ($etapasSemOs as $es): ?>
                    <label style="display:block"><input type="checkbox" name="etapas[]" value="<?= (int) $es->idEtapa ?>" style="margin:0 6px 0 0"> <?= html_escape(trim($es->codigo_eap . ' ' . $es->nome)) ?></label>
                <?php endforeach; ?>
                <?php if (! $etapasSemOs): ?><small>Todas as etapas já têm OS.</small><?php endif; ?>
            </div>
            <?= $camposOs(false) ?>
            <div class="span12" style="margin-left:0"><label><input type="checkbox" name="forcar" value="1" style="margin:0 6px 0 0"> Forçar (gerar mesmo se a etapa já tiver OS ou não tiver itens)</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button class="button btn btn-success"><span class="button__text2">Gerar OS</span></button></div>
    </form>
</div>
<!-- Modal Gerar OS por pontos -->
<div id="modal-os-pontos" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obras/gerarOsPontos') ?>" method="post" id="form-os-pontos">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Gerar OS para os pontos marcados</h5></div>
        <div class="modal-body">
            <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
            <div id="os_pontos_ids"></div>
            <p><strong id="os_pontos_qtd">0</strong> ponto(s) sem OS selecionado(s).</p>
            <div class="span12" style="margin-left:0"><label>Etapa *</label>
                <select name="etapa_id" class="span12" required><option value="">Selecione a etapa...</option>
                    <?php foreach ($etapas as $e): ?><option value="<?= (int) $e->idEtapa ?>"><?= html_escape($e->nome) ?></option><?php endforeach; ?>
                </select></div>
            <?= $camposOs(false) ?>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button class="button btn btn-success"><span class="button__text2">Gerar OS</span></button></div>
    </form>
</div>
<script nonce="<?= csp_nonce() ?>">
    $(function () {
        $('.js-itens-toggle').on('click', function (ev) { ev.preventDefault(); $('#' + $(this).data('alvo')).toggle(); });
        $('.js-excluir-item').on('submit', function () { return confirm('Remover este item previsto?'); });
        $('.js-gerar-os').on('click', function () {
            $('#os_etapa_id').val($(this).data('etapa')); $('#os_etapa_nome').text($(this).data('nome'));
            $('#modal-os-etapa .js-os-data').val($(this).data('inicio') || '');
        });
        $('.js-form-item').each(function () {
            var $f = $(this), $busca = $f.find('.js-busca');
            $busca.autocomplete({
                minLength: 2,
                source: function (req, resp) {
                    $.getJSON('<?= site_url('obras/autoCompleteItem') ?>', { term: req.term, tipo: $f.find('.js-tipo').val() }, resp);
                },
                select: function (ev, ui) {
                    $f.find('.js-ref').val(ui.item.id); $f.find('.js-preco').val(ui.item.preco);
                    $busca.val(ui.item.label.split(' | ')[0]);
                    return false;
                }
            });
            $busca.on('input', function () { $f.find('.js-ref').val(''); });
            $f.find('.js-tipo').on('change', function () { $busca.val(''); $f.find('.js-ref').val(''); });
            $f.on('submit', function () {
                if (! $f.find('.js-ref').val()) { alert('Escolha o item na lista de busca.'); return false; }
            });
        });
        $('.js-os-pontos').on('click', function () {
            var $box = $('#os_pontos_ids').empty(), n = 0;
            $('input[name="ponto_ids[]"]:checked').each(function () {
                if (String($(this).data('sem-os')) === '1') { n++; $box.append($('<input type="hidden" name="pontos[]">').val($(this).val())); }
            });
            $('#os_pontos_qtd').text(n);
        });
        $('#form-os-pontos').on('submit', function () {
            if (! $('#os_pontos_ids input').length) { alert('Marque ao menos um ponto sem OS.'); return false; }
        });
    });
</script>
<?php endif; ?>
<form id="formDesvincularOs" action="<?= site_url('obras/desvincularOs') ?>" method="post" style="display:none">
    <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>"><input type="hidden" name="idVinculo" id="del_vinculo">
</form>
<form id="formDesvincularUsuario" action="<?= site_url('obras/desvincularUsuario') ?>" method="post" style="display:none">
    <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>"><input type="hidden" name="idObraUsuario" id="del_usuario">
</form>
<script nonce="<?= csp_nonce() ?>">
    function desvincularOs(id){ if(confirm('Desvincular esta OS do projeto? A OS não é excluída.')){ document.getElementById('del_vinculo').value=id; document.getElementById('formDesvincularOs').submit(); } }
    function desvincularUsuario(id){ if(confirm('Remover o acesso deste usuário ao projeto?')){ document.getElementById('del_usuario').value=id; document.getElementById('formDesvincularUsuario').submit(); } }
</script>
<?php endif; ?>

<?php if (! empty($pode_executar)): ?>
<!-- Modal Registro rápido de execução (reusa RDO) -->
<div id="modal-registro" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obras/registrarExecucao') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5><i class='bx bx-check-circle'></i> Registrar execução — <?= date('d/m/Y') ?></h5></div>
        <div class="modal-body">
            <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
            <div class="span12" style="margin-left:0"><label>O que foi feito hoje? *</label>
                <textarea name="atividades" id="reg_atividades" class="span12" rows="4" placeholder="Descreva a atividade executada..." required></textarea>
            </div>
            <div class="span12" style="margin-left:0"><label>Fotos (opcional)</label>
                <input type="file" accept="image/*" multiple id="regFotos" capture="environment">
                <div id="regFotosHidden"></div><div id="regFotosPrev" class="obra-fotos-prev"></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button class="button btn btn-success"><span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2">Registrar</span></button></div>
    </form>
</div>
<?php endif; ?>

<?php if ($perm('cObraRdo')): ?>
<form id="formExcluirApont" action="<?= site_url('obras/excluirApontamento') ?>" method="post" style="display:none">
    <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>"><input type="hidden" name="idApontamento" id="del_apont">
</form>
<script nonce="<?= csp_nonce() ?>">
    function excluirApont(id){ if(confirm('Remover apontamento?')){ document.getElementById('del_apont').value=id; document.getElementById('formExcluirApont').submit(); } }
</script>
<?php endif; ?>

<?php if ($perm('eObras')): ?>
<!-- Modal Ponto -->
<div id="modal-ponto" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obras/salvarPonto') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Ponto do projeto</h5></div>
        <div class="modal-body">
            <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
            <input type="hidden" name="idPonto" id="pt_id" value="">
            <div class="span6" style="margin-left:0"><label>Tipo *</label>
                <select name="tipo" id="pt_tipo" class="span12" required>
                    <?php foreach ($pontos_tipos as $k => $t): ?><option value="<?= html_escape($k) ?>"><?= html_escape($t['rotulo']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="span6"><label>Identificação *</label><input type="text" class="span12" name="identificacao" id="pt_ident" maxlength="30" placeholder="CAM-07" required></div>
            <div class="span12" style="margin-left:0"><label>Local</label><input type="text" class="span12" name="local" id="pt_local" maxlength="150" placeholder="Ex.: Corredor 2, entrada de funcionários"></div>
            <div class="span6" style="margin-left:0"><label>IP</label><input type="text" class="span12" name="ip" id="pt_ip" maxlength="45" placeholder="192.168.1.107"></div>
            <div class="span3"><label>Canal</label><input type="text" class="span12" name="canal" id="pt_canal" maxlength="10"></div>
            <div class="span3"><label>Porta</label><input type="text" class="span12" name="porta" id="pt_porta" maxlength="30"></div>
            <div class="span6" style="margin-left:0"><label>Modelo</label><input type="text" class="span12" name="modelo" id="pt_modelo" maxlength="80"></div>
            <div class="span6"><label>Série / MAC</label><input type="text" class="span12" name="serie" id="pt_serie" maxlength="60"></div>
            <div class="span12" style="margin-left:0"><label>Observação</label><input type="text" class="span12" name="observacao" id="pt_obs" maxlength="255"></div>
            <div class="span12 pt-modal-fotos" style="margin-left:0">
                <label for="pt_fotos">Fotos do equipamento e da etiqueta (até 6)</label>
                <input type="file" id="pt_fotos" accept="image/*" multiple data-foto-compacta>
                <div class="pt-foto-preview" data-foto-preview></div>
                <small>As fotos são reduzidas no seu navegador antes de enviar. Não fotografe senhas.</small>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button class="button btn btn-success"><span class="button__text2">Salvar</span></button></div>
    </form>
</div>

<!-- Modal Gerar em lote -->
<div id="modal-lote" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obras/gerarPontos') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Gerar pontos em lote</h5></div>
        <div class="modal-body">
            <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
            <div class="span12" style="margin-left:0"><label>Tipo *</label>
                <select name="tipo" id="lt_tipo" class="span12" required>
                    <?php foreach ($pontos_tipos as $k => $t): ?><option value="<?= html_escape($k) ?>" data-prefixo="<?= html_escape($t['prefixo']) ?>"><?= html_escape($t['rotulo']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="span4" style="margin-left:0"><label>Prefixo</label><input type="text" class="span12" name="prefixo" id="lt_prefixo" maxlength="20"></div>
            <div class="span4"><label>Número inicial</label><input type="number" min="1" class="span12" name="inicio" value="1"></div>
            <div class="span4"><label>Quantidade (1 a 500)</label><input type="number" min="1" max="500" class="span12" name="quantidade" value="16" required></div>
            <div class="span12" style="margin-left:0"><label>Local (opcional, igual para todos)</label><input type="text" class="span12" name="local" maxlength="150"></div>
            <div class="span12" style="margin-left:0"><small>Ex.: tipo Câmera, prefixo CAM, início 1, quantidade 16 cria CAM-01 a CAM-16. Identificações que já existem são ignoradas.</small></div>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button class="button btn btn-success"><span class="button__text2">Gerar</span></button></div>
    </form>
</div>
<form id="formExcluirPonto" action="<?= site_url('obras/excluirPonto') ?>" method="post" style="display:none">
    <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>"><input type="hidden" name="idPonto" id="del_ponto">
</form>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/foto-compacta.js') ?>"></script>
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
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Registro de execução</h5></div>
        <div class="modal-body">
            <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
            <div class="span4" style="margin-left:0"><label>Data</label><input type="text" class="span12 datepicker" name="data" value="<?= date('d/m/Y') ?>"></div>
            <div class="span4"><label>Técnicos no local</label><input type="number" class="span12" name="efetivo_total" value="0"></div>
            <div class="span4"><label>Condição</label><select name="condicao" class="span12"><option value="praticavel">Normal</option><option value="parcial">Impedimento parcial</option><option value="impraticavel">Sem acesso</option></select></div>
            <div class="span12" style="margin-left:0"><label>Atividades executadas</label><textarea class="span12" name="atividades" rows="3"></textarea></div>
            <div class="span6" style="margin-left:0"><label>Ocorrências</label><textarea class="span12" name="ocorrencias" rows="2"></textarea></div>
            <div class="span6"><label>Observações</label><textarea class="span12" name="observacoes" rows="2"></textarea></div>
            <div class="span12" style="margin-left:0"><label>Fotos</label><input type="file" accept="image/*" multiple id="rdoFotos" capture="environment"><div id="rdoFotosHidden"></div><div id="rdoFotosPrev" class="obra-fotos-prev"></div></div>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button class="button btn btn-success"><span class="button__text2">Registrar</span></button></div>
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
<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script nonce="<?= csp_nonce() ?>">
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

        // fotos (RDO e registro rápido) -> base64 em hidden
        function fotosParaBase64(input, alvoHidden, alvoPrev) {
            $(input).on('change', function () {
                $(alvoHidden).empty(); $(alvoPrev).empty();
                Array.prototype.forEach.call(this.files, function (file) {
                    var reader = new FileReader();
                    reader.onload = function (ev) {
                        $(alvoHidden).append('<input type="hidden" name="fotos[]" value="' + ev.target.result + '">');
                        $(alvoPrev).append('<img src="' + ev.target.result + '">');
                    };
                    reader.readAsDataURL(file);
                });
            });
        }
        fotosParaBase64('#rdoFotos', '#rdoFotosHidden', '#rdoFotosPrev');
        fotosParaBase64('#regFotos', '#regFotosHidden', '#regFotosPrev');
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

    function limparFotosPonto() {
        $('#modal-ponto input[type=file][data-foto-compacta]').val('');
        $('#modal-ponto [data-foto-preview]').empty();
        $('#modal-ponto input[name="fotos[]"]').remove();
    }
    function novoPonto() {
        $('#pt_id,#pt_ident,#pt_local,#pt_ip,#pt_canal,#pt_porta,#pt_modelo,#pt_serie,#pt_obs').val('');
        limparFotosPonto();
    }
    function editarPonto(p) {
        $('#pt_id').val(p.idPonto); $('#pt_tipo').val(p.tipo); $('#pt_ident').val(p.identificacao);
        $('#pt_local').val(p.local || ''); $('#pt_ip').val(p.ip || ''); $('#pt_canal').val(p.canal || '');
        $('#pt_porta').val(p.porta || ''); $('#pt_modelo').val(p.modelo || ''); $('#pt_serie').val(p.serie || '');
        $('#pt_obs').val(p.observacao || '');
        limparFotosPonto();
        $('#modal-ponto').modal('show');
    }
    function excluirPonto(id) {
        if (confirm('Excluir este ponto e o histórico dele?')) { $('#del_ponto').val(id); $('#formExcluirPonto').submit(); }
    }
    $(function () {
        $('#lt_tipo').on('change', function () { $('#lt_prefixo').val($(this).find(':selected').data('prefixo') || ''); }).trigger('change');

        // Pontos: marcar todos para repasse
        $('#pt-selecionar-todos').on('change', function () {
            $('input[name="ponto_ids[]"]').prop('checked', this.checked);
        });
    });
</script>
