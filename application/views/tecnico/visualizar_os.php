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

// Endereco para navegacao
$endParts = array_filter([
    isset($cliente->rua) ? $cliente->rua : '',
    isset($cliente->numero) ? $cliente->numero : '',
    isset($cliente->bairro) ? $cliente->bairro : '',
    isset($cliente->cidade) ? $cliente->cidade : '',
    isset($cliente->estado) ? $cliente->estado : '',
]);
$enderecoStr = trim(implode(', ', $endParts));
$mapUrl  = $enderecoStr !== '' ? 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($enderecoStr) : '';
$wazeUrl = $enderecoStr !== '' ? 'https://waze.com/ul?q=' . rawurlencode($enderecoStr) . '&navigate=yes' : '';

$documentoCliente = isset($cliente->documento) ? $cliente->documento : (isset($cliente->cpf) ? $cliente->cpf : '');

// Estado da OS (usado no cabecalho, no assistente e na barra de acao)
$nr_pendente = isset($nao_realizada_pendente) ? $nao_realizada_pendente : null;
$os_concluida = in_array($os->status, ['Finalizado', 'Faturado', 'Cancelado'], true);
$tem_assinatura = !empty($assinaturas);
$atendimento_encerrado = $os_concluida || (!$checkin_ativo && $tem_assinatura);

// Status em palavras simples
if ($nr_pendente) {
    $statusTxt = 'Não deu para fazer'; $statusCls = 'nao';
} elseif ($atendimento_encerrado) {
    $statusTxt = $os->status === 'Cancelado' ? 'Cancelada' : 'Concluída'; $statusCls = 'ok';
} elseif ($checkin_ativo || $os->status === 'Em Andamento') {
    $statusTxt = 'Em atendimento'; $statusCls = 'andando';
} elseif ($os->status === 'Aguardando Peças') {
    $statusTxt = 'Aguardando peças'; $statusCls = 'espera';
} else {
    $statusTxt = 'Agendada'; $statusCls = 'agendada';
}

// Tipo de servico (campo opcional, criado em outra tarefa)
$tipoServico = isset($os->tipo_servico) && !is_numeric($os->tipo_servico) ? trim((string) $os->tipo_servico) : '';
$tipoIcone = 'bx-wrench';
if ($tipoServico !== '') {
    $t = function_exists('mb_strtolower') ? mb_strtolower($tipoServico, 'UTF-8') : strtolower($tipoServico);
    $mapaIcones = [
        'cftv' => 'bx-cctv', 'câmera' => 'bx-cctv', 'camera' => 'bx-cctv',
        'alarme' => 'bx-bell', 'acesso' => 'bx-lock-alt', 'interfon' => 'bx-phone-call',
        'cerca' => 'bx-bolt-circle', 'portão' => 'bx-door-open', 'portao' => 'bx-door-open',
        'rede' => 'bx-network-chart', 'wifi' => 'bx-wifi',
    ];
    foreach ($mapaIcones as $chave => $ic) {
        if (strpos($t, $chave) !== false) { $tipoIcone = $ic; break; }
    }
}

// Descricao / defeito em texto simples
$limpar = function ($txt) {
    return trim(strip_tags(html_entity_decode((string) $txt, ENT_QUOTES, 'UTF-8')));
};
$descricaoTxt = $limpar($os->descricaoProduto);
$defeitoTxt = $limpar($os->defeito);
$servicos_previstos = isset($servicos_previstos) ? $servicos_previstos : [];
$produtos_previstos = isset($produtos_previstos) ? $produtos_previstos : [];
$historico_cliente = isset($historico_cliente) ? $historico_cliente : [];
$fmtQtd = function ($q) {
    $f = (float) $q;
    return $f == (int) $f ? (string) (int) $f : rtrim(rtrim(number_format($f, 2, ',', ''), '0'), ',');
};
$statusSimples = function ($st) {
    $m = ['Aberto' => 'Agendada', 'Em Andamento' => 'Em atendimento', 'Finalizado' => 'Concluída', 'Faturado' => 'Concluída', 'Cancelado' => 'Cancelada', 'Orçamento' => 'Orçamento', 'Aguardando Peças' => 'Aguardando peças'];
    return isset($m[$st]) ? $m[$st] : $st;
};
?>
<link rel="stylesheet" href="<?= base_url('assets/css/tecnico-atendimento.css') ?>?v=<?= asset_versao('assets/css/tecnico-atendimento.css', '1') ?>">

