<?php
$rotulos = ['novo' => 'Novo', 'atualizar' => 'Atualizar', 'pular' => 'Pular', 'erro' => 'Erro'];
$cores = ['novo' => '#2e7d32', 'atualizar' => '#1565c0', 'pular' => '#757575', 'erro' => '#c62828'];
$gravaveis = $contagem['novo'] + $contagem['atualizar'];
?>
<div class="widget-box">
    <div class="widget-title"><span class="icon"><i class="bx bx-upload"></i></span><h5>Importar pontos: prévia (<?= html_escape($obra->nome) ?>)</h5></div>
    <div class="widget-content">
        <p>
            <strong><?= (int) $contagem['novo'] ?></strong> novo(s),
            <strong><?= (int) $contagem['atualizar'] ?></strong> a atualizar,
            <strong><?= (int) $contagem['pular'] ?></strong> a pular,
            <strong><?= (int) $contagem['erro'] ?></strong> com erro (linhas com erro não serão importadas).
        </p>
        <form action="<?= site_url('obraimportar/confirmar') ?>" method="post">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
            <input type="hidden" name="obra_id" value="<?= (int) $obra->idObra ?>">
            <input type="hidden" name="token" value="<?= html_escape($this->session->userdata('imp_pontos')['token'] ?? '') ?>">
            <input type="hidden" name="existentes" value="<?= html_escape($modo) ?>">
            <a href="<?= site_url('obras/visualizar/' . (int) $obra->idObra . '#pontos') ?>" class="button btn btn-warning"><span class="button__text2">Cancelar</span></a>
            <?php if ($gravaveis > 0): ?>
                <button class="button btn btn-success"><span class="button__icon"><i class="bx bx-check"></i></span><span class="button__text2">Confirmar importação de <?= (int) $gravaveis ?> ponto(s)</span></button>
            <?php endif; ?>
        </form>
        <div style="overflow-x:auto;margin-top:12px">
            <table class="table table-bordered table-condensed">
                <thead><tr><th>Linha</th><th>Situação</th><th>Identificação</th><th>Tipo</th><th>Local</th><th>IP</th><th>Canal</th><th>Porta</th><th>Modelo</th><th>Série</th><th>Motivo</th></tr></thead>
                <tbody>
                <?php foreach ($linhas as $l): $d = $l['dados']; ?>
                    <tr>
                        <td><?= (int) $l['_n'] ?></td>
                        <td><strong style="color:<?= $cores[$l['status']] ?>"><?= html_escape($rotulos[$l['status']]) ?></strong></td>
                        <td><?= html_escape($d['identificacao']) ?></td>
                        <td><?= html_escape($tipos[$d['tipo']]['rotulo'] ?? $d['tipo']) ?></td>
                        <td><?= html_escape($d['local']) ?></td>
                        <td><?= html_escape($d['ip']) ?></td>
                        <td><?= html_escape($d['canal']) ?></td>
                        <td><?= html_escape($d['porta']) ?></td>
                        <td><?= html_escape($d['modelo']) ?></td>
                        <td><?= html_escape($d['serie']) ?></td>
                        <td><?= html_escape($l['motivo']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
