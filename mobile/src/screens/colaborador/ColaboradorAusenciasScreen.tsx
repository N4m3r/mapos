/** Folgas, férias, atestados e licenças: pedido e histórico. */

import { useCallback, useEffect, useState } from 'react';
import * as col from '../../lib/api-colaborador';
import { useApi } from '../../lib/store';
import { navigate } from '../../lib/router';
import { dateBr, dateTimeBr, todayIso } from '../../lib/format';
import { escolherFoto } from '../../lib/capture';
import { Alert, Button, Card, Empty, Field, Input, Select, SkeletonList, Textarea, Topbar } from '../../components/ui';
import { MOTIVOS_AUSENCIA, type Ausencia } from '../../lib/types-tecnico';

const STATUS_LABEL: Record<string, string> = {
  pendente: 'Pendente',
  aprovada: 'Aprovada',
  aprovado: 'Aprovada',
  rejeitada: 'Recusada',
  rejeitado: 'Recusada',
};

function tone(status: string): string {
  const s = String(status).toLowerCase();
  if (s.startsWith('aprov')) return 'concluida';
  if (s.startsWith('reje') || s.startsWith('recus')) return 'nao-feita';
  return 'alerta';
}

export function ColaboradorAusenciasScreen() {
  const { baseUrl, token, handle } = useApi();
  const [itens, setItens] = useState<Ausencia[] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [aberto, setAberto] = useState(false);

  const [tipo, setTipo] = useState<'ferias' | 'folga' | 'atestado' | 'licenca'>('ferias');
  const [inicio, setInicio] = useState(todayIso());
  const [fim, setFim] = useState(todayIso());
  const [motivo, setMotivo] = useState('');
  const [anexo, setAnexo] = useState<{ dataUrl: string; nome: string } | null>(null);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setItens(await handle(() => col.ausencias(baseUrl, token)));
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  }, [baseUrl, token, handle]);

  useEffect(() => {
    void load();
  }, [load]);

  const anexar = async () => {
    const escolhida = await escolherFoto();
    if (escolhida) setAnexo({ dataUrl: escolhida, nome: 'atestado.jpg' });
  };

  const enviar = async () => {
    if (fim < inicio) {
      setError('A data final não pode ser anterior à inicial.');
      return;
    }
    setBusy(true);
    setError(null);
    setOk(null);
    try {
      await handle(() =>
        col.solicitarAusencia(baseUrl, token, {
          tipo,
          data_inicio: inicio,
          data_fim: fim || undefined,
          motivo: motivo.trim() || undefined,
          anexo: anexo?.dataUrl,
          anexo_nome: anexo?.nome,
        }),
      );
      setOk('Solicitação enviada para o RH.');
      setAberto(false);
      setMotivo('');
      setAnexo(null);
      await load();
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="screen">
      <Topbar title="Folga / férias" subtitle="Pedidos ao RH" onBack={() => navigate('colaborador')} />

      <div className="content">
        {error ? <Alert kind="error">{error}</Alert> : null}
        {ok ? <Alert kind="ok">{ok}</Alert> : null}

        {!aberto ? (
          <div className="actionbar">
            <Button block onClick={() => setAberto(true)}>
              Novo pedido
            </Button>
            <Button variant="ghost" block onClick={() => navigate('colaborador/solicitacoes')}>
              Correção de ponto
            </Button>
          </div>
        ) : null}

        {aberto ? (
          <Card>
            <div className="section-head">
              <p className="section-title">Nova ausência</p>
              <button type="button" className="reset-button" onClick={() => setAberto(false)}>
                Fechar
              </button>
            </div>
            <div className="stack" style={{ marginTop: 10 }}>
              <Field label="Tipo">
                <Select value={tipo} onChange={(e) => setTipo(e.target.value as typeof tipo)}>
                  {MOTIVOS_AUSENCIA.map((m) => (
                    <option key={m.key} value={m.key}>
                      {m.label}
                    </option>
                  ))}
                </Select>
              </Field>
              <div className="grid-2">
                <Field label="Início">
                  <Input type="date" value={inicio} onChange={(e) => setInicio(e.target.value)} />
                </Field>
                <Field label="Fim">
                  <Input type="date" value={fim} onChange={(e) => setFim(e.target.value)} />
                </Field>
              </div>
              <Field label="Motivo (opcional)">
                <Textarea value={motivo} onChange={(e) => setMotivo(e.target.value)} maxLength={2000} />
              </Field>
              {tipo === 'atestado' || tipo === 'licenca' ? (
                <>
                  <button type="button" className="capture-btn" onClick={() => void anexar()}>
                    {anexo ? 'Trocar anexo' : 'Anexar documento (opcional)'}
                  </button>
                  {anexo ? (
                    <div className="row">
                      <span className="hidden-thumb">
                        <img src={anexo.dataUrl} alt="" />
                      </span>
                      <button type="button" className="reset-button" onClick={() => setAnexo(null)}>
                        remover anexo
                      </button>
                    </div>
                  ) : null}
                </>
              ) : null}
              <Button block loading={busy} onClick={() => void enviar()}>
                Enviar pedido
              </Button>
            </div>
          </Card>
        ) : null}

        <p className="section-title">Meus pedidos</p>
        {loading ? (
          <SkeletonList count={3} />
        ) : !itens?.length ? (
          <Empty title="Nenhum pedido" message="Férias, folgas, atestados e licenças aparecem aqui." />
        ) : (
          <div className="stack">
            {itens.map((a) => (
              <Card key={a.id}>
                <div className="card__head">
                  <p className="card__title">{MOTIVOS_AUSENCIA.find((m) => m.key === a.tipo)?.label ?? a.tipo}</p>
                  <span className={`pill pill--${tone(a.status)}`}>{STATUS_LABEL[String(a.status).toLowerCase()] ?? a.status}</span>
                </div>
                <p className="card__meta" style={{ marginTop: 4 }}>
                  {dateBr(a.data_inicio)} → {dateBr(a.data_fim)} · {a.dias} dia(s)
                </p>
                {a.motivo ? <p className="prose" style={{ marginTop: 8 }}>{a.motivo}</p> : null}
                {a.resposta ? (
                  <div className="hint-box" style={{ marginTop: 8 }}>
                    <span>RH: {a.resposta}</span>
                  </div>
                ) : null}
                <p className="card__meta" style={{ marginTop: 6 }}>
                  Solicitado em {dateTimeBr(a.created_at)}
                  {a.tem_anexo ? ' · com anexo' : ''}
                </p>
              </Card>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
