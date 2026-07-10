<?php
$tokenName = $this->security->get_csrf_token_name();
$tokenHash = $this->security->get_csrf_hash();
?>
<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="fas fa-robot"></i></span>
        <h5>Automação na aprovação da OS</h5>
    </div>
    <div class="widget-content">
        <div class="alert" style="background:#fff8e1; border:1px solid #ffe0a3; color:#8a6d3b; border-radius:8px;">
            <strong>Atenção:</strong> quando ligada, ao cliente <strong>aprovar</strong> a OS pelo link público, o sistema
            <strong>emite a NFS-e</strong> dos serviços e <strong>gera o boleto</strong> automaticamente. Emissão fiscal é
            uma ação real — teste primeiro em ambiente de <strong>homologação</strong> e habilite só para os clientes desejados
            (checkbox na ficha do cliente).
        </div>

        <form action="<?= site_url('automacao/salvar') ?>" method="post" class="automacao-form">
            <input type="hidden" name="<?= $tokenName ?>" value="<?= $tokenHash ?>">

            <div class="control-group">
                <label class="control-label" style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="automacao_aprovacao_ativa" value="1" <?= (string) $automacao_aprovacao_ativa === '1' ? 'checked' : '' ?>>
                    Ativar automação (NFS-e + boleto) na aprovação da OS
                </label>
                <span class="help-block">Master global. Mesmo ligada, só roda para clientes com a automação habilitada na ficha.</span>
            </div>

            <h5 style="color:#1e3a8a; margin-top:18px">Padrões da NFS-e</h5>
            <p style="color:#6b7191; font-size:12px; margin-top:0">
                Campos vazios usam o padrão do módulo fiscal / do serviço. Você pode usar tags da OS:
                <code>{os_numero}</code>, <code>{os_descricao}</code>, <code>{os_observacoes}</code>,
                <code>{os_defeito}</code>, <code>{os_laudo}</code>, <code>{os_aprovador}</code>.
            </p>

            <div class="control-group">
                <label class="control-label" for="automacao_desc_servico">Descrição do serviço</label>
                <div class="controls">
                    <textarea id="automacao_desc_servico" name="automacao_desc_servico" rows="2" class="span8"><?= html_escape($automacao_desc_servico) ?></textarea>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="automacao_info_complementar">Informações complementares</label>
                <div class="controls">
                    <textarea id="automacao_info_complementar" name="automacao_info_complementar" rows="2" class="span8"><?= html_escape($automacao_info_complementar) ?></textarea>
                </div>
            </div>

            <div class="row-fluid">
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for="automacao_ctribnac">Cód. tributação nacional</label>
                        <div class="controls"><input id="automacao_ctribnac" type="text" name="automacao_ctribnac" maxlength="6" value="<?= html_escape($automacao_ctribnac) ?>" placeholder="Padrão: 010701"></div>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for="automacao_ctribmun">Cód. tributação municipal</label>
                        <div class="controls"><input id="automacao_ctribmun" type="text" name="automacao_ctribmun" maxlength="10" value="<?= html_escape($automacao_ctribmun) ?>" placeholder="Padrão: 100"></div>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for="automacao_aliquota_iss">Alíquota ISS (%)</label>
                        <div class="controls"><input id="automacao_aliquota_iss" type="text" name="automacao_aliquota_iss" value="<?= html_escape($automacao_aliquota_iss) ?>" placeholder="ex.: 5"></div>
                    </div>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="automacao_tp_ret_issqn">Retenção de ISS</label>
                <div class="controls">
                    <select id="automacao_tp_ret_issqn" name="automacao_tp_ret_issqn">
                        <option value="" <?= $automacao_tp_ret_issqn === '' ? 'selected' : '' ?>>— padrão do config fiscal —</option>
                        <option value="1" <?= (string) $automacao_tp_ret_issqn === '1' ? 'selected' : '' ?>>Não retido</option>
                        <option value="2" <?= (string) $automacao_tp_ret_issqn === '2' ? 'selected' : '' ?>>Retido</option>
                    </select>
                </div>
            </div>

            <h5 style="color:#1e3a8a; margin-top:18px">Faturamento agendado</h5>
            <p style="color:#6b7191; font-size:12px; margin-top:0">
                Para clientes marcados com <strong>“Faturamento agendado”</strong> na ficha, a aprovação no meio do mês
                <strong>não</strong> emite na hora: a NFS-e e o boleto ficam em espera e são emitidos no dia abaixo.
            </p>
            <div class="control-group">
                <label class="control-label" for="automacao_faturamento_dia">Dia do faturamento</label>
                <div class="controls">
                    <input id="automacao_faturamento_dia" type="number" min="1" max="28" name="automacao_faturamento_dia"
                        value="<?= html_escape($automacao_faturamento_dia) ?>" style="width:80px">
                    <span class="help-inline">Dia do mês (1 a 28) em que a fila é liberada. Padrão: 1.</span>
                </div>
            </div>

            <div style="margin-top:12px">
                <button type="submit" class="button btn btn-success">
                    <span class="button__icon"><i class="bx bx-save"></i></span>
                    <span class="button__text2">Salvar automação</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="fas fa-clock"></i></span>
        <h5>Faturamentos em espera</h5>
    </div>
    <div class="widget-content">
        <?php $agendados = isset($agendados) ? $agendados : []; ?>
        <div style="margin-bottom:12px">
            <a href="<?= site_url('automacao/processarAgendados') ?>" class="btn btn-primary btn-mini"
                onclick="return confirm('Emitir agora todos os itens já vencidos (data igual ou anterior a hoje)?');">
                <i class="bx bx-play"></i> Processar vencidos agora
            </a>
            <span class="help-inline" style="margin-left:8px">A fila também é processada sozinha (a cada ~2 min) quando chega o dia.</span>
        </div>

        <?php if (empty($agendados)) { ?>
            <p style="color:#6b7191">Nenhum faturamento em espera no momento.</p>
        <?php } else { ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>OS</th>
                        <th>Cliente</th>
                        <th>Aprovada em</th>
                        <th>Emite em</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agendados as $a) { ?>
                        <tr>
                            <td><a href="<?= site_url('os/visualizar/' . (int) $a->os_id) ?>">#<?= (int) $a->os_id ?></a></td>
                            <td><?= html_escape($a->nomeCliente ?? '—') ?></td>
                            <td><?= $a->data_aprovacao ? date('d/m/Y H:i', strtotime($a->data_aprovacao)) : '—' ?></td>
                            <td><?= $a->data_agendada ? date('d/m/Y', strtotime($a->data_agendada)) : '—' ?></td>
                            <td>
                                <?php if ($a->status === 'erro') { ?>
                                    <span class="label label-important" title="<?= html_escape($a->motivo ?? '') ?>">Erro (<?= (int) $a->tentativas ?>x)</span>
                                <?php } else { ?>
                                    <span class="label label-warning">Aguardando</span>
                                <?php } ?>
                            </td>
                            <td>
                                <a href="<?= site_url('automacao/cancelarAgendado/' . (int) $a->id) ?>" class="btn btn-danger btn-mini"
                                    onclick="return confirm('Cancelar este faturamento agendado? A nota não será emitida.');">
                                    <i class="bx bx-x"></i> Cancelar
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>
</div>
