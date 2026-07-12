<?php
$this->load->view('rh/_subnav', ['ativo' => 'colaboradores']);
$fmt = function ($min) { return $this->rh_calculo->minParaHoras($min); };
$lblTipo = ['entrada'=>'Entrada','saida'=>'Saída','inicio_intervalo'=>'Início int.','fim_intervalo'=>'Fim int.'];
$lblAus = ['ferias'=>'Férias','folga'=>'Folga','atestado'=>'Atestado','licenca'=>'Licença'];
$lblLan = ['hora_extra'=>'Hora extra','adicional'=>'Adicional','comissao'=>'Comissão','bonus'=>'Bônus','adiantamento'=>'Adiantamento','desconto'=>'Desconto','falta'=>'Falta','vale'=>'Vale'];
$ph = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="90" height="90"><rect width="90" height="90" rx="12" fill="#eef0f4"/><circle cx="45" cy="34" r="18" fill="#c3c9d4"/><path d="M16 82c0-16 13-24 29-24s29 8 29 24z" fill="#c3c9d4"/></svg>');
?>
<div class="new122">
    <!-- Cabeçalho do colaborador -->
    <div class="widget-box"><div class="widget-content" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <img src="<?= ! empty($colaborador->foto_base64) ? site_url('rh/fotoColaborador/'.$colaborador->id) : $ph ?>"
             style="width:90px;height:90px;border-radius:12px;object-fit:cover;border:2px solid #eef0f4">
        <div style="flex:1;min-width:200px">
            <h4 style="margin:0"><?= htmlspecialchars($colaborador->nome) ?>
                <?= $colaborador->situacao ? '<span style="font-size:12px;color:#16a34a">● Ativo</span>' : '<span style="font-size:12px;color:#888">● Inativo</span>' ?>
            </h4>
            <div style="color:#6b7280"><?= htmlspecialchars($colaborador->cargo ?: 'Sem cargo') ?><?= $colaborador->departamento ? ' · '.htmlspecialchars($colaborador->departamento) : '' ?></div>
            <div style="color:#9ca3af;font-size:13px;margin-top:2px">
                <?= $unidade ? '<i class="bx bx-buildings"></i> '.htmlspecialchars($unidade->nome).' &nbsp; ' : '' ?>
                <?= $jornada ? '<i class="bx bx-time"></i> '.htmlspecialchars($jornada->nome) : '' ?>
            </div>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="<?= site_url('rh/espelho/'.$colaborador->id.'/'.$competencia) ?>" class="button btn btn-mini btn-inverse"><span class="button__icon"><i class='bx bx-calendar-check'></i></span><span class="button__text2"> Espelho</span></a>
            <?php if ($pode_editar): ?>
                <a href="<?= site_url('rh/ajustarPonto/'.$colaborador->id.'/'.$competencia) ?>" class="button btn btn-mini btn-inverse"><span class="button__icon"><i class='bx bx-edit-alt'></i></span><span class="button__text2"> Ajustar ponto</span></a>
            <?php endif; ?>
            <?php if ($pode_financeiro): ?>
                <a href="<?= site_url('rh/holerite/'.$colaborador->id.'/'.$competencia) ?>" class="button btn btn-mini btn-inverse"><span class="button__icon"><i class='bx bx-receipt'></i></span><span class="button__text2"> Holerite</span></a>
            <?php endif; ?>
            <?php if ($pode_editar): ?>
                <a href="<?= site_url('rh/biometria/'.$colaborador->id) ?>" class="button btn btn-mini <?= $tem_biometria ? 'btn-success':'btn-warning' ?>"><span class="button__icon"><i class='bx bx-face'></i></span><span class="button__text2"> Facial <?= $tem_biometria?'✓':'' ?></span></a>
                <a href="<?= site_url('rh/editarColaborador/'.$colaborador->id) ?>" class="button btn btn-mini btn-primary"><span class="button__icon"><i class='bx bx-edit'></i></span><span class="button__text2"> Editar</span></a>
            <?php endif; ?>
        </div>
    </div></div>

    <!-- KPIs da competência -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
        <div class="widget-box" style="flex:1;min-width:130px;margin:0"><div class="widget-content" style="text-align:center;padding:12px">
            <div style="font-size:12px;color:#6b7280">Trabalhadas</div><div style="font-size:20px;font-weight:700"><?= $fmt($horas['minutos_trabalhados'] ?? 0) ?></div></div></div>
        <div class="widget-box" style="flex:1;min-width:130px;margin:0"><div class="widget-content" style="text-align:center;padding:12px">
            <div style="font-size:12px;color:#6b7280">Extra 50/100%</div><div style="font-size:20px;font-weight:700"><?= $fmt(($horas['minutos_extras_50'] ?? 0)+($horas['minutos_extras_100'] ?? 0)) ?></div></div></div>
        <div class="widget-box" style="flex:1;min-width:130px;margin:0"><div class="widget-content" style="text-align:center;padding:12px">
            <div style="font-size:12px;color:#6b7280">Faltas</div><div style="font-size:20px;font-weight:700;color:#dc2626"><?= $fmt($horas['minutos_faltas'] ?? 0) ?></div></div></div>
        <div class="widget-box" style="flex:1;min-width:130px;margin:0"><div class="widget-content" style="text-align:center;padding:12px">
            <div style="font-size:12px;color:#6b7280">Saldo banco</div><div style="font-size:20px;font-weight:700;color:<?= ($horas['saldo_banco_min']??0)<0?'#dc2626':'#16a34a' ?>"><?= $fmt($horas['saldo_banco_min'] ?? 0) ?></div></div></div>
    </div>

    <form method="get" style="margin-bottom:10px">
        <label style="display:inline">Competência: </label>
        <input type="month" name="competencia" value="<?= $competencia ?>" onchange="this.form.submit()">
    </form>

    <div class="row-fluid">
        <!-- Coluna esquerda: dados + últimas batidas -->
        <div class="span6">
            <div class="widget-box">
                <div class="widget-title"><span class="icon"><i class="fas fa-id-card"></i></span><h5>Dados cadastrais</h5></div>
                <div class="widget-content">
                    <table style="width:100%;font-size:13px">
                        <tr><td style="color:#6b7280;padding:3px 0">Contrato</td><td><?= htmlspecialchars($colaborador->tipo_contrato) ?></td></tr>
                        <tr><td style="color:#6b7280">Admissão</td><td><?= $colaborador->admissao ? date('d/m/Y', strtotime($colaborador->admissao)) : '-' ?></td></tr>
                        <tr><td style="color:#6b7280">CPF</td><td><?= htmlspecialchars($colaborador->cpf ?: '-') ?></td></tr>
                        <tr><td style="color:#6b7280">Celular</td><td><?= htmlspecialchars($colaborador->celular ?: '-') ?></td></tr>
                        <tr><td style="color:#6b7280">E-mail</td><td><?= htmlspecialchars($colaborador->email ?: '-') ?></td></tr>
                        <?php if ($pode_financeiro): ?>
                        <tr><td style="color:#6b7280">Salário base</td><td><?= $colaborador->salario_base ? 'R$ '.number_format($colaborador->salario_base,2,',','.') : '-' ?></td></tr>
                        <tr><td style="color:#6b7280">PIX</td><td><?= htmlspecialchars(($colaborador->pix_tipo?$colaborador->pix_tipo.': ':'').($colaborador->pix_chave ?: '-')) ?></td></tr>
                        <?php endif; ?>
                        <tr><td style="color:#6b7280">Acesso/login</td><td><?= $colaborador->usuarios_id ? 'Vinculado (bate ponto)' : '<span style="color:#d97706">sem acesso</span>' ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="widget-box">
                <div class="widget-title"><span class="icon"><i class="fas fa-clock"></i></span><h5>Últimas batidas</h5>
                    <?php if ($pode_editar): ?><a href="#modal-ponto" data-toggle="modal" role="button" class="btn btn-mini" style="float:right;margin:8px">+ Manual</a><?php endif; ?>
                </div>
                <div class="widget-content nopadding">
                    <table class="table table-bordered" style="margin:0">
                        <?php if (empty($ultimas_batidas)): ?>
                            <tr><td>Nenhuma batida.</td></tr>
                        <?php else: foreach ($ultimas_batidas as $b): ?>
                            <tr>
                                <td><?= $lblTipo[$b->tipo] ?? $b->tipo ?> <?= $b->origem==='manual' ? '<small style="color:#d97706">(manual)</small>':'' ?></td>
                                <td style="text-align:right"><?= date('d/m H:i', strtotime($b->data_hora)) ?>
                                    <?php if ($b->dentro_geofence === '0'): ?><i class='bx bx-error' style="color:#dc2626" title="Fora da área"></i><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Coluna direita: ausências, lançamentos, ocorrências -->
        <div class="span6">
            <div class="widget-box">
                <div class="widget-title"><span class="icon"><i class="fas fa-calendar-alt"></i></span><h5>Ausências</h5>
                    <?php if ($pode_editar): ?><a href="#modal-ausencia" data-toggle="modal" role="button" class="btn btn-mini" style="float:right;margin:8px">+ Registrar</a><?php endif; ?>
                </div>
                <div class="widget-content nopadding">
                    <table class="table table-bordered" style="margin:0">
                        <?php if (empty($ausencias)): ?><tr><td>Nenhuma.</td></tr>
                        <?php else: foreach ($ausencias as $a): ?>
                            <tr><td><?= $lblAus[$a->tipo] ?? $a->tipo ?><br><small style="color:#9ca3af"><?= date('d/m/Y', strtotime($a->data_inicio)) ?> a <?= date('d/m/Y', strtotime($a->data_fim)) ?></small></td>
                                <td style="text-align:right"><span class="rh-badge <?= $a->status ?>"><?= ucfirst($a->status) ?></span></td></tr>
                        <?php endforeach; endif; ?>
                    </table>
                </div>
            </div>

            <?php if ($pode_financeiro): ?>
            <div class="widget-box">
                <div class="widget-title"><span class="icon"><i class="fas fa-money-bill"></i></span><h5>Lançamentos (<?= date('m/Y', strtotime($competencia.'-01')) ?>)</h5>
                    <a href="<?= site_url('rh/lancamentos?competencia='.$competencia.'&colaborador_id='.$colaborador->id) ?>" class="btn btn-mini" style="float:right;margin:8px">Gerenciar</a>
                </div>
                <div class="widget-content nopadding">
                    <table class="table table-bordered" style="margin:0">
                        <?php if (empty($lancamentos)): ?><tr><td>Nenhum.</td></tr>
                        <?php else: foreach ($lancamentos as $l): ?>
                            <tr><td><?= $lblLan[$l->tipo] ?? $l->tipo ?> <?= $l->aprovado?'':'<small style="color:#d97706">(pendente)</small>' ?></td>
                                <td style="text-align:right;color:<?= $l->natureza==='desconto'?'#dc2626':'#16a34a' ?>"><?= $l->natureza==='desconto'?'-':'+' ?> R$ <?= number_format($l->valor,2,',','.') ?></td></tr>
                        <?php endforeach; endif; ?>
                        <tr style="background:#f9fafb"><td><strong>Líquido</strong></td><td style="text-align:right"><strong>R$ <?= number_format($resumo_financeiro['liquido'] ?? 0,2,',','.') ?></strong></td></tr>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="widget-box">
                <div class="widget-title"><span class="icon"><i class="fas fa-exclamation-circle"></i></span><h5>Ocorrências</h5></div>
                <div class="widget-content nopadding">
                    <table class="table table-bordered" style="margin:0">
                        <?php if (empty($ocorrencias)): ?><tr><td>Nenhuma.</td></tr>
                        <?php else: foreach ($ocorrencias as $o): ?>
                            <tr><td><?= htmlspecialchars($o->descricao) ?><br><small style="color:#9ca3af"><?= $o->data_referencia ? date('d/m/Y', strtotime($o->data_referencia)):'' ?></small></td>
                                <td style="text-align:right"><span class="rh-badge <?= $o->status ?>"><?= ucfirst($o->status) ?></span></td></tr>
                        <?php endforeach; endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <a href="<?= site_url('rh/colaboradores') ?>" class="button btn btn-warning"><span class="button__text2">Voltar</span></a>
