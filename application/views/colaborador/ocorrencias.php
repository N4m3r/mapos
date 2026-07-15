<?php
$this->load->view('colaborador/_topo', [
    'titulo' => 'Justificar / Corrigir',
    'header_icone' => 'bx-error-circle',
    'voltar_url' => site_url('colaborador'),
]);
$tipos = ['correcao_ponto'=>'Correção de ponto','justificativa_falta'=>'Justificativa de falta','abono'=>'Pedido de abono'];
<<<<<<< HEAD
$csrf_n = $this->security->get_csrf_token_name();
$csrf_h = $this->security->get_csrf_hash();
=======
$lblBat = [
    'entrada' => 'Entrada',
    'saida' => 'Saída',
    'inicio_intervalo' => 'Início do intervalo',
    'fim_intervalo' => 'Fim do intervalo',
];
$csrf_n = $this->security->get_csrf_token_name();
$csrf_h = $this->security->get_csrf_hash();
$refData = $ref_data ?? ($this->input->get('ref') ?: '');
$batidasRef = $batidas_ref ?? [];
>>>>>>> 43f6f5a (correcao sintaxe)
?>
<div class="ponto-wrap">
    <?php if ($var = $this->session->flashdata('success')): ?><div class="tec-alert success"><?= $var ?></div><?php endif; ?>
    <?php if ($var = $this->session->flashdata('error')): ?><div class="tec-alert error"><?= $var ?></div><?php endif; ?>

    <form method="post" action="<?= site_url('colaborador/solicitarOcorrencia') ?>" enctype="multipart/form-data"
          class="rh-card" style="margin-bottom:16px">
        <input type="hidden" name="<?= $csrf_n ?>" value="<?= $csrf_h ?>">
        <h4 style="margin:0 0 10px"><i class='bx bx-plus-circle'></i> Nova solicitação</h4>
<<<<<<< HEAD
        <?php $refData = $this->input->get('ref'); ?>
=======

>>>>>>> 43f6f5a (correcao sintaxe)
        <label>Tipo</label>
        <select name="tipo" id="oc-tipo" class="span12" style="width:100%">
            <?php foreach ($tipos as $k=>$v): ?>
                <option value="<?= $k ?>" <?= ($refData && $k==='correcao_ponto') ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
<<<<<<< HEAD
        <label style="margin-top:8px">Data de referência</label>
        <input type="date" name="data_referencia" class="span12" style="width:100%" value="<?= htmlspecialchars($refData ?: '') ?>">

        <div id="bloco-correcao" style="display:none">
            <div style="display:flex;gap:8px;margin-top:8px">
                <div style="flex:1"><label>Batida</label>
                    <select name="correcao_tipo" class="span12" style="width:100%">
                        <option value="entrada">Entrada</option>
                        <option value="inicio_intervalo">Início do intervalo</option>
                        <option value="fim_intervalo">Fim do intervalo</option>
                        <option value="saida">Saída</option>
                    </select>
                </div>
                <div style="flex:1"><label>Data e hora desejada</label>
                    <input type="datetime-local" name="correcao_data_hora" class="span12" style="width:100%">
=======

        <label style="margin-top:8px">Data de referência</label>
        <input type="date" name="data_referencia" id="oc-data" class="span12" style="width:100%" value="<?= htmlspecialchars($refData) ?>">

        <label style="margin-top:8px">O que deseja justificar / corrigir?</label>
        <select name="justificar_tipo" id="oc-justificar" class="span12" style="width:100%">
            <option value="">— selecione —</option>
            <?php foreach ($lblBat as $k=>$v): ?>
                <option value="<?= $k ?>"><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <small style="color:#9ca3af">Escolha se é entrada, intervalo ou saída.</small>

        <label style="margin-top:8px">Batida registrada neste dia <small>(opcional)</small></label>
        <select name="registro_id" id="oc-registro" class="span12" style="width:100%">
            <option value="">— nenhuma / batida faltante —</option>
            <?php foreach ($batidasRef as $b): ?>
                <option value="<?= $b->id ?>" data-tipo="<?= htmlspecialchars($b->tipo) ?>">
                    <?= ($lblBat[$b->tipo] ?? $b->tipo) ?> · <?= date('H:i', strtotime($b->data_hora)) ?>
                    <?php if (! empty($b->latitude)): ?> · 📍<?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small style="color:#9ca3af">Selecione a batida que quer justificar (carrega ao escolher a data).</small>

        <div id="bloco-correcao" style="display:none">
            <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
                <div style="flex:1;min-width:140px"><label>Tipo da batida desejada</label>
                    <select name="correcao_tipo" id="oc-correcao-tipo" class="span12" style="width:100%">
                        <?php foreach ($lblBat as $k=>$v): ?>
                            <option value="<?= $k ?>"><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex:1;min-width:160px"><label>Data e hora desejada</label>
                    <input type="datetime-local" name="correcao_data_hora" id="oc-correcao-dh" class="span12" style="width:100%">
