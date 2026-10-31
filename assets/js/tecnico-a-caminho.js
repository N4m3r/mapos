/* "Estou a caminho": avisa o cliente por WhatsApp (POST tecnico/a_caminho).
 * Sem internet nao enfileira: avisa o tecnico que o cliente nao foi avisado.
 * O servidor e a fonte de verdade da anti-repeticao; o localStorage so evita
 * mostrar o botao de novo neste aparelho. */
(function () {
    'use strict';
    var URL_ENVIO = (document.currentScript && document.currentScript.getAttribute('data-url')) || '/tecnico/a_caminho';

    function chave(osId) { return 'tecACaminho:' + osId; }

    function lerAviso(osId) {
        try {
            var v = JSON.parse(localStorage.getItem(chave(osId)) || 'null');
            // Vale so no dia em que foi enviado.
            if (v && v.dia === new Date().toDateString()) { return v; }
        } catch (e) { /* ignora */ }
        return null;
    }

    function gravarAviso(osId, hora) {
        try { localStorage.setItem(chave(osId), JSON.stringify({ hora: hora, dia: new Date().toDateString() })); } catch (e) { /* ignora */ }
    }

    function cookie(nome) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + nome.replace(/[.$?*|{}()[\]\\/+^]/g, '\\$&') + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : '';
    }

    function enviar(osId, cb) {
        if (!navigator.onLine) {
            cb({ success: false, message: 'Sem internet — o cliente não foi avisado.' });
            return;
        }
        var nomeToken = (document.querySelector('meta[name="csrf-token-name"]') || {}).content || 'MAPOS_TOKEN';
        var nomeCookie = (document.querySelector('meta[name="csrf-cookie-name"]') || {}).content || 'MAPOS_COOKIE';
        var fd = new FormData();
        fd.append('os_id', osId);
        fd.append(nomeToken, cookie(nomeCookie));

        fetch(URL_ENVIO, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (j) {
            cb(j || { success: false, message: 'Não foi possível avisar o cliente.' });
        }).catch(function () {
            cb({ success: false, message: 'Sem internet — o cliente não foi avisado.' });
        });
    }

    // Clique em qualquer [data-a-caminho="<osId>"] (assistente da OS e cartao do inicio)
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('[data-a-caminho]') : null;
        if (!btn || btn.disabled) { return; }
        var osId = btn.getAttribute('data-a-caminho');
        var rotulo = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = 'Avisando...';
        enviar(osId, function (j) {
            if (j.success) {
                var hora = j.hora || new Date().toTimeString().slice(0, 5);
                gravarAviso(osId, hora);
                document.dispatchEvent(new CustomEvent('tec:aCaminho', { detail: { osId: osId, hora: hora } }));
                var msg = document.createElement('span');
                msg.className = 'tec-a-caminho-ok';
                msg.setAttribute('role', 'status');
                msg.textContent = 'Cliente avisado às ' + hora;
                if (btn.parentNode) { btn.parentNode.replaceChild(msg, btn); }
            } else {
                btn.disabled = false;
                btn.innerHTML = rotulo;
                window.alert(j.message || 'Não foi possível avisar o cliente.');
            }
        });
    });

    window.TecACaminho = { lerAviso: lerAviso };

    // Cartao do inicio: se ja avisou hoje neste aparelho, troca o botao pelo texto.
    document.addEventListener('DOMContentLoaded', function () {
        var btns = document.querySelectorAll('[data-a-caminho]');
        for (var i = 0; i < btns.length; i++) {
            var a = lerAviso(btns[i].getAttribute('data-a-caminho'));
            if (a) {
                var s = document.createElement('span');
                s.className = 'tec-a-caminho-ok';
                s.textContent = 'Cliente avisado às ' + a.hora;
                btns[i].parentNode.replaceChild(s, btns[i]);
            }
        }
    });
})();
