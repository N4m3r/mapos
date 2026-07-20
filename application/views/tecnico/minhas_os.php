<?php
$this->load->view('tecnico/_topo', [
    'titulo'       => 'Minhas OS',
    'header_icone' => 'bx-list-ul',
    'voltar_url'   => site_url('tecnico'),
]);

// Monta querystring preservando datas ao trocar de chip de status
$qs = function ($novoStatus) use ($data_inicio, $data_fim) {
    $p = ['status' => $novoStatus];
    if (!empty($data_inicio)) { $p['data_inicio'] = $data_inicio; }
    if (!empty($data_fim)) { $p['data_fim'] = $data_fim; }
    return site_url('tecnico/os') . '?' . http_build_query($p);
};
$chips = ['todos' => 'Todos', 'pendente' => 'Pendentes', 'em_andamento' => 'Em andamento', 'finalizado' => 'Finalizados', 'nao_realizado' => 'Não realizados'];

// Helper: telefone -> digitos para WhatsApp (assume Brasil se sem DDI)
$waNumero = function ($fone) {
    $d = preg_replace('/\D/', '', $fone);
    if ($d === '') { return ''; }
    if (strlen($d) <= 11) { $d = '55' . $d; }
    return $d;
};
?>

<div class="tec-container">

    <?php if (!empty($pode_criar_atividade)): ?>
        <a href="<?= site_url('tecnico/nova_atividade') ?>" class="btn-tec primary block lg" style="margin-bottom:14px;">
            <i class='bx bx-plus-circle'></i> Nova Atividade não programada
        </a>
    <?php endif; ?>

    <input type="text" id="buscaOs" class="tec-search" placeholder="🔎 Buscar por nº da OS ou cliente...">

    <div class="chips">
        <?php foreach ($chips as $val => $rot): ?>
            <a href="<?= $qs($val) ?>" class="chip <?= $status === $val ? 'active' : '' ?>"><?= $rot ?></a>
        <?php endforeach; ?>
    </div>

    <details class="tec-adv" <?= (!empty($data_inicio) || !empty($data_fim)) ? 'open' : '' ?>>
        <summary><i class='bx bx-slider-alt'></i> Filtrar por data</summary>
        <form method="get" action="<?= site_url('tecnico/os') ?>">
            <input type="hidden" name="status" value="<?= html_escape($status) ?>">
            <div class="adv-grid">
                <div>
                    <label>Data início</label>
                    <input type="date" name="data_inicio" value="<?= html_escape($data_inicio) ?>">
                </div>
                <div>
                    <label>Data fim</label>
                    <input type="date" name="data_fim" value="<?= html_escape($data_fim) ?>">
                </div>
            </div>
            <button type="submit" class="btn-tec primary block" style="margin-top:10px;"><i class='bx bx-filter'></i> Aplicar</button>
        </form>
    </details>

    <div id="listaOs">
        <?php if (!empty($ordens)): ?>
            <?php foreach ($ordens as $os):
                $classe = 'pendente';
                if ($os->status == 'Em Andamento') { $classe = 'andamento'; }
                elseif (in_array($os->status, ['Finalizado', 'Faturado'])) { $classe = 'finalizado'; }
                elseif ($os->status == 'Não Realizado') { $classe = 'nao-realizado'; }
                $fone = !empty($os->celular) ? $os->celular : (!empty($os->telefone) ? $os->telefone : '');
                $wa = $waNumero($fone);
            ?>
                <div class="os-card <?= $classe ?>" data-busca="<?= html_escape(strtolower(sprintf('%04d', $os->idOs) . ' ' . $os->nomeCliente)) ?>">
                    <div class="os-head">
                        <span class="os-num">#OS <?= sprintf('%04d', $os->idOs) ?>
                            <?php if (!empty($os->nao_programada)): ?>
                                <span class="badge-status andamento" style="font-size:10px;"><i class='bx bx-bolt-circle'></i> Não programada</span>
                            <?php endif; ?>
                        </span>
                        <span class="badge-status <?= $classe ?>"><?= $os->status ?></span>
                    </div>
                    <div class="os-cliente">
                        <i class='bx bx-user'></i> <?= $os->nomeCliente ?>
                    </div>
                    <div class="os-desc"><?= character_limiter(strip_tags($os->descricaoProduto), 100) ?></div>

                    <?php if ($fone): ?>
                        <div class="quick-actions" style="margin-bottom:11px;">
                            <a href="tel:<?= preg_replace('/[^\d+]/', '', $fone) ?>" class="btn-tec qa-call"><i class='bx bx-phone'></i> Ligar</a>
                            <?php if ($wa): ?>
                                <a href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener" class="btn-tec qa-whats"><i class='bx bxl-whatsapp'></i> WhatsApp</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="os-foot">
                        <span class="os-meta">
                            <span><i class='bx bx-calendar'></i> <?= date('d/m/Y', strtotime($os->dataInicial)) ?></span>
                            <span><i class='bx bx-time'></i> <?= date('H:i', strtotime($os->dataInicial)) ?></span>
                        </span>
                        <span class="os-actions">
                            <a href="<?= site_url('checkin/imprimir/' . $os->idOs) ?>" target="_blank" rel="noopener" class="btn-tec neutral"><i class='bx bx-time'></i> Relatório</a>
                            <a href="<?= site_url('tecnico/visualizar/' . $os->idOs) ?>" class="btn-tec primary"><i class='bx bx-show'></i> Abrir</a>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class='bx bx-inbox'></i>
                <h3>Nenhuma OS encontrada</h3>
                <p>Você não possui ordens de serviço para este filtro.</p>
            </div>
        <?php endif; ?>
    </div>

    <div id="semResultado" class="empty-state" style="display:none;">
        <i class='bx bx-search-alt'></i>
        <h3>Nada encontrado</h3>
        <p>Nenhuma OS corresponde à sua busca.</p>
    </div>
</div>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => 'os', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>

<script>
(function () {
    var input = document.getElementById('buscaOs');
    if (!input) return;
    var cards = [].slice.call(document.querySelectorAll('#listaOs .os-card'));
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
