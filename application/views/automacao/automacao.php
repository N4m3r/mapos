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

        <form action="<?= site_url('automacao/salvar') ?>" method="post">
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
                <code>{os_defeito}</code>, <code>{os_laudo}</code>.
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
                        <div class="controls"><input id="automacao_ctribnac" type="text" name="automacao_ctribnac" value="<?= html_escape($automacao_ctribnac) ?>"></div>
                    </div>
                </div>
                <div class="span4">
                    <div class="control-group">
                        <label class="control-label" for="automacao_ctribmun">Cód. tributação municipal</label>
                        <div class="controls"><input id="automacao_ctribmun" type="text" name="automacao_ctribmun" value="<?= html_escape($automacao_ctribmun) ?>"></div>
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

            <div style="margin-top:12px">
                <button type="submit" class="button btn btn-success">
                    <span class="button__icon"><i class="bx bx-save"></i></span>
                    <span class="button__text2">Salvar automação</span>
                </button>
            </div>
        </form>
    </div>
</div>
