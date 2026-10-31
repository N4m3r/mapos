<?php
// Variaveis: $itens (trib_status_atualizacao), $verificacao (array|null), $libs, $janela (array|null)
$rotulos = ['ok' => ['OK', 'label-success'], 'atencao' => ['Atenção', 'label-warning'], 'desatualizado' => ['Desatualizado', 'label-important']];
$pendencias = 0;
foreach ($itens as $i) {
    if ($i['situacao'] !== 'ok') {
        $pendencias++;
    }
}
?>
<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="ui-cabecalho">
            <div>
                <h1><i class="fas fa-balance-scale"></i> Central da Reforma Tributária</h1>
                <p>Veja se o sistema está com as tabelas e bibliotecas fiscais atualizadas e quais prazos estão chegando.</p>
            </div>
            <div class="ui-acoes">
                <a href="<?= site_url('tributacao/tabelas') ?>" class="button btn btn-info"><span class="button__icon"><i class="bx bx-table"></i></span><span class="button__text2">Tabelas da Reforma</span></a>
                <a href="<?= site_url('tributacao/simulador') ?>" class="button btn btn-success"><span class="button__icon"><i class="bx bx-calculator"></i></span><span class="button__text2">Simulador DAS × regular</span></a>
                <a href="<?= site_url('nfe/configuracoes') ?>" class="button btn btn-info"><span class="button__icon"><i class="bx bx-arrow-back"></i></span><span class="button__text2">Configurações Fiscais</span></a>
            </div>
        </div>

        <div class="ui-dica">
            <i class="bx bx-info-circle"></i>
            <div>Esta tela só mostra a situação. Ela não altera nenhuma nota nem configuração. Quando houver pendência, leia a mensagem e confirme com o seu contador antes de mexer.</div>
        </div>

        <div class="widget-box">
            <div class="widget-title"><span class="icon"><i class="fas fa-check-circle"></i></span><h5>Situação do sistema</h5></div>
            <div class="widget-content">
                <?php if ($pendencias === 0) { ?>
                    <div class="alert alert-success">Tudo em dia. Nenhuma pendência encontrada.</div>
                <?php } else { ?>
                    <div class="alert alert-warning"><?= (int) $pendencias ?> ponto(s) pedem atenção. Veja abaixo.</div>
                <?php } ?>
                <table class="table table-bordered">
                    <thead><tr><th>Item</th><th style="width:120px">Situação</th><th>O que fazer</th></tr></thead>
                    <tbody>
                    <?php foreach ($itens as $i) { $r = $rotulos[$i['situacao']] ?? ['-', '']; ?>
                        <tr>
                            <td><?= htmlspecialchars($i['item']) ?></td>
                            <td><span class="label <?= $r[1] ?>"><?= $r[0] ?></span></td>
                            <td><?= htmlspecialchars($i['mensagem']) ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="widget-box">
            <div class="widget-title"><span class="icon"><i class="fas fa-sync"></i></span><h5>Verificação de novidades</h5></div>
            <div class="widget-content">
                <?php if (! $verificacao) { ?>
                    <p>Ainda não foi feita nenhuma verificação de novidades. Peça ao responsável técnico para rodar o comando mensal (veja a linha de cron abaixo).</p>
                <?php } else { ?>
                    <p>Última verificação: <strong><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) ($verificacao['data'] ?? 'now')))) ?></strong></p>
                    <?php if (! empty($verificacao['avisos'])) { ?>
                        <ul>
                            <?php foreach ($verificacao['avisos'] as $a) { ?><li><?= htmlspecialchars($a) ?></li><?php } ?>
                        </ul>
                    <?php } else { ?>
                        <p>Nenhum aviso na última verificação.</p>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>

        <div class="widget-box">
            <div class="widget-title"><span class="icon"><i class="fas fa-cube"></i></span><h5>Versões instaladas</h5></div>
            <div class="widget-content">
                <table class="table table-bordered">
                    <thead><tr><th>Biblioteca</th><th>Instalada</th><th>Última conhecida</th></tr></thead>
                    <tbody>
                    <?php foreach ($libs as $l) { ?>
                        <tr>
                            <td><?= htmlspecialchars($l['pacote']) ?></td>
                            <td><?= htmlspecialchars((string) ($l['instalada'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string) ($l['ultima'] ?? 'não verificada')) ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="widget-box">
            <div class="widget-title"><span class="icon"><i class="fas fa-calendar-alt"></i></span><h5>Prazos</h5></div>
            <div class="widget-content">
                <?php if ($janela) { ?>
                    <p><strong><?= htmlspecialchars($janela['rotulo']) ?></strong>: de <?= date('d/m/Y', strtotime($janela['inicio'])) ?> até <?= date('d/m/Y', strtotime($janela['fim'])) ?>.</p>
                    <?php if ($janela['aberta']) { ?>
                        <p>Janela <strong>aberta</strong>. Faltam <?= (int) $janela['dias_restantes'] ?> dia(s) para fechar.</p>
                    <?php } else { ?>
                        <p>Abre em <?= (int) $janela['dias_para_abrir'] ?> dia(s).</p>
                    <?php } ?>
                    <p class="hint">As datas futuras dependem de resolução do CGSN. Confira no Portal antes de agir.</p>
                <?php } else { ?>
                    <p>Nenhum prazo de opção cadastrado a partir de hoje.</p>
                <?php } ?>
            </div>
        </div>

        <div class="widget-box">
            <div class="widget-title"><span class="icon"><i class="fas fa-link"></i></span><h5>Fontes oficiais</h5></div>
            <div class="widget-content">
                <ul>
                    <li><a href="https://www.nfe.fazenda.gov.br" target="_blank" rel="noopener">Portal Nacional da NF-e (nfe.fazenda.gov.br)</a></li>
                    <li><a href="https://www.gov.br/nfse" target="_blank" rel="noopener">NFS-e Nacional (gov.br/nfse), com a documentação</a></li>
                    <li>Decisão técnica do sistema: <code>docs/adr/ADR-022-planejamento-tributario-iva.md</code></li>
                </ul>
            </div>
        </div>

        <div class="widget-box">
            <div class="widget-title"><span class="icon"><i class="fas fa-terminal"></i></span><h5>Agendamento mensal (para o TI)</h5></div>
            <div class="widget-content">
                <p>Rode uma vez por mês. O comando só consulta as versões e grava o resultado nesta tela.</p>
                <pre>0 7 1 * * cd /caminho/do/mapos &amp;&amp; php index.php tributacao verificar</pre>
            </div>
        </div>
    </div>
</div>
