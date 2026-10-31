<?php
$edit = isset($colaborador);
$c = $edit ? $colaborador : null;
$val = function ($campo, $default = '') use ($c) {
    return html_escape($c && isset($c->$campo) && $c->$campo !== null ? $c->$campo : $default);
};
$action = $edit ? site_url('rh/editarColaborador/' . $c->id) : site_url('rh/adicionarColaborador');
$this->load->view('rh/_subnav', ['ativo' => $rh_ativo ?? 'colaboradores']);
?>
<div class="new122">
    <?php $this->load->view('rh/_migalha', ['atual' => $edit ? 'Editar ' . $c->nome : 'Novo colaborador']); ?>
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-user"></i> <?= $edit ? 'Editar colaborador' : 'Novo colaborador' ?></h1>
            <p class="ui-subtitulo">Dados cadastrais e vínculo do colaborador. Campos com <span class="rh-obrigatorio">*</span> são obrigatórios.</p>
        </div>
    </div>
    <?= $custom_error ?? '' ?>
    <form method="post" action="<?= $action ?>" class="form-horizontal" enctype="multipart/form-data" id="form-colaborador">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $c->id ?>"><?php endif; ?>

        <fieldset class="rh-secao">
            <legend><i class='bx bx-user'></i> Dados pessoais</legend>
            <div class="row-fluid" style="display:flex;align-items:center;gap:16px;margin-bottom:10px">
                <div style="text-align:center">
                    <?php $temFoto = $edit && ! empty($c->foto_base64); ?>
                    <?php $avatarPlaceholder = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="110" height="110"><rect width="110" height="110" rx="12" fill="#eef0f4"/><circle cx="55" cy="42" r="22" fill="#c3c9d4"/><path d="M20 100c0-19 16-30 35-30s35 11 35 30z" fill="#c3c9d4"/></svg>'); ?>
                    <img id="foto-preview"
                         src="<?= $temFoto ? site_url('rh/fotoColaborador/' . $c->id) : $avatarPlaceholder ?>"
                         alt="Foto do colaborador" style="width:110px;height:110px;border-radius:12px;object-fit:cover;border:2px solid #eef0f4">
                </div>
                <div style="flex:1">
                    <label for="foto-input">Foto do colaborador</label>
                    <input type="file" name="foto" id="foto-input" accept="image/*" class="span12">
                    <small class="rh-ajuda">Envie uma imagem (até 3MB). Também pode tirar pela câmera no celular.</small>
                    <?php if ($temFoto): ?>
                        <div style="margin-top:6px"><label style="font-weight:normal"><input type="checkbox" name="remover_foto" value="1"> Remover foto atual</label></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span6"><label for="f-nome">Nome completo <span class="rh-obrigatorio">*</span></label><input type="text" id="f-nome" name="nome" class="span12" value="<?= $val('nome') ?>" required autocomplete="off"></div>
                <div class="span3"><label for="f-cpf">CPF</label><input type="text" id="f-cpf" name="cpf" class="span12" value="<?= $val('cpf') ?>" data-mascara="cpf" inputmode="numeric" placeholder="000.000.000-00"></div>
                <div class="span3"><label for="f-rg">RG</label><input type="text" id="f-rg" name="rg" class="span12" value="<?= $val('rg') ?>"></div>
            </div>
            <div class="row-fluid">
                <div class="span3"><label for="f-nasc">Data de nascimento</label><input type="date" id="f-nasc" name="data_nascimento" class="span12" value="<?= $val('data_nascimento') ?>"></div>
            </div>
        </fieldset>

        <fieldset class="rh-secao">
            <legend><i class='bx bx-phone'></i> Contato</legend>
            <div class="row-fluid">
                <div class="span6"><label for="f-email">E-mail</label><input type="email" id="f-email" name="email" class="span12" value="<?= $val('email') ?>"></div>
                <div class="span6"><label for="f-celular">Celular (WhatsApp)</label><input type="text" id="f-celular" name="celular" class="span12" value="<?= $val('celular') ?>" data-mascara="celular" inputmode="tel" placeholder="(00) 00000-0000"></div>
            </div>
        </fieldset>

        <fieldset class="rh-secao">
            <legend><i class='bx bx-briefcase'></i> Contrato e cargo</legend>
            <div class="row-fluid">
                <div class="span3"><label for="f-cargo">Cargo</label><input type="text" id="f-cargo" name="cargo" class="span12" value="<?= $val('cargo') ?>"></div>
                <div class="span3"><label for="f-depto">Departamento</label><input type="text" id="f-depto" name="departamento" class="span12" value="<?= $val('departamento') ?>"></div>
                <div class="span3"><label for="f-contrato">Tipo de contrato</label>
                    <select id="f-contrato" name="tipo_contrato" class="span12">
                        <?php foreach (['CLT','PJ','Estagio','Temporario'] as $t): ?>
                            <option value="<?= $t ?>" <?= $val('tipo_contrato','CLT')===$t?'selected':'' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="span3"><label for="f-situacao">Situação</label>
                    <select id="f-situacao" name="situacao" class="span12">
                        <option value="1" <?= (!$c || $c->situacao)?'selected':'' ?>>Ativo</option>
                        <option value="0" <?= ($c && !$c->situacao)?'selected':'' ?>>Inativo</option>
                    </select>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span3"><label for="f-adm">Data de admissão</label><input type="date" id="f-adm" name="admissao" class="span12" value="<?= $val('admissao') ?>"></div>
                <div class="span3"><label for="f-dem">Data de demissão</label><input type="date" id="f-dem" name="demissao" class="span12" value="<?= $val('demissao') ?>"></div>
                <div class="span6"><label for="f-unidade">Unidade de trabalho</label>
                    <select id="f-unidade" name="unidade_id" class="span12"><option value="">—</option>
                        <?php foreach ($unidades as $u): ?><option value="<?= (int) $u->id ?>" <?= ($c && $c->unidade_id==$u->id)?'selected':'' ?>><?= html_escape($u->nome) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
        </fieldset>

        <fieldset class="rh-secao">
            <legend><i class='bx bx-time-five'></i> Jornada e ponto</legend>
            <div class="row-fluid">
                <div class="span4"><label for="f-jornada">Jornada de trabalho</label>
                    <select id="f-jornada" name="jornada_id" class="span12"><option value="">—</option>
                        <?php foreach ($jornadas as $j): ?><option value="<?= (int) $j->id ?>" <?= ($c && $c->jornada_id==$j->id)?'selected':'' ?>><?= html_escape($j->nome) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="span4"><label for="f-usuario">Usuário do sistema <small>(login/ponto)</small></label>
                    <select id="f-usuario" name="usuarios_id" class="span12"><option value="">— sem acesso —</option>
                        <?php foreach ($usuarios as $u): ?><option value="<?= (int) $u->idUsuarios ?>" <?= ($c && $c->usuarios_id==$u->idUsuarios)?'selected':'' ?>><?= html_escape($u->nome) ?> (<?= html_escape($u->email) ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="span4">
                    <label for="f-ponto-inicio">Início do controle de ponto</label>
                    <input type="date" id="f-ponto-inicio" name="ponto_inicio" class="span12" value="<?= $val('ponto_inicio') ?>">
                </div>
            </div>
            <small class="rh-ajuda">
                Enquanto o início do controle estiver vazio, o sistema <strong>não cobra</strong> faltas nem horas negativas no banco; só conta batidas reais.
                Preencha a data para começar a calcular dívida de dias sem ponto (ex.: data de implantação).
            </small>
        </fieldset>

        <fieldset class="rh-secao">
            <legend><i class='bx bx-wallet'></i> Pagamento</legend>
            <div class="row-fluid">
                <div class="span3"><label for="f-salario">Salário base (R$)</label><input type="text" id="f-salario" name="salario_base" class="span12" inputmode="decimal" placeholder="0,00" value="<?= $c && $c->salario_base ? number_format($c->salario_base,2,',','.') : '' ?>"></div>
                <div class="span3"><label for="f-vh">Valor da hora (R$) <small>(opcional)</small></label><input type="text" id="f-vh" name="valor_hora" class="span12" inputmode="decimal" placeholder="0,00" value="<?= $c && $c->valor_hora ? number_format($c->valor_hora,2,',','.') : '' ?>"></div>
                <div class="span3"><label for="f-pixtipo">Tipo da chave PIX</label><input type="text" id="f-pixtipo" name="pix_tipo" class="span12" value="<?= $val('pix_tipo') ?>" placeholder="CPF, e-mail, celular..."></div>
                <div class="span3"><label for="f-pix">Chave PIX</label><input type="text" id="f-pix" name="pix_chave" class="span12" value="<?= $val('pix_chave') ?>"></div>
            </div>
        </fieldset>

        <fieldset class="rh-secao">
            <legend><i class='bx bx-id-card'></i> Documentos: carteira de trabalho (CTPS) e PIS</legend>
            <div class="row-fluid">
                <div class="span3"><label for="f-ctps">CTPS número</label><input type="text" id="f-ctps" name="ctps_numero" class="span12" value="<?= $val('ctps_numero') ?>"></div>
                <div class="span2"><label for="f-serie">Série</label><input type="text" id="f-serie" name="ctps_serie" class="span12" value="<?= $val('ctps_serie') ?>"></div>
                <div class="span1"><label for="f-uf">UF</label><input type="text" id="f-uf" name="ctps_uf" class="span12" maxlength="2" value="<?= $val('ctps_uf') ?>" style="text-transform:uppercase"></div>
                <div class="span3"><label for="f-emissao">Emissão da CTPS</label><input type="date" id="f-emissao" name="ctps_data_emissao" class="span12" value="<?= $val('ctps_data_emissao') ?>"></div>
                <div class="span3"><label for="f-pis">PIS/PASEP</label><input type="text" id="f-pis" name="pis_pasep" class="span12" value="<?= $val('pis_pasep') ?>" data-mascara="pis" inputmode="numeric" placeholder="000.00000.00-0"></div>
            </div>
            <div class="row-fluid"><div class="span12"><label for="f-obs">Observações</label><textarea id="f-obs" name="observacoes" rows="2" class="span12"><?= $val('observacoes') ?></textarea></div></div>
        </fieldset>

        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="submit" class="button btn btn-success"><span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2"> Salvar</span></button>
            <a href="<?= html_escape($rh_voltar_url ?? site_url('rh/colaboradores')) ?>" class="button btn btn-warning"><span class="button__text2"><?= html_escape($rh_voltar_rotulo ?? 'Voltar') ?></span></a>
            <?php if ($edit): ?>
                <a href="<?= site_url('rh/biometria/'.$c->id) ?>" class="button btn btn-primary"><span class="button__icon"><i class='bx bx-face'></i></span><span class="button__text2"> Biometria <?= !empty($tem_biometria)?'(cadastrada)':'' ?></span></a>
                <a href="<?= site_url('rh/fichaCadastralPdf/'.$c->id) ?>" target="_blank" class="button btn btn-inverse"><span class="button__icon"><i class='bx bx-file'></i></span><span class="button__text2"> Ficha cadastral</span></a>
                <a href="<?= site_url('rh/crachaPdf/'.$c->id) ?>" target="_blank" class="button btn btn-inverse"><span class="button__icon"><i class='bx bx-id-card'></i></span><span class="button__text2"> Crachá</span></a>
                <?php if ($c->situacao): ?>
                    <a href="#modal-desligar" role="button" data-toggle="modal" class="button btn btn-danger"><span class="button__icon"><i class='bx bx-user-x'></i></span><span class="button__text2"> Desligar</span></a>
                <?php else: ?>
                    <button type="submit" form="form-reativar" class="button btn btn-success"><span class="button__icon"><i class='bx bx-user-check'></i></span><span class="button__text2"> Reativar</span></button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($edit && ! $c->situacao): ?>
    <!-- Formulário próprio (fora do principal): o botão "Reativar" acima o dispara via atributo form -->
    <form method="post" action="<?= site_url('rh/reativarColaborador') ?>" id="form-reativar" data-confirmar="Reativar este colaborador?">
        <input type="hidden" name="id" value="<?= (int) $c->id ?>">
    </form>
    <?php endif; ?>

    <?php if ($edit && $c->situacao): ?>
    <div id="modal-desligar" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
        <form method="post" action="<?= site_url('rh/desligarColaborador') ?>">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Fechar">×</button><h5>Desligar colaborador</h5></div>
            <div class="modal-body">
                <input type="hidden" name="id" value="<?= (int) $c->id ?>">
                <p>Desligar <strong><?= html_escape($c->nome) ?></strong>? O cadastro ficará inativo.</p>
                <label>Data do desligamento</label>
                <input type="date" name="demissao" class="span12" value="<?= date('Y-m-d') ?>" required>
                <label style="margin-top:8px">Tipo</label>
                <select name="tipo_desligamento" class="span12">
                    <option value="pedido">Pedido de demissão</option>
                    <option value="sem_justa_causa">Dispensa sem justa causa</option>
                    <option value="justa_causa">Dispensa por justa causa</option>
                    <option value="termino_contrato">Término de contrato</option>
                    <option value="acordo">Acordo entre as partes</option>
                    <option value="aposentadoria">Aposentadoria</option>
                    <option value="outro">Outro</option>
                </select>
                <label style="margin-top:8px">Motivo / observações</label>
                <textarea name="motivo_desligamento" rows="3" class="span12" placeholder="Descreva o motivo..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="submit" class="button btn btn-danger"><span class="button__text2">Confirmar desligamento</span></button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/jquery.mask.min.js') ?>"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/rh-admin.js') ?>"></script>
<script nonce="<?= csp_nonce() ?>">
document.getElementById('foto-input').addEventListener('change', function(e){
    var f = e.target.files[0];
    if (!f) return;
    if (f.size > 3*1024*1024){ alert('A foto excede 3MB.'); this.value=''; return; }
    var r = new FileReader();
    r.onload = function(ev){ document.getElementById('foto-preview').src = ev.target.result; };
    r.readAsDataURL(f);
});
</script>