<div class="tec-container">

    <!-- Banner: OS aberta pelo projeto (somente leitura) -->
    <?php if (!empty($somente_leitura)): ?>
        <div class="info-card" style="border-left:4px solid var(--tec-grad-1, #667eea);">
            <h3 style="color:var(--tec-grad-1, #667eea);"><i class='bx bx-show'></i> Visualização pelo projeto</h3>
            <p style="margin:0;color:#666;">Esta OS está vinculada a um projeto seu. Você pode consultar os detalhes, mas o atendimento é feito pelo técnico responsável pela OS.</p>
        </div>
    <?php endif; ?>

    <!-- Cabecalho da visita -->
    <section class="tec-cab" aria-label="Cliente e endereço">
        <div class="tec-cab-topo">
            <span class="tec-status tec-status-<?= $statusCls ?>" role="status"><?= html_escape($statusTxt) ?></span>
            <?php
    // rotulo e icone oficiais do tipo de servico (tipo_servico_helper)
    if ($tipoServico !== '') {
        $this->load->helper('tipo_servico');
        if (function_exists('tipo_servico_valido') && tipo_servico_valido($tipoServico)) {
            $tipoIcone = tipo_servico_icone($tipoServico);
            $tipoServico = tipo_servico_rotulo($tipoServico);
        }
    }
