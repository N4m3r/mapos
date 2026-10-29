<?php
$this->load->view('tecnico/_topo', [
    'titulo'       => (string) $obra->nome,
    'header_icone' => 'bx-network-chart',
    'header_sub'   => !empty($obra->nomeCliente) ? (string) $obra->nomeCliente : null,
    'voltar_url'   => site_url('tecnico/projetos'),
]);
// Bloco de foto compactada (liga em foto-compacta.js): botao, miniaturas e erro.
$blocoFoto = function ($rotulo) {
    ?>
    <label class="btn-tec ghost block" style="cursor:pointer;margin-top:8px;">
        <i class='bx bx-camera'></i> <?= html_escape($rotulo) ?>
        <input type="file" accept="image/*" capture="environment" multiple data-foto-compacta style="display:none;">
    </label>
    <div data-foto-preview style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;"></div>
    <div data-foto-erro role="alert" style="display:none;color:#e74c3c;font-weight:600;margin-top:6px;"></div>
    <?php
};
$statusLabels = ['planejamento' => 'Planejamento', 'em_execucao' => 'Em execução', 'paralisada' => 'Paralisada', 'concluida' => 'Concluída', 'entregue' => 'Entregue', 'cancelada' => 'Cancelada'];
$dataBr = function ($d) {
    return (!empty($d) && $d !== '0000-00-00') ? date('d/m/Y', strtotime($d)) : '—';
};
$end = trim(($obra->logradouro ?? '') . ', ' . ($obra->numero ?? '') . ' - ' . ($obra->bairro ?? '') . ' - ' . ($obra->cidade ?? '') . '/' . ($obra->uf ?? ''), ' ,-/');

// Produtos/serviços distintos das OS do projeto (para seleção no registro)
$matDistinct = [];
foreach ($os_produtos as $mp) {
    if (empty($mp->produtos_id)) { continue; }
    $matDistinct[(int) $mp->produtos_id] = $mp->nome ?: 'Produto';
}
$servDistinct = [];
foreach ($os_servicos as $ms) {
    if (empty($ms->servicos_id)) { continue; }
    $servDistinct[(int) $ms->servicos_id] = $ms->nome ?: 'Serviço';
}
?>

