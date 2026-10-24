/**
 * Fila offline da area do tecnico (ADR-005, F4).
 *
 * Quando o envio de foto, o inicio ou a finalizacao do atendimento falha por falta de
 * internet, os dados (inclusive fotos e assinaturas em base64) ficam guardados no aparelho
 * (IndexedDB) e sao reenviados em ordem quando a conexao volta. Cada item leva
 * "registrado_em" (hora em UTC de quando foi feito), que o servidor usa no lugar da hora
 * do envio.
 *
 * Uso:
 *   FilaOffline.semConexao(xhr)                     -> true se a falha foi de rede
 *   FilaOffline.guardar(url, campos, 'Foto da OS 12') -> Promise
 *   FilaOffline.sincronizar()                       -> Promise (chamado sozinho ao voltar a internet)
 */
(function (window, document) {
    'use strict';

    var BANCO = 'mapos_tecnico';
    var LOJA = 'pendentes';
    var sincronizando = false;

    function abrir() {
        return new Promise(function (ok, erro) {
            var req = window.indexedDB.open(BANCO, 1);
            req.onupgradeneeded = function () {
                req.result.createObjectStore(LOJA, { keyPath: 'id', autoIncrement: true });
            };
            req.onsuccess = function () { ok(req.result); };
            req.onerror = function () { erro(req.error); };
        });
    }

    function transacao(modo, acao) {
        return abrir().then(function (db) {
            return new Promise(function (ok, erro) {
                var tx = db.transaction(LOJA, modo);
                var resultado = acao(tx.objectStore(LOJA));
                tx.oncomplete = function () { ok(resultado && resultado.result !== undefined ? resultado.result : resultado); };
                tx.onerror = function () { erro(tx.error); };
            });
        });
    }

    function listar() {
        return abrir().then(function (db) {
            return new Promise(function (ok, erro) {
                var req = db.transaction(LOJA, 'readonly').objectStore(LOJA).getAll();
                req.onsuccess = function () { ok(req.result || []); };
                req.onerror = function () { erro(req.error); };
            });
        });
    }

    function cookie(nome) {
        var m = document.cookie.match(new RegExp('(^| )' + nome + '=([^;]+)'));
        return m ? decodeURIComponent(m[2]) : '';
    }

    function usuarioAtual() {
        var meta = document.querySelector('meta[name="usuario-id"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // So os itens de quem esta logado (aparelho compartilhado entre tecnicos)
    function listarDoUsuario() {
        var usuario = usuarioAtual();
        return listar().then(function (itens) {
            return itens.filter(function (i) { return !usuario || !i.usuario || i.usuario === usuario; });
        });
    }

    function csrf() {
        var nome = document.querySelector('meta[name="csrf-token-name"]');
        var biscoito = document.querySelector('meta[name="csrf-cookie-name"]');
        return {
            nome: nome ? nome.getAttribute('content') : '',
            valor: biscoito ? cookie(biscoito.getAttribute('content')) : ''
        };
    }

    // ---------------------------------------------------------------- aviso na tela
    function aviso() {
        var el = document.getElementById('fila-offline-aviso');
        if (!el) {
            el = document.createElement('div');
            el.id = 'fila-offline-aviso';
            el.style.cssText = 'position:fixed;left:12px;right:12px;bottom:84px;z-index:3000;display:none;' +
                'background:#2d3436;color:#fff;border-radius:10px;padding:10px 14px;font-size:14px;' +
                'box-shadow:0 6px 18px rgba(0,0,0,.25);align-items:center;gap:10px;justify-content:space-between';
            el.innerHTML = '<span id="fila-offline-texto"></span>' +
                '<button type="button" id="fila-offline-botao" style="background:#00b894;color:#fff;border:0;border-radius:6px;padding:6px 10px;font-weight:600">Enviar agora</button>';
            document.body.appendChild(el);
            document.getElementById('fila-offline-botao').addEventListener('click', function () {
                FilaOffline.sincronizar(true);
            });
        }
        return el;
    }

    function atualizarAviso(textoExtra) {
        return listarDoUsuario().then(function (itens) {
            var el = aviso();
            var comErro = itens.filter(function (i) { return i.erro; }).length;
            if (!itens.length) {
                el.style.display = 'none';
                return itens;
            }
            var texto = itens.length + (itens.length > 1 ? ' registros guardados' : ' registro guardado') + ' no aparelho';
            texto += navigator.onLine ? '' : ' (sem internet)';
            if (comErro) {
                texto += ' - ' + comErro + ' com erro: ' + itens.filter(function (i) { return i.erro; })[0].erro;
            }
            document.getElementById('fila-offline-texto').textContent = textoExtra || texto;
            document.getElementById('fila-offline-botao').style.display = navigator.onLine ? '' : 'none';
            el.style.display = 'flex';
            return itens;
        }).catch(function () { return []; });
    }

    // ---------------------------------------------------------------- envio
    function enviar(item) {
        var dados = new FormData();
        Object.keys(item.campos).forEach(function (k) {
            if (item.campos[k] !== null && item.campos[k] !== undefined) {
                dados.append(k, item.campos[k]);
            }
        });
        dados.append('registrado_em', item.registrado_em);
        var token = csrf();
        if (token.nome && token.valor) {
            dados.set(token.nome, token.valor);
        }

        return fetch(item.url, {
            method: 'POST',
            body: dados,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (resp) {
            return resp.text().then(function (texto) {
                var json = null;
                try { json = JSON.parse(texto); } catch (e) { /* resposta nao-JSON */ }
                return { status: resp.status, json: json, redirecionou: resp.redirected };
            });
        });
    }

    var FilaOffline = {
        semConexao: function (xhr) {
            return !navigator.onLine || !xhr || xhr.status === 0;
        },

        guardar: function (url, campos, descricao) {
            var item = {
                url: url,
                campos: campos,
                descricao: descricao || '',
                registrado_em: new Date().toISOString(),
                usuario: usuarioAtual(),
                erro: null
            };
            return transacao('readwrite', function (loja) { return loja.add(item); }).then(function () {
                atualizarAviso();
                if ('serviceWorker' in navigator && navigator.serviceWorker.ready) {
                    navigator.serviceWorker.ready.then(function (reg) {
                        if (reg.sync) { reg.sync.register('fila-offline').catch(function () {}); }
                    }).catch(function () {});
                }
            });
        },

        quantidade: function () {
            return listarDoUsuario().then(function (itens) { return itens.length; });
        },

        /**
         * Envia os itens em ordem. Para no primeiro erro de rede (tenta de novo depois).
         * Resposta do servidor com erro (ex.: OS ja finalizada) fica marcada no item, sem travar a fila.
         */
        sincronizar: function (manual) {
            if (sincronizando || !navigator.onLine) {
                return Promise.resolve(false);
            }
            sincronizando = true;
            var enviados = 0;

            var proximo = function (itens) {
                var item = itens.shift();
                if (!item) {
                    return true;
                }
                if (item.erro && !manual) {
                    return proximo(itens);
                }
                return enviar(item).then(function (r) {
                    if (r.status === 0 || r.status >= 500) {
                        throw new Error('rede');
                    }
                    if (r.status === 403 || r.redirecionou || !r.json) {
                        // Sessao expirada ou token CSRF velho: o tecnico precisa entrar de novo
                        return transacao('readwrite', function (loja) {
                            item.erro = 'entre no sistema de novo para enviar';
                            return loja.put(item);
                        }).then(function () { return false; });
                    }
                    if (r.json.success) {
                        enviados++;
                        return transacao('readwrite', function (loja) { return loja.delete(item.id); }).then(function () { return proximo(itens); });
                    }
                    return transacao('readwrite', function (loja) {
                        item.erro = r.json.message || 'recusado pelo servidor';
                        return loja.put(item);
                    }).then(function () { return proximo(itens); });
                });
            };

            return listarDoUsuario().then(proximo).catch(function () { return false; }).then(function (resultado) {
                sincronizando = false;
                return atualizarAviso().then(function (restantes) {
                    if (enviados > 0) {
                        var el = aviso();
                        document.getElementById('fila-offline-texto').textContent = enviados + ' registro(s) enviado(s).' + (restantes.length ? ' Restam ' + restantes.length + '.' : '');
                        el.style.display = 'flex';
                        setTimeout(function () {
                            atualizarAviso().then(function (r) { if (!r.length) { window.location.reload(); } });
                        }, 1500);
                    }
                    return resultado;
                });
            });
        },

        /** Descarta itens com erro (o tecnico decide depois de ler a mensagem). */
        descartarComErro: function () {
            return listarDoUsuario().then(function (itens) {
                return Promise.all(itens.filter(function (i) { return i.erro; }).map(function (i) {
                    return transacao('readwrite', function (loja) { return loja.delete(i.id); });
                }));
            }).then(atualizarAviso);
        }
    };

    window.FilaOffline = FilaOffline;

    if (!window.indexedDB) {
        return;
    }
    window.addEventListener('online', function () { FilaOffline.sincronizar(); });
    window.addEventListener('offline', function () { atualizarAviso(); });
    document.addEventListener('DOMContentLoaded', function () {
        atualizarAviso().then(function (itens) {
            if (itens.length && navigator.onLine) {
                FilaOffline.sincronizar();
            }
        });
    });
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', function (e) {
            if (e.data === 'sincronizar-fila') {
                FilaOffline.sincronizar();
            }
        });
    }
})(window, document);
