<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 15mm;
        }

        /* Cabeçalho */
        .header {
            border-bottom: 2px solid #2d335b;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .company-info {
            flex: 1;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2d335b;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 10px;
            color: #666;
            line-height: 1.5;
        }

        .document-title {
            text-align: right;
        }

        .document-title h1 {
            font-size: 16px;
            color: #2d335b;
            margin-bottom: 5px;
        }

        .document-title .os-number {
            font-size: 24px;
            font-weight: bold;
            color: #dc3545;
        }

        /* Seções */
        .section {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }

        .section-header {
            background: #2d335b;
            color: #fff;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }

        .section-content {
            padding: 12px;
        }

        /* Grid de informações */
        .info-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .info-item {
            flex: 1;
            min-width: 200px;
        }

        .info-label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 11px;
            font-weight: bold;
            color: #333;
        }

        /* Tabela de checkins */
        .checkin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .checkin-table th,
        .checkin-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }

        .checkin-table th {
            background: #f5f5f5;
            font-weight: bold;
            color: #2d335b;
        }

        .checkin-table tr:nth-child(even) {
            background: #fafafa;
        }

        /* Status badges */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        /* Seção de assinaturas */
        .signatures-grid {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .signature-box {
            flex: 1;
            min-width: 150px;
            text-align: center;
        }

        .signature-image {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 8px;
            background: #fafafa;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .signature-image img {
            max-width: 100%;
            max-height: 100px;
        }

        .signature-label {
            font-size: 10px;
            font-weight: bold;
            color: #333;
        }

        .signature-name {
            font-size: 9px;
            color: #666;
            margin-top: 3px;
        }

        /* Galeria de fotos */
        .photos-section {
            margin-top: 15px;
        }

        .photos-title {
            font-size: 11px;
            font-weight: bold;
            color: #2d335b;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }

        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
        }

        .photo-item {
            border: 1px solid #ddd;
            border-radius: 3px;
            overflow: hidden;
        }

        .photo-item img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            display: block;
        }

        .photo-description {
            padding: 5px;
            font-size: 8px;
            color: #666;
            background: #f5f5f5;
            border-top: 1px solid #ddd;
        }

        /* Rodapé */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
            text-align: center;
        }

        .footer-info {
            margin-bottom: 5px;
        }

        /* Quebra de página */
        .page-break {
            page-break-before: always;
        }

        /* Responsivo para impressão */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .container {
                padding: 0;
                max-width: none;
            }

            .no-print {
                display: none !important;
            }

            .section {
                break-inside: avoid;
            }

            .photo-item {
                break-inside: avoid;
            }
        }

        /* Botões de ação (não imprimem) */
        .actions {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #2d335b;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1a1f3a;
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        /* Mapa de localização */
        .location-info {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 10px;
        }

        .location-info strong {
            color: #2d335b;
        }
    </style>
</head>
<body>
    <!-- Botões de ação -->
    <div class="actions no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="bx bx-printer"></i> Imprimir
        </button>
        <button type="button" class="btn btn-secondary" onclick="window.close()">
            <i class="bx bx-x"></i> Fechar
        </button>
    </div>

    <div class="container">
        <!-- Cabeçalho -->
        <div class="header">
            <div class="header-top">
                <div class="company-info">
                    <?php if ($emitente) { ?>
                        <?php if ($emitente->url_logo) { ?>
                            <img src="<?php echo $emitente->url_logo; ?>" alt="Logo" style="max-height: 60px; margin-bottom: 10px;">
                        <?php } ?>
                        <div class="company-name"><?php echo $emitente->nome; ?></div>
                        <div class="company-details">
                            <?php if ($emitente->cnpj) echo 'CNPJ: ' . $emitente->cnpj . '<br>'; ?>
                            <?php if ($emitente->rua) {
                                echo $emitente->rua;
                                if ($emitente->numero) echo ', ' . $emitente->numero;
                                echo '<br>';
                            } ?>
                            <?php if ($emitente->cidade) {
                                echo $emitente->cidade;
                                if ($emitente->uf) echo '/' . $emitente->uf;
                                if ($emitente->cep) echo ' - CEP: ' . $emitente->cep;
                                echo '<br>';
                            } ?>
                            <?php if ($emitente->telefone) echo 'Tel: ' . $emitente->telefone . '<br>'; ?>
                            <?php if ($emitente->email) echo 'Email: ' . $emitente->email; ?>
                        </div>
                    <?php } else { ?>
                        <div class="company-name">Dados do Emitente não configurados</div>
                    <?php } ?>
                </div>
                <div class="document-title">
                    <h1>RELATÓRIO DE ATENDIMENTO</h1>
                    <div class="os-number">OS #<?php echo sprintf('%04d', $os->idOs); ?></div>
                </div>
            </div>
        </div>

        <!-- Informações da OS -->
        <div class="section">
            <div class="section-header">Informações da Ordem de Serviço</div>
            <div class="section-content">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Número da OS</div>
                        <div class="info-value">#<?php echo sprintf('%04d', $os->idOs); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Data de Entrada</div>
                        <div class="info-value"><?php echo date('d/m/Y', strtotime($os->dataInicial)); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value"><?php echo $os->status; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Data Prevista</div>
                        <div class="info-value"><?php echo date('d/m/Y', strtotime($os->dataFinal)); ?></div>
                    </div>
                </div>

                <div class="info-grid" style="margin-top: 15px;">
                    <div class="info-item" style="flex: 2;">
                        <div class="info-label">Descrição do Produto/Serviço</div>
                        <div class="info-value"><?php echo strip_tags($os->descricaoProduto); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações do Cliente -->
        <div class="section">
            <div class="section-header">Informações do Cliente</div>
            <div class="section-content">
                <div class="info-grid">
                    <div class="info-item" style="flex: 2;">
                        <div class="info-label">Nome</div>
                        <div class="info-value"><?php echo $cliente->nomeCliente; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Documento</div>
                        <div class="info-value"><?php echo $cliente->documento ? $cliente->documento : 'Não informado'; ?></div>
                    </div>
                </div>

                <div class="info-grid" style="margin-top: 10px;">
                    <div class="info-item" style="flex: 2;">
                        <div class="info-label">Endereço</div>
                        <div class="info-value">
                            <?php
                            $endereco = [];
                            if ($cliente->rua) $endereco[] = $cliente->rua;
                            if ($cliente->numero) $endereco[] = $cliente->numero;
                            if ($cliente->complemento) $endereco[] = $cliente->complemento;
                            if ($cliente->bairro) $endereco[] = $cliente->bairro;
                            echo implode(', ', $endereco);
                            ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Cidade/Estado</div>
                        <div class="info-value">
                            <?php
                            echo $cliente->cidade;
                            if ($cliente->estado) echo '/' . $cliente->estado;
                            ?>
                        </div>
                    </div>
                </div>

                <div class="info-grid" style="margin-top: 10px;">
                    <div class="info-item">
                        <div class="info-label">Telefone</div>
                        <div class="info-value"><?php echo $cliente->telefone ? $cliente->telefone : 'Não informado'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Celular</div>
                        <div class="info-value"><?php echo $cliente->celular ? $cliente->celular : 'Não informado'; ?></div>
                    </div>
                    <div class="info-item" style="flex: 2;">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo $cliente->email ? $cliente->email : 'Não informado'; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Histórico de Atendimentos -->
        <div class="section">
            <div class="section-header">Histórico de Atendimentos</div>
            <div class="section-content">
                <?php if (!empty($checkins)) { ?>
                    <table class="checkin-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Técnico</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                                <th>Tempo Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($checkins as $index => $checkin) { ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $checkin->nome_tecnico; ?></td>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($checkin->data_entrada)); ?>
                                        <?php if ($checkin->latitude_entrada && $checkin->longitude_entrada) { ?>
                                            <br><small>Loc: <?php echo $checkin->latitude_entrada . ', ' . $checkin->longitude_entrada; ?></small>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($checkin->data_saida) { ?>
                                            <?php echo date('d/m/Y H:i', strtotime($checkin->data_saida)); ?>
                                            <?php if ($checkin->latitude_saida && $checkin->longitude_saida) { ?>
                                                <br><small>Loc: <?php echo $checkin->latitude_saida . ', ' . $checkin->longitude_saida; ?></small>
                                            <?php } ?>
                                        <?php } else { ?>
                                            -
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($checkin->data_saida) {
                                            $entrada = new DateTime($checkin->data_entrada);
                                            $saida = new DateTime($checkin->data_saida);
                                            $intervalo = $entrada->diff($saida);
                                            echo $intervalo->format('%h horas %i minutos');
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($checkin->data_saida) { ?>
                                            <span class="badge badge-success">Finalizado</span>
                                        <?php } else { ?>
                                            <span class="badge badge-warning">Em Andamento</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php if ($checkin->observacao_entrada || $checkin->observacao_saida) { ?>
                                    <tr style="background: #f9f9f9;">
                                        <td colspan="6">
                                            <?php if ($checkin->observacao_entrada) { ?>
                                                <strong>Obs. Entrada:</strong> <?php echo nl2br($checkin->observacao_entrada); ?><br>
                                            <?php } ?>
                                            <?php if ($checkin->observacao_saida) { ?>
                                                <strong>Obs. Saída:</strong> <?php echo nl2br($checkin->observacao_saida); ?>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p style="text-align: center; color: #666; padding: 20px;">Nenhum atendimento registrado para esta OS.</p>
                <?php } ?>
            </div>
        </div>

        <!-- Assinaturas -->
        <?php if (!empty($assinaturas) && is_array($assinaturas) && count($assinaturas) > 0) { ?>
            <div class="section">
                <div class="section-header">Assinaturas Digitais</div>
                <div class="section-content">
                    <div class="signatures-grid">
                        <?php foreach ($assinaturas as $tipo => $assinatura) { ?>
                            <?php if (is_object($assinatura) && !empty($assinatura->assinatura)) { ?>
                                <div class="signature-box">
                                    <div class="signature-image">
                                        <?php
                                        // Verifica se é base64 ou arquivo
                                        if (isset($assinatura->is_base64) && $assinatura->is_base64) {
                                            $img_src = $assinatura->url_visualizacao;
                                        } else {
                                            $img_src = base_url($assinatura->assinatura);
                                        }
                                        ?>
                                        <img src="<?php echo $img_src; ?>" alt="Assinatura <?php echo $tipo; ?>">
                                    </div>
                                    <div class="signature-label">
                                        <?php
                                        switch ($tipo) {
                                            case 'tecnico_entrada':
                                                echo 'Técnico - Entrada';
                                                break;
                                            case 'tecnico_saida':
                                                echo 'Técnico - Saída';
                                                break;
                                            case 'cliente_saida':
                                                echo 'Cliente - Saída';
                                                break;
                                            default:
                                                echo ucfirst(str_replace('_', ' ', $tipo));
                                        }
                                        ?>
                                    </div>
                                    <?php if (!empty($assinatura->nome_assinante)) { ?>
                                        <div class="signature-name">
                                            <?php echo $assinatura->nome_assinante; ?>
                                            <?php if (!empty($assinatura->documento_assinante)) { ?>
                                                <br><small><?php echo $assinatura->documento_assinante; ?></small>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                    <div class="signature-name">
                                        <?php echo date('d/m/Y H:i', strtotime($assinatura->data_assinatura)); ?>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Fotos do Atendimento -->
        <?php if (!empty($fotosPorEtapa['entrada']) || !empty($fotosPorEtapa['durante']) || !empty($fotosPorEtapa['saida'])) { ?>
            <div class="section">
                <div class="section-header">Registro Fotográfico do Atendimento</div>
                <div class="section-content">
                    <!-- Fotos de Entrada -->
                    <?php if (!empty($fotosPorEtapa['entrada'])) { ?>
                        <div class="photos-section">
                            <div class="photos-title">📷 Fotos de Entrada</div>
                            <div class="photos-grid">
                                <?php foreach ($fotosPorEtapa['entrada'] as $foto) {
                                    // Garante URL correta para imagens em base64
                                    $imgUrl = !empty($foto->imagem_base64)
                                        ? base_url('index.php/checkin/verFotoDB/' . $foto->idFoto)
                                        : $foto->url;
                                ?>
                                    <div class="photo-item">
                                        <img src="<?php echo $imgUrl; ?>" alt="Foto de entrada">
                                        <?php if ($foto->descricao) { ?>
                                            <div class="photo-description"><?php echo $foto->descricao; ?></div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>

                    <!-- Fotos Durante -->
                    <?php if (!empty($fotosPorEtapa['durante'])) { ?>
                        <div class="photos-section">
                            <div class="photos-title">📷 Fotos Durante o Atendimento</div>
                            <div class="photos-grid">
                                <?php foreach ($fotosPorEtapa['durante'] as $foto) {
                                    // Garante URL correta para imagens em base64
                                    $imgUrl = !empty($foto->imagem_base64)
                                        ? base_url('index.php/checkin/verFotoDB/' . $foto->idFoto)
                                        : $foto->url;
                                ?>
                                    <div class="photo-item">
                                        <img src="<?php echo $imgUrl; ?>" alt="Foto durante atendimento">
                                        <?php if ($foto->descricao) { ?>
                                            <div class="photo-description"><?php echo $foto->descricao; ?></div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>

                    <!-- Fotos de Saída -->
                    <?php if (!empty($fotosPorEtapa['saida'])) { ?>
                        <div class="photos-section">
                            <div class="photos-title">📷 Fotos de Saída</div>
                            <div class="photos-grid">
                                <?php foreach ($fotosPorEtapa['saida'] as $foto) {
                                    // Garante URL correta para imagens em base64
                                    $imgUrl = !empty($foto->imagem_base64)
                                        ? base_url('index.php/checkin/verFotoDB/' . $foto->idFoto)
                                        : $foto->url;
                                ?>
                                    <div class="photo-item">
                                        <img src="<?php echo $imgUrl; ?>" alt="Foto de saída">
                                        <?php if ($foto->descricao) { ?>
                                            <div class="photo-description"><?php echo $foto->descricao; ?></div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <!-- Rodapé -->
        <div class="footer">
            <div class="footer-info">
                <strong><?php echo $emitente ? $emitente->nome : 'Sistema MAP-OS'; ?></strong><br>
                Documento gerado em <?php echo date('d/m/Y \à\s H:i:s'); ?>
            </div>
            <div class="footer-info" style="margin-top: 10px; font-size: 8px; color: #999;">
                Este documento é um registro digital de atendimento e possui valor legal conforme assinaturas digitais registradas.
            </div>
        </div>
    </div>

    <script>
        // Auto-print após 1 segundo (opcional)
        // setTimeout(function() { window.print(); }, 1000);
    </script>
</body>
</html>
