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
    <form method="post" action="<?= $action ?>" class="form-horizontal">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= $c->id ?>"><?php endif; ?>
        <div class="widget-box"><div class="widget-content">
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
