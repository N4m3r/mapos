<div class="row-fluid">
    <div id="footer" class="span12">
        <a class="pecolor" href="https://github.com/RamonSilva20/mapos" target="_blank">
            <?= date('Y') ?> &copy; Ramon Silva - Map-OS - Versão: <?= $this->config->item('app_version') ?>
        </a>
    </div>
</div>
<!--end-Footer-part-->
<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/bootstrap.min.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/matrix.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url('assets/js/paleta-comandos.js') ?>?v=<?= asset_versao('assets/js/paleta-comandos.js') ?>" data-url-pesquisa="<?= site_url('mapos/pesquisar') ?>"></script>
</body>
<script nonce="<?= csp_nonce() ?>" type="text/javascript">
    $(document).ready(function() {
        var dataTableEnabled = '<?= $configuration['control_datatable'] ?>';
        if(dataTableEnabled == '1') {
            // A linha "Nenhum ... cadastrado" (um <td colspan>) quebra o DataTables
            // (_DT_CellIndex). Se for a unica linha, sai da tabela e o texto vira a
            // mensagem de tabela vazia do proprio DataTables.
            var textoVazio = null;
            var $linhas = $('#tabela > tbody > tr');
            if ($linhas.length === 1 && $linhas.children('td').length === 1 && $linhas.children('td').attr('colspan')) {
                textoVazio = $.trim($linhas.text());
                $linhas.remove();
            }
            $('#tabela').dataTable( {
                "ordering": false,
                "info": false,
                "language": {
                    "url": "<?= base_url() ?>assets/js/dataTable_pt-br.json",
                },
                "oLanguage": {
                    "sSearch": "Pesquisa rápida na tabela abaixo:"
                },
                "initComplete": function() {
                    if (textoVazio) {
                        this.api().settings()[0].oLanguage.sEmptyTable = textoVazio;
                        this.api().draw();
                    }
                }
            } );
        }
    } );
</script>
</html>
