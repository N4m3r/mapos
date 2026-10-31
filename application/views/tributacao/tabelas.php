<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="ui-cabecalho">
            <div>
                <h1><i class="fas fa-table"></i> Tabelas da Reforma Tributária</h1>
                <p>Códigos oficiais usados nas notas (CST, cClassTrib, crédito presumido e NFS-e). Atualize quando o governo publicar uma nova versão.</p>
            </div>
            <div class="ui-acoes">
                <a href="<?= site_url('nfe/configuracoes') ?>" class="button btn btn-info"><span class="button__icon"><i class="bx bx-arrow-back"></i></span><span class="button__text2">Configurações Fiscais</span></a>
            </div>
        </div>
        <div class="ui-dica">
            <i class="bx bx-info-circle"></i>
            <div>Baixe a tabela no site oficial, salve como <strong>CSV</strong> (separador ; ou ,) e envie abaixo. A importação <strong>substitui</strong> a tabela inteira; se houver erro, nada é alterado.
                Fontes: <a href="https://www.nfe.fazenda.gov.br" target="_blank" rel="noopener">Portal Nacional da NF-e</a> (Informe Técnico 2025.002),
                <a href="https://dfe-portal.svrs.rs.gov.br/DFE/TabelaClassificacaoTributaria" target="_blank" rel="noopener">SVRS</a> e
                <a href="https://www.gov.br/nfse" target="_blank" rel="noopener">gov.br/nfse</a> (Anexo VIII).</div>
        </div>
        <?php if ($this->session->flashdata('success')) { ?><div class="alert alert-success"><?= htmlspecialchars($this->session->flashdata('success')) ?></div><?php } ?>
        <?php if ($this->session->flashdata('error')) { ?><div class="alert alert-danger"><?= htmlspecialchars($this->session->flashdata('error')) ?></div><?php } ?>

        <?php foreach ($resumo as $k => $t) { ?>
        <div class="widget-box">
            <div class="widget-title"><span class="icon"><i class="fas fa-list"></i></span><h5><?= htmlspecialchars($t['nome']) ?></h5></div>
            <div class="widget-content">
                <p>
                    <strong><?= (int) $t['qtd'] ?></strong> registros
                    <?php if ($t['versao']) { ?>
                        &middot; versão <strong><?= htmlspecialchars((string) $t['versao']->versao ?: '-') ?></strong>
                        &middot; importada em <?= date('d/m/Y H:i', strtotime($t['versao']->importado_em)) ?>
                        <?php if ($t['versao']->vigencia_inicio) { ?>&middot; vigência desde <?= date('d/m/Y', strtotime($t['versao']->vigencia_inicio)) ?><?php } ?>
                    <?php } else { ?>
                        &middot; ainda não importada
                    <?php } ?>
                </p>
                <form method="post" action="<?= site_url('tributacao/tabelas') ?>" enctype="multipart/form-data" class="form-inline">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    <input type="hidden" name="tabela" value="<?= htmlspecialchars($k) ?>">
                    <input type="file" name="arquivo" accept=".csv,.txt" required>
                    <input type="text" name="versao" placeholder="Versão (ex.: IT 2025.002 v1.31)" maxlength="40">
                    <input type="date" name="vigencia_inicio" title="Vigência desde">
                    <button type="submit" class="button btn btn-success"><span class="button__icon"><i class="bx bx-upload"></i></span><span class="button__text2">Importar CSV</span></button>
                </form>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
