<div class="span12" style="margin-left: 0">
    <div class="widget-box">
        <div class="widget-title">
            <span class="icon">
                <i class="fas fa-clipboard-check"></i>
            </span>
            <h5>Relatório de Atendimento</h5>
        </div>

        <div class="widget-content nopadding tab-content">
            <p style="padding: 12px 14px 0; color:#6b7191; margin:0;">
                Ordens de serviço com atendimento registrado. Clique em <strong>Ver relatório</strong>
                para visualizar o histórico, as fotos, as assinaturas e os formulários preenchidos.
            </p>
            <table class="table table-bordered" style="margin-top: 10px">
                <thead>
                    <tr>
                        <th style="width:70px">OS</th>
                        <th>Descrição</th>
                        <th style="width:110px">Abertura</th>
                        <th style="width:120px">Status</th>
                        <th style="width:130px; text-align:center">Relatório</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)) { ?>
                        <tr>
                            <td colspan="5">Nenhum atendimento registrado ainda.</td>
                        </tr>
                    <?php } else {
                        foreach ($results as $r) {
                            $abertura = $r->dataInicial ? date('d/m/Y', strtotime($r->dataInicial)) : '-';
                            $descricao = trim(strip_tags((string) $r->descricaoProduto));
                            if (mb_strlen($descricao) > 60) { $descricao = mb_substr($descricao, 0, 60) . '…'; }
                            echo '<tr>';
                            echo '<td>#' . sprintf('%04d', $r->idOs) . '</td>';
                            echo '<td>' . htmlspecialchars($descricao !== '' ? $descricao : '—') . '</td>';
                            echo '<td>' . $abertura . '</td>';
                            echo '<td><span class="badge" style="background-color:#2D335B;border-color:#2D335B">' . htmlspecialchars($r->status) . '</span></td>';
                            echo '<td style="text-align:center">
                                    <a href="' . base_url() . 'index.php/mine/relatorioAtendimento/' . $r->idOs . '" class="btn btn-mini btn-inverse" title="Ver relatório de atendimento" target="_blank">
                                        <i class="bx bx-printer"></i> Ver relatório
                                    </a>
                                  </td>';
                            echo '</tr>';
                        }
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
