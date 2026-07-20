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

    <!-- Banner: serviço não realizado (em espera) -->
    <?php $nr_pendente = isset($nao_realizada_pendente) ? $nao_realizada_pendente : null; ?>
    <?php if ($nr_pendente): ?>
        <div class="info-card" style="border-left:4px solid var(--tec-danger, #e74c3c);">
            <h3 style="color:var(--tec-danger, #e74c3c);"><i class='bx bx-x-circle'></i> Serviço não realizado</h3>
            <?php if (!empty($nr_pendente->motivo_texto)): ?>
                <div class="info-row"><span class="info-label">Motivo</span><span class="info-value"><?= html_escape($nr_pendente->motivo_texto) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($nr_pendente->observacao)): ?>
                <div class="info-row"><span class="info-label">Observação</span><span class="info-value"><?= nl2br(html_escape($nr_pendente->observacao)) ?></span></div>
            <?php endif; ?>
            <div class="info-row"><span class="info-label">Registrado</span><span class="info-value"><?= !empty($nr_pendente->data_registro) ? date('d/m/Y H:i', strtotime($nr_pendente->data_registro)) : '--' ?></span></div>
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;">
                <button type="button" class="btn-tec warning" onclick="abrirReagendar(<?= (int) $nr_pendente->idOcorrencia ?>)"><i class='bx bx-calendar-plus'></i> Reagendar</button>
                <button type="button" class="btn-tec neutral" data-ocorrencia="<?= (int) $nr_pendente->idOcorrencia ?>" onclick="reabrirAtividade(this)"><i class='bx bx-revision'></i> Reabrir p/ refazer</button>
            </div>
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

    <?php
    // Ações de atendimento (assinatura, impressão e relatório) só fazem sentido
    // depois que o atendimento começou: há check-in ativo, ou já existem
    // assinaturas/fotos registradas para esta OS.
    $atendimento_iniciado = !empty($checkin_ativo) || !empty($assinaturas) || !empty($fotos);
    ?>
    <?php if ($atendimento_iniciado): ?>
        <a href="<?= site_url('tecnico/assinatura_solicitante/' . $os->idOs) ?>" class="btn-tec success block" style="margin-bottom:8px;">
            <i class='bx bx-pen'></i> Assinatura do Solicitante
        </a>

        <a href="<?= site_url('os/imprimir/' . $os->idOs) ?>" target="_blank" class="btn-tec ghost block" style="margin-bottom:8px;">
            <i class='bx bx-printer'></i> Imprimir OS
        </a>

        <a href="<?= site_url('checkin/imprimir/' . $os->idOs) ?>" target="_blank" class="btn-tec neutral block">
            <i class='bx bx-time'></i> Relatório de Atendimento
        </a>
    <?php endif; ?>

    <?php
    // "Não foi possível realizar": disponível quando há permissão, a OS ainda
    // não está em espera e não está concluída. Fica na barra de ação, junto do
    // "Iniciar Atendimento" (parte do fluxo de início).
    $nr_concluida = in_array($os->status, ['Finalizado', 'Faturado', 'Cancelado'], true);
    $mostrar_nao_realizado = !empty($permissao_nao_realizado) && empty($nr_pendente) && !$nr_concluida;
    ?>

    <?php if ($permissao_checkin || $permissao_checkout || $mostrar_nao_realizado): ?>
        <div style="height:76px;"></div><!-- espaco para a barra de acao fixa -->
    <?php endif; ?>
</div>

<!-- Barra de acao fixa: iniciar / nao realizado / finalizar -->
<?php if ($permissao_checkin || $permissao_checkout || $mostrar_nao_realizado): ?>
<div class="action-bar">
    <?php if ($permissao_checkin): ?>
        <button type="button" id="btn-iniciar-atendimento" class="btn-tec success lg <?= $checkin_ativo ? 'hidden' : '' ?>">
            <i class='bx bx-log-in'></i> Iniciar Atendimento
        </button>
    <?php endif; ?>
    <?php if ($mostrar_nao_realizado): ?>
        <button type="button" id="btn-nao-realizado-bar" class="btn-tec lg btn-nr-outline <?= $checkin_ativo ? 'hidden' : '' ?>" onclick="abrirNaoRealizado()">
            <i class='bx bx-x-circle'></i> Não realizado
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

<!-- Modal: registrar "não foi possível realizar" -->
<?php if ($mostrar_nao_realizado): ?>
<div id="naoRealizadoModal" class="nr-modal" style="display:none;">
    <div class="nr-modal-box">
        <h3><i class='bx bx-x-circle'></i> Serviço não realizado</h3>
        <p class="nr-modal-sub">Registre o motivo para a OS ficar em espera e poder ser reagendada depois.</p>

        <label class="nr-label">Motivo</label>
        <select id="nr-motivo" class="atv-input">
            <option value="">— Selecione um motivo —</option>
            <?php foreach ($motivos_nao_realizado as $m): ?>
                <option value="<?= (int) $m->idMotivo ?>"><?= html_escape($m->nome) ?></option>
            <?php endforeach; ?>
        </select>

        <label class="nr-label">Observação</label>
        <textarea id="nr-obs" class="atv-input" rows="3" placeholder="Descreva o que aconteceu (opcional se escolheu um motivo)"></textarea>

        <div class="nr-modal-actions">
            <button type="button" class="btn-tec ghost" onclick="fecharNaoRealizado()">Cancelar</button>
            <button type="button" id="nr-confirmar" class="btn-tec danger" onclick="confirmarNaoRealizado()"><i class='bx bx-check'></i> Confirmar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal: reagendar -->
