<?php
$this->load->view('tecnico/_topo', [
    'titulo'       => 'OS #' . sprintf('%04d', $os->idOs),
    'header_icone' => 'bx-file',
    'voltar_url'   => site_url('tecnico/os'),
]);

// Telefone do cliente
$fone = !empty($cliente->celular) ? $cliente->celular : (!empty($cliente->telefone) ? $cliente->telefone : '');
$foneTel = preg_replace('/[^\d+]/', '', $fone);
$wa = preg_replace('/\D/', '', $fone);
if ($wa !== '' && strlen($wa) <= 11) { $wa = '55' . $wa; }

// Endereco para o mapa
$endParts = array_filter([
    isset($cliente->rua) ? $cliente->rua : '',
    isset($cliente->numero) ? $cliente->numero : '',
    isset($cliente->bairro) ? $cliente->bairro : '',
    isset($cliente->cidade) ? $cliente->cidade : '',
    isset($cliente->estado) ? $cliente->estado : '',
]);
$enderecoStr = trim(implode(', ', $endParts));
$mapUrl = $enderecoStr !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($enderecoStr) : '';

$documentoCliente = isset($cliente->documento) ? $cliente->documento : (isset($cliente->cpf) ? $cliente->cpf : '');
?>

<div class="tec-container">

    <!-- Banner de atendimento em andamento -->
    <?php if ($checkin_ativo): ?>
        <div class="checkin-banner">
            <h4><i class='bx bx-play-circle'></i> Atendimento em andamento</h4>
            <p>Iniciado em <?= date('d/m/Y \à\s H:i', strtotime($checkin_ativo->data_entrada)) ?></p>
        </div>
    <?php endif; ?>

    <!-- Atalhos de contato -->
    <?php if ($foneTel || $mapUrl): ?>
        <div class="quick-actions" style="margin-bottom:16px;">
            <?php if ($foneTel): ?>
                <a href="tel:<?= $foneTel ?>" class="btn-tec qa-call"><i class='bx bx-phone'></i> Ligar</a>
                <?php if ($wa): ?>
                    <a href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener" class="btn-tec qa-whats"><i class='bx bxl-whatsapp'></i> WhatsApp</a>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($mapUrl): ?>
                <a href="<?= $mapUrl ?>" target="_blank" rel="noopener" class="btn-tec qa-map"><i class='bx bx-map'></i> Ver no mapa</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Dados da OS -->
    <div class="info-card">
        <h3><i class='bx bx-file'></i> Dados da OS</h3>
        <div class="info-row"><span class="info-label">Número</span><span class="info-value">#<?= sprintf('%04d', $os->idOs) ?>
            <?php if (!empty($os->nao_programada)): ?>
                <span class="badge-status andamento" style="font-size:10px;"><i class='bx bx-bolt-circle'></i> Não programada</span>
            <?php endif; ?>
        </span></div>
        <div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge-status <?= in_array($os->status, ['Finalizado','Faturado']) ? 'finalizado' : ($os->status == 'Em Andamento' ? 'andamento' : 'pendente') ?>"><?= $os->status ?></span></span></div>
        <div class="info-row"><span class="info-label">Data</span><span class="info-value"><?= date('d/m/Y', strtotime($os->dataInicial)) ?></span></div>
        <div class="info-row"><span class="info-label">Garantia</span><span class="info-value"><?= $os->garantia ?: 'N/A' ?> dias</span></div>
    </div>

    <!-- Dados do Cliente -->
    <div class="info-card">
        <h3><i class='bx bx-user'></i> Cliente</h3>
        <div class="info-row"><span class="info-label">Nome</span><span class="info-value"><?= $cliente->nomeCliente ?></span></div>
        <div class="info-row"><span class="info-label">Telefone</span><span class="info-value"><?= $fone ?: 'N/A' ?></span></div>
        <div class="info-row"><span class="info-label">Endereço</span><span class="info-value"><?= $enderecoStr !== '' ? html_escape($enderecoStr) : 'N/A' ?></span></div>
    </div>

    <!-- Descricao / Defeito -->
    <div class="info-card">
        <h3><i class='bx bx-detail'></i> Descrição do Serviço</h3>
        <p style="margin:0; font-size:14px; line-height:1.6;"><?= nl2br($os->descricaoProduto) ?: 'Nenhuma descrição informada.' ?></p>
    </div>
    <div class="info-card">
        <h3><i class='bx bx-error-circle'></i> Defeito</h3>
        <p style="margin:0; font-size:14px; line-height:1.6;"><?= nl2br($os->defeito) ?: 'Nenhum defeito informado.' ?></p>
    </div>

    <!-- Fotos -->
    <?php if (!empty($fotos)): ?>
        <div class="info-card">
            <h3><i class='bx bx-camera'></i> Fotos do Atendimento</h3>
            <?php
            $etapasLabels = ['entrada' => ['bx-log-in-circle', 'Entrada'], 'durante' => ['bx-time', 'Durante'], 'saida' => ['bx-log-out-circle', 'Saída']];
            foreach ($etapasLabels as $et => $meta):
                if (empty($fotos_etapa[$et])) continue; ?>
                <div class="timeline-title"><i class='bx <?= $meta[0] ?>'></i> Fotos de <?= $meta[1] ?></div>
                <div class="fotos-grid">
                    <?php foreach ($fotos_etapa[$et] as $foto):
                        $imgUrl = !empty($foto->imagem_base64) ? base_url('index.php/checkin/verFotoDB/' . $foto->idFoto) : $foto->url; ?>
                        <div class="foto-item">
                            <img src="<?= $imgUrl ?>" alt="Foto <?= $meta[1] ?>" onclick="abrirFoto('<?= $imgUrl ?>')">
                            <?php if (!empty($foto->descricao)): ?><div class="cap"><?= html_escape($foto->descricao) ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Assinaturas ja coletadas -->
    <?php if (!empty($assinaturas)): ?>
        <div class="info-card">
            <h3><i class='bx bx-pen'></i> Assinaturas</h3>
            <div class="row" style="display:flex; flex-wrap:wrap; gap:14px;">
                <?php foreach ($assinaturas as $assinatura): ?>
                    <div style="flex:1 1 140px; text-align:center;">
                        <h5 style="font-size:12px; color:#8a8fa3; margin:0 0 8px;"><?= ucfirst(str_replace('_', ' ', $assinatura->tipo)) ?></h5>
                        <div class="assinatura-box">
                            <?php if (!empty($assinatura->is_base64)): ?>
                                <img src="<?= $assinatura->url_visualizacao ?>" alt="Assinatura">
                            <?php elseif (!empty($assinatura->assinatura) && file_exists($assinatura->assinatura)): ?>
                                <img src="<?= base_url($assinatura->assinatura) ?>" alt="Assinatura">
                            <?php else: ?>
                                <span class="vazia">Indisponível</span>
                            <?php endif; ?>
                        </div>
                        <small style="color:#8a8fa3;"><?= date('d/m/Y H:i', strtotime($assinatura->data_assinatura)) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <a href="<?= site_url('tecnico/assinatura_solicitante/' . $os->idOs) ?>" class="btn-tec success block" style="margin-bottom:8px;">
        <i class='bx bx-pen'></i> Assinatura do Solicitante
    </a>

    <a href="<?= site_url('os/imprimir/' . $os->idOs) ?>" target="_blank" class="btn-tec ghost block" style="margin-bottom:8px;">
        <i class='bx bx-printer'></i> Imprimir OS
    </a>
    <?php if (!empty($checkin_ativo) || (isset($assinaturas) && !empty($assinaturas))): ?>
        <a href="<?= site_url('checkin/imprimir/' . $os->idOs) ?>" target="_blank" class="btn-tec neutral block">
            <i class='bx bx-time'></i> Relatório de Atendimento
        </a>
    <?php endif; ?>

    <?php if ($permissao_checkin || $permissao_checkout): ?>
        <div style="height:76px;"></div><!-- espaco para a barra de acao fixa -->
    <?php endif; ?>
