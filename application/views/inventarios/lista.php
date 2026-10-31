<div class="widget-box">
    <div class="widget-content nopadding">
        <div class="ui-cabecalho" style="padding: 16px 16px 0; margin-bottom: 0">
            <div>
                <h1 class="ui-titulo"><i class="bx bx-list-check"></i> Inventários de estoque</h1>
                <p class="ui-subtitulo">Conte o estoque real e ajuste as diferenças de forma registrada.</p>
            </div>
        </div>
        <div style="padding:12px 14px 0; display:flex; gap:12px; flex-wrap:wrap; justify-content:space-between; align-items:flex-start">
            <p style="max-width:640px; color:#555; margin:0">
                O inventário copia o estoque atual de todos os produtos. Digite as quantidades contadas (pode salvar e continuar depois)
                e finalize: o estoque dos produtos contados passa a ser o valor contado e cada diferença fica no histórico de movimentações.
                Produtos deixados em branco não são alterados.
            </p>
            <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eProduto')) { ?>
                <form action="<?= site_url('inventarios/abrir') ?>" method="post" style="display:flex; gap:8px; align-items:center; margin:0">
                    <input type="text" name="descricao" placeholder="Descrição (ex.: Inventário mensal)" aria-label="Descrição do inventário" style="margin:0; width:240px">
                    <button class="button btn btn-mini btn-success" type="submit"><span class="button__icon"><i class="bx bx-plus-circle"></i></span><span class="button__text2">Novo inventário</span></button>
                </form>
            <?php } ?>
        </div>

        <table class="table table-bordered" style="margin-top:12px">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th>Descrição</th>
                    <th style="width:110px; text-align:center">Situação</th>
                    <th style="width:120px; text-align:center">Contados</th>
                    <th style="width:140px">Aberto em</th>
                    <th style="width:140px">Finalizado em</th>
                    <th style="width:140px">Responsável</th>
                    <th style="width:70px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inventarios)) { ?>
                    <tr><td colspan="8"><div class="ui-vazio"><i class="bx bx-list-check"></i><strong>Nenhum inventário ainda</strong>Informe uma descrição e clique em "Novo inventário" para começar.</div></td></tr>
                <?php }
                $cores = ['aberto' => 'badge-warning', 'finalizado' => 'badge-success', 'cancelado' => ''];
                foreach ($inventarios as $i) { ?>
                    <tr>
                        <td><?= (int) $i->id ?></td>
                        <td><?= html_escape($i->descricao) ?></td>
                        <td style="text-align:center"><span class="badge <?= $cores[$i->status] ?? '' ?>"><?= html_escape(ucfirst($i->status)) ?></span></td>
                        <td style="text-align:center"><?= (int) $i->contados ?> / <?= (int) $i->itens ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($i->criado_em)) ?></td>
                        <td><?= $i->finalizado_em ? date('d/m/Y H:i', strtotime($i->finalizado_em)) : '-' ?></td>
                        <td><?= html_escape($i->usuario ?: '-') ?></td>
                        <td style="text-align:center"><a href="<?= site_url('inventarios/contar/' . (int) $i->id) ?>" class="btn btn-mini btn-info" title="Abrir" aria-label="Abrir inventário"><i class="bx bx-show"></i></a></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
