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

        .info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .info-card h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            min-width: 120px;
        }

        .info-value {
            color: #333;
            flex: 1;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-aberto { background: #fff3e0; color: #f57c00; }
        .status-andamento { background: #e3f2fd; color: #1976d2; }
        .status-finalizado { background: #e8f5e9; color: #388e3c; }
        .status-cancelado { background: #ffebee; color: #c62828; }

        .btn-acao {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-acao:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-iniciar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-finalizar {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-imprimir {
            background: #fff;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-voltar {
            background: #6c757d;
            color: white;
        }

        .foto-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .foto-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .foto-descricao {
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 8px;
            font-size: 12px;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
        }

        .assinatura-box {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .assinatura-box img {
            max-width: 100%;
            max-height: 120px;
        }

        .assinatura-box.sem-assinatura {
            color: #999;
            font-style: italic;
        }

        .acoes-bar {
            position: fixed;
            bottom: 70px;
            left: 0;
            right: 0;
            background: white;
            padding: 15px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: center;
            gap: 10px;
            z-index: 100;
        }

        .checkin-status {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .checkin-status h4 {
            margin: 0 0 10px 0;
            color: #2e7d32;
            font-size: 16px;
        }

        .checkin-status p {
            margin: 0;
            color: #666;
            font-size: 14px;
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

            .acoes-bar {
                position: static;
                background: transparent;
                box-shadow: none;
                padding: 0;
                margin-bottom: 20px;
            }
        }

        /* Timeline de fotos */
        .timeline-section {
            margin-bottom: 30px;
        }

        .timeline-title {
            font-size: 14px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .timeline-title i {
            font-size: 18px;
        }

        .fotos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
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
                        <h1><i class='bx bx-file'></i> OS #<?= sprintf('%04d', $os->idOs) ?></h1>
                    </div>
                    <a href="<?= base_url('index.php/tecnico/os') ?>" class="btn btn-light">
                        <i class='bx bx-arrow-back'></i> Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid pb-5">
        <!-- Status do Check-in -->
        <?php if ($checkin_ativo): ?>
            <div class="checkin-status">
                <h4><i class='bx bx-play-circle'></i> Atendimento em Andamento</h4>
                <p>
                    Iniciado em <?= date('d/m/Y \à\s H:i', strtotime($checkin_ativo->data_entrada)) ?>
                    <?php if ($checkin_ativo->localizacao_entrada): ?>
                        <br><i class='bx bx-map'></i> Localização registrada
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Ações -->
        <div class="acoes-bar">
            <?php if (!$checkin_ativo && $permissao_checkin && in_array($os->status, ['Aberto', 'Orçamento', 'Aprovado'])): ?>
                <a href="<?= base_url('index.php/checkin?os_id=' . $os->idOs) ?>" class="btn-acao btn-iniciar">
                    <i class='bx bx-play-circle'></i> Iniciar Atendimento
                </a>
            <?php endif; ?>

            <?php if ($checkin_ativo && $permissao_checkout): ?>
                <a href="<?= base_url('index.php/checkin/finalizar?os_id=' . $os->idOs) ?>" class="btn-acao btn-finalizar">
                    <i class='bx bx-stop-circle'></i> Finalizar Atendimento
                </a>
            <?php endif; ?>

            <a href="<?= base_url('index.php/os/visualizar/' . $os->idOs) ?>" class="btn-acao btn-imprimir" target="_blank">
                <i class='bx bx-printer'></i> Imprimir OS
            </a>
        </div>

        <!-- Informações da OS -->
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="info-card">
                    <h3><i class='bx bx-file'></i> Dados da OS</h3>
                    <div class="info-row">
                        <span class="info-label">Número:</span>
                        <span class="info-value">#<?= sprintf('%04d', $os->idOs) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $os->status)) ?>">
                                <?= $os->status ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Data:</span>
                        <span class="info-value"><?= date('d/m/Y', strtotime($os->dataInicial)) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Garantia:</span>
                        <span class="info-value"><?= $os->garantia ?: 'N/A' ?> dias</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="info-card">
                    <h3><i class='bx bx-user'></i> Dados do Cliente</h3>
                    <div class="info-row">
                        <span class="info-label">Nome:</span>
                        <span class="info-value"><?= $cliente->nomeCliente ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Telefone:</span>
                        <span class="info-value"><?= $cliente->telefone ?: $cliente->celular ?: 'N/A' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Endereço:</span>
                        <span class="info-value">
                            <?= $cliente->rua ? $cliente->rua . ', ' . $cliente->numero : 'N/A' ?>
                            <?= $cliente->bairro ? ' - ' . $cliente->bairro : '' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Descrição e Defeito -->
        <div class="row">
            <div class="col-12">
                <div class="info-card">
                    <h3><i class='bx bx-detail'></i> Descrição do Serviço</h3>
                    <p style="color: #333; font-size: 14px; line-height: 1.6;">
                        <?= nl2br($os->descricaoProduto) ?: 'Nenhuma descrição informada.' ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="info-card">
                    <h3><i class='bx bx-error-circle'></i> Defeito</h3>
                    <p style="color: #333; font-size: 14px; line-height: 1.6;">
                        <?= nl2br($os->defeito) ?: 'Nenhum defeito informado.' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Fotos -->
        <?php if (!empty($fotos)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="info-card">
                        <h3><i class='bx bx-camera'></i> Fotos do Atendimento</h3>

                        <?php if (!empty($fotos_etapa['entrada'])): ?>
                            <div class="timeline-section">
                                <div class="timeline-title">
                                    <i class='bx bx-log-in-circle'></i> Fotos de Entrada
                                </div>
                                <div class="fotos-grid">
                                    <?php foreach ($fotos_etapa['entrada'] as $foto):
                                        // Garante URL correta para imagens em base64
                                        $imgUrl = !empty($foto->imagem_base64)
                                            ? base_url('index.php/checkin/verFotoDB/' . $foto->idFoto)
                                            : $foto->url;
                                    ?>
                                        <div class="foto-item">
                                            <img src="<?= $imgUrl ?>" alt="Foto entrada" onclick="abrirModalFoto('<?= $imgUrl ?>', '<?= $foto->descricao ?>')">
                                            <div class="foto-descricao"><?= $foto->descricao ?: 'Foto entrada' ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($fotos_etapa['durante'])): ?>
                            <div class="timeline-section">
                                <div class="timeline-title">
                                    <i class='bx bx-time'></i> Fotos Durante
                                </div>
                                <div class="fotos-grid">
                                    <?php foreach ($fotos_etapa['durante'] as $foto):
                                        // Garante URL correta para imagens em base64
                                        $imgUrl = !empty($foto->imagem_base64)
                                            ? base_url('index.php/checkin/verFotoDB/' . $foto->idFoto)
                                            : $foto->url;
                                    ?>
                                        <div class="foto-item">
                                            <img src="<?= $imgUrl ?>" alt="Foto durante" onclick="abrirModalFoto('<?= $imgUrl ?>', '<?= $foto->descricao ?>')">
                                            <div class="foto-descricao"><?= $foto->descricao ?: 'Foto durante' ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($fotos_etapa['saida'])): ?>
                            <div class="timeline-section">
                                <div class="timeline-title">
                                    <i class='bx bx-log-out-circle'></i> Fotos de Saída
                                </div>
                                <div class="fotos-grid">
                                    <?php foreach ($fotos_etapa['saida'] as $foto):
                                        // Garante URL correta para imagens em base64
                                        $imgUrl = !empty($foto->imagem_base64)
                                            ? base_url('index.php/checkin/verFotoDB/' . $foto->idFoto)
                                            : $foto->url;
                                    ?>
                                        <div class="foto-item">
                                            <img src="<?= $imgUrl ?>" alt="Foto saída" onclick="abrirModalFoto('<?= $imgUrl ?>', '<?= $foto->descricao ?>')">
                                            <div class="foto-descricao"><?= $foto->descricao ?: 'Foto saída' ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Assinaturas -->
        <?php if (!empty($assinaturas)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="info-card">
                        <h3><i class='bx bx-pen'></i> Assinaturas</h3>
                        <div class="row">
                            <?php foreach ($assinaturas as $assinatura): ?>
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="text-center">
                                        <h5 style="font-size: 12px; color: #666; margin-bottom: 10px;">
                                            <?= ucfirst(str_replace('_', ' ', $assinatura->tipo)) ?>
                                        </h5>
                                        <div class="assinatura-box">
                                            <?php if ($assinatura->is_base64): ?>
                                                <img src="<?= $assinatura->url_visualizacao ?>" alt="Assinatura">
                                            <?php elseif (file_exists($assinatura->assinatura)): ?>
                                                <img src="<?= base_url($assinatura->assinatura) ?>" alt="Assinatura">
                                            <?php else: ?>
                                                <span class="sem-assinatura">Assinatura não disponível</span>
                                            <?php endif; ?>
                                        </div>
                                        <small style="color: #999;">
                                            <?= date('d/m/Y H:i', strtotime($assinatura->data_assinatura)) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Espaço para menu mobile -->
    <div style="height: 80px;"></div>

    <!-- Menu Mobile -->
    <div class="mobile-menu">
        <a href="<?= base_url('index.php/tecnico') ?>">
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

    <!-- Modal para visualizar foto -->
    <div id="modalFoto" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFotoTitulo">Visualizar Foto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalFotoImagem" src="" alt="Foto" style="max-width: 100%; max-height: 500px;">
                    <p id="modalFotoDescricao" class="mt-3 text-muted"></p>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/js/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.min.js') ?>"></script>
    <script>
        function abrirModalFoto(url, descricao) {
            $('#modalFotoImagem').attr('src', url);
            $('#modalFotoDescricao').text(descricao || '');
            $('#modalFoto').modal('show');
        }
    </script>
</body>
</html>
