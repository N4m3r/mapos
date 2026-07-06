<?php
// Partial: tabela de notas fiscais emitidas para uma OS.
// Espera receber $notas (array de linhas de notas_fiscais).
$notas = isset($notas) ? $notas : [];
$podeCancelar = $this->permission->checkPermission($this->session->userdata('permissao'), 'dNfe');
?>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Nº / Série</th>
                <th>Valor</th>
                <th>Emissão</th>
                <th>Status</th>
                <th>Chave / Retorno</th>
                <th style="text-align:center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($notas)) { ?>
                <tr>
                    <td colspan="7" style="text-align:center">Nenhuma nota fiscal emitida para esta OS.</td>
                </tr>
            <?php } else {
                foreach ($notas as $nota) {
                    $corStatus = match ($nota->status) {
                        'autorizada' => '#4d9c79',
                        'rejeitada' => '#f24c6f',
                        'cancelada' => '#CD0000',
                        'erro' => '#FF7F00',
                        default => '#AEB404',
                    };
                    $tipoLabel = $nota->tipo === 'nfe' ? 'NF-e (produtos)' : 'NFS-e (serviços)';
                    $tipoIcon = $nota->tipo === 'nfe' ? 'bx-box' : 'bx-receipt';
                    ?>
                    <tr>
                        <td><i class="bx <?php echo $tipoIcon; ?>"></i> <?php echo $tipoLabel; ?><?php echo $nota->ambiente == 2 ? ' <span class="badge" style="background:#AEB404">Homolog.</span>' : ''; ?></td>
                        <td><?php echo $nota->numero; ?> / <?php echo $nota->serie; ?></td>
                        <td>R$ <?php echo number_format($nota->valor_total, 2, ',', '.'); ?></td>
                        <td><?php echo $nota->data_emissao ? date('d/m/Y H:i', strtotime($nota->data_emissao)) : '-'; ?></td>
                        <td><span class="badge" style="background-color:<?php echo $corStatus; ?>;border-color:<?php echo $corStatus; ?>"><?php echo ucfirst($nota->status); ?></span></td>
                        <td style="font-size:11px;max-width:280px;word-break:break-all">
                            <?php echo $nota->chave ? html_escape($nota->chave) : ''; ?>
                            <?php echo $nota->motivo ? '<br><span style="color:#888">' . html_escape(mb_substr($nota->motivo, 0, 140)) . '</span>' : ''; ?>
                        </td>
                        <td style="text-align:center;white-space:nowrap">
                            <?php if (in_array($nota->status, ['autorizada', 'cancelada']) && !empty($nota->xml_path)) { ?>
                                <a href="<?php echo site_url('nfe/xml/' . $nota->idNota); ?>" class="btn-nwe6" title="Baixar XML"><i class="bx bx-code-alt bx-xs"></i></a>
                            <?php } ?>
                            <?php if ($nota->status === 'autorizada') { ?>
                                <a href="<?php echo site_url('nfe/danfe/' . $nota->idNota); ?>" target="_blank" class="btn-nwe6" title="Imprimir <?php echo $nota->tipo === 'nfe' ? 'DANFE' : 'DANFSe'; ?>"><i class="bx bx-printer bx-xs"></i></a>
                            <?php } ?>
                            <?php if ($nota->status === 'autorizada' && $podeCancelar) { ?>
                                <a href="<?php echo site_url('nfe/gerenciar?status=autorizada'); ?>" class="btn-nwe4" title="Cancelar (na tela de Notas Fiscais)"><i class="bx bx-x-circle bx-xs"></i></a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php }
            } ?>
        </tbody>
    </table>
</div>
<?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vNfe')) { ?>
    <div style="text-align:right;margin-top:6px">
        <a href="<?php echo site_url('nfe/gerenciar'); ?>" class="button btn btn-mini btn-inverse">
            <span class="button__icon"><i class='bx bx-list-ul'></i></span><span class="button__text2">Gerenciar Notas Fiscais</span>
        </a>
    </div>
<?php } ?>
