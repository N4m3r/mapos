<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Map OS</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/matrix-style.css" />
    <link href="<?php echo base_url(); ?>assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="<?= base_url('assets/css/custom.css'); ?>" rel="stylesheet">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700,800' rel='stylesheet' type='text/css'>
    <style>
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #FAFAFA;
        }

        * {
            box-sizing: border-box;
            -moz-box-sizing: border-box;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 4mm;
            margin: 1mm auto;
            border: 1px #D3D3D3 solid;
            border-radius: 5px;
            background: white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .subpage {
            padding: 0.5cm;
            border: 0px red solid;
            height: 257mm;
            outline: 2cm #FFEAEA solid;
        }

        @page {
            size: A4;
            margin: 0;
        }

        @media print {

            html,
            body {
                width: 210mm;
                height: 297mm;
            }

            .page {
                margin: 0;
                border: initial;
                border-radius: initial;
                width: initial;
                min-height: initial;
                box-shadow: initial;
                background: initial;
                page-break-after: always;
            }
        }
    </style>
</head>

<body>


    <div class="container-fluid page" id="viaCliente">
        <div class="subpage">
            <div class="row-fluid">
                <div class="span12">

                    <div class="invoice-content">
                        <div class="invoice-head" style="margin-bottom: 0">

                            <table class="table table-condensed">
                                <tbody>
                                    <?php if ($emitente == null) { ?>
                                        <tr>
                                            <td colspan="3" class="alert">Você precisa configurar os dados do emitente. >>><a href="<?php echo base_url(); ?>index.php/mapos/emitente">Configurar</a>
                                                <<<</td> </tr> <?php
                                    } else { ?> <tr>
                                            <td style="width: 25%"><img src=" <?php echo $emitente->url_logo; ?> "></td>
                                            <td> <span style="font-size: 20px; ">
                                                    <?php echo $emitente->nome; ?></span> <br />
                                                <span>
                                                    <?php echo $emitente->cnpj; ?> <br />
                                                    <?php echo $emitente->rua . ', nº:' . $emitente->numero . ', ' . $emitente->bairro . ' - ' . $emitente->cidade . ' - ' . $emitente->uf; ?> </span> </br> <span> E-mail:
                                                    <?php echo $emitente->email . ' - Fone: ' . $emitente->telefone; ?>
                                                </span>
                                            </td>
                                            <td style="width: 18%; text-align: center">
                                                <br /> <br />
                                                <span>Emissão:
                                                    <?php echo date('d/m/Y'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php
                                    } ?>
                                </tbody>
                            </table>
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td style="width: 50%; padding-left: 0">
                                            <ul>
                                                <li>
                                                    <span>
                                                        <h5 class="text-center">Termo de Garantia</h5>
                                                    </span>
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td style="width: 100%; padding-left: 0">
                                            <ul>
                                                <li>

                                                    <span><?php echo htmlspecialchars_decode($result->textoGarantia) ?></span><br />
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <table class="table table-bordered table-condensed">
                                <tbody>
                                    <tr>
                                        <td>Data
                                            <hr>
                                        </td>
                                        <td>Assinatura do Cliente
                                            <hr>
                                        </td>
                                        <td>Assinatura do Técnico Responsável
                                            <hr>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?php echo base_url(); ?>assets/js/bootstrap.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/matrix.js"></script>
    <script>
        // Aguarda o carregamento completo de todas as imagens antes de imprimir
        document.addEventListener('DOMContentLoaded', function() {
            var imagens = document.querySelectorAll('img');
            var totalImagens = imagens.length;
            var imagensCarregadas = 0;
            var imagensComErro = 0;

            function verificarImagens() {
                if (imagensCarregadas + imagensComErro === totalImagens) {
                    // Pequeno delay adicional para garantir renderização completa
                    setTimeout(function() {
                        window.print();
                    }, 500);
                }
            }

            if (totalImagens === 0) {
                // Se não houver imagens, imprime após pequeno delay
                setTimeout(function() {
                    window.print();
                }, 500);
            } else {
                imagens.forEach(function(img) {
                    if (img.complete) {
                        imagensCarregadas++;
                        verificarImagens();
                    } else {
                        img.addEventListener('load', function() {
                            imagensCarregadas++;
                            verificarImagens();
                        });
                        img.addEventListener('error', function() {
                            imagensComErro++;
                            // Define uma imagem placeholder para imagens que falharam
                            this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTQwIiBoZWlnaHQ9IjE0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTQwIiBoZWlnaHQ9IjE0MCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5JbWFnZW0mbmJzcDtuw6NvJm5ic3A7Y2FycmVnYWRhPC90ZXh0Pjwvc3ZnPg==';
                            verificarImagens();
                        });
                    }
                });
            }
        });
    </script>
</body>

</html>
