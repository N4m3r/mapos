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

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            margin-bottom: 20px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 22px;
        }

        .filter-bar {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .os-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid #ddd;
            transition: transform 0.2s;
        }

        .os-card:hover {
            transform: translateX(5px);
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
            color: #333;
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

        .os-cliente i {
            color: #667eea;
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
            flex-wrap: wrap;
            gap: 10px;
        }

        .os-info {
            font-size: 12px;
            color: #999;
        }

        .os-info span {
            margin-right: 15px;
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

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 15px;
            color: #ddd;
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
    <div class="page-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class='bx bx-list-ul'></i> Minhas Ordens de Serviço</h1>
                    </div>
                    <a href="<?= base_url('index.php/tecnico') ?>" class="btn btn-light">
                        <i class='bx bx-arrow-back'></i> Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid pb-5">
        <!-- Filtros -->
        <div class="filter-bar">
            <form method="get" action="<?= base_url('index.php/tecnico/os') ?>" class="row">
                <div class="col-12 col-md-3 mb-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="todos" <?= $status == 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="pendente" <?= $status == 'pendente' ? 'selected' : '' ?>>Pendentes</option>
                        <option value="em_andamento" <?= $status == 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="finalizado" <?= $status == 'finalizado' ? 'selected' : '' ?>>Finalizados</option>
                    </select>
                </div>

                <div class="col-6 col-md-3 mb-2">
                    <label class="form-label">Data Início</label>
                    <input type="date" name="data_inicio" class="form-control" value="<?= $data_inicio ?>">
                </div>

                <div class="col-6 col-md-3 mb-2">
                    <label class="form-label">Data Fim</label>
                    <input type="date" name="data_fim" class="form-control" value="<?= $data_fim ?>">
                </div>

                <div class="col-12 col-md-3 mb-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class='bx bx-filter'></i> Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de OS -->
        <?php if (!empty($ordens)): ?>
            <?php foreach ($ordens as $os): ?>
                <?php
                // Definir classe baseada no status
                $classe_status = 'pendente';
                $texto_status = $os->status;

                if ($os->status == 'Em Andamento') {
                    $classe_status = 'andamento';
                } elseif (in_array($os->status, ['Finalizado', 'Faturado'])) {
                    $classe_status = 'finalizado';
                }
                ?>

                <div class="os-card <?= $classe_status ?>">
                    <div class="os-header">
                        <span class="os-number">#OS <?= sprintf('%04d', $os->idOs) ?></span>
                        <span class="os-status <?= $classe_status ?>"><?= $texto_status ?></span>
                    </div>

                    <div class="os-cliente">
                        <i class='bx bx-user'></i> <?= $os->nomeCliente ?>
                        <?php if ($os->telefone || $os->celular): ?>
                            <span class="ms-2">
                                <i class='bx bx-phone'></i> <?= $os->celular ?: $os->telefone ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="os-descricao">
                        <?= character_limiter(strip_tags($os->descricaoProduto), 100) ?>
                    </div>

                    <div class="os-footer">
                        <div class="os-info">
                            <span>
                                <i class='bx bx-calendar'></i>
                                <?= date('d/m/Y', strtotime($os->dataInicial)) ?>
                            </span>

                            <span>
                                <i class='bx bx-time'></i>
                                <?= date('H:i', strtotime($os->dataInicial)) ?>
                            </span>
                        </div>

                        <a href="<?= base_url('index.php/tecnico/visualizar/' . $os->idOs) ?>" class="btn-acao btn btn-primary">
                            <i class='bx bx-show'></i> Visualizar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?
            <div class="empty-state">
                <i class='bx bx-inbox'></i>
                <h3>Nenhuma OS encontrada</h3>
                <p>Você não possui ordens de serviço designadas no momento.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Menu Mobile -->
    <div class="mobile-menu">
        <a href="<?= base_url('index.php/tecnico') ?>">
            <i class='bx bxs-dashboard'></i>
            <span>Início</span>
        </a>

        <a href="<?= base_url('index.php/tecnico/os') ?>" class="active">
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