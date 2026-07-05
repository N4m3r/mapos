<?php
/**
 * Script de teste para verificar se as assinaturas estão sendo salvas corretamente
 * Acesse: http://seu-site/teste_assinatura.php
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste de Assinatura</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        canvas { border: 2px solid #333; background: #f5f5f5; }
        .resultado { margin-top: 20px; padding: 10px; border: 1px solid #ccc; }
        .erro { color: red; }
        .sucesso { color: green; }
        img { border: 1px solid #ccc; margin-top: 10px; max-width: 300px; }
    </style>
</head>
<body>
    <h1>Teste de Assinatura - Canvas</h1>
    <p>Desenhe algo no canvas abaixo:</p>
    <canvas id="canvas" width="400" height="200"></canvas>
    <br><br>
    <button onclick="obterBase64()">1. Obter Base64</button>
    <button onclick="testarEnvio()">2. Testar Envio</button>
    <button onclick="limpar()">Limpar</button>

    <div id="resultado" class="resultado"></div>

    <script>
        var canvas = document.getElementById('canvas');
        var ctx = canvas.getContext('2d');
        var desenhando = false;

        // Configurar canvas
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';

        // Eventos do mouse
        canvas.addEventListener('mousedown', function(e) {
            desenhando = true;
            ctx.beginPath();
            ctx.moveTo(e.offsetX, e.offsetY);
        });

        canvas.addEventListener('mousemove', function(e) {
            if (desenhando) {
                ctx.lineTo(e.offsetX, e.offsetY);
                ctx.stroke();
            }
        });

        canvas.addEventListener('mouseup', function() {
            desenhando = false;
        });

        canvas.addEventListener('mouseleave', function() {
            desenhando = false;
        });

        function obterBase64() {
            var base64 = canvas.toDataURL('image/png');
            document.getElementById('resultado').innerHTML =
                '<h3>Base64 gerado:</h3>' +
                '<p>Tamanho: ' + base64.length + ' caracteres</p>' +
                '<p>Primeiros 100 caracteres:</p>' +
                '<textarea style="width:100%;height:80px;">' + base64.substring(0, 100) + '...</textarea>' +
                '<h3>Preview da imagem:</h3>' +
                '<img src="' + base64 + '">';
        }

        function testarEnvio() {
            // Criar canvas temporário com fundo branco
            var tempCanvas = document.createElement('canvas');
            tempCanvas.width = canvas.width;
            tempCanvas.height = canvas.height;
            var tempCtx = tempCanvas.getContext('2d');

            // Fundo branco
            tempCtx.fillStyle = '#FFFFFF';
            tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);

            // Desenhar assinatura
            tempCtx.drawImage(canvas, 0, 0);

            var base64 = tempCanvas.toDataURL('image/png');

            document.getElementById('resultado').innerHTML =
                '<h3>Teste de conversão:</h3>' +
                '<p><strong>Método 1 - Canvas original (com transparência):</strong></p>' +
                '<img src="' + canvas.toDataURL('image/png') + '">' +
                '<p><strong>Método 2 - Canvas temporário (com fundo branco):</strong></p>' +
                '<img src="' + base64 + '">';
        }

        function limpar() {
            ctx.fillStyle = '#f5f5f5';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            document.getElementById('resultado').innerHTML = '';
        }
    </script>
</body>
</html>
