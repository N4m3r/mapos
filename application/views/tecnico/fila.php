<?php
$this->load->view('tecnico/_topo', [
    'titulo'       => 'Minha fila',
    'header_icone' => 'bx-transfer-alt',
    'header_sub'   => 'Pontos que chegaram para você',
    'voltar_url'   => site_url('tecnico'),
]);

$uidAtual = (int) ($uid ?? 0);
$fila = $fila ?? [];
$minhas = [];
$equipe = [];
foreach ($fila as $it) {
    if (!empty($it->minha)) {
        $minhas[] = $it;
    } else {
        $equipe[] = $it;
    }
}

// "há N h" desde o repasse (ou a ultima atualizacao, se nunca foi repassado)
$haQuanto = function ($it) {
    $ref = !empty($it->repassado_em) ? $it->repassado_em : ($it->atualizado_em ?? null);
    if (empty($ref) || !($ts = strtotime($ref))) {
        return '';
    }
    $h = (int) floor((time() - $ts) / 3600);
    return $h < 1 ? 'há menos de 1 h' : 'há ' . $h . ' h';
};

// Bloco de foto compactada (liga em foto-compacta.js): botao, miniaturas e erro.
$blocoFoto = function ($rotulo, $etiqueta = false) {
    ?>
    <label class="btn-tec ghost block" style="cursor:pointer;margin-top:8px;">
        <i class='bx <?= $etiqueta ? 'bx-barcode-reader' : 'bx-camera' ?>'></i> <?= html_escape($rotulo) ?>
        <input type="file" accept="image/*" capture="environment" multiple data-foto-compacta<?= $etiqueta ? ' data-etiqueta' : '' ?> style="display:none;">
    </label>
    <div data-foto-preview style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;"></div>
    <div data-foto-erro role="alert" style="display:none;color:#e74c3c;font-weight:600;margin-top:6px;"></div>
    <?php
};

