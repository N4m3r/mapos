/*
 * Mosaico de cameras das telas de login (assets/css/login-cftv.css).
 *
 * Monta as cameras em [data-cftv-wall], atualiza o relogio do HUD, alterna a
 * camera em destaque, provoca falhas de sinal curtas e faz a camera dome
 * (.cftv-domo) seguir o ponteiro. So decoracao: nada aqui toca no formulario.
 */
(function (window, document) {
    'use strict';

    var SVG = 'http://www.w3.org/2000/svg';

    // Silhueta de pessoa com os pes na origem e caixa de deteccao
    function pessoa(rotulo) {
        return '<circle class="corpo" cx="0" cy="-14.5" r="2.6"/>' +
            '<rect class="corpo" x="-2.7" y="-11.5" width="5.4" height="11.5" rx="1.6"/>' +
            '<rect class="alvo" x="-6" y="-20" width="12" height="21"/>' +
            '<text class="alvo-txt" x="-6" y="-21.5">' + rotulo + '</text>';
    }

    function carro(rotulo) {
        return '<rect class="corpo" x="-14" y="-6" width="28" height="7" rx="2"/>' +
            '<rect class="corpo" x="-8" y="-10.5" width="15" height="5" rx="1.5"/>' +
            '<circle class="f2" cx="-8" cy="1.5" r="2.4"/><circle class="f2" cx="8" cy="1.5" r="2.4"/>' +
            '<rect class="alvo" x="-17" y="-13" width="34" height="18"/>' +
            '<text class="alvo-txt" x="-17" y="-14.5">' + rotulo + '</text>';
    }

    // Cenas em viewBox 160x90, desenhadas em perspectiva simples
    var CENAS = [
        // corredor com pessoa vindo em direcao a camera
        '<path class="l" d="M0 0 L60 32 M160 0 L100 32 M0 90 L60 58 M160 90 L100 58"/>' +
        '<rect class="f2" x="60" y="32" width="40" height="26"/>' +
        '<path class="f2" d="M14 8 L30 16 L30 70 L14 80 Z M146 8 L130 16 L130 70 L146 80 Z"/>' +
        '<rect class="luz" x="74" y="4" width="12" height="2"/><rect class="luz" x="76" y="14" width="8" height="1.6"/><rect class="luz" x="77.5" y="22" width="5" height="1.2"/>' +
        '<g class="ator vem">' + pessoa('PESSOA 97%') + '</g>',
        // estacionamento com carro passando
        '<path class="l" d="M0 38 L160 38 M38 38 L10 90 M58 38 L46 90 M78 38 L82 90 M98 38 L118 90 M118 38 L154 90"/>' +
        '<rect class="f2" x="22" y="44" width="22" height="12" rx="2"/><rect class="f2" x="96" y="44" width="22" height="12" rx="2"/>' +
        '<rect class="f2" x="0" y="10" width="160" height="22"/>' +
        '<g class="ator carro">' + carro('VEICULO') + '</g>',
        // portaria com portao e pessoa atravessando
        '<path class="l" d="M0 78 L160 78 M0 30 L160 30"/>' +
        '<path class="l" d="M50 30 V78 M56 30 V78 M62 30 V78 M68 30 V78 M74 30 V78 M80 30 V78 M86 30 V78 M92 30 V78 M98 30 V78 M104 30 V78 M110 30 V78"/>' +
        '<rect class="f2" x="120" y="22" width="30" height="56"/><rect class="luz" x="126" y="30" width="18" height="10"/>' +
        '<g class="ator passa">' + pessoa('PESSOA 92%') + '</g>',
        // deposito com prateleiras
        '<path class="l" d="M0 90 L66 40 M160 90 L94 40 M66 40 L94 40"/>' +
        '<path class="f2" d="M4 12 L50 30 L50 70 L4 86 Z M156 12 L110 30 L110 70 L156 86 Z"/>' +
        '<path class="l" d="M4 32 L50 42 M4 52 L50 54 M156 32 L110 42 M156 52 L110 54"/>' +
        '<g class="ator passa-lento">' + pessoa('PESSOA 88%') + '</g>',
        // rua com faixa animada e carro descendo
        '<path class="f2" d="M62 30 L98 30 L160 90 L0 90 Z"/>' +
        '<path class="l faixa" d="M80 30 L80 90"/>' +
        '<rect class="f2" x="4" y="6" width="34" height="38"/><rect class="f2" x="122" y="2" width="34" height="44"/>' +
        '<rect class="luz" x="10" y="12" width="6" height="5"/><rect class="luz" x="24" y="22" width="6" height="5"/><rect class="luz" x="132" y="10" width="6" height="5"/>' +
        '<g class="ator desce">' + carro('VEICULO') + '</g>',
        // recepcao com atendente parado
        '<path class="l" d="M0 64 L160 64"/>' +
        '<rect class="f2" x="118" y="18" width="24" height="46"/><circle class="luz" cx="137" cy="42" r="1.2"/>' +
        '<g class="ator balanca">' + pessoa('PESSOA 99%') + '</g>' +
        '<rect class="f2" x="40" y="50" width="80" height="20" rx="2"/>' +
        '<circle class="f2" cx="20" cy="56" r="8"/><rect class="f2" x="17" y="62" width="6" height="10"/>'
    ];

    var LOCAIS = ['Portaria', 'Estacionamento', 'Corredor A', 'Deposito', 'Rua lateral', 'Recepcao',
        'Doca', 'Corredor B', 'Patio', 'Garagem', 'Acesso norte', 'Perimetro'];
    var ORDEM_CENA = [2, 1, 0, 3, 4, 5, 1, 0, 2, 4, 3, 5];

    function dois(n) { return n < 10 ? '0' + n : String(n); }

    function agora() {
        var d = new Date();
        return dois(d.getDate()) + '/' + dois(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' +
            dois(d.getHours()) + ':' + dois(d.getMinutes()) + ':' + dois(d.getSeconds());
    }

    function montarCamera(i) {
        var cam = document.createElement('div');
        cam.className = 'cam';

        var svg = document.createElementNS(SVG, 'svg');
        svg.setAttribute('class', 'cena');
        svg.setAttribute('viewBox', '0 0 160 90');
        svg.setAttribute('preserveAspectRatio', 'xMidYMid slice');
        svg.innerHTML = '<rect class="fundo" width="160" height="90"/>' + CENAS[ORDEM_CENA[i] % CENAS.length];

        // cada camera comeca num ponto diferente da animacao
        var atraso = (-(i * 2.7) % 19).toFixed(1) + 's';
        Array.prototype.forEach.call(svg.querySelectorAll('.ator'), function (a) { a.style.animationDelay = atraso; });

        var hud = document.createElement('div');
        hud.className = 'cam-hud';
        hud.innerHTML = '<span class="tl"></span><span class="tr">REC</span><span class="bl hora"></span><span class="br">1080p 25fps</span>';
        hud.querySelector('.tl').textContent = 'CAM ' + dois(i + 1) + ' · ' + LOCAIS[i];

        cam.appendChild(svg);
        cam.appendChild(hud);
        return cam;
    }

    function iniciar() {
        var reduzir = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var mural = document.querySelector('[data-cftv-wall]');
        var cams = [];

        if (mural) {
            for (var i = 0; i < LOCAIS.length; i++) {
                cams.push(mural.appendChild(montarCamera(i)));
            }
        }

        Array.prototype.forEach.call(document.querySelectorAll('[data-cftv-total]'), function (el) {
            el.textContent = String(cams.length);
        });

        var horas = document.querySelectorAll('.cam-hud .hora');
        function tique() {
            var t = agora();
            for (var h = 0; h < horas.length; h++) { horas[h].textContent = t; }
        }
        tique();
        window.setInterval(tique, 1000);

        if (reduzir || !cams.length) {
            return;
        }

        // operador "focando" uma camera de cada vez
        var ativa = 0;
        cams[ativa].classList.add('ativa');
        window.setInterval(function () {
            cams[ativa].classList.remove('ativa');
            ativa = (ativa + 1 + Math.floor(Math.random() * 3)) % cams.length;
            cams[ativa].classList.add('ativa');
        }, 3500);

        // falha de sinal curta numa camera qualquer
        window.setInterval(function () {
            var c = cams[Math.floor(Math.random() * cams.length)];
            c.classList.add('glitch');
            window.setTimeout(function () { c.classList.remove('glitch'); }, 400);
        }, 2600);

        // camera dome segue o ponteiro em telas com mouse
        var domo = document.querySelector('.cftv-domo');
        if (domo && window.matchMedia('(hover: hover)').matches) {
            var cabeca = domo.querySelector('.cabeca');
            var fov = domo.querySelector('.fov');
            var parado;
            document.addEventListener('mousemove', function (e) {
                var r = domo.getBoundingClientRect();
                var dx = e.clientX - (r.left + r.width / 2);
                var dy = e.clientY - (r.top + r.height / 2);
                var ang = Math.atan2(dy, dx);
                var dist = Math.min(1, Math.sqrt(dx * dx + dy * dy) / 400);
                domo.classList.add('seguindo');
                cabeca.style.transform = 'translate(' + (Math.cos(ang) * 12 * dist).toFixed(1) + 'px,' +
                    (Math.max(-2, Math.sin(ang) * 8 * dist)).toFixed(1) + 'px)';
                var giro = Math.max(-60, Math.min(60, (90 - ang * 180 / Math.PI)));
                fov.style.transform = 'rotate(' + (-giro).toFixed(1) + 'deg)';
                window.clearTimeout(parado);
                parado = window.setTimeout(function () {
                    domo.classList.remove('seguindo');
                    cabeca.style.transform = '';
                    fov.style.transform = '';
                }, 2500);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})(window, document);
