<style>
    .form-horizontal .control-label {
        padding-top: 9px;
        width: 200px;
    }

    .form-horizontal .controls {
        margin-left: 220px;
    }

    .hint {
        display: block;
        color: #999;
        font-size: 11px;
        margin-top: 2px;
    }
</style>

<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="fas fa-file-invoice"></i></span>
                <h5>Configurações Fiscais - NF-e / NFS-e (Simples Nacional)</h5>
            </div>
            <div class="widget-content">
                <?php if (!empty($custom_error)) { echo $custom_error; } ?>

                <?php if ($this->session->flashdata('success')) { ?>
                    <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
                <?php } ?>
                <?php if ($this->session->flashdata('error')) { ?>
                    <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
                <?php } ?>

                <?php if (!$emitente) { ?>
                    <div class="alert alert-danger">
                        <strong>Atenção:</strong> cadastre os dados do emitente (CNPJ, IE e endereço) em
                        <a href="<?= site_url('mapos/emitente') ?>">Configurações &gt; Emitente</a> antes de emitir notas.
                    </div>
                <?php } else { ?>
                    <div class="alert alert-info">
                        Emitente: <strong><?= html_escape($emitente->nome) ?></strong> — CNPJ <?= html_escape($emitente->cnpj) ?> — IE <?= html_escape($emitente->ie) ?>.
                        <a href="<?= site_url('mapos/emitente') ?>">Alterar dados do emitente</a>
                    </div>
                <?php } ?>

                <form action="<?= site_url('nfe/configuracoes') ?>" method="post" enctype="multipart/form-data" class="form-horizontal">

                    <h5 style="margin-top:10px">Certificado Digital (A1)</h5>

                    <div class="control-group">
                        <label class="control-label" for="certificado">Arquivo .pfx / .p12</label>
                        <div class="controls">
                            <input type="file" id="certificado" name="certificado" accept=".pfx,.p12" />
                            <?php if (!empty($configNfe->certificado_path)) { ?>
                                <span class="hint">Certificado atual: <?= html_escape(basename($configNfe->certificado_path)) ?> (envie um novo arquivo apenas para substituir)</span>
                            <?php } else { ?>
                                <span class="hint">Nenhum certificado enviado ainda.</span>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="senha_certificado">Senha do certificado</label>
                        <div class="controls">
                            <input type="password" id="senha_certificado" name="senha_certificado" autocomplete="new-password" />
                            <span class="hint">A senha é guardada criptografada. Deixe em branco para manter a atual.</span>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label">Certificado</label>
                        <div class="controls">
                            <button type="button" id="btnSalvarCert" class="button btn btn-success">
                                <span class="button__icon"><i class="bx bx-save"></i></span><span class="button__text2">Salvar Certificado</span>
                            </button>
                            <button type="button" id="btnTestarCert" class="button btn btn-primary" <?= empty($configNfe->certificado_path) ? 'disabled' : '' ?>>
                                <span class="button__icon"><i class="bx bx-check-shield"></i></span><span class="button__text2">Testar Certificado</span>
                            </button>
                            <span class="hint"><strong>1)</strong> Selecione o .pfx + senha e clique em <strong>Salvar Certificado</strong>. <strong>2)</strong> Depois clique em <strong>Testar Certificado</strong> (leitura, senha, validade, assinatura e comunicação com a SEFAZ). Isso não mexe nos demais campos.</span>
                            <div id="salvarCertResultado" style="margin-top:8px"></div>
                            <div id="testeCertResultado" style="margin-top:10px"></div>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="ambiente">Ambiente</label>
                        <div class="controls">
                            <select name="ambiente" id="ambiente">
                                <option value="2" <?= $configNfe->ambiente == 2 ? 'selected' : '' ?>>Homologação (testes, sem valor fiscal)</option>
                                <option value="1" <?= $configNfe->ambiente == 1 ? 'selected' : '' ?>>Produção</option>
                            </select>
                        </div>
                    </div>

                    <h5>Emitente / Tributação</h5>

                    <div class="control-group">
                        <label class="control-label" for="codigo_municipio">Código IBGE do município</label>
                        <div class="controls">
                            <input type="text" id="codigo_municipio" name="codigo_municipio" maxlength="7" value="<?= html_escape($configNfe->codigo_municipio) ?>" required />
                            <span class="hint">7 dígitos. Consulte em ibge.gov.br/explica/codigos-dos-municipios.php</span>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="op_simp_nac">Situação no Simples Nacional</label>
                        <div class="controls">
                            <select name="op_simp_nac" id="op_simp_nac">
                                <option value="3" <?= $configNfe->op_simp_nac == 3 ? 'selected' : '' ?>>Optante - ME/EPP</option>
                                <option value="2" <?= $configNfe->op_simp_nac == 2 ? 'selected' : '' ?>>Optante - MEI</option>
                                <option value="1" <?= $configNfe->op_simp_nac == 1 ? 'selected' : '' ?>>Não optante</option>
                            </select>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="inscricao_municipal">Inscrição Municipal</label>
                        <div class="controls">
                            <input type="text" id="inscricao_municipal" name="inscricao_municipal" maxlength="20" value="<?= html_escape($configNfe->inscricao_municipal) ?>" />
                            <span class="hint">Usada na NFS-e. Deixe em branco se não possuir.</span>
                        </div>
                    </div>

                    <h5>NF-e (Vendas - modelo 55)</h5>

                    <div class="control-group">
                        <label class="control-label" for="serie_nfe">Série</label>
                        <div class="controls">
                            <input type="text" id="serie_nfe" name="serie_nfe" maxlength="3" value="<?= html_escape($configNfe->serie_nfe) ?>" required style="width:60px" />
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="proximo_numero_nfe">Próximo número</label>
                        <div class="controls">
                            <input type="number" id="proximo_numero_nfe" name="proximo_numero_nfe" min="1" value="<?= html_escape($configNfe->proximo_numero_nfe) ?>" required style="width:100px" />
                            <span class="hint">Se já emitiu NF-e em outro sistema, informe o próximo número livre da série.</span>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="csosn_padrao">CSOSN padrão</label>
                        <div class="controls">
                            <select name="csosn_padrao" id="csosn_padrao" style="width:auto">
                                <?php foreach (['102' => '102 - Tributada sem permissão de crédito', '101' => '101 - Tributada com permissão de crédito', '103' => '103 - Isenção do ICMS para faixa de receita', '400' => '400 - Não tributada pelo Simples Nacional', '500' => '500 - ICMS cobrado anteriormente por ST'] as $codigo => $descricao) { ?>
                                    <option value="<?= $codigo ?>" <?= $configNfe->csosn_padrao == $codigo ? 'selected' : '' ?>><?= $descricao ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="cfop_padrao">CFOP padrão</label>
                        <div class="controls">
                            <input type="text" id="cfop_padrao" name="cfop_padrao" maxlength="4" value="<?= html_escape($configNfe->cfop_padrao) ?>" required style="width:60px" />
                            <span class="hint">5102 = venda de mercadoria adquirida de terceiros, dentro do estado. Produtos podem ter CFOP próprio no cadastro.</span>
                        </div>
                    </div>

                    <h5>NFS-e Padrão Nacional (Ordens de Serviço)</h5>

                    <div class="control-group">
                        <label class="control-label" for="serie_dps">Série da DPS</label>
                        <div class="controls">
                            <input type="text" id="serie_dps" name="serie_dps" maxlength="5" value="<?= html_escape($configNfe->serie_dps) ?>" required style="width:60px" />
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="proximo_numero_dps">Próximo número da DPS</label>
                        <div class="controls">
                            <input type="number" id="proximo_numero_dps" name="proximo_numero_dps" min="1" value="<?= html_escape($configNfe->proximo_numero_dps) ?>" required style="width:100px" />
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="tp_ret_issqn">Retenção do ISSQN</label>
                        <div class="controls">
                            <select name="tp_ret_issqn" id="tp_ret_issqn" style="width:auto">
                                <option value="1" <?= $configNfe->tp_ret_issqn == 1 ? 'selected' : '' ?>>1 - Não retido</option>
                                <option value="2" <?= $configNfe->tp_ret_issqn == 2 ? 'selected' : '' ?>>2 - Retido pelo tomador</option>
                            </select>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="reg_esp_trib">Regime especial de tributação</label>
                        <div class="controls">
                            <input type="number" id="reg_esp_trib" name="reg_esp_trib" min="0" max="6" value="<?= html_escape($configNfe->reg_esp_trib) ?>" style="width:60px" />
                            <span class="hint">0 = Nenhum (padrão para a maioria das empresas do Simples).</span>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="aliquota_iss">Alíquota ISS (%)</label>
                        <div class="controls">
                            <input type="text" id="aliquota_iss" name="aliquota_iss" value="<?= html_escape($configNfe->aliquota_iss) ?>" style="width:60px" />
                            <span class="hint">Informativa. No Simples Nacional o ISS é apurado pelo DAS.</span>
                        </div>
                    </div>

                    <div class="form-actions" style="background:transparent">
                        <button type="submit" class="button btn btn-success">
                            <span class="button__icon"><i class='bx bx-save'></i></span>
                            <span class="button__text2">Salvar Configurações</span>
                        </button>
                        <a href="<?= site_url('nfe/gerenciar') ?>" class="button btn btn-warning">
                            <span class="button__icon"><i class='bx bx-arrow-back'></i></span>
                            <span class="button__text2">Voltar</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#btnSalvarCert').on('click', function() {
            var btn = $(this);
            var fd = new FormData();
            var arquivo = $('#certificado')[0].files[0];
            if (arquivo) { fd.append('certificado', arquivo); }
            fd.append('senha_certificado', $('#senha_certificado').val());

            // O ajaxSetup global (csrf.js) não consegue injetar o token em FormData;
            // anexamos manualmente para o CSRF do CodeIgniter não bloquear (403).
            var csrfName = $('meta[name="csrf-token-name"]').attr('content');
            var csrfCookie = $('meta[name="csrf-cookie-name"]').attr('content');
            if (csrfName && typeof getCookie === 'function') {
                fd.append(csrfName, getCookie(csrfCookie));
            }

            btn.attr('disabled', true);
            $('#salvarCertResultado').html('<div class="alert alert-info">Salvando o certificado, aguarde...</div>');

            $.ajax({
                url: '<?= site_url('nfe/salvarCertificado') ?>',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(data) {
                    btn.attr('disabled', false);
                    $('#salvarCertResultado').html('<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</div>');
                    if (data.success) {
                        $('#btnTestarCert').attr('disabled', false);
                    }
                },
                error: function(xhr) {
                    btn.attr('disabled', false);
                    var msg = 'Falha de comunicação (HTTP ' + xhr.status + ').';
                    if (xhr.status === 404) {
                        msg += ' O endpoint "salvarCertificado" não existe no servidor — falta o deploy do arquivo Nfe.php e limpar o cache do PHP (OPcache).';
                    } else if (xhr.status === 413) {
                        msg += ' O arquivo é grande demais para o limite de upload do servidor.';
                    } else if (xhr.responseText) {
                        msg += ' Resposta do servidor: ' + $('<div>').text(xhr.responseText.substring(0, 300)).html();
                    }
                    $('#salvarCertResultado').html('<div class="alert alert-danger">' + msg + '</div>');
                }
            });
        });

        $('#btnTestarCert').on('click', function() {
            var btn = $(this);
            btn.attr('disabled', true);
            $('#testeCertResultado').html('<div class="alert alert-info">Testando o certificado, aguarde...</div>');

            $.get('<?= site_url('nfe/testarCertificado') ?>', function(data) {
                btn.attr('disabled', false);
                var html = '<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</div>';
                if (data.checks && data.checks.length) {
                    html += '<ul style="list-style:none;padding-left:0;margin:0">';
                    $.each(data.checks, function(i, c) {
                        var icon = c.ok === true ? '✅' : (c.ok === false ? '❌' : '⚠️');
                        html += '<li style="margin-bottom:4px">' + icon + ' <strong>' + c.titulo + ':</strong> ' + c.detalhe + '</li>';
                    });
                    html += '</ul>';
                }
                $('#testeCertResultado').html(html);
            }, 'json').fail(function() {
                btn.attr('disabled', false);
                $('#testeCertResultado').html('<div class="alert alert-danger">Falha de comunicação com o servidor.</div>');
            });
        });
    });
</script>
