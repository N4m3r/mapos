/**
 * Acoes declarativas das telas (ADR-005, CSP).
 *
 * A Content-Security-Policy nao permite handlers inline (onclick="..."), entao
 * as telas usam atributos data-* tratados aqui, sem eval:
 *
 *   data-confirmar="Mensagem?"        em link/botao (clique) ou form (envio): cancela se nao confirmar
 *   data-acao="imprimir|fechar|voltar|selecionar|esconder|clicar"  (clicar usa data-alvo="#id")
 *   data-chamar="nomeFuncao" data-args='[1, "x", "@this", "@event"]'
 *                                     chama window.nomeFuncao(...); retorno false cancela o padrao
 *   data-cancelar                     junto de data-chamar: sempre cancela o padrao (o antigo "return false")
 *   data-definir="#campo" data-valor="x"   define o valor de um campo ao clicar
 *   data-ao-mudar="enviar"            envia o formulario ao mudar (select/input)
 *   data-ao-mudar-ir="/prefixo/"      vai para prefixo + valor ao mudar
 *   data-ao-enviar="nomeFuncao"       em form: chama nomeFuncao(event); false cancela
 *   data-bloquear-envio               em form: nunca envia (formulario so de JS)
 *   data-fallback="url"               em img: troca a imagem se nao carregar
 */
(function (window, document) {
    'use strict';

    function argumentos(el, evento) {
        var bruto = el.getAttribute('data-args');
        var lista = [];
        if (bruto) {
            try { lista = JSON.parse(bruto); } catch (e) { lista = [bruto]; }
            if (!Array.isArray(lista)) { lista = [lista]; }
        }
        return lista.map(function (a) {
            if (a === '@this') { return el; }
            if (a === '@event') { return evento; }
            return a;
        });
    }

    function chamar(el, nome, evento) {
        var fn = window[nome];
        if (typeof fn !== 'function') {
            if (window.console) { console.warn('acoes.js: funcao nao encontrada:', nome); }
            return undefined;
        }
        return fn.apply(el, argumentos(el, evento));
    }

    function linkVazio(el) {
        if (el.tagName !== 'A') { return false; }
        var href = (el.getAttribute('href') || '').trim();
        return href === '' || href === '#' || href.indexOf('javascript:') === 0;
    }

    function cancelar(evento) {
        evento.preventDefault();
        evento.stopImmediatePropagation();
    }

    document.addEventListener('click', function (evento) {
        var el = evento.target.closest('[data-confirmar]:not(form), [data-acao], [data-chamar], [data-definir]');
        if (!el) { return; }

        if (el.hasAttribute('data-confirmar') && el.tagName !== 'FORM') {
            if (!window.confirm(el.getAttribute('data-confirmar'))) {
                cancelar(evento);
                return;
            }
        }

        var acao = el.getAttribute('data-acao');
        if (acao) {
            switch (acao) {
                case 'imprimir': window.print(); break;
                case 'fechar': window.close(); break;
                case 'voltar': window.history.back(); break;
                case 'selecionar': if (el.select) { el.select(); } break;
                case 'esconder': el.style.display = 'none'; break;
                case 'clicar':
                    var alvo = document.querySelector(el.getAttribute('data-alvo'));
                    if (alvo) { alvo.click(); }
                    break;
            }
            if (linkVazio(el)) { evento.preventDefault(); }
        }

        if (el.hasAttribute('data-definir')) {
            var campo = document.querySelector(el.getAttribute('data-definir'));
            if (campo) {
                campo.value = el.getAttribute('data-valor') || '';
                campo.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        if (el.hasAttribute('data-chamar')) {
            var retorno = chamar(el, el.getAttribute('data-chamar'), evento);
            if (retorno === false || linkVazio(el) || el.hasAttribute('data-cancelar')) { evento.preventDefault(); }
        }
    });

    document.addEventListener('change', function (evento) {
        var el = evento.target;
        if (!el || !el.getAttribute) { return; }
        if (el.getAttribute('data-ao-mudar') === 'enviar' && el.form) {
            el.form.submit();
        } else if (el.hasAttribute('data-ao-mudar-ir')) {
            window.location = el.getAttribute('data-ao-mudar-ir') + encodeURIComponent(el.value);
        }
    });

    document.addEventListener('submit', function (evento) {
        var form = evento.target;
        if (!form || form.tagName !== 'FORM') { return; }
        if (form.hasAttribute('data-bloquear-envio')) {
            evento.preventDefault();
            return;
        }
        if (form.hasAttribute('data-confirmar') && !window.confirm(form.getAttribute('data-confirmar'))) {
            cancelar(evento);
            return;
        }
        if (form.hasAttribute('data-ao-enviar') && chamar(form, form.getAttribute('data-ao-enviar'), evento) === false) {
            evento.preventDefault();
        }
    }, true);

    // Evento de erro de imagem nao borbulha: escuta na fase de captura
    document.addEventListener('error', function (evento) {
        var img = evento.target;
        if (img && img.tagName === 'IMG' && img.hasAttribute('data-fallback')) {
            var reserva = img.getAttribute('data-fallback');
            img.removeAttribute('data-fallback');
            img.src = reserva;
        }
    }, true);
})(window, document);
