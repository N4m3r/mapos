<link rel="stylesheet" href="<?= base_url() ?>assets/css/obras.css">
<?php $perm = function ($p) { return $this->permission->checkPermission($this->session->userdata('permissao'), $p); }; ?>

<?php $this->load->view('obras/_nav'); ?>

<div class="new122" style="margin-top:0">
    <div class="widget-title" style="margin:-6px 0 8px"><span class="icon"><i class="fas fa-users"></i></span><h5>Equipes de Projeto</h5></div>
    <?php if ($perm('cObraEquipe')): ?>
        <a href="#modal-equipe" data-toggle="modal" class="button btn btn-mini btn-success" style="max-width:160px" onclick="novaEquipe()"><span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Nova Equipe</span></a>
    <?php endif; ?>

    <div class="widget-box"><div class="widget-content nopadding">
        <table class="table table-bordered">
            <thead><tr><th>#</th><th>Equipe</th><th>Encarregado</th><th>Membros</th><th>Ativa</th><th>Ações</th></tr></thead>
            <tbody>
                <?php if (! $equipes) echo '<tr><td colspan="6">Nenhuma equipe cadastrada.</td></tr>'; ?>
                <?php foreach ($equipes as $q): ?>
                    <tr>
                        <td><?= $q->idEquipe ?></td>
                        <td><?= html_escape($q->nome) ?></td>
                        <td><?= html_escape($q->encarregado ?: '—') ?></td>
                        <td><?= (int) $q->total_membros ?></td>
                        <td><?= $q->ativo ? '<span class="label label-success">Sim</span>' : '<span class="label">Não</span>' ?></td>
                        <td>
                            <a href="<?= site_url('obraequipe/membros/' . $q->idEquipe) ?>" class="btn-nwe" title="Membros e alocação"><i class="bx bx-group bx-xs"></i></a>
                            <?php if ($perm('cObraEquipe')): ?>
                                <a href="#" class="btn-nwe3" title="Editar" onclick='editarEquipe(<?= json_encode($q, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>);return false;'><i class="bx bx-edit bx-xs"></i></a>
                                <a href="#" class="btn-nwe4" title="Excluir" onclick="excluirEquipe(<?= $q->idEquipe ?>);return false;"><i class="bx bx-trash-alt bx-xs"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>

<?php if ($perm('cObraEquipe')): ?>
<div id="modal-equipe" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('obraequipe/salvar') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Equipe</h5></div>
        <div class="modal-body">
            <input type="hidden" name="idEquipe" id="eq_id" value="">
            <div class="span12" style="margin-left:0"><label>Nome da equipe *</label><input type="text" class="span12" name="nome" id="eq_nome" required></div>
            <div class="span12" style="margin-left:0"><label>Encarregado</label>
                <select name="encarregado_id" id="eq_enc" class="span12">
                    <option value="">—</option>
                    <?php
                    $usuarios = $this->db->select('idUsuarios, nome')->where('situacao', 1)->order_by('nome', 'ASC')->get('usuarios')->result();
                    foreach ($usuarios as $u): ?>
                        <option value="<?= $u->idUsuarios ?>"><?= html_escape($u->nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="span12" style="margin-left:0"><label class="checkbox"><input type="checkbox" name="ativo" id="eq_ativo" value="1" checked> Ativa</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="button btn btn-warning" data-dismiss="modal"><span class="button__text2">Cancelar</span></button><button class="button btn btn-success"><span class="button__text2">Salvar</span></button></div>
    </form>
</div>
<form id="formExcluirEquipe" action="<?= site_url('obraequipe/excluir') ?>" method="post" style="display:none"><input type="hidden" name="idEquipe" id="del_equipe"></form>
<?php endif; ?>

<script>
    function novaEquipe() { $('#eq_id').val(''); $('#eq_nome').val(''); $('#eq_enc').val(''); $('#eq_ativo').prop('checked', true); }
    function editarEquipe(q) {
        $('#eq_id').val(q.idEquipe); $('#eq_nome').val(q.nome); $('#eq_enc').val(q.encarregado_id || '');
        $('#eq_ativo').prop('checked', q.ativo == 1);
        $('#modal-equipe').modal('show');
    }
    function excluirEquipe(id) { if (confirm('Excluir esta equipe? Membros e alocações serão removidos.')) { $('#del_equipe').val(id); $('#formExcluirEquipe').submit(); } }
</script>
