/** Lista de clientes com busca e acesso rápido ao telefone. */

import { useCallback, useEffect, useState } from 'react';
import * as api from '../lib/api';
import { useApi, useApp } from '../lib/store';
import { initials, telHref } from '../lib/format';
import { navigate } from '../lib/router';
import { Card, Empty, SkeletonList, Topbar } from '../components/ui';
import type { Cliente } from '../lib/types';

const PAGE_SIZE = 30;

export function ClientesScreen() {
  const { baseUrl, token, handle } = useApi();
  const { can } = useApp();
  const [items, setItems] = useState<Cliente[]>([]);
  const [search, setSearch] = useState('');
  const [term, setTerm] = useState('');
  const [page, setPage] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [hasMore, setHasMore] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);

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
          api.listClientes(baseUrl, token, { page: targetPage, perPage: PAGE_SIZE, search: term || undefined }),
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
    [baseUrl, token, term, handle],
  );

  useEffect(() => {
    void load(page);
  }, [load, page]);

  return (
    <div className="screen">
      <Topbar title="Clientes" subtitle={`${items.length} carregado(s)`} />

      <div className="content">
        <div className="search">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.2-3.2" />
          </svg>
          <input
            className="input"
            type="search"
            placeholder="Nome, documento, telefone ou e-mail"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            aria-label="Buscar clientes"
          />
        </div>

        {error ? <div className="alert alert--error">{error}</div> : null}

        {loading ? (
          <SkeletonList count={5} />
        ) : items.length === 0 ? (
          <Empty title="Nenhum cliente" message="Tente outro termo de busca." />
        ) : (
          <div className="stack">
            {items.map((c) => {
              const tel = telHref(c.celular ?? c.telefone);
              return (
                <Card key={c.idClientes}>
                  <div className="row" style={{ alignItems: 'flex-start' }}>
                    <button
                      type="button"
                      onClick={() => navigate(`clientes/${c.idClientes}`)}
                      style={{ display: 'flex', gap: 10, flex: 1, minWidth: 0, alignItems: 'flex-start', border: 0, background: 'none', padding: 0, textAlign: 'left', cursor: 'pointer' }}
                    >
                      <span className="avatar">{initials(c.nomeCliente)}</span>
                      <span style={{ flex: 1, minWidth: 0 }}>
                        <span className="card__title" style={{ display: 'block' }}>
                          {c.nomeCliente}
                        </span>
                        <span className="card__meta" style={{ display: 'block' }}>
                          {[c.celular ?? c.telefone, c.cidade].filter(Boolean).join(' · ') || 'Sem contato cadastrado'}
                        </span>
                      </span>
                    </button>
                    {tel ? (
                      <a className="icon-btn" href={tel} aria-label={`Ligar para ${c.nomeCliente}`}>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round">
                          <path d="M6.6 3.5h3l1.4 3.4-2 1.4a12 12 0 0 0 5.7 5.7l1.4-2 3.4 1.4v3c0 1.3-1 2.4-2.3 2.4A16.5 16.5 0 0 1 4.2 5.8c0-1.3 1.1-2.3 2.4-2.3z" />
                        </svg>
                      </a>
                    ) : null}
                  </div>
                </Card>
              );
            })}

            {hasMore ? (
              <button type="button" className="btn btn--ghost btn--block" onClick={() => setPage((p) => p + 1)} disabled={loadingMore}>
                {loadingMore ? <span className="spinner" /> : null}
                Carregar mais
              </button>
            ) : null}
          </div>
        )}
      </div>

      {can('aCliente') ? (
        <button type="button" className="fab" onClick={() => navigate('clientes/novo')} aria-label="Novo cliente">
          <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round">
            <path d="M12 5v14M5 12h14" />
          </svg>
        </button>
      ) : null}
    </div>
  );
}
