<div class="new122">
    <?php $this->load->view('rh/_subnav', ['ativo' => 'painel']); ?>
    <div class="widget-title" style="margin: 0 0 10px">
        <span class="icon"><i class="fas fa-users"></i></span>
        <h5>RH — Painel</h5>
    </div>

    <div class="row-fluid">
        <div class="span3">
            <div class="widget-box"><div class="widget-content" style="text-align:center;padding:18px">
                <div style="font-size:30px;font-weight:700;color:#2D335B"><?= (int) $total_colaboradores ?></div>
                <div>Colaboradores ativos</div>
            </div></div>
        </div>
        <div class="span3">
            <div class="widget-box"><div class="widget-content" style="text-align:center;padding:18px">
                <div style="font-size:30px;font-weight:700;color:#16a34a"><?= (int) $presentes_hoje ?></div>
                <div>Presentes agora</div>
            </div></div>
        </div>
        <div class="span3">
            <div class="widget-box"><div class="widget-content" style="text-align:center;padding:18px">
                <div style="font-size:30px;font-weight:700;color:#d97706"><?= (int) $pendencias ?></div>
                <div>Pendências de aprovação</div>
            </div></div>
        </div>
        <div class="span3">
            <div class="widget-box"><div class="widget-content" style="text-align:center;padding:18px">
                <a href="<?= site_url('rh/colaboradores') ?>" class="button btn btn-success" style="margin-top:8px">
                    <span class="button__text2">Gerenciar</span></a>
            </div></div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span8">
            <div class="widget-box">
                <div class="widget-title"><span class="icon"><i class="fas fa-clock"></i></span><h5>Últimas batidas</h5></div>
                <div class="widget-content nopadding">
                    <table class="table table-bordered">
                        <thead><tr><th>Colaborador</th><th>Tipo</th><th>Data/Hora</th><th>Local</th></tr></thead>
                        <tbody>
                        <?php $lbl=['entrada'=>'Entrada','saida'=>'Saída','inicio_intervalo'=>'Início int.','fim_intervalo'=>'Fim int.'];
                        if (empty($ultimos_registros)): ?>
                            <tr><td colspan="4">Nenhuma batida registrada.</td></tr>
                        <?php else: foreach ($ultimos_registros as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r->nome_colaborador ?: '#'.$r->colaborador_id) ?></td>
                                <td><?= $lbl[$r->tipo] ?? $r->tipo ?></td>
                                <td><?= date('d/m H:i', strtotime($r->data_hora)) ?></td>
                                <td><?php if ($r->dentro_geofence === '1') echo '<span style="color:#16a34a">Na área</span>';
                                    elseif ($r->dentro_geofence === '0') echo '<span style="color:#dc2626">Fora ('.(int)$r->distancia_metros.'m)</span>';
                                    else echo '—'; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="widget-box">
                <div class="widget-title"><span class="icon"><i class="fas fa-birthday-cake"></i></span><h5>Aniversariantes do mês</h5></div>
                <div class="widget-content">
                    <?php if (empty($aniversariantes)): ?>
                        <p>Nenhum aniversariante.</p>
                    <?php else: foreach ($aniversariantes as $a): ?>
                        <div style="padding:5px 0;border-bottom:1px solid #eee">
                            <i class='bx bx-gift'></i> <?= htmlspecialchars($a->nome) ?>
                            <span style="float:right;color:#888"><?= date('d/m', strtotime($a->data_nascimento)) ?></span>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <div class="widget-box">
                <div class="widget-content" style="padding:12px">
                    <a href="<?= site_url('rh/ocorrencias?status=pendente') ?>" class="btn btn-block"><i class='bx bx-error-circle'></i> Ocorrências</a>
                    <a href="<?= site_url('rh/ausencias?status=pendente') ?>" class="btn btn-block" style="margin-top:6px"><i class='bx bx-calendar-star'></i> Folgas/Férias</a>
                    <a href="<?= site_url('rh/unidades') ?>" class="btn btn-block" style="margin-top:6px"><i class='bx bx-buildings'></i> Unidades</a>
                    <a href="<?= site_url('rh/jornadas') ?>" class="btn btn-block" style="margin-top:6px"><i class='bx bx-time'></i> Jornadas</a>
                </div>
            </div>
        </div>
    </div>
</div>
