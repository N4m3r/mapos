<?php
// Painel inicial (Mapos::index). Dados montados em Painel_model / Painel_inicio.
$e = fn ($v) => html_escape((string) ($v ?? ''));
$perm = $this->session->userdata('permissao');
$pode = fn ($atividade) => $this->permission->checkPermission($perm, $atividade);
$moeda = fn ($v) => Painel_inicio::moeda($v);
$num = fn ($v, $d = 0) => Painel_inicio::numero($v, $d);

require_once APPPATH . 'libraries/Grafico_svg.php';

$hora = (int) date('H');
$saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
$ehHoje = date('d/m/Y');
// Central de atendimento exige eOs; sem isso, o indicador cai na lista simples
$rotaAgenda = $pode('eOs') ? 'os/atribuir' : 'os';

// Blocos que a permissao libera (o controller deixa null no que nao libera)
$temConteudo = $pode('vOs') || $pi_pendentes !== null || $pi_estoque !== null || $pi_vendas_mes !== null;
$colunaEsquerda = $pode('vOs') || $pi_pendentes !== null;
$colunaDireita = $pode('vOs') || ($pi_mes !== null && ! empty($pi_meses)) || $pi_estoque !== null;

$atalhos = [];
if ($pode('aCliente')) {
    $atalhos[] = ['clientes/adicionar', 'bx-user-plus', 'Novo cliente'];
}
if ($pode('aOs')) {
    $atalhos[] = ['os/adicionar', 'bx-file-blank', 'Nova OS'];
}
if ($pode('aVenda')) {
    $atalhos[] = ['pdv', 'bx-store-alt', 'PDV'];
}
if ($pode('vLancamento')) {
    $atalhos[] = ['financeiro/lancamentos', 'bx-wallet', 'Lançamentos'];
}
if ($pode('vOs')) {
    $atalhos[] = ['agenda', 'bx-calendar', 'Agenda'];
}
if ($pode('rOs')) {
    $atalhos[] = ['relatorioos', 'bx-bar-chart', 'OS realizadas'];
}
if ($pode('vDashboard') && $pode('rOs')) {
    $atalhos[] = ['painelgerencial', 'bx-line-chart', 'Painel gerencial'];
}
if ($pode('rProduto')) {
    $atalhos[] = ['comprasugerida', 'bx-cart', 'Compra sugerida'];
}
$temAtalhos = (bool) $atalhos;
$colunaDireita = $colunaDireita || $temAtalhos;

$rotuloPrazo = function ($data) {
    [$texto, $tom] = Painel_inicio::prazo($data);

    return '<span class="pi-prazo ' . ($tom ?: 'neutro') . '">' . html_escape($texto) . '</span>';
};
?>

<link rel="stylesheet" href="<?= base_url('assets/css/painel-inicio.css') ?>?v=<?= asset_versao('assets/css/painel-inicio.css', '1') ?>" />
<?php /* Contraste dos graficos SVG nos temas escuros (mesmas regras dos relatorios) */ ?>
<link rel="stylesheet" href="<?= base_url('assets/css/relatorios-escuro.css') ?>?v=<?= asset_versao('assets/css/relatorios-escuro.css') ?>" />

<?php if (! empty($avisoJanelaIbscbs)) {
    $jn = $avisoJanelaIbscbs; ?>
    <div class="ui-dica alerta">
        <i class="bx bx-calendar-exclamation"></i>
        <div>
            <strong><?= $e($jn['rotulo']) ?>:</strong>
            <?= $jn['aberta'] ? 'aberta até ' . date('d/m/Y', strtotime($jn['fim'])) : 'abre em ' . date('d/m/Y', strtotime($jn['inicio'])) ?>.
            <a href="<?= site_url('nfe/configuracoes') ?>">Ver em Configurações Fiscais</a>
        </div>
    </div>
<?php } ?>

