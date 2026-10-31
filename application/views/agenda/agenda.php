<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/fullcalendar.min.js"></script>
<script nonce="<?= csp_nonce() ?>" src="<?= base_url() ?>assets/js/fullcalendar/locales/pt-br.js"></script>
<link href="<?= base_url() ?>assets/css/fullcalendar.min.css" rel="stylesheet" />
<style>
    #agendaEquipe { background:#fff; padding:10px; border-radius:8px; }
    .agenda-concluida { opacity:.55; text-decoration:line-through; }
    .agenda-legenda span { display:inline-flex; align-items:center; gap:6px; margin:0 14px 6px 0; font-size:12px; }
    .agenda-legenda i { width:12px; height:12px; border-radius:3px; display:inline-block; }
    #agendaAviso { display:none; position:fixed; right:20px; bottom:20px; z-index:2000; padding:10px 14px; border-radius:8px; color:#fff; }
</style>

<div class="ui-cabecalho">
    <div>
        <h1 class="ui-titulo"><i class="bx bx-calendar"></i> Agenda da equipe</h1>
        <p class="ui-subtitulo">Visão semanal das OS de cada técnico. Arraste uma OS para outro horário, quando permitido.</p>
    </div>
    <div class="ui-acoes">
        <a href="<?= site_url('os/atribuir') ?>" class="button btn btn-success"><span class="button__icon"><i class="bx bx-user-plus"></i></span><span class="button__text2">Atribuir / agendar OS</span></a>
        <a href="<?= site_url('sla') ?>" class="button btn btn-info"><span class="button__icon"><i class="bx bx-timer"></i></span><span class="button__text2">Painel de SLA</span></a>
    </div>
</div>
<div class="widget-box">
    <div class="widget-content">
        <?php if (! $disponivel) { ?>
            <div class="ui-dica"><i class="bx bx-error-circle"></i><div>O campo de agendamento ainda não existe no banco. Rode as migrations.</div></div>
        <?php } ?>
        <div class="ui-filtros">
            <div class="ui-campo">
                <label for="filtroTecnico">Técnico</label>
                <select id="filtroTecnico">
                    <option value="">Todos</option>
                    <?php foreach ($tecnicos as $t) { ?><option value="<?= (int) $t->idUsuarios ?>"><?= html_escape($t->nome) ?></option><?php } ?>
                </select>
            </div>
            <?php if ($podeEditar) { ?><div class="ui-campo-acao"><span class="ui-selo info"><i class="bx bx-move"></i> Arraste uma OS para reagendar</span></div><?php } ?>
        </div>
        <div class="agenda-legenda">
            <?php foreach ($tecnicos as $t) { ?><span><i style="background:<?= $t->cor ?>"></i><?= html_escape($t->nome) ?></span><?php } ?>
            <span><i style="background:#95a5a6"></i>Sem técnico</span>
        </div>

        <div class="row-fluid">
            <div class="span9"><div id="agendaEquipe"></div></div>
            <div class="span3">
                <div class="ui-secao"><h4 style="margin-top:0">Com técnico e sem data</h4></div>
                <?php if (empty($semData)) { ?>
                    <div class="ui-vazio" style="padding:16px 8px"><i class="bx bx-check-circle"></i><strong>Nenhuma OS</strong>Todas as OS com técnico já têm data.</div>
                <?php } else { ?>
                    <ul class="unstyled">
                        <?php foreach ($semData as $os) { ?>
                            <li style="padding:6px 0; border-bottom:1px solid #eee">
                                <a href="<?= site_url('os/visualizar/' . (int) $os->idOs) ?>">#<?= (int) $os->idOs ?></a> <?= html_escape($os->nomeCliente) ?><br>
                                <small style="color:#777"><?= html_escape($os->tecnico) ?> · <?= html_escape($os->status) ?></small>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<div id="agendaAviso"></div>

<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function () {
    var aviso = function (texto, ok) {
        var el = document.getElementById('agendaAviso');
        el.textContent = texto;
        el.style.background = ok ? '#27ae60' : '#c0392b';
        el.style.display = 'block';
        clearTimeout(el._t);
        el._t = setTimeout(function () { el.style.display = 'none'; }, 3500);
    };
    var doisDigitos = function (n) { return (n < 10 ? '0' : '') + n; };
    var formatar = function (d) {
        return d.getFullYear() + '-' + doisDigitos(d.getMonth() + 1) + '-' + doisDigitos(d.getDate()) + ' ' + doisDigitos(d.getHours()) + ':' + doisDigitos(d.getMinutes());
    };
    var filtro = document.getElementById('filtroTecnico');

    var calendario = new FullCalendar.Calendar(document.getElementById('agendaEquipe'), {
        locale: 'pt-br',
        initialView: window.innerWidth < 768 ? 'listWeek' : 'timeGridWeek',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: false,
        nowIndicator: true,
        height: 'auto',
        editable: <?= $podeEditar ? 'true' : 'false' ?>,
        eventDurationEditable: false,
        events: {
            url: '<?= site_url('agenda/eventos') ?>',
            extraParams: function () { return { tecnico: filtro.value }; },
            failure: function () { aviso('Não foi possível carregar a agenda.', false); }
        },
        eventDidMount: function (info) {
            var p = info.event.extendedProps;
            info.el.title = info.event.title + '\n' + p.tecnico + ' · ' + p.status + (p.local ? '\n' + p.local : '');
        },
        eventDrop: function (info) {
            $.ajax({
                url: '<?= site_url('agenda/mover') ?>',
                type: 'POST',
                dataType: 'json',
                data: { os_id: info.event.id, inicio: formatar(info.event.start) }
            }).done(function (r) {
                aviso(r.mensagem, r.result);
                if (!r.result) { info.revert(); }
            }).fail(function (xhr) {
                var r = xhr.responseJSON || {};
                aviso(r.mensagem || 'Erro ao reagendar.', false);
                info.revert();
            });
        }
    });
    calendario.render();
    filtro.addEventListener('change', function () { calendario.refetchEvents(); });
});
</script>
