/* Visualizador de anexos: navegacao anterior/proximo, swipe, setas, duplo toque para zoom.
   Requer #modal-anexo com data-excluir-url e data-download-url (terminadas em /). */
(function ($) {
    'use strict';
    var items = [], idx = 0, zoomed = false, lastTap = 0, sx = 0, sy = 0, st = 0;
    var $m, $box;

    function init() {
        $m = $('#modal-anexo');
        if (!$m.length || $m.data('av')) { return; }
        $m.data('av', 1);
        $box = $('#div-visualizar-anexo');
        $box.after('<button type="button" class="btn av-nav av-prev" aria-label="Anterior">&#8249;</button>' +
            '<button type="button" class="btn av-nav av-next" aria-label="Próximo">&#8250;</button>');
        $m.find('.modal-footer').prepend('<span class="av-count"></span>');
        $box.parent().css('position', 'relative');
        $m.on('click', '.av-prev', function () { go(-1); });
        $m.on('click', '.av-next', function () { go(1); });
        $m.on('dblclick', 'img.av-img', toggleZoom);
        $m.on('hidden', function () { zoomed = false; $box.removeClass('av-zoom'); });
        $m.on('touchstart', '#div-visualizar-anexo', function (e) {
            var t = e.originalEvent.touches[0];
            if (e.originalEvent.touches.length > 1) { return; }
            sx = t.clientX; sy = t.clientY; st = Date.now();
        });
        $m.on('touchend', '#div-visualizar-anexo', function (e) {
            var t = e.originalEvent.changedTouches[0], dx = t.clientX - sx, dy = t.clientY - sy, now = Date.now();
            if (Math.abs(dx) < 10 && Math.abs(dy) < 10 && $(e.target).is('img.av-img')) {
                if (now - lastTap < 300) { toggleZoom(); lastTap = 0; e.preventDefault(); return; }
                lastTap = now; return;
            }
            if (!zoomed && Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5 && now - st < 800) { go(dx < 0 ? 1 : -1); }
        });
        $(document).on('keydown', function (e) {
            if (!$m.hasClass('in')) { return; }
            if (e.which === 37) { go(-1); } else if (e.which === 39) { go(1); }
        });
    }

    function toggleZoom() {
        zoomed = !zoomed;
        $box.toggleClass('av-zoom', zoomed);
    }

    function go(d) {
        var n = idx + d;
        if (n < 0 || n >= items.length) { return; }
        show(n);
    }

    function show(i) {
        idx = i; zoomed = false; $box.removeClass('av-zoom').scrollTop(0).scrollLeft(0);
        var $a = items[i], link = $a.attr('link'), id = $a.attr('imagem');
        var dl = $m.data('download-url') + id;
        var isFile = /icon-file\.png/.test(link);
        if (isFile) {
            $box.html('<div class="av-file"><img class="av-icon" src="' + link + '" alt=""><div><a class="btn btn-large btn-primary" target="_blank" rel="noopener" href="' + dl + '">Abrir arquivo</a></div></div>');
        } else {
            $box.html('<img class="av-img" src="' + link + '" alt="">');
        }
        $('#excluir-anexo').attr('link', $m.data('excluir-url') + id);
        $('#download').attr('href', dl);
        $m.find('.av-prev').toggle(i > 0);
        $m.find('.av-next').toggle(i < items.length - 1);
        $m.find('.av-count').text(items.length > 1 ? (i + 1) + ' / ' + items.length : '');
    }

    $(document).on('click', '.anexo', function (e) {
        e.preventDefault();
        init();
        items = [];
        var cur = $(this);
        $('.anexo[imagem]').each(function () { items.push($(this)); });
        var i = 0;
        $.each(items, function (k, $a) { if ($a[0] === cur[0]) { i = k; } });
        show(i);
    });
})(jQuery);