</div>

<?php if ($pode_editar): ?>
<!-- Modal batida manual -->
<div id="modal-ponto" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/registrarPontoManual') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Batida manual</h5></div>
        <div class="modal-body">
            <input type="hidden" name="colaborador_id" value="<?= $colaborador->id ?>">
            <label>Data e hora</label><input type="datetime-local" name="data_hora" class="span12" required>
            <label>Tipo</label>
            <select name="tipo" class="span12">
                <option value="entrada">Entrada</option><option value="inicio_intervalo">Início do intervalo</option>
                <option value="fim_intervalo">Fim do intervalo</option><option value="saida">Saída</option>
            </select>
        </div>
        <div class="modal-footer"><button class="button btn btn-success"><span class="button__text2">Registrar</span></button></div>
    </form>
</div>
<!-- Modal ausência -->
<div id="modal-ausencia" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="<?= site_url('rh/salvarAusencia') ?>" method="post">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h5>Registrar ausência</h5></div>
        <div class="modal-body">
            <input type="hidden" name="colaborador_id" value="<?= $colaborador->id ?>">
            <label>Tipo</label>
            <select name="tipo" class="span12"><option value="ferias">Férias</option><option value="folga">Folga</option><option value="atestado">Atestado</option><option value="licenca">Licença</option></select>
            <div style="display:flex;gap:8px">
                <div style="flex:1"><label>Início</label><input type="date" name="data_inicio" class="span12" required></div>
                <div style="flex:1"><label>Fim</label><input type="date" name="data_fim" class="span12"></div>
            </div>
            <label>Motivo</label><input type="text" name="motivo" class="span12">
        </div>
        <div class="modal-footer"><button class="button btn btn-success"><span class="button__text2">Registrar (aprovada)</span></button></div>
    </form>
</div>
<?php endif; ?>
