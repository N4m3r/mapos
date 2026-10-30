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
$coresStatus = ['planejamento' => 'info', 'em_execucao' => 'sucesso', 'paralisada' => 'alerta', 'concluida' => 'info', 'entregue' => 'sucesso', 'cancelada' => 'perigo'];
$coresOs = ['Aberto' => 'info', 'Orçamento' => 'neutro', 'Em Andamento' => 'alerta', 'Aguardando peças' => 'alerta', 'Finalizado' => 'sucesso', 'Faturado' => 'sucesso', 'Cancelado' => 'perigo'];

$ptEdita = $perm('eObras');
$ptMuda = $ptEdita || ! empty($pode_executar);
$planOk = ! empty($plan_ativo) && $ptEdita;
$verValor = $perm('vObraCusto');
$ptFiltrado = ($pontos_filtro['tipo'] !== '' || $pontos_filtro['status'] !== ''
    || ($pontos_filtro['aguardando'] ?? '') !== '' || ($pontos_filtro['resp'] ?? '') !== '');
$ptTestados = (int) ($pontos_resumo['por_status']['testado'] ?? 0);

$etapasSemOs = [];
foreach ($etapas as $_e) {
    if (empty($osPorEtapa[$_e->idEtapa])) {
        $etapasSemOs[] = $_e;
    }
}

$endereco = trim(trim((string) $obra->logradouro . ' ' . (string) $obra->numero) . ($obra->cidade ? ' - ' . $obra->cidade . ($obra->uf ? '/' . $obra->uf : '') : ''), ' ,-/');

// Alvo do guia: aba (#aba-x) ou modal (#modal-x). Devolve o link pronto ou '' se o usuario nao pode usar.
$linkAlvo = function ($alvo, $rotulo, $classe, $icone = '') use ($ptEdita, $planOk, $pode_executar) {
    if ($alvo === '' || $rotulo === '') {
        return '';
    }
    $conteudo = ($icone ? '<span class="button__icon"><i class="bx ' . $icone . '"></i></span>' : '') . '<span class="button__text2">' . html_escape($rotulo) . '</span>';
    if (strpos($alvo, '#aba-') === 0) {
        $aba = substr($alvo, 5);
        return '<a href="#' . html_escape($aba) . '" class="button btn ' . $classe . ' js-ir-aba" data-aba="' . html_escape($aba) . '">' . $conteudo . '</a>';
    }
    $ok = ['#modal-lote' => $ptEdita, '#modal-os-lote' => $planOk, '#modal-registro' => ! empty($pode_executar)];
    if (empty($ok[$alvo])) {
        return '';
    }
    return '<a href="' . html_escape($alvo) . '" data-toggle="modal" class="button btn ' . $classe . '">' . $conteudo . '</a>';
};

$fasePasso = (int) $guia['fase'];
$trilho = [1 => 'Montar', 2 => 'Planejar', 3 => 'Executar', 4 => 'Entregar'];
$projetoEncerrado = in_array($obra->status, ['concluida', 'entregue'], true);

// Sugestao de concluir etapa (ADR-015 fase 4): OS da etapa todas terminadas, mas ainda
// há ponto sem teste. Enquanto existir, ela vira o proximo passo da faixa.
$etapaSugeridaDestaque = $etapasSugestoesConcluir ? reset($etapasSugestoesConcluir) : null;
if ($etapaSugeridaDestaque && ! $projetoEncerrado) {
    $guia['texto'] = 'Todas as OS da etapa "' . $etapaSugeridaDestaque['etapa_nome'] . '" terminaram, mas '
        . (int) $etapaSugeridaDestaque['pontos_pendentes'] . ' ponto(s) ainda não foram testados.';
    $guia['alvo'] = '#aba-planejamento';
    $guia['botao_rotulo'] = 'Ver etapa';
    // ja houve OS terminada: o projeto esta em execucao, nao mais planejando
    $fasePasso = max($fasePasso, 3);
}

// Acao principal do topo: registrar execucao quando em execucao e pode executar; senao o proximo passo.
if ($obra->status === 'em_execucao' && ! empty($pode_executar)) {
    $acaoTopo = $linkAlvo('#modal-registro', 'Registrar execução', 'btn-primary pj-primaria', 'bx-check-circle');
} else {
    $acaoTopo = $linkAlvo($guia['alvo'], $guia['botao_rotulo'], 'btn-primary pj-primaria');
}
$botaoPasso = $linkAlvo($guia['alvo'], $guia['botao_rotulo'], 'pj-secundario');
?>
<link rel="stylesheet" href="<?= base_url('assets/css/projeto-pagina.css') ?>?v=<?= asset_versao('assets/css/projeto-pagina.css', '1') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/obras.css') ?>">

<div class="pj-pagina">

<nav class="pj-migalhas" aria-label="Você está em">
    <a href="<?= site_url('obras/painel') ?>">Painel</a>
    <a href="<?= site_url('obras/gerenciar') ?>">Projetos</a>
    <span aria-current="page"><?= html_escape($obra->nome) ?></span>
</nav>

<header class="pj-topo">
    <div class="pj-topo-titulo">
        <h1 class="pj-nome"><?= html_escape($obra->nome) ?><?= $obra->codigo ? ' <small>#' . html_escape($obra->codigo) . '</small>' : '' ?></h1>
        <span class="ui-selo <?= $coresStatus[$obra->status] ?? '' ?>"><?= html_escape($statusLabels[$obra->status] ?? $obra->status) ?></span>
    </div>
    <div class="pj-topo-acoes">
        <?= $acaoTopo ?>
        <details class="pj-mais">
            <summary class="button btn pj-secundario" aria-label="Mais ações do projeto"><span class="button__icon"><i class="bx bx-dots-horizontal-rounded"></i></span><span class="button__text2">Mais</span></summary>
            <ul class="pj-mais-menu">
                <?php if ($perm('eObras')): ?><li><a href="<?= site_url('obras/editar/' . $obra->idObra) ?>"><i class="bx bx-edit"></i> Editar projeto</a></li><?php endif; ?>
                <li><a href="<?= site_url('obras/asbuilt/' . (int) $obra->idObra) ?>" target="_blank" rel="noopener"><i class="bx bx-printer"></i> Documentação final (as-built)</a></li>
                <?php if ($perm('rObraMaterial')): ?><li><a href="<?= site_url('obramaterial/receber/' . $obra->idObra) ?>"><i class="bx bx-down-arrow-circle"></i> Receber material</a></li><?php endif; ?>
                <?php if ($perm('sObraMaterial')): ?><li><a href="<?= site_url('obramaterial/entregar/' . $obra->idObra) ?>"><i class="bx bx-up-arrow-circle"></i> Entregar material</a></li><?php endif; ?>
                <?php if ($ptEdita): ?><li><a href="#modal-vincular-os" data-toggle="modal"><i class="bx bx-link"></i> Vincular OS existente</a></li><?php endif; ?>
                <li><a href="<?= site_url('obras/gerenciar') ?>"><i class="bx bx-undo"></i> Voltar para Projetos</a></li>
                <?php if ($perm('dObras')): ?>
                    <li class="pj-mais-perigo">
                        <form action="<?= site_url('obras/excluir') ?>" method="post" data-confirmar="Excluir este projeto? Etapas, pontos e registros dele também serão removidos.">
                            <input type="hidden" name="idObra" value="<?= (int) $obra->idObra ?>">
                            <button type="submit"><i class="bx bx-trash-alt"></i> Excluir projeto</button>
                        </form>
                    </li>
                <?php endif; ?>
            </ul>
        </details>
    </div>
</header>

<p class="pj-meta">
    <span><i class="bx bx-user"></i> <?= html_escape($obra->nomeCliente ?: 'Sem cliente') ?></span>
    <span><i class="bx bx-wrench"></i> <?= html_escape($obra->responsavel ?: 'Sem técnico responsável') ?></span>
    <?php if ($endereco !== ''): ?><span><i class="bx bx-map"></i> <?= html_escape($endereco) ?></span><?php endif; ?>
    <span class="pj-prazo <?= html_escape($prazo['classe']) ?>"><i class="bx bx-calendar"></i> <?= html_escape($prazo['texto']) ?></span>
</p>

<ol class="pj-trilho" aria-label="Fases do projeto">
    <?php foreach ($trilho as $n => $rot):
        $estadoFase = $projetoEncerrado ? 'feito' : ($fasePasso === 0 ? 'futuro' : ($n < $fasePasso ? 'feito' : ($n === $fasePasso ? 'atual' : 'futuro'))); ?>
        <li class="pj-fase <?= $estadoFase ?>"<?= $estadoFase === 'atual' ? ' aria-current="step"' : '' ?>>
            <span class="pj-fase-n"><?= $estadoFase === 'feito' ? '<i class="bx bx-check"></i>' : (int) $n ?></span>
            <span class="pj-fase-rot"><?= html_escape($rot) ?></span>
        </li>
    <?php endforeach; ?>