?>
<?php if ($tipoServico !== ''): ?>
                <span class="tec-selo-tipo"><i class='bx <?= $tipoIcone ?>' aria-hidden="true"></i> <?= html_escape($tipoServico) ?></span>
            <?php endif; ?>
            <?php if (!empty($os->nao_programada)): ?>
                <span class="tec-selo-tipo"><i class='bx bx-bolt-circle' aria-hidden="true"></i> Não programada</span>
            <?php endif; ?>
        </div>
        <h2 class="tec-cab-cliente"><i class='bx bx-user' aria-hidden="true"></i> <?= html_escape($cliente->nomeCliente) ?></h2>
        <p class="tec-cab-end"><i class='bx bx-map' aria-hidden="true"></i> <?= $enderecoStr !== '' ? html_escape($enderecoStr) : 'Endereço não informado' ?></p>
        <?php if ($fone): ?><p class="tec-cab-end"><i class='bx bx-phone' aria-hidden="true"></i> <?= html_escape($fone) ?></p><?php endif; ?>
        <div class="tec-cab-botoes">
            <?php if ($mapUrl): ?>
                <a href="<?= html_escape($mapUrl) ?>" target="_blank" rel="noopener" class="tec-btn tec-btn-azul"><i class='bx bxs-navigation' aria-hidden="true"></i> Ir até lá (Google Maps)</a>
                <a href="<?= html_escape($wazeUrl) ?>" target="_blank" rel="noopener" class="tec-btn tec-btn-azul"><i class='bx bx-navigation' aria-hidden="true"></i> Ir até lá (Waze)</a>
            <?php endif; ?>
            <?php if ($foneTel): ?>
                <a href="tel:<?= html_escape($foneTel) ?>" class="tec-btn tec-btn-verde"><i class='bx bx-phone' aria-hidden="true"></i> Ligar</a>
                <?php if ($wa): ?>
                    <a href="https://wa.me/<?= html_escape($wa) ?>" target="_blank" rel="noopener" class="tec-btn tec-btn-verde"><i class='bx bxl-whatsapp' aria-hidden="true"></i> WhatsApp</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Banner: serviço não realizado (em espera) -->
    <?php if ($nr_pendente): ?>
        <div class="info-card" style="border-left:4px solid var(--tec-danger, #e74c3c);">
            <h3 style="color:var(--tec-danger, #e74c3c);"><i class='bx bx-x-circle'></i> Não deu para fazer o serviço</h3>
            <?php if (!empty($nr_pendente->motivo_texto)): ?>
                <div class="info-row"><span class="info-label">Motivo</span><span class="info-value"><?= html_escape($nr_pendente->motivo_texto) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($nr_pendente->observacao)): ?>
                <div class="info-row"><span class="info-label">Observação</span><span class="info-value"><?= nl2br(html_escape($nr_pendente->observacao)) ?></span></div>
            <?php endif; ?>
            <div class="info-row"><span class="info-label">Registrado</span><span class="info-value"><?= !empty($nr_pendente->data_registro) ? date('d/m/Y H:i', strtotime($nr_pendente->data_registro)) : '--' ?></span></div>
            <div class="tec-linha-botoes">
                <button type="button" class="tec-btn tec-btn-laranja" data-chamar="abrirReagendar" data-args="[<?= (int) $nr_pendente->idOcorrencia ?>]"><i class='bx bx-calendar-plus' aria-hidden="true"></i> Reagendar</button>
                <button type="button" class="tec-btn tec-btn-cinza" data-ocorrencia="<?= (int) $nr_pendente->idOcorrencia ?>" data-chamar="reabrirAtividade" data-args='["@this"]'><i class='bx bx-revision' aria-hidden="true"></i> Reabrir para refazer</button>
            </div>
        </div>
    <?php endif; ?>

    <!-- O que fazer nesta visita -->
    <section class="tec-visita" aria-labelledby="tec-visita-t">
        <h3 id="tec-visita-t"><i class='bx bx-task' aria-hidden="true"></i> O que fazer nesta visita</h3>
        <?php if ($defeitoTxt !== ''): ?>
            <h4>Problema informado</h4>
            <p><?= nl2br(html_escape($defeitoTxt)) ?></p>
        <?php endif; ?>
        <?php if ($descricaoTxt !== ''): ?>
            <h4>Detalhes do serviço</h4>
            <p><?= nl2br(html_escape($descricaoTxt)) ?></p>
        <?php endif; ?>
        <?php if ($defeitoTxt === '' && $descricaoTxt === ''): ?>
            <p>Nenhuma descrição informada. Fale com o escritório se precisar.</p>
        <?php endif; ?>
        <?php if (!empty($servicos_previstos)): ?>
            <h4>Serviços previstos</h4>
            <ul class="tec-lista">
                <?php foreach ($servicos_previstos as $sv): ?>
                    <li><i class='bx bx-wrench' aria-hidden="true"></i> <span><?= html_escape($sv['nome']) ?></span> <b>x<?= html_escape($fmtQtd($sv['quantidade'])) ?></b></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!empty($produtos_previstos)): ?>
            <h4>Materiais previstos</h4>
            <ul class="tec-lista">
                <?php foreach ($produtos_previstos as $pv): ?>
                    <li><i class='bx bx-package' aria-hidden="true"></i> <span><?= html_escape($pv['nome']) ?></span> <b>x<?= html_escape($fmtQtd($pv['quantidade'])) ?></b></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php
    // Assistente passo a passo: so quando o tecnico pode atender e a OS esta em andamento
    $assistente_ativo = empty($somente_leitura)
        && !$nr_pendente
        && !$atendimento_encerrado
        && (($checkin_ativo) || in_array($os->status, ['Aberto', 'Em Andamento'], true))
        && (!empty($permissao_checkin) || $checkin_ativo);
    $assistente_cfg = [
        'osId'     => (int) $os->idOs,
        'chegou'   => (bool) $checkin_ativo,
        'antes'    => count($fotos_etapa['entrada'] ?? []),
        'depois'   => count($fotos_etapa['saida'] ?? []),
        'permFotos'  => !empty($permissao_fotos),
        'permCheckin' => !empty($permissao_checkin),
        'permCheckout' => !empty($permissao_checkout),
        'urlACaminho' => site_url('tecnico/a_caminho'),
        'naoRealizado' => !empty($permissao_nao_realizado) && empty($nr_pendente) && !$os_concluida,
    ];
    ?>
    <?php if ($atendimento_encerrado && empty($somente_leitura)): ?>
        <div class="tec-concluido" role="status"><i class='bx bx-check-circle' aria-hidden="true"></i> Atendimento concluído. Bom trabalho!</div>
    <?php endif; ?>
    <?php if ($assistente_ativo): ?>
        <section id="tec-assistente" class="tec-assist" aria-label="Passo a passo do atendimento" data-config="<?= html_escape(json_encode($assistente_cfg)) ?>">
            <noscript><p>Ative o JavaScript para usar o passo a passo.</p></noscript>
        </section>
    <?php endif; ?>

    <!-- Historico deste cliente -->
    <?php if (!empty($historico_cliente)): ?>
        <section class="tec-hist" aria-labelledby="tec-hist-t">
            <h3 id="tec-hist-t"><i class='bx bx-history' aria-hidden="true"></i> Histórico deste cliente</h3>
            <ul class="tec-lista">
                <?php foreach ($historico_cliente as $h): ?>
                    <li class="tec-hist-item">
                        <div><b>OS #<?= sprintf('%04d', $h->idOs) ?></b> &middot; <?= !empty($h->dataInicial) ? date('d/m/Y', strtotime($h->dataInicial)) : '--' ?> &middot; <?= html_escape($statusSimples($h->status)) ?></div>
                        <?php $dh = trim(strip_tags((string) $h->defeito)); ?>
                        <?php if ($dh !== ''): ?><div class="tec-hist-def"><?= html_escape(mb_strlen($dh) > 90 ? mb_substr($dh, 0, 90) . '…' : $dh) ?></div><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <!-- Dados da OS -->
    <div class="info-card">
        <h3><i class='bx bx-file'></i> Dados da OS</h3>
        <div class="info-row"><span class="info-label">Número</span><span class="info-value">#<?= sprintf('%04d', $os->idOs) ?></span></div>
        <div class="info-row"><span class="info-label">Data</span><span class="info-value"><?= date('d/m/Y', strtotime($os->dataInicial)) ?></span></div>
        <div class="info-row"><span class="info-label">Garantia</span><span class="info-value"><?= html_escape(formatar_garantia($os->garantia, 'N/A')) ?></span></div>
        <?php if ($checkin_ativo): ?>
            <div class="info-row"><span class="info-label">Chegada</span><span class="info-value"><?= date('d/m/Y \à\s H:i', strtotime($checkin_ativo->data_entrada)) ?></span></div>
        <?php endif; ?>
    </div>

    <?php $this->load->view('tecnico/_projeto_selo', ['vinculo_projeto' => $vinculo_projeto ?? null, 'pode_ver_projeto' => $pode_ver_projeto ?? false]); ?>

    <!-- Fotos -->
    <?php if (!empty($fotos)): ?>
        <div class="info-card">
            <h3><i class='bx bx-camera'></i> Fotos já enviadas</h3>
            <?php
            $etapasLabels = ['entrada' => ['bx-log-in-circle', 'antes'], 'durante' => ['bx-time', 'durante o serviço'], 'saida' => ['bx-log-out-circle', 'depois']];
            foreach ($etapasLabels as $et => $meta):
                if (empty($fotos_etapa[$et])) continue; ?>
                <div class="timeline-title"><i class='bx <?= $meta[0] ?>'></i> Fotos de <?= $meta[1] ?></div>
                <div class="fotos-grid">
                    <?php foreach ($fotos_etapa[$et] as $foto):
                        $imgUrl = !empty($foto->imagem_base64) ? base_url('index.php/checkin/verFotoDB/' . $foto->idFoto) : $foto->url; ?>
                        <div class="foto-item">
                            <img src="<?= $imgUrl ?>" alt="Foto <?= $meta[1] ?>" data-chamar="abrirFoto" data-args="<?= html_escape(json_encode([$imgUrl])) ?>">
                            <?php if (!empty($foto->descricao)): ?><div class="cap"><?= html_escape($foto->descricao) ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Assinaturas ja coletadas -->
    <?php
    // "cliente_aceite" (assinatura pelo link publico de aceite) e "solicitante"
    // (assinatura coletada no aparelho do tecnico) sao a MESMA assinatura: a
    // pessoa que solicita/recebe o servico. Exibimos uma unica, rotulada como
    // "Solicitante". Prefere a linha 'solicitante' quando as duas existirem.
    $assinaturas_exibicao = [];
    $solicitante_exibido = false;
    foreach ($assinaturas as $a) {
        if (in_array($a->tipo, ['solicitante', 'cliente_aceite'], true)) {
            if ($solicitante_exibido) {
                continue;
            }
            if ($a->tipo === 'cliente_aceite' && !empty($assinaturas_tipo['solicitante'])) {
                continue;
            }
            $solicitante_exibido = true;
        }
        $assinaturas_exibicao[] = $a;
    }
    ?>
    <?php if (!empty($assinaturas_exibicao)): ?>
        <div class="info-card">
            <h3><i class='bx bx-pen'></i> Assinaturas</h3>
            <div class="row" style="display:flex; flex-wrap:wrap; gap:14px;">
                <?php foreach ($assinaturas_exibicao as $assinatura): ?>
                    <div style="flex:1 1 140px; text-align:center;">
                        <h5 style="font-size:12px; color:#8a8fa3; margin:0 0 8px;"><?= in_array($assinatura->tipo, ['solicitante', 'cliente_aceite'], true) ? 'Solicitante' : ucfirst(str_replace('_', ' ', $assinatura->tipo)) ?></h5>
                        <div class="assinatura-box">
                            <?php if (!empty($assinatura->is_base64)): ?>
                                <img src="<?= $assinatura->url_visualizacao ?>" alt="Assinatura">
                            <?php elseif (!empty($assinatura->assinatura) && (file_exists($assinatura->assinatura) || file_exists(FCPATH . $assinatura->assinatura))): ?>
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
        <?php $tem_assinatura_solicitante = !empty($assinaturas_tipo['solicitante']) || !empty($assinaturas_tipo['cliente_aceite']); ?>
        <?php if (!empty($os->nao_programada) && !$tem_assinatura_solicitante): ?>
            <a href="<?= site_url('tecnico/assinatura_solicitante/' . $os->idOs) ?>" class="btn-tec success block" style="margin-bottom:8px;">
                <i class='bx bx-pen'></i> Assinatura do Solicitante
            </a>
        <?php elseif (!empty($os->nao_programada) && $tem_assinatura_solicitante): ?>
            <div class="btn-tec ghost block" style="margin-bottom:8px; cursor:default; opacity:.85;">
                <i class='bx bx-check-circle'></i> Assinatura do solicitante coletada
            </div>
        <?php endif; ?>

        <a href="<?= site_url('tecnico/imprimir/' . $os->idOs) ?>" target="_blank" class="btn-tec ghost block" style="margin-bottom:8px;">
            <i class='bx bx-printer'></i> Imprimir OS
        </a>

        <a href="<?= site_url('checkin/imprimir/' . $os->idOs) ?>" target="_blank" class="btn-tec neutral block">
            <i class='bx bx-time'></i> Relatório do atendimento
        </a>
    <?php endif; ?>

    <?php
    // "Não foi possível realizar": disponível quando há permissão, a OS ainda
    // não está em espera e não está concluída. Fica na barra de ação, junto do
    // "Iniciar Atendimento" (parte do fluxo de início).
    $os_concluida = in_array($os->status, ['Finalizado', 'Faturado', 'Cancelado'], true);
    $mostrar_nao_realizado = !empty($permissao_nao_realizado) && empty($nr_pendente) && !$os_concluida;

    // "Iniciar Atendimento" só aparece enquanto a OS ainda está "Aberto" ou
    // "Em Andamento" E ainda não tem nenhuma assinatura coletada. Assim o botão
    // some depois que o serviço é assinado/finalizado e não reaparece por engano.
    $status_permite_iniciar = in_array($os->status, ['Aberto', 'Em Andamento'], true);
    $tem_assinatura = !empty($assinaturas);
    $pode_iniciar = $permissao_checkin && $status_permite_iniciar && !$tem_assinatura;
    $permissao_fotos = isset($permissao_fotos) ? $permissao_fotos : false;

    // A barra de ação fixa só deve existir se ao menos um botão for realmente
    // exibido — evita renderizar uma barra branca vazia numa OS concluída.
    $tem_acao_barra = ($pode_iniciar && !$checkin_ativo)
        || ($mostrar_nao_realizado && !$checkin_ativo)
        || ($permissao_fotos && $checkin_ativo)
        || ($permissao_checkout && $checkin_ativo);
    ?>

    <?php if ($tem_acao_barra): ?>
        <?php if (!$assistente_ativo): ?><div style="height:76px;"></div><!-- espaco para a barra de acao fixa --><?php endif; ?>
    <?php endif; ?>