<div class="tec-container">

    <?php if ($this->session->flashdata('success')): ?>
        <div class="info-card" style="border-left:4px solid var(--tec-success, #2ecc71); color:var(--tec-success, #2ecc71);"><i class='bx bx-check-circle'></i> <?= html_escape($this->session->flashdata('success')) ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="info-card" style="border-left:4px solid var(--tec-danger, #e74c3c); color:var(--tec-danger, #e74c3c);"><i class='bx bx-error-circle'></i> <?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <a href="<?= site_url('tecnico/meu_relatorio?tipo=semanal') ?>" class="btn-tec ghost" style="display:flex;align-items:center;justify-content:center;gap:8px;min-height:44px;margin-bottom:12px;text-decoration:none;">
        <i class='bx bx-bar-chart-alt-2'></i> Meu relatório da semana
    </a>

    <!-- Info do projeto -->
    <div class="info-card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
            <span class="badge-status pendente"><?= html_escape($statusLabels[$obra->status] ?? $obra->status) ?></span>
            <strong><?= number_format((float) $obra->percentual_concluido, 1, ',', '.') ?>% concluído</strong>
        </div>
        <div style="background:#e9ecef;border-radius:6px;height:8px;overflow:hidden;margin:8px 0;">
            <div style="background:#2b7;height:8px;width:<?= (float) $obra->percentual_concluido ?>%;"></div>
        </div>
        <div style="color:#666;font-size:13px;"><i class='bx bx-map'></i> <?= html_escape($end ?: '—') ?></div>
    </div>

    <?php if (!empty($pontos)): ?>
    <?php
    $prog = $pontos_resumo['progresso'];
    $cabo = (float) $pontos_resumo['cabo_m'];
    $totalPontos = (int) $pontos_resumo['total'];
    $testadosPontos = (int) ($pontos_resumo['por_status']['testado'] ?? 0);
    // Abre o primeiro tipo que ainda tenha ponto nao testado.
    $abrirTipo = null;
    foreach ($pontos_resumo['por_tipo'] as $tk => $tinfo) {
        if ((int) $tinfo['testados'] < (int) $tinfo['total']) { $abrirTipo = $tk; break; }
    }
    $pontosPorTipo = [];
    foreach ($pontos as $pt) { $pontosPorTipo[$pt->tipo][] = $pt; }
    ?>
    <style nonce="<?= csp_nonce() ?>">
        .ponto-card { margin: 0 0 10px; }
        .ponto-card.ponto-testado { opacity: .55; }
        .ponto-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; }
        .ponto-id { font-size: 1.1em; font-weight: 700; }
        .ponto-meta { color: #666; font-size: 13px; margin-top: 4px; }
        .ponto-barra { background: #e9ecef; border-radius: 6px; height: 8px; overflow: hidden; }
        .ponto-barra > div { background: #2b7; height: 8px; }
        .ponto-tipo { margin-bottom: 10px; }
        .ponto-tipo > summary { font-weight: 600; cursor: pointer; padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .ponto-tipo > .ponto-lista { padding: 0 10px 10px; }
        .ponto-mais > summary { cursor: pointer; font-weight: 600; color: #55596e; padding: 8px 0; }
        .ponto-campo { display: block; font-weight: 600; margin: 8px 0 0; }
        .ponto-input { width: 100%; border: 1px solid #ccc; border-radius: 8px; padding: 10px; font-size: 15px; margin-top: 4px; font-weight: 400; }
        .ponto-acao { min-height: 48px; width: 100%; }
    </style>

    <!-- PONTOS DO PROJETO -->
    <h2 class="tec-section-title" id="pontos"><i class='bx bx-git-branch'></i> Pontos do projeto</h2>
    <div class="info-card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
            <strong><?= $prog === null ? '—' : number_format((float) $prog, 1, ',', '.') ?>% concluído</strong>
            <small style="color:#666;"><?= $testadosPontos ?> de <?= $totalPontos ?> pontos testados</small>
        </div>
        <div class="ponto-barra" style="margin:8px 0;"><div style="width:<?= max(0, min(100, (float) $prog)) ?>%;"></div></div>
        <?php foreach ($pontos_resumo['por_tipo'] as $tk => $tinfo):
            $pctTipo = (int) $tinfo['total'] > 0 ? round(((int) $tinfo['testados']) * 100 / (int) $tinfo['total']) : 0;
        ?>
            <div style="margin-top:8px;">
                <div style="font-size:14px;"><i class='bx <?= html_escape($tipos[$tk]['icone'] ?? 'bx-devices') ?>'></i> <?= html_escape($tinfo['rotulo']) ?> <strong><?= (int) $tinfo['testados'] ?>/<?= (int) $tinfo['total'] ?></strong> testadas</div>
                <div class="ponto-barra" style="margin-top:4px;"><div style="width:<?= $pctTipo ?>%;"></div></div>
            </div>
        <?php endforeach; ?>
        <?php if ($cabo > 0): ?>
            <div class="ponto-meta"><i class='bx bx-git-commit'></i> Cabo passado: <?= number_format($cabo, 2, ',', '.') ?> m</div>
        <?php endif; ?>
    </div>

    <?php foreach ($pontos_resumo['por_tipo'] as $tk => $tinfo): ?>
        <details class="info-card ponto-tipo"<?= $tk === $abrirTipo ? ' open' : '' ?> style="padding:0;">
            <summary>
                <span><i class='bx <?= html_escape($tipos[$tk]['icone'] ?? 'bx-devices') ?>'></i> <?= html_escape($tinfo['rotulo']) ?></span>
                <span style="color:#666;font-weight:500;"><?= (int) $tinfo['testados'] ?> de <?= (int) $tinfo['total'] ?> testados</span>
            </summary>
            <div class="ponto-lista">
            <?php
            $uidAtual = (int) ($uid ?? 0);
            foreach ($pontosPorTipo[$tk] ?? [] as $pt):
                $pid = (int) $pt->idPonto;
                $testado = $pt->status === 'testado';
                $info = $status_lista[$pt->status] ?? ['rotulo' => $pt->status, 'classe' => 'neutro'];
                $proximo = Obra_pontos_model::proximoStatus((string) $pt->status);
                $infoProx = $proximo !== null ? ($status_lista[$proximo] ?? null) : null;
                $fotosDoPonto = $fotos_pontos[$pid] ?? [];
                $temInstalado = false;
                foreach ($fotosDoPonto as $fp) {
                    if (($fp->etapa ?? '') === 'instalado') { $temInstalado = true; break; }
                }
                // Com quem esta o ponto ("Com: Fulano" ou "Da equipe") e o que aguarda
                $partesResp = [];
                if (!$testado && !empty($pt->aguardando)) {
                    $partesResp[] = !empty($pt->responsavel_id) ? 'Com: ' . ($pt->responsavel_nome ?? '—') : 'Da equipe';
                    $rotAguardando = Obra_pontos_model::rotuloAguardando($pt->aguardando);
                    if ($rotAguardando !== '') { $partesResp[] = $rotAguardando; }
                }
            ?>
                <div class="info-card ponto-card<?= $testado ? ' ponto-testado' : '' ?>" id="ponto-<?= $pid ?>">
                    <div class="ponto-head">
                        <span class="ponto-id"><?= html_escape((string) $pt->identificacao) ?></span>
                        <?php if (!$testado): ?>
                            <span class="ui-selo <?= html_escape($info['classe']) ?>"><?= html_escape($info['rotulo']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($pt->local)): ?>
                        <div class="ponto-meta"><i class='bx bx-map-pin'></i> <?= html_escape((string) $pt->local) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($pt->ip) || !empty($pt->canal) || !empty($pt->porta)): ?>
                        <div class="ponto-meta">
                            <?php if (!empty($pt->ip)): ?>IP <?= html_escape((string) $pt->ip) ?> <?php endif; ?>
                            <?php if (!empty($pt->canal)): ?>· Canal <?= html_escape((string) $pt->canal) ?> <?php endif; ?>
                            <?php if (!empty($pt->porta)): ?>· Porta <?= html_escape((string) $pt->porta) ?><?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($fotosDoPonto):
                        $capa = $fotosDoPonto[0];
                        $urlFoto = site_url('obras/fotoPonto/' . (int) $capa->idFoto);
                    ?>
                        <div style="display:flex;align-items:center;gap:10px;margin-top:8px;">
                            <a href="<?= html_escape($urlFoto) ?>" target="_blank" rel="noopener">
                                <img src="<?= html_escape($urlFoto) ?>" loading="lazy" alt="Foto do equipamento" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid #ccc;">
                            </a>
                            <small style="color:#666;"><i class='bx bx-camera'></i> <?= count($fotosDoPonto) ?> foto(s)</small>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($partesResp)): ?>
                        <div class="ponto-meta"><i class='bx bx-user'></i> <?= html_escape(implode(' · ', $partesResp)) ?></div>
                    <?php endif; ?>
                    <?php if (!$testado && !empty($pt->instrucao)): ?>
                        <div class="ponto-meta" style="color:#55596e;"><i class='bx bx-note'></i> <?= html_escape((string) $pt->instrucao) ?></div>
                    <?php endif; ?>

                    <?php if ($testado): ?>
                        <div style="color:#12a454;font-weight:700;margin-top:8px;">Testado ✓</div>
                    <?php elseif ($infoProx && $proximo === 'instalado'): ?>
                        <form action="<?= site_url('tecnico/ponto_status') ?>" method="post" style="margin-top:10px;" data-exige-foto="Tire ao menos uma foto do equipamento instalado"<?= $temInstalado ? ' data-ja-tem-foto="1"' : '' ?>>
                            <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
                            <input type="hidden" name="ponto_id" value="<?= $pid ?>">
                            <input type="hidden" name="status" value="instalado">
                            <?php $blocoFoto($temInstalado ? 'Foto do equipamento e etiqueta (opcional, já tem foto)' : 'Foto do equipamento e etiqueta (obrigatória)'); ?>
                            <label class="ponto-campo">Modelo
                                <input type="text" name="modelo" value="<?= html_escape((string) $pt->modelo) ?>" maxlength="80" class="ponto-input">
                            </label>
                            <label class="ponto-campo">Série / MAC
                                <input type="text" name="serie" value="<?= html_escape((string) $pt->serie) ?>" maxlength="60" class="ponto-input">
                            </label>
                            <label class="ponto-campo">Local
                                <input type="text" name="local" value="<?= html_escape((string) $pt->local) ?>" maxlength="150" class="ponto-input">
                            </label>
                            <label class="ponto-campo">Quem vai configurar?
                                <select name="repassar_para" class="ponto-input">
                                    <option value="">Eu mesmo</option>
                                    <option value="equipe">Equipe (qualquer técnico)</option>
                                    <?php foreach ($executores ?? [] as $ex):
                                        if ((int) $ex['idUsuarios'] === $uidAtual) { continue; }
                                    ?>
                                        <option value="<?= (int) $ex['idUsuarios'] ?>"><?= html_escape((string) $ex['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="ponto-campo">Instrução para quem configura
                                <textarea name="instrucao" rows="2" maxlength="255" placeholder="Ex.: canal 7, lente 2.8 mm. Não escreva senhas aqui." class="ponto-input"></textarea>
                            </label>
                            <div class="ponto-meta">Senhas de câmera ou NVR não são guardadas no sistema.</div>
                            <button type="submit" class="btn-tec primary block lg ponto-acao" style="margin-top:10px;"><?= html_escape($infoProx['acao'] ?? 'Instalei') ?> ✓</button>
                        </form>
                    <?php elseif ($infoProx): ?>
                        <form action="<?= site_url('tecnico/ponto_status') ?>" method="post" style="margin-top:10px;">
                            <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
                            <input type="hidden" name="ponto_id" value="<?= $pid ?>">
                            <input type="hidden" name="status" value="<?= html_escape($proximo) ?>">
                            <?php if ($proximo === 'cabeado'): ?>
                                <label class="ponto-campo">Metros de cabo (opcional)
                                    <input type="number" name="metragem_cabo" min="0" step="0.01" inputmode="decimal" placeholder="0" class="ponto-input">
                                </label>
                            <?php endif; ?>
                            <button type="submit" class="btn-tec primary block lg ponto-acao"><?= html_escape($infoProx['acao'] ?? $infoProx['rotulo']) ?> ✓</button>
                        </form>
                    <?php endif; ?>

                    <?php if (!$testado): ?>
                        <details class="ponto-mais" style="margin-top:8px;">
                            <summary>Mais</summary>
                            <form action="<?= site_url('tecnico/ponto_status') ?>" method="post">
                                <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
                                <input type="hidden" name="ponto_id" value="<?= $pid ?>">
                                <label class="ponto-campo">Status
                                    <select name="status" class="ponto-input">
                                        <?php foreach ($status_lista as $sk => $sinfo): ?>
                                            <option value="<?= html_escape($sk) ?>"<?= $sk === $pt->status ? ' selected' : '' ?>><?= html_escape($sinfo['rotulo']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="ponto-campo">IP
                                    <input type="text" name="ip" value="<?= html_escape((string) $pt->ip) ?>" maxlength="45" class="ponto-input">
                                </label>
                                <label class="ponto-campo">Canal (NVR) / porta do controlador
                                    <input type="text" name="canal" value="<?= html_escape((string) $pt->canal) ?>" maxlength="10" class="ponto-input">
                                </label>
                                <label class="ponto-campo">Porta (patch panel / switch)
                                    <input type="text" name="porta" value="<?= html_escape((string) $pt->porta) ?>" maxlength="30" class="ponto-input">
                                </label>
                                <label class="ponto-campo">Metros de cabo nesta etapa
                                    <input type="number" name="metragem_cabo" min="0" step="0.01" inputmode="decimal" placeholder="0" class="ponto-input">
                                </label>
                                <label class="ponto-campo">Observação
                                    <input type="text" name="observacao" maxlength="255" class="ponto-input">
                                </label>
                                <?php $blocoFoto('Foto do equipamento (opcional)'); ?>
                                <button type="submit" class="btn-tec success block lg ponto-acao" style="margin-top:10px;"><i class='bx bx-save'></i> Salvar</button>
                            </form>

                            <form action="<?= site_url('tecnico/repassar_ponto') ?>" method="post" style="margin-top:14px;border-top:1px solid #eee;padding-top:10px;">
                                <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
                                <input type="hidden" name="ponto_id" value="<?= $pid ?>">
                                <strong><i class='bx bx-transfer-alt'></i> Repassar este ponto</strong>
                                <label class="ponto-campo">Para quem
                                    <select name="repassar_para" class="ponto-input" required>
                                        <option value="">Escolha...</option>
                                        <option value="equipe">Equipe (qualquer técnico)</option>
                                        <?php foreach ($executores ?? [] as $ex):
                                            if ((int) $ex['idUsuarios'] === $uidAtual) { continue; }
                                        ?>
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
                </div>
            <?php endforeach; ?>
            </div>
        </details>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- REGISTRO RÁPIDO (one-click) -->
    <h2 class="tec-section-title"><i class='bx bx-check-circle'></i> Registrar o que foi feito</h2>
    <form action="<?= site_url('tecnico/salvar_registro_projeto') ?>" method="post" class="info-card" id="formRegistro">
        <input type="hidden" name="obra_id" value="<?= $obra->idObra ?>">
        <label style="font-weight:600;display:block;margin-bottom:6px;">O que você fez hoje? (<?= date('d/m/Y') ?>)</label>
        <textarea name="atividades" rows="4" placeholder="Descreva a atividade realizada... (opcional)" style="width:100%;border:1px solid #ccc;border-radius:8px;padding:10px;font-size:15px;"></textarea>

        <?php if (!empty($matDistinct)): ?>
            <details style="margin-top:10px;border:1px solid #eee;border-radius:8px;padding:6px 10px;">
                <summary style="font-weight:600;cursor:pointer;"><i class='bx bx-package'></i> Material utilizado (opcional)</summary>
                <p style="color:#888;font-size:12px;margin:6px 0;">Informe a quantidade usada — dá baixa no estoque.</p>
                <?php foreach ($matDistinct as $pid => $pnome): ?>
                    <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f2f2f2;">
                        <span style="flex:1;"><?= html_escape($pnome) ?></span>
                        <input type="hidden" name="material_nome[<?= $pid ?>]" value="<?= html_escape($pnome) ?>">
                        <input type="number" name="material[<?= $pid ?>]" min="0" step="0.01" placeholder="0" style="width:80px;border:1px solid #ccc;border-radius:6px;padding:6px;text-align:center;">
                    </div>
                <?php endforeach; ?>
            </details>
        <?php endif; ?>

        <?php if (!empty($servDistinct)): ?>
            <details style="margin-top:10px;border:1px solid #eee;border-radius:8px;padding:6px 10px;">
                <summary style="font-weight:600;cursor:pointer;"><i class='bx bx-list-check'></i> Serviços realizados (opcional)</summary>
                <p style="color:#888;font-size:12px;margin:6px 0;">Informe a quantidade realizada de cada serviço.</p>
                <?php foreach ($servDistinct as $sid => $snome): ?>
                    <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f2f2f2;">
                        <span style="flex:1;"><?= html_escape($snome) ?></span>
                        <input type="hidden" name="servico_nome[<?= $sid ?>]" value="<?= html_escape($snome) ?>">
                        <input type="number" name="servico[<?= $sid ?>]" min="0" step="0.01" placeholder="0" style="width:80px;border:1px solid #ccc;border-radius:6px;padding:6px;text-align:center;">
                    </div>
                <?php endforeach; ?>
            </details>
        <?php endif; ?>

        <label class="btn-tec ghost block" style="margin-top:10px;cursor:pointer;">
            <i class='bx bx-camera'></i> Adicionar fotos (opcional)
            <input type="file" accept="image/*" capture="environment" multiple id="regFotos" style="display:none;">
        </label>
        <div id="regFotosHidden"></div>
        <div id="regFotosPrev" style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;"></div>

        <!-- Assinatura do cliente (opcional) -->
        <label style="display:flex;align-items:center;gap:10px;margin-top:14px;cursor:pointer;">
            <input type="checkbox" id="reg-quer-assinatura" style="width:20px;height:20px;">
            <span style="font-weight:600;"><i class='bx bx-pen'></i> Coletar assinatura do cliente</span>
        </label>
        <div id="reg-assinatura-box" style="display:none;margin-top:8px;">
            <p style="color:#888;font-size:12px;margin:6px 0;">Entregue o aparelho ao cliente para assinar abaixo.</p>
            <div style="border:2px dashed #c7ccdd;border-radius:12px;background:#fff;touch-action:none;">
                <canvas id="reg-assinatura-canvas" style="display:block;width:100%;touch-action:none;border-radius:10px;"></canvas>
            </div>
            <button type="button" class="btn-tec neutral" id="reg-assinatura-limpar" style="margin-top:8px;">
                <i class='bx bx-eraser'></i> Limpar assinatura
            </button>
        </div>
        <input type="hidden" name="assinatura" id="reg-assinatura-hidden">

        <button type="submit" class="btn-tec success block lg" style="margin-top:12px;">
            <i class='bx bx-save'></i> Registrar execução
        </button>
    </form>

    <!-- O QUE EXECUTAR -->
    <h2 class="tec-section-title"><i class='bx bx-list-check'></i> O que executar</h2>
    <?php if (!empty($os_servicos)): ?>
        <div class="info-card" style="padding:0;">
            <?php foreach ($os_servicos as $s): ?>
                <div style="padding:10px 12px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;gap:8px;">
                    <span><i class='bx bx-wrench'></i> <?= html_escape($s->nome ?: '—') ?></span>
                    <span style="color:#888;">OS #<?= (int) $s->os_id ?> · <?= number_format((float) $s->quantidade, 0, ',', '.') ?>x</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state" style="padding:16px;"><i class='bx bx-list-check'></i><p>Nenhum serviço nas OS deste projeto.</p></div>
    <?php endif; ?>

    <!-- MATERIAL A UTILIZAR -->
    <h2 class="tec-section-title"><i class='bx bx-package'></i> Material a utilizar</h2>
    <?php if (!empty($os_produtos)): ?>
        <div class="info-card" style="padding:0;">
            <?php foreach ($os_produtos as $p): ?>
                <div style="padding:10px 12px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;gap:8px;">
                    <span><i class='bx bx-box'></i> <?= html_escape($p->nome ?: '—') ?></span>
                    <span style="color:#888;">OS #<?= (int) $p->os_id ?> · <?= number_format((float) $p->quantidade, 0, ',', '.') ?>x</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state" style="padding:16px;"><i class='bx bx-package'></i><p>Nenhum material nas OS deste projeto.</p></div>
    <?php endif; ?>

    <!-- ORDENS DE SERVIÇO -->
    <h2 class="tec-section-title"><i class='bx bx-clipboard'></i> Ordens de Serviço
        <?php if (!empty($os_vinculadas)): ?><span class="count"><?= count($os_vinculadas) ?></span><?php endif; ?>
    </h2>
    <?php if (!empty($os_vinculadas)): ?>
        <?php foreach ($os_vinculadas as $o): ?>
            <a href="<?= site_url('tecnico/visualizar/' . $o->idOs) ?>" style="text-decoration:none;color:inherit;display:block;">
                <div class="os-card">
                    <div class="os-head">
                        <span class="os-num">#OS <?= sprintf('%04d', $o->idOs) ?></span>
                        <span class="badge-status pendente"><?= html_escape($o->status ?: '—') ?></span>
                    </div>
                    <div class="os-cliente"><i class='bx bx-user'></i> <?= html_escape($o->nomeCliente ?: '—') ?></div>
                    <div class="os-foot">
                        <span class="os-meta"><span><i class='bx bx-wrench'></i> <?= html_escape($o->tecnico ?: 'Sem técnico') ?></span></span>
                        <span class="btn-tec primary">Abrir <i class='bx bx-right-arrow-alt'></i></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state" style="padding:16px;"><i class='bx bx-clipboard'></i><p>Nenhuma OS vinculada a este projeto.</p></div>
    <?php endif; ?>

    <!-- CONSUMO REGISTRADO -->
    <?php
    $consMat = array_filter($consumo, function ($c) { return $c->tipo === 'material'; });
    $consServ = array_filter($consumo, function ($c) { return $c->tipo === 'servico'; });
    ?>
    <?php if (!empty($consMat) || !empty($consServ)): ?>
        <h2 class="tec-section-title"><i class='bx bx-task'></i> Já registrado</h2>
        <?php if (!empty($consMat)): ?>
            <div class="info-card" style="padding:0;">
                <div style="padding:8px 12px;font-weight:600;background:#f7f7f7;"><i class='bx bx-package'></i> Material utilizado</div>
                <?php foreach ($consMat as $c): ?>
                    <div style="padding:8px 12px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;gap:8px;">
                        <span><?= html_escape($c->descricao ?: 'Produto') ?></span>
                        <span style="color:#888;"><?= number_format((float) $c->quantidade, 2, ',', '.') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($consServ)): ?>
            <div class="info-card" style="padding:0;">
                <div style="padding:8px 12px;font-weight:600;background:#f7f7f7;"><i class='bx bx-list-check'></i> Serviços realizados</div>
                <?php foreach ($consServ as $c): ?>
                    <div style="padding:8px 12px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;gap:8px;">
                        <span><i class='bx bx-check' style="color:#2b7;"></i> <?= html_escape($c->descricao ?: 'Serviço') ?></span>
                        <span style="color:#888;"><?= number_format((float) $c->quantidade, 2, ',', '.') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- DIÁRIO / MEUS REGISTROS -->
    <h2 class="tec-section-title"><i class='bx bx-book'></i> Meus registros</h2>
    <?php if (!empty($rdos)): ?>
        <?php foreach ($rdos as $r): ?>
            <div class="info-card">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                    <strong>#<?= (int) $r->numero ?> · <?= $dataBr($r->data) ?></strong>
                    <a href="<?= site_url('obras/imprimirRdo/' . $r->idRdo) ?>" target="_blank" class="btn-tec ghost" style="padding:6px 10px;"><i class='bx bx-printer'></i></a>
                </div>
                <div style="color:#555;margin-top:4px;"><?= html_escape($r->atividades ?: '—') ?></div>
                <small style="color:#999;"><i class='bx bx-user'></i> <?= html_escape($r->responsavel ?: '—') ?></small>
                <?php if (!empty($r->assinatura)): ?>
                    <small style="color:#2b7;margin-left:8px;"><i class='bx bx-pen'></i> Assinado pelo cliente</small>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state" style="padding:16px;"><i class='bx bx-book'></i><p>Nenhum registro ainda. Use o botão acima para registrar.</p></div>
    <?php endif; ?>
</div>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => 'projetos', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/assinatura-canvas.js') ?>"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/csrf.js') ?>?v=<?= asset_versao('assets/js/csrf.js', '4') ?>"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/foto-compacta.js') ?>?v=<?= asset_versao('assets/js/foto-compacta.js', '1') ?>"></script>
<script nonce="<?= csp_nonce() ?>">
    // Assinatura do cliente (opcional) — revelada pelo checkbox.
    (function () {
        var chk = document.getElementById('reg-quer-assinatura');
        var box = document.getElementById('reg-assinatura-box');
        var hidden = document.getElementById('reg-assinatura-hidden');
        var form = document.getElementById('formRegistro');
        if (!chk || !box || !form) return;
        var manager = null;
        chk.addEventListener('change', function () {
            box.style.display = this.checked ? 'block' : 'none';
            if (this.checked && !manager && typeof AssinaturaManager !== 'undefined') {
                // Cria depois de exibir para o canvas medir a largura certa.
                manager = AssinaturaManager.criar('rdo', 'reg-assinatura-canvas');
            }
        });
        var limpar = document.getElementById('reg-assinatura-limpar');
        if (limpar) {
            limpar.addEventListener('click', function () { if (manager) manager.limpar(); });
        }
        form.addEventListener('submit', function () {
            hidden.value = (chk.checked && manager && !manager.estaVazio()) ? manager.obterImagem() : '';
        });
    })();

    (function () {
        var input = document.getElementById('regFotos');
        if (!input) return;
        input.addEventListener('change', function () {
            var hidden = document.getElementById('regFotosHidden');
            var prev = document.getElementById('regFotosPrev');
            hidden.innerHTML = '';
            prev.innerHTML = '';
            Array.prototype.forEach.call(this.files, function (file) {
                var reader = new FileReader();
                reader.onload = function (ev) {
                    var h = document.createElement('input');
                    h.type = 'hidden'; h.name = 'fotos[]'; h.value = ev.target.result;
                    hidden.appendChild(h);
                    var img = document.createElement('img');
                    img.src = ev.target.result;
                    img.style.cssText = 'width:70px;height:70px;object-fit:cover;border-radius:8px;border:1px solid #ccc;';
                    prev.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    })();
</script>
</body>
</html>