<div class="pi">

    <div class="ui-cabecalho">
        <div class="pi-saudacao">
            <span class="pi-avatar" aria-hidden="true"><?= $e($pi_iniciais) ?></span>
            <div>
                <h1 class="ui-titulo"><?= $e($saudacao) ?>, <?= $e($pi_nome) ?></h1>
                <p class="ui-subtitulo"><?= $e($ehHoje) ?> · o que precisa da sua atenção hoje, em uma tela só.</p>
            </div>
        </div>
        <div class="ui-acoes">
            <?php if ($pode('aOs')) { ?>
                <a href="<?= site_url('os/adicionar') ?>" class="button btn btn-success">
                    <span class="button__icon"><i class="bx bx-plus-circle"></i></span><span class="button__text2">Nova OS</span>
                </a>
            <?php } ?>
            <?php if ($pode('eOs')) { ?>
                <a href="<?= site_url('os/atribuir') ?>" class="button btn btn-dark">
                    <span class="button__icon"><i class="bx bx-headphone"></i></span><span class="button__text2">Central de atendimento</span>
                </a>
            <?php } ?>
        </div>
    </div>

    <!-- Indicadores -->
    <?php if ($temConteudo) { ?>
    <div class="pi-kpis">
        <?php if ($pode('vOs')) { ?>
            <a class="pi-kpi info" href="<?= site_url('os') ?>">
                <div class="pi-kpi-topo">
                    <span class="pi-kpi-rotulo">OS em aberto</span>
                    <i class="bx bx-file pi-kpi-icone" aria-hidden="true"></i>
                </div>
                <div class="pi-kpi-valor"><?= $num($pi_em_aberto) ?></div>
                <div class="pi-kpi-extra"><?= $num($pi_movimento['os']) ?> abertas hoje</div>
            </a>
            <a class="pi-kpi <?= (int) $pi_agenda['atrasadas'] > 0 ? 'perigo' : '' ?>" href="<?= site_url($rotaAgenda) ?><?= $pode('eOs') ? '?aba=atrasados' : '' ?>">
                <div class="pi-kpi-topo">
                    <span class="pi-kpi-rotulo">Atrasadas</span>
                    <i class="bx bx-error-circle pi-kpi-icone" aria-hidden="true"></i>
                </div>
                <div class="pi-kpi-valor"><?= $num($pi_agenda['atrasadas']) ?></div>
                <div class="pi-kpi-extra">data combinada já venceu</div>
            </a>
            <a class="pi-kpi <?= (int) $pi_agenda['hoje'] > 0 ? 'alerta' : '' ?>" href="<?= site_url($rotaAgenda) ?><?= $pode('eOs') ? '?aba=hoje' : '' ?>">
                <div class="pi-kpi-topo">
                    <span class="pi-kpi-rotulo">Para hoje</span>
                    <i class="bx bx-calendar-star pi-kpi-icone" aria-hidden="true"></i>
                </div>
                <div class="pi-kpi-valor"><?= $num($pi_agenda['hoje']) ?></div>
                <div class="pi-kpi-extra">visitas marcadas</div>
            </a>
        <?php } ?>

        <?php if ($pi_mes !== null) { ?>
            <a class="pi-kpi <?= $pi_mes['liquido'] >= 0 ? 'sucesso' : 'perigo' ?>" href="<?= site_url('financeiro/lancamentos') ?>">
                <div class="pi-kpi-topo">
                    <span class="pi-kpi-rotulo">Resultado do mês</span>
                    <i class="bx bx-line-chart pi-kpi-icone" aria-hidden="true"></i>
                </div>
                <div class="pi-kpi-valor"><?= $moeda($pi_mes['liquido']) ?></div>
                <div class="pi-kpi-extra"><?= $moeda($pi_mes['receita']) ?> entrou · <?= $moeda($pi_mes['despesa']) ?> saiu</div>
            </a>
        <?php } ?>

        <?php if ($pi_pendentes !== null) { ?>
            <a class="pi-kpi <?= $pi_pendentes['receita_vencida'] > 0 ? 'perigo' : '' ?>" href="<?= site_url('financeiro/lancamentos') ?>?status=0">
                <div class="pi-kpi-topo">
                    <span class="pi-kpi-rotulo">A receber vencido</span>
                    <i class="bx bx-money pi-kpi-icone" aria-hidden="true"></i>
                </div>
                <div class="pi-kpi-valor"><?= $moeda($pi_pendentes['receita_vencida']) ?></div>
                <div class="pi-kpi-extra"><?= $moeda($pi_pendentes['receita_avencer']) ?> ainda vai vencer</div>
            </a>
            <a class="pi-kpi <?= $pi_pendentes['despesa_vencida'] > 0 ? 'alerta' : '' ?>" href="<?= site_url('financeiro/lancamentos') ?>?tipo=despesa&status=0">
                <div class="pi-kpi-topo">
                    <span class="pi-kpi-rotulo">A pagar vencido</span>
                    <i class="bx bx-receipt pi-kpi-icone" aria-hidden="true"></i>
                </div>
                <div class="pi-kpi-valor"><?= $moeda($pi_pendentes['despesa_vencida']) ?></div>
                <div class="pi-kpi-extra"><?= $num($pi_pendentes['itens']) ?> lançamentos pendentes</div>
            </a>
        <?php } ?>

        <?php if ($pi_vendas_mes !== null) { ?>
            <a class="pi-kpi" href="<?= site_url('vendas') ?>">
                <div class="pi-kpi-topo">
                    <span class="pi-kpi-rotulo">Vendas do mês</span>
                    <i class="bx bx-cart-alt pi-kpi-icone" aria-hidden="true"></i>
                </div>
                <div class="pi-kpi-valor"><?= $num($pi_vendas_mes['quantidade']) ?></div>
                <div class="pi-kpi-extra"><?= $moeda($pi_vendas_mes['total']) ?> em vendas</div>
            </a>
        <?php } ?>

        <?php if ($pi_estoque !== null) { ?>
            <a class="pi-kpi <?= (int) $pi_estoque['total'] > 0 ? 'alerta' : 'sucesso' ?>" href="<?= site_url('produtos') ?>">
                <div class="pi-kpi-topo">
                    <span class="pi-kpi-rotulo">Estoque no mínimo</span>
                    <i class="bx bx-package pi-kpi-icone" aria-hidden="true"></i>
                </div>
                <div class="pi-kpi-valor"><?= $num($pi_estoque['total']) ?></div>
                <div class="pi-kpi-extra">produtos para repor</div>
            </a>
        <?php } ?>
    </div>
    <?php } ?>

    <?php // Perfil sem nenhuma permissao dos blocos: explica em vez de pagina vazia ?>
    <?php if (! $temConteudo) { ?>
        <div class="widget-box pi-vazio">
            <i class="bx bx-lock-alt"></i>
            <strong>Seu perfil ainda não tem acesso aos indicadores</strong>
            Peça ao administrador para liberar as permissões de OS, financeiro ou estoque. Enquanto isso, use o menu lateral.
        </div>
    <?php } ?>

    <?php if ($colunaEsquerda || $colunaDireita) { ?>
    <div class="pi-grade<?= $colunaEsquerda && $colunaDireita ? '' : ' uma-coluna' ?>">
        <?php if ($colunaEsquerda) { ?>
        <div class="pi-pilha">

            <!-- Precisa de você -->
            <?php if ($pode('vOs')) { ?>
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="bx bx-error"></i></span>
                        <h5>Precisa de você</h5>
                    </div>
                    <div class="widget-content">
                        <div class="pi-sec-topo">
                            <div class="pi-sec-titulo"><i class="bx bx-list-ul"></i> OS em aberto <span class="pi-sec-contagem">(<?= $num(count($pi_os)) ?>)</span></div>
                            <a class="pi-ver-tudo" href="<?= site_url('os') ?>">Ver todas <i class="bx bx-right-arrow-alt"></i></a>
                        </div>

                        <?php if (empty($pi_os)) { ?>
                            <div class="ui-vazio">
                                <i class="bx bx-check-circle"></i>
                                <strong>Nada pendente</strong>
                                Nenhuma OS em aberto agora. Bom momento para adiantar o resto.
                            </div>
                        <?php } else { ?>
                            <ul class="pi-lista">
                                <?php foreach ($pi_os as $os) {
                                    $dataCombinada = $os->data_agendamento ?? null;
                                    if (empty($dataCombinada)) {
                                        $dataCombinada = $os->dataFinal ?: $os->dataInicial;
                                    }
                                    $tom = Painel_inicio::tom($os->status);
                                    $fone = preg_replace('/\D/', '', (string) ($os->celular ?: $os->telefone));
                                    ?>
                                    <li>
                                        <div class="pi-os">
                                            <div class="pi-os-corpo">
                                                <div class="pi-os-titulo">
                                                    <span class="pi-os-num">OS #<?= (int) $os->idOs ?></span>
                                                    <span class="ui-selo <?= $e($tom) ?>"><i class="bx <?= $e(Painel_inicio::icone($os->status)) ?>"></i> <?= $e($os->status) ?></span>
                                                    <?= $rotuloPrazo($dataCombinada) ?>
                                                </div>
                                                <div class="pi-os-meta">
                                                    <span class="pi-os-cliente" title="<?= $e($os->nomeCliente) ?>"><i class="bx bx-user"></i> <?= $e($os->nomeCliente) ?></span>
                                                    <?php if (! empty($os->nome_tecnico)) { ?>
                                                        <span><i class="bx bx-wrench"></i> <?= $e($os->nome_tecnico) ?></span>
                                                    <?php } elseif (property_exists($os, 'tecnico_responsavel') && empty($os->tecnico_responsavel)) { ?>
                                                        <span><i class="bx bx-user-x"></i> Sem técnico</span>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <div class="pi-os-acoes">
                                                <a href="<?= site_url('os/visualizar/' . (int) $os->idOs) ?>" class="btn-nwe" title="Ver OS <?= (int) $os->idOs ?>" aria-label="Ver OS <?= (int) $os->idOs ?>"><i class="bx bx-show"></i></a>
                                                <?php if ($fone !== '') { ?>
                                                    <a href="https://wa.me/<?= $e(strlen($fone) <= 11 ? '55' . $fone : $fone) ?>" target="_blank" rel="noopener" class="btn-nwe3" title="WhatsApp do cliente" aria-label="WhatsApp do cliente"><i class="bx bxl-whatsapp"></i></a>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <!-- Situação das OS -->
            <?php if ($pode('vOs')) { ?>
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="bx bx-pie-chart-alt-2"></i></span>
                        <h5>Situação das OS</h5>
                    </div>
                    <div class="widget-content">
                        <div class="pi-status">
                            <?php foreach ($pi_status as $linha) { ?>
                                <a class="pi-status-item <?= $linha['total'] === 0 ? 'vazia' : '' ?>" href="<?= $linha['total'] === 0 ? site_url('os') : site_url('os/gerenciar') . '?status=' . rawurlencode($linha['status']) ?>">
                                    <span class="pi-status-nome"><?= $e($linha['status']) ?></span>
                                    <span class="pi-status-total"><?= $num($linha['total']) ?></span>
                                </a>
                            <?php } ?>
                        </div>

                        <?php if (! empty($pi_status_pontos)) { ?>
                            <figure class="pi-grafico" style="margin-top:14px">
                                <figcaption>Quantidade por status</figcaption>
                                <?= Grafico_svg::barrasHorizontais($pi_status_pontos, [
                                    'titulo' => 'OS por status atual',
                                    'descricao' => 'Barras horizontais com a quantidade de ordens de serviço em cada status.',
                                ]) ?>
                            </figure>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <!-- Fila de cobrança -->
            <?php if ($pi_pendentes !== null) { ?>
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="bx bx-time-five"></i></span>
                        <h5>Cobrança atrasada</h5>
                    </div>
                    <div class="widget-content">
                        <div class="pi-totais">
                            <div class="pi-total">
                                <div class="pi-total-rot">Receber vencido</div>
                                <div class="pi-total-val receita"><?= $moeda($pi_pendentes['receita_vencida']) ?></div>
                            </div>
                            <div class="pi-total">
                                <div class="pi-total-rot">Pagar vencido</div>
                                <div class="pi-total-val despesa"><?= $moeda($pi_pendentes['despesa_vencida']) ?></div>
                            </div>
                        </div>

                        <?php if (empty($pi_cobrar)) { ?>
                            <div class="ui-vazio" style="padding:18px 8px">
                                <i class="bx bx-check-circle"></i>
                                <strong>Nada vencido</strong>
                                Nenhum lançamento em atraso.
                            </div>
                        <?php } else { ?>
                            <ul class="pi-lista">
                                <?php foreach ($pi_cobrar as $lanc) {
                                    $liquido = Painel_inicio::valorLancamento($lanc); ?>
                                    <li>
                                        <div class="pi-lanc">
                                            <div class="pi-lanc-corpo">
                                                <div class="pi-lanc-desc" title="<?= $e($lanc->descricao) ?>"><?= $e($lanc->descricao ?: ucfirst($lanc->tipo)) ?></div>
                                                <div class="pi-lanc-quem">
                                                    <?= $e($lanc->cliente_fornecedor ?: '—') ?> ·
                                                    <?= $rotuloPrazo($lanc->data_vencimento) ?>
                                                </div>
                                            </div>
                                            <div class="pi-lanc-valor <?= $lanc->tipo === 'receita' ? 'receita' : 'despesa' ?>">
                                                <?= $moeda($liquido) ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php } ?>
                            </ul>
                            <div class="pi-rodape">
                                <a href="<?= site_url('financeiro/lancamentos') ?>?status=0">Abrir lançamentos pendentes <i class="bx bx-right-arrow-alt"></i></a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
        <?php } ?>

        <?php if ($colunaDireita) { ?>
        <div class="pi-pilha">
            <?php if ($pode('vOs')) { ?>
            <!-- Movimento -->
            <div class="widget-box">
                <div class="widget-title">
                    <span class="icon"><i class="bx bx-bar-chart-alt-2"></i></span>
                    <h5>Movimento dos últimos 14 dias</h5>
                </div>
                <div class="widget-content">
                    <figure class="pi-grafico">
                        <figcaption>OS abertas por dia</figcaption>
                        <?= Grafico_svg::colunas($pi_serie, [
                            'titulo' => 'OS abertas por dia',
                            'descricao' => 'Colunas com a quantidade de ordens de serviço abertas em cada um dos últimos 14 dias.',
                            'rotulos_x' => 7,
                        ]) ?>
                    </figure>
                    <div class="pi-rodape">
                        <?= $num($pi_movimento['os']) ?> OS e <?= $num($pi_movimento['vendas']) ?> vendas registradas hoje.
                    </div>
                </div>
            </div>
            <?php } ?>

            <!-- Financeiro do ano -->
            <?php if ($pi_mes !== null && ! empty($pi_meses)) { ?>
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="bx bx-line-chart"></i></span>
                        <h5>Resultado mês a mês</h5>
                    </div>
                    <div class="widget-content">
                        <form method="get" action="<?= site_url('mapos') ?>" class="pi-filtro-ano" style="justify-content:flex-end;margin-bottom:8px">
                            <label for="year" class="text-muted" style="font-size:12px;margin:0">Ano</label>
                            <input class="form-control" type="number" id="year" name="year" min="2000" max="2100" value="<?= (int) $pi_ano ?>" />
                            <button type="submit" class="btn btn-sm btn-outline-secondary" aria-label="Trocar ano"><i class="bx bx-refresh"></i></button>
                        </form>

                        <?php
                        $pontosMes = [];
                        $nomesMes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                        foreach ($pi_meses as $m) {
                            $pontosMes[] = [
                                'rotulo' => $nomesMes[$m['mes'] - 1],
                                'valor' => $m['liquido'],
                                'dica' => $nomesMes[$m['mes'] - 1] . ' de ' . $pi_ano . "\n"
                                    . 'Receita: ' . $moeda($m['receita']) . "\n"
                                    . 'Despesa: ' . $moeda($m['despesa']) . "\n"
                                    . 'Resultado: ' . $moeda($m['liquido']),
                            ];
                        }
                        ?>
                        <figure class="pi-grafico">
                            <figcaption>Resultado líquido (receita menos despesa)</figcaption>
                            <?= Grafico_svg::colunas($pontosMes, [
                                'titulo' => 'Resultado mensal de ' . $pi_ano,
                                'descricao' => 'Colunas com o resultado líquido de cada mês do ano escolhido.',
                                'rotulos_x' => 12,
                            ]) ?>
                            <details class="pi-grafico-tabela">
                                <summary>Ver como tabela</summary>
                                <div class="ui-tabela-rolagem">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr><th>Mês</th><th>Receita</th><th>Despesa</th><th>Resultado</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pi_meses as $m) { ?>
                                                <tr>
                                                    <td><?= $e($nomesMes[$m['mes'] - 1]) ?></td>
                                                    <td><?= $moeda($m['receita']) ?></td>
                                                    <td><?= $moeda($m['despesa']) ?></td>
                                                    <td class="<?= $m['liquido'] >= 0 ? 'ui-positivo' : 'ui-negativo' ?>"><?= $moeda($m['liquido']) ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        </figure>
                    </div>
                </div>
            <?php } ?>

            <!-- Estoque -->
            <?php if ($pi_estoque !== null) { ?>
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="bx bx-package"></i></span>
                        <h5>Repor estoque</h5>
                    </div>
                    <div class="widget-content">
                        <?php if (empty($pi_estoque['itens'])) { ?>
                            <div class="ui-vazio" style="padding:18px 8px">
                                <i class="bx bx-check-circle"></i>
                                <strong>Estoque em dia</strong>
                                Nenhum produto abaixo do mínimo.
                            </div>
                        <?php } else { ?>
                            <ul class="pi-lista">
                                <?php foreach ($pi_estoque['itens'] as $p) {
                                    $minimo = max(1, (int) $p->estoqueMinimo);
                                    $pct = min(100, max(0, round((int) $p->estoque * 100 / $minimo))); ?>
                                    <li>
                                        <div class="pi-estoque">
                                            <div class="pi-estoque-corpo">
                                                <div class="pi-estoque-nome" title="<?= $e($p->descricao) ?>"><?= $e($p->descricao) ?></div>
                                                <div class="pi-estoque-nums"><?= $num($p->estoque) ?> em estoque · mínimo <?= $num($p->estoqueMinimo) ?></div>
                                            </div>
                                            <div class="pi-estoque-barra">
                                                <div class="ui-barra"><span style="width:<?= (int) $pct ?>%"></span></div>
                                            </div>
                                        </div>
                                    </li>
                                <?php } ?>
                            </ul>
                            <?php if ((int) $pi_estoque['total'] > count($pi_estoque['itens'])) { ?>
                                <div class="pi-rodape">
                                    <a href="<?= site_url('produtos') ?>">Ver os <?= $num($pi_estoque['total']) ?> produtos abaixo do mínimo <i class="bx bx-right-arrow-alt"></i></a>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <?php if ($temAtalhos) { ?>
                <!-- Atalhos -->
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="bx bx-grid-alt"></i></span>
                        <h5>Atalhos</h5>
                    </div>
                    <div class="widget-content">
                        <div class="pi-atalhos">
                            <?php foreach ($atalhos as [$rota, $icone, $texto]) { ?>
                                <a class="pi-atalho" href="<?= site_url($rota) ?>"><i class="bx <?= $e($icone) ?>"></i> <?= $e($texto) ?></a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
    <?php } ?>
</div>

<?php if ($temConteudo) { ?>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/grafico-svg.js') ?>?v=<?= asset_versao('assets/js/grafico-svg.js') ?>"></script>
<?php } ?>
