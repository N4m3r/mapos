<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Dados da empresa (emitente).
 *
 * A tela tinha dois modais quase iguais — um para cadastrar, outro para
 * editar — com rotulos vazios nos campos (so o placeholder dizia o que era
 * cada um) e nenhuma indicacao do que faltava para emitir nota. Agora ha um
 * formulario unico, em secoes, com a conferencia fiscal ao lado e as acoes
 * (trocar logo, limpar logo) no mesmo lugar.
 *
 * Variaveis: $dados (objeto|null), $checklist, $resumoChecklist,
 * $configFiscalExiste, $tokenName, $tokenHash.
 */
$e = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$dados = $dados ?? null;
$checklist = $checklist ?? [];
$resumo = $resumoChecklist ?? ['prontos' => 0, 'total' => 0];
$id = ($dados && isset($dados->id)) ? (int) $dados->id : 0;
$v = static function ($campo, $padrao = '') use ($dados) {
    return $dados && isset($dados->$campo) ? (string) $dados->$campo : $padrao;
};
$acao = $id > 0 ? site_url('mapos/editarEmitente') : site_url('mapos/cadastrarEmitente');
$logo = $v('url_logo');
$temLogo = $logo !== '' && is_file(convertUrlToUploadsPath($logo));
$tudoPronto = $resumo['total'] > 0 && $resumo['prontos'] === $resumo['total'];
?>
<div class="new122">
    <div class="ui-cabecalho">
        <div>
            <h1 class="ui-titulo"><i class="bx bx-building"></i> Dados do emitente</h1>
            <p class="ui-subtitulo">Empresa que aparece no cabeçalho das OS, orçamentos, vendas, recibos e notas fiscais.</p>
        </div>
        <div class="ui-acoes">
            <?php if (! empty($configFiscalExiste)) { ?>
                <a href="<?= site_url('nfe/configuracoes') ?>" class="button btn btn-dark">
                    <span class="button__icon"><i class="bx bx-receipt"></i></span><span class="button__text2">Configurações fiscais</span>
                </a>
            <?php } ?>
        </div>
    </div>

    <div class="ui-kpis">
        <div class="ui-kpi <?= $tudoPronto ? 'sucesso' : 'alerta' ?>">
            <div class="ui-kpi-rotulo">Pronto para emitir</div>
            <div class="ui-kpi-valor"><?= (int) $resumo['prontos'] ?>/<?= (int) $resumo['total'] ?></div>
            <div class="ui-kpi-extra"><?= $tudoPronto ? 'Todos os itens conferidos.' : 'Veja o que falta ao lado do formulário.' ?></div>
        </div>
        <div class="ui-kpi">
            <div class="ui-kpi-rotulo">Identificação</div>
            <div class="ui-kpi-valor" style="font-size:18px"><?= $e($v('nome_fantasia') !== '' ? $v('nome_fantasia') : $v('nome', 'sem nome')) ?></div>
            <div class="ui-kpi-extra"><?= $e($v('cnpj', 'CNPJ não informado')) ?></div>
        </div>
        <div class="ui-kpi">
            <div class="ui-kpi-rotulo">Logo</div>
            <div class="ui-kpi-valor" style="font-size:18px"><?= $temLogo ? 'Cadastrada' : 'Não enviada' ?></div>
            <div class="ui-kpi-extra"><?= $temLogo ? 'Aparece nos documentos.' : 'Os documentos saem sem logo.' ?></div>
        </div>
    </div>

    <?php if ($id === 0) { ?>
        <div class="ui-dica alerta">
            <i class="bx bx-error"></i>
            <div>Nenhum dado cadastrado. Enquanto o emitente não existir, as telas de impressão mostram um aviso no lugar do cabeçalho.</div>
        </div>
    <?php } ?>

    <div class="emitente-grade">
        <form action="<?= $acao ?>" id="formEmitente" method="post" enctype="multipart/form-data" class="widget-box emitente-form">
            <input type="hidden" name="<?= $e($tokenName) ?>" value="<?= $e($tokenHash) ?>">
            <?php if ($id > 0) { ?>
                <input type="hidden" name="id" value="<?= $id ?>">
            <?php } ?>

            <div class="widget-title"><h5>Dados da empresa</h5></div>
            <div class="widget-content">
                <div class="ui-secao">
                    <h4 class="ui-secao-titulo">Identificação</h4>
                    <div class="ui-grade">
                        <div class="mb-3">
                            <label for="nomeEmitente" class="form-label">Razão social<span class="required">*</span></label>
                            <div class="">
                                <input class="form-control" id="nomeEmitente" type="text" name="nome" maxlength="255" value="<?= $e($v('nome')) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="nome_fantasia" class="form-label">Nome fantasia</label>
                            <div class="">
                                <input class="form-control" id="nome_fantasia" type="text" name="nome_fantasia" maxlength="255" value="<?= $e($v('nome_fantasia')) ?>">
                                <span class="form-text">É o nome que o cliente reconhece. Vai como xFant na NF-e.</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="documento" class="form-label">CNPJ<span class="required">*</span></label>
                            <div class="">
                                <div class="emitente-campo-botao">
                                    <input id="documento" class="cnpjEmitente form-control" type="text" name="cnpj" maxlength="45" value="<?= $e($v('cnpj')) ?>" required>
                                    <button id="buscar_info_cnpj" class="btn btn-sm btn-outline-secondary" type="button" title="Buscar dados do CNPJ">
                                        <i class="bx bx-search"></i>
                                    </button>
                                </div>
                                <span class="form-text">Digite 00.000.000/0000-00 para ocultar o CNPJ nos documentos.</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="ie" class="form-label">Inscrição estadual</label>
                            <div class="">
                                <input class="form-control" id="ie" type="text" name="ie" maxlength="50" value="<?= $e($v('ie')) ?>">
                                <span class="form-text">Ou ISENTO, se não for contribuinte de ICMS.</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inscricao_municipal" class="form-label">Inscrição municipal</label>
                            <div class="">
                                <input class="form-control" id="inscricao_municipal" type="text" name="inscricao_municipal" maxlength="20" value="<?= $e($v('inscricao_municipal')) ?>">
                                <span class="form-text">Exigida pela NFS-e na maioria dos municípios. Se preenchida aqui, vale na próxima nota.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ui-secao">
                    <h4 class="ui-secao-titulo">Endereço</h4>
                    <div class="ui-grade">
                        <div class="mb-3">
                            <label for="cep" class="form-label">CEP<span class="required">*</span></label>
                            <div class="">
                                <input class="form-control" id="cep" type="text" name="cep" maxlength="20" value="<?= $e($v('cep')) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="rua" class="form-label">Logradouro<span class="required">*</span></label>
                            <div class="">
                                <input class="form-control" id="rua" type="text" name="logradouro" maxlength="70" value="<?= $e($v('rua')) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="numero" class="form-label">Número<span class="required">*</span></label>
                            <div class="">
                                <input class="form-control" id="numero" type="text" name="numero" maxlength="15" value="<?= $e($v('numero')) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="complemento" class="form-label">Complemento</label>
                            <div class="">
                                <input class="form-control" id="complemento" type="text" name="complemento" maxlength="45" value="<?= $e($v('complemento')) ?>">
                                <span class="form-text">Sala, loja, bloco. Vai como xCpl na NF-e.</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="bairro" class="form-label">Bairro<span class="required">*</span></label>
                            <div class="">
                                <input class="form-control" id="bairro" type="text" name="bairro" maxlength="45" value="<?= $e($v('bairro')) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="cidade" class="form-label">Cidade<span class="required">*</span></label>
                            <div class="">
                                <input class="form-control" id="cidade" type="text" name="cidade" maxlength="45" value="<?= $e($v('cidade')) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="estado" class="form-label">UF<span class="required">*</span></label>
                            <div class="">
                                <input class="form-control" id="estado" type="text" name="uf" maxlength="2" value="<?= $e($v('uf')) ?>" required placeholder="AM">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ui-secao">
                    <h4 class="ui-secao-titulo">Contato</h4>
                    <div class="ui-grade">
                        <div class="mb-3">
                            <label for="telefone" class="form-label">Telefone<span class="required">*</span></label>
                            <div class="">
                                <input class="form-control" id="telefone" type="text" name="telefone" maxlength="20" value="<?= $e($v('telefone')) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail<span class="required">*</span></label>
                            <div class="">
                                <input class="form-control" id="email" type="email" name="email" maxlength="255" value="<?= $e($v('email')) ?>" required>
                                <span class="form-text">Usado como remetente das cobranças e das notificações.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($id === 0) { ?>
                    <div class="ui-secao">
                        <h4 class="ui-secao-titulo">Logo</h4>
                        <div class="mb-3">
                            <label for="userfile" class="form-label">Arquivo</label>
                            <div class="">
                                <input class="form-control" type="file" id="userfile" name="userfile" accept="image/png,image/jpeg,image/bmp">
                                <span class="form-text">PNG, JPG ou BMP, até 2 MB. Indicado 130 × 130 px.</span>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <div class="ui-rodape-form">
                    <a href="<?= site_url('mapos') ?>" class="button btn btn-warning">
                        <span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span>
                    </a>
                    <button type="submit" class="button btn btn-primary">
                        <span class="button__icon"><i class="bx bx-save"></i></span><span class="button__text2"><?= $id > 0 ? 'Salvar alterações' : 'Cadastrar emitente' ?></span>
                    </button>
                </div>
            </div>
        </form>

        <aside class="emitente-lateral">
            <?php if ($id > 0) { ?>
                <div class="widget-box">
                    <div class="widget-title"><h5>Logo</h5></div>
                    <div class="widget-content emitente-logo">
                        <?php if ($temLogo) { ?>
                            <img src="<?= $e($logo) ?>" alt="Logo da empresa">
                        <?php } else { ?>
                            <div class="ui-vazio" style="padding:18px">
                                <i class="bx bx-image"></i>
                                <strong>Sem logo</strong>
                                Os documentos saem só com os dados.
                            </div>
                        <?php } ?>
                        <form action="<?= site_url('mapos/editarLogo') ?>" method="post" enctype="multipart/form-data" class="emitente-logo-form">
                            <input type="hidden" name="<?= $e($tokenName) ?>" value="<?= $e($tokenHash) ?>">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input class="form-control" type="file" name="userfile" accept="image/png,image/jpeg,image/bmp" required>
                            <button type="submit" class="button btn btn-dark btn-sm">
                                <span class="button__icon"><i class="bx bx-upload"></i></span><span class="button__text2"><?= $temLogo ? 'Trocar logo' : 'Enviar logo' ?></span>
                            </button>
                        </form>
                        <?php if ($temLogo) { ?>
                            <form action="<?= site_url('mapos/removerLogo') ?>" method="post" data-confirmar="Remover a logo atual? Os documentos passam a sair sem ela.">
                                <input type="hidden" name="<?= $e($tokenName) ?>" value="<?= $e($tokenHash) ?>">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button type="submit" class="button btn btn-danger btn-sm">
                                    <span class="button__icon"><i class="bx bx-trash"></i></span><span class="button__text2">Remover</span>
                                </button>
                            </form>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <div class="widget-box">
                <div class="widget-title"><h5>Conferência para emissão</h5></div>
                <div class="widget-content">
                    <p class="emitente-ajuda">Estes itens são checados quando o sistema emite uma nota. O que aparece aqui, com o mesmo critério.</p>
                    <ul class="emitente-check">
                        <?php foreach ($checklist as $item) { ?>
                            <li class="<?= ! empty($item['ok']) ? 'ok' : 'falta' ?>">
                                <i class="bx <?= ! empty($item['ok']) ? 'bx-check-circle' : 'bx-error-circle' ?>"></i>
                                <div>
                                    <strong><?= $e($item['titulo']) ?></strong>
                                    <span><?= $e($item['detalhe']) ?></span>
                                    <?php if (! empty($item['acao']) && empty($item['ok'])) { ?>
                                        <a href="<?= site_url($item['acao']) ?>">Resolver</a>
                                    <?php } ?>
                                </div>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
    .emitente-grade { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 16px; align-items: start; }
    .emitente-form .widget-content { padding: 16px 16px 4px; }
    .emitente-lateral { display: flex; flex-direction: column; gap: 16px; }
    .emitente-campo-botao { display: flex; gap: 6px; align-items: center; }
    .emitente-campo-botao input { flex: 1 1 auto; }
    .emitente-campo-botao .btn { flex: 0 0 auto; }
    .emitente-logo { display: flex; flex-direction: column; gap: 10px; text-align: center; }
    .emitente-logo img { max-width: 100%; max-height: 110px; object-fit: contain; margin: 0 auto; }
    .emitente-logo-form { display: flex; flex-direction: column; gap: 8px; }
    .emitente-ajuda { font-size: 12px; color: var(--ui-texto-2); margin: 0 0 10px; }
    .emitente-check { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
    .emitente-check li { display: flex; gap: 8px; align-items: flex-start; font-size: 13px; line-height: 1.35; }
    .emitente-check li i { font-size: 17px; flex: 0 0 auto; margin-top: 1px; }
    .emitente-check li strong { display: block; font-size: 13px; }
    .emitente-check li span { display: block; color: var(--ui-texto-2); font-size: 12px; }
    .emitente-check li.ok i { color: var(--ui-sucesso); }
    .emitente-check li.falta i { color: var(--ui-alerta); }
    @media (max-width: 991px) { .emitente-grade { grid-template-columns: 1fr; } }
</style>

<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/jquery.mask.min.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/sweetalert2.all.min.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/funcoes.js"></script>
