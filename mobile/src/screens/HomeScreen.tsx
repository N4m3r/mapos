/** Início: contadores, OS abertas/em andamento e atalhos. */

import { useCallback, useEffect, useState } from 'react';
import * as api from '../lib/api';
import { useApi, useApp } from '../lib/store';
import { count, dateBr, initials, statusColor } from '../lib/format';
import { navigate } from '../lib/router';
import { Badge, Card, Empty, SkeletonList, Topbar } from '../components/ui';
import type { DashboardResult, Os } from '../lib/types';

function OsRow({ os }: { os: Os }) {
  return (
    <Card onClick={() => navigate(`os/${os.idOs}`)}>
      <div className="row" style={{ alignItems: 'flex-start' }}>
        <div className="avatar">{initials(os.nomeCliente)}</div>
        <div style={{ flex: 1, minWidth: 0 }}>
          <div className="card__head">
            <p className="card__title">
              #{os.idOs} · {os.nomeCliente ?? 'Cliente'}
            </p>
            <Badge color={statusColor(os.status)}>{os.status}</Badge>
          </div>
          <p className="card__meta">
            {dateBr(os.dataInicial)} → {dateBr(os.dataFinal)}
            {os.nome ? ` · ${os.nome}` : ''}
          </p>
        </div>
      </div>
    </Card>
  );
}

export function HomeScreen() {
  const { baseUrl, token, session, handle } = useApi();
  const { brand, signOut, can } = useApp();
  const [data, setData] = useState<DashboardResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await handle(() => api.dashboard(baseUrl, token));
      setData(result);
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  }, [baseUrl, token, handle]);

  useEffect(() => {
    void load();
  }, [load]);

  const osAbertas = data?.osAbertas ?? [];
  const osAndamento = data?.osAndamento ?? [];

  return (
    <div className="screen">
      <Topbar
        title={brand?.emitente?.nome ?? brand?.appName ?? 'MAPOS'}
        subtitle={session?.user.nome ?? ''}
        logo={brand?.emitente?.url_logo}
        right={
          <button type="button" className="icon-btn" onClick={() => navigate('config')} aria-label="Configurações">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round">
              <circle cx="12" cy="12" r="3.2" />
              <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2v.2a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-2.9-1.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 4.6 15H4.5a2 2 0 1 1 0-4h.2a1.7 1.7 0 0 0 1.2-2.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 11.6 4.6V4.5a2 2 0 1 1 4 0v.2a1.7 1.7 0 0 0 2.9 1.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0 1.2 2.9h.2a2 2 0 1 1 0 4h-.2a1.7 1.7 0 0 0-1.5 1.4z" />
            </svg>
          </button>
        }
      />

      <div className="content">
        {error ? (
          <div className="stack">
            <div className="alert alert--error">{error}</div>
            <button type="button" className="reset-button" onClick={() => void load()}>
              Tentar novamente
            </button>
          </div>
        ) : null}

        {loading && !data ? (
          <SkeletonList count={4} />
        ) : data ? (
          <>
            <div className="grid-2">
              <div className="tile">
                <div className="tile__value">{count(data.countOs)}</div>
                <div className="tile__label">Ordens de serviço</div>
              </div>
              <div className="tile">
                <div className="tile__value">{count(data.clientes)}</div>
                <div className="tile__label">Clientes</div>
              </div>
              <div className="tile">
                <div className="tile__value">{count(data.produtos)}</div>
                <div className="tile__label">Produtos</div>
              </div>
              <div className="tile">
                <div className="tile__value">{count(data.servicos)}</div>
                <div className="tile__label">Serviços</div>
              </div>
            </div>

            {Array.isArray(data.estoqueBaixo) && data.estoqueBaixo.length > 0 ? (
              <div className="card">
                <p className="section-title" style={{ marginBottom: 6 }}>
                  Estoque baixo
                </p>
                {data.estoqueBaixo.slice(0, 4).map((p) => (
                  <div className="kv" key={p.idProdutos}>
                    <span className="kv__k" style={{ color: 'var(--text)' }}>
                      {p.descricao}
                    </span>
                    <span className="kv__v" style={{ color: 'var(--warn)' }}>
                      {count(p.estoque)} un.
                    </span>
                  </div>
                ))}
              </div>
            ) : null}

            <div className="row row--between">
              <p className="section-title">Em andamento</p>
              <button type="button" className="reset-button" onClick={() => navigate('os')}>
                Ver todas
              </button>
            </div>
            {osAndamento.length ? (
              <div className="stack">
                {osAndamento.slice(0, 5).map((os) => (
                  <OsRow key={os.idOs} os={os} />
                ))}
              </div>
            ) : (
              <Card>
                <p className="card__meta" style={{ margin: 0 }}>
                  Nenhuma OS em andamento.
                </p>
              </Card>
            )}

            <p className="section-title">Abertas</p>
            {osAbertas.length ? (
              <div className="stack">
                {osAbertas.slice(0, 5).map((os) => (
                  <OsRow key={os.idOs} os={os} />
                ))}
              </div>
            ) : (
              <Empty title="Nada por aqui" message="Nenhuma OS aberta no momento." />
            )}

            {can('vDashboard') ? (
              <p className="card__meta" style={{ textAlign: 'center' }}>
                Total faturado e relatórios completos estão no painel web.
              </p>
            ) : null}

            <button type="button" className="reset-button" style={{ color: 'var(--danger)', marginTop: 8 }} onClick={signOut}>
              Sair da conta
            </button>
          </>
        ) : null}
      </div>
    </div>
  );
}
