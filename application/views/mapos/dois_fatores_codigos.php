<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="fas fa-key"></i></span>
        <h5>Códigos de recuperação</h5>
    </div>
    <div class="widget-content">
        <div class="alert alert-success">Verificação em duas etapas ativa.</div>
        <p><strong>Guarde estes códigos agora</strong> (imprima ou salve num gerenciador de senhas). Eles não serão mostrados de novo.
            Cada um vale uma única vez para entrar sem o aplicativo, por exemplo se perder o celular.</p>
        <pre id="codigosRecuperacao" style="font-size:16px;line-height:1.8;max-width:320px;columns:2"><?php foreach ($codigos as $c) {
            echo html_escape($c) . "\n";
        } ?></pre>
        <button type="button" class="button btn btn-info" data-acao="imprimir"><span class="button__icon"><i class="bx bx-printer"></i></span><span class="button__text2">Imprimir</span></button>
        <a href="<?= site_url('mapos/minhaConta') ?>" class="button btn btn-primary"><span class="button__icon"><i class="bx bx-check"></i></span><span class="button__text2">Já guardei</span></a>
    </div>
</div>
