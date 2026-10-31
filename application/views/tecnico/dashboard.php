<?php
$this->load->helper('tecnico_inicio');
$hora = (int) date('H');
$saud = ($hora < 12) ? 'Bom dia' : (($hora < 18) ? 'Boa tarde' : 'Boa noite');
$nome = isset($nome_tecnico) ? $nome_tecnico : $this->session->userdata('nome_admin');
$primeiro = trim(explode(' ', trim((string) $nome))[0]);
$dias = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
$dataExtenso = ucfirst($dias[(int) date('w')]) . ', ' . date('d/m/Y');

$this->load->view('tecnico/_topo', [
    'titulo'       => 'Área do Técnico',
    'header_icone' => 'bxs-user-circle',
    'header_sub'   => $saud . ', ' . $primeiro . '!',
]);

$filaQtd = (int) ($fila_qtd ?? 0);
$nrQtd = (int) ($nao_realizadas_pendentes ?? 0);
$atrQtd = (int) ($qtd_atrasadas ?? 0);
$prox = $proxima ?? null;
?>
<link rel="stylesheet" href="<?= base_url('assets/css/tecnico-inicio.css') ?>?v=<?= asset_versao('assets/css/tecnico-inicio.css', '1') ?>">

<div class="tec-container ti">

    <div class="ti-saud">
        <h1><?= html_escape($saud . ', ' . $primeiro) ?>!</h1>
        <p><i class='bx bx-calendar'></i> <?= html_escape($dataExtenso) ?></p>
    </div>

    <?php if ($prox):
        $os = $prox['os'];
        $continuar = !empty($prox['continuar']);
        $hr = TecnicoInicio::horaVisita($os);
        $dEf = (string) TecnicoInicio::dataEfetiva($os);
        $ehHoje = substr($dEf, 0, 10) === $hoje;
        $quando = $ehHoje ? 'hoje' : date('d/m/Y', strtotime($dEf));
        $quando = $hr ? ($quando === 'hoje' ? 'Hoje às ' . $hr : $quando . ' às ' . $hr) : ($ehHoje ? 'Hoje' : $quando);
        $end = TecnicoInicio::enderecoCurto($os);
        $rota = TecnicoInicio::urlRota($os);
        $tipo = trim((string) ($os->tipo_servico ?? ''));
        if ($tipo !== '') { $this->load->helper('tipo_servico'); if (function_exists('tipo_servico_valido') && tipo_servico_valido($tipo)) { $tipo = tipo_servico_rotulo($tipo); } }
    ?>
        <section class="ti-prox <?= $continuar ? 'andamento' : '' ?>" aria-label="Próxima visita">
            <div class="ti-prox-tit">
                <i class='bx <?= $continuar ? 'bx-play-circle' : 'bx-navigation' ?>'></i>
                <?= $continuar ? 'Atendimento em andamento' : 'Próxima visita' ?>
            </div>
            <h2 class="ti-prox-cli"><?= html_escape($os->nomeCliente ?: 'Cliente') ?></h2>
            <div class="ti-prox-info"><i class='bx bx-time-five'></i> <span><?= html_escape($quando) ?> &middot; OS <?= sprintf('%04d', (int) $os->idOs) ?></span></div>
            <?php if ($end !== ''): ?>
                <div class="ti-prox-info"><i class='bx bx-map'></i> <span><?= html_escape($end) ?></span></div>
            <?php endif; ?>
            <?php if ($tipo !== ''): ?>
                <div class="ti-prox-info"><i class='bx bx-wrench'></i> <span><?= html_escape($tipo) ?></span></div>
            <?php endif; ?>
            <div class="ti-prox-acoes">
                <a class="ti-btn <?= $continuar ? 'ok' : 'main' ?>" href="<?= site_url('tecnico/visualizar/' . (int) $os->idOs) ?>">
                    <i class='bx <?= $continuar ? 'bx-play' : 'bx-right-arrow-circle' ?>'></i>
                    <?= $continuar ? 'Continuar atendimento' : 'Abrir atendimento' ?>
                </a>
                <?php if ($rota !== ''): ?>
                    <a class="ti-btn alt" href="<?= html_escape($rota) ?>" target="_blank" rel="noopener">
                        <i class='bx bx-map-pin'></i> Ir até lá
                    </a>
                <?php endif; ?>
                <?php if (!$continuar): ?>
                    <button type="button" class="ti-btn soft" data-a-caminho="<?= (int) $os->idOs ?>"><i class='bx bx-car'></i> Estou a caminho</button>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <section class="ti-vazio-prox">
            <i class='bx bx-check-circle'></i>
            <p>Nenhuma visita marcada para você agora.</p>
            <a class="ti-btn soft" href="<?= site_url('tecnico/os?aba=proximos') ?>"><i class='bx bx-calendar'></i> Ver próximos dias</a>
        </section>
    <?php endif; ?>

    <?php if ($atrQtd > 0 || $nrQtd > 0 || $filaQtd > 0): ?>
        <h2 class="ti-sec"><i class='bx bx-bell'></i> Precisa da sua atenção</h2>
        <?php if ($atrQtd > 0): ?>
            <a class="ti-aviso bad" href="<?= site_url('tecnico/os?aba=atrasadas') ?>">
                <i class='bx bx-error'></i><span class="txt">Visitas atrasadas</span><b class="n"><?= $atrQtd ?></b>
            </a>
        <?php endif; ?>
        <?php if ($nrQtd > 0): ?>
            <a class="ti-aviso bad" href="<?= site_url('tecnico/nao_realizadas') ?>">
                <i class='bx bx-calendar-x'></i><span class="txt">Não deu para fazer: reagendar</span><b class="n"><?= $nrQtd ?></b>
            </a>
        <?php endif; ?>
        <?php if ($filaQtd > 0): ?>
            <a class="ti-aviso" href="<?= site_url('tecnico/fila') ?>">
                <i class='bx bx-transfer-alt'></i><span class="txt">Pontos de projeto esperando você</span><b class="n"><?= $filaQtd ?></b>
            </a>
        <?php endif; ?>
    <?php endif; ?>

    <h2 class="ti-sec"><i class='bx bx-list-check'></i> Hoje</h2>
    <?php if (!empty($do_dia)): ?>
        <ul class="ti-tempo">
            <?php foreach ($do_dia as $o):
                [$rotTxt, $rotCls, $rotIco] = TecnicoInicio::rotuloOs($o, $hoje);
                $h = TecnicoInicio::horaVisita($o);
            ?>
                <li>
                    <a class="ti-linha" href="<?= site_url('tecnico/visualizar/' . (int) $o->idOs) ?>">
                        <span class="ti-hora"><?= $h ? html_escape($h) : '--:--' ?></span>
                        <span class="ti-linha-cli"><?= html_escape($o->nomeCliente ?: 'Cliente') ?></span>
                        <span class="ti-selo <?= html_escape($rotCls) ?>"><i class='bx <?= html_escape($rotIco) ?>'></i> <?= html_escape($rotTxt) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="ti-dica">Nenhuma visita hoje.</p>
    <?php endif; ?>

    <div class="ti-nums" aria-label="Números do dia">
        <span><b><?= count($do_dia ?? []) ?></b> visitas hoje</span>
        <span><b><?= (int) ($qtd_concluidas_hoje ?? 0) ?></b> concluídas</span>
    </div>

    <?php if (!empty($pode_criar_atividade)): ?>
        <a href="<?= site_url('tecnico/nova_atividade') ?>" class="ti-btn soft ti-mais"><i class='bx bx-plus-circle'></i> Nova atividade não programada</a>
    <?php endif; ?>
    <a href="<?= site_url('tecnico/os') ?>" class="ti-btn soft ti-mais"><i class='bx bx-list-ul'></i> Ver todas as minhas OS</a>
</div>

<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/tecnico-a-caminho.js') ?>?v=<?= asset_versao('assets/js/tecnico-a-caminho.js', '1') ?>" data-url="<?= site_url('tecnico/a_caminho') ?>"></script>
<?php $this->load->view('tecnico/_nav', ['nav_ativo' => 'home', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>
</body>
</html>
