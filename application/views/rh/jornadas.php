<?php $dsem = [0=>'Dom',1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb']; ?>
<div class="new122">
    <?php $this->load->view('rh/_subnav', ['ativo' => 'jornadas']); ?>
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-time-five"></i> Jornadas e escalas</h1>
            <p class="ui-subtitulo">Modelos de jornada aplicados aos colaboradores.</p>
        </div>
    </div>
    <div class="span12" style="margin-left:0">
        <a href="#modal-jornada" role="button" data-toggle="modal" class="button btn btn-mini btn-success" data-chamar="novaJornada">
            <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2"> Jornada</span>
        </a>
    </div>
    <div class="widget-box"><div class="widget-content nopadding ui-tabela-rolagem">
        <table class="table table-bordered">
            <thead><tr><th>Nome</th><th>Carga por dia</th><th>Dias</th><th>Horário</th><th>Tolerância</th><th>Situação</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if (empty($jornadas)): ?>
                <tr><td colspan="7"><div class="ui-vazio"><i class='bx bx-time-five'></i><strong>Nenhuma jornada cadastrada</strong>Crie uma jornada para definir carga horária, dias e horários dos colaboradores.<div style="margin-top:10px"><a href="#modal-jornada" role="button" data-toggle="modal" class="button btn btn-success" data-chamar="novaJornada"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2"> Nova jornada</span></a></div></div></td></tr>
            <?php else: foreach ($jornadas as $j):
                $dias = array_map('trim', explode(',', $j->dias_semana));
                $diasTxt = implode(', ', array_map(function($d) use ($dsem){ return $dsem[(int)$d] ?? $d; }, $dias)); ?>
                <tr>
                    <td><?= htmlspecialchars($j->nome) ?></td>
                    <td><?= Rh_admin::minutosParaHoras($j->carga_diaria_min) ?> h</td>
                    <td><?= html_escape($diasTxt) ?></td>
                    <td><?= $j->hora_entrada ? substr($j->hora_entrada,0,5).' - '.substr($j->hora_saida,0,5) : '-' ?></td>
                    <td><?= (int) $j->tolerancia_min ?> min</td>
                    <td><?= $j->situacao ? '<span class="ui-selo sucesso">Ativa</span>' : '<span class="ui-selo neutro">Inativa</span>' ?></td>
                    <td>
                        <a href="#modal-jornada" role="button" data-toggle="modal" class="btn-nwe3" title="Editar" aria-label="Editar" data-chamar="editarJornada" data-args="<?= html_escape(json_encode([$j])) ?>"><i class="bx bx-edit bx-xs"></i></a>
                        <a href="#modal-excluir-j" role="button" data-toggle="modal" reg="<?= (int) $j->id ?>" data-nome="<?= html_escape($j->nome) ?>" class="btn-nwe4" title="Excluir" aria-label="Excluir"><i class="bx bx-trash-alt bx-xs"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div></div>
</div>

<div id="modal-jornada" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/salvarJornada') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5 id="j-titulo">Jornada</h5></div>
        <div class="modal-body">
            <input type="hidden" name="id" id="j-id">
            <label>Nome</label><input type="text" name="nome" id="j-nome" class="span12" required>
            <div style="display:flex;gap:8px">
                <div style="flex:1"><label for="j-carga">Carga diária (hh:mm)</label><input type="text" name="carga_diaria_min" id="j-carga" class="span12" value="08:00" placeholder="08:48" inputmode="numeric" pattern="\d{1,2}:[0-5]\d" title="Horas e minutos, por exemplo 08:48" required></div>
                <div style="flex:1"><label>Intervalo (min)</label><input type="number" name="intervalo_min" id="j-intervalo" class="span12" value="60"></div>
                <div style="flex:1"><label>Tolerância (min)</label><input type="number" name="tolerancia_min" id="j-tol" class="span12" value="10"></div>
            </div>
            <div style="display:flex;gap:8px">
                <div style="flex:1"><label>Entrada</label><input type="time" name="hora_entrada" id="j-entrada" class="span12"></div>
                <div style="flex:1"><label>Saída</label><input type="time" name="hora_saida" id="j-saida" class="span12"></div>
            </div>
            <label>Dias da semana</label>
            <div id="j-dias" style="display:flex;gap:6px;flex-wrap:wrap">
                <?php foreach ($dsem as $n=>$lbl): ?>
                    <label style="font-weight:normal"><input type="checkbox" name="dias_semana[]" value="<?= $n ?>" <?= in_array($n,[1,2,3,4,5])?'checked':'' ?>> <?= $lbl ?></label>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:8px"><label>Situação</label>
                <select name="situacao" id="j-situacao" class="span12"><option value="1">Ativa</option><option value="0">Inativa</option></select>
            </div>
        </div>
        <div class="modal-footer"><button class="button btn btn-success"><span class="button__text2">Salvar</span></button></div>
    </form>
</div>

<div id="modal-excluir-j" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/excluirJornada') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Excluir jornada</h5></div>
        <div class="modal-body"><input type="hidden" id="j-del-id" name="id"><h5 style="text-align:center">Deseja excluir a jornada <strong id="j-del-nome"></strong>?</h5></div>
        <div class="modal-footer"><button class="button btn btn-danger"><span class="button__text2">Excluir</span></button></div>
    </form>
</div>
<script nonce="<?= csp_nonce() ?>">
function minParaHHMM(m){ m = parseInt(m, 10) || 0; return ('0' + Math.floor(m / 60)).slice(-2) + ':' + ('0' + (m % 60)).slice(-2); }
function novaJornada(){ $('#j-titulo').text('Nova jornada'); $('#j-id').val(''); $('#j-nome').val(''); $('#j-carga').val('08:00'); $('#j-intervalo').val(60); $('#j-tol').val(10); $('#j-entrada').val(''); $('#j-saida').val(''); $('#j-situacao').val('1'); $('#j-dias input').each(function(){ this.checked = ['1','2','3','4','5'].indexOf(this.value)>=0; }); }
function editarJornada(j){ $('#j-titulo').text('Editar jornada'); $('#j-id').val(j.id); $('#j-nome').val(j.nome); $('#j-carga').val(minParaHHMM(j.carga_diaria_min)); $('#j-intervalo').val(j.intervalo_min); $('#j-tol').val(j.tolerancia_min); $('#j-entrada').val((j.hora_entrada||'').substring(0,5)); $('#j-saida').val((j.hora_saida||'').substring(0,5)); $('#j-situacao').val(j.situacao); var dias=(j.dias_semana||'').split(','); $('#j-dias input').each(function(){ this.checked = dias.indexOf(this.value)>=0; }); }
$(document).on('click','a[reg]',function(){ $('#j-del-id').val($(this).attr('reg')); $('#j-del-nome').text($(this).attr('data-nome') || ''); });
</script>
