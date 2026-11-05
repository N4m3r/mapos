/** Detalhe do cliente: contato, endereço e histórico de OS. */

import { useCallback, useEffect, useState } from 'react';
import * as api from '../lib/api';
import { useApi, useApp } from '../lib/store';
import { dateBr, initials, statusColor, telHref, whatsHref } from '../lib/format';
import { navigate } from '../lib/router';
import { Alert, Badge, Card, Empty, Kv, SkeletonList, Topbar } from '../components/ui';
import type { Cliente } from '../lib/types';

export function ClienteDetailScreen({ id }: { id: string }) {
  const { baseUrl, token, handle } = useApi();
  const { can } = useApp();
  const [cliente, setCliente] = useState<Cliente | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await handle(() => api.getCliente(baseUrl, token, id));
      setCliente(result);
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  }, [baseUrl, token, id, handle]);

  useEffect(() => {
    void load();
  }, [load]);

  const phone = cliente?.celular ?? cliente?.telefone;
  const tel = telHref(phone);
  const zap = whatsHref(phone, `Olá, ${cliente?.nomeCliente ?? ''}!`);
  const osList = cliente?.ordensServicos ?? [];
  const endereco = [cliente?.rua, cliente?.numero, cliente?.complemento, cliente?.bairro, cliente?.cidade, cliente?.estado]
    .filter(Boolean)
    .join(', ');

  return (
    <div className="screen">
      <Topbar title={cliente?.nomeCliente ?? 'Cliente'} subtitle={cliente?.documento} onBack={() => navigate('clientes')} />

      <div className="content">
        {loading ? <SkeletonList count={3} /> : null}
        {error ? <Alert kind="error">{error}</Alert> : null}

        {cliente ? (
          <>
            <Card>
              <div className="row" style={{ alignItems: 'center' }}>
                <span className="avatar" style={{ width: 52, height: 52 }}>
                  {initials(cliente.nomeCliente)}
                </span>
                <div style={{ flex: 1, minWidth: 0 }}>
                  <p className="card__title" style={{ margin: 0 }}>
                    {cliente.nomeCliente}
                  </p>
                  <p className="card__meta">{cliente.email || cliente.documento || 'Sem e-mail cadastrado'}</p>
                </div>
              </div>

              {(tel || zap) && (
                <div className="actionbar" style={{ marginTop: 12 }}>
                  {tel ? (
                    <a className="btn btn--ghost btn--sm" href={tel}>
                      Ligar
                    </a>
                  ) : null}
                  {zap ? (
                    <a className="btn btn--ghost btn--sm" href={zap} target="_blank" rel="noopener noreferrer">
                      WhatsApp
                    </a>
                  ) : null}
                </div>
              )}
            </Card>

            <Card>
              <Kv k="Telefone" v={cliente.telefone || '—'} />
              <Kv k="Celular" v={cliente.celular || '—'} />
              <Kv k="Contato" v={cliente.contato || '—'} />
              <Kv k="Documento" v={cliente.documento || '—'} />
              <Kv k="E-mail" v={cliente.email || '—'} />
              <Kv k="Endereço" v={endereco || '—'} />
              <Kv k="CEP" v={cliente.cep || '—'} />
              <Kv k="Cadastro" v={dateBr(cliente.dataCadastro)} />
            </Card>

            <p className="section-title">Ordens de serviço ({osList.length})</p>
            {osList.length === 0 ? (
              <Empty title="Sem histórico" message="Este cliente ainda não possui OS." />
            ) : (
              <div className="stack">
                {osList.map((os) => (
                  <Card key={os.idOs} onClick={() => navigate(`os/${os.idOs}`)}>
                    <div className="card__head">
                      <p className="card__title">#{os.idOs}</p>
                      <Badge color={statusColor(os.status)}>{os.status}</Badge>
                    </div>
                    <p className="card__meta">
                      {dateBr(os.dataInicial)} → {dateBr(os.dataFinal)}
                    </p>
                  </Card>
                ))}
              </div>
            )}

            {can('aOs') ? (
              <button type="button" className="btn btn--block" onClick={() => navigate('os/nova')}>
                Abrir nova OS
              </button>
            ) : null}
          </>
        ) : null}
      </div>
    </div>
  );
}
