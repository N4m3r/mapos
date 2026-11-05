/** Serviços não realizados: reagendar ou reabrir a OS. */

import { useCallback, useEffect, useState } from 'react';
import * as tec from '../../lib/api-tecnico';
import { useApi } from '../../lib/store';
import { navigate } from '../../lib/router';
import { dateBr, dateTimeBr, telHref, whatsHref } from '../../lib/format';
import { Alert, Button, Card, Empty, Field, Input, Sheet, SkeletonList, Topbar } from '../../components/ui';
import type { NaoRealizada } from '../../lib/types-tecnico';

export function TecnicoNaoRealizadasScreen() {
  const { baseUrl, token, handle } = useApi();
  const [itens, setItens] = useState<NaoRealizada[] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [alvo, setAlvo] = useState<NaoRealizada | null>(null);
  const [acao, setAcao] = useState<'reagendar' | 'reabrir' | null>(null);
  const [novaData, setNovaData] = useState('');
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setItens(await handle(() => tec.listNaoRealizadas(baseUrl, token)));
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  }, [baseUrl, token, handle]);

  useEffect(() => {
    void load();
  }, [load]);

  const abrir = (item: NaoRealizada, qual: 'reagendar' | 'reabrir') => {
    setAlvo(item);
    setAcao(qual);
    setOk(null);
    setError(null);
    const hoje = new Date();
    const offset = hoje.getTimezoneOffset() * 60000;
    setNovaData(new Date(hoje.getTime() + 86400000 - offset).toISOString().slice(0, 10));
  };

  const confirmar = async () => {
    if (!alvo || !acao) return;
    setBusy(true);
    setError(null);
    try {
      if (acao === 'reagendar') {
        if (!novaData) {
          setError('Informe a nova data.');
          return;
        }
        const r = await handle(() => tec.reagendar(baseUrl, token, alvo.id, novaData));
        setOk(`OS reagendada para ${dateBr(r.nova_data)}.`);
      } else {
        await handle(() => tec.reabrir(baseUrl, token, alvo.id));
        setOk('OS reaberta e devolvida à sua lista.');
      }
      setAcao(null);
      setAlvo(null);
      await load();
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="screen">
      <Topbar
        title="Não realizadas"
        subtitle={itens ? `${itens.length} aguardando decisão` : 'Carregando…'}
        onBack={() => navigate('tecnico')}
      />

      <div className="content">
        {error ? <Alert kind="error">{error}</Alert> : null}
        {ok ? <Alert kind="ok">{ok}</Alert> : null}

        {loading ? (
          <SkeletonList count={3} />
        ) : !itens?.length ? (
          <Empty title="Nada pendente" message="Nenhum serviço aguardando reagendamento." />
        ) : (
          <div className="stack">
            {itens.map((item) => {
              const fone = item.celular ?? item.telefone ?? undefined;
              const tel = telHref(fone);
              const zap = whatsHref(fone, `Olá! Precisamos remarcar a visita da OS ${item.os_id}.`);
              return (
                <Card key={item.id}>
                  <div className="card__head">
                    <p className="card__title">{item.nomeCliente || 'Cliente'}</p>
                    <span className="pill pill--nao-feita">Não realizado</span>
                  </div>
                  <p className="card__meta" style={{ marginTop: 4 }}>
                    OS {String(item.os_id).padStart(4, '0')} · registrado em {dateTimeBr(item.data_registro)}
                  </p>
                  {item.descricaoProduto ? (
                    <p className="card__meta" style={{ marginTop: 2 }}>
                      {item.descricaoProduto}
                    </p>
                  ) : null}
                  <p className="prose" style={{ marginTop: 8, fontSize: '0.86rem' }}>
                    <strong>{item.motivo || 'Sem motivo informado'}</strong>
                    {item.observacao ? ` — ${item.observacao}` : ''}
                  </p>
                  {item.status_anterior ? (
                    <p className="card__meta" style={{ marginTop: 4 }}>
                      Status anterior: {item.status_anterior}
                    </p>
                  ) : null}

                  <div className="actionbar" style={{ marginTop: 12 }}>
                    <Button small block onClick={() => abrir(item, 'reagendar')}>
                      Reagendar
                    </Button>
                    <Button small block variant="ghost" onClick={() => abrir(item, 'reabrir')}>
                      Reabrir
                    </Button>
                  </div>
                  {tel || zap ? (
                    <div className="actionbar" style={{ marginTop: 8 }}>
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
                  ) : null}
                </Card>
              );
            })}
          </div>
        )}
      </div>

      <Sheet
        open={Boolean(alvo && acao)}
        title={acao === 'reagendar' ? 'Reagendar visita' : 'Reabrir OS'}
        onClose={() => {
          setAcao(null);
          setAlvo(null);
        }}
        footer={
          <>
            <Button
              variant="ghost"
              block
              onClick={() => {
                setAcao(null);
                setAlvo(null);
              }}
            >
              Cancelar
            </Button>
            <Button block loading={busy} onClick={() => void confirmar()}>
              Confirmar
            </Button>
          </>
        }
      >
        {alvo ? (
          <>
            <p className="card__meta">
              {alvo.nomeCliente || 'Cliente'} · OS {String(alvo.os_id).padStart(4, '0')}
            </p>
            {acao === 'reagendar' ? (
              <Field label="Nova data da visita">
                <Input type="date" value={novaData} onChange={(e) => setNovaData(e.target.value)} required />
              </Field>
            ) : (
              <p className="card__meta">
                A OS volta para a sua agenda e recebe de novo o status anterior. Você poderá iniciar o atendimento.
              </p>
            )}
            <p className="card__meta" style={{ fontSize: '0.75rem' }}>
              O cliente recebe o aviso pelo WhatsApp quando configurado.
            </p>
          </>
        ) : null}
      </Sheet>
    </div>
  );
}
