<?php
$this->load->view('rh/_subnav', ['ativo' => 'lancamentos']);
$lblTipo = ['hora_extra'=>'Horas extras','adicional'=>'Adicional','comissao'=>'Comissão','bonus'=>'Bônus','adiantamento'=>'Adiantamento','desconto'=>'Desconto','falta'=>'Falta','vale'=>'Vale','inss'=>'INSS','irrf'=>'IRRF','vale_transporte'=>'Vale-transporte','salario'=>'Salário base'];
$liberado = $holerite && ! empty($holerite->liberado_colaborador);
$temArquivo = $holerite && ! empty($holerite->arquivo_base64);
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
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="<?= site_url("rh/holeritePdf/{$colaborador->id}/{$competencia}") ?>" target="_blank" class="button btn btn-mini btn-inverse">
                <span class="button__icon"><i class='bx bx-printer'></i></span><span class="button__text2"> Pré-visualizar PDF</span></a>
            <a href="<?= site_url("rh/holeritePdf/{$colaborador->id}/{$competencia}?liberar=1") ?>" class="button btn btn-mini btn-success"
               onclick="return confirm('Gerar o holerite e liberar para o colaborador ver na área dele?')">
                <span class="button__icon"><i class='bx bx-send'></i></span><span class="button__text2"> Gerar e liberar p/ colaborador</span></a>
        </div>
    </div>

    <?php if ($liberado): ?>
        <div class="alert alert-success"><i class='bx bx-check-circle'></i> Holerite <strong>liberado</strong> para o colaborador
            <?= ! empty($holerite->liberado_em) ? ' em ' . date('d/m/Y H:i', strtotime($holerite->liberado_em)) : '' ?>.</div>
    <?php elseif ($temArquivo): ?>
        <div class="alert alert-warning"><i class='bx bx-hide'></i> Arquivo anexado, mas <strong>ainda não liberado</strong> para o colaborador.</div>
    <?php endif; ?>

    <div class="row-fluid">
        <div class="span6">
            <div class="widget-box">
                <div class="widget-title"><span class="icon"><i class="fas fa-list"></i></span><h5>Demonstrativo (lançamentos + CLT)</h5>
                    <a href="<?= site_url('rh/lancamentos?competencia='.$competencia.'&colaborador_id='.$colaborador->id) ?>" class="btn btn-mini" style="float:right;margin:8px">Editar lançamentos</a>
                </div>
                <div class="widget-content">
                    <?php
                    // Monta preview com dados da folha se disponíveis via resumo simples
                    if (empty($resumo['itens'])): ?>
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
                    <p style="font-size:12px;color:#9ca3af;margin-top:10px">
                        Descontos legais em <a href="<?= site_url('rh/descontosClt') ?>">Descontos CLT</a>.
                        Use <strong>Gerar e liberar</strong> para o colaborador ver o holerite.
                    </p>
                </div>
            </div>
        </div>

        <div class="span6">
            <div class="widget-box">
                <div class="widget-title"><span class="icon"><i class="fas fa-file-pdf"></i></span><h5>Holerite oficial (PDF)</h5></div>
                <div class="widget-content">
                    <?php if ($temArquivo): ?>
                        <div class="alert alert-success" style="margin-bottom:10px">
                            <i class='bx bx-check-circle'></i> Anexado: <strong><?= htmlspecialchars($holerite->arquivo_nome) ?></strong>
                            <a href="<?= site_url('rh/baixarHolerite/'.$colaborador->id.'/'.$competencia) ?>" target="_blank" style="margin-left:6px">abrir</a>
                        </div>
                        <form method="post" action="<?= site_url('rh/liberarHolerite') ?>" style="margin-bottom:12px;display:flex;gap:8px;align-items:center">
                            <input type="hidden" name="colaborador_id" value="<?= $colaborador->id ?>">
                            <input type="hidden" name="competencia" value="<?= $competencia ?>">
                            <?php if ($liberado): ?>
                                <input type="hidden" name="liberar" value="0">
                                <button class="button btn btn-mini btn-warning"><span class="button__text2">Ocultar do colaborador</span></button>
                            <?php else: ?>
                                <input type="hidden" name="liberar" value="1">
                                <button class="button btn btn-mini btn-success"><span class="button__text2">Liberar para o colaborador</span></button>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <p style="color:#888">Nenhum holerite oficial anexado. Gere pelo botão acima ou anexe o PDF da contabilidade.</p>
                    <?php endif; ?>

                    <form method="post" action="<?= site_url('rh/salvarHolerite') ?>" enctype="multipart/form-data">
                        <input type="hidden" name="colaborador_id" value="<?= $colaborador->id ?>">
                        <input type="hidden" name="competencia" value="<?= $competencia ?>">
                        <label>Arquivo PDF <?= $temArquivo ? '(enviar substitui o atual)' : '' ?></label>
                        <input type="file" name="arquivo" accept="application/pdf,image/*" class="span12">
                        <label style="margin-top:8px">Valor líquido (opcional)</label>
                        <input type="text" name="valor_liquido" class="span12" value="<?= $holerite && $holerite->valor_liquido ? number_format($holerite->valor_liquido,2,',','.') : '' ?>" placeholder="0,00">
                        <label style="margin-top:8px">Observação</label>
                        <textarea name="observacao" rows="2" class="span12"><?= $holerite ? htmlspecialchars($holerite->observacao) : '' ?></textarea>
                        <label style="font-weight:normal;margin-top:8px">
                            <input type="checkbox" name="liberar_colaborador" value="1" <?= $liberado ? 'checked' : 'checked' ?>>
                            Liberar para o colaborador ao salvar
                        </label>
                        <div style="margin-top:10px;display:flex;gap:8px">
                            <button class="button btn btn-success"><span class="button__icon"><i class='bx bx-save'></i></span><span class="button__text2"> Salvar</span></button>
                            <?php if ($holerite): ?>
                                <button formaction="<?= site_url('rh/excluirHolerite') ?>" class="button btn btn-danger" onclick="return confirm('Remover o holerite desta competência?')"><span class="button__text2">Remover</span></button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <a href="<?= site_url('rh/ficha/'.$colaborador->id) ?>" class="button btn btn-warning"><span class="button__text2">Voltar à ficha</span></a>
</div>