</div>

<!-- Barra de acao fixa: iniciar / finalizar -->
<?php if ($permissao_checkin || $permissao_checkout): ?>
<div class="action-bar">
    <?php if ($permissao_checkin): ?>
        <button type="button" id="btn-iniciar-atendimento" class="btn-tec success lg <?= $checkin_ativo ? 'hidden' : '' ?>">
            <i class='bx bx-log-in'></i> Iniciar Atendimento
        </button>
    <?php endif; ?>
    <?php if ($permissao_checkout): ?>
        <button type="button" id="btn-finalizar-atendimento" class="btn-tec danger lg <?= $checkin_ativo ? '' : 'hidden' ?>">
            <i class='bx bx-log-out'></i> Finalizar Atendimento
        </button>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Modal de foto ampliada -->
<div id="fotoModal" onclick="this.style.display='none'" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.85); z-index:2000; align-items:center; justify-content:center; padding:20px;">
    <img id="fotoModalImg" src="" alt="Foto" style="max-width:100%; max-height:100%; border-radius:8px;">
</div>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => 'os', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>

<!-- UI de atendimento (check-in/out, assinaturas, fotos) -->
<?php $this->load->view('checkin/atendimento_ui', [
    'os_id' => $os->idOs,
    'nome_cliente' => $cliente->nomeCliente,
    'documento_cliente' => $documentoCliente,
]); ?>

<script>
    function abrirFoto(url) {
        document.getElementById('fotoModalImg').src = url;
        document.getElementById('fotoModal').style.display = 'flex';
    }
</script>
</body>
</html>
