/*
 * Registra na hora os plugins jQuery do Bootstrap 5 ($.fn.modal, .tooltip, .tab...).
 * O bundle so faz isso no DOMContentLoaded; com jQuery 1.12 os $(document).ready das
 * views rodam antes e chamariam .modal() ainda indefinido. Carregar logo depois de
 * assets/js/bootstrap.bundle.min.js.
 */
(function ($, bs) {
    if (!$ || !bs) {
        return;
    }
    ['Alert', 'Button', 'Carousel', 'Collapse', 'Dropdown', 'Modal', 'Offcanvas', 'Popover', 'ScrollSpy', 'Tab', 'Toast', 'Tooltip'].forEach(function (nome) {
        var Componente = bs[nome];
        if (!Componente || typeof Componente.jQueryInterface !== 'function') {
            return;
        }
        var plugin = Componente.NAME;
        if (!$.fn[plugin] || !$.fn[plugin].Constructor || $.fn[plugin].Constructor !== Componente) {
            $.fn[plugin] = Componente.jQueryInterface;
            $.fn[plugin].Constructor = Componente;
        }
    });
})(window.jQuery, window.bootstrap);