</div>

<!-- Barra de acao fixa: iniciar / nao realizado / finalizar -->
<?php if ($tem_acao_barra): ?>
<div class="action-bar<?= $assistente_ativo ? ' tec-bar-oculta' : '' ?>">
    <?php if ($pode_iniciar): ?>
        <button type="button" id="btn-iniciar-atendimento" class="btn-tec success lg <?= $checkin_ativo ? 'hidden' : '' ?>">
            <i class='bx bx-log-in'></i> Cheguei no local
        </button>
    <?php endif; ?>
    <?php if ($mostrar_nao_realizado): ?>
        <button type="button" id="btn-nao-realizado-bar" class="btn-tec lg btn-nr-outline <?= $checkin_ativo ? 'hidden' : '' ?>" data-chamar="abrirNaoRealizado">
            <i class='bx bx-x-circle'></i> Não deu para fazer
        </button>
    <?php endif; ?>
    <?php if ($permissao_fotos): ?>
        <button type="button" id="btn-foto-servico" class="btn-tec info lg <?= $checkin_ativo ? '' : 'hidden' ?>">
            <i class='bx bx-camera'></i> Foto do serviço
        </button>
    <?php endif; ?>
    <?php if ($permissao_checkout): ?>
        <button type="button" id="btn-finalizar-atendimento" class="btn-tec danger lg <?= $checkin_ativo ? '' : 'hidden' ?>">
            <i class='bx bx-log-out'></i> Concluir atendimento
        </button>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Modal de foto ampliada -->
