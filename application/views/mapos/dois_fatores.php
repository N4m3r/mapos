<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon"><i class="fas fa-shield-alt"></i></span>
        <h5>Ativar verificação em duas etapas</h5>
    </div>
    <div class="widget-content">
        <div class="row-fluid">
            <div class="span6">
                <h5>1. Cadastre no aplicativo autenticador</h5>
                <p>Abra o Google Authenticator, Microsoft Authenticator, Authy ou outro aplicativo compatível e leia o QR code:</p>
                <img src="<?= $qrCode ?>" alt="QR code da verificação em duas etapas" style="width:220px;height:220px;border:1px solid #e2e6f0;border-radius:8px;padding:8px;background:#fff">
                <p style="margin-top:10px">Se não conseguir ler, digite esta chave no aplicativo:<br>
                    <code style="font-size:15px;letter-spacing:1px"><?= html_escape($segredo) ?></code></p>
                <p><a href="<?= site_url('mapos/doisFatores?novo=1') ?>">Gerar outra chave</a></p>
            </div>
            <div class="span6">
                <h5>2. Confirme com o código gerado</h5>
                <p>Digite o código de 6 dígitos que o aplicativo mostra agora.</p>
                <form action="<?= site_url('mapos/doisFatoresConfirmar') ?>" method="post">
                    <input type="text" name="codigo" inputmode="numeric" autocomplete="one-time-code" maxlength="7" placeholder="000000" required
                           style="font-size:22px;letter-spacing:6px;text-align:center;width:180px;height:auto">
                    <div style="margin-top:10px">
                        <button class="button btn btn-success" type="submit"><span class="button__icon"><i class="bx bx-check"></i></span><span class="button__text2">Ativar</span></button>
                        <a href="<?= site_url('mapos/minhaConta') ?>" class="button btn btn-warning"><span class="button__icon"><i class="bx bx-x"></i></span><span class="button__text2">Cancelar</span></a>
                    </div>
                </form>
                <div class="alert alert-info" style="margin-top:16px">
                    Depois de ativar, o login vai pedir o código do aplicativo além da senha.
                    Você receberá códigos de recuperação para usar se perder o celular.
                </div>
            </div>
        </div>
    </div>
</div>
