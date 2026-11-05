/** Minhas OS: abas por situação (hoje, próximos, atrasadas, concluídas, todas). */

import { useCallback, useEffect, useState } from 'react';
import * as tec from '../../lib/api-tecnico';
import { useApi, useApp } from '../../lib/store';
import { navigate } from '../../lib/router';
import { quandoVisita, telHref, whatsHref } from '../../lib/format';
import { Alert, Card, Empty, Fab, Segmented, SkeletonList, StatusPill, Topbar } from '../../components/ui';
import { ABAS_TECNICO, type OsResumo, type TecnicoOsLista } from '../../lib/types-tecnico';

function OsCard({ os }: { os: OsResumo }) {
  const celular = os.celular ?? os.telefone ?? undefined;
  const tel = telHref(celular);
  const zap = whatsHref(celular);

  return (
    <Card onClick={() => navigate(`tecnico/atendimento/${os.idOs}`)}>
      <div className="card__head">
        <p className="card__title">
          #{String(os.idOs).padStart(4, '0')} · {os.nomeCliente || 'Cliente'}
        </p>
        <StatusPill text={os.rotuloStatus ?? os.status} tone={os.statusClasse} />
      </div>
      <p className="card__meta" style={{ marginTop: 4 }}>
        {quandoVisita(os.dataEfetiva ?? os.data_agendamento ?? os.dataInicial)}
        {os.hora ? ` · ${os.hora}` : ''}
      </p>
      {os.endereco ? (
        <p className="card__meta" style={{ marginTop: 2 }}>
          {os.endereco}
        </p>
      ) : null}
      {tel || zap ? (
        <div className="actionbar" style={{ marginTop: 10, gridTemplateColumns: 'repeat(2, minmax(0, 1fr))' }}>
          {tel ? (
            <a className="btn btn--ghost btn--sm" href={tel} onClick={(e) => e.stopPropagation()}>
              Ligar
            </a>
          ) : null}
          {zap ? (
            <a
              className="btn btn--ghost btn--sm"
              href={zap}
              target="_blank"
              rel="noopener noreferrer"
              onClick={(e) => e.stopPropagation()}
            >
              WhatsApp
            </a>
          ) : null}
        </div>
      ) : null}
    </Card>
  );
}

export function TecnicoOsListScreen({ aba }: { aba?: string }) {
  const { baseUrl, token, handle } = useApi();
  const { can } = useApp();
  const [atual, setAtual] = useState(aba && ABAS_TECNICO.some((a) => a.key === aba) ? aba : 'hoje');
  const [data, setData] = useState<TecnicoOsLista | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setData(await handle(() => tec.listOs(baseUrl, token, atual)));
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  }, [baseUrl, token, atual, handle]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    if (aba && aba !== atual && ABAS_TECNICO.some((a) => a.key === aba)) setAtual(aba);
  }, [aba, atual]);

  const contagens = data?.contagens;
  const itens = data?.ordens ?? [];
  const podeCriar = can('aTecnicoAtividade');

  return (
    <div className="screen">
      <Topbar
        title="Minhas OS"
        subtitle={
          contagens
            ? `${contagens.hoje} hoje · ${contagens.atrasadas} atrasadas · ${contagens.concluidas} concluídas`
            : 'Carregando…'
        }
      />

      <div className="content">
        <Segmented
          label="Situação das OS"
          value={atual}
          onChange={(key) => {
            setAtual(key);
            navigate(`tecnico/os${key === 'hoje' ? '' : `/${key}`}`);
          }}
          items={ABAS_TECNICO.map((a) => ({
            key: a.key,
            label: a.label,
            count:
              a.key === 'todas'
                ? contagens
                  ? contagens.hoje + contagens.proximos + contagens.atrasadas + contagens.concluidas
                  : undefined
                : contagens?.[a.key as keyof typeof contagens],
          }))}
        />

        {error ? (
          <div className="stack">
            <Alert kind="error">{error}</Alert>
            <button type="button" className="reset-button" onClick={() => void load()}>
              Tentar novamente
            </button>
          </div>
        ) : null}

        {loading ? (
          <SkeletonList count={4} />
        ) : itens.length === 0 ? (
          <Empty
            title="Nenhuma OS nesta aba"
            message={
              atual === 'atrasadas'
                ? 'Nada atrasado. Bom trabalho!'
                : atual === 'concluidas'
                  ? 'As OS finalizadas aparecem aqui.'
                  : 'Ajuste a aba ou aguarde novas designações.'
            }
          />
        ) : (
          <div className="stack">
            {itens.map((os) => (
              <OsCard key={`${os.idOs}-${os.status}`} os={os} />
            ))}
          </div>
        )}
      </div>

      {podeCriar ? <Fab label="Nova atividade" onClick={() => navigate('tecnico/atividade')} /> : null}
    </div>
  );
}
