<?php
$tiposPonto = Obra_pontos_model::tipos();
$tiposProjeto = [
    'cftv' => ['rotulo' => 'CFTV', 'valor' => 'CFTV IP', 'icone' => 'bx-cctv', 'dica' => 'Câmeras, NVR e rack'],
    'acesso' => ['rotulo' => 'Controle de acesso', 'valor' => 'Controle de acesso', 'icone' => 'bx-id-card', 'dica' => 'Leitores, fechaduras, botoeiras'],
    'rede' => ['rotulo' => 'Rede e cabeamento', 'valor' => 'Rede estruturada', 'icone' => 'bx-network-chart', 'dica' => 'Pontos de rede, AP, switch'],
    'alarme' => ['rotulo' => 'Alarme', 'valor' => 'Alarme', 'icone' => 'bx-bell', 'dica' => 'Sensores e central'],
    'outro' => ['rotulo' => 'Outro', 'valor' => '', 'icone' => 'bx-dots-horizontal-rounded', 'dica' => 'Informe o tipo'],
];
$sugeridos = [
    'cftv' => ['camera', 'nvr', 'switch', 'rack'],
    'acesso' => ['leitor_acesso', 'fechadura', 'botoeira'],
    'rede' => ['ponto_rede', 'ap_wifi', 'switch', 'rack'],
    'alarme' => ['sensor'],
    'outro' => ['ponto_rede', 'camera'],
];
$pontosJs = [];
foreach ($tiposPonto as $k => $t) {
    $pontosJs[$k] = ['rotulo' => $t['rotulo'], 'icone' => $t['icone']];
}
$clientesJs = array_map(function ($c) {
    $doc = $c->documento ? ' — ' . $c->documento : '';
    return ['id' => (int) $c->idClientes, 'label' => $c->nomeCliente . $doc, 'nome' => $c->nomeCliente, 'doc' => (string) $c->documento];
}, $clientes);
?>
<link rel="stylesheet" href="<?= base_url('assets/css/projeto-assistente.css') ?>?v=<?= asset_versao('assets/css/projeto-assistente.css') ?>">
<link rel="stylesheet" href="<?= base_url() ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<div class="row" style="margin-top:0">
    <div class="col-md-12">
        <div class="widget-box">
            <div class="widget-content pa">
                <div class="ui-cabecalho">
                    <div>
                        <h1 class="ui-titulo"><i class="bx bx-building-house"></i> Novo projeto</h1>
                        <p class="ui-subtitulo">Responda em 3 passos. O que você não souber agora, ajuste depois.</p>
                    </div>
                    <div class="ui-acoes"><a href="<?= site_url('obras') ?>" class="button btn btn-sm"><span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Cancelar</span></a></div>
                </div>

                <ol class="pa-passos" id="paPassos">
                    <li data-passo="1" class="ativo"><span class="pa-num">1</span><span class="pa-rot">Cliente e tipo</span></li>
                    <li data-passo="2"><span class="pa-num">2</span><span class="pa-rot">Escopo</span></li>
                    <li data-passo="3"><span class="pa-num">3</span><span class="pa-rot">Prazo e equipe</span></li>
                </ol>

                <form action="<?= current_url() ?>" method="post" id="formObra" autocomplete="off">
                    <input type="hidden" name="status" value="planejamento">
                    <input type="hidden" name="tipo_obra" id="tipo_obra" value="">

                    <section class="pa-painel" data-painel="1">
                        <h2 class="pa-titulo">Qual é o projeto?</h2>
                        <div class="mb-3">
                            <label for="nome">Nome do projeto <span class="required">*</span></label>
                            <input type="text" id="nome" name="nome" class="pa-campo form-control" maxlength="150" placeholder="Ex.: CFTV da loja centro">
                            <small class="pa-erro" data-erro="nome">Informe o nome do projeto.</small>
                        </div>
                        <div class="mb-3">
                            <label for="cliente_busca">Cliente</label>
                            <div style="position:relative">
                                <input type="text" id="cliente_busca" class="pa-campo form-control" autocomplete="off" placeholder="Buscar por nome ou CNPJ/CPF...">
                                <input type="hidden" name="clientes_id" id="clientes_id" value="">
                                <a href="#" id="cliente_limpar" title="Limpar cliente" aria-label="Limpar cliente" class="pa-limpar" style="display:none"><i class="bx bx-x"></i></a>
                            </div>
                            <small class="form-text">Digite o nome ou o documento. Pode ficar em branco por enquanto.</small>
                        </div>
                        <div class="mb-3">
                            <label id="lblTipo">Tipo de projeto <span class="required">*</span></label>
                            <div class="pa-tipos" role="radiogroup" aria-labelledby="lblTipo">
                                <?php foreach ($tiposProjeto as $k => $t): ?>
                                    <button type="button" class="pa-tipo" role="radio" aria-checked="false" data-tipo="<?= $k ?>" data-valor="<?= html_escape($t['valor']) ?>">
                                        <i class="bx <?= $t['icone'] ?>"></i>
                                        <strong><?= html_escape($t['rotulo']) ?></strong>
                                        <small><?= html_escape($t['dica']) ?></small>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div id="paOutro" style="display:none;margin-top:10px">
                                <input type="text" id="tipo_outro" class="pa-campo form-control" maxlength="80" placeholder="Qual tipo? Ex.: Interfonia, Manutenção">
                            </div>
                            <small class="pa-erro" data-erro="tipo">Escolha o tipo do projeto.</small>
                        </div>
                    </section>

                    <section class="pa-painel" data-painel="2" hidden>
                        <h2 class="pa-titulo">O que será feito?</h2>
                        <div class="pa-bloco">
                            <h3>Etapas</h3>
                            <p class="pa-dica">Sugerimos as etapas mais comuns para este tipo. Desmarque as que não se aplicam.</p>
                            <ul class="pa-etapas" id="paEtapas"></ul>
                            <div class="pa-add">
                                <input type="text" id="paEtapaExtra" class="pa-campo form-control" maxlength="150" placeholder="Adicionar outra etapa">
                                <button type="button" class="button btn" id="paEtapaAdd"><span class="button__icon"><i class="bx bx-plus"></i></span><span class="button__text2">Adicionar etapa</span></button>
                            </div>
                        </div>
                        <div class="pa-bloco">
                            <h3>Pontos</h3>
                            <p class="pa-dica">Quantos pontos de cada tipo? Eles são criados já numerados (CAM-01, CAM-02...).</p>
                            <div class="pa-pontos" id="paPontos"></div>
                            <button type="button" class="pa-link" id="paMais" aria-expanded="false"><i class="bx bx-chevron-down"></i> Mais tipos</button>
                            <div class="pa-pontos" id="paPontosMais" hidden></div>
                        </div>
                    </section>

                    <section class="pa-painel" data-painel="3" hidden>
                        <h2 class="pa-titulo">Quando e quem?</h2>
                        <div class="pa-duas">
                            <div class="mb-3"><label for="data_inicio_prevista">Início previsto</label><input type="text" id="data_inicio_prevista" class="datepicker pa-campo form-control" name="data_inicio_prevista" placeholder="dd/mm/aaaa"></div>
                            <div class="mb-3"><label for="data_fim_prevista">Fim previsto</label><input type="text" id="data_fim_prevista" class="datepicker pa-campo form-control" name="data_fim_prevista" placeholder="dd/mm/aaaa"></div>
                        </div>
                        <small class="pa-erro" data-erro="datas">O fim previsto não pode ser antes do início.</small>

                        <div class="pa-bloco">
                            <h3>Técnicos do projeto</h3>
                            <p class="pa-dica">Quem vai executar. Eles veem o projeto no celular.</p>
                            <div class="pa-tecnicos">
                                <?php foreach ($usuarios as $u): ?>
                                    <label class="pa-tec"><input class="form-check-input" type="checkbox" name="tecnicos[]" value="<?= (int) $u->idUsuarios ?>"> <span><?= html_escape($u->nome) ?></span></label>
                                <?php endforeach; ?>
                                <?php if (! $usuarios): ?><p class="pa-dica">Nenhum usuário ativo.</p><?php endif; ?>
                            </div>
                        </div>

                        <div class="pa-bloco">
                            <button type="button" class="pa-link" id="paDetalhesBt" aria-expanded="false" aria-controls="paDetalhes"><i class="bx bx-chevron-down"></i> Mais detalhes</button>
                            <div id="paDetalhes" hidden>
                                <div class="ui-grade">
                                    <div class="mb-3"><label for="codigo">Código</label><input class="form-control" type="text" id="codigo" name="codigo"></div>
                                    <div class="mb-3">
                                        <label for="responsavel_id">Responsável técnico</label>
                                        <select class="form-select" id="responsavel_id" name="responsavel_id">
                                            <option value="">Selecione</option>
                                            <?php foreach ($usuarios as $u): ?>
                                                <option value="<?= (int) $u->idUsuarios ?>"><?= html_escape($u->nome) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3"><label for="contrato_numero">Nº do contrato</label><input class="form-control" type="text" id="contrato_numero" name="contrato_numero"></div>
                                    <div class="mb-3"><label for="valor_contrato">Valor do contrato (R$)</label><input class="form-control" type="number" id="valor_contrato" step="0.01" name="valor_contrato" value="0"></div>
                                    <div class="mb-3"><label for="bdi_percentual">BDI (%)</label><input class="form-control" type="number" id="bdi_percentual" step="0.01" name="bdi_percentual" value="0"><small class="form-text">Margem aplicada sobre o custo.</small></div>
                                    <div class="mb-3"><label for="cep">CEP</label><input class="form-control" type="text" id="cep" name="cep"></div>
                                    <div class="mb-3" style="grid-column:span 2"><label for="logradouro">Logradouro</label><input class="form-control" type="text" id="logradouro" name="logradouro"></div>
                                    <div class="mb-3"><label for="numero">Número</label><input class="form-control" type="text" id="numero" name="numero"></div>
                                    <div class="mb-3"><label for="complemento">Complemento</label><input class="form-control" type="text" id="complemento" name="complemento"></div>
                                    <div class="mb-3"><label for="bairro">Bairro</label><input class="form-control" type="text" id="bairro" name="bairro"></div>
                                    <div class="mb-3" style="grid-column:span 2"><label for="cidade">Cidade</label><input class="form-control" type="text" id="cidade" name="cidade"></div>
                                    <div class="mb-3"><label for="uf">UF</label><input class="form-control" type="text" id="uf" name="uf" maxlength="2"></div>
                                </div>
                                <div class="mb-3"><label for="observacoes">Observações</label><textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea></div>
                            </div>
                        </div>

                        <div class="pa-resumo" id="paResumo" aria-live="polite"></div>
                    </section>

                    <div class="pa-rodape">
                        <button type="button" class="button btn" id="paVoltar" hidden><span class="button__icon"><i class="bx bx-left-arrow-alt"></i></span><span class="button__text2">Voltar</span></button>
                        <span class="pa-rodape-info" id="paInfo">Passo 1 de 3</span>
                        <button type="button" class="button btn btn-primary" id="paAvancar"><span class="button__text2">Avançar</span><span class="button__icon"><i class="bx bx-right-arrow-alt"></i></span></button>
                        <button type="submit" class="button btn btn-primary" id="paCriar" hidden><span class="button__icon"><i class="bx bx-check"></i></span><span class="button__text2">Criar projeto</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script nonce="<?= csp_nonce() ?>">
    var PA_CLIENTES = <?= json_encode($clientesJs, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var PA_PONTOS = <?= json_encode($pontosJs, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var PA_SUGERIDOS = <?= json_encode($sugeridos) ?>;
    var PA_URL_ETAPAS = <?= json_encode(site_url('obras/etapasPadraoTipo')) ?>;
</script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/projeto-assistente.js') ?>?v=<?= asset_versao('assets/js/projeto-assistente.js') ?>"></script>
