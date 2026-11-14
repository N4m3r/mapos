<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Formulario de perfil de permissao, usado por adicionar e editar.
 *
 * Antes eram duas views de 63 KB e 82 KB, quase identicas, e a copia de "editar"
 * deixava de salvar as permissoes de Contrato que a de "adicionar" tinha. Agora
 * as duas telas desenham o mesmo catalogo (helpers/permissoes_helper.php) e o
 * controller grava exatamente as chaves que estao nele.
 *
 * Variaveis esperadas:
 *  - $secoes       catalogo de permissoes_catalogo()
 *  - $salvas       permissoes_normalizar() do perfil (array chave => 0|1)
 *  - $nome         nome do perfil
 *  - $situacao     1 ativo, 0 inativo
 *  - $idPermissao  ausente ao criar
 *  - $acao         URL do formulario
 *  - $titulo       titulo da tela
 *  - $subtitulo    linha de apoio
 *  - $tokenName, $tokenHash  CSRF
 */
$e = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$totalChaves = 0;
foreach ($secoes as $secao) {
    $totalChaves += count($secao['chaves']);
}
$marcadas = 0;
foreach ($salvas as $valor) {
    $marcadas += (int) $valor;
}
?>
<div class="new122 perfil-tela">
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-lock-alt"></i> <?= $e($titulo) ?></h1>
            <p class="ui-subtitulo"><?= $e($subtitulo) ?></p>
        </div>
        <div class="ui-acoes">
            <a href="<?= site_url('permissoes') ?>" class="button btn btn-warning">
                <span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span>
            </a>
        </div>
    </div>

    <form action="<?= $e($acao) ?>" id="formPermissao" method="post">
        <input type="hidden" name="<?= $e($tokenName) ?>" value="<?= $e($tokenHash) ?>">
        <?php if (! empty($idPermissao)) { ?>
            <input type="hidden" name="idPermissao" value="<?= (int) $idPermissao ?>">
        <?php } ?>

        <div class="widget-box">
            <div class="widget-content">
                <div class="perfil-topo">
                    <div class="ui-grade">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do perfil<span class="required">*</span></label>
                            <div class="">
                                <input class="form-control" type="text" id="nome" name="nome" value="<?= $e($nome) ?>" maxlength="80" required>
                                <span class="form-text">Ex.: Técnico externo, Financeiro, Atendimento.</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="situacao" class="form-label">Situação</label>
                            <div class="">
                                <select class="form-select" name="situacao" id="situacao">
                                    <option value="1" <?= (int) $situacao === 1 ? 'selected' : '' ?>>Ativo</option>
                                    <option value="0" <?= (int) $situacao === 0 ? 'selected' : '' ?>>Inativo</option>
                                </select>
                                <span class="form-text">Perfil inativo não pode ser atribuído a um usuário novo.</span>
                            </div>
                        </div>
                    </div>

                    <div class="perfil-busca">
                        <label for="filtro-permissoes">Buscar permissão</label>
                        <input class="form-control" type="search" id="filtro-permissoes" placeholder="Digite parte do nome (ex.: excluir, nota, folha)" autocomplete="off">
                        <span class="perfil-contagem" id="perfil-contagem" aria-live="polite">
                            <?= (int) $marcadas ?> de <?= (int) $totalChaves ?> permissões
                        </span>
                    </div>
                </div>

                <div class="perfil-acoes-rapidas">
                    <button type="button" class="btn btn-sm" data-marcar="todas">
                        <i class="bx bx-check-square"></i> Marcar todas
                    </button>
                    <button type="button" class="btn btn-sm" data-marcar="visiveis">
                        <i class="bx bx-filter"></i> Marcar as visíveis
                    </button>
                    <button type="button" class="btn btn-sm" data-marcar="nenhuma">
                        <i class="bx bx-square"></i> Desmarcar todas
                    </button>
                </div>

                <div class="perfil-secoes">
                    <?php foreach ($secoes as $chaveSecao => $secao) { ?>
                        <?php
                        $quantas = 0;
                        foreach ($secao['chaves'] as $item) {
                            $quantas += (int) ($salvas[$item['chave']] ?? 0);
                        }
                        ?>
                        <section class="perfil-secao" data-secao="<?= $e($chaveSecao) ?>">
                            <header class="perfil-secao-topo">
                                <div class="perfil-secao-titulo">
                                    <i class="bx <?= $e($secao['icone']) ?>" aria-hidden="true"></i>
                                    <div>
                                        <h4><?= $e($secao['titulo']) ?></h4>
                                        <p><?= $e($secao['descricao']) ?></p>
                                    </div>
                                </div>
                                <span class="ui-selo" data-secao-contagem><?= (int) $quantas ?>/<?= count($secao['chaves']) ?></span>
                            </header>

                            <div class="perfil-chaves">
                                <?php foreach ($secao['chaves'] as $item) { ?>
                                    <?php $marcada = (int) ($salvas[$item['chave']] ?? 0) === 1; ?>
                                    <label class="perfil-chave" data-busca="<?= $e(mb_strtolower($secao['titulo'] . ' ' . $item['rotulo'] . ' ' . $item['descricao'] . ' ' . $item['chave'])) ?>">
                                        <input type="checkbox" name="<?= $e($item['chave']) ?>" value="1" class="marcar form-check-input" <?= $marcada ? 'checked' : '' ?>>
                                        <span class="perfil-chave-texto">
                                            <strong><?= $e($item['rotulo']) ?></strong>
                                            <?php if ($item['descricao'] !== '') { ?>
                                                <small><?= $e($item['descricao']) ?></small>
                                            <?php } ?>
                                            <code><?= $e($item['chave']) ?></code>
                                        </span>
                                    </label>
                                <?php } ?>
                            </div>

                            <p class="perfil-vazio" hidden>Nenhuma permissão desta seção corresponde à busca.</p>
                        </section>
                    <?php } ?>
                </div>

                <p class="perfil-sem-resultado" id="perfil-sem-resultado" hidden>
                    Nenhuma permissão corresponde à busca.
                </p>

                <div class="ui-rodape-form">
                    <a href="<?= site_url('permissoes') ?>" class="button btn btn-warning">
                        <span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span>
                    </a>
                    <button type="submit" class="button btn btn-primary">
                        <span class="button__icon"><i class="bx bx-save"></i></span><span class="button__text2">Salvar perfil</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .perfil-topo { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-start; justify-content: space-between; }
    .perfil-topo .ui-grade { flex: 1 1 420px; }
    .perfil-busca { flex: 0 1 280px; display: flex; flex-direction: column; gap: 4px; }
    .perfil-busca input { width: 100%; }
    .perfil-contagem { font-size: 12px; color: var(--ui-texto-2); }
    .perfil-acoes-rapidas { display: flex; flex-wrap: wrap; gap: 8px; margin: 4px 0 16px; }
    .perfil-secoes { display: flex; flex-direction: column; gap: 14px; }
    .perfil-secao { border: 1px solid var(--ui-borda); border-radius: var(--ui-raio); overflow: hidden; }
    .perfil-secao-topo { display: flex; gap: 12px; align-items: flex-start; justify-content: space-between; padding: 12px 14px; background: var(--ui-suave); border-bottom: 1px solid var(--ui-borda); }
    .perfil-secao-titulo { display: flex; gap: 10px; align-items: flex-start; }
    .perfil-secao-titulo i { font-size: 20px; margin-top: 2px; opacity: .85; }
    .perfil-secao-titulo h4 { margin: 0; font-size: 15px; font-weight: 700; }
    .perfil-secao-titulo p { margin: 2px 0 0; font-size: 12px; color: var(--ui-texto-2); }
    .perfil-chaves { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 8px; padding: 12px 14px; }
    .perfil-chave { display: flex; gap: 10px; align-items: flex-start; margin: 0; padding: 8px 10px; border: 1px solid transparent; border-radius: var(--ui-raio-p); cursor: pointer; }
    .perfil-chave:hover { background: var(--ui-suave); border-color: var(--ui-borda); }
    .perfil-chave:focus-within { outline: 2px solid var(--ui-primaria); outline-offset: 1px; }
    .perfil-chave input { margin: 2px 0 0; flex: 0 0 auto; }
    .perfil-chave-texto { display: flex; flex-direction: column; gap: 1px; line-height: 1.3; }
    .perfil-chave-texto strong { font-size: 13px; font-weight: 600; }
    .perfil-chave-texto small { font-size: 12px; color: var(--ui-texto-2); }
    .perfil-chave-texto code { font-size: 10px; opacity: .55; }
    .perfil-chave[hidden], .perfil-secao[hidden] { display: none !important; }
    .perfil-vazio, .perfil-sem-resultado { margin: 0; padding: 10px 14px; font-size: 13px; color: var(--ui-texto-2); }
    @media (max-width: 767px) {
        .perfil-chaves { grid-template-columns: 1fr; }
    }
