/** Justificar / corrigir: solicitações de ponto e falta, com histórico do RH. */

import { useCallback, useEffect, useState } from 'react';
import * as col from '../../lib/api-colaborador';
import { useApi } from '../../lib/store';
import { navigate } from '../../lib/router';
import { dateBr, dateTimeBr, todayIso } from '../../lib/format';
import { escolherFoto } from '../../lib/capture';
import { Alert, Button, Card, Empty, Field, Input, Select, SkeletonList, Textarea, Topbar } from '../../components/ui';
import { PONTO_LABELS, TIPOS_OCORRENCIA, type Ocorrencia, type PontoBatida } from '../../lib/types-tecnico';

const STATUS_LABEL: Record<string, string> = {
  pendente: 'Pendente',
  aprovado: 'Aprovada',
  aprovada: 'Aprovada',
  rejeitado: 'Recusada',
  rejeitada: 'Recusada',
  respondido: 'Respondida',
};

function tone(status: string): string {
  const s = String(status).toLowerCase();
  if (s.startsWith('aprov')) return 'concluida';
  if (s.startsWith('reje') || s.startsWith('recus')) return 'nao-feita';
  return 'alerta';
}

export function ColaboradorSolicitacoesScreen() {
  const { baseUrl, token, handle } = useApi();
  const [itens, setItens] = useState<Ocorrencia[] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [aberto, setAberto] = useState(false);

  const [tipo, setTipo] = useState<'correcao_ponto' | 'justificativa_falta' | 'abono'>('correcao_ponto');
  const [dataRef, setDataRef] = useState(todayIso());
  const [batidasDia, setBatidasDia] = useState<PontoBatida[]>([]);
  const [batidaId, setBatidaId] = useState('');
  const [justificarTipo, setJustificarTipo] = useState('');
  const [correcaoTipo, setCorrecaoTipo] = useState('');
  const [correcaoHora, setCorrecaoHora] = useState('');
  const [descricao, setDescricao] = useState('');
  const [anexo, setAnexo] = useState<{ dataUrl: string; nome: string } | null>(null);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setItens(await handle(() => col.ocorrencias(baseUrl, token)));
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  }, [baseUrl, token, handle]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    if (tipo !== 'correcao_ponto') {
      setBatidasDia([]);
      return;
    }
    let cancelado = false;
    col
      .batidas(baseUrl, token, dataRef)
      .then((r) => {
        if (!cancelado) setBatidasDia(r.batidas);
      })
      .catch(() => {
        if (!cancelado) setBatidasDia([]);
      });
    return () => {
      cancelado = true;
    };
  }, [baseUrl, token, dataRef, tipo]);

  const anexar = async () => {
    const escolhida = await escolherFoto();
    if (escolhida) setAnexo({ dataUrl: escolhida, nome: 'anexo.jpg' });
  };

  const enviar = async () => {
    if (!descricao.trim()) {
      setError('Descreva o motivo da solicitação.');
      return;
    }
    setBusy(true);
    setError(null);
    setOk(null);
    try {
      await handle(() =>
        col.solicitarOcorrencia(baseUrl, token, {
          tipo,
          data_referencia: dataRef || undefined,
          registro_id: batidaId ? Number(batidaId) : undefined,
          justificar_tipo: justificarTipo || undefined,
          correcao_tipo: correcaoTipo || undefined,
          correcao_data_hora: correcaoHora ? `${dataRef} ${correcaoHora}:00` : undefined,
          descricao: descricao.trim(),
          anexo: anexo?.dataUrl,
          anexo_nome: anexo?.nome,
        }),
      );
      setOk('Solicitação enviada para o RH.');
      setAberto(false);
      setDescricao('');
      setAnexo(null);
      setBatidaId('');
      setJustificarTipo('');
      setCorrecaoTipo('');
      setCorrecaoHora('');
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
        title="Justificar / corrigir"
        subtitle={itens ? `${itens.length} solicitação(ões)` : 'Carregando…'}
        onBack={() => navigate('colaborador')}
      />

      <div className="content">
        {error ? <Alert kind="error">{error}</Alert> : null}
        {ok ? <Alert kind="ok">{ok}</Alert> : null}

        {!aberto ? (
          <div className="actionbar">
            <Button block onClick={() => setAberto(true)}>
              Nova correção de ponto
            </Button>
            <Button variant="ghost" block onClick={() => navigate('colaborador/ausencias')}>
              Folga / férias
            </Button>
          </div>
        ) : null}

        {aberto ? (
          <Card>
            <div className="section-head">
              <p className="section-title">Nova solicitação</p>
              <button type="button" className="reset-button" onClick={() => setAberto(false)}>
                Fechar
              </button>
            </div>

            <div className="stack" style={{ marginTop: 10 }}>
              <Field label="Tipo">
                <Select value={tipo} onChange={(e) => setTipo(e.target.value as typeof tipo)}>
                  {TIPOS_OCORRENCIA.map((t) => (
                    <option key={t.key} value={t.key}>
                      {t.label}
                    </option>
                  ))}
                </Select>
              </Field>

              <Field label="Data de referência">
                <Input type="date" value={dataRef} onChange={(e) => setDataRef(e.target.value)} />
              </Field>

              {tipo === 'correcao_ponto' ? (
                <>
                  <Field label="Batida registrada neste dia (opcional)">
                    <Select value={batidaId} onChange={(e) => setBatidaId(e.target.value)}>
                      <option value="">Nenhuma / batida faltante</option>
                      {batidasDia.map((b) => (
                        <option key={b.id} value={String(b.id)}>
                          {(PONTO_LABELS[b.tipo] ?? b.tipo)} · {b.hora}
                        </option>
                      ))}
                    </Select>
                  </Field>
                  <Field label="O que deseja justificar / corrigir?">
                    <Select value={justificarTipo} onChange={(e) => setJustificarTipo(e.target.value)}>
                      <option value="">Selecione</option>
                      {Object.entries(PONTO_LABELS).map(([k, v]) => (
                        <option key={k} value={k}>
                          {v}
                        </option>
                      ))}
                    </Select>
                  </Field>
                  <div className="grid-2">
                    <Field label="Corrigir tipo">
                      <Select value={correcaoTipo} onChange={(e) => setCorrecaoTipo(e.target.value)}>
                        <option value="">Manter</option>
                        {Object.entries(PONTO_LABELS).map(([k, v]) => (
                          <option key={k} value={k}>
                            {v}
                          </option>
                        ))}
                      </Select>
                    </Field>
                    <Field label="Horário correto">
                      <Input type="time" value={correcaoHora} onChange={(e) => setCorrecaoHora(e.target.value)} />
                    </Field>
                  </div>
                </>
              ) : null}

              <Field label="Motivo" hint="Explique o que aconteceu — o RH responde por aqui.">
                <Textarea value={descricao} onChange={(e) => setDescricao(e.target.value)} maxLength={2000} />
              </Field>

              <button type="button" className="capture-btn" onClick={() => void anexar()}>
                {anexo ? 'Trocar anexo' : 'Anexar foto ou documento (opcional)'}
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

              <Button block loading={busy} onClick={() => void enviar()}>
                Enviar para o RH
              </Button>
            </div>
          </Card>
        ) : null}

        <p className="section-title">Minhas solicitações</p>
        {loading ? (
          <SkeletonList count={3} />
        ) : !itens?.length ? (
          <Empty title="Nenhuma solicitação" message="Correções, justificativas e abonos aparecem aqui." />
        ) : (
          <div className="stack">
            {itens.map((o) => (
              <Card key={o.id}>
                <div className="card__head">
                  <p className="card__title">
                    {TIPOS_OCORRENCIA.find((t) => t.key === o.tipo)?.label ?? o.tipo}
                  </p>
                  <span className={`pill pill--${tone(o.status)}`}>{STATUS_LABEL[String(o.status).toLowerCase()] ?? o.status}</span>
                </div>
                <p className="card__meta" style={{ marginTop: 4 }}>
                  {o.data_referencia ? `${dateBr(o.data_referencia)} · ` : ''}
                  {dateTimeBr(o.created_at)}
                </p>
                {o.descricao ? <p className="prose" style={{ marginTop: 8 }}>{o.descricao}</p> : null}
                {o.resposta ? (
                  <div className="hint-box" style={{ marginTop: 8 }}>
                    <span>RH: {o.resposta}</span>
                  </div>
                ) : null}
              </Card>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
