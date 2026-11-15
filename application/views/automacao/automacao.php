<?php
/**
 * Automação de faturamento — configuração e fila (Automacao::index).
 *
 * Variaveis: $abaAtiva, $hoje, $agendados, $resumo, $proxima_data,
 * $automacao_ativa, $resultado_fila e as chaves automacao_*.
 *
 * Escrita so por POST: as acoes da fila sao formularios com o token CSRF.
 * A tabela usa ui-tabela-empilha, que vira cartao no celular (data-rotulo).
 */
require_once APPPATH . 'helpers/automacao_regras_helper.php';

$e = function ($v) {
    return html_escape((string) ($v ?? ''));
};

$tokenName = $this->security->get_csrf_token_name();
$tokenHash = $this->security->get_csrf_hash();

$abaAtiva = $abaAtiva ?? 'configuracao';
$hoje = $hoje ?? date('Y-m-d');
$agendados = $agendados ?? [];
$resumo = $resumo ?? ['total' => 0, 'aguardando' => 0, 'vencidos' => 0, 'futuros' => 0, 'erro' => 0];
$proxima_data = $proxima_data ?? '';
$automacao_ativa = !empty($automacao_ativa);
$resultado_fila = $resultado_fila ?? null;

$fmtData = function ($valor, $comHora = false) {
    if (empty($valor)) {
        return '—';
    }
    $ts = strtotime((string) $valor);

    return $ts ? date($comHora ? 'd/m/Y H:i' : 'd/m/Y', $ts) : '—';
};

$rotuloSituacao = [
    'erro' => ['Erro', 'perigo', 'bx-error-circle'],
    'vencido' => ['Vencido', 'alerta', 'bx-time-five'],
    'futuro' => ['Aguardando', 'info', 'bx-calendar'],
    'resolvido' => ['Resolvido', 'neutro', 'bx-check-circle'],
];
?>
<link rel="stylesheet" href="<?= base_url('assets/css/automacao.css') ?>?v=<?= asset_versao('assets/css/automacao.css', '1') ?>" />

<div class="ui-cabecalho">
    <div>
        <h1 class="ui-titulo"><i class="bx bx-bot"></i> Automação de faturamento</h1>
        <p class="ui-subtitulo">
            Emissão de NFS-e e geração de boleto quando o cliente aceita a OS.
            <?php if ($automacao_ativa): ?>
                <span class="ui-selo sucesso aut-estado"><i class="bx bx-check-circle"></i> Ativa</span>
            <?php else: ?>
                <span class="ui-selo neutro aut-estado"><i class="bx bx-pause-circle"></i> Desligada</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="ui-acoes">
        <a href="<?= site_url('nfe/gerenciar') ?>" class="btn btn-outline-secondary">
            <i class="bx bx-receipt"></i> Notas fiscais
        </a>
    </div>
</div>

<div class="ui-kpis">
    <div class="ui-kpi">
        <div class="ui-kpi-rotulo">Aguardando</div>
        <div class="ui-kpi-valor"><?= (int) ($resumo['futuros'] ?? 0) ?></div>
        <div class="ui-kpi-extra"><?= (int) ($resumo['aguardando'] ?? 0) ?> em fila no total</div>
    </div>
    <div class="ui-kpi <?= ((int) ($resumo['vencidos'] ?? 0)) > 0 ? 'alerta' : '' ?>">
        <div class="ui-kpi-rotulo">Vencidos</div>
        <div class="ui-kpi-valor"><?= (int) ($resumo['vencidos'] ?? 0) ?></div>
        <div class="ui-kpi-extra">Serão emitidos no próximo processamento</div>
    </div>
    <div class="ui-kpi <?= ((int) ($resumo['erro'] ?? 0)) > 0 ? 'perigo' : '' ?>">
        <div class="ui-kpi-rotulo">Com erro</div>
        <div class="ui-kpi-valor"><?= (int) ($resumo['erro'] ?? 0) ?></div>
        <div class="ui-kpi-extra">Precisam de decisão manual</div>
    </div>
    <div class="ui-kpi info">
        <div class="ui-kpi-rotulo">Próximo lote</div>
        <div class="ui-kpi-valor"><?= $e($fmtData($proxima_data)) ?></div>
        <div class="ui-kpi-extra">Dia <?= (int) $automacao_faturamento_dia ?> de cada mês</div>
    </div>
</div>

<nav class="ui-abas" aria-label="Seções da automação">
    <a class="ui-aba" href="<?= site_url('automacao') ?>" aria-selected="<?= $abaAtiva === 'configuracao' ? 'true' : 'false' ?>">
        <i class="bx bx-slider-alt"></i> Configuração
    </a>
    <a class="ui-aba" href="<?= site_url('automacao?aba=fila') ?>" aria-selected="<?= $abaAtiva === 'fila' ? 'true' : 'false' ?>">
        <i class="bx bx-list-check"></i> Fila de faturamento
        <span class="ui-aba-num"><?= (int) ($resumo['aguardando'] ?? 0) + (int) ($resumo['erro'] ?? 0) ?></span>
    </a>
