<?php
$perfilAtual = function_exists('tributacao_perfil_atual') ? tributacao_perfil_atual($configNfe) : 'simples';
$perfis = function_exists('tributacao_perfis') ? tributacao_perfis() : [];
$reformaOficial = function_exists('tributacao_preset_reforma') ? tributacao_preset_reforma(2026) : [];
$pa = $parametrosAno ?? [];
$paAno = (int) ($pa['ano'] ?? date('Y'));
$temAliqSimples = property_exists($configNfe, 'simples_aliquota_efetiva');
?>
<style>
    .fiscal-passo { margin-bottom: 18px; padding: 14px 16px; border: 1px solid rgba(128,128,128,.35); border-radius: 8px; }
    .fiscal-passo h4 { margin: 0 0 10px; }
    .fiscal-passo .control-label { width: 200px; padding-top: 9px; }
    .fiscal-passo .controls { margin-left: 220px; }
    .hint { display: block; opacity: .7; font-size: 12px; margin-top: 2px; }
    .perfil-cards { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; }
    .perfil-card { flex: 1 1 200px; cursor: pointer; padding: 12px; border: 2px solid rgba(128,128,128,.4); border-radius: 8px; }
    .perfil-card strong { display: block; font-size: 15px; }
    .perfil-card small { display: block; opacity: .75; margin-top: 4px; }
    .perfil-card.ativo { border-color: #2d9cdb; box-shadow: 0 0 0 2px rgba(45,156,219,.3); }
    .perfil-card input { display: none; }
    .reforma-previa { padding: 8px 12px; border-radius: 6px; background: rgba(45,156,219,.12); display: inline-block; margin: 6px 0; }
    .fiscal-avancado summary { cursor: pointer; font-weight: bold; padding: 6px 0; }
    @media (max-width: 767px) {
        .fiscal-passo .control-label { width: auto; text-align: left; }
        .fiscal-passo .controls { margin-left: 0; }
    }
</style>

<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="ui-cabecalho">
            <div>
                <h1><i class="fas fa-file-invoice"></i> Configurações Fiscais</h1>
                <p>Siga os 4 passos abaixo para deixar o sistema pronto para emitir notas.</p>
            </div>
            <div class="ui-acoes">
                <a href="<?= site_url('nfe/gerenciar') ?>" class="button btn btn-info" title="Voltar para as notas fiscais">
                    <span class="button__icon"><i class="bx bx-arrow-back"></i></span><span class="button__text2">Notas Fiscais</span>
                </a>
            </div>
        </div>
        <div class="ui-dica">
            <i class="bx bx-info-circle"></i>
            <div>Comece sempre no ambiente de <strong>Homologação</strong> (notas de teste, sem valor fiscal). Só mude para <strong>Produção</strong> depois de validar uma emissão completa.</div>
        </div>
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="fas fa-cog"></i></span>
                <h5>Parâmetros de emissão</h5>
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

                <form action="<?= site_url('nfe/configuracoes') ?>" method="post" enctype="multipart/form-data" class="form-horizontal" id="formFiscal">

                    <!-- PASSO 1 -->
                    <div class="fiscal-passo">
                        <h4>1. Sua empresa</h4>
                        <p class="hint" style="margin-bottom:8px">Escolha como sua empresa é enquadrada. Não sabe? Pergunte ao seu contador.</p>
                        <div class="perfil-cards">
                            <?php foreach ($perfis as $chave => $perfil) { ?>
                                <label class="perfil-card <?= $chave === $perfilAtual ? 'ativo' : '' ?>" data-perfil="<?= $chave ?>"
                                    data-op="<?= (int) $perfil['preset']['op_simp_nac'] ?>"
                                    data-csosn="<?= html_escape((string) $perfil['preset']['csosn_padrao']) ?>"
                                    data-cfop="<?= html_escape($perfil['preset']['cfop_padrao']) ?>">
                                    <input type="radio" name="perfil_empresa" value="<?= $chave ?>" <?= $chave === $perfilAtual ? 'checked' : '' ?> />
                                    <strong><?= html_escape($perfil['rotulo']) ?></strong>
                                    <small><?= html_escape($perfil['descricao']) ?></small>
                                </label>
                            <?php } ?>
                        </div>

                        <div class="control-group" id="grupoAliquotaDas" style="<?= $perfilAtual === 'normal' ? 'display:none' : '' ?>">
                            <label class="control-label" for="simples_aliquota_efetiva">% que você paga no DAS</label>
                            <div class="controls">
                                <?php if ($temAliqSimples) { ?>
                                    <input type="text" id="simples_aliquota_efetiva" name="simples_aliquota_efetiva" value="<?= html_escape(number_format((float) $configNfe->simples_aliquota_efetiva, 2, ',', '')) ?>" style="width:80px" /> %
                                    <span class="hint">Veja no extrato do Simples (PGDAS-D) a alíquota efetiva. Ex.: 6,00. Usado para estimar o imposto das cobranças.</span>
                                <?php } else { ?>
                                    <span class="hint">Disponível após atualizar o banco de dados (migration).</span>
                                <?php } ?>
                            </div>
                        </div>

                        <?php if (!empty($opcaoTabelaOk)) {
                            $regAtual = tributacao_regime_ibscbs($opcoesIbscbs, date('Y-m-d'), (int) $configNfe->op_simp_nac);
                            $janela = tributacao_janela_opcao(date('Y-m-d'));
                            $mapaOpc = [];
                            foreach ($opcoesIbscbs as $o) { $mapaOpc[$o['ano'] . '-' . $o['semestre']] = $o['regime']; }
                            ?>
                        <div id="grupoOpcaoIbscbs" style="<?= $perfilAtual === 'normal' ? 'display:none' : '' ?>">
                            <h5>IBS/CBS a partir de 2027</h5>
                            <?php if ($janela && ($janela['aberta'] || $janela['dias_para_abrir'] <= 30)) { ?>
                                <div class="alert alert-warning">
                                    <?= html_escape($janela['rotulo']) ?>:
                                    <?= $janela['aberta'] ? 'aberta até ' : 'abre em ' ?><?= date('d/m/Y', strtotime($janela[$janela['aberta'] ? 'fim' : 'inicio'])) ?>.
                                    Fale com seu contador antes do prazo.
                                </div>
                            <?php } ?>
                            <?php foreach ([[2027, 1], [2027, 2], [2028, 1], [2028, 2]] as $sm) { $k = $sm[0] . '-' . $sm[1]; ?>
                                <div class="control-group" style="margin-bottom:4px">
                                    <label class="control-label" for="opc_<?= $k ?>"><?= $sm[0] ?>/<?= $sm[1] ?></label>
                                    <div class="controls">
                                        <select id="opc_<?= $k ?>" name="opcao_ibscbs[<?= $k ?>]">
                                            <option value="das" <?= ($mapaOpc[$k] ?? 'das') === 'das' ? 'selected' : '' ?>>Dentro do DAS</option>
                                            <option value="regular" <?= ($mapaOpc[$k] ?? 'das') === 'regular' ? 'selected' : '' ?>>Regime regular (por fora do DAS)</option>
                                        </select>
                                    </div>
                                </div>
                            <?php } ?>
                            <p class="hint"><?= html_escape(tributacao_texto_credito_cliente($regAtual)) ?></p>
                        </div>
                        <?php } ?>

                        <div class="control-group">
                            <label class="control-label" for="codigo_municipio">Código IBGE do município</label>
                            <div class="controls">
                                <input type="text" id="codigo_municipio" name="codigo_municipio" maxlength="7" value="<?= html_escape($configNfe->codigo_municipio) ?>" required />
                                <span class="hint">7 dígitos. Consulte em ibge.gov.br/explica/codigos-dos-municipios.php (Manaus: 1302603).</span>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="inscricao_municipal">Inscrição Municipal</label>
                            <div class="controls">
                                <input type="text" id="inscricao_municipal" name="inscricao_municipal" maxlength="20" value="<?= html_escape($configNfe->inscricao_municipal) ?>" />
                                <span class="hint">Necessária para nota de serviço (NFS-e). Deixe em branco se não possuir.</span>
                            </div>
                        </div>
                    </div>

                    <!-- PASSO 2 -->
                    <div class="fiscal-passo">
                        <h4>2. Certificado digital</h4>

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
                                <span class="hint">Use Homologação até validar uma emissão completa.</span>
                            </div>
                        </div>
                    </div>

                    <!-- PASSO 3 -->
                    <div class="fiscal-passo">
                        <h4>3. Reforma Tributária (IBS/CBS)</h4>
                        <div class="alert alert-info" style="margin-bottom:10px">
                            2026 é ano de teste: os valores aparecem na nota mas não são cobrados. Para empresas do Simples o preenchimento em 2026 é opcional — confirme com seu contador.
                        </div>

                        <div class="alert alert-warning" id="avisoRegimeNormal" style="margin-bottom:10px;<?= (int) $configNfe->op_simp_nac === 1 ? '' : 'display:none' ?>">
                            Empresa do regime normal: o IBS/CBS é obrigatório na NF-e e será sempre enviado.
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="reforma_ativa">Mostrar IBS/CBS na nota</label>
                            <div class="controls">
                                <label class="checkbox" style="display:inline-flex;align-items:center;gap:8px">
                                    <input type="checkbox" id="reforma_ativa" name="reforma_ativa" value="1" <?= (!empty($configNfe->reforma_ativa) || (int) $configNfe->op_simp_nac === 1) ? 'checked' : '' ?> />
                                    Ligar (desligado, a nota sai como hoje)
                                </label>
                                <span class="hint">Comece em Homologação. A NFS-e passa a levar IBS/CBS automaticamente quando o layout for liberado.</span>
                            </div>
                        </div>

                        <div id="reforma_campos" style="<?= (!empty($configNfe->reforma_ativa) || (int) $configNfe->op_simp_nac === 1) ? '' : 'display:none' ?>">
                            <div class="control-group">
                                <label class="control-label">Valores</label>
                                <div class="controls">
                                    <button type="button" id="btnReformaOficial" class="button btn btn-primary"
                                        data-cbs="<?= $pa['cbs'] ?? 0.9 ?>" data-ibsuf="<?= $pa['ibs_uf'] ?? 0.1 ?>"
                                        data-ibsmun="<?= $pa['ibs_mun'] ?? 0 ?>" data-cst="<?= html_escape($reformaOficial['reforma_cst'] ?? '000') ?>"
                                        data-cclass="<?= html_escape($reformaOficial['reforma_cclasstrib'] ?? '000001') ?>">
                                        <span class="button__icon"><i class="bx bx-check-circle"></i></span><span class="button__text2">Usar valores oficiais de <?= $paAno ?></span>
                                    </button>
                                    <?php if (!empty($pa['estimado']) || empty($pa['confirmado'])): ?><div class="hint">(estimado — confirme com o contador)</div><?php endif; ?>
                                    <div><span class="reforma-previa" id="reformaPrevia"></span></div>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label" for="reforma_cbs">CBS (%)</label>
                                <div class="controls">
                                    <input type="text" id="reforma_cbs" name="reforma_cbs" value="<?= html_escape(isset($configNfe->reforma_cbs) ? $configNfe->reforma_cbs : '0.9000') ?>" style="width:90px" />
                                    <span class="hint">Imposto federal (substitui PIS/COFINS). Teste 2026: 0,9%.</span>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label" for="reforma_ibs_uf">IBS estadual (%)</label>
                                <div class="controls">
                                    <input type="text" id="reforma_ibs_uf" name="reforma_ibs_uf" value="<?= html_escape(isset($configNfe->reforma_ibs_uf) ? $configNfe->reforma_ibs_uf : '0.1000') ?>" style="width:90px" />
                                    <span class="hint">Parte do estado. Teste 2026: 0,1%.</span>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label" for="reforma_ibs_mun">IBS municipal (%)</label>
                                <div class="controls">
                                    <input type="text" id="reforma_ibs_mun" name="reforma_ibs_mun" value="<?= html_escape(isset($configNfe->reforma_ibs_mun) ? $configNfe->reforma_ibs_mun : '0.0000') ?>" style="width:90px" />
                                    <span class="hint">Parte do município. Em 2026 é 0.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PASSO 4 -->
                    <div class="fiscal-passo">
                        <h4>4. Numeração das notas</h4>
                        <p class="hint" style="margin-bottom:8px">Se já emitiu notas em outro sistema, informe o próximo número livre de cada série.</p>

                        <div class="control-group">
                            <label class="control-label" for="serie_nfe">NF-e (venda) — série</label>
                            <div class="controls">
                                <input type="text" id="serie_nfe" name="serie_nfe" maxlength="3" value="<?= html_escape($configNfe->serie_nfe) ?>" required style="width:60px" />
                                <span class="hint">Nota de venda de produtos (modelo 55). Normalmente série 1.</span>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="proximo_numero_nfe">NF-e — próximo número</label>
                            <div class="controls">
                                <input type="number" id="proximo_numero_nfe" name="proximo_numero_nfe" min="1" value="<?= html_escape($configNfe->proximo_numero_nfe) ?>" required style="width:100px" />
                            </div>
                        </div>

                        <?php if (property_exists($configNfe, 'serie_nfce')) { ?>
                            <div class="control-group">
                                <label class="control-label" for="serie_nfce">NFC-e (PDV) — série</label>
                                <div class="controls">
                                    <input type="number" id="serie_nfce" name="serie_nfce" min="1" max="999" value="<?= html_escape($configNfe->serie_nfce) ?>" style="width:60px" />
                                    <span class="hint">Cupom fiscal eletrônico do PDV (modelo 65).</span>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label" for="proximo_numero_nfce">NFC-e — próximo número</label>
                                <div class="controls">
                                    <input type="number" id="proximo_numero_nfce" name="proximo_numero_nfce" min="1" value="<?= html_escape($configNfe->proximo_numero_nfce) ?>" style="width:100px" />
                                </div>
                            </div>
                        <?php } ?>

                        <div class="control-group">
                            <label class="control-label" for="serie_dps">NFS-e (serviço) — série</label>
                            <div class="controls">
                                <input type="text" id="serie_dps" name="serie_dps" maxlength="5" value="<?= html_escape($configNfe->serie_dps) ?>" required style="width:60px" />
                                <span class="hint">Nota de serviço das Ordens de Serviço (DPS, padrão nacional).</span>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="proximo_numero_dps">NFS-e — próximo número</label>
                            <div class="controls">
                                <input type="number" id="proximo_numero_dps" name="proximo_numero_dps" min="1" value="<?= html_escape($configNfe->proximo_numero_dps) ?>" required style="width:100px" />
                            </div>
                        </div>
                    </div>

                    <!-- AVANCADO -->
                    <details class="fiscal-passo fiscal-avancado">
                        <summary>Avançado (para o contador)</summary>
                        <p><a href="<?= site_url('tributacao/tabelas') ?>"><i class="fas fa-table"></i> Tabelas da Reforma Tributária</a> &middot; <a href="<?= site_url('tributacao/central') ?>"><i class="fas fa-balance-scale"></i> Central da Reforma Tributária</a> &middot; <a href="<?= site_url('tributacao/simulador') ?>"><i class="fas fa-calculator"></i> Simulador DAS × regime regular</a></p>
                        <p class="hint">Estes campos são preenchidos automaticamente ao escolher o perfil no passo 1. Só altere com orientação do contador.</p>

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
                            <label class="control-label" for="csosn_padrao">CSOSN padrão</label>
                            <div class="controls">
                                <select name="csosn_padrao" id="csosn_padrao" style="width:auto">
                                    <?php foreach (['102' => '102 - Tributada sem permissão de crédito', '101' => '101 - Tributada com permissão de crédito', '103' => '103 - Isenção do ICMS para faixa de receita', '400' => '400 - Não tributada pelo Simples Nacional', '500' => '500 - ICMS cobrado anteriormente por ST'] as $codigo => $descricao) { ?>
                                        <option value="<?= $codigo ?>" <?= $configNfe->csosn_padrao == $codigo ? 'selected' : '' ?>><?= $descricao ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <?php if (is_object($configNfe) && property_exists($configNfe, 'icms_cst_padrao')) { ?>
                        <div id="blocoRegimeNormal" style="<?= (int) $configNfe->op_simp_nac === 1 ? '' : 'display:none' ?>">
                            <p><strong>Regime normal (Lucro Presumido/Real)</strong></p>
                            <div class="control-group">
                                <label class="control-label" for="icms_cst_padrao">CST do ICMS padrão</label>
                                <div class="controls">
                                    <select name="icms_cst_padrao" id="icms_cst_padrao" style="width:auto">
                                        <?php foreach (['00' => '00 - Tributada integralmente', '40' => '40 - Isenta', '41' => '41 - Não tributada', '50' => '50 - Suspensão', '60' => '60 - ICMS cobrado antes por ST'] as $c => $t) { ?>
                                            <option value="<?= $c ?>" <?= $configNfe->icms_cst_padrao === (string) $c ? 'selected' : '' ?>><?= $t ?></option>
                                        <?php } ?>
                                    </select>
                                    <span class="hint">Cada produto pode ter o seu no cadastro.</span>
                                </div>
                            </div>
                            <div class="control-group">
                                <label class="control-label" for="icms_aliquota_interna">ICMS dentro do estado (%)</label>
                                <div class="controls"><input type="text" id="icms_aliquota_interna" name="icms_aliquota_interna" value="<?= html_escape($configNfe->icms_aliquota_interna) ?>" style="width:70px" /></div>
                            </div>
                            <div class="control-group">
                                <label class="control-label" for="icms_aliquota_interestadual">ICMS para outro estado (%)</label>
                                <div class="controls"><input type="text" id="icms_aliquota_interestadual" name="icms_aliquota_interestadual" value="<?= html_escape($configNfe->icms_aliquota_interestadual) ?>" style="width:70px" />
                                    <span class="hint">Em geral 12% (7% do Sul/Sudeste para Norte, Nordeste, Centro-Oeste e ES).</span></div>
                            </div>
                            <div class="control-group">
                                <label class="control-label" for="pis_cofins_regime">PIS e COFINS</label>
                                <div class="controls">
                                    <select name="pis_cofins_regime" id="pis_cofins_regime" style="width:auto">
                                        <option value="cumulativo" <?= $configNfe->pis_cofins_regime === 'cumulativo' ? 'selected' : '' ?>>Cumulativo (PIS 0,65% e COFINS 3%)</option>
                                        <option value="nao_cumulativo" <?= $configNfe->pis_cofins_regime === 'nao_cumulativo' ? 'selected' : '' ?>>Não cumulativo (PIS 1,65% e COFINS 7,6%)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="control-group">
                                <label class="control-label" for="pis_cofins_cst">CST do PIS/COFINS</label>
                                <div class="controls"><input type="text" id="pis_cofins_cst" name="pis_cofins_cst" maxlength="2" value="<?= html_escape($configNfe->pis_cofins_cst) ?>" style="width:50px" />
                                    <span class="hint">01 = tributado pela alíquota normal.</span></div>
                            </div>
                        </div>
                        <?php } ?>

                        <div class="control-group">
                            <label class="control-label" for="cfop_padrao">CFOP padrão</label>
                            <div class="controls">
                                <input type="text" id="cfop_padrao" name="cfop_padrao" maxlength="4" value="<?= html_escape($configNfe->cfop_padrao) ?>" required style="width:60px" />
                                <span class="hint">5102 = venda de mercadoria adquirida de terceiros, dentro do estado. Produtos podem ter CFOP próprio no cadastro.</span>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="reforma_cst">CST do IBS/CBS</label>
                            <div class="controls">
                                <input type="text" id="reforma_cst" name="reforma_cst" maxlength="3" value="<?= html_escape(isset($configNfe->reforma_cst) && $configNfe->reforma_cst !== '' ? $configNfe->reforma_cst : '000') ?>" style="width:70px" />
                                <span class="hint">3 dígitos. 000 = tributação integral (padrão).</span>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="reforma_cclasstrib">cClassTrib</label>
                            <div class="controls">
                                <input type="text" id="reforma_cclasstrib" name="reforma_cclasstrib" maxlength="6" value="<?= html_escape(isset($configNfe->reforma_cclasstrib) && $configNfe->reforma_cclasstrib !== '' ? $configNfe->reforma_cclasstrib : '000001') ?>" style="width:90px" />
                                <span class="hint">6 dígitos. Classificação tributária do IBS/CBS. 000001 = tributação integral.</span>
                            </div>
                        </div>

                        <?php if (is_object($configNfe) && property_exists($configNfe, 'reforma_cindop')) { ?>
                        <div class="control-group">
                            <label class="control-label" for="reforma_cindop">Indicador da operação (NFS-e)</label>
                            <div class="controls">
                                <input type="text" id="reforma_cindop" name="reforma_cindop" maxlength="6" value="<?= html_escape($configNfe->reforma_cindop ?? '') ?>" placeholder="000000" style="width:90px" />
                                <span class="hint">cIndOp, 6 dígitos (Anexo VIII da NFS-e: código do serviço × NBS). <strong>Vazio = a NFS-e sai sem o grupo IBS/CBS.</strong> Peça o código ao contador. Cada serviço pode ter o seu no cadastro de serviços.</span>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="reforma_indfinal">Consumidor final (NFS-e)</label>
                            <div class="controls">
                                <label class="checkbox" style="display:inline-flex;align-items:center;gap:8px">
                                    <input type="checkbox" id="reforma_indfinal" name="reforma_indfinal" value="1" <?= ! empty($configNfe->reforma_indfinal) ? 'checked' : '' ?> />
                                    A maioria dos meus clientes é consumidor final (pessoa física / uso próprio)
                                </label>
                            </div>
                        </div>
                        <?php } ?>

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

                        <div class="control-group">
                            <label class="control-label" for="ctribnac_padrao">Cód. Tributação Nacional padrão</label>
                            <div class="controls">
                                <input type="text" id="ctribnac_padrao" name="ctribnac_padrao" maxlength="6" value="<?= html_escape(isset($configNfe->ctribnac_padrao) && $configNfe->ctribnac_padrao !== '' ? $configNfe->ctribnac_padrao : '010701') ?>" style="width:90px" />
                                <span class="hint">6 dígitos. Usado na emissão automática (com boleto) e como sugestão no wizard. Padrão: 010701 (suporte em informática).</span>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="ctribmun_padrao">Cód. Tributação Municipal padrão</label>
                            <div class="controls">
                                <input type="text" id="ctribmun_padrao" name="ctribmun_padrao" maxlength="10" value="<?= html_escape(isset($configNfe->ctribmun_padrao) && $configNfe->ctribmun_padrao !== '' ? $configNfe->ctribmun_padrao : '100') ?>" style="width:80px" />
                                <span class="hint">Padrão: 100 (validado em Manaus com cTribNac 010701).</span>
                            </div>
                        </div>

                        <?php if (property_exists($configNfe, 'serie_nfce')) { ?>
                            <div class="control-group">
                                <label class="control-label" for="csc_id">ID do CSC</label>
                                <div class="controls">
                                    <input type="text" id="csc_id" name="csc_id" maxlength="10" value="<?= html_escape($configNfe->csc_id) ?>" style="width:100px" placeholder="000001" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label" for="csc">CSC (token)</label>
                                <div class="controls">
                                    <input type="text" id="csc" name="csc" maxlength="64" value="<?= html_escape($configNfe->csc) ?>" autocomplete="off" style="width:320px" />
                                    <span class="hint">Código de Segurança do Contribuinte, gerado no portal da SEFAZ do seu estado (um para homologação e outro para produção). Sem ele a NFC-e não é emitida.</span>
                                </div>
                            </div>
                        <?php } ?>
                    </details>

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

<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        // Perfil da empresa: aplica o preset nos campos (inclusive os do "Avançado").
        $('.perfil-card').on('click', function() {
            var c = $(this);
            $('.perfil-card').removeClass('ativo');
            c.addClass('ativo').find('input').prop('checked', true);
            $('#op_simp_nac').val(c.data('op'));
            if (c.data('csosn') !== '' && c.data('csosn') !== undefined) { $('#csosn_padrao').val(String(c.data('csosn'))); }
            $('#cfop_padrao').val(String(c.data('cfop')));
            $('#grupoAliquotaDas').toggle(c.data('perfil') !== 'normal');
        });

        // Prévia: numa venda de R$ 100,00.
        function num(v) { return parseFloat(String(v).replace(',', '.')) || 0; }
        function fmt(v) { return v.toFixed(2).replace('.', ','); }
        function atualizarPrevia() {
            var cbs = num($('#reforma_cbs').val());
            var ibs = num($('#reforma_ibs_uf').val()) + num($('#reforma_ibs_mun').val());
            $('#reformaPrevia').text('Numa venda de R$ 100,00: CBS R$ ' + fmt(cbs) + ' · IBS R$ ' + fmt(ibs));
        }
        $('#reforma_cbs, #reforma_ibs_uf, #reforma_ibs_mun').on('input', atualizarPrevia);
        atualizarPrevia();

        $('#btnReformaOficial').on('click', function() {
            var b = $(this);
            $('#reforma_cbs').val(b.data('cbs'));
            $('#reforma_ibs_uf').val(b.data('ibsuf'));
            $('#reforma_ibs_mun').val(b.data('ibsmun'));
            $('#reforma_cst').val(String(b.data('cst')));
            $('#reforma_cclasstrib').val(String(b.data('cclass')));
            atualizarPrevia();
        });

        // Mostra/oculta os parâmetros de IBS/CBS conforme o checkbox mestre.
        // Regime normal (1): IBS/CBS sempre enviado; interruptor fica marcado e travado
        // visualmente (sem 'disabled', para o valor continuar indo no POST).
        function regimeNormal() {
            var normal = String($('#op_simp_nac').val()) === '1';
            $('#avisoRegimeNormal').toggle(normal);
            $('#blocoRegimeNormal').toggle(normal);
            $('#grupoOpcaoIbscbs').toggle(!normal);
            if (normal) { $('#reforma_ativa').prop('checked', true).trigger('change'); }
            $('#reforma_ativa').closest('label').css({opacity: normal ? .6 : 1, 'pointer-events': normal ? 'none' : ''});
        }
        $('#op_simp_nac').on('change', regimeNormal);
        regimeNormal();
        $('.perfil-card').on('click', function() { setTimeout(regimeNormal, 0); });

        $('#reforma_ativa').on('change', function() {
            $('#reforma_campos').toggle(this.checked);
        });

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
