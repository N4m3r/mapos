<?php if ($emitente): ?>
    <div>
        <br>
        <div style="width: 50%; float: left" class="float-left col-md-3">
            <?php if(!empty($emitente->url_logo) && file_exists(convertUrlToUploadsPath($emitente->url_logo))) { ?>
                <img style="width: 150px" src="<?= $emitente->url_logo ?>" alt="<?= html_escape($emitente->nome) ?>"><br><br>
            <?php } else { ?>
                <div style="width: 150px;"><p></p></div>
            <?php } ?>
        </div>
        <div style="float: right">
            <b>EMPRESA: </b> <?= html_escape($emitente->nome) ?> <b>CNPJ: </b> <?= html_escape($emitente->cnpj) ?><br>
            <b>ENDEREÇO: </b> <?= html_escape($emitente->rua) ?>, <?= html_escape($emitente->numero) ?>, <?= html_escape($emitente->bairro) ?>, <?= html_escape($emitente->cidade) ?> - <?= html_escape($emitente->uf) ?> <br>

            <?php if (isset($title)): ?>
                <b>RELATÓRIO: </b> <?= $title ?> <br>
            <?php endif ?>

            <?php if (isset($dataInicial)): ?>
                <b>DATA INICIAL: </b> <?= $dataInicial ?>
            <?php endif ?>

            <?php if (isset($dataFinal)): ?>
                <b>DATA FINAL: </b> <?= $dataFinal ?>
            <?php endif ?>
        </div>
    </div>
<?php endif ?>
