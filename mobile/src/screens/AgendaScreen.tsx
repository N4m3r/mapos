/**
 * Agenda: agrupa as OS por data final (semana/mês).
 * A API não expõe endpoint de calendário autenticado por JWT para o app,
 * então montamos a agenda a partir de /api/v1/os no intervalo escolhido.
 */

import { useCallback, useEffect, useMemo, useState } from 'react';
import * as api from '../lib/api';
import { useApi } from '../lib/store';
import { dateBr, statusColor } from '../lib/format';
import { navigate } from '../lib/router';
import { Badge, Card, Empty, SkeletonList, Topbar } from '../components/ui';
import { OS_STATUS, type Os } from '../lib/types';

function isoOf(date: Date): string {
  const offset = date.getTimezoneOffset() * 60000;
  return new Date(date.getTime() - offset).toISOString().slice(0, 10);
}

function brOf(iso: string): string {
  const [y, m, d] = iso.split('-');
  return `${d}/${m}/${y}`;
}

function rangeFor(kind: 'semana' | 'mes'): { from: string; to: string; label: string } {
  const now = new Date();
  if (kind === 'semana') {
    const start = new Date(now);
    start.setDate(now.getDate() - now.getDay());
    const end = new Date(start);
    end.setDate(start.getDate() + 6);
    return { from: isoOf(start), to: isoOf(end), label: `${brOf(isoOf(start))} – ${brOf(isoOf(end))}` };
  }
  const start = new Date(now.getFullYear(), now.getMonth(), 1);
  const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  return { from: isoOf(start), to: isoOf(end), label: `${brOf(isoOf(start))} – ${brOf(isoOf(end))}` };
}

export function AgendaScreen() {
  const { baseUrl, token, handle } = useApi();
  const [kind, setKind] = useState<'semana' | 'mes'>('semana');
  const [status, setStatus] = useState<string>('Todas');
  const [items, setItems] = useState<Os[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const range = useMemo(() => rangeFor(kind), [kind]);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await handle(() =>
        api.listOs(baseUrl, token, {
          from: brOf(range.from),
          to: brOf(range.to),
          status: status === 'Todas' ? undefined : status,
          perPage: 100,
        }),
      );
      setItems(result);
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  }, [baseUrl, token, range.from, range.to, status, handle]);

  useEffect(() => {
    void load();
  }, [load]);

  const grouped = useMemo(() => {
    const map = new Map<string, Os[]>();
    for (const os of items) {
      const day = String(os.dataFinal).slice(0, 10);
      const list = map.get(day) ?? [];
      list.push(os);
      map.set(day, list);
    }
    return [...map.entries()].sort(([a], [b]) => a.localeCompare(b));
  }, [items]);

  return (
    <div className="screen">
      <Topbar title="Agenda" subtitle={range.label} />

      <div className="content">
        <div className="chips">
          {(['semana', 'mes'] as const).map((k) => (
            <button key={k} type="button" className={`chip${kind === k ? ' chip--active' : ''}`} onClick={() => setKind(k)}>
              {k === 'semana' ? 'Esta semana' : 'Este mês'}
            </button>
          ))}
        </div>

        <div className="chips">
          {['Todas', ...OS_STATUS].map((st) => (
            <button
              key={st}
              type="button"
              className={`chip${status === st ? ' chip--active' : ''}`}
              onClick={() => setStatus(st)}
            >
              {st}
            </button>
          ))}
        </div>

        {error ? <div className="alert alert--error">{error}</div> : null}

        {loading ? (
          <SkeletonList count={4} />
        ) : grouped.length === 0 ? (
          <Empty title="Agenda livre" message="Nenhuma OS prevista para este período." />
        ) : (
          <div className="stack">
            {grouped.map(([day, list]) => (
              <div key={day} className="stack">
                <p className="section-title">{dateBr(day)}</p>
                {list.map((os) => (
                  <Card key={os.idOs} onClick={() => navigate(`os/${os.idOs}`)}>
                    <div className="card__head">
                      <p className="card__title">
                        #{os.idOs} · {os.nomeCliente ?? 'Cliente'}
                      </p>
                      <Badge color={statusColor(os.status)}>{os.status}</Badge>
                    </div>
                    {os.descricaoProduto ? (
                      <p className="card__meta" style={{ marginTop: 4 }}>
                        {os.descricaoProduto.slice(0, 90)}
                      </p>
                    ) : null}
                  </Card>
                ))}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
