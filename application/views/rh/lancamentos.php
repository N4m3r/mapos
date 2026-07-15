<?php
$tipos = ['hora_extra'=>'Hora extra','adicional'=>'Adicional','comissao'=>'Comissão','bonus'=>'Bônus',
    'adiantamento'=>'Adiantamento','desconto'=>'Desconto','falta'=>'Falta','vale'=>'Vale'];
?>
<div class="new122">
    <?php $this->load->view('rh/_subnav', ['ativo' => 'lancamentos']); ?>
    <div class="widget-title" style="margin:0 0 10px">
        <span class="icon"><i class="fas fa-money-bill"></i></span><h5>Lançamentos / Extras</h5>
    </div>

    <div class="span12" style="margin-left:0;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <form method="get" action="<?= site_url('rh/lancamentos') ?>" style="display:flex;gap:6px;align-items:center">
            <input type="month" name="competencia" value="<?= $competencia ?>">
            <select name="colaborador_id">
                <option value="">Todos os colaboradores</option>
                <?php foreach ($colaboradores as $c): ?>
                    <option value="<?= $c->id ?>" <?= $colaborador_id==$c->id?'selected':'' ?>><?= htmlspecialchars($c->nome) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button btn btn-mini btn-warning"><span class="button__text2">Filtrar</span></button>
        </form>
        <a href="#modal-lanc" role="button" data-toggle="modal" class="button btn btn-mini btn-success" onclick="novoLanc()">
            <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2"> Lançamento</span>
        </a>
    </div>

    <div class="widget-box"><div class="widget-content nopadding">
        <table class="table table-bordered">
            <thead><tr><th>Colaborador</th><th>Tipo</th><th>Descrição</th><th>Qtd</th><th>Valor</th><th>Aprovado</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (empty($lancamentos)): ?>
                <tr><td colspan="7">Nenhum lançamento na competência.</td></tr>
            <?php else: foreach ($lancamentos as $l): ?>
                <tr>
                    <td><?= htmlspecialchars($l->nome_colaborador ?: '#'.$l->colaborador_id) ?></td>
                    <td><?= $tipos[$l->tipo] ?? $l->tipo ?></td>
                    <td><?= htmlspecialchars($l->descricao) ?></td>
                    <td><?= $l->quantidade !== null ? rtrim(rtrim(number_format($l->quantidade,2,',','.'),'0'),',') : '-' ?></td>
                    <td style="color:<?= $l->natureza==='desconto'?'#dc2626':'#16a34a' ?>"><?= $l->natureza==='desconto'?'-':'+' ?> R$ <?= number_format($l->valor,2,',','.') ?></td>
                    <td><?php if ($l->aprovado): ?><span style="color:#16a34a">Sim</span><?php else: ?>
                        <form method="post" action="<?= site_url('rh/aprovarLancamento') ?>" style="display:inline">
                            <input type="hidden" name="id" value="<?= $l->id ?>">
                            <button class="btn btn-mini btn-success">Aprovar</button>
                        </form><?php endif; ?></td>
                    <td>
                        <a href="#modal-lanc" role="button" data-toggle="modal" class="btn-nwe3" onclick='editarLanc(<?= json_encode($l) ?>)'><i class="bx bx-edit bx-xs"></i></a>
                        <a href="#modal-excluir-l" role="button" data-toggle="modal" reg="<?= $l->id ?>" class="btn-nwe4"><i class="bx bx-trash-alt bx-xs"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div></div>
</div>

<div id="modal-lanc" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/salvarLancamento') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5 id="l-titulo">Lançamento</h5></div>
        <div class="modal-body">
            <input type="hidden" name="id" id="l-id">
            <label>Colaborador</label>
            <select name="colaborador_id" id="l-colab" class="span12">
                <?php foreach ($colaboradores as $c): ?><option value="<?= $c->id ?>"><?= htmlspecialchars($c->nome) ?></option><?php endforeach; ?>
            </select>
            <div style="display:flex;gap:8px">
                <div style="flex:1"><label>Competência</label><input type="month" name="competencia" id="l-comp" class="span12" value="<?= $competencia ?>"></div>
                <div style="flex:1"><label>Tipo</label>
                    <select name="tipo" id="l-tipo" class="span12"><?php foreach ($tipos as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select>
                </div>
            </div>
            <label>Descrição</label><input type="text" name="descricao" id="l-desc" class="span12">
            <div style="display:flex;gap:8px">
                <div style="flex:1"><label>Quantidade</label><input type="text" name="quantidade" id="l-qtd" class="span12"></div>
                <div style="flex:1"><label>Valor (R$)</label><input type="text" name="valor" id="l-valor" class="span12"></div>
            </div>
            <label style="font-weight:normal;margin-top:6px"><input type="checkbox" name="aprovado" id="l-aprov" value="1"> Já aprovado</label>
<<<<<<< HEAD
=======
            <p style="font-size:11px;color:#9ca3af;margin:4px 0 0">Horas extras ficam pendentes de aprovação do administrativo (conforme config CLT).</p>
>>>>>>> 43f6f5a (correcao sintaxe)
        </div>
        <div class="modal-footer"><button class="button btn btn-success"><span class="button__text2">Salvar</span></button></div>
    </form>
</div>

<div id="modal-excluir-l" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/excluirLancamento') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Excluir</h5></div>
        <div class="modal-body"><input type="hidden" id="l-del-id" name="id"><h5 style="text-align:center">Confirma exclusão?</h5></div>
        <div class="modal-footer"><button class="button btn btn-danger"><span class="button__text2">Excluir</span></button></div>
    </form>
</div>
<script>
function novoLanc(){ $('#l-titulo').text('Novo lançamento'); $('#l-id').val(''); $('#l-desc').val(''); $('#l-qtd').val(''); $('#l-valor').val(''); $('#l-aprov').prop('checked',false); }
function editarLanc(l){ $('#l-titulo').text('Editar'); $('#l-id').val(l.id); $('#l-colab').val(l.colaborador_id); $('#l-comp').val(l.competencia); $('#l-tipo').val(l.tipo); $('#l-desc').val(l.descricao); $('#l-qtd').val(l.quantidade); $('#l-valor').val(l.valor); $('#l-aprov').prop('checked', l.aprovado==1); }
$(document).on('click','a[reg]',function(){ $('#l-del-id').val($(this).attr('reg')); });
</script>
