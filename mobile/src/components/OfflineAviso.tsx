/**
 * Modo offline na tela: aviso no topo e painel com download e fila.
 *
 * Também comanda a sincronização: entrega a fila ao abrir o app, quando a
 * internet volta, ao voltar ao primeiro plano e a cada 30 s enquanto houver
 * envio pendente; baixa os dados na primeira vez e depois a cada 6 h.
 */

import { useCallback, useEffect, useState } from 'react';
import { useApp } from '../lib/store';
import { definirUsuarioOffline, descartarDaFila, estadoOffline, itensDaFila, mudarEstado, ouvirOffline, type EstadoOffline } from '../lib/offline';
import { baixarParaOffline, sincronizarFila } from '../lib/offline-sync';
import type { ItemFila } from '../lib/offline-db';
import { Alert, Button, Sheet } from './ui';

const REBAIXAR_MS = 6 * 60 * 60 * 1000;

export function useEstadoOffline(): EstadoOffline {
  const [estado, setEstado] = useState(estadoOffline());
  useEffect(() => ouvirOffline(setEstado), []);
  return estado;
}

function quando(ts: number | null): string {
  if (!ts) return 'nunca';
  const d = new Date(ts);
  const hoje = new Date().toDateString() === d.toDateString();
  const hora = d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  return hoje ? `hoje às ${hora}` : `${d.toLocaleDateString('pt-BR')} às ${hora}`;
}

export function OfflineAviso() {
  const { status, serverUrl, session, areas, signOut } = useApp();
  const estado = useEstadoOffline();
  const [aberto, setAberto] = useState(false);
  const [fila, setFila] = useState<ItemFila[]>([]);
  const [msg, setMsg] = useState<{ kind: 'ok' | 'error' | 'info'; texto: string } | null>(null);
  const baseUrl = serverUrl ?? '';
  const token = session?.token ?? '';
  const pronto = status === 'ready' && Boolean(baseUrl && token);

  const sincronizar = useCallback(async () => {
    if (!pronto) return;
    const r = await sincronizarFila(baseUrl, token);
    if (r.sessaoVencida) signOut();
    if (r.enviados) setMsg({ kind: 'ok', texto: `${r.enviados} envio(s) feitos sem internet foram entregues ao servidor.` });
  }, [pronto, baseUrl, token, signOut]);

  const baixar = useCallback(
    async (manual: boolean) => {
      if (!pronto) return;
      await sincronizar();
      const ok = await baixarParaOffline(baseUrl, token, areas);
      if (manual) {
        setMsg(
          ok
            ? { kind: 'ok', texto: 'Pronto: o app tem uma cópia dos seus dados para usar sem internet.' }
            : { kind: 'error', texto: 'A internet caiu no meio do download. O que já veio ficou guardado; tente de novo com sinal.' },
        );
      }
    },
    [pronto, baseUrl, token, areas, sincronizar],
  );

  // Dono dos dados guardados + primeira sincronização/download.
  useEffect(() => {
    if (!pronto) return;
    void (async () => {
      await definirUsuarioOffline(baseUrl, session?.user.idUsuarios);
      await sincronizar();
      const ultimo = estadoOffline().ultimoDownload;
      if (navigator.onLine !== false && (!ultimo || Date.now() - ultimo > REBAIXAR_MS)) void baixar(false);
    })();
    // só quando muda o usuário/servidor
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pronto, baseUrl, session?.user.idUsuarios]);

  // Internet voltou / app voltou à frente / pendências: tenta entregar.
  useEffect(() => {
    if (!pronto) return;
    const tentar = () => {
      if (navigator.onLine !== false && document.visibilityState === 'visible') void sincronizar();
    };
    const voltou = () => {
      mudarEstado({ semRede: false, usandoGuardado: null });
      tentar();
    };
    window.addEventListener('online', voltou);
    document.addEventListener('visibilitychange', tentar);
    const timer = window.setInterval(() => {
      if (estadoOffline().pendentes > 0) tentar();
    }, 30000);
    return () => {
      window.removeEventListener('online', voltou);
      document.removeEventListener('visibilitychange', tentar);
      window.clearInterval(timer);
    };
  }, [pronto, sincronizar]);

  useEffect(() => {
    if (aberto) void itensDaFila().then(setFila);
  }, [aberto, estado.pendentes, estado.falhas]);

  if (!pronto) return null;

  const mostrar = estado.semRede || estado.pendentes > 0 || estado.falhas > 0 || estado.baixando || msg;
  const texto = estado.baixando
    ? `Baixando para usar offline… ${estado.progresso ?? ''}`
    : estado.semRede
      ? `Sem internet${estado.pendentes > 0 ? ` · ${estado.pendentes} envio(s) aguardando internet` : estado.usandoGuardado ? ` · dados de ${quando(estado.usandoGuardado)}` : ''}`
      : estado.sincronizando
        ? 'Enviando o que foi feito sem internet…'
        : estado.falhas > 0
          ? `${estado.falhas} envio(s) recusado(s) pelo servidor`
          : estado.pendentes > 0
            ? `${estado.pendentes} envio(s) aguardando internet`
            : null;

  return (
    <>
      {mostrar && texto ? (
        <button
          type="button"
          className={`offline-aviso${estado.semRede ? ' offline-aviso--sem-rede' : ''}${estado.falhas ? ' offline-aviso--falha' : ''}`}
          onClick={() => setAberto(true)}
        >
          <span>{texto}</span>
          {estado.pendentes > 0 && !estado.baixando ? <b>{estado.pendentes}</b> : null}
        </button>
      ) : null}

      <Sheet open={aberto} title="Usar sem internet" onClose={() => setAberto(false)}>
        {msg ? <Alert kind={msg.kind}>{msg.texto}</Alert> : null}
        <p className="card__meta">
          O app guarda uma cópia das suas OS, projetos, pontos e dados do RH para abrir sem sinal. O que você fizer
          sem internet fica guardado e é enviado sozinho quando a conexão voltar.
        </p>
        <p className="card__meta">Último download: {quando(estado.ultimoDownload)}.</p>
        <Button block loading={estado.baixando} onClick={() => void baixar(true)} disabled={estado.semRede && navigator.onLine === false}>
          {estado.baixando ? estado.progresso ?? 'Baixando…' : 'Baixar para usar offline'}
        </Button>
        {estado.pendentes > 0 ? (
          <Button variant="ghost" block loading={estado.sincronizando} onClick={() => void sincronizar()}>
            Enviar agora
          </Button>
        ) : null}

        <p className="section-title" style={{ marginTop: 14 }}>
          Aguardando envio ({fila.length})
        </p>
        {!fila.length ? <p className="card__meta">Nada pendente.</p> : null}
        {fila.map((item) => (
          <div key={item.id} className="offline-item">
            <div>
              <strong>{item.rotulo}</strong>
              <p className="card__meta">
                Feito {quando(item.criadoEm)}
                {item.erro ? ` · Recusado: ${item.erro}` : ' · aguardando internet'}
              </p>
            </div>
            {item.erro && item.id !== undefined ? (
              <Button variant="ghost" small onClick={() => void descartarDaFila(item.id as number)}>
                Descartar
              </Button>
            ) : null}
          </div>
        ))}
      </Sheet>
    </>
  );
}
