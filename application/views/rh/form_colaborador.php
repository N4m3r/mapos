<?php
$edit = isset($colaborador);
$c = $edit ? $colaborador : null;
$val = function ($campo, $default = '') use ($c) {
    return htmlspecialchars($c && isset($c->$campo) && $c->$campo !== null ? $c->$campo : $default);
};
$action = $edit ? site_url('rh/editarColaborador/' . $c->id) : site_url('rh/adicionarColaborador');
?>
<div class="new122">
    <div class="widget-title" style="margin:-20px 0 10px">
        <span class="icon"><i class="fas fa-user"></i></span>
        <h5><?= $edit ? 'Editar' : 'Novo' ?> Colaborador</h5>
    </div>
    <?= $custom_error ?? '' ?>
    <form method="post" action="<?= $action ?>" class="form-horizontal" enctype="multipart/form-data">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= $c->id ?>"><?php endif; ?>
        <div class="widget-box"><div class="widget-content">
            <div class="row-fluid" style="display:flex;align-items:center;gap:16px;margin-bottom:10px">
                <div style="text-align:center">
                    <?php $temFoto = $edit && ! empty($c->foto_base64); ?>
                    <?php $avatarPlaceholder = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="110" height="110"><rect width="110" height="110" rx="12" fill="#eef0f4"/><circle cx="55" cy="42" r="22" fill="#c3c9d4"/><path d="M20 100c0-19 16-30 35-30s35 11 35 30z" fill="#c3c9d4"/></svg>'); ?>
                    <img id="foto-preview"
                         src="<?= $temFoto ? site_url('rh/fotoColaborador/' . $c->id) : $avatarPlaceholder ?>"
                         alt="Foto" style="width:110px;height:110px;border-radius:12px;object-fit:cover;border:2px solid #eef0f4">
                </div>
                <div style="flex:1">
                    <label>Foto do colaborador</label>
                    <input type="file" name="foto" id="foto-input" accept="image/*" class="span12">
                    <small style="color:#888">Envie uma imagem (até 3MB). Também pode tirar pela câmera no celular.</small>
                    <?php if ($temFoto): ?>
                        <div style="margin-top:6px"><label style="font-weight:normal"><input type="checkbox" name="remover_foto" value="1"> Remover foto atual</label></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span6"><label>Nome *</label><input type="text" name="nome" class="span12" value="<?= $val('nome') ?>" required></div>
                <div class="span3"><label>CPF</label><input type="text" name="cpf" class="span12" value="<?= $val('cpf') ?>"></div>
                <div class="span3"><label>RG</label><input type="text" name="rg" class="span12" value="<?= $val('rg') ?>"></div>
            </div>
            <div class="row-fluid">
                <div class="span3"><label>Nascimento</label><input type="date" name="data_nascimento" class="span12" value="<?= $val('data_nascimento') ?>"></div>
                <div class="span3"><label>Cargo</label><input type="text" name="cargo" class="span12" value="<?= $val('cargo') ?>"></div>
                <div class="span3"><label>Departamento</label><input type="text" name="departamento" class="span12" value="<?= $val('departamento') ?>"></div>
                <div class="span3"><label>Contrato</label>
                    <select name="tipo_contrato" class="span12">
                        <?php foreach (['CLT','PJ','Estagio','Temporario'] as $t): ?>
                            <option value="<?= $t ?>" <?= $val('tipo_contrato','CLT')===$t?'selected':'' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span3"><label>Admissão</label><input type="date" name="admissao" class="span12" value="<?= $val('admissao') ?>"></div>
                <div class="span3"><label>Demissão</label><input type="date" name="demissao" class="span12" value="<?= $val('demissao') ?>"></div>
                <div class="span3"><label>Unidade</label>
                    <select name="unidade_id" class="span12"><option value="">—</option>
                        <?php foreach ($unidades as $u): ?><option value="<?= $u->id ?>" <?= ($c && $c->unidade_id==$u->id)?'selected':'' ?>><?= htmlspecialchars($u->nome) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="span3"><label>Jornada</label>
                    <select name="jornada_id" class="span12"><option value="">—</option>
                        <?php foreach ($jornadas as $j): ?><option value="<?= $j->id ?>" <?= ($c && $c->jornada_id==$j->id)?'selected':'' ?>><?= htmlspecialchars($j->nome) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span3"><label>Salário base</label><input type="text" name="salario_base" class="span12" value="<?= $c && $c->salario_base ? number_format($c->salario_base,2,',','.') : '' ?>"></div>
                <div class="span3"><label>Valor hora <small>(opcional)</small></label><input type="text" name="valor_hora" class="span12" value="<?= $c && $c->valor_hora ? number_format($c->valor_hora,2,',','.') : '' ?>"></div>
                <div class="span3"><label>E-mail</label><input type="email" name="email" class="span12" value="<?= $val('email') ?>"></div>
                <div class="span3"><label>Celular (WhatsApp)</label><input type="text" name="celular" class="span12" value="<?= $val('celular') ?>"></div>
            </div>
            <div class="row-fluid">
                <div class="span3"><label>Tipo PIX</label><input type="text" name="pix_tipo" class="span12" value="<?= $val('pix_tipo') ?>"></div>
                <div class="span3"><label>Chave PIX</label><input type="text" name="pix_chave" class="span12" value="<?= $val('pix_chave') ?>"></div>
                <div class="span3"><label>Usuário do sistema <small>(login/ponto)</small></label>
                    <select name="usuarios_id" class="span12"><option value="">— sem acesso —</option>
                        <?php foreach ($usuarios as $u): ?><option value="<?= $u->idUsuarios ?>" <?= ($c && $c->usuarios_id==$u->idUsuarios)?'selected':'' ?>><?= htmlspecialchars($u->nome) ?> (<?= htmlspecialchars($u->email) ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="span3"><label>Situação</label>
                    <select name="situacao" class="span12">
                        <option value="1" <?= (!$c || $c->situacao)?'selected':'' ?>>Ativo</option>
                        <option value="0" <?= ($c && !$c->situacao)?'selected':'' ?>>Inativo</option>
                    </select>
                </div>
            </div>
            <div class="row-fluid"><div class="span12"><label>Observações</label><textarea name="observacoes" rows="2" class="span12"><?= $val('observacoes') ?></textarea></div></div>
        </div></div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="button btn btn-success"><span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2"> Salvar</span></button>
            <a href="<?= site_url('rh/colaboradores') ?>" class="button btn btn-warning"><span class="button__text2">Voltar</span></a>
            <?php if ($edit): ?>
                <a href="<?= site_url('rh/biometria/'.$c->id) ?>" class="button btn btn-primary"><span class="button__icon"><i class='bx bx-face'></i></span><span class="button__text2"> Biometria <?= !empty($tem_biometria)?'(cadastrada)':'' ?></span></a>
            <?php endif; ?>
        </div>
    </form>
</div>
<script>
document.getElementById('foto-input').addEventListener('change', function(e){
    var f = e.target.files[0];
    if (!f) return;
    if (f.size > 3*1024*1024){ alert('A foto excede 3MB.'); this.value=''; return; }
    var r = new FileReader();
    r.onload = function(ev){ document.getElementById('foto-preview').src = ev.target.result; };
    r.readAsDataURL(f);
});
</script>