</ol>

<div class="pj-passo <?= $fasePasso === 0 || $projetoEncerrado ? 'quieto' : '' ?>">
    <div class="pj-passo-texto">
        <strong><?= $projetoEncerrado || $fasePasso === 0 ? 'Situação' : 'Próximo passo' ?></strong>
        <span><?= html_escape($guia['texto']) ?></span>
    </div>
    <?= $botaoPasso ?>
</div>

<ul class="nav nav-tabs obra-tabs pj-abas" id="obraTabs">
    <li class="active"><a href="#tab-resumo" data-tab="resumo">Resumo</a></li>
    <li><a href="#tab-planejamento" data-tab="planejamento">Planejamento <span class="pj-aba-n"><?= count($etapas) ?></span></a></li>
    <li><a href="#tab-pontos" data-tab="pontos">Pontos<?php if ($pontos_resumo['total'] > 0): ?> <span class="pj-aba-n"><?= $ptTestados ?>/<?= (int) $pontos_resumo['total'] ?></span><?php endif; ?></a></li>
    <li><a href="#tab-execucao" data-tab="execucao">Execução</a></li>
    <li><a href="#tab-material" data-tab="material">Material</a></li>
    <li><a href="#tab-financeiro" data-tab="financeiro">Financeiro</a></li>
</ul>

