<?php
$this->load->view('tecnico/_topo', [
    'titulo'       => 'Meu perfil',
    'header_icone' => 'bx-user',
    'header_sub'   => 'Seus dados e atalhos',
]);
$this->load->helper('menu_tecnico');

$menu = menu_tecnico_dados(true);
$campo = function ($v) { return trim((string) $v) !== '' ? html_escape((string) $v) : '<span class="tec-vazio">não informado</span>'; };
$pode_rh = $this->permission->checkPermission($this->session->userdata('permissao'), 'vAreaColaborador') && $colab;
?>
<div class="tec-container">
    <?php if ($this->session->flashdata('success')): ?>
        <div class="info-card" role="status" style="border-left:4px solid var(--tec-success);color:var(--tec-success)"><i class='bx bx-check-circle' aria-hidden="true"></i> <?= html_escape($this->session->flashdata('success')) ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="info-card" role="alert" style="border-left:4px solid var(--tec-danger);color:var(--tec-danger)"><i class='bx bx-error-circle' aria-hidden="true"></i> <?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <div class="info-card tec-perfil-topo">
        <?php if ($menu['foto_url']): ?>
            <img class="tec-menu-avatar" src="<?= html_escape($menu['foto_url']) ?>" alt="Sua foto">
        <?php else: ?>
            <span class="tec-menu-avatar tec-menu-iniciais" aria-hidden="true"><?= html_escape($menu['iniciais']) ?></span>
        <?php endif; ?>
        <div>
            <h2 style="margin:0;font-size:18px"><?= html_escape($usuario->nome ?? $menu['nome']) ?></h2>
            <?php if ($menu['cargo'] !== ''): ?><div style="font-size:14px"><?= html_escape($menu['cargo']) ?></div><?php endif; ?>
            <div style="font-size:13px;word-break:break-all"><?= html_escape($usuario->email ?? '') ?></div>
        </div>
    </div>

    <h2 class="tec-section-title"><i class='bx bx-bar-chart-alt-2' aria-hidden="true"></i> Neste mês (<?= html_escape($mes_ref) ?>)</h2>
    <div class="tec-stats">
        <div class="info-card" style="text-align:center"><div style="font-size:26px;font-weight:700"><?= (int) $os_mes ?></div><div style="font-size:13px">OS atendidas</div></div>
        <div class="info-card" style="text-align:center"><div style="font-size:26px;font-weight:700"><?= (int) $pontos_mes ?></div><div style="font-size:13px">Pontos avançados</div></div>
    </div>

    <h2 class="tec-section-title"><i class='bx bx-id-card' aria-hidden="true"></i> Trabalho</h2>
    <div class="info-card">
        <?php if ($colab): ?>
        <ul class="tec-perfil-lista">
            <li><span>Função / cargo</span><span><?= $campo($colab->cargo ?? '') ?></span></li>
            <li><span>Unidade</span><span><?= $campo($colab->nome_unidade ?? '') ?></span></li>
            <li><span>Jornada</span><span><?= $campo($colab->nome_jornada ?? '') ?></span></li>
        </ul>
        <?php else: ?>
            <p style="margin:0;font-size:14px">Seu usuário ainda não está vinculado a um cadastro de RH. Peça ao administrador.</p>
        <?php endif; ?>
        <h3 style="font-size:14px;margin:14px 0 6px">Equipes de obra</h3>
        <?php if (!empty($equipes)): ?>
            <ul class="tec-perfil-lista">
            <?php foreach ($equipes as $eq): ?>
                <li><span><?= html_escape($eq->nome) ?></span><span><?= !empty($eq->encarregado) ? 'Encarregado' : 'Membro' ?></span></li>
            <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="margin:0;font-size:14px">Você não está em nenhuma equipe ativa.</p>
        <?php endif; ?>
    </div>

    <h2 class="tec-section-title"><i class='bx bx-phone' aria-hidden="true"></i> Contato</h2>
    <form class="info-card tec-perfil-form" method="post" action="<?= site_url('tecnico/salvar_perfil') ?>">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
        <label for="perfil-telefone">Telefone</label>
        <input type="tel" id="perfil-telefone" name="telefone" inputmode="tel" autocomplete="tel" maxlength="20" placeholder="(11) 3456-7890" value="<?= html_escape(menu_tecnico_telefone($usuario->telefone ?? '')['valor']) ?>">
        <label for="perfil-celular">Celular</label>
        <input type="tel" id="perfil-celular" name="celular" inputmode="tel" autocomplete="tel" maxlength="20" placeholder="(11) 91234-5678" value="<?= html_escape(menu_tecnico_telefone($usuario->celular ?? '')['valor']) ?>">
        <p style="font-size:12px;margin:8px 0 0">Nome, e-mail e dados de RH só podem ser alterados pelo administrador ou pelo RH.</p>
        <button type="submit" class="tec-btn-salvar">Salvar contato</button>
    </form>

    <h2 class="tec-section-title"><i class='bx bx-cog' aria-hidden="true"></i> Atalhos</h2>
    <div class="info-card tec-perfil-atalhos">
        <a href="<?= site_url('mapos/minhaConta') ?>"><i class='bx bx-lock-alt' aria-hidden="true"></i> Alterar senha (Minha conta)</a>
        <?php if ($pode_rh): ?>
        <a href="<?= site_url('colaborador/meusDados') ?>"><i class='bx bx-id-card' aria-hidden="true"></i> Meus dados de RH</a>
        <?php endif; ?>
    </div>
</div>

<?php $this->load->view('tecnico/_nav', ['nav_ativo' => '', 'pode_ver_sistema' => isset($pode_ver_sistema) ? $pode_ver_sistema : false]); ?>
</body>
</html>
