<div class="span12" style="margin-left: 0">
    <div class="widget-box">
        <div class="widget-title">
            <span class="icon">
                <i class="fas fa-file-invoice"></i>
            </span>
            <h5>Notas Fiscais</h5>
        </div>

        <div class="widget-content nopadding tab-content">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nº</th>
                        <th>Tipo</th>
                        <th>Cliente / CNPJ</th>
                        <th>Emissão</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)) { ?>
                        <tr>
                            <td colspan="7">Nenhuma nota fiscal encontrada.</td>
                        </tr>
                    <?php } else {
                        foreach ($results as $r) {
                            $tipo = strtolower($r->tipo) === 'nfe' ? 'NF-e' : 'NFS-e';
                            $emissao = $r->data_emissao ? date('d/m/Y', strtotime($r->data_emissao)) : ($r->data_autorizacao ? date('d/m/Y', strtotime($r->data_autorizacao)) : '-');
                            $doc = trim((string) $r->documento);
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($r->numero) . '</td>';
                            echo '<td>' . $tipo . '</td>';
                            echo '<td>' . htmlspecialchars($r->nomeCliente) . ($doc !== '' ? '<br><small>' . htmlspecialchars($doc) . '</small>' : '') . '</td>';
                            echo '<td>' . $emissao . '</td>';
                            echo '<td>R$ ' . number_format((float) $r->valor_total, 2, ',', '.') . '</td>';
                            echo '<td><span class="badge" style="background-color:#256;border-color:#256">' . htmlspecialchars($r->status) . '</span></td>';
                            echo '<td>
                                    <a href="' . base_url() . 'index.php/mine/notaDanfe/' . $r->idNota . '" class="btn-nwe" title="Visualizar/Imprimir DANFE" target="_blank"><i class="bx bx-file"></i></a>
                                    <a href="' . base_url() . 'index.php/mine/notaXml/' . $r->idNota . '" class="btn-nwe3" title="Baixar XML"><i class="bx bx-download"></i></a>
                                  </td>';
                            echo '</tr>';
                        }
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
