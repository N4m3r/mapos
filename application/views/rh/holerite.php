<?php
$this->load->view('rh/_subnav', ['ativo' => 'lancamentos']);
$lblTipo = ['hora_extra'=>'Horas extras','adicional'=>'Adicional','comissao'=>'Comissão','bonus'=>'Bônus','adiantamento'=>'Adiantamento','desconto'=>'Desconto','falta'=>'Falta','vale'=>'Vale'];
?>
<div class="new122">
    <div class="widget-title" style="margin:0 0 10px">
        <span class="icon"><i class="fas fa-receipt"></i></span>
        <h5>Holerite — <?= htmlspecialchars($colaborador->nome) ?></h5>
    </div>

    <div style="margin-bottom:10px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;align-items:center">
        <div>
            <label style="display:inline">Competência: </label>
            <input type="month" value="<?= $competencia ?>" onchange="window.location='<?= site_url('rh/holerite/'.$colaborador->id) ?>/'+this.value">
        </div>
        <a href="<?= site_url("rh/holeritePdf/{$colaborador->id}/{$competencia}") ?>" target="_blank" class="button btn btn-mini btn-success">
            <span class="button__icon"><i class='bx bx-printer'></i></span><span class="button__text2"> Gerar holerite (PDF)</span></a>
    </div>

    <div class="row-fluid">
        <!-- Demonstrativo gerencial (dos lançamentos) -->
        <div class="span6">
            <div class="widget-box">
                <div class="widget-title"><span class="icon"><i class="fas fa-list"></i></span><h5>Demonstrativo (lançamentos)</h5>
                    <a href="<?= site_url('rh/lancamentos?competencia='.$competencia.'&colaborador_id='.$colaborador->id) ?>" class="btn btn-mini" style="float:right;margin:8px">Editar lançamentos</a>
                </div>
                <div class="widget-content">
                    <?php if (empty($resumo['itens'])): ?>
                        <p style="color:#888">Nenhum lançamento aprovado nesta competência.</p>
                    <?php else: ?>
                        <table style="width:100%;font-size:13px">
                            <?php foreach ($resumo['itens'] as $it): ?>
                                <tr>
                                    <td style="padding:3px 0"><?= htmlspecialchars($it->descricao ?: ($lblTipo[$it->tipo] ?? $it->tipo)) ?></td>
                                    <td style="text-align:right;color:<?= $it->natureza==='desconto'?'#dc2626':'#16a34a' ?>"><?= $it->natureza==='desconto'?'-':'+' ?> R$ <?= number_format($it->valor,2,',','.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        <hr style="margin:8px 0">
                        <div style="display:flex;justify-content:space-between"><span>Proventos</span><strong style="color:#16a34a">R$ <?= number_format($resumo['proventos'],2,',','.') ?></strong></div>
                        <div style="display:flex;justify-content:space-between"><span>Descontos</span><strong style="color:#dc2626">R$ <?= number_format($resumo['descontos'],2,',','.') ?></strong></div>
                        <div style="display:flex;justify-content:space-between;font-size:16px;margin-top:4px"><span><strong>Líquido</strong></span><strong>R$ <?= number_format($resumo['liquido'],2,',','.') ?></strong></div>
                    <?php endif; ?>
                    <p style="font-size:12px;color:#9ca3af;margin-top:10px">Cálculo gerencial. Encargos (INSS/IRRF/FGTS) e o holerite oficial ficam com a contabilidade — anexe o PDF ao lado.</p>
                </div>
            </div>
        </div>

        <!-- Holerite oficial (PDF do contador) -->
        <div class="span6">
            <div class="widget-box">
                <div class="widget-title"><span class="icon"><i class="fas fa-file-pdf"></i></span><h5>Holerite oficial (PDF)</h5></div>
                <div class="widget-content">
                    <?php if ($holerite && ! empty($holerite->arquivo_base64)): ?>
                        <div class="alert alert-success" style="margin-bottom:10px">
                            <i class='bx bx-check-circle'></i> Anexado: <strong><?= htmlspecialchars($holerite->arquivo_nome) ?></strong>
                            <a href="<?= site_url('rh/baixarHolerite/'.$colaborador->id.'/'.$competencia) ?>" target="_blank" style="margin-left:6px">abrir</a>
                        </div>
                    <?php else: ?>
                        <p style="color:#888">Nenhum holerite oficial anexado para esta competência.</p>
                    <?php endif; ?>

                    <form method="post" action="<?= site_url('rh/salvarHolerite') ?>" enctype="multipart/form-data">
                        <input type="hidden" name="colaborador_id" value="<?= $colaborador->id ?>">
                        <input type="hidden" name="competencia" value="<?= $competencia ?>">
                        <label>Arquivo PDF <?= $holerite && $holerite->arquivo_base64 ? '(enviar substitui o atual)' : '' ?></label>
                        <input type="file" name="arquivo" accept="application/pdf,image/*" class="span12">
                        <label style="margin-top:8px">Valor líquido (opcional)</label>
                        <input type="text" name="valor_liquido" class="span12" value="<?= $holerite && $holerite->valor_liquido ? number_format($holerite->valor_liquido,2,',','.') : '' ?>" placeholder="0,00">
                        <label style="margin-top:8px">Observação</label>
                        <textarea name="observacao" rows="2" class="span12"><?= $holerite ? htmlspecialchars($holerite->observacao) : '' ?></textarea>
                        <div style="margin-top:10px;display:flex;gap:8px">
                            <button class="button btn btn-success"><span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2"> Salvar</span></button>
                            <?php if ($holerite): ?>
                                <button formaction="<?= site_url('rh/excluirHolerite') ?>" class="button btn btn-danger" onclick="return confirm('Remover o holerite desta competência?')"><span class="button__text2">Remover</span></button>
                            <?php endif; ?>
                        </div>
                    </form>
                    <p style="font-size:12px;color:#9ca3af;margin-top:10px">O colaborador baixa este arquivo na área dele, em Holerite.</p>
                </div>
            </div>
        </div>
    </div>
    <a href="<?= site_url('rh/ficha/'.$colaborador->id) ?>" class="button btn btn-warning"><span class="button__text2">Voltar à ficha</span></a>
</div>
