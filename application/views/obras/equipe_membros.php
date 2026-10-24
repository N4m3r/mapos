<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css">
<link rel="stylesheet" href="<?= base_url() ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script src="<?= base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<?php
$perm = function ($p) { return $this->permission->checkPermission($this->session->userdata('permissao'), $p); };
$data_br = function ($x) { return (! empty($x) && $x !== '0000-00-00') ? date('d/m/Y', strtotime($x)) : '—'; };
$podeGerir = $perm('cObraEquipe');
?>

<?php $this->load->view('obras/_nav'); ?>

<div class="widget-box">
    <div class="widget-title"><span class="icon"><i class="fas fa-users"></i></span><h5>Equipe: <?= html_escape($equipe->nome) ?></h5></div>
    <div class="widget-content">
        <a href="<?= site_url('obraequipe') ?>" class="button btn btn-mini btn-warning"><span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span></a>
    </div>
</div>

<div class="row-fluid">
    <!-- Membros -->
    <div class="span6" style="margin-left:0">
        <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-group'></i> Membros</h5></div>
            <div class="widget-content nopadding">
                <table class="table table-bordered">
                    <thead><tr><th>Nome</th><th>Função</th><th>Diária</th><?php if ($podeGerir): ?><th></th><?php endif; ?></tr></thead>
                    <tbody>
                        <?php if (! $membros) echo '<tr><td colspan="4">Sem membros.</td></tr>'; ?>
                        <?php foreach ($membros as $m): ?>
                            <tr>
                                <td><?= html_escape($m->nome_usuario ?: $m->nome) ?></td>
                                <td><?= html_escape($m->funcao) ?></td>
                                <td>R$ <?= number_format((float) $m->valor_diaria, 2, ',', '.') ?></td>
                                <?php if ($podeGerir): ?>
                                    <td><a href="#" class="btn-nwe4" title="Remover" onclick="removerMembro(<?= $m->idMembro ?>);return false;"><i class="bx bx-trash-alt bx-xs"></i></a></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($podeGerir): ?>
                    <form action="<?= site_url('obraequipe/salvarMembro') ?>" method="post" style="padding:8px">
                        <input type="hidden" name="equipe_id" value="<?= $equipe->idEquipe ?>">
                        <div class="span6" style="margin-left:0"><label>Colaborador (cadastrado)</label>
                            <select name="colaborador_id" class="span12">
                                <option value="">— usar nome livre —</option>
                                <?php foreach ($usuarios as $u): ?><option value="<?= $u->idUsuarios ?>"><?= html_escape($u->nome) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="span6"><label>ou Nome</label><input type="text" class="span12" name="nome"></div>
                        <div class="span4" style="margin-left:0"><label>Função</label><input type="text" class="span12" name="funcao" placeholder="Técnico, Instalador..."></div>
                        <div class="span4"><label>Diária (R$)</label><input type="number" step="0.01" class="span12" name="valor_diaria" value="0"></div>
                        <div class="span4"><label>Valor/hora</label><input type="number" step="0.01" class="span12" name="valor_hora" value="0"></div>
                        <div class="span12" style="margin-left:0;margin-top:6px"><button class="button btn btn-mini btn-success"><span class="button__icon"><i class='bx bx-plus'></i></span><span class="button__text2">Adicionar membro</span></button></div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Alocações -->
    <div class="span6">
        <div class="widget-box"><div class="widget-title"><h5><i class='bx bx-network-chart'></i> Alocação em projetos</h5></div>
            <div class="widget-content nopadding">
                <table class="table table-bordered">
                    <thead><tr><th>Projeto</th><th>Início</th><th>Fim</th><?php if ($podeGerir): ?><th></th><?php endif; ?></tr></thead>
                    <tbody>
                        <?php if (! $alocacoes) echo '<tr><td colspan="4">Não alocada.</td></tr>'; ?>
                        <?php foreach ($alocacoes as $a): ?>
                            <tr>
                                <td><a href="<?= site_url('obras/visualizar/' . $a->obra_id) ?>"><?= html_escape($a->obra_nome) ?></a></td>
                                <td><?= $data_br($a->data_inicio) ?></td>
                                <td><?= $data_br($a->data_fim) ?></td>
                                <?php if ($podeGerir): ?>
                                    <td><a href="#" class="btn-nwe4" title="Remover" onclick="desalocar(<?= $a->idAlocacao ?>);return false;"><i class="bx bx-trash-alt bx-xs"></i></a></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($podeGerir): ?>
                    <form action="<?= site_url('obraequipe/alocar') ?>" method="post" style="padding:8px">
                        <input type="hidden" name="equipe_id" value="<?= $equipe->idEquipe ?>">
                        <div class="span5" style="margin-left:0"><label>Projeto</label>
                            <select name="obra_id" class="span12" required>
                                <option value="">— Selecione —</option>
                                <?php foreach ($obras as $o): ?><option value="<?= $o->idObra ?>"><?= html_escape($o->nome) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="span3"><label>Início</label><input type="text" class="span12 datepicker" name="data_inicio"></div>
                        <div class="span3"><label>Fim</label><input type="text" class="span12 datepicker" name="data_fim"></div>
                        <div class="span1" style="padding-top:22px"><button class="button btn btn-mini btn-success" title="Alocar"><span class="button__icon"><i class='bx bx-plus'></i></span></button></div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($podeGerir): ?>
<form id="formRemMembro" action="<?= site_url('obraequipe/excluirMembro') ?>" method="post" style="display:none"><input type="hidden" name="equipe_id" value="<?= $equipe->idEquipe ?>"><input type="hidden" name="idMembro" id="rem_membro"></form>
<form id="formDesalocar" action="<?= site_url('obraequipe/desalocar') ?>" method="post" style="display:none"><input type="hidden" name="equipe_id" value="<?= $equipe->idEquipe ?>"><input type="hidden" name="idAlocacao" id="rem_aloc"></form>
<?php endif; ?>

<script>
    $(function () { $('.datepicker').datepicker({ dateFormat: 'dd/mm/yy' }); });
    function removerMembro(id) { if (confirm('Remover membro?')) { $('#rem_membro').val(id); $('#formRemMembro').submit(); } }
    function desalocar(id) { if (confirm('Remover alocação?')) { $('#rem_aloc').val(id); $('#formDesalocar').submit(); } }
</script>
