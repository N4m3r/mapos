<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/boxicons.min.css') ?>">
    <style>
        body {
            background: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            margin-bottom: 20px;
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .dashboard-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .stat-icon.primary { background: #e3f2fd; color: #1976d2; }
        .stat-icon.success { background: #e8f5e9; color: #388e3c; }
        .stat-icon.warning { background: #fff3e0; color: #f57c00; }
        .stat-icon.danger { background: #fce4ec; color: #c2185b; }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .os-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid #ddd;
        }

        .os-card.pendente { border-left-color: #f57c00; }
        .os-card.andamento { border-left-color: #1976d2; }
        .os-card.finalizado { border-left-color: #388e3c; }

        .os-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .os-number {
            font-weight: bold;
            font-size: 16px;
        }

        .os-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .os-status.pendente { background: #fff3e0; color: #f57c00; }
        .os-status.andamento { background: #e3f2fd; color: #1976d2; }
        .os-status.finalizado { background: #e8f5e9; color: #388e3c; }

        .os-cliente {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .os-descricao {
            color: #333;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .os-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .os-data {
            font-size: 12px;
            color: #999;
        }

        .btn-acao {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 10px;
        }

        /* Menu lateral mobile */
        .mobile-menu {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-around;
            padding: 10px;
            z-index: 1000;
        }

        .mobile-menu a {
            color: #666;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 12px;
        }

        .mobile-menu a.active {
            color: #667eea;
        }

        .mobile-menu i {
            font-size: 20px;
            margin-bottom: 2px;
        }

        /* Desktop */
        @media (min-width: 768px) {
            .mobile-menu {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class='bx bxs-user-circle'></i> Área do Técnico</h1>
                        <p>Bem-vindo, <?= $this->session->userdata('nome') ?></p>
                    </div>
                    <a href="<?= base_url() ?>" class="btn btn-light">
                        <i class='bx bx-arrow-back'></i> Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid pb-5">
        <!-- Estatisticas -->
        <div class="row">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class='bx bx-calendar-check'></i>
                    </div>
                    <div class="stat-value"><?= count($os_hoje) ?></div>
                    <div class="stat-label">OS Hoje</div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class='bx bx-time'></i>
                    </div>
                    <div class="stat-value"><?= count($os_pendentes) ?></div>
                    <div class="stat-label">Pendentes</div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class='bx bx-loader-alt bx-spin'></i>
                    </div>
                    <div class="stat-value"><?= count($os_em_andamento) ?></div>
                    <div class="stat-label">Em Andamento</div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class='bx bx-check-circle'></i>
                    </div>
                    <div class="stat-value"><?= $estatisticas['os_finalizadas_mes'] ?></div>
                    <div class="stat-label">Finalizadas (Mês)</div>
                </div>
            </div>
        </div>

        <!-- OS em Andamento (Prioridade) -->
        <div class="row mt-4">
            <div class="col-12">
                <h2 class="section-title">
                    <i class='bx bx-play-circle'></i> OS em Andamento
                </h2>

                <?php if (!empty($os_em_andamento)): ?>
                    <?php foreach ($os_em_andamento as $os): ?>
                        <div class="os-card andamento">
                            <div class="os-header">
                                <span class="os-number">#OS <?= sprintf('%04d', $os->idOs) ?></span>
                                <span class="os-status andamento">Em Andamento</span>
                            </div>

                            <div class="os-cliente">
                                <i class='bx bx-user'></i> <?= $os->nomeCliente ?>
                            </div>

                            <div class="os-descricao">
                                <?= character_limiter(strip_tags($os->descricaoProduto), 80) ?>
                            </div>

                            <div class="os-footer">
                                <span class="os-data">
                                    <i class='bx bx-time'></i>
                                    Iniciada <?= date('H:i', strtotime($os->data_entrada)) ?>
                                </span>

                                <a href="<?= base_url('index.php/tecnico/visualizar/' . $os->idOs) ?>" class="btn-acao btn btn-primary">
                                    Continuar <i class='bx bx-right-arrow-alt'></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?
                    <div class="empty-state">
                        <i class='bx bx-check-circle'></i>
                        <p>Nenhuma OS em andamento</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- OS Pendentes -->
        <div class="row mt-4">
            <div class="col-12">
                <h2 class="section-title">
                    <i class='bx bx-time'></i> OS Pendentes
                </h2>

                <?php if (!empty($os_pendentes)): ?>
                    <?php foreach (array_slice($os_pendentes, 0, 3) as $os): ?>
                        <div class="os-card pendente">
                            <div class="os-header">
                                <span class="os-number">#OS <?= sprintf('%04d', $os->idOs) ?></span>
                                <span class="os-status pendente"><?= $os->status ?></span>
                            </div>

                            <div class="os-cliente">
                                <i class='bx bx-user'></i> <?= $os->nomeCliente ?>
                            </div>

                            <div class="os-descricao">
                                <?= character_limiter(strip_tags($os->descricaoProduto), 80) ?>
                            </div>

                            <div class="os-footer">
                                <span class="os-data">
                                    <i class='bx bx-calendar'></i>
                                    <?= date('d/m/Y', strtotime($os->dataInicial)) ?>
                                </span>

                                <a href="<?= base_url('index.php/tecnico/visualizar/' . $os->idOs) ?>" class="btn-acao btn btn-warning">
                                    Iniciar <i class='bx bx-play'></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (count($os_pendentes) > 3): ?>
                        <div class="text-center mt-3">
                            <a href="<?= base_url('index.php/tecnico/os?status=pendente') ?>" class="btn btn-outline-primary">
                                Ver todas (<?= count($os_pendentes) ?>)
                            </a>
                        </div>
                    <?php endif; ?
003e
                <?php else: ?
                    <div class="empty-state">
                        <i class='bx bx-inbox'></i>
                        <p>Nenhuma OS pendente</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Botao Ver Todas -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="<?= base_url('index.php/tecnico/os') ?>" class="btn btn-primary btn-lg">
                    <i class='bx bx-list-ul'></i> Ver Todas as Minhas OS
                </a>
            </div>
        </div>
    </div>

    <!-- Menu Mobile -->
    <div class="mobile-menu">
        <a href="<?= base_url('index.php/tecnico') ?>" class="active">
            <i class='bx bxs-dashboard'></i>
            <span>Início</span>
        </a>

        <a href="<?= base_url('index.php/tecnico/os') ?>">
            <i class='bx bx-list-ul'></i>
            <span>Minhas OS</span>
        </a>

        <a href="<?= base_url() ?>">
            <i class='bx bx-home'></i>
            <span>Sistema</span>
        </a>
    </div>

    <script src="<?= base_url('assets/js/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.min.js') ?>"></script>
</body>
</html>