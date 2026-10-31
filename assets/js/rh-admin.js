/* rh-admin.js - mascaras de campos do cadastro de colaborador (RH).
 * Depende de jQuery e jquery.mask.min.js. Uso: data-mascara="cpf|celular|pis|cep". */
(function ($) {
    'use strict';
    if (!$ || !$.fn || !$.fn.mask) { return; }

    $(function () {
        $('[data-mascara="cpf"]').mask('000.000.000-00');
        $('[data-mascara="pis"]').mask('000.00000.00-0');
        $('[data-mascara="cep"]').mask('00000-000');

        // Telefone com 8 ou 9 digitos (fixo ou celular)
        var maskTelefone = function (val) {
            return val.replace(/\D/g, '').length > 10 ? '(00) 00000-0000' : '(00) 0000-00009';
        };
        $('[data-mascara="celular"]').mask(maskTelefone, {
            onKeyPress: function (val, e, field, options) {
                field.mask(maskTelefone.apply({}, arguments), options);
            }
        });
    });
}(window.jQuery));
