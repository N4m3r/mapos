<?php
$hora = date('H');
$saud = ($hora < 12) ? 'Bom dia' : (($hora < 18) ? 'Boa tarde' : 'Boa noite');
$nome = isset($nome_tecnico) ? $nome_tecnico : $this->session->userdata('nome_admin');
$this->load->view('tecnico/_topo', [
    'titulo'       => 'Área do Técnico',
    'header_icone' => 'bxs-user-circle',
    'header_sub'   => $saud . ', ' . $nome . '!',
]);
?>

<div class="tec-container">

    <?php if (!empty($pode_criar_atividade)): ?>
        <a href="<?= site_url('tecnico/nova_atividade') ?>" class="btn-tec primary block lg" style="margin-bottom:18px;">
            <i class='bx bx-plus-circle'></i> Nova Atividade não programada
        </a>
    <?php endif; ?>

    <!-- Estatisticas -->
    <div class="tec-stats">
        <div class="tec-stat">
            <div class="ic primary"><i class='bx bx-calendar-check'></i></div>
            <div class="val"><?= count($os_hoje) ?></div>
            <div class="lbl">OS de hoje</div>
        </div>
        <div class="tec-stat">
            <div class="ic warning"><i class='bx bx-time'></i></div>
            <div class="val"><?= count($os_pendentes) ?></div>
            <div class="lbl">Pendentes</div>
        </div>
        <div class="tec-stat">
            <div class="ic danger"><i class='bx bx-loader-alt bx-spin'></i></div>
            <div class="val"><?= count($os_em_andamento) ?></div>
            <div class="lbl">Em andamento</div>
        </div>
        <div class="tec-stat">
            <div class="ic success"><i class='bx bx-check-circle'></i></div>
            <div class="val"><?= isset($estatisticas['os_finalizadas_mes']) ? $estatisticas['os_finalizadas_mes'] : 0 ?></div>
            <div class="lbl">Finalizadas (mês)</div>
        </div>
    </div>

    <!-- OS em Andamento -->
    <h2 class="tec-section-title">
        <i class='bx bx-play-circle'></i> Em andamento
        <?php if (!empty($os_em_andamento)): ?><span class="count"><?= count($os_em_andamento) ?></span><?php endif; ?>
    </h2>

    <?php if (!empty($os_em_andamento)): ?>
        <?php foreach ($os_em_andamento as $os): ?>
            <a href="<?= site_url('tecnico/visualizar/' . $os->idOs) ?>" style="text-decoration:none; color:inherit; display:block;">
                <div class="os-card andamento">
                    <div class="os-head">
                        <span class="os-num">#OS <?= sprintf('%04d', $os->idOs) ?></span>
                        <span class="badge-status andamento">Em Andamento</span>
                    </div>
                    <div class="os-cliente"><i class='bx bx-user'></i> <?= $os->nomeCliente ?></div>
                    <div class="os-desc"><?= character_limiter(strip_tags($os->descricaoProduto), 80) ?></div>
                    <div class="os-foot">
                        <span class="os-meta">
                            <span><i class='bx bx-time'></i> Iniciada <?= !empty($os->data_entrada) ? date('H:i', strtotime($os->data_entrada)) : '--:--' ?></span>
                        </span>
                        <span class="btn-tec primary">Continuar <i class='bx bx-right-arrow-alt'></i></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bx-check-circle'></i>
            <p>Nenhuma OS em andamento</p>
        </div>
    <?php endif; ?>

    <!-- Serviços não realizados (aguardando reagendar/refazer) -->
    <?php if (!empty($nao_realizadas_pendentes)): ?>
        <h2 class="tec-section-title">
            <i class='bx bx-x-circle'></i> Não realizados
            <span class="count" style="background:var(--tec-danger); color:#fff;"><?= (int) $nao_realizadas_pendentes ?></span>
        </h2>

        <?php foreach ($nao_realizadas as $nr): ?>
            <a href="<?= site_url('tecnico/visualizar/' . $nr->idOs) ?>" style="text-decoration:none; color:inherit; display:block;">
                <div class="os-card nao-realizado">
                    <div class="os-head">
                        <span class="os-num">#OS <?= sprintf('%04d', $nr->idOs) ?></span>
                        <span class="badge-status nao-realizado">Não Realizado</span>
                    </div>
                    <div class="os-cliente"><i class='bx bx-user'></i> <?= html_escape($nr->nomeCliente ?: 'Cliente') ?></div>
                    <?php if (!empty($nr->motivo_texto)): ?>
                        <div class="os-desc"><strong>Motivo:</strong> <?= html_escape($nr->motivo_texto) ?></div>
                    <?php endif; ?>
                    <div class="os-foot">
                        <span class="os-meta">
                            <span><i class='bx bx-time'></i> <?= !empty($nr->data_registro) ? date('d/m/Y H:i', strtotime($nr->data_registro)) : '--' ?></span>
                        </span>
                        <span class="btn-tec danger">Resolver <i class='bx bx-right-arrow-alt'></i></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>

        <?php if ($nao_realizadas_pendentes > count($nao_realizadas)): ?>
            <a href="<?= site_url('tecnico/nao_realizadas') ?>" class="btn-tec ghost block" style="margin-top:4px;">
                Ver todos os não realizados (<?= (int) $nao_realizadas_pendentes ?>)
            </a>
        <?php endif; ?>
    <?php endif; ?>

    <!-- OS Pendentes -->
    <h2 class="tec-section-title">
        <i class='bx bx-time'></i> Pendentes
        <?php if (!empty($os_pendentes)): ?><span class="count"><?= count($os_pendentes) ?></span><?php endif; ?>
    </h2>

    <?php if (!empty($os_pendentes)): ?>
        <?php foreach (array_slice($os_pendentes, 0, 3) as $os): ?>
            <a href="<?= site_url('tecnico/visualizar/' . $os->idOs) ?>" style="text-decoration:none; color:inherit; display:block;">
                <div class="os-card pendente">
                    <div class="os-head">
                        <span class="os-num">#OS <?= sprintf('%04d', $os->idOs) ?></span>
                        <span class="badge-status pendente"><?= $os->status ?></span>
                    </div>
                    <div class="os-cliente"><i class='bx bx-user'></i> <?= $os->nomeCliente ?></div>
                    <div class="os-desc"><?= character_limiter(strip_tags($os->descricaoProduto), 80) ?></div>
                    <div class="os-foot">
                        <span class="os-meta">
                            <span><i class='bx bx-calendar'></i> <?= date('d/m/Y', strtotime($os->dataInicial)) ?></span>
                        </span>
                        <span class="btn-tec warning">Iniciar <i class='bx bx-play'></i></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>

        <?php if (count($os_pendentes) > 3): ?>
            <a href="<?= site_url('tecnico/os?status=pendente') ?>" class="btn-tec ghost block" style="margin-top:4px;">
                Ver todas as pendentes (<?= count($os_pendentes) ?>)
            </a>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bx-inbox'></i>
            <p>Nenhuma OS pendente</p>
        </div>
    <?php endif; ?>

    <a href="<?= site_url('tecnico/os') ?>" class="btn-tec primary block lg" style="margin-top:22px;">
        <i class='bx bx-list-ul'></i> Ver todas as minhas OS
    </a>
</div>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => 'home', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>
</body>
</html>