>>>>>>> 43f6f5a (correcao sintaxe)
                </div>
            </div>
            <small style="color:#9ca3af">Se o RH aprovar, essa batida é lançada/corrigida automaticamente no seu ponto.</small>
        </div>

        <label style="margin-top:8px">Descrição</label>
        <textarea name="descricao" rows="3" class="span12" style="width:100%" placeholder="Explique o motivo..." required></textarea>
        <label style="margin-top:8px">Anexo (atestado/comprovante) — opcional</label>
        <input type="file" name="anexo" accept="image/*,application/pdf" class="span12" style="width:100%">
        <button type="submit" class="btn-bater" style="margin-top:12px"><i class='bx bx-send'></i> Enviar ao RH</button>
    </form>

    <h4 style="color:#374151"><i class='bx bx-history'></i> Minhas solicitações</h4>
    <?php if (empty($ocorrencias)): ?>
        <div style="color:#9ca3af;font-size:13px">Nenhuma solicitação ainda.</div>
    <?php else: foreach ($ocorrencias as $o): ?>
        <div class="rh-list-item">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <strong><?= $tipos[$o->tipo] ?? $o->tipo ?></strong>
                <span class="rh-badge <?= $o->status ?>"><?= ucfirst($o->status) ?></span>
            </div>
            <?php if ($o->data_referencia): ?><small style="color:#6b7280">Ref: <?= date('d/m/Y', strtotime($o->data_referencia)) ?></small><?php endif; ?>
<<<<<<< HEAD
=======
            <?php if (! empty($o->correcao_tipo)): ?>
                <small style="color:#6b7280;display:block">Batida: <?= $lblBat[$o->correcao_tipo] ?? $o->correcao_tipo ?>
                    <?= ! empty($o->correcao_data_hora) ? ' → ' . date('d/m/Y H:i', strtotime($o->correcao_data_hora)) : '' ?>
                </small>
            <?php endif; ?>
>>>>>>> 43f6f5a (correcao sintaxe)
            <div style="font-size:13px;margin-top:4px"><?= nl2br(htmlspecialchars($o->descricao)) ?></div>
            <?php if (! empty($o->anexo_base64)): ?>
                <a href="<?= site_url('colaborador/anexo/ocorrencia/'.$o->id) ?>" target="_blank" style="font-size:12px">Ver anexo</a>
            <?php endif; ?>
            <?php if ($o->resposta): ?><div style="font-size:12px;color:#065f46;margin-top:4px">RH: <?= htmlspecialchars($o->resposta) ?></div><?php endif; ?>
        </div>
    <?php endforeach; endif; ?>
</div>
<script>
(function(){
    var sel = document.getElementById('oc-tipo');
    var bloco = document.getElementById('bloco-correcao');
<<<<<<< HEAD
    function toggle(){ bloco.style.display = (sel.value === 'correcao_ponto') ? 'block' : 'none'; }
    sel.addEventListener('change', toggle);
    toggle();
=======
    var dataInp = document.getElementById('oc-data');
    var regSel = document.getElementById('oc-registro');
    var justSel = document.getElementById('oc-justificar');
    var corrTipo = document.getElementById('oc-correcao-tipo');
    var lblBat = <?= json_encode($lblBat, JSON_UNESCAPED_UNICODE) ?>;
    var urlBatidas = '<?= site_url('colaborador/batidasDoDia') ?>';

    function toggle(){ bloco.style.display = (sel.value === 'correcao_ponto') ? 'block' : 'none'; }
    sel.addEventListener('change', toggle);
    toggle();

    justSel.addEventListener('change', function(){
        if (justSel.value && corrTipo) corrTipo.value = justSel.value;
    });

    regSel.addEventListener('change', function(){
        var opt = regSel.options[regSel.selectedIndex];
        if (!opt || !opt.value) return;
        var t = opt.getAttribute('data-tipo');
        if (t) {
            justSel.value = t;
            if (corrTipo) corrTipo.value = t;
        }
    });

    function carregarBatidas(){
        var d = dataInp.value;
        if (!d) return;
        fetch(urlBatidas + '?data=' + encodeURIComponent(d), { credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(lista){
                regSel.innerHTML = '<option value="">— nenhuma / batida faltante —</option>';
                (lista || []).forEach(function(b){
                    var o = document.createElement('option');
                    o.value = b.id;
                    o.setAttribute('data-tipo', b.tipo);
                    o.textContent = (b.label || b.tipo) + ' · ' + b.hora + (b.latitude ? ' · 📍' : '');
                    regSel.appendChild(o);
                });
            })
            .catch(function(){});
    }
    dataInp.addEventListener('change', carregarBatidas);
    if (dataInp.value && regSel.options.length <= 1) carregarBatidas();
>>>>>>> 43f6f5a (correcao sintaxe)
})();
</script>
<?php $this->load->view('colaborador/_nav', ['nav_ativo' => 'solicitacoes', 'pode_bater_ponto' => $pode_bater_ponto]); ?>
</body>
</html>