<div class="obra-tab-content">

    <!-- RESUMO -->
    <div class="obra-pane" id="tab-resumo">
        <div class="pj-grade">
            <div class="pj-coluna">

                <section class="pj-cartao" aria-labelledby="pj-t-pontos">
                    <div class="pj-cartao-cab">
                        <h2 id="pj-t-pontos">Painel de pontos</h2>
                        <?php if ($pontos_resumo['total'] > 0): ?><span class="pj-cartao-nota"><?= $ptTestados ?> de <?= (int) $pontos_resumo['total'] ?> testados</span><?php endif; ?>
                    </div>
                    <?php if ($pontos_resumo['total'] > 0):
                        $porTipoPt = [];
                        foreach ($pontos_todos as $_pt) {
                            $_k = isset($pontos_tipos[$_pt->tipo]) ? $_pt->tipo : 'outro';
                            $porTipoPt[$_k][] = $_pt;
                        } ?>
                        <div class="pj-rack">
                            <?php foreach ($pontos_resumo['por_tipo'] as $tk => $tr): ?>
                                <div class="pj-rack-faixa">
                                    <div class="pj-rack-tipo">
                                        <i class="bx <?= html_escape($pontos_tipos[$tk]['icone'] ?? 'bx-chip') ?>"></i>
                                        <span class="pj-rack-nome"><?= html_escape($tr['rotulo']) ?></span>
                                        <span class="pj-rack-cont"><?= (int) $tr['testados'] ?>/<?= (int) $tr['total'] ?> testados</span>
                                    </div>
                                    <div class="pj-rack-portas">
                                        <?php foreach (($porTipoPt[$tk] ?? []) as $pt):
                                            $stR = $pontos_status[$pt->status]['rotulo'] ?? $pt->status;
                                            $dica = $pt->identificacao . ' - ' . $stR . ($pt->local ? ' - ' . $pt->local : '');
                                            if ($ptEdita) {
                                                $argsPt = [[
                                                    'idPonto' => (int) $pt->idPonto, 'tipo' => $pt->tipo, 'identificacao' => $pt->identificacao,
                                                    'local' => $pt->local, 'ip' => $pt->ip, 'canal' => $pt->canal, 'porta' => $pt->porta,
                                                    'modelo' => $pt->modelo ?? '', 'serie' => $pt->serie ?? '', 'observacao' => $pt->observacao ?? '',
                                                ]];
                                            } ?>
                                            <?php if ($ptEdita): ?>
                                                <a href="#" class="pj-porta st-<?= html_escape($pt->status) ?>" title="<?= html_escape($dica) ?>" aria-label="<?= html_escape($dica) ?>" data-chamar="editarPonto" data-args="<?= html_escape(json_encode($argsPt)) ?>" data-cancelar><?= html_escape($pt->identificacao) ?></a>
                                            <?php else: ?>
                                                <a href="#pt-linha-<?= (int) $pt->idPonto ?>" class="pj-porta st-<?= html_escape($pt->status) ?> js-ir-ponto" data-ponto="<?= (int) $pt->idPonto ?>" title="<?= html_escape($dica) ?>" aria-label="<?= html_escape($dica) ?>"><?= html_escape($pt->identificacao) ?></a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <ul class="pj-legenda" aria-label="Legenda das cores">
                            <?php foreach ($pontos_status as $k => $st): ?>
                                <li><span class="pj-legenda-cor st-<?= html_escape($k) ?>"></span><?= html_escape($st['rotulo']) ?> <b><?= (int) ($pontos_resumo['por_status'][$k] ?? 0) ?></b></li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="pj-nota">
                            <?php if ((float) $pontos_resumo['cabo_m'] > 0): ?>Cabo passado: <b><?= $fmt($pontos_resumo['cabo_m']) ?> m</b>. <?php endif; ?>
                            Clique em um ponto para <?= $ptEdita ? 'editar' : 'ver na lista' ?>. O status é atualizado na aba <a href="#pontos" class="js-ir-aba" data-aba="pontos">Pontos</a>.
                        </p>
                    <?php else: ?>
                        <div class="ui-vazio">
                            <i class="bx bx-cctv"></i>
                            <strong>Nenhum ponto ainda.</strong>
                            Cadastre câmeras, leitores e tomadas para acompanhar cada um até o teste.
                            <?php if ($ptEdita): ?><div class="pj-vazio-acao"><a href="#modal-lote" data-toggle="modal" class="button btn btn-primary"><span class="button__icon"><i class="bx bx-layer-plus"></i></span><span class="button__text2">Adicionar pontos</span></a></div><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="pj-cartao" aria-labelledby="pj-t-prog">
                    <div class="pj-cartao-cab"><h2 id="pj-t-prog">Progresso</h2></div>
                    <div class="pj-prog">
                        <span class="pj-prog-num"><?= $fmt($obra->percentual_concluido) ?>%</span>
                        <div class="ui-barra" role="progressbar" aria-valuenow="<?= (int) round($obra->percentual_concluido) ?>" aria-valuemin="0" aria-valuemax="100"><span style="width:<?= max(0, min(100, (float) $obra->percentual_concluido)) ?>%"></span></div>
                    </div>
                    <p class="pj-nota">
                        Média das etapas pelo peso<?php if ($pontos_resumo['total'] > 0): ?>; pontos: <?= $ptTestados ?> de <?= (int) $pontos_resumo['total'] ?> testados<?php endif; ?>.
                    </p>
                    <dl class="pj-dl">
                        <dt>Início previsto</dt><dd><?= $data_br($obra->data_inicio_prevista) ?></dd>
                        <dt>Fim previsto</dt><dd><?= $data_br($obra->data_fim_prevista) ?> <span class="ui-selo <?= html_escape($prazo['classe']) ?>"><?= html_escape($prazo['texto']) ?></span></dd>
                        <dt>Início real</dt><dd><?= $data_br($obra->data_inicio_real) ?></dd>
                        <dt>Fim real</dt><dd><?= $data_br($obra->data_fim_real) ?></dd>
                    </dl>
                </section>

                <section class="pj-cartao" aria-labelledby="pj-t-contr">
                    <div class="pj-cartao-cab"><h2 id="pj-t-contr">Dados do contrato</h2></div>
                    <dl class="pj-dl">
                        <dt>Contrato nº</dt><dd><?= html_escape($obra->contrato_numero ?: '—') ?></dd>
                        <dt>Tipo de projeto</dt><dd><?= html_escape($obra->tipo_obra ?: '—') ?></dd>
                        <dt>Cliente</dt><dd><?= html_escape($obra->nomeCliente ?: '—') ?></dd>
                        <dt>Endereço</dt><dd><?= html_escape($endereco ?: '—') ?></dd>
                        <dt>Observações</dt><dd><?= $obra->observacoes ? nl2br(html_escape($obra->observacoes)) : '—' ?></dd>
                    </dl>
                </section>
            </div>

            <div class="pj-coluna">
                <!-- Equipe: tecnicos com acesso e equipes alocadas -->
                <section class="pj-cartao" id="equipe" aria-labelledby="pj-t-equipe">
                    <div class="pj-cartao-cab">
                        <h2 id="pj-t-equipe">Equipe</h2>
                        <?php if ($perm('cObraEquipe')): ?><a href="<?= site_url('obraequipe') ?>" class="pj-link">Gerenciar equipes</a><?php endif; ?>
                    </div>
                    <p class="pj-nota">Só quem está aqui (e o técnico responsável) pode registrar a execução.</p>
                    <ul class="pj-lista">
                        <?php if (! $usuarios_projeto): ?><li class="pj-lista-vazio">Nenhum técnico vinculado.</li><?php endif; ?>
                        <?php foreach ($usuarios_projeto as $u): ?>
                            <li>
                                <span class="pj-lista-nome"><?= html_escape($u->nome ?: '—') ?><small><?= html_escape($u->email ?: '') ?></small></span>
                                <?php if ($ptEdita): ?><a href="#" class="btn-nwe4" title="Desvincular" aria-label="Desvincular <?= html_escape($u->nome) ?>" data-chamar="desvincularUsuario" data-args="[<?= (int) $u->idObraUsuario ?>]" data-cancelar><i class="bx bx-user-x"></i></a><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($ptEdita): ?>
                        <form action="<?= site_url('obras/vincularUsuario') ?>" method="post" class="pj-vincular">
                            <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
                            <label for="pj-usuario-id" class="pj-sr">Vincular técnico</label>
                            <select name="usuario_id" id="pj-usuario-id" required>
                                <option value="">Escolha um técnico</option>
                                <?php foreach ($usuarios_disponiveis as $ud): ?>
                                    <option value="<?= (int) $ud->idUsuarios ?>"><?= html_escape($ud->nome) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="button btn btn-mini"><span class="button__icon"><i class="bx bx-plus"></i></span><span class="button__text2">Vincular</span></button>
                        </form>
                    <?php endif; ?>
                    <?php if ($equipes): ?>
                        <h3 class="pj-sub">Equipes alocadas</h3>
                        <ul class="pj-lista">
                            <?php foreach ($equipes as $q): ?>
                                <li><span class="pj-lista-nome"><?= html_escape($q->nome) ?><small>Encarregado: <?= html_escape($q->encarregado ?: '—') ?><?= $q->data_inicio ? ', desde ' . $data_br($q->data_inicio) : '' ?></small></span><span class="ui-selo <?= $q->ativo ? 'sucesso' : 'neutro' ?>"><?= $q->ativo ? 'Ativa' : 'Inativa' ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <section class="pj-cartao" aria-labelledby="pj-t-os">
                    <div class="pj-cartao-cab">
                        <h2 id="pj-t-os">OS recentes</h2>
                        <?php if ($os_vinculadas): ?><a href="#execucao" class="pj-link js-ir-aba" data-aba="execucao">Ver todas (<?= count($os_vinculadas) ?>)</a><?php endif; ?>
                    </div>
                    <?php if (! $os_vinculadas): ?>
                        <div class="ui-vazio pj-vazio-p"><strong>Nenhuma OS ainda.</strong>
                            <?php if ($planOk && $etapas): ?>Gere uma OS para cada etapa.<div class="pj-vazio-acao"><a href="#modal-os-lote" data-toggle="modal" class="button btn btn-mini"><span class="button__icon"><i class="bx bx-receipt"></i></span><span class="button__text2">Gerar OS das etapas</span></a></div><?php endif; ?>
                        </div>
                    <?php else: ?>
                        <ul class="pj-lista">
                            <?php foreach (array_slice(array_reverse($os_vinculadas), 0, 5) as $o): ?>
                                <li>
                                    <span class="pj-lista-nome"><a href="<?= site_url('os/visualizar/' . (int) $o->idOs) ?>">OS #<?= (int) $o->idOs ?></a><small><?= html_escape($o->tecnico ?: 'Sem técnico') ?><?= $o->etapa_nome ? ', ' . html_escape($o->etapa_nome) : '' ?></small></span>
                                    <span class="ui-selo <?= $coresOs[$o->status] ?? 'neutro' ?>"><?= html_escape($o->status ?: '—') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>

    <!-- PONTOS -->
    <div class="obra-pane" id="tab-pontos" style="display:none">
        <div class="pj-secao-cab">
            <h2>Pontos <?php if ($pontos_resumo['total'] > 0): ?><small><?= (int) $pontos_resumo['total'] ?> no total, <?= $ptTestados ?> testados</small><?php endif; ?></h2>
            <div class="pj-secao-acoes">
                <?php if ($ptEdita): ?>
                    <a href="#modal-ponto" data-toggle="modal" class="button btn btn-mini" data-chamar="novoPonto"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Novo ponto</span></a>
                    <a href="#modal-lote" data-toggle="modal" class="button btn btn-mini"><span class="button__icon"><i class='bx bx-layer-plus'></i></span><span class="button__text2">Gerar em lote</span></a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (! empty($pontos_filtro_etapa)): ?>
            <div class="ui-dica">
                <i class="bx bx-filter-alt"></i>
                <div>
                    Mostrando só os pontos pendentes da etapa &quot;<?= html_escape($pontos_filtro_etapa['nome']) ?>&quot;.
                    <a href="<?= site_url('obras/visualizar/' . (int) $obra->idObra) ?>#pontos" class="pj-link">Ver todos os pontos</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($pontos_resumo['total'] > 0): ?>
        <form action="<?= site_url('obras/visualizar/' . (int) $obra->idObra) ?>#pontos" method="get" class="pj-filtros">
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
            <button class="button btn btn-mini"><span class="button__icon"><i class='bx bx-filter-alt'></i></span><span class="button__text2">Filtrar</span></button>
            <?php if ($ptFiltrado): ?><a href="<?= site_url('obras/visualizar/' . (int) $obra->idObra) ?>#pontos" class="pj-link">Limpar filtros</a><?php endif; ?>
        </form>
        <?php endif; ?>

        <?php if ($ptEdita): ?>
            <!-- Barra de selecao: so aparece com pontos marcados -->
            <div class="pj-selecao" id="pj-selecao" hidden>
                <strong class="pj-selecao-qtd"><span id="pj-sel-qtd">0</span> selecionado(s)</strong>
                <form id="form-repasse-lote" action="<?= site_url('obras/repassarPontos') ?>" method="post" class="pj-selecao-form">
                    <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
                    <select name="destinatario" aria-label="Quem recebe os pontos selecionados">
                        <option value="">Equipe (livre)</option>
                        <?php foreach ($executores as $ex): ?>
                            <option value="<?= (int) $ex['idUsuarios'] ?>"><?= html_escape($ex['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="instrucao" maxlength="255" placeholder="Instrução (não escreva senhas)" aria-label="Instrução para quem recebe">
                    <button class="button btn btn-mini" title="Passar os pontos marcados para outro técnico"><span class="button__icon"><i class='bx bx-transfer-alt'></i></span><span class="button__text2">Passar para outro técnico</span></button>
                </form>
                <?php if (! empty($plan_ativo) && $etapas): ?>
                    <a href="#modal-os-pontos" data-toggle="modal" class="button btn btn-mini btn-primary js-os-pontos" title="Gerar uma OS com os pontos marcados que ainda não têm OS"><span class="button__icon"><i class='bx bx-receipt'></i></span><span class="button__text2">Gerar OS</span></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="pj-tabela-caixa ui-tabela-rolagem">
            <table class="table pt-tabela pj-tab-cartao">
                <thead><tr>
                    <?php if ($ptEdita): ?><th class="pt-col-sel"><input type="checkbox" id="pt-selecionar-todos" title="Selecionar todos" aria-label="Selecionar todos os pontos"></th><?php endif; ?>
                    <th class="pt-col-foto">Foto</th><th>Ponto</th><th>Local</th><th>Rede</th><th>Cabo</th><th>Status</th><th>Com quem</th><?php if ($ptMuda): ?><th class="pj-col-atualizar">Atualizar status</th><?php endif; ?>
                </tr></thead>
                <tbody>
                    <?php if (! $pontos): ?>
                        <tr class="pj-linha-vazia"><td colspan="<?= 8 + ($ptEdita ? 1 : 0) + ($ptMuda ? 1 : 0) ?>">
                            <?php if ($ptFiltrado): ?>
                                <div class="ui-vazio"><i class="bx bx-filter-alt"></i><strong>Nenhum ponto com esse filtro.</strong>Tire um filtro para ver mais pontos.</div>
                            <?php else: ?>
                                <div class="ui-vazio"><i class="bx bx-cctv"></i><strong>Nenhum ponto ainda.</strong>Gere os pontos em lote, por exemplo 16 câmeras de CAM-01 a CAM-16. Depois cada um avança até o teste e o progresso do projeto é calculado sozinho.
                                    <?php if ($ptEdita): ?><div class="pj-vazio-acao"><a href="#modal-lote" data-toggle="modal" class="button btn btn-primary"><span class="button__icon"><i class="bx bx-layer-plus"></i></span><span class="button__text2">Adicionar pontos</span></a></div><?php endif; ?></div>
                            <?php endif; ?>
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($pontos as $pt):
                        $st = $pontos_status[$pt->status] ?? null;
                        $fotosPt = array_values($fotos_pontos[$pt->idPonto] ?? []);
                        $nFotosPt = count($fotosPt);
                        $rotAguardando = Obra_pontos_model::rotuloAguardando($pt->aguardando ?? null);
                        $instrucaoPt = (string) ($pt->instrucao ?? '');
                        ?>
                        <tr id="pt-linha-<?= (int) $pt->idPonto ?>">
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
                            <td data-label="Ponto" class="pj-col-ponto"><strong><?= html_escape($pt->identificacao) ?></strong><small><?= html_escape($pontos_tipos[$pt->tipo]['rotulo'] ?? $pt->tipo) ?></small></td>
                            <td data-label="Local"><?= html_escape($pt->local ?: '—') ?></td>
                            <td data-label="Rede" class="pj-col-rede">
                                <?php if ($pt->ip || $pt->canal || $pt->porta): ?>
                                    <?php if ($pt->ip): ?><span>IP <?= html_escape($pt->ip) ?></span><?php endif; ?>
                                    <?php if ($pt->canal): ?><span>Canal <?= html_escape($pt->canal) ?></span><?php endif; ?>
                                    <?php if ($pt->porta): ?><span>Porta <?= html_escape($pt->porta) ?></span><?php endif; ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td data-label="Cabo"><?= (float) $pt->metragem_cabo > 0 ? $fmt($pt->metragem_cabo) . ' m' : '—' ?></td>
                            <td data-label="Status"><span class="ui-selo <?= html_escape($st['classe'] ?? '') ?>"><?= html_escape($st['rotulo'] ?? $pt->status) ?></span>
                                <small class="pj-atualizado"><?= ! empty($pt->atualizado_em) ? 'em ' . date('d/m/Y H:i', strtotime($pt->atualizado_em)) : '' ?></small></td>
                            <td data-label="Com quem" class="pt-col-com">
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
                            <?php if ($ptMuda): ?>
                                <td class="pt-acoes pj-col-atualizar" data-label="Atualizar status">
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
                                        <span class="pj-icones">
                                        <a href="#" class="btn-nwe3" title="Editar" aria-label="Editar <?= html_escape($pt->identificacao) ?>" data-chamar="editarPonto" data-args="<?= html_escape(json_encode([$pt])) ?>" data-cancelar><i class="bx bx-edit bx-xs"></i></a>
                                        <a href="#" class="btn-nwe4" title="Excluir" aria-label="Excluir <?= html_escape($pt->identificacao) ?>" data-chamar="excluirPonto" data-args="[<?= (int) $pt->idPonto ?>]" data-cancelar><i class="bx bx-trash-alt bx-xs"></i></a>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PLANEJAMENTO -->
    <div class="obra-pane" id="tab-planejamento" style="display:none">
        <div class="pj-secao-cab">
            <h2>Etapas <?php if ($etapas): ?><small><?= count($etapas) ?> no total<?= $etapasSemOs && ! empty($plan_ativo) ? ', ' . count($etapasSemOs) . ' sem OS' : '' ?></small><?php endif; ?></h2>
            <div class="pj-secao-acoes">
                <?php if ($perm('eObraCronograma')): ?>
                    <a href="#modal-etapa" data-toggle="modal" class="button btn btn-mini" data-chamar="novaEtapa"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Nova etapa</span></a>
                <?php endif; ?>
                <?php if ($perm('eObras') && trim((string) $obra->tipo_obra) !== ''): ?>
                    <form action="<?= site_url('obras/aplicarEtapasPadrao') ?>" method="post" class="pj-form-linha">
                        <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
                        <button class="button btn btn-mini" data-confirmar="Criar as etapas padrão para &quot;<?= html_escape($obra->tipo_obra) ?>&quot;? As que já existem não são repetidas."><span class="button__icon"><i class='bx bx-list-plus'></i></span><span class="button__text2">Criar etapas padrão</span></button>
                    </form>
                <?php endif; ?>
                <?php if ($planOk && $etapas): ?>
                    <a href="#modal-os-lote" data-toggle="modal" class="button btn btn-mini"><span class="button__icon"><i class='bx bx-receipt'></i></span><span class="button__text2">Gerar OS das etapas sem OS</span></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="pj-tabela-caixa ui-tabela-rolagem">
            <table class="table pj-tab-etapas pj-tab-cartao">
                <thead><tr><th>Nº</th><th>Etapa</th><th>Peso</th><th>Valor previsto</th><?php if (! empty($plan_ativo)): ?><th>Materiais e serviços</th><th>OS</th><?php endif; ?><th>Datas</th><th>Progresso</th><?php if ($perm('eObraCronograma')): ?><th></th><?php endif; ?></tr></thead>
                <tbody>
                    <?php if (! $etapas): ?>
                        <tr class="pj-linha-vazia"><td colspan="10"><div class="ui-vazio"><i class="bx bx-list-ul"></i><strong>Nenhuma etapa ainda.</strong>As etapas dividem o trabalho e dão o peso de cada parte no progresso.
                            <div class="pj-vazio-acao">
                                <?php if ($perm('eObras') && trim((string) $obra->tipo_obra) !== ''): ?>
                                    <form action="<?= site_url('obras/aplicarEtapasPadrao') ?>" method="post" class="pj-form-linha"><input type="hidden" name="obra_id" value="<?= $obra->idObra ?>"><button class="button btn btn-primary"><span class="button__icon"><i class='bx bx-list-plus'></i></span><span class="button__text2">Criar etapas padrão</span></button></form>
                                <?php elseif ($perm('eObraCronograma')): ?>
                                    <a href="#modal-etapa" data-toggle="modal" class="button btn btn-primary" data-chamar="novaEtapa"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Nova etapa</span></a>
                                <?php endif; ?>
                            </div></div></td></tr>
                    <?php endif; ?>
                    <?php foreach ($etapas as $e):
                        $osEt = $osPorEtapa[$e->idEtapa] ?? [];
                        $itEt = $itensPorEtapa[$e->idEtapa] ?? [];
                        $iniOs = $data_br($e->data_inicio_prevista) === '—' ? '' : $data_br($e->data_inicio_prevista); ?>
                        <tr>
                            <td data-label="Nº" class="pj-col-n"><?= html_escape($e->codigo_eap) ?></td>
                            <td data-label="Etapa"><strong><?= html_escape($e->nome) ?></strong></td>
                            <td data-label="Peso"><?= $fmt($e->peso_percentual) ?>%</td>
                            <td data-label="Valor previsto">R$ <?= $fmt($e->valor_previsto) ?></td>
                            <?php if (! empty($plan_ativo)): ?>
                                <td data-label="Materiais e serviços" class="pj-col-itens">
                                    <?php if ($planOk || $itEt): ?>
                                        <a href="#" class="js-itens-toggle pj-itens-link" data-alvo="itens-etapa-<?= (int) $e->idEtapa ?>" aria-expanded="false">
                                            <i class="bx bx-chevron-down"></i> <?= count($itEt) ?> <?= count($itEt) === 1 ? 'item' : 'itens' ?><?= $itEt ? ', R$ ' . $fmt($totaisPrevistos[$e->idEtapa] ?? 0) : '' ?>
                                        </a>
                                        <?php if ($itEt): ?><small class="pj-itens-previa"><?= html_escape(implode(', ', array_map(function ($i) { return ((object) $i)->descricao; }, array_slice($itEt, 0, 2)))) ?><?= count($itEt) > 2 ? ' e mais ' . (count($itEt) - 2) : '' ?></small><?php endif; ?>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td data-label="OS" class="pj-col-os">
                                    <?php foreach ($osEt as $oe): $oe = (object) $oe; ?>
                                        <a href="<?= site_url('os/visualizar/' . (int) $oe->idOs) ?>" class="ui-selo <?= $coresOs[$oe->status] ?? 'neutro' ?>" title="<?= html_escape(($oe->status ?? '') . (! empty($oe->tecnico_nome) ? ' - ' . $oe->tecnico_nome : '')) ?>">#<?= (int) $oe->idOs ?> <?= html_escape($oe->status ?? '') ?></a>
                                    <?php endforeach; ?>
                                    <?php if ($planOk && ! $osEt): ?>
                                        <a href="#modal-os-etapa" data-toggle="modal" class="button btn btn-mini js-gerar-os" data-etapa="<?= (int) $e->idEtapa ?>" data-nome="<?= html_escape($e->nome) ?>" data-inicio="<?= html_escape($iniOs) ?>"><span class="button__icon"><i class='bx bx-receipt'></i></span><span class="button__text2">Gerar OS</span></a>
                                    <?php elseif ($planOk): ?>
                                        <a href="#modal-os-etapa" data-toggle="modal" class="pj-link-fraco js-gerar-os" data-etapa="<?= (int) $e->idEtapa ?>" data-nome="<?= html_escape($e->nome) ?>" data-inicio="<?= html_escape($iniOs) ?>">Gerar outra</a>
                                    <?php elseif (! $osEt): ?>—<?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td data-label="Datas" class="pj-col-datas"><?= $data_br($e->data_inicio_prevista) ?><br><small>até <?= $data_br($e->data_fim_prevista) ?></small></td>
                            <td data-label="Progresso" class="pj-col-prog">
                                <div class="ui-barra"><span style="width:<?= max(0, min(100, (float) $e->percentual_concluido)) ?>%"></span></div>
                                <small><?= $fmt($e->percentual_concluido) ?>%</small>
                            </td>
                            <?php if ($perm('eObraCronograma')): ?>
                                <td class="pj-col-acoes">
                                    <a href="#" class="btn-nwe3" title="Editar" aria-label="Editar etapa <?= html_escape($e->nome) ?>" data-chamar="editarEtapa" data-args="<?= html_escape(json_encode([$e])) ?>" data-cancelar><i class="bx bx-edit bx-xs"></i></a>
                                    <a href="#" class="btn-nwe4" title="Excluir" aria-label="Excluir etapa <?= html_escape($e->nome) ?>" data-chamar="excluirEtapa" data-args="[<?= (int) $e->idEtapa ?>]" data-cancelar><i class="bx bx-trash-alt bx-xs"></i></a>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php $sugEtapa = $etapasSugestoesConcluir[$e->idEtapa] ?? null; ?>
                        <?php if ($sugEtapa && $e->status !== 'concluida'): ?>
                        <tr class="pj-linha-sugestao">
                            <td colspan="10">
                                <div class="ui-dica alerta pj-sugestao-concluir">
                                    <i class="bx bx-error"></i>
                                    <div>
                                        Todas as OS desta etapa terminaram, mas <?= (int) $sugEtapa['pontos_pendentes'] ?> ponto(s) ainda não foram testados.
                                        <div class="pj-sugestao-acoes">
                                            <a href="<?= site_url('obras/visualizar/' . (int) $obra->idObra) ?>?pt_etapa=<?= (int) $e->idEtapa ?>#pontos" class="button btn btn-mini"><span class="button__icon"><i class="bx bx-cctv"></i></span><span class="button__text2">Ver pontos pendentes</span></a>
                                            <?php if ($perm('eObraCronograma')): ?>
                                            <form action="<?= site_url('obras/salvarEtapa') ?>" method="post" class="pj-form-linha" data-confirmar="Concluir esta etapa mesmo com pontos pendentes?">
                                                <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
                                                <input type="hidden" name="idEtapa" value="<?= (int) $e->idEtapa ?>">
                                                <input type="hidden" name="codigo_eap" value="<?= html_escape((string) $e->codigo_eap) ?>">
                                                <input type="hidden" name="nome" value="<?= html_escape($e->nome) ?>">
                                                <input type="hidden" name="peso_percentual" value="<?= (float) $e->peso_percentual ?>">
                                                <input type="hidden" name="valor_previsto" value="<?= (float) $e->valor_previsto ?>">
                                                <input type="hidden" name="percentual_concluido" value="100">
                                                <input type="hidden" name="data_inicio_prevista" value="<?= html_escape((string) $e->data_inicio_prevista) ?>">
                                                <input type="hidden" name="data_fim_prevista" value="<?= html_escape((string) $e->data_fim_prevista) ?>">
                                                <input type="hidden" name="status" value="concluida">
                                                <button class="button btn btn-mini btn-success"><span class="button__icon"><i class="bx bx-check-circle"></i></span><span class="button__text2">Concluir etapa assim mesmo</span></button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if (! empty($plan_ativo) && ($planOk || $itEt)): ?>
                        <tr id="itens-etapa-<?= (int) $e->idEtapa ?>" class="js-itens-row pj-linha-itens" style="display:none">
                            <td colspan="10">
                                <div class="pj-itens">
                                <strong>Materiais e serviços de &quot;<?= html_escape($e->nome) ?>&quot;</strong>
                                <table class="table pj-tab-itens">
                                    <thead><tr><th>Tipo</th><th>Descrição</th><th>Qtd</th><th>Preço</th><th>Subtotal</th><?php if ($planOk): ?><th></th><?php endif; ?></tr></thead>
                                    <tbody>
                                    <?php if (! $itEt): ?><tr><td colspan="6"><small>Nenhum item previsto. Adicione abaixo o que será usado nesta etapa.</small></td></tr><?php endif; ?>
                                    <?php foreach ($itEt as $it): $it = (object) $it; ?>
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
                                                        <button class="btn-nwe4" style="border:0;background:none" title="Remover item" aria-label="Remover item"><i class="bx bx-trash-alt bx-xs"></i></button>
                                                    </form>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php if ($planOk): ?>
                                <form action="<?= site_url('obras/salvarItemEtapa') ?>" method="post" class="js-form-item pj-form-item">
                                    <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
                                    <input type="hidden" name="etapa_id" value="<?= (int) $e->idEtapa ?>">
                                    <input type="hidden" name="referencia_id" class="js-ref">
                                    <select name="tipo" class="js-tipo" aria-label="Tipo do item"><option value="produto">Produto</option><option value="servico">Serviço</option></select>
                                    <input type="text" class="js-busca" placeholder="Buscar produto ou serviço" autocomplete="off" aria-label="Buscar item">
                                    <input type="text" name="quantidade" value="1" class="pj-curto" aria-label="Quantidade">
                                    <input type="text" name="preco" class="js-preco pj-curto" placeholder="Preço" aria-label="Preço (opcional)">
                                    <button class="button btn btn-mini"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Adicionar item</span></button>
                                </form>
                                <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- EXECUCAO: registros do dia, mao de obra e OS vinculadas -->
    <?php $podeRdo = $perm('cObraRdo'); ?>
    <div class="obra-pane" id="tab-execucao" style="display:none">

        <section class="pj-bloco" id="registros" aria-labelledby="pj-t-reg">
            <div class="pj-secao-cab">
                <h2 id="pj-t-reg">Registros do dia</h2>
                <div class="pj-secao-acoes">
                    <?php if (! empty($pode_executar)): ?><a href="#modal-registro" data-toggle="modal" class="button btn btn-mini"><span class="button__icon"><i class='bx bx-check-circle'></i></span><span class="button__text2">Registrar execução</span></a><?php endif; ?>
                    <?php if ($podeRdo): ?><a href="#modal-rdo" data-toggle="modal" class="button btn btn-mini"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Registro completo</span></a><?php endif; ?>
                </div>
            </div>
            <?php
            // Junta os registros do dia (RDO) com a atividade vinda de OS vinculada
            // (check-in/checkout, mão de obra e consumo com os_id) - ADR-012/ADR-015 fase 4.
            $execRegistros = [];
            foreach ($rdos as $r) {
                $execRegistros[] = ['data' => (string) $r->data, 'ordem' => (int) $r->idRdo, 'tipo' => 'rdo', 'obj' => $r];
            }
            foreach ($atividades_os as $a) {
                $execRegistros[] = ['data' => (string) $a['data'], 'ordem' => 0, 'tipo' => 'os', 'obj' => (object) $a];
            }
            usort($execRegistros, function ($x, $y) {
                return strcmp($y['data'], $x['data']) ?: ($y['ordem'] <=> $x['ordem']);
            });
            ?>
            <div class="pj-tabela-caixa ui-tabela-rolagem">
                <table class="table pj-tab-cartao">
                    <thead><tr><th>Origem</th><th>Data</th><th>O que foi feito</th><th>Condição</th><th>Técnicos</th><th>Responsável</th><th></th></tr></thead>
                    <tbody>
                        <?php if (! $execRegistros): ?><tr class="pj-linha-vazia"><td colspan="7"><div class="ui-vazio"><i class="bx bx-notepad"></i><strong>Nenhum registro do dia ainda.</strong>Registre o que foi feito para o histórico e o cliente acompanharem.</div></td></tr><?php endif; ?>
                        <?php foreach ($execRegistros as $item): $o = $item['obj']; ?>
                            <?php if ($item['tipo'] === 'rdo'): ?>
                                <tr>
                                    <td data-label="Origem"><span class="ui-selo neutro" title="Registro do dia nº <?= html_escape($o->numero) ?>">Registro do dia</span></td>
                                    <td data-label="Data"><?= $data_br($o->data) ?></td>
                                    <td data-label="O que foi feito"><?= html_escape(mb_strimwidth((string) $o->atividades, 0, 80, '…')) ?: '—' ?></td>
                                    <td data-label="Condição"><?= html_escape($condicoesRdo[$o->condicao] ?? (string) $o->condicao) ?></td>
                                    <td data-label="Técnicos"><?= (int) $o->efetivo_total ?></td>
                                    <td data-label="Responsável"><?= html_escape($o->responsavel) ?></td>
                                    <td class="pj-col-acoes"><a href="<?= site_url('obras/imprimirRdo/' . $o->idRdo) ?>" target="_blank" class="btn-nwe" title="Imprimir registro" aria-label="Imprimir registro <?= html_escape($o->numero) ?>"><i class="bx bx-printer bx-xs"></i></a></td>
                                </tr>
                            <?php else: ?>
                                <tr class="pj-linha-os">
                                    <td data-label="Origem"><a href="<?= site_url('os/visualizar/' . (int) $o->os_id) ?>" class="ui-selo info" title="Atividade vinda da OS #<?= (int) $o->os_id ?>">OS #<?= (int) $o->os_id ?></a></td>
                                    <td data-label="Data"><?= $data_br($o->data) ?></td>
                                    <td data-label="O que foi feito"><?= html_escape((string) $o->atividades) ?></td>
                                    <td data-label="Condição">—</td>
                                    <td data-label="Técnicos">—</td>
                                    <td data-label="Responsável"><?= html_escape((string) ($o->responsavel ?: '—')) ?></td>
                                    <td class="pj-col-acoes"><a href="<?= site_url('os/visualizar/' . (int) $o->os_id) ?>" class="btn-nwe" title="Ver a OS" aria-label="Ver a OS <?= (int) $o->os_id ?>"><i class="bx bx-link-external bx-xs"></i></a></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="pj-bloco" id="maodeobra" aria-labelledby="pj-t-mo">
            <div class="pj-secao-cab">
                <h2 id="pj-t-mo">Mão de obra<?php if ($verValor): ?> <small>total R$ <?= $fmt($apontamento_total) ?></small><?php endif; ?></h2>
                <?php if ($podeRdo): ?>
                <div class="pj-secao-acoes">
                    <form action="<?= site_url('obras/consolidarPonto') ?>" method="post" class="pj-form-linha pj-consolidar">
                        <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
                        <label for="pj-cons-data" class="pj-sr">Data do ponto</label>
                        <input type="text" id="pj-cons-data" class="datepicker" name="data" value="<?= date('d/m/Y') ?>" autocomplete="off">
                        <button class="button btn btn-mini" data-confirmar="Consolidar as batidas de ponto vinculadas a este projeto nesse dia?" title="Transforma as batidas de ponto (facial/GPS) do dia em horas e custo de mão de obra"><span class="button__icon"><i class='bx bx-sync'></i></span><span class="button__text2">Consolidar ponto do dia</span></button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <div class="pj-tabela-caixa ui-tabela-rolagem">
                <table class="table pj-tab-cartao">
                    <thead><tr><th>Data</th><th>Colaborador</th><th>Função</th><th>Horas</th><th>Diária</th><th>Origem</th><?php if ($verValor): ?><th>Valor</th><?php endif; ?><?php if ($podeRdo): ?><th></th><?php endif; ?></tr></thead>
                    <tbody>
                        <?php if (! $apontamentos): ?><tr class="pj-linha-vazia"><td colspan="9"><div class="ui-vazio"><i class="bx bx-time-five"></i><strong>Nenhum apontamento ainda.</strong>Consolide o ponto do dia ou lance manualmente abaixo.</div></td></tr><?php endif; ?>
                        <?php foreach ($apontamentos as $ap): ?>
                            <tr>
                                <td data-label="Data"><?= $data_br($ap->data) ?></td>
                                <td data-label="Colaborador"><?= html_escape($ap->nome ?: ('#' . $ap->colaborador_id)) ?></td>
                                <td data-label="Função"><?= html_escape($ap->funcao) ?></td>
                                <td data-label="Horas"><?= $fmt($ap->horas) ?></td>
                                <td data-label="Diária"><?= $ap->diaria ? '1' : '—' ?></td>
                                <td data-label="Origem">
                                    <?php if (! empty($ap->os_id)): ?>
                                        <a href="<?= site_url('os/visualizar/' . (int) $ap->os_id) ?>" class="ui-selo info" title="Lançado pela execução da OS">OS #<?= (int) $ap->os_id ?></a>
                                    <?php else: ?>
                                        <span class="ui-selo neutro"><?= $ap->origem === 'ponto_facial' ? 'Ponto' : 'Manual' ?></span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($verValor): ?><td data-label="Valor">R$ <?= $fmt($ap->valor) ?></td><?php endif; ?>
                                <?php if ($podeRdo): ?>
                                    <td class="pj-col-acoes"><a href="#" class="btn-nwe4" title="Remover" aria-label="Remover apontamento" data-chamar="excluirApont" data-args="[<?= (int) $ap->idApontamento ?>]" data-cancelar><i class="bx bx-trash-alt bx-xs"></i></a></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($podeRdo): ?>
                <form action="<?= site_url('obras/salvarApontamento') ?>" method="post" class="pj-form-manual">
                    <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
                    <strong>Lançar horas manualmente</strong>
                    <div class="pj-campos">
                        <label>Data<input type="text" class="datepicker" name="data" value="<?= date('d/m/Y') ?>" autocomplete="off"></label>
                        <label>Colaborador<input type="text" name="nome" placeholder="Nome"></label>
                        <label>Função<input type="text" name="funcao"></label>
                        <label>Horas<input type="number" step="0.5" name="horas" value="0"></label>
                        <label>Valor (R$)<input type="number" step="0.01" name="valor" value="0"></label>
                        <label class="pj-check"><input type="checkbox" name="diaria" value="1"> Diária</label>
                        <button class="button btn btn-mini"><span class="button__icon"><i class='bx bx-plus'></i></span><span class="button__text2">Lançar</span></button>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <section class="pj-bloco" id="os" aria-labelledby="pj-t-osv">
            <div class="pj-secao-cab">
                <h2 id="pj-t-osv">OS vinculadas <?php if ($os_vinculadas): ?><small><?= count($os_vinculadas) ?></small><?php endif; ?></h2>
                <div class="pj-secao-acoes">
                    <?php if ($ptEdita): ?><a href="#modal-vincular-os" data-toggle="modal" class="button btn btn-mini"><span class="button__icon"><i class='bx bx-link'></i></span><span class="button__text2">Vincular OS existente</span></a><?php endif; ?>
                </div>
            </div>
            <div class="pj-tabela-caixa ui-tabela-rolagem">
                <table class="table pj-tab-cartao">
                    <thead><tr><th>OS</th><th>Cliente</th><th>Técnico</th><th>Status</th><th>Etapa</th><th></th></tr></thead>
                    <tbody>
                        <?php if (! $os_vinculadas): ?><tr class="pj-linha-vazia"><td colspan="6"><div class="ui-vazio"><i class="bx bx-wrench"></i><strong>Nenhuma OS vinculada.</strong>Gere as OS pelo Planejamento ou vincule uma OS que já existe.</div></td></tr><?php endif; ?>
                        <?php foreach ($os_vinculadas as $o): ?>
                            <tr>
                                <td data-label="OS"><a href="<?= site_url('os/visualizar/' . $o->idOs) ?>"><strong>#<?= (int) $o->idOs ?></strong></a></td>
                                <td data-label="Cliente"><?= html_escape($o->nomeCliente ?: '—') ?></td>
                                <td data-label="Técnico"><?= html_escape($o->tecnico ?: '—') ?></td>
                                <td data-label="Status"><span class="ui-selo <?= $coresOs[$o->status] ?? 'neutro' ?>"><?= html_escape($o->status ?: '—') ?></span></td>
                                <td data-label="Etapa"><?= html_escape($o->etapa_nome ?: '—') ?></td>
                                <td class="pj-col-acoes">
                                    <a href="<?= site_url('tecnico/visualizar/' . $o->idOs) ?>" class="button btn btn-mini" title="Executar em campo"><span class="button__icon"><i class='bx bx-play-circle'></i></span><span class="button__text2">Executar</span></a>
                                    <?php if ($ptEdita): ?><a href="#" class="btn-nwe4" title="Desvincular" aria-label="Desvincular OS <?= (int) $o->idOs ?>" data-chamar="desvincularOs" data-args="[<?= (int) $o->idVinculo ?>]" data-cancelar><i class="bx bx-unlink bx-xs"></i></a><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($os_vinculadas): ?>
            <div class="pj-duas">
                <div>
                    <h3 class="pj-sub">O que executar (serviços)</h3>
                    <div class="pj-tabela-caixa ui-tabela-rolagem"><table class="table">
                        <thead><tr><th>OS</th><th>Serviço</th><th>Qtd.</th></tr></thead>
                        <tbody>
                            <?php if (! $os_servicos): ?><tr><td colspan="3" class="pj-vazio-linha">Sem serviços lançados nas OS.</td></tr><?php endif; ?>
                            <?php foreach ($os_servicos as $s): ?><tr><td>#<?= (int) $s->os_id ?></td><td><?= html_escape($s->nome ?: '—') ?></td><td><?= $fmt($s->quantidade) ?></td></tr><?php endforeach; ?>
                        </tbody>
                    </table></div>
                </div>
                <div>
                    <h3 class="pj-sub">Material a utilizar (produtos)</h3>
                    <div class="pj-tabela-caixa ui-tabela-rolagem"><table class="table">
                        <thead><tr><th>OS</th><th>Produto</th><th>Qtd.</th></tr></thead>
                        <tbody>
                            <?php if (! $os_produtos): ?><tr><td colspan="3" class="pj-vazio-linha">Sem produtos lançados nas OS.</td></tr><?php endif; ?>
                            <?php foreach ($os_produtos as $p): ?><tr><td>#<?= (int) $p->os_id ?></td><td><?= html_escape($p->nome ?: '—') ?></td><td><?= $fmt($p->quantidade) ?></td></tr><?php endforeach; ?>
                        </tbody>
                    </table></div>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- MATERIAL -->
    <div class="obra-pane" id="tab-material" style="display:none">
        <div class="pj-secao-cab">
            <h2>Material</h2>
            <div class="pj-secao-acoes">
                <?php if ($perm('rObraMaterial')): ?><a href="<?= site_url('obramaterial/receber/' . $obra->idObra) ?>" class="button btn btn-mini"><span class="button__icon"><i class='bx bx-down-arrow-circle'></i></span><span class="button__text2">Receber material</span></a><?php endif; ?>
                <?php if ($perm('sObraMaterial')): ?><a href="<?= site_url('obramaterial/entregar/' . $obra->idObra) ?>" class="button btn btn-mini"><span class="button__icon"><i class='bx bx-up-arrow-circle'></i></span><span class="button__text2">Entregar à equipe</span></a><?php endif; ?>
            </div>
        </div>
        <div class="pj-cartao pj-compras">
            <i class='bx bx-cart-alt'></i>
            <div>
                <div class="pj-compras-total">R$ <?= $fmt($compras['total']) ?></div>
                <div class="pj-nota">Comprado pela empresa para este projeto (<?= (int) $compras['recebimentos'] ?> recebimento(s) por compra própria). Entra no custo realizado.</div>
            </div>
        </div>

        <section class="pj-bloco" aria-labelledby="pj-t-saldo">
            <div class="pj-secao-cab"><h2 id="pj-t-saldo">Saldo no almoxarifado</h2></div>
            <div class="pj-tabela-caixa ui-tabela-rolagem">
                <table class="table pj-tab-cartao">
                    <thead><tr><th>Item</th><th>Cód. barras</th><th>Un.</th><th>Origem</th><th>Saldo</th></tr></thead>
                    <tbody>
                        <?php if (! $saldos): ?><tr class="pj-linha-vazia"><td colspan="5"><div class="ui-vazio"><i class="bx bx-package"></i><strong>Sem material em estoque.</strong>Quando o material chegar, use Receber material.</div></td></tr><?php endif; ?>
                        <?php foreach ($saldos as $s): ?>
                            <tr>
                                <td data-label="Item"><?= html_escape($s->descricao) ?></td>
                                <td data-label="Cód. barras"><?= html_escape($s->cod_barras) ?></td>
                                <td data-label="Un."><?= html_escape($s->unidade) ?></td>
                                <td data-label="Origem"><?= $s->do_cliente ? '<span class="ui-selo info">Cliente</span>' : 'Próprio' ?></td>
                                <td data-label="Saldo"><strong><?= rtrim(rtrim(number_format((float) $s->saldo, 3, ',', '.'), '0'), ',') ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="pj-bloco" aria-labelledby="pj-t-consumo">
            <div class="pj-secao-cab"><h2 id="pj-t-consumo">Consumo registrado</h2></div>
            <div class="pj-tabela-caixa ui-tabela-rolagem">
                <table class="table pj-tab-cartao">
                    <thead><tr><th>Data</th><th>Tipo</th><th>Descrição</th><th>Qtd.</th><th>Origem</th><th>Quem</th></tr></thead>
                    <tbody>
                        <?php if (! $consumo_projeto): ?><tr class="pj-linha-vazia"><td colspan="6"><div class="ui-vazio"><i class="bx bx-cube"></i><strong>Nenhum consumo registrado ainda.</strong>Material e serviço usados no Registro do dia ou na OS aparecem aqui.</div></td></tr><?php endif; ?>
                        <?php foreach ($consumo_projeto as $c): ?>
                            <tr>
                                <td data-label="Data"><?= $data_br($c['data']) ?></td>
                                <td data-label="Tipo"><?= $c['tipo'] === 'servico' ? 'Serviço' : 'Material' ?></td>
                                <td data-label="Descrição"><?= html_escape($c['descricao']) ?></td>
                                <td data-label="Qtd."><?= rtrim(rtrim(number_format((float) $c['quantidade'], 3, ',', '.'), '0'), ',') ?></td>
                                <td data-label="Origem">
                                    <?php if ($c['os_id']): ?>
                                        <a href="<?= site_url('os/visualizar/' . (int) $c['os_id']) ?>" class="ui-selo info" title="Consumo lançado pela execução da OS"><?= html_escape($c['origem_rotulo']) ?></a>
                                    <?php else: ?>
                                        <span class="ui-selo neutro"><?= html_escape($c['origem_rotulo']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Quem"><?= html_escape($c['usuario'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="pj-duas">
            <section aria-labelledby="pj-t-rec">
                <h3 class="pj-sub" id="pj-t-rec">Recebimentos</h3>
                <div class="pj-tabela-caixa ui-tabela-rolagem"><table class="table">
                    <thead><tr><th>Nº</th><th>Data</th><th>Origem</th><th>Doc.</th></tr></thead>
                    <tbody>
                        <?php if (! $recebimentos): ?><tr><td colspan="4" class="pj-vazio-linha">Nenhum recebimento.</td></tr><?php endif; ?>
                        <?php foreach ($recebimentos as $r): ?><tr><td><?= html_escape($r->numero) ?></td><td><?= $data_br($r->data) ?></td><td><?= html_escape($r->origem) ?></td><td><?= html_escape($r->documento) ?></td></tr><?php endforeach; ?>
                    </tbody>
                </table></div>
            </section>
            <section aria-labelledby="pj-t-ent">
                <h3 class="pj-sub" id="pj-t-ent">Entregas à equipe</h3>
                <div class="pj-tabela-caixa ui-tabela-rolagem"><table class="table">
                    <thead><tr><th>Nº</th><th>Data</th><th>Equipe</th><th>Recebedor</th></tr></thead>
                    <tbody>
                        <?php if (! $entregas): ?><tr><td colspan="4" class="pj-vazio-linha">Nenhuma entrega.</td></tr><?php endif; ?>
                        <?php foreach ($entregas as $e): ?><tr><td><?= html_escape($e->numero) ?></td><td><?= $data_br($e->data) ?></td><td><?= html_escape($e->equipe ?: '—') ?></td><td><?= html_escape($e->recebedor_nome) ?></td></tr><?php endforeach; ?>
                    </tbody>
                </table></div>
            </section>
        </div>
    </div>

    <!-- FINANCEIRO -->
    <div class="obra-pane" id="tab-financeiro" style="display:none">
        <div class="pj-grade">
            <section class="pj-cartao" aria-labelledby="pj-t-fin">
                <div class="pj-cartao-cab"><h2 id="pj-t-fin">Contrato</h2></div>
                <dl class="pj-dl">
                    <dt>Valor do contrato</dt><dd>R$ <?= $fmt($obra->valor_contrato) ?></dd>
                    <dt>BDI</dt><dd><?= $fmt($obra->bdi_percentual) ?>% <small class="pj-dica-inline">margem aplicada sobre o custo</small></dd>
                    <dt>Contrato nº</dt><dd><?= html_escape($obra->contrato_numero ?: '—') ?></dd>
                </dl>
            </section>
            <section class="pj-cartao" aria-labelledby="pj-t-custo">
                <div class="pj-cartao-cab"><h2 id="pj-t-custo">Custos</h2></div>
                <?php $cPrev = (float) $custo['previsto']; $cReal = (float) $custo['realizado'];
                $cPct = $cPrev > 0 ? min(100, round($cReal * 100 / $cPrev)) : 0; ?>
                <dl class="pj-dl">
                    <dt>Previsto</dt><dd>R$ <?= $fmt($cPrev) ?></dd>
                    <dt>Realizado</dt><dd>R$ <?= $fmt($cReal) ?> <?php if ($cPrev > 0): ?><span class="ui-selo <?= $cReal > $cPrev ? 'perigo' : 'neutro' ?>"><?= (int) round($cReal * 100 / $cPrev) ?>% do previsto</span><?php endif; ?></dd>
                </dl>
                <?php if ($cPrev > 0): ?><div class="ui-barra" role="progressbar" aria-valuenow="<?= (int) $cPct ?>" aria-valuemin="0" aria-valuemax="100"><span style="width:<?= (int) $cPct ?>%"></span></div><?php endif; ?>
            </section>
        </div>

        <section class="pj-bloco" id="medicao" aria-labelledby="pj-t-med">
            <div class="pj-secao-cab">
                <h2 id="pj-t-med">Medições</h2>
                <div class="pj-secao-acoes">
                    <?php if ($perm('cObraMedicao') && $etapas): ?><a href="#modal-medicao" data-toggle="modal" class="button btn btn-mini"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Nova medição</span></a><?php endif; ?>
                </div>
            </div>
            <div class="ui-dica"><i class="bx bx-info-circle"></i><div>Medição é a cobrança parcial do que foi executado. Informe o novo percentual de cada etapa e o valor é calculado.<?php if (! $etapas): ?> Crie as etapas no Planejamento para poder medir.<?php endif; ?></div></div>
            <div class="pj-tabela-caixa ui-tabela-rolagem">
                <table class="table pj-tab-cartao">
                    <thead><tr><th>Nº</th><th>Período</th><th>% acumulado</th><th>Valor</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php if (! $medicoes): ?><tr class="pj-linha-vazia"><td colspan="6"><div class="ui-vazio"><i class="bx bx-receipt"></i><strong>Nenhuma medição ainda.</strong>Faça a primeira quando houver etapas executadas para cobrar.</div></td></tr><?php endif; ?>
                        <?php foreach ($medicoes as $m): ?>
                            <tr>
                                <td data-label="Nº"><?= html_escape($m->numero) ?></td>
                                <td data-label="Período"><?= $data_br($m->periodo_inicio) ?> a <?= $data_br($m->periodo_fim) ?></td>
                                <td data-label="% acumulado"><?= $fmt($m->percentual_acumulado) ?>%</td>
                                <td data-label="Valor">R$ <?= $fmt($m->valor_medido) ?></td>
                                <td data-label="Status"><span class="ui-selo <?= $m->status === 'aberta' ? 'alerta' : 'sucesso' ?>"><?= html_escape($m->status) ?></span></td>
                                <td class="pj-col-acoes">
                                    <?php if ($m->status === 'aberta' && $perm('aObraMedicao')): ?>
                                        <form action="<?= site_url('obras/aprovarMedicao') ?>" method="post" class="pj-form-linha">
                                            <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
                                            <input type="hidden" name="idMedicao" value="<?= $m->idMedicao ?>">
                                            <button class="button btn btn-mini btn-success" data-confirmar="Aprovar medição?"><span class="button__text2">Aprovar</span></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
</div><!-- /.pj-pagina -->

<?php if ($perm('eObras')): ?>
<!-- Modal Vincular OS -->
<div id="modal-vincular-os" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obras/vincularOs') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Vincular OS existente</h5></div>
        <div class="modal-body">
            <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
            <div class="span12" style="margin-left:0"><label>Ordem de serviço *</label>
                <select name="os_id" class="span12" required>
                    <option value="">Selecione a OS...</option>
                    <?php foreach ($os_disponiveis as $d): ?>
                        <option value="<?= (int) $d->idOs ?>">#<?= (int) $d->idOs ?> — <?= html_escape($d->nomeCliente ?: 'sem cliente') ?><?= $d->status ? ' (' . html_escape($d->status) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (! $os_disponiveis): ?><small>Nenhuma OS livre para vincular (todas já estão em algum projeto).</small><?php endif; ?>
            </div>
            <div class="span12" style="margin-left:0"><label>Etapa (opcional)</label>
                <select name="etapa_id" class="span12">
                    <option value="">Sem etapa</option>
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
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Gerar OS da etapa <span id="os_etapa_nome"></span></h5></div>
        <div class="modal-body">
            <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
            <input type="hidden" name="etapa_id" id="os_etapa_id">
            <?= $camposOs(true) ?>
            <small>Os materiais e serviços da etapa são copiados para a OS.</small>
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
            <div class="span12" style="margin-left:0"><label><input type="checkbox" name="forcar" value="1" style="margin:0 6px 0 0"> Gerar mesmo se a etapa já tiver OS ou não tiver itens</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button class="button btn btn-success"><span class="button__text2">Gerar OS</span></button></div>
    </form>
</div>
<!-- Modal Gerar OS por pontos -->
<div id="modal-os-pontos" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obras/gerarOsPontos') ?>" method="post" id="form-os-pontos">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Gerar OS dos pontos marcados</h5></div>
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
        $('.js-itens-toggle').on('click', function (ev) { ev.preventDefault(); var $l = $(this), $r = $('#' + $l.data('alvo')); $r.toggle(); var aberto = $r.is(':visible'); $l.attr('aria-expanded', aberto).toggleClass('aberto', aberto); });
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
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5><i class='bx bx-check-circle'></i> Registrar execução do dia — <?= date('d/m/Y') ?></h5></div>
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
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Adicionar pontos em lote</h5></div>
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
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Etapa do projeto</h5></div>
        <div class="modal-body">
            <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
            <input type="hidden" name="idEtapa" id="et_id" value="">
            <div class="span5" style="margin-left:0"><label>Nº</label><input type="text" class="span12" name="codigo_eap" id="et_cod"></div>
            <div class="span7"><label>Nome *</label><input type="text" class="span12" name="nome" id="et_nome" required></div>
            <div class="span4" style="margin-left:0"><label>Peso</label><input type="number" step="0.01" class="span12" name="peso_percentual" id="et_peso" value="0"></div>
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
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Registro do dia</h5></div>
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
            <div class="span12" style="margin-left:0"><label>% concluído por etapa (medição é a cobrança parcial do que foi executado)</label>
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

        // abas por hash (aceita os nomes antigos das abas)
        var apelidos = { geral: 'resumo', cronograma: 'planejamento', rdo: 'execucao', os: 'execucao', maodeobra: 'execucao', equipe: 'resumo', medicao: 'financeiro' };
        function ativar(tab) {
            tab = apelidos[tab] || tab;
            if (! $('#tab-' + tab).length) return;
            $('.obra-pane').hide();
            $('#tab-' + tab).show();
            $('#obraTabs li').removeClass('active');
            var $a = $('#obraTabs a[data-tab="' + tab + '"]');
            $a.parent().addClass('active');
            if ($a[0] && $a[0].scrollIntoView) { try { $a[0].scrollIntoView({ block: 'nearest', inline: 'center' }); } catch (e) {} }
        }
        $('#obraTabs a').on('click', function (e) {
            e.preventDefault();
            var t = $(this).data('tab');
            ativar(t);
            history.replaceState(null, '', '#' + t);
        });
        $(document).on('click', '.js-ir-aba', function (e) {
            e.preventDefault();
            var t = $(this).data('aba');
            ativar(t);
            history.replaceState(null, '', '#' + t);
            window.scrollTo(0, 0);
        });
        $(document).on('click', '.js-ir-ponto', function (e) {
            e.preventDefault();
            ativar('pontos');
            var el = document.getElementById('pt-linha-' + $(this).data('ponto'));
            if (el) { el.scrollIntoView({ block: 'center' }); $(el).addClass('pj-destaque'); setTimeout(function () { $(el).removeClass('pj-destaque'); }, 2200); }
        });
        var h = (location.hash || '').replace('#', '');
        if (h && (apelidos[h] || $('#tab-' + h).length)) {
            ativar(h);
            var alvoEl = document.getElementById(h);
            if (alvoEl && h !== (apelidos[h] || h)) { alvoEl.scrollIntoView(); }
        }
        // menu Mais: fecha ao clicar fora
        $(document).on('click', function (e) {
            $('.pj-mais[open]').each(function () { if (! this.contains(e.target)) this.removeAttribute('open'); });
        });

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
        function atualizarSelecao() {
            var n = $('input[name="ponto_ids[]"]:checked').length;
            $('#pj-sel-qtd').text(n);
            $('#pj-selecao').prop('hidden', n === 0);
        }
        $('#pt-selecionar-todos').on('change', function () {
            $('input[name="ponto_ids[]"]').prop('checked', this.checked);
            atualizarSelecao();
        });
        $(document).on('change', 'input[name="ponto_ids[]"]', atualizarSelecao);
        atualizarSelecao();
    });
</script>
