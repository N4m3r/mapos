<?php
$this->load->view('tecnico/_topo', [
    'titulo'       => 'Meu relatório',
    'header_icone' => 'bx-bar-chart-alt-2',
    'header_sub'   => 'O que você registrou nos projetos',
]);

$projetos = isset($relatorio['projetos']) ? $relatorio['projetos'] : [];
$totais   = isset($relatorio['totais']) ? $relatorio['totais'] : [];
$tot = function ($k) use ($totais) { return (int) ($totais[$k] ?? 0); };
$dd = function ($d) { return $d ? date('d/m', strtotime($d)) : ''; };
$qtd = function ($v) { return rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ','); };
$url = function ($tipo, $data) { return site_url('tecnico/meu_relatorio?tipo=' . $tipo . '&data=' . $data); };
$hoje = date('Y-m-d');
$ref = $periodo['inicio'];
// Uma linha por avanço de ponto: "29/10 14:05 · CAM-07 (Corredor B) → Cabo passado · 35 m".
$linhaAvanco = function (array $av) use ($qtd) {
    $hora = !empty($av['data']) ? date('d/m H:i', strtotime($av['data'])) : '';
    $evento = $av['evento'] ?? 'status';
    // Repasse e assumir (ADR-014): "29/10 14:05 · CAM-07 repassado para Maria" / "· assumiu CAM-07"
    if ($evento === 'repasse') {
        return $hora . ' · ' . ($av['identificacao'] ?? '') . ' repassado para ' . (($av['destinatario'] ?? '') ?: 'a equipe');
    }
    if ($evento === 'assumiu') {
        return $hora . ' · assumiu ' . ($av['identificacao'] ?? '');
    }
    $linha = $hora . ' · ' . ($av['identificacao'] ?? '');
    if (!empty($av['local'])) {
        $linha .= ' (' . $av['local'] . ')';
    }
    $linha .= ' → ' . ($av['para_rotulo'] ?? '');
    if ((float) ($av['metragem_cabo'] ?? 0) > 0) {
        $linha .= ' · ' . $qtd($av['metragem_cabo']) . ' m';
    }
    return $linha;
};

// Texto do resumo para compartilhar (montado no servidor).
$txt = "Meu relatório - " . $periodo['rotulo'] . "\n";
$txt .= $tot('registros') . ' registro(s), ' . $tot('projetos') . ' projeto(s), ' . $tot('fotos') . ' foto(s), ' . $tot('ocorrencias') . " ocorrência(s)\n";
$txt .= $tot('avancos') . ' ponto(s) avançado(s), ' . $qtd($totais['cabo_m'] ?? 0) . " m de cabo\n";
foreach ($projetos as $pj) {
    $txt .= "\n" . $pj['obra']['nome'] . "\n";
    foreach ($pj['registros'] as $r) {
        $txt .= '- ' . $dd($r['data']) . ': ' . trim(preg_replace('/\s+/', ' ', (string) $r['atividades'])) . "\n";
        if (trim((string) $r['ocorrencias']) !== '') {
            $txt .= '  Ocorrência: ' . trim(preg_replace('/\s+/', ' ', (string) $r['ocorrencias'])) . "\n";
        }
    }
    if (!empty($pj['avancos'] ?? [])) {
        $txt .= "Pontos avançados:\n";
        foreach ($pj['avancos'] as $av) {
            $txt .= '- ' . $linhaAvanco($av) . "\n";
        }
    }
}
?>