<div id="fotoModal" data-acao="esconder" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.85); z-index:2000; align-items:center; justify-content:center; padding:20px;">
    <img id="fotoModalImg" src="" alt="Foto" style="max-width:100%; max-height:100%; border-radius:8px;">
</div>

<!-- Modal: registrar "não foi possível realizar" -->
<?php if ($mostrar_nao_realizado): ?>
<div id="naoRealizadoModal" class="nr-modal" style="display:none;">
    <div class="nr-modal-box">
        <h3><i class='bx bx-x-circle'></i> Não deu para fazer o serviço</h3>
        <p class="nr-modal-sub">Conte o motivo. A OS fica em espera para ser reagendada depois.</p>

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
            <button type="button" class="btn-tec ghost" data-chamar="fecharNaoRealizado">Cancelar</button>
            <button type="button" id="nr-confirmar" class="btn-tec danger" data-chamar="confirmarNaoRealizado"><i class='bx bx-check'></i> Confirmar</button>
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
            <button type="button" class="btn-tec ghost" data-chamar="fecharReagendar">Cancelar</button>
            <button type="button" class="btn-tec warning" data-chamar="confirmarReagendar"><i class='bx bx-check'></i> Reagendar</button>
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

<script nonce="<?= csp_nonce() ?>">
    // Aviso na tela (sem alert); usa o aviso do check-in
    function avisoTec(msg, tipo) {
        if (window.CheckinManager && CheckinManager.aviso) { CheckinManager.aviso(msg, tipo || 'info'); }
        else { var d = document.createElement('div'); d.setAttribute('role', 'alert'); d.textContent = msg; document.body.appendChild(d); }
    }
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
            avisoTec('Escolha um motivo ou escreva o que aconteceu.', 'warning');
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
                avisoTec(r.message || 'Registrado.', 'success');
                setTimeout(function () { window.location.href = NR_BASE + '/nao_realizadas'; }, 1200);
            } else {
                avisoTec((r && r.message) || 'Não consegui registrar. Tente de novo.', 'error');
                btn.disabled = false;
            }
        }).fail(function () {
            avisoTec('Sem conexão com a internet. Tente de novo.', 'error');
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
        if (!data) { avisoTec('Escolha uma data.', 'warning'); return; }
        $.ajax({
            url: NR_BASE + '/reagendar_atividade',
            type: 'POST',
            dataType: 'json',
            data: { ocorrencia_id: oc, nova_data: data }
        }).done(function (r) {
            avisoTec((r && r.message) || (r && r.success ? 'Reagendado.' : 'Não consegui reagendar.'), r && r.success ? 'success' : 'error');
            if (r && r.success) { setTimeout(function () { window.location.reload(); }, 1200); }
        }).fail(function () { avisoTec('Sem conexão com a internet. Tente de novo.', 'error'); });
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
            avisoTec((r && r.message) || (r && r.success ? 'Reaberta.' : 'Não consegui reabrir.'), r && r.success ? 'success' : 'error');
            if (r && r.success) { setTimeout(function () { window.location.reload(); }, 1200); } else { btn.disabled = false; }
        }).fail(function () { avisoTec('Sem conexão com a internet. Tente de novo.', 'error'); btn.disabled = false; });
    }
