<?php
$this->load->helper('tecnico_inicio');
$this->load->view('tecnico/_topo', [
    'titulo'       => 'Minhas OS',
    'header_icone' => 'bx-list-ul',
    'voltar_url'   => site_url('tecnico'),
]);

$vaziosMsg = [
    'hoje'       => 'Nenhuma visita hoje.',
    'proximos'   => 'Nenhuma visita nos próximos dias.',
    'atrasadas'  => 'Nenhuma visita atrasada. Tudo em dia!',
    'concluidas' => 'Nenhuma visita concluída recentemente.',
];
?>
<link rel="stylesheet" href="<?= base_url('assets/css/tecnico-inicio.css') ?>?v=<?= asset_versao('assets/css/tecnico-inicio.css', '1') ?>">

<div class="tec-container ti">

    <nav class="ti-abas" aria-label="Filtrar visitas">
        <?php foreach (TecnicoInicio::ABAS as $k => $rot): ?>
            <a href="<?= site_url('tecnico/os?aba=' . $k) ?>" class="ti-aba <?= $aba === $k ? 'on' : '' ?> <?= ($k === 'atrasadas' && !empty($contagens[$k])) ? 'alerta' : '' ?>" <?= $aba === $k ? 'aria-current="page"' : '' ?>>
                <?= html_escape($rot) ?> <span class="n"><?= (int) ($contagens[$k] ?? 0) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <input type="search" id="buscaOs" class="ti-busca" placeholder="Buscar cliente, número ou endereço" aria-label="Buscar cliente, número ou endereço" autocomplete="off">

    <div id="listaOs">
        <?php foreach ($ordens as $os):
            [$rotTxt, $rotCls, $rotIco] = TecnicoInicio::rotuloOs($os, $hoje);
            $h = TecnicoInicio::horaVisita($os);
            $dEf = (string) TecnicoInicio::dataEfetiva($os);
            $quando = $dEf !== '' ? date('d/m', strtotime($dEf)) . ($h ? ' às ' . $h : '') : 'Sem data';
            $end = TecnicoInicio::enderecoCurto($os);
            $tipo = trim((string) ($os->tipo_servico ?? ''));
        if ($tipo !== '') { $this->load->helper('tipo_servico'); if (function_exists('tipo_servico_valido') && tipo_servico_valido($tipo)) { $tipo = tipo_servico_rotulo($tipo); } }
            $fone = !empty($os->celular) ? $os->celular : (!empty($os->telefone) ? $os->telefone : '');
            $foneTel = preg_replace('/[^\d+]/', '', (string) $fone);
            $wa = TecnicoInicio::foneWhats($fone);
        ?>
            <article class="ti-os <?= html_escape($rotCls) ?>" data-busca="<?= html_escape(TecnicoInicio::textoBusca($os)) ?>">
                <div class="ti-os-topo">
                    <span class="ti-os-hora"><?= html_escape($quando) ?></span>
                    <span class="ti-selo <?= html_escape($rotCls) ?>"><i class='bx <?= html_escape($rotIco) ?>'></i> <?= html_escape($rotTxt) ?></span>
                </div>
                <h3 class="ti-os-cli"><?= html_escape($os->nomeCliente ?: 'Cliente') ?> <small>(OS <?= sprintf('%04d', (int) $os->idOs) ?>)</small></h3>
                <?php if ($end !== ''): ?>
                    <div class="ti-os-lin"><i class='bx bx-map'></i> <span><?= html_escape($end) ?></span></div>
                <?php endif; ?>
                <?php if ($tipo !== ''): ?>
                    <div class="ti-os-lin"><i class='bx bx-wrench'></i> <span><?= html_escape($tipo) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($os->nao_programada)): ?>
                    <div class="ti-os-lin"><i class='bx bx-bolt-circle'></i> <span>Atividade não programada</span></div>
                <?php endif; ?>
                <div class="ti-os-acoes">
                    <a class="ti-btn main abrir" href="<?= site_url('tecnico/visualizar/' . (int) $os->idOs) ?>"><i class='bx bx-right-arrow-circle'></i> Abrir</a>
                    <?php if ($foneTel !== ''): ?>
                        <a class="ti-btn soft" href="tel:<?= html_escape($foneTel) ?>"><i class='bx bx-phone'></i> Ligar</a>
                    <?php endif; ?>
                    <?php if ($wa !== ''): ?>
                        <a class="ti-btn soft" href="https://wa.me/<?= html_escape($wa) ?>" target="_blank" rel="noopener"><i class='bx bxl-whatsapp'></i> WhatsApp</a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (empty($ordens)): ?>
        <div class="ti-vazio">
            <i class='bx bx-calendar-check'></i>
            <p><?= html_escape($vaziosMsg[$aba]) ?></p>
            <?php if ($aba === 'hoje'): ?>
                <a class="ti-btn soft" href="<?= site_url('tecnico/os?aba=proximos') ?>"><i class='bx bx-calendar'></i> Ver os próximos dias</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div id="semResultado" class="ti-vazio" style="display:none;">
        <i class='bx bx-search-alt'></i>
        <p>Nada encontrado para essa busca.</p>
    </div>

    <?php if (!empty($pode_criar_atividade)): ?>
        <a href="<?= site_url('tecnico/nova_atividade') ?>" class="ti-btn soft ti-mais"><i class='bx bx-plus-circle'></i> Nova atividade não programada</a>
    <?php endif; ?>
</div>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => 'os', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>

<script nonce="<?= csp_nonce() ?>">
(function () {
    var input = document.getElementById('buscaOs');
    if (!input) return;
    var cards = [].slice.call(document.querySelectorAll('#listaOs .ti-os'));
    var vazio = document.getElementById('semResultado');
    input.addEventListener('input', function () {
        var termo = this.value.trim().toLowerCase();
        var visiveis = 0;
        cards.forEach(function (c) {
            var ok = !termo || (c.getAttribute('data-busca') || '').indexOf(termo) !== -1;
            c.style.display = ok ? '' : 'none';
            if (ok) visiveis++;
        });
        if (vazio) vazio.style.display = (cards.length && visiveis === 0) ? '' : 'none';
    });
})();
</script>
</body>
</html>