</nav>

<?php if ($abaAtiva === 'configuracao'): ?>
    <div class="ui-dica alerta">
        <i class="bx bx-error"></i>
        <div>
            <strong>Emissão fiscal é ação real.</strong>
            Quando ligada, ao cliente aceitar a resolução do serviço pelo link público, o sistema
            emite a NFS-e e gera o boleto. Teste primeiro em homologação e habilite só para os
            clientes desejados (checkbox na ficha do cliente).
        </div>
    </div>

    <form action="<?= site_url('automacao/salvar') ?>" method="post">
        <input type="hidden" name="<?= $e($tokenName) ?>" value="<?= $e($tokenHash) ?>">

        <div class="aut-liga">
            <input class="form-check-input" type="checkbox" id="automacao_aprovacao_ativa" name="automacao_aprovacao_ativa" value="1"
                <?= $automacao_ativa ? 'checked' : '' ?>>
            <label class="aut-liga-txt" for="automacao_aprovacao_ativa">
                <strong>Ativar automação (NFS-e + boleto) no aceite da OS</strong>
                <span>Master global. Mesmo ligada, só roda para clientes com a automação habilitada na ficha.</span>
            </label>
        </div>

        <div class="ui-secao">
            <h4><i class="bx bx-file"></i> Padrões da NFS-e</h4>
            <p class="ui-subtitulo">Campos vazios usam o padrão do módulo fiscal ou do serviço.</p>
            <div class="aut-tags" aria-label="Tags disponíveis">
                <code>{os_numero}</code>
                <code>{os_descricao}</code>
                <code>{os_observacoes}</code>
                <code>{os_defeito}</code>
                <code>{os_laudo}</code>
                <code>{os_aprovador}</code>
            </div>
        </div>

        <div class="ui-secao">
            <div class="ui-grade">
                <div class="mb-3">
                    <label class="form-label" for="automacao_desc_servico">Descrição do serviço</label>
                    <div class="">
                        <textarea class="form-control" id="automacao_desc_servico" name="automacao_desc_servico" rows="3"><?= $e($automacao_desc_servico) ?></textarea>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="automacao_info_complementar">Informações complementares</label>
                    <div class="">
                        <textarea class="form-control" id="automacao_info_complementar" name="automacao_info_complementar" rows="3"><?= $e($automacao_info_complementar) ?></textarea>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="automacao_ctribnac">Cód. tributação nacional</label>
                    <div class="">
                        <input class="form-control" id="automacao_ctribnac" type="text" name="automacao_ctribnac" maxlength="6"
                            value="<?= $e($automacao_ctribnac) ?>" placeholder="Padrão: 010701">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="automacao_ctribmun">Cód. tributação municipal</label>
                    <div class="">
                        <input class="form-control" id="automacao_ctribmun" type="text" name="automacao_ctribmun" maxlength="10"
                            value="<?= $e($automacao_ctribmun) ?>" placeholder="Padrão: 100">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="automacao_aliquota_iss">Alíquota ISS (%)</label>
                    <div class="">
                        <input class="form-control" id="automacao_aliquota_iss" type="text" name="automacao_aliquota_iss"
                            value="<?= $e($automacao_aliquota_iss) ?>" placeholder="ex.: 5">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="automacao_tp_ret_issqn">Retenção de ISS</label>
                    <div class="">
                        <select class="form-select" id="automacao_tp_ret_issqn" name="automacao_tp_ret_issqn">
                            <option value="" <?= $automacao_tp_ret_issqn === '' ? 'selected' : '' ?>>— padrão do config fiscal —</option>
                            <option value="1" <?= (string) $automacao_tp_ret_issqn === '1' ? 'selected' : '' ?>>Não retido</option>
                            <option value="2" <?= (string) $automacao_tp_ret_issqn === '2' ? 'selected' : '' ?>>Retido</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="ui-secao">
            <h4><i class="bx bx-calendar-event"></i> Faturamento agendado</h4>
            <p class="ui-subtitulo">
                Para clientes marcados com <strong>“Faturamento agendado”</strong> na ficha, o aceite no meio do mês
                não emite na hora: a NFS-e e o boleto ficam em espera e entram no lote do dia abaixo.
            </p>
            <div class="ui-grade">
                <div class="mb-3">
                    <label class="form-label" for="automacao_faturamento_dia">Dia do lote</label>
                    <div class="">
                        <input class="form-control" id="automacao_faturamento_dia" type="number" min="1" max="28"
                            name="automacao_faturamento_dia" value="<?= $e($automacao_faturamento_dia) ?>">
                    </div>
                    <span class="form-text">De 1 a 28, para existir em todos os meses. Próximo lote: <?= $e($fmtData($proxima_data)) ?>.</span>
                </div>
            </div>
        </div>

        <div class="ui-rodape-form">
            <button type="submit" class="btn btn-success">
                <i class="bx bx-save"></i> Salvar automação
            </button>
        </div>
    </form>
