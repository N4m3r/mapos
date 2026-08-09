<?php
/**
 * Aviso da Área do Colaborador quando o acesso não pode ser concluído
 * (RH não ativado ou usuário sem cadastro de colaborador vinculado).
 *
 * Renderizado inline em vez de redirecionar para base_url(): como o painel
 * principal (Mapos) reencaminha o perfil "colaborador" de volta para cá,
 * um redirect aqui geraria loop (ERR_TOO_MANY_REDIRECTS).
 */
$titulo = isset($titulo) ? $titulo : 'Área do Colaborador';
$mensagem = isset($mensagem) ? $mensagem : 'Não foi possível abrir a Área do Colaborador.';
// return=true para que o cabeçalho entre no buffer desta view (capturada como
// string pelo controller antes do exit), e não no buffer de saída do CI.
echo $this->load->view('colaborador/_topo', ['titulo' => $titulo, 'header_icone' => 'bxs-lock-alt'], true);
?>
    <main class="tec-main">
        <div class="rh-card" style="max-width:520px;margin:24px auto;text-align:center;">
            <i class='bx bxs-error-circle' style="font-size:56px;color:#e0a800;"></i>
            <h2 style="margin:12px 0 8px;">Acesso indisponível</h2>
            <p style="color:#555;line-height:1.5;"><?= html_escape($mensagem) ?></p>
            <a href="<?= site_url('login/sair') ?>" class="btn btn-primary" style="margin-top:16px;">
                <i class='bx bx-log-out'></i> Sair
            </a>
        </div>
    </main>
</body>
</html>
