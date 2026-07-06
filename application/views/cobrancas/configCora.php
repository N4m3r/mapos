<?php
$cfg = isset($configCora) ? $configCora : null;
$ativo = $cfg && $cfg->ativo;
$producao = $cfg && $cfg->producao;
$clientId = $cfg->client_id ?? '';
$expiracao = $cfg->boleto_expiration ?? 'P3D';
$certPath = $cfg->certificado_path ?? '';
$chavePath = $cfg->chave_path ?? '';
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
                            <?php if (! empty($certPath)) { ?>
                                <span class="hint">Enviado: <?= html_escape(basename($certPath)) ?> (envie novamente apenas para substituir)</span>
                            <?php } else { ?>
                                <span class="hint">Nenhum certificado enviado ainda.</span>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="chave">Chave privada (.key)</label>
                        <div class="controls">
                            <input type="file" id="chave" name="chave" accept=".key,.pem" />
                            <?php if (! empty($chavePath)) { ?>
                                <span class="hint">Enviada: <?= html_escape(basename($chavePath)) ?> (envie novamente apenas para substituir)</span>
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
                        <span id="resultado-teste-cora" style="margin-left:10px"></span>
                    </div>
                </form>
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
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Erro ao testar conexão.';
            $res.html("<span style='color:#CD0000'><i class='bx bx-x-circle'></i> " + msg + "</span>");
        },
        complete: function () { $btn.prop('disabled', false); }
    });
});
</script>