<?php else: ?>
    <?php if (is_array($resultado_fila) && (int) ($resultado_fila['processados'] ?? 0) > 0): ?>
        <div class="aut-resultado" role="status">
            <span><b><?= (int) $resultado_fila['processados'] ?></b> processado(s)</span>
            <span class="ui-positivo"><b><?= (int) ($resultado_fila['sucesso'] ?? 0) ?></b> emitido(s)</span>
            <span class="ui-negativo"><b><?= (int) ($resultado_fila['falhas'] ?? 0) ?></b> falha(s)</span>
            <span><b><?= (int) ($resultado_fila['cancelados'] ?? 0) ?></b> cancelado(s)</span>
        </div>
    <?php endif; ?>

    <div class="ui-dica">
        <i class="bx bx-info-circle"></i>
        <div>
            A fila é processada sozinha a cada ~2 minutos quando chega o dia. Use o botão para
            emitir <strong>agora</strong> os itens que já venceram.
            <?php if ((int) ($resumo['erro'] ?? 0) > 0): ?>
                Há <strong><?= (int) $resumo['erro'] ?></strong> item(ns) em erro: abra a linha, veja o motivo e devolva à fila.
            <?php endif; ?>
        </div>
    </div>

    <div class="ui-acoes ui-mb">
        <form action="<?= site_url('automacao/processarAgendados') ?>" method="post"
            data-confirmar="Emitir agora todos os itens já vencidos? A emissão fiscal é definitiva.">
            <input type="hidden" name="<?= $e($tokenName) ?>" value="<?= $e($tokenHash) ?>">
            <button type="submit" class="btn btn-primary" <?= (int) ($resumo['vencidos'] ?? 0) === 0 ? 'disabled' : '' ?>>
                <i class="bx bx-play"></i> Processar vencidos
                <?php if ((int) ($resumo['vencidos'] ?? 0) > 0): ?>
                    (<?= (int) $resumo['vencidos'] ?>)
                <?php endif; ?>
            </button>
        </form>
    </div>

    <?php if (empty($agendados)): ?>
        <div class="ui-vazio">
            <i class="bx bx-check-circle"></i>
            <strong>Nenhum faturamento em espera</strong>
            Quando houver OS aceitas aguardando a data de emissão, elas aparecem aqui.
        </div>
    <?php else: ?>
        <div class="ui-tabela-rolagem">
            <table class="table table-striped table-bordered ui-tabela-empilha">
                <thead>
                    <tr>
                        <th>OS</th>
                        <th>Cliente</th>
                        <th>Aprovada em</th>
                        <th>Emite em</th>
                        <th>Situação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agendados as $a): ?>
                        <?php
                        $situacao = AutomacaoRegras::situacaoItem($a->status ?? '', $a->data_agendada ?? '', $hoje);
                        [$rot, $tom, $ico] = $rotuloSituacao[$situacao] ?? $rotuloSituacao['futuro'];
                        ?>
                        <tr class="<?= $situacao === 'erro' ? 'ui-linha-perigo' : ($situacao === 'vencido' ? 'ui-linha-alerta' : '') ?>">
                            <td data-rotulo="OS">
                                <a href="<?= site_url('os/visualizar/' . (int) $a->os_id) ?>">#<?= (int) $a->os_id ?></a>
                            </td>
                            <td data-rotulo="Cliente"><?= $e($a->nomeCliente ?? '—') ?></td>
                            <td data-rotulo="Aprovada em"><?= $e($fmtData($a->data_aprovacao, true)) ?></td>
                            <td data-rotulo="Emite em"><?= $e($fmtData($a->data_agendada)) ?></td>
                            <td data-rotulo="Situação">
                                <span class="ui-selo <?= $e($tom) ?>" title="<?= $e($a->motivo ?? '') ?>">
                                    <i class="bx <?= $e($ico) ?>"></i> <?= $e($rot) ?>
                                    <?php if ($situacao === 'erro'): ?>
                                        (<?= (int) $a->tentativas ?>x)
                                    <?php endif; ?>
                                </span>
                                <?php if (!empty($a->motivo)): ?>
                                    <br><small class="ui-negativo"><?= $e(mb_substr((string) $a->motivo, 0, 160)) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="ui-tabela-acoes">
                                <div class="aut-fila-acoes">
                                    <?php if (AutomacaoRegras::podeRetentar($a->status ?? '')): ?>
                                        <form action="<?= site_url('automacao/retentarAgendado') ?>" method="post"
                                            data-confirmar="Devolver este item para a fila? Ele será tentado de novo automaticamente.">
                                            <input type="hidden" name="<?= $e($tokenName) ?>" value="<?= $e($tokenHash) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $a->id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bx bx-refresh"></i> Tentar de novo</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (AutomacaoRegras::podeCancelar($a->status ?? '')): ?>
                                        <form action="<?= site_url('automacao/cancelarAgendado') ?>" method="post"
                                            data-confirmar="Cancelar este faturamento agendado? A nota não será emitida.">
                                            <input type="hidden" name="<?= $e($tokenName) ?>" value="<?= $e($tokenHash) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $a->id ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="bx bx-x"></i> Cancelar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endif; ?>
