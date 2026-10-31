<div class="ui-cabecalho">
    <div>
        <h1 class="ui-titulo"><i class="bx bx-envelope"></i> Fila de e-mails</h1>
        <p class="ui-subtitulo">E-mails aguardando envio ou já processados. Remova da fila o que não deve sair.</p>
    </div>
</div>
<div class="widget-box">
    <div class="widget-content nopadding tab-content ui-tabela-rolagem">
        <table id="tabela" class="table table-bordered ">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Para</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$results) { ?>
                    <tr>
                        <td colspan="5"><div class="ui-vazio"><i class="bx bx-envelope"></i><strong>Nenhum e-mail na fila</strong>Os e-mails automáticos aparecem aqui quando forem gerados.</div></td>
                    </tr>
                <?php } ?>

                <?php foreach ($results as $r) {
                    $status = [
                                        'pending' => '<span class="ui-selo">Pendente</span>',
                                        'sending' => '<span class="ui-selo info">Enviando</span>',
                                        'sent' => '<span class="ui-selo sucesso">Enviado</span>',
                                        'failed' => '<span class="ui-selo alerta">Falhou</span>',
                                    ];
                    echo '<tr>';
                    echo '<td>' . $r->id . '</td>';
                    echo '<td>' . $r->to . '</td>';
                    echo '<td>' . $status[$r->status] . '</td>';
                    echo '<td>' . date('d/m/Y H:i:s', strtotime($r->date)) . '</td>';
                    echo '<td>';
                    echo '<a href="#modal-excluir" role="button" data-toggle="modal" email="' . $r->id . '" class="btn-nwe4" title="Remover da fila" aria-label="Remover da fila"><i class="bx bx-trash-alt"></i></a>  ';
                    echo '</td>';
                    echo '</tr>';
                } ?>

            </tbody>
        </table>
    </div>
</div>
<?php echo $this->pagination->create_links(); ?>
<!-- Modal -->
<div id="modal-excluir" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <form action="<?= site_url('mapos/excluirEmail') ?>" method="post">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h5 id="myModalLabel">Excluir Email da Lista</h5>
        </div>
        <div class="modal-body">
            <input type="hidden" id="idEmail" name="id" value="" />
            <h5 style="text-align: center">Deseja realmente excluir este email da lista de envio?</h5>
        </div>
        <div class="modal-footer" style="display:flex;justify-content: center">
          <button class="button btn btn-warning" data-dismiss="modal" aria-hidden="true"><span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span></button>
          <button class="button btn btn-danger"><span class="button__icon"><i class='bx bx-trash'></i></span> <span class="button__text2">Excluir</span></button>
        </div>
    </form>
</div>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        $(document).on('click', 'a', function(event) {
            var email = $(this).attr('email');
            $('#idEmail').val(email);
        });
    });
</script>
