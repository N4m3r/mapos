<?php
$this->load->view('colaborador/_topo', [
    'titulo' => 'Justificar / Corrigir',
    'header_icone' => 'bx-error-circle',
    'voltar_url' => site_url('colaborador'),
]);
$tipos = ['correcao_ponto'=>'Correção de ponto','justificativa_falta'=>'Justificativa de falta','abono'=>'Pedido de abono'];
$csrf_n = $this->security->get_csrf_token_name();
$csrf_h = $this->security->get_csrf_hash();
?>
<div class="ponto-wrap">
    <?php if ($var = $this->session->flashdata('success')): ?><div class="tec-alert success"><?= $var ?></div><?php endif; ?>
    <?php if ($var = $this->session->flashdata('error')): ?><div class="tec-alert error"><?= $var ?></div><?php endif; ?>

    <form method="post" action="<?= site_url('colaborador/solicitarOcorrencia') ?>" enctype="multipart/form-data"
          class="rh-card" style="margin-bottom:16px">
        <input type="hidden" name="<?= $csrf_n ?>" value="<?= $csrf_h ?>">
        <h4 style="margin:0 0 10px"><i class='bx bx-plus-circle'></i> Nova solicitação</h4>
        <?php $refData = $this->input->get('ref'); ?>
        <label>Tipo</label>
        <select name="tipo" id="oc-tipo" class="span12" style="width:100%">
            <?php foreach ($tipos as $k=>$v): ?>
                <option value="<?= $k ?>" <?= ($refData && $k==='correcao_ponto') ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
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
    function toggle(){ bloco.style.display = (sel.value === 'correcao_ponto') ? 'block' : 'none'; }
    sel.addEventListener('change', toggle);
    toggle();
})();
</script>
<?php $this->load->view('colaborador/_nav', ['nav_ativo' => 'solicitacoes', 'pode_bater_ponto' => $pode_bater_ponto]); ?>
</body>
</html>