<div class="tec-container">

    <h2 class="tec-section-title"><i class='bx bx-bar-chart-alt-2'></i> Meu relatório</h2>
    <div class="info-card" style="text-align:center;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
            <a href="<?= $url($tipo, $periodo['anterior']) ?>" class="btn-tec ghost" style="min-width:48px;min-height:48px;display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:1.4em;" aria-label="Período anterior">&lsaquo;</a>
            <strong style="flex:1;"><?= html_escape($periodo['rotulo']) ?></strong>
            <a href="<?= $url($tipo, $periodo['proximo']) ?>" class="btn-tec ghost" style="min-width:48px;min-height:48px;display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:1.4em;" aria-label="Próximo período">&rsaquo;</a>
        </div>
        <div style="display:flex;gap:8px;margin-top:10px;">
            <?php foreach (['diario' => 'Dia', 'semanal' => 'Semana', 'mensal' => 'Mês'] as $k => $nome): ?>
                <a href="<?= $url($k, $ref) ?>" class="btn-tec <?= $tipo === $k ? 'primary' : 'ghost' ?>" style="flex:1;min-height:48px;display:flex;align-items:center;justify-content:center;text-decoration:none;"><?= $nome ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:12px;">
        <?php foreach ([['registros', 'Registros', 'bx-edit'], ['projetos', 'Projetos', 'bx-network-chart'], ['fotos', 'Fotos', 'bx-camera'], ['ocorrencias', 'Ocorrências', 'bx-error'], ['avancos', 'Pontos avançados', 'bx-git-branch'], ['cabo_m', 'Cabo (m)', 'bx-git-commit']] as $c): ?>
            <div class="info-card" style="text-align:center;margin:0;">
                <i class='bx <?= $c[2] ?>' style="font-size:1.4em;"></i>
                <div style="font-size:1.6em;font-weight:700;"><?= $c[0] === 'cabo_m' ? $qtd($totais['cabo_m'] ?? 0) : $tot($c[0]) ?></div>
                <small style="color:#888;"><?= $c[1] ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($projetos)): ?>
        <div class="empty-state">
            <i class='bx bx-folder-open'></i>
            <p>Nenhum registro neste período.</p>
            <a href="<?= site_url('tecnico/projetos') ?>" class="btn-tec primary" style="min-height:48px;display:inline-flex;align-items:center;gap:6px;text-decoration:none;"><i class='bx bx-network-chart'></i> Ir para meus projetos</a>
        </div>
    <?php else: ?>
        <?php foreach ($projetos as $pj): $o = $pj['obra']; ?>
            <div class="os-card">
                <div class="os-head">
                    <span class="os-num"><i class='bx bx-network-chart'></i> <?= html_escape($o['nome']) ?></span>
                </div>
                <div class="os-cliente"><i class='bx bx-user'></i> <?= html_escape($o['cliente'] ?: '—') ?></div>
                <div style="margin:8px 0 4px;">
                    <div style="background:#e9ecef;border-radius:6px;height:8px;overflow:hidden;">
                        <div style="background:#2b7;height:8px;width:<?= max(0, min(100, (float) $o['percentual_concluido'])) ?>%;"></div>
                    </div>
                    <small style="color:#888;"><?= number_format((float) $o['percentual_concluido'], 1, ',', '.') ?>% concluído</small>
                </div>

                <?php if (!empty($pj['avancos'] ?? [])): ?>
                    <div style="border-top:1px solid #eee;padding:10px 0;">
                        <strong><i class='bx bx-git-branch'></i> Pontos avançados</strong>
                        <?php foreach ($pj['avancos'] as $av): ?>
                            <div style="margin-top:4px;font-size:.92em;color:#555;"><?= html_escape($linhaAvanco($av)) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php foreach ($pj['registros'] as $r): ?>
                    <div style="border-top:1px solid #eee;padding:10px 0;">
                        <strong><i class='bx bx-calendar'></i> <?= html_escape($dd($r['data'])) ?></strong>
                        <div style="margin-top:4px;"><?= nl2br(html_escape((string) $r['atividades'])) ?></div>
                        <?php if (trim((string) $r['ocorrencias']) !== ''): ?>
                            <div style="margin-top:6px;padding:8px;border-left:4px solid #e74c3c;background:#fdecea;border-radius:4px;">
                                <strong><i class='bx bx-error'></i> Ocorrência:</strong> <?= nl2br(html_escape((string) $r['ocorrencias'])) ?>
                            </div>
                        <?php endif; ?>
                        <?php foreach (['materiais' => 'Material', 'servicos' => 'Serviço'] as $ch => $rot): ?>
                            <?php if (!empty($r[$ch])): ?>
                                <div style="margin-top:6px;font-size:.92em;color:#555;">
                                    <strong><?= $rot ?>:</strong>
                                    <?php $parts = []; foreach ($r[$ch] as $it) { $parts[] = html_escape($it['descricao'] ?: '—') . ' (' . $qtd($it['quantidade']) . ')'; } echo implode(', ', $parts); ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (!empty($r['fotos'])): ?>
                            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;">
                                <?php foreach ($r['fotos'] as $fid): ?>
                                    <a href="<?= site_url('obras/fotoRdo/' . (int) $fid) ?>" target="_blank" rel="noopener"><img src="<?= site_url('obras/fotoRdo/' . (int) $fid) ?>" loading="lazy" alt="Foto do registro" style="width:84px;height:84px;object-fit:cover;border-radius:6px;"></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="os-foot">
                    <span></span>
                    <a href="<?= site_url('tecnico/projeto/' . (int) $o['idObra']) ?>" class="btn-tec primary" style="min-height:44px;display:inline-flex;align-items:center;gap:6px;text-decoration:none;">Abrir projeto <i class='bx bx-right-arrow-alt'></i></a>
                </div>
            </div>
        <?php endforeach; ?>

        <button type="button" id="btn-compartilhar" class="btn-tec ghost" style="width:100%;min-height:48px;margin-top:8px;"><i class='bx bx-share-alt'></i> Compartilhar resumo</button>
        <script nonce="<?= csp_nonce() ?>">
        (function () {
            var texto = <?= json_encode($txt, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
            var btn = document.getElementById('btn-compartilhar');
            btn.addEventListener('click', function () {
                if (navigator.share) {
                    navigator.share({ title: 'Meu relatório', text: texto }).catch(function () {});
                } else if (navigator.clipboard) {
                    navigator.clipboard.writeText(texto).then(function () { btn.textContent = 'Resumo copiado!'; });
                }
            });
        })();
        </script>
    <?php endif; ?>
</div>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => 'projetos', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>
</body>
</html>