// Um cartao da fila. $minha = true: o ponto esta comigo (acoes); false: da equipe (assumir).
$cartao = function ($it, $minha) use ($executores, $uidAtual, $status_lista, $tipos, $haQuanto, $blocoFoto) {
    $pid = (int) $it->idPonto;
    $oid = (int) $it->obra_id;
    $aguardando = (string) ($it->aguardando ?? '');
    $rotulo = Obra_pontos_model::rotuloAguardando($aguardando);
    if ($rotulo === '') {
        $rotulo = $status_lista[$it->status]['rotulo'] ?? (string) $it->status;
    }
    $destinos = array_filter($executores[$oid] ?? [], function ($ex) use ($uidAtual) {
        return (int) $ex['idUsuarios'] !== $uidAtual;
    });
    $tipoIcone = $tipos[$it->tipo]['icone'] ?? 'bx-devices';
    $ha = $haQuanto($it);
    $urlFoto = !empty($it->foto_id) ? site_url('obras/fotoPonto/' . (int) $it->foto_id) : null;
    $qtdFotos = (int) ($it->qtd_fotos ?? 0);
    $urlProjeto = site_url('tecnico/projeto/' . $oid . '#ponto-' . $pid);
    $modeloSerie = trim((string) ($it->modelo ?? '') . ' ' . (string) ($it->serie ?? ''));
    ?>
    <div class="info-card fila-card" id="item-<?= $pid ?>">
        <div class="fila-head">
            <span class="ponto-id"><i class='bx <?= html_escape($tipoIcone) ?>'></i> <?= html_escape((string) $it->identificacao) ?></span>
            <span class="ui-selo info"><?= html_escape($rotulo) ?></span>
        </div>
        <div class="ponto-meta"><i class='bx bx-network-chart'></i> <?= html_escape((string) ($it->obra_nome ?? '')) ?><?= !empty($it->cliente) ? ' · ' . html_escape((string) $it->cliente) : '' ?></div>
        <?php if (!empty($it->local)): ?>
            <div class="ponto-meta"><i class='bx bx-map-pin'></i> <?= html_escape((string) $it->local) ?></div>
        <?php endif; ?>
        <?php if ($modeloSerie !== ''): ?>
            <div class="ponto-meta"><i class='bx bx-devices'></i> <?= html_escape($modeloSerie) ?></div>
        <?php endif; ?>
        <?php if (!empty($it->instrucao)): ?>
            <div class="ponto-meta" style="color:#55596e;"><i class='bx bx-note'></i> <?= html_escape((string) $it->instrucao) ?></div>
        <?php endif; ?>
        <div class="ponto-meta">
            <?php if ($minha && !empty($it->repassado_por_nome)): ?>
                Repassado por <?= html_escape((string) $it->repassado_por_nome) ?><?= $ha !== '' ? ' · ' . html_escape($ha) : '' ?>
            <?php elseif (!$minha): ?>
                Da equipe<?= $ha !== '' ? ' · ' . html_escape($ha) : '' ?>
            <?php elseif ($ha !== ''): ?>
                <?= html_escape($ha) ?>
            <?php endif; ?>
        </div>

        <?php if ($urlFoto): ?>
            <div style="display:flex;align-items:center;gap:10px;margin-top:8px;">
                <a href="<?= html_escape($urlFoto) ?>" target="_blank" rel="noopener">
                    <img src="<?= html_escape($urlFoto) ?>" loading="lazy" alt="Foto do equipamento" style="width:100%;max-width:280px;height:160px;object-fit:cover;border-radius:8px;border:1px solid #ccc;">
                </a>
                <?php if ($qtdFotos > 1): ?><small style="color:#666;">+<?= $qtdFotos - 1 ?> foto(s)</small><?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$minha): ?>
            <form action="<?= site_url('tecnico/assumir_ponto') ?>" method="post" style="margin-top:10px;">
                <input type="hidden" name="obra_id" value="<?= $oid ?>">
                <input type="hidden" name="ponto_id" value="<?= $pid ?>">
                <input type="hidden" name="voltar" value="fila">
                <button type="submit" class="btn-tec primary block lg ponto-acao"><i class='bx bx-hand'></i> Assumir</button>
            </form>
        <?php else: ?>
            <?php if ($aguardando === 'configurado'): ?>
                <form action="<?= site_url('tecnico/ponto_status') ?>" method="post" style="margin-top:10px;">
                    <input type="hidden" name="obra_id" value="<?= $oid ?>">
                    <input type="hidden" name="ponto_id" value="<?= $pid ?>">
                    <input type="hidden" name="status" value="configurado">
                    <input type="hidden" name="voltar" value="fila">
                    <label class="ponto-campo">IP
                        <input type="text" name="ip" value="<?= html_escape((string) ($it->ip ?? '')) ?>" maxlength="45" class="ponto-input">
                    </label>
                    <label class="ponto-campo">Canal (NVR)
                        <input type="text" name="canal" value="<?= html_escape((string) ($it->canal ?? '')) ?>" maxlength="10" class="ponto-input">
                    </label>
                    <label class="ponto-campo">Porta
                        <input type="text" name="porta" value="<?= html_escape((string) ($it->porta ?? '')) ?>" maxlength="30" class="ponto-input">
                    </label>
                    <button type="submit" class="btn-tec primary block lg ponto-acao" style="margin-top:10px;">Configurei ✓</button>
                </form>
            <?php elseif ($aguardando === 'testado'): ?>
                <?php $camTeste = Obra_pontos_model::exigeTeste('testado', $it->tipo); ?>
                <form action="<?= site_url('tecnico/ponto_status') ?>" method="post" style="margin-top:10px;"<?= $camTeste ? ' data-exige-foto="Tire ao menos uma foto da imagem da câmera testada"' : '' ?>>
                    <input type="hidden" name="obra_id" value="<?= $oid ?>">
                    <input type="hidden" name="ponto_id" value="<?= $pid ?>">
                    <input type="hidden" name="status" value="testado">
                    <input type="hidden" name="voltar" value="fila">
                    <?php if ($camTeste): ?>
                        <?php $blocoFoto('Foto da imagem da câmera (obrigatória)'); ?>
                        <?php include APPPATH . 'views/tecnico/_teste_camera.php'; ?>
                    <?php endif; ?>
                    <button type="submit" class="btn-tec success block lg ponto-acao">Testei ✓</button>
                </form>
            <?php elseif ($aguardando === 'instalado'): ?>
                <form action="<?= site_url('tecnico/ponto_status') ?>" method="post" style="margin-top:10px;" data-exige-foto="Tire ao menos uma foto do equipamento instalado">
                    <input type="hidden" name="obra_id" value="<?= $oid ?>">
                    <input type="hidden" name="ponto_id" value="<?= $pid ?>">
                    <input type="hidden" name="status" value="instalado">
                    <input type="hidden" name="voltar" value="fila">
                    <?php $blocoFoto('Foto do equipamento e etiqueta (obrigatória)'); ?>
                    <?php $blocoFoto('Foto da etiqueta (preenche modelo e série)', true); ?>
                    <label class="ponto-campo">Modelo
                        <input type="text" name="modelo" value="<?= html_escape((string) ($it->modelo ?? '')) ?>" maxlength="80" class="ponto-input">
                    </label>
                    <label class="ponto-campo">Série / MAC
                        <input type="text" name="serie" value="<?= html_escape((string) ($it->serie ?? '')) ?>" maxlength="60" class="ponto-input">
                    </label>
                    <label class="ponto-campo">Local
                        <input type="text" name="local" value="<?= html_escape((string) ($it->local ?? '')) ?>" maxlength="150" class="ponto-input">
                    </label>
                    <label class="ponto-campo">Quem vai configurar?
                        <select name="repassar_para" class="ponto-input">
                            <option value="">Eu mesmo</option>
                            <option value="equipe">Equipe (qualquer técnico)</option>
                            <?php foreach ($destinos as $ex): ?>
                                <option value="<?= (int) $ex['idUsuarios'] ?>"><?= html_escape((string) $ex['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="ponto-campo">Instrução para quem configura
                        <textarea name="instrucao" rows="2" maxlength="255" placeholder="Ex.: canal 7, lente 2.8 mm. Não escreva senhas aqui." class="ponto-input"></textarea>
                    </label>
                    <button type="submit" class="btn-tec primary block lg ponto-acao" style="margin-top:10px;">Instalei ✓</button>
                </form>
            <?php elseif ($aguardando !== ''): ?>
                <form action="<?= site_url('tecnico/ponto_status') ?>" method="post" style="margin-top:10px;">
                    <input type="hidden" name="obra_id" value="<?= $oid ?>">
                    <input type="hidden" name="ponto_id" value="<?= $pid ?>">
                    <input type="hidden" name="status" value="<?= html_escape($aguardando) ?>">
                    <input type="hidden" name="voltar" value="fila">
                    <?php if (Obra_pontos_model::fotoOpcional($aguardando)): ?>
                        <?php $blocoFoto('Foto da infraestrutura (opcional)'); ?>
                    <?php endif; ?>
                    <button type="submit" class="btn-tec primary block lg ponto-acao"><?= html_escape($status_lista[$aguardando]['acao'] ?? $rotulo) ?> ✓</button>
                </form>
            <?php endif; ?>

            <?php if ($aguardando !== 'instalado'): ?>
                <details class="ponto-mais" style="margin-top:8px;">
                    <summary>Repassar</summary>
                    <form action="<?= site_url('tecnico/repassar_ponto') ?>" method="post" style="margin-top:8px;">
                        <input type="hidden" name="obra_id" value="<?= $oid ?>">
                        <input type="hidden" name="ponto_id" value="<?= $pid ?>">
                        <input type="hidden" name="voltar" value="fila">
                        <label class="ponto-campo">Para quem
                            <select name="repassar_para" class="ponto-input" required>
                                <option value="">Escolha...</option>
                                <option value="equipe">Equipe (qualquer técnico)</option>
                                <?php foreach ($destinos as $ex): ?>
                                    <option value="<?= (int) $ex['idUsuarios'] ?>"><?= html_escape((string) $ex['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="ponto-campo">Instrução
                            <textarea name="instrucao" rows="2" maxlength="255" placeholder="Ex.: canal 7, lente 2.8 mm. Não escreva senhas aqui." class="ponto-input"></textarea>
                        </label>
                        <button type="submit" class="btn-tec ghost block lg ponto-acao" style="margin-top:10px;"><i class='bx bx-transfer-alt'></i> Repassar</button>
                    </form>
                </details>
            <?php endif; ?>
        <?php endif; ?>

        <a href="<?= html_escape($urlProjeto) ?>" class="btn-tec ghost block" style="margin-top:8px;text-decoration:none;">Abrir no projeto <i class='bx bx-right-arrow-alt'></i></a>
    </div>
    <?php
};
?>
<style nonce="<?= csp_nonce() ?>">
    .fila-card { margin: 0 0 12px; }
    .fila-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; }
    .ponto-id { font-size: 1.1em; font-weight: 700; }
    .ponto-meta { color: #666; font-size: 13px; margin-top: 4px; }
    .ponto-mais > summary { cursor: pointer; font-weight: 600; color: #55596e; padding: 8px 0; }
    .ponto-campo { display: block; font-weight: 600; margin: 8px 0 0; }
    .ponto-input { width: 100%; border: 1px solid #ccc; border-radius: 8px; padding: 10px; font-size: 15px; margin-top: 4px; font-weight: 400; }
    .ponto-acao { min-height: 48px; width: 100%; }
</style>

<div class="tec-container">

    <?php if ($this->session->flashdata('success')): ?>
        <div class="info-card" style="border-left:4px solid var(--tec-success, #2ecc71); color:var(--tec-success, #2ecc71);"><i class='bx bx-check-circle'></i> <?= html_escape($this->session->flashdata('success')) ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="info-card" style="border-left:4px solid var(--tec-danger, #e74c3c); color:var(--tec-danger, #e74c3c);"><i class='bx bx-error-circle'></i> <?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (empty($fila)): ?>
        <div class="empty-state">
            <i class='bx bx-transfer-alt'></i>
            <p>Nada na sua fila agora. Quando um técnico repassar um ponto para você, ou para a equipe, ele aparece aqui.</p>
            <a href="<?= site_url('tecnico/projetos') ?>" class="btn-tec primary" style="min-height:48px;display:inline-flex;align-items:center;gap:6px;text-decoration:none;"><i class='bx bx-network-chart'></i> Ir para meus projetos</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($minhas)): ?>
        <h2 class="tec-section-title"><i class='bx bx-user'></i> Comigo <span class="count"><?= count($minhas) ?></span></h2>
        <?php foreach ($minhas as $it) { $cartao($it, true); } ?>
    <?php endif; ?>

    <?php if (!empty($equipe)): ?>
        <h2 class="tec-section-title"><i class='bx bx-group'></i> Da equipe (livre) <span class="count"><?= count($equipe) ?></span></h2>
        <?php foreach ($equipe as $it) { $cartao($it, false); } ?>
    <?php endif; ?>
</div>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => 'fila', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/csrf.js') ?>?v=<?= asset_versao('assets/js/csrf.js', '4') ?>"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/foto-compacta.js') ?>?v=<?= asset_versao('assets/js/foto-compacta.js', '1') ?>"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/etiqueta-camera.js') ?>?v=<?= asset_versao('assets/js/etiqueta-camera.js', '1') ?>"></script>
</body>
</html>
