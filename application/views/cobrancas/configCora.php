<?php
$cfg = isset($configCora) ? $configCora : null;
$ativo = $cfg && $cfg->ativo;
$producao = $cfg && $cfg->producao;
$clientId = $cfg->client_id ?? '';
$expiracao = $cfg->boleto_expiration ?? 'P3D';
$certPath = $cfg->certificado_path ?? '';
$chavePath = $cfg->chave_path ?? '';
$webhookId = $cfg->webhook_endpoint_id ?? '';
$webhookUrl = site_url('webhook/cora');
$pronto = $ativo && $clientId && $certPath && $chavePath;
?>
<style>
    .form-horizontal .control-label { padding-top: 9px; width: 220px; }
    .form-horizontal .controls { margin-left: 240px; }
    .hint { display: block; color: #999; font-size: 11px; margin-top: 2px; }
</style>

<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="bx bx-dollar-circle"></i></span>
                <h5>Configuração da Cobrança - Cora (Boleto + PIX)</h5>
            </div>
            <div class="widget-content">
                <?php if (! empty($custom_error)) { echo $custom_error; } ?>

                <?php if ($this->session->flashdata('success')) { ?>
                    <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
                <?php } ?>
                <?php if ($this->session->flashdata('error')) { ?>
                    <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
                <?php } ?>

                <?php if (! $cfg) { ?>
                    <div class="alert alert-danger">
                        <strong>Atenção:</strong> a tabela <code>configuracoes_cora</code> ainda não existe.
                        Rode a migração (<code>php index.php tools migrate</code>) antes de configurar.
                    </div>
                <?php } else { ?>
                    <div class="alert <?php echo $pronto ? 'alert-success' : 'alert-info'; ?>">
                        <?php if ($pronto) { ?>
                            <i class="bx bx-check-circle"></i> Cora <strong>ativa</strong> em ambiente
                            <strong><?php echo $producao ? 'Produção' : 'Stage (homologação)'; ?></strong>.
                            Pronta para gerar boletos a partir das notas fiscais.
                        <?php } else { ?>
                            Preencha o <strong>client_id</strong>, envie o <strong>certificado (.pem)</strong> e a
                            <strong>chave privada (.key)</strong>, e marque <strong>Ativar</strong> para começar a usar.
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class="alert alert-info" style="font-size:12px">
                    As credenciais são geradas no app/Cora Web em <strong>Integração &gt; API (Integração Direta)</strong>.
                    Você recebe um <em>client_id</em> e baixa o <em>certificate.pem</em> + <em>private-key.key</em>.
                    Credenciais de <strong>Stage</strong> e <strong>Produção</strong> são diferentes.
                </div>

                <form action="<?= site_url('cobrancas/configCora') ?>" method="post" enctype="multipart/form-data" class="form-horizontal">

                    <div class="control-group">
                        <label class="control-label" for="ativo">Ativar cobrança Cora</label>
                        <div class="controls">
                            <input type="checkbox" id="ativo" name="ativo" value="1" <?= $ativo ? 'checked' : '' ?> />
                            <span class="hint">Quando ativa, o botão "Gerar Boleto/PIX" aparece nas notas fiscais da OS.</span>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="producao">Ambiente</label>
                        <div class="controls">
                            <select name="producao" id="producao">
                                <option value="0" <?= ! $producao ? 'selected' : '' ?>>Stage (homologação, sem cobrança real)</option>
                                <option value="1" <?= $producao ? 'selected' : '' ?>>Produção</option>
                            </select>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="client_id">Client ID</label>
                        <div class="controls">
                            <input type="text" id="client_id" name="client_id" value="<?= html_escape($clientId) ?>" style="width:360px" autocomplete="off" />
                            <span class="hint">Identificador da integração direta (ex: int-xxxxx).</span>
                        </div>
                    </div>

                    <h5 style="margin-top:10px">Certificado mTLS</h5>

                    <div class="control-group">
                        <label class="control-label" for="certificado">Certificado (.pem)</label>
                        <div class="controls">
                            <input type="file" id="certificado" name="certificado" accept=".pem,.crt,.cer" />
                            <?php if (! empty($certPath) && is_file($certPath)) { ?>
                                <span class="hint" style="color:#4d9c79"><i class="bx bx-check-circle"></i> Arquivo em disco: <?= html_escape(basename($certPath)) ?> (<?= number_format(filesize($certPath)) ?> bytes). Envie de novo só para substituir.</span>
                            <?php } elseif (! empty($certPath)) { ?>
                                <span class="hint" style="color:#CD0000"><i class="bx bx-error-circle"></i> Registrado no banco, mas o arquivo não está em disco. Reenvie o certificado.</span>
                            <?php } else { ?>
                                <span class="hint">Nenhum certificado enviado ainda.</span>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="chave">Chave privada (.key)</label>
                        <div class="controls">
                            <input type="file" id="chave" name="chave" accept=".key,.pem" />
                            <?php if (! empty($chavePath) && is_file($chavePath)) { ?>
                                <span class="hint" style="color:#4d9c79"><i class="bx bx-check-circle"></i> Arquivo em disco: <?= html_escape(basename($chavePath)) ?> (<?= number_format(filesize($chavePath)) ?> bytes). Envie de novo só para substituir.</span>
                            <?php } elseif (! empty($chavePath)) { ?>
                                <span class="hint" style="color:#CD0000"><i class="bx bx-error-circle"></i> Registrada no banco, mas o arquivo não está em disco. Reenvie a chave.</span>
                            <?php } else { ?>
                                <span class="hint">Nenhuma chave enviada ainda.</span>
                            <?php } ?>
                        </div>
                    </div>

                    <h5 style="margin-top:10px">Boleto</h5>

                    <div class="control-group">
                        <label class="control-label" for="boleto_expiration">Vencimento</label>
                        <div class="controls">
                            <input type="text" id="boleto_expiration" name="boleto_expiration" value="<?= html_escape($expiracao) ?>" style="width:100px" />
                            <span class="hint">Formato ISO 8601 a partir de hoje. Ex: <code>P3D</code> = 3 dias, <code>P7D</code> = 7 dias.</span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success"><i class="bx bx-save"></i> Salvar Configurações</button>
                        <button type="button" id="btn-testar-cora" class="btn btn-info"><i class="bx bx-plug"></i> Testar Conexão</button>
                        <button type="button" id="btn-diagnostico-cora" class="btn"><i class="bx bx-search-alt"></i> Diagnóstico</button>
                        <span id="resultado-teste-cora" style="margin-left:10px"></span>
                    </div>
                </form>
                <pre id="diagnostico-cora" style="display:none;margin-top:10px;max-height:340px;overflow:auto;background:#1e1e1e;color:#dcdcdc;padding:10px;border-radius:4px;font-size:12px"></pre>

                <hr>
                <h5>Baixa automática (Webhook)</h5>
                <div class="alert alert-info" style="font-size:12px">
                    Registre o webhook para que a Cora avise este sistema assim que o boleto/PIX for pago —
                    a cobrança é marcada como <strong>Paga</strong> e o lançamento financeiro recebe baixa automaticamente.
                    O servidor precisa estar acessível pela internet (HTTPS) nesta URL.
                </div>
                <div class="control-group">
                    <label class="control-label" for="webhook_url">URL do webhook</label>
                    <div class="controls">
                        <input type="text" id="webhook_url" value="<?= html_escape($webhookUrl) ?>" style="width:360px" readonly />
                        <a href="#" class="btn btn-mini" id="btn-copiar-webhook"><i class="bx bx-copy"></i> Copiar</a>
                        <span class="hint">
                            <?php if (! empty($webhookId)) { ?>
                                <span style="color:#4d9c79"><i class="bx bx-check-circle"></i> Webhook registrado (id: <?= html_escape($webhookId) ?>).</span>
                            <?php } else { ?>
                                Ainda não registrado na Cora.
                            <?php } ?>
                        </span>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label">&nbsp;</label>
                    <div class="controls">
                        <button type="button" id="btn-registrar-webhook" class="btn btn-warning"><i class="bx bx-link"></i> Registrar webhook na Cora</button>
                        <span id="resultado-webhook-cora" style="margin-left:10px"></span>
                        <span class="hint">Salve as credenciais antes de registrar. Registrar de novo apenas atualiza o cadastro.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).on('click', '#btn-testar-cora', function () {
    var $btn = $(this);
    var $res = $('#resultado-teste-cora');
    $btn.prop('disabled', true);
    $res.html("<span style='color:#888'><i class='bx bx-loader bx-spin'></i> Testando...</span>");
    $.ajax({
        type: 'POST',
        url: '<?= site_url('cobrancas/testarCora') ?>',
        dataType: 'json',
        data: {},
        success: function (data) {
            $res.html("<span style='color:#4d9c79'><i class='bx bx-check-circle'></i> " + data.message + "</span>");
        },
        error: function (xhr) {
            $res.html("<span style='color:#CD0000'><i class='bx bx-x-circle'></i> " + coraErroMsg(xhr, 'testar conexão') + "</span>");
        },
        complete: function () { $btn.prop('disabled', false); }
    });
});

// Extrai a mensagem de erro real do XHR (JSON, ou HTML de 403/500).
function coraErroMsg(xhr, acao) {
    if (xhr.responseJSON && xhr.responseJSON.message) {
        return xhr.responseJSON.message;
    }
    if (xhr.status === 403) {
        return 'Sessão/CSRF expirada (HTTP 403). Recarregue a página (F5) e tente de novo.';
    }
    var txt = (xhr.responseText || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    if (txt.length > 200) { txt = txt.substring(0, 200) + '…'; }
    return 'Erro ao ' + acao + ' (HTTP ' + xhr.status + ').' + (txt ? ' ' + txt : '');
}

$(document).on('click', '#btn-diagnostico-cora', function () {
    var $btn = $(this);
    var $out = $('#diagnostico-cora');
    $btn.prop('disabled', true);
    $out.show().text('Coletando diagnóstico...');
    $.ajax({
        type: 'POST',
        url: '<?= site_url('cobrancas/diagnosticarCora') ?>',
        dataType: 'json',
        data: {},
        success: function (data) {
            $out.text(JSON.stringify(data, null, 2));
        },
        error: function (xhr) {
            $out.text(coraErroMsg(xhr, 'gerar diagnóstico'));
        },
        complete: function () { $btn.prop('disabled', false); }
    });
});

$(document).on('click', '#btn-copiar-webhook', function (e) {
    e.preventDefault();
    var url = $('#webhook_url').val();
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url);
    } else {
        var el = document.getElementById('webhook_url');
        el.select(); document.execCommand('copy');
    }
    $(this).html("<i class='bx bx-check'></i> Copiado");
});

$(document).on('click', '#btn-registrar-webhook', function () {
    var $btn = $(this);
    var $res = $('#resultado-webhook-cora');
    $btn.prop('disabled', true);
    $res.html("<span style='color:#888'><i class='bx bx-loader bx-spin'></i> Registrando...</span>");
    $.ajax({
        type: 'POST',
        url: '<?= site_url('cobrancas/registrarWebhookCora') ?>',
        dataType: 'json',
        data: {},
        success: function (data) {
            $res.html("<span style='color:#4d9c79'><i class='bx bx-check-circle'></i> " + data.message + "</span>");
        },
        error: function (xhr) {
            $res.html("<span style='color:#CD0000'><i class='bx bx-x-circle'></i> " + coraErroMsg(xhr, 'registrar webhook') + "</span>");
        },
        complete: function () { $btn.prop('disabled', false); }
    });
});
</script>