</script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/tecnico-a-caminho.js') ?>?v=<?= asset_versao('assets/js/tecnico-a-caminho.js', '1') ?>" data-url="<?= site_url('tecnico/a_caminho') ?>"></script>
<script nonce="<?= csp_nonce() ?>">
// ---- Assistente de atendimento (camada de interface sobre check-in / fotos / checklist / check-out) ----
(function () {
    'use strict';
    var root = document.getElementById('tec-assistente');
    if (!root) { return; }
    var cfg = {};
    try { cfg = JSON.parse(root.getAttribute('data-config') || '{}'); } catch (e) { cfg = {}; }

    var KEY = 'tecAss:' + cfg.osId;
    var loc = {};
    try { loc = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) { loc = {}; }
    if (!loc.pulou) { loc.pulou = {}; }
    function gravar() { try { localStorage.setItem(KEY, JSON.stringify(loc)); } catch (e) { /* sem espaco/privado */ } }

    var st = {
        chegou: !!cfg.chegou,
        nAntes: cfg.antes || 0,
        nDepois: cfg.depois || 0,
        checklist: !!loc.checklist,
        semChecklist: false,
        encerrado: false
    };

    var PASSOS = [
        { id: 'chegou',    nome: 'Cheguei',          icone: 'bx-log-in',      botao: 'Cheguei no local',      dica: 'Registre sua chegada e assine no quadro.' },
        { id: 'antes',     nome: 'Fotos de antes',   icone: 'bx-camera',      botao: 'Tirar foto do antes',   dica: 'Fotografe o local e os equipamentos antes de mexer em qualquer coisa.', pulavel: true },
        { id: 'checklist', nome: 'Checklist',        icone: 'bx-list-check',  botao: 'Preencher checklist',   dica: 'Responda as perguntas do serviço.', pulavel: true },
        { id: 'depois',    nome: 'Fotos de depois',  icone: 'bx-camera',      botao: 'Tirar foto do depois',  dica: 'Fotografe como ficou o serviço pronto.', pulavel: true },
        { id: 'concluir',  nome: 'Assinatura e conclusão', icone: 'bx-check-double', botao: 'Concluir atendimento', dica: 'Peça a assinatura do cliente e conclua.' }
    ];

    function feito(id) {
        switch (id) {
            case 'chegou': return st.chegou;
            case 'antes': return st.nAntes > 0 || !!loc.pulou.antes || !cfg.permFotos;
            case 'checklist': return st.checklist || st.semChecklist || !!loc.pulou.checklist;
            case 'depois': return st.nDepois > 0 || !!loc.pulou.depois || !cfg.permFotos;
            case 'concluir': return st.encerrado;
        }
        return false;
    }
    function textoFeito(id) {
        if (id === 'antes' && st.nAntes > 0) { return st.nAntes + (st.nAntes > 1 ? ' fotos enviadas' : ' foto enviada'); }
        if (id === 'depois' && st.nDepois > 0) { return st.nDepois + (st.nDepois > 1 ? ' fotos enviadas' : ' foto enviada'); }
        if (id === 'checklist' && st.semChecklist && !st.checklist) { return 'Sem checklist neste serviço'; }
        if (loc.pulou[id]) { return 'Pulado'; }
        if (id === 'antes' || id === 'depois') { return cfg.permFotos ? 'Feito' : 'Sem permissão de fotos'; }
        return 'Feito';
    }

    function atual() {
        for (var i = 0; i < PASSOS.length; i++) { if (!feito(PASSOS[i].id)) { return i; } }
        return PASSOS.length;
    }

    function render() {
        var cur = atual();
        var qtdFeitos = 0;
        PASSOS.forEach(function (p) { if (feito(p.id)) { qtdFeitos++; } });
        var pct = Math.round(qtdFeitos * 100 / PASSOS.length);
        var h = '<div class="tec-prog" role="progressbar" aria-valuemin="0" aria-valuemax="' + PASSOS.length + '" aria-valuenow="' + qtdFeitos + '" aria-label="Progresso do atendimento">'
            + '<div class="tec-prog-txt">' + (cur < PASSOS.length ? 'Passo ' + (cur + 1) + ' de ' + PASSOS.length : 'Tudo pronto') + '</div>'
            + '<div class="tec-prog-barra"><span></span></div></div>';

        h += '<ol class="tec-passos">';
        PASSOS.forEach(function (p, i) {
            var ok = feito(p.id);
            var cls = ok ? 'feito' : (i === cur ? 'atual' : 'espera');
            h += '<li class="tec-passo tec-passo-' + cls + '"' + (i === cur ? ' aria-current="step"' : '') + '>'
                + '<span class="tec-passo-num" aria-hidden="true">' + (ok ? '&#10003;' : (i + 1)) + '</span>'
                + '<span class="tec-passo-nome">' + p.nome + '</span>'
                + '<span class="tec-passo-sit">' + (ok ? textoFeito(p.id) : (i === cur ? 'Agora' : 'Falta')) + '</span></li>';
        });
        h += '</ol>';

        // "Estou a caminho": antes do passo "Cheguei", enquanto nao houver chegada
        if (!st.chegou && cfg.permCheckin) {
            var av = window.TecACaminho ? window.TecACaminho.lerAviso(cfg.osId) : null;
            h += '<div class="tec-acao-caminho">'
                + (av ? '<span class="tec-a-caminho-ok" role="status"><i class="bx bx-check-circle" aria-hidden="true"></i> Cliente avisado às ' + av.hora + '</span>'
                      : '<button type="button" class="tec-btn tec-btn-cinza" data-a-caminho="' + cfg.osId + '"><i class="bx bx-car" aria-hidden="true"></i> Estou a caminho</button>')
                + '</div>';
        }

        if (cur < PASSOS.length) {
            var p = PASSOS[cur];
            var bloqueado = (p.id === 'chegou' && !cfg.permCheckin) || (p.id === 'concluir' && !cfg.permCheckout);
            h += '<div class="tec-acao">'
                + '<p class="tec-acao-dica">' + p.dica + '</p>'
                + '<button type="button" class="tec-btn tec-btn-principal" data-passo="' + p.id + '"' + (bloqueado ? ' disabled' : '') + '><i class="bx ' + p.icone + '" aria-hidden="true"></i> ' + p.botao + '</button>';
            if (bloqueado) { h += '<p class="tec-acao-dica">Você não tem permissão para este passo. Fale com o escritório.</p>'; }
            if (p.pulavel) { h += '<button type="button" class="tec-btn tec-btn-link" data-pular="' + p.id + '">Pular este passo</button>'; }
            h += '</div>';
        }

        // Atalhos sempre visiveis
        h += '<div class="tec-extras">';
        if (st.chegou && cfg.permFotos) {
            h += '<button type="button" class="tec-btn tec-btn-cinza" data-passo="extra"><i class="bx bx-image-add" aria-hidden="true"></i> Enviar foto extra</button>';
        }
        if (cfg.naoRealizado) {
            h += '<button type="button" class="tec-btn tec-btn-vermelho" data-passo="nao"><i class="bx bx-x-circle" aria-hidden="true"></i> Não deu para fazer o serviço</button>';
        }
        h += '</div>';

        root.innerHTML = h;
        var barra = root.querySelector('.tec-prog-barra span');
        if (barra) { barra.style.width = pct + '%'; }
        document.body.classList.add('tec-assist-ativo');
    }

    function acao(id) {
        var CM = window.CheckinManager;
        if (!CM && id !== 'nao') { return; }
        switch (id) {
            case 'chegou': CM.abrirModalCheckin(); break;
            case 'antes': CM.abrirCamera('entrada'); break;
            case 'checklist': $('#modal-checklist').modal('show'); break;
            case 'depois': CM.abrirCamera('saida'); break;
            case 'concluir': CM.abrirModalCheckout(); break;
            case 'extra': $('#btn-foto-servico').trigger('click'); break;
            case 'nao': if (typeof window.abrirNaoRealizado === 'function') { window.abrirNaoRealizado(); } break;
        }
    }

    $(root).on('click', '[data-passo]', function () { acao(this.getAttribute('data-passo')); });
    $(root).on('click', '[data-pular]', function () {
        loc.pulou[this.getAttribute('data-pular')] = true;
        gravar();
        render();
    });

    // Eventos do fluxo existente (checkin.js / atendimento_ui.php)
    $(document).on('checkin:iniciado', function () { st.chegou = true; render(); });
    document.addEventListener('tec:aCaminho', function () { render(); });
    $(document).on('checkin:fotoEnviada', function (e, d) {
        d = d || {};
        if (d.etapa === 'entrada') { st.nAntes++; }
        if (d.etapa === 'saida') { st.nDepois++; }
        render();
    });
    $(document).on('tec:checklistSalvo', function () { st.checklist = true; loc.checklist = true; gravar(); render(); });
    $(document).on('checkin:finalizado', function () {
        st.encerrado = true;
        try { localStorage.removeItem(KEY); } catch (e) { /* ignora */ }
        render();
        setTimeout(function () { window.location.reload(); }, 2500);
    });

    $(function () {
        // Mantem o passo 1 em dia se o status do servidor (ou outro aparelho) disser que ja ha atendimento
        if (window.CheckinManager) {
            var orig = CheckinManager.atualizarUI;
            CheckinManager.atualizarUI = function () {
                orig.apply(this, arguments);
                if (this.estado.emAtendimento && !st.chegou) { st.chegou = true; render(); }
            };
        }
        // Descobre se existe checklist para este servico (se nao houver, o passo some sozinho)
        if (window.CheckinFormularios && $('#formularios-checklist').length) {
            CheckinFormularios.carregar('durante', '#formularios-checklist', cfg.osId, function (erro) {
                if (!erro && !$('#formularios-checklist .form-atendimento').length) { st.semChecklist = true; render(); }
            });
        }
    });

    render();
})();
</script>
</body>
</html>
