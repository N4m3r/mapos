<?php
$this->load->view('tecnico/_topo', [
    'titulo'       => 'Nova Atividade',
    'header_icone' => 'bx-plus-circle',
    'header_sub'   => 'Atividade não programada',
    'voltar_url'   => site_url('tecnico'),
]);
?>

<style>
    .atv-field { margin-bottom: 16px; }
    .atv-field > label { display:block; font-size:13px; font-weight:700; color:#55596e; margin-bottom:6px; }
    .atv-input {
        width:100%; box-sizing:border-box; padding:12px 14px; font-size:15px;
        border:1.5px solid #e2e5f0; border-radius:12px; background:#fff; color:var(--tec-ink);
        -webkit-appearance:none; appearance:none;
    }
    .atv-input:focus { outline:none; border-color:var(--tec-grad-1); }
    textarea.atv-input { min-height:70px; resize:vertical; }
    .atv-ac { position:relative; }
    .atv-ac-list {
        position:absolute; z-index:50; left:0; right:0; top:calc(100% + 4px);
        background:#fff; border:1px solid #e2e5f0; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,.10);
        max-height:240px; overflow-y:auto; display:none;
    }
    .atv-ac-list div { padding:11px 14px; font-size:14px; cursor:pointer; border-bottom:1px solid #f2f3f8; }
    .atv-ac-list div:last-child { border-bottom:0; }
    .atv-ac-list div:hover, .atv-ac-list div.active { background:#f4f5fb; }
    .atv-chip {
        display:flex; align-items:center; gap:8px; background:#eef0f7; border-radius:12px;
        padding:10px 12px; margin-bottom:8px;
    }
    .atv-chip .nome { flex:1; font-size:14px; color:var(--tec-ink); }
    .atv-stepper { display:flex; align-items:center; gap:0; background:#fff; border:1.5px solid #e2e5f0; border-radius:10px; overflow:hidden; }
    .atv-stepper button {
        width:34px; height:36px; border:0; background:#fff; color:var(--tec-grad-1);
        font-size:20px; line-height:1; cursor:pointer; -webkit-appearance:none; appearance:none;
        display:flex; align-items:center; justify-content:center;
    }
    .atv-stepper button:active { background:#eef0f7; }
    .atv-chip input.qtd {
        width:44px; height:36px; text-align:center; padding:0; border:0; border-left:1.5px solid #e2e5f0;
        border-right:1.5px solid #e2e5f0; font-size:15px; background:#fff; -moz-appearance:textfield;
    }
    .atv-chip input.qtd::-webkit-outer-spin-button,
    .atv-chip input.qtd::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
    .atv-chip .rm { color:var(--tec-danger); font-size:20px; cursor:pointer; line-height:1; }
    .atv-cliente-sel {
        display:flex; align-items:center; justify-content:space-between; gap:8px;
        background:var(--tec-info-bg); color:var(--tec-info); border-radius:12px; padding:12px 14px; font-weight:600;
    }
    .atv-cliente-sel .rm { color:var(--tec-danger); cursor:pointer; font-size:20px; }
    .atv-hint { font-size:12px; color:var(--tec-muted); margin-top:4px; }
    .atv-empty { font-size:13px; color:var(--tec-muted); font-style:italic; }
</style>

<div class="tec-container">

    <?php if ($this->session->flashdata('error')): ?>
        <div class="info-card" style="border-left:4px solid var(--tec-danger); color:var(--tec-danger);">
            <i class='bx bx-error-circle'></i> <?= $this->session->flashdata('error') ?>
        </div>
    <?php endif; ?>

    <form id="form-atividade" method="post" action="<?= site_url('tecnico/salvar_atividade') ?>">

        <!-- Cliente -->
        <div class="info-card">
            <h3><i class='bx bx-user'></i> Cliente</h3>
            <div class="atv-field atv-ac" id="ac-cliente-wrap">
                <input type="text" class="atv-input" id="ac-cliente" placeholder="Buscar cliente por nome, telefone ou documento..." autocomplete="off">
                <div class="atv-ac-list" id="ac-cliente-list"></div>
                <div class="atv-hint">Digite ao menos 2 letras para buscar.</div>
            </div>
            <div id="cliente-selecionado" style="display:none;">
                <div class="atv-cliente-sel">
                    <span><i class='bx bx-user-check'></i> <span id="cliente-nome"></span></span>
                    <i class='bx bx-x rm' id="cliente-remover" title="Remover"></i>
                </div>
            </div>
            <input type="hidden" name="clientes_id" id="clientes_id" value="">
        </div>

        <!-- Descrição / Defeito -->
        <div class="info-card">
            <h3><i class='bx bx-detail'></i> Descrição da Atividade</h3>
            <div class="atv-field">
                <label>O que será feito</label>
                <textarea class="atv-input" name="descricaoProduto" placeholder="Descreva o serviço a ser realizado..."></textarea>
            </div>
            <div class="atv-field">
                <label>Defeito / Motivo (opcional)</label>
                <textarea class="atv-input" name="defeito" placeholder="Defeito relatado ou motivo do atendimento..."></textarea>
            </div>
            <div class="atv-field" style="margin-bottom:0;">
                <label>Observações (opcional)</label>
                <textarea class="atv-input" name="observacoes" placeholder="Anotações internas..."></textarea>
            </div>
        </div>

        <!-- Serviços -->
        <div class="info-card">
            <h3><i class='bx bx-wrench'></i> Serviços</h3>
            <div class="atv-field atv-ac" id="ac-servico-wrap">
                <input type="text" class="atv-input" id="ac-servico" placeholder="Buscar serviço..." autocomplete="off">
                <div class="atv-ac-list" id="ac-servico-list"></div>
            </div>
            <div id="servicos-lista"><div class="atv-empty" id="servicos-vazio">Nenhum serviço adicionado.</div></div>
        </div>

        <!-- Produtos -->
        <div class="info-card">
            <h3><i class='bx bx-package'></i> Produtos</h3>
            <div class="atv-field atv-ac" id="ac-produto-wrap">
                <input type="text" class="atv-input" id="ac-produto" placeholder="Buscar produto..." autocomplete="off">
                <div class="atv-ac-list" id="ac-produto-list"></div>
            </div>
            <div id="produtos-lista"><div class="atv-empty" id="produtos-vazio">Nenhum produto adicionado.</div></div>
        </div>

        <button type="submit" class="btn-tec success block lg" id="btn-salvar-atividade" style="margin-bottom:8px;">
            <i class='bx bx-check'></i> Abrir Atividade
        </button>
        <a href="<?= site_url('tecnico') ?>" class="btn-tec neutral block">Cancelar</a>

        <div style="height:70px;"></div>
    </form>
</div>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => '', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>

<script src="<?= base_url('assets/js/csrf.js?v=3') ?>"></script>
<script>
(function () {
    var BASE = '<?= site_url('tecnico') ?>/';

    // ---- Autocomplete genérico -------------------------------------
    function setupAutocomplete(opts) {
        var input = document.getElementById(opts.inputId);
        var list = document.getElementById(opts.listId);
        var timer = null;

        function render(items) {
            list.innerHTML = '';
            if (!items.length) { list.style.display = 'none'; return; }
            items.forEach(function (it) {
                var d = document.createElement('div');
                d.textContent = it.label;
                d.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    opts.onPick(it);
                    input.value = '';
                    list.style.display = 'none';
                });
                list.appendChild(d);
            });
            list.style.display = 'block';
        }

        input.addEventListener('input', function () {
            var q = input.value.trim();
            if (timer) clearTimeout(timer);
            if (q.length < 2) { list.style.display = 'none'; return; }
            timer = setTimeout(function () {
                $.getJSON(BASE + opts.endpoint, { q: q }, function (data) {
                    render(data || []);
                });
            }, 250);
        });

        document.addEventListener('click', function (e) {
            if (!list.contains(e.target) && e.target !== input) list.style.display = 'none';
        });
    }

    // ---- Cliente ---------------------------------------------------
    setupAutocomplete({
        inputId: 'ac-cliente', listId: 'ac-cliente-list', endpoint: 'buscar_clientes',
        onPick: function (it) {
            document.getElementById('clientes_id').value = it.id;
            document.getElementById('cliente-nome').textContent = it.nome + (it.documento ? ' · ' + it.documento : '');
            document.getElementById('cliente-selecionado').style.display = 'block';
            document.getElementById('ac-cliente-wrap').style.display = 'none';
        }
    });
    document.getElementById('cliente-remover').addEventListener('click', function () {
        document.getElementById('clientes_id').value = '';
        document.getElementById('cliente-selecionado').style.display = 'none';
        document.getElementById('ac-cliente-wrap').style.display = 'block';
    });

    // ---- Itens (serviços/produtos) ---------------------------------
    function setupItens(cfg) {
        var container = document.getElementById(cfg.listaId);
        var vazio = document.getElementById(cfg.vazioId);
        var selecionados = {};

        function refreshVazio() {
            vazio.style.display = Object.keys(selecionados).length ? 'none' : 'block';
        }

        function add(it) {
            if (selecionados[it.id]) return; // evita duplicado
            selecionados[it.id] = true;

            var row = document.createElement('div');
            row.className = 'atv-chip';
            row.innerHTML =
                '<span class="nome"></span>' +
                '<input type="hidden" name="' + cfg.campo + '[' + it.id + '][id]" value="' + it.id + '">' +
                '<div class="atv-stepper">' +
                    '<button type="button" class="qtd-menos" aria-label="Diminuir">−</button>' +
                    '<input type="number" class="qtd" inputmode="numeric" min="1" value="1" name="' + cfg.campo + '[' + it.id + '][quantidade]">' +
                    '<button type="button" class="qtd-mais" aria-label="Aumentar">+</button>' +
                '</div>' +
                '<i class="bx bx-trash rm" title="Remover"></i>';
            row.querySelector('.nome').textContent = it.nome;

            var inputQtd = row.querySelector('.qtd');
            function setQtd(v) {
                v = parseInt(v, 10);
                if (isNaN(v) || v < 1) { v = 1; }
                inputQtd.value = v;
            }
            row.querySelector('.qtd-menos').addEventListener('click', function () {
                setQtd((parseInt(inputQtd.value, 10) || 1) - 1);
            });
            row.querySelector('.qtd-mais').addEventListener('click', function () {
                setQtd((parseInt(inputQtd.value, 10) || 1) + 1);
            });
            // Sanitiza quando o técnico digita direto e ao perder o foco.
            inputQtd.addEventListener('blur', function () { setQtd(inputQtd.value); });

            row.querySelector('.rm').addEventListener('click', function () {
                row.remove();
                delete selecionados[it.id];
                refreshVazio();
            });
            container.appendChild(row);
            refreshVazio();
        }

        setupAutocomplete({
            inputId: cfg.inputId, listId: cfg.acListId, endpoint: cfg.endpoint, onPick: add
        });
    }

    setupItens({
        inputId: 'ac-servico', acListId: 'ac-servico-list', endpoint: 'buscar_servicos',
        listaId: 'servicos-lista', vazioId: 'servicos-vazio', campo: 'servicos'
    });
    setupItens({
        inputId: 'ac-produto', acListId: 'ac-produto-list', endpoint: 'buscar_produtos',
        listaId: 'produtos-lista', vazioId: 'produtos-vazio', campo: 'produtos'
    });

    // ---- Validação no submit ---------------------------------------
    document.getElementById('form-atividade').addEventListener('submit', function (e) {
        if (!document.getElementById('clientes_id').value) {
            e.preventDefault();
            alert('Selecione um cliente para abrir a atividade.');
            return false;
        }
        document.getElementById('btn-salvar-atividade').setAttribute('disabled', 'disabled');
    });
})();
</script>
</body>
</html>