<?php if (!empty($nr_pendente)): ?>
<div id="reagendarModal" class="nr-modal" style="display:none;">
    <div class="nr-modal-box">
        <h3><i class='bx bx-calendar-plus'></i> Reagendar OS</h3>
        <p class="nr-modal-sub">Escolha a nova data. A OS volta para a sua agenda como "Aberto".</p>
        <input type="hidden" id="reag-ocorrencia" value="">
        <label class="nr-label">Nova data</label>
        <input type="date" id="reag-data" class="atv-input" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
        <div class="nr-modal-actions">
            <button type="button" class="btn-tec ghost" onclick="fecharReagendar()">Cancelar</button>
            <button type="button" class="btn-tec warning" onclick="confirmarReagendar()"><i class='bx bx-check'></i> Reagendar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.nr-modal{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:2100;display:flex;align-items:flex-end;justify-content:center;padding:0;}
.nr-modal-box{background:var(--tec-card,#fff);width:100%;max-width:520px;border-radius:16px 16px 0 0;padding:20px 18px calc(20px + env(safe-area-inset-bottom));box-shadow:0 -8px 30px rgba(0,0,0,.25);}
.nr-modal-box h3{margin:0 0 4px;font-size:17px;display:flex;align-items:center;gap:8px;}
.nr-modal-sub{margin:0 0 14px;font-size:13px;color:#8a8fa3;}
.nr-label{display:block;font-size:12px;font-weight:600;color:#8a8fa3;margin:10px 0 5px;}
.nr-modal-box .atv-input{width:100%;padding:11px 12px;border:1px solid rgba(0,0,0,.15);border-radius:10px;font-size:15px;background:transparent;color:inherit;box-sizing:border-box;}
.nr-modal-actions{display:flex;gap:10px;margin-top:18px;}
.nr-modal-actions .btn-tec{flex:1;justify-content:center;}
@media (min-width:600px){.nr-modal{align-items:center;}.nr-modal-box{border-radius:16px;}}
/* Botão "Não realizado" na barra de ação: destaque secundário (contorno). */
.btn-nr-outline{background:#fff;color:#f5576c;border:1.5px solid #f5576c;}
/* Garante o toggle de visibilidade dos botões da barra (iniciar/finalizar/nao-realizado). */
.action-bar .hidden{display:none !important;}
</style>

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

    // ---- Serviço não realizado -------------------------------------
    var NR_OS_ID = <?= (int) $os->idOs ?>;
    var NR_BASE = '<?= site_url('tecnico') ?>';

    function abrirNaoRealizado() {
        var m = document.getElementById('naoRealizadoModal');
        if (m) { m.style.display = 'flex'; }
    }
    function fecharNaoRealizado() {
        var m = document.getElementById('naoRealizadoModal');
        if (m) { m.style.display = 'none'; }
    }
    function confirmarNaoRealizado() {
        var motivo = document.getElementById('nr-motivo').value;
        var obs = document.getElementById('nr-obs').value.trim();
        if (!motivo && obs === '') {
            alert('Selecione um motivo ou descreva o que aconteceu.');
            return;
        }
        var btn = document.getElementById('nr-confirmar');
        btn.disabled = true;
        $.ajax({
            url: NR_BASE + '/nao_realizado',
            type: 'POST',
            dataType: 'json',
            data: { os_id: NR_OS_ID, motivo_id: motivo, observacao: obs }
        }).done(function (r) {
            if (r && r.success) {
                alert(r.message || 'Registrado.');
                window.location.href = NR_BASE + '/nao_realizadas';
            } else {
                alert((r && r.message) || 'Falha ao registrar.');
                btn.disabled = false;
            }
        }).fail(function () {
            alert('Erro de conexão. Tente novamente.');
            btn.disabled = false;
        });
    }

    // ---- Reagendar / reabrir (OS já em espera) ---------------------
    function abrirReagendar(ocorrenciaId) {
        var inp = document.getElementById('reag-ocorrencia');
        if (inp) { inp.value = ocorrenciaId; }
        var m = document.getElementById('reagendarModal');
        if (m) { m.style.display = 'flex'; }
    }
    function fecharReagendar() {
        var m = document.getElementById('reagendarModal');
        if (m) { m.style.display = 'none'; }
    }
    function confirmarReagendar() {
        var oc = document.getElementById('reag-ocorrencia').value;
        var data = document.getElementById('reag-data').value;
        if (!data) { alert('Escolha uma data.'); return; }
        $.ajax({
            url: NR_BASE + '/reagendar_atividade',
            type: 'POST',
            dataType: 'json',
            data: { ocorrencia_id: oc, nova_data: data }
        }).done(function (r) {
            alert((r && r.message) || (r && r.success ? 'Reagendado.' : 'Falha.'));
            if (r && r.success) { window.location.reload(); }
        }).fail(function () { alert('Erro de conexão.'); });
    }
    function reabrirAtividade(btn) {
        if (!confirm('Reabrir esta OS para refazer o serviço?')) { return; }
        var oc = btn.getAttribute('data-ocorrencia');
        btn.disabled = true;
        $.ajax({
            url: NR_BASE + '/reabrir_atividade',
            type: 'POST',
            dataType: 'json',
            data: { ocorrencia_id: oc }
        }).done(function (r) {
            alert((r && r.message) || (r && r.success ? 'Reaberta.' : 'Falha.'));
            if (r && r.success) { window.location.reload(); } else { btn.disabled = false; }
        }).fail(function () { alert('Erro de conexão.'); btn.disabled = false; });
    }
</script>
</body>
</html>
