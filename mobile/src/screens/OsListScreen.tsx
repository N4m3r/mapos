/** Lista de ordens de serviço com busca, filtro por status e paginação. */

import { useCallback, useEffect, useState } from 'react';
import * as api from '../lib/api';
import { useApi, useApp } from '../lib/store';
import { dateBr, money, statusColor } from '../lib/format';
import { navigate } from '../lib/router';
import { Badge, Card, Empty, SkeletonList, Topbar } from '../components/ui';
import { OS_STATUS, type Os } from '../lib/types';

const FILTERS = ['Todas', ...OS_STATUS] as const;
const PAGE_SIZE = 20;

export function OsListScreen() {
  const { baseUrl, token, handle } = useApi();
  const { can } = useApp();
  const [items, setItems] = useState<Os[]>([]);
  const [search, setSearch] = useState('');
  const [term, setTerm] = useState('');
  const [status, setStatus] = useState<string>('Todas');
  const [page, setPage] = useState(0);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [hasMore, setHasMore] = useState(false);

  // debounce da busca: evita uma requisição por tecla
  useEffect(() => {
    const timer = window.setTimeout(() => {
      setTerm(search.trim());
      setPage(0);
    }, 350);
    return () => window.clearTimeout(timer);
  }, [search]);

  const load = useCallback(
    async (targetPage: number) => {
      const isFirst = targetPage === 0;
      if (isFirst) setLoading(true);
      else setLoadingMore(true);
      setError(null);
      try {
        const result = await handle(() =>
          api.listOs(baseUrl, token, {
            page: targetPage,
            perPage: PAGE_SIZE,
            search: term || undefined,
            status: status === 'Todas' ? undefined : status,
          }),
        );
        setItems((prev) => (isFirst ? result : [...prev, ...result]));
        setHasMore(result.length === PAGE_SIZE);
      } catch (err) {
        setError((err as Error).message);
      } finally {
        setLoading(false);
        setLoadingMore(false);
      }
    },
    [baseUrl, token, term, status, handle],
  );

  useEffect(() => {
    void load(page);
  }, [load, page]);

  return (
    <div className="screen">
      <Topbar title="Ordens de serviço" subtitle={loading ? 'Carregando…' : `${items.length} carregada(s)`} />

      <div className="content">
        <div className="search">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.2-3.2" />
          </svg>
          <input
            className="input"
            type="search"
            placeholder="Cliente, documento ou nº da OS"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            aria-label="Buscar ordens de serviço"
          />
        </div>

        <div className="chips" role="tablist" aria-label="Filtrar por status">
          {FILTERS.map((filter) => (
            <button
              key={filter}
              type="button"
              role="tab"
              aria-selected={status === filter}
              className={`chip${status === filter ? ' chip--active' : ''}`}
              onClick={() => {
                setStatus(filter);
                setPage(0);
              }}
            >
              {filter}
            </button>
          ))}
        </div>

        {error ? <div className="alert alert--error">{error}</div> : null}

        {loading ? (
          <SkeletonList count={5} />
        ) : items.length === 0 ? (
          <Empty title="Nenhuma OS encontrada" message="Ajuste a busca ou o filtro de status." />
        ) : (
          <div className="stack">
            {items.map((os) => (
              <Card key={os.idOs} onClick={() => navigate(`os/${os.idOs}`)}>
                <div className="card__head">
                  <p className="card__title">
                    #{os.idOs} · {os.nomeCliente ?? 'Cliente'}
                  </p>
                  <Badge color={statusColor(os.status)}>{os.status}</Badge>
                </div>
                <div className="row row--between" style={{ marginTop: 6 }}>
                  <span className="card__meta">
                    {dateBr(os.dataFinal)} {os.nome ? `· ${os.nome}` : ''}
                  </span>
                  <span style={{ fontWeight: 640, fontSize: '0.9rem' }}>
                    {money(Number(os.totalProdutos ?? 0) + Number(os.totalServicos ?? 0))}
                  </span>
                </div>
              </Card>
            ))}

            {hasMore ? (
              <button
                type="button"
                className="btn btn--ghost btn--block"
                onClick={() => setPage((p) => p + 1)}
                disabled={loadingMore}
              >
                {loadingMore ? <span className="spinner" /> : null}
                Carregar mais
              </button>
            ) : null}
          </div>
        )}
      </div>

      {can('aOs') ? (
        <button type="button" className="fab" onClick={() => navigate('os/nova')} aria-label="Nova OS">
          <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round">
            <path d="M12 5v14M5 12h14" />
          </svg>
        </button>
      ) : null}
    </div>
  );
}