</style>

<script nonce="<?= csp_nonce() ?>">
    (function () {
        'use strict';

        var form = document.getElementById('formPermissao');
        var filtro = document.getElementById('filtro-permissoes');
        var contagem = document.getElementById('perfil-contagem');
        var semResultado = document.getElementById('perfil-sem-resultado');
        if (!form) { return; }

        var caixas = Array.prototype.slice.call(form.querySelectorAll('input.marcar'));

        function normalizar(texto) {
            return (texto || '').toString().toLowerCase()
                .normalize ? (texto || '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
                : (texto || '').toString().toLowerCase();
        }

        function atualizarContagem() {
            var marcadas = 0;
            caixas.forEach(function (c) { if (c.checked) { marcadas++; } });
            if (contagem) {
                contagem.textContent = marcadas + ' de ' + caixas.length + ' permissões';
            }

            Array.prototype.forEach.call(form.querySelectorAll('.perfil-secao'), function (secao) {
                var selo = secao.querySelector('[data-secao-contagem]');
                if (!selo) { return; }
                var doSecao = secao.querySelectorAll('input.marcar');
                var n = 0;
                Array.prototype.forEach.call(doSecao, function (c) { if (c.checked) { n++; } });
                selo.textContent = n + '/' + doSecao.length;
            });
        }

        function aplicarFiltro() {
            var termo = normalizar(filtro ? filtro.value.trim() : '');
            var visiveis = 0;

            Array.prototype.forEach.call(form.querySelectorAll('.perfil-secao'), function (secao) {
                var temVisivel = false;
                Array.prototype.forEach.call(secao.querySelectorAll('.perfil-chave'), function (linha) {
                    var casa = termo === '' || normalizar(linha.getAttribute('data-busca')).indexOf(termo) !== -1;
                    linha.hidden = !casa;
                    if (casa) { temVisivel = true; }
                });
                secao.hidden = !temVisivel;
                var vazio = secao.querySelector('.perfil-vazio');
                if (vazio) { vazio.hidden = temVisivel; }
                if (temVisivel) { visiveis++; }
            });

            if (semResultado) { semResultado.hidden = visiveis !== 0; }
        }

        form.addEventListener('change', function (evento) {
            if (evento.target && evento.target.classList.contains('marcar')) {
                atualizarContagem();
            }
        });

        if (filtro) { filtro.addEventListener('input', aplicarFiltro); }

        Array.prototype.forEach.call(form.querySelectorAll('[data-marcar]'), function (botao) {
            botao.addEventListener('click', function () {
                var modo = botao.getAttribute('data-marcar');
                caixas.forEach(function (c) {
                    var visivel = !c.closest('.perfil-chave').hidden;
                    if (modo === 'todas') { c.checked = true; }
                    if (modo === 'nenhuma') { c.checked = false; }
                    if (modo === 'visiveis' && visivel) { c.checked = true; }
                });
                atualizarContagem();
            });
        });

        atualizarContagem();
    })();
</script>
