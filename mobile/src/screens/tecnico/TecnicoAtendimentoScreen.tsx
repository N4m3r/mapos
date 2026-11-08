/**
 * Atendimento da OS pelo técnico: check-in, fotos, observações, check-out com
 * assinaturas e registro de serviço não realizado. As regras ficam no servidor
 * (geofence, assinatura obrigatória, status); aqui só conduzimos o fluxo.
 */

import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import * as tec from '../../lib/api-tecnico';
import { useApi } from '../../lib/store';
import { navigate } from '../../lib/router';
import { dateBr, dateTimeBr, telHref, whatsHref } from '../../lib/format';
import { obterLocalizacao, escolherFoto } from '../../lib/capture';
import { Alert, Button, Card, Field, Input, Kv, Select, SkeletonList, StatusPill, Textarea, Topbar } from '../../components/ui';
import { Assinatura, lerAssinatura } from '../../components/Assinatura';
import { Fotos } from '../../components/Fotos';
import type { TecnicoOsDetalhe } from '../../lib/types-tecnico';
import { BotoesPdf } from '../../components/BotoesPdf';
import * as api from '../../lib/api';

type Modo = null | 'checkin' | 'checkout' | 'nao-realizado' | 'observacao' | 'foto';

export function TecnicoAtendimentoScreen({ id }: { id: string }) {
  const { baseUrl, token, handle } = useApi();
  const [detalhe, setDetalhe] = useState<TecnicoOsDetalhe | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [modo, setModo] = useState<Modo>(null);
  const [busy, setBusy] = useState(false);

  // formulários dos painéis
  const [obsCheckin, setObsCheckin] = useState('');
  const [obsCheckout, setObsCheckout] = useState('');
  const [assinaturaTecnico, setAssinaturaTecnico] = useState(false);
  const [assinaturaCliente, setAssinaturaCliente] = useState(false);
  const [nomeCliente, setNomeCliente] = useState('');
  const [docCliente, setDocCliente] = useState('');
  const [fotosCheckout, setFotosCheckout] = useState<string[]>([]);
  const [motivo, setMotivo] = useState('');
  const [obsMotivo, setObsMotivo] = useState('');
  const [textoObs, setTextoObs] = useState('');

  const canvasTec = useRef<HTMLCanvasElement | null>(null);
  const canvasCli = useRef<HTMLCanvasElement | null>(null);
  const canvasCheckin = useRef<HTMLCanvasElement | null>(null);
  const [temAssinaturaCheckin, setTemAssinaturaCheckin] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setDetalhe(await handle(() => tec.osDetalhe(baseUrl, token, id)));
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  }, [baseUrl, token, id, handle]);

  useEffect(() => {
    void load();
  }, [load]);

  const os = detalhe?.os;
  const cliente = detalhe?.cliente;
  const celular = cliente?.celular ?? cliente?.telefone ?? undefined;
  const tel = telHref(celular);
  const zap = whatsHref(celular, `Olá! Sobre a OS ${String(os?.idOs ?? '').padStart(4, '0')}.`);

  const fotos = useMemo(() => detalhe?.fotos ?? [], [detalhe]);
  const observacoes = detalhe?.observacoes ?? [];

  const finalizado = os ? ['Finalizado', 'Faturado', 'Cancelado'].includes(os.status) : false;

  const executar = async (fn: () => Promise<unknown>, mensagem: string) => {
    setBusy(true);
    setError(null);
    setOk(null);
    try {
      await fn();
      setOk(mensagem);
      setModo(null);
      await load();
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setBusy(false);
    }
  };

  const posicao = async () => {
    const pos = await obterLocalizacao();
    return pos ? { latitude: pos.latitude, longitude: pos.longitude } : {};
  };

  const fazerCheckin = async () => {
    const geo = await posicao();
    const assinatura = lerAssinatura(canvasCheckin.current) ?? undefined;
    await executar(
      () =>
        tec.checkin(baseUrl, token, {
          os_id: id,
          observacao: obsCheckin.trim() || undefined,
          assinatura,
          ...geo,
        }),
      'Atendimento iniciado.',
    );
    setObsCheckin('');
    setTemAssinaturaCheckin(false);
  };

  const fazerCheckout = async () => {
    const assinatura = lerAssinatura(canvasTec.current);
    if (!assinatura) {
      setError('Assine com o dedo no campo do técnico para finalizar.');
      return;
    }
    const assinaturaCli = lerAssinatura(canvasCli.current);
    if (!assinaturaCli && !Number(os?.nao_programada)) {
      setError('Assinatura do cliente obrigatória para finalizar o atendimento.');
      return;
    }
    const geo = await posicao();
    await executar(
      () =>
        tec.checkout(baseUrl, token, {
          os_id: id,
          assinatura,
          assinatura_cliente: assinaturaCli ?? undefined,
          nome_cliente: nomeCliente.trim() || undefined,
          documento_cliente: docCliente.trim() || undefined,
          observacao: obsCheckout.trim() || undefined,
          fotos: fotosCheckout,
          ...geo,
        }),
      'Atendimento finalizado.',
    );
    setObsCheckout('');
    setFotosCheckout([]);
  };

  const enviarFotoRapida = async () => {
    const foto = await escolherFoto();
    if (!foto) return;
    await executar(() => tec.enviarFoto(baseUrl, token, { os_id: id, foto, etapa: 'durante' }), 'Foto enviada.');
  };

  const registrarNaoRealizado = async () => {
    if (!motivo && !obsMotivo.trim()) {
      setError('Escolha um motivo ou descreva o que aconteceu.');
      return;
    }
    await executar(
      () =>
        tec.marcarNaoRealizado(baseUrl, token, {
          os_id: id,
          motivo_id: motivo ? Number(motivo) : undefined,
          observacao: obsMotivo.trim() || undefined,
        }),
      'Registrado. A OS foi para "Não Realizadas".',
    );
  };

  const salvarObservacao = async () => {
    if (!textoObs.trim()) return;
    await executar(
      () => tec.addObservacao(baseUrl, token, { os_id: id, texto: textoObs.trim() }),
      'Observação registrada.',
    );
    setTextoObs('');
  };

  const abrirFoto = async () => {
    const foto = await escolherFoto();
    if (foto) setFotosCheckout((lista) => [...lista, foto]);
  };

  return (
    <div className="screen">
      <Topbar
        title={os ? `OS ${String(os.idOs).padStart(4, '0')}` : 'Atendimento'}
        subtitle={cliente?.nomeCliente ?? undefined}
        onBack={() => navigate('tecnico/os')}
      />

      <div className="content">
        <BotoesPdf carregar={() => api.osPdf(baseUrl, token, Number(id))} nome={`OS-${String(id).padStart(4, '0')}.pdf`} />
        {loading ? <SkeletonList count={4} /> : null}
        {error ? <Alert kind="error">{error}</Alert> : null}
        {ok ? <Alert kind="ok">{ok}</Alert> : null}

        {detalhe && os ? (
          <>
            <Card>
              <div className="card__head">
                <div style={{ minWidth: 0 }}>
                  <p className="card__title">{cliente?.nomeCliente ?? 'Cliente'}</p>
                  <p className="card__meta">{detalhe.endereco || 'Sem endereço cadastrado'}</p>
                </div>
                <StatusPill text={os.status} tone={statusTone(os.status)} />
              </div>

              {detalhe.checkinAtivo ? (
                <p className="card__meta" style={{ marginTop: 8 }}>
                  Atendimento iniciado em {dateTimeBr(detalhe.checkinAtivo.data_entrada)}
                </p>
              ) : null}

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
                {detalhe.urlRota ? (
                  <a className="btn btn--ghost btn--sm" href={detalhe.urlRota} target="_blank" rel="noopener noreferrer">
                    Rota
                  </a>
                ) : null}
              </div>
            </Card>

            {!finalizado ? (
              <div className="quick">
                {detalhe.podeCheckin ? (
                  <button type="button" className="quick__item" onClick={() => setModo('checkin')}>
                    <IconeTec d="M8 5v14l11-7z" />
                    Iniciar atendimento
                    <span className="quick__meta">Check-in com GPS</span>
                  </button>
                ) : null}
                {detalhe.podeCheckout ? (
                  <button type="button" className="quick__item" onClick={() => setModo('checkout')}>
                    <IconeTec d="M20 6 9 17l-5-5" />
                    Finalizar
                    <span className="quick__meta">Assinaturas e fotos</span>
                  </button>
                ) : null}
                {detalhe.podeFoto ? (
                  <button type="button" className="quick__item" onClick={() => void enviarFotoRapida()}>
                    <IconeTec d="M4 8h3l2-3h6l2 3h3v11H4zM12 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                    Tirar foto
                    <span className="quick__meta">Câmera do aparelho</span>
                  </button>
                ) : null}
                <button type="button" className="quick__item" onClick={() => setModo('observacao')}>
                  <IconeTec d="M4 5h16v10H8l-4 4z" />
                  Observação
                  <span className="quick__meta">Registrar no atendimento</span>
                </button>
                {detalhe.podeNaoRealizado ? (
                  <button type="button" className="quick__item" onClick={() => setModo('nao-realizado')}>
                    <IconeTec d="M12 9v4m0 4h.01M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" />
                    Não deu para fazer
                    <span className="quick__meta">Motivo e reagendamento</span>
                  </button>
                ) : null}
              </div>
            ) : (
              <Alert kind="info">
                Esta OS está {os.status.toLowerCase()} e não aceita novas ações de campo.
              </Alert>
            )}

            <Card>
              <p className="section-title">Ordem de serviço</p>
              <Kv k="Abertura" v={dateBr(os.dataInicial)} />
              {os.data_agendamento ? <Kv k="Agendada" v={dateTimeBr(os.data_agendamento)} /> : null}
              {os.tipo_servico ? <Kv k="Tipo de serviço" v={os.tipo_servico} /> : null}
              {os.chamado_id ? <Kv k="Chamado" v={os.chamado_id} /> : null}
              {os.prioridade ? <Kv k="Prioridade" v={os.prioridade} /> : null}
              {os.garantia ? <Kv k="Garantia" v={String(os.garantia)} /> : null}
              {Number(os.nao_programada) ? <Kv k="Origem" v="Atividade não programada" /> : null}
            </Card>

            {os.descricaoProduto ? (
              <Card>
                <p className="section-title">Produto / descrição</p>
                <p className="prose">{os.descricaoProduto}</p>
              </Card>
            ) : null}

            {os.defeito ? (
              <Card>
                <p className="section-title">Defeito relatado</p>
                <p className="prose">{os.defeito}</p>
              </Card>
            ) : null}

            {(detalhe.servicosPrevistos?.length || detalhe.produtosPrevistos?.length) ? (
              <Card>
                <p className="section-title">Previsto para o atendimento</p>
                {detalhe.servicosPrevistos?.map((s, i) => (
                  <div className="kv" key={`s-${i}`}>
                    <span className="kv__k" style={{ color: 'var(--text)' }}>
                      {s.nome}
                    </span>
                    <span className="kv__v">×{s.quantidade}</span>
                  </div>
                ))}
                {detalhe.produtosPrevistos?.map((p, i) => (
                  <div className="kv" key={`p-${i}`}>
                    <span className="kv__k" style={{ color: 'var(--text)' }}>
                      {p.nome}
                    </span>
                    <span className="kv__v">×{p.quantidade}</span>
                  </div>
                ))}
              </Card>
            ) : null}

            {fotos.length ? (
              <Card>
                <p className="section-title">Fotos do atendimento ({fotos.length})</p>
                <Fotos
                  itens={fotos.map((f) => ({ path: f.url ?? '', etapa: f.etapa ?? null, titulo: f.descricao ?? undefined }))}
                />
              </Card>
            ) : null}

            <Card>
              <div className="section-head">
                <p className="section-title">Observações ({observacoes.length})</p>
                {!finalizado ? (
                  <button type="button" className="reset-button" onClick={() => setModo('observacao')}>
                    + Adicionar
                  </button>
                ) : null}
              </div>
              {observacoes.length ? (
                <div className="stack" style={{ marginTop: 8 }}>
                  {observacoes.map((o) => (
                    <div key={o.id} style={{ borderLeft: '3px solid var(--brand)', paddingLeft: 10 }}>
                      <p className="prose">{o.texto}</p>
                      <p className="card__meta">
                        {o.autor} · {o.hora}
                      </p>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="card__meta" style={{ marginTop: 6 }}>
                  Nenhuma observação registrada.
                </p>
              )}
            </Card>

            {detalhe.historicoCliente?.length ? (
              <Card>
                <p className="section-title">Histórico do cliente</p>
                {detalhe.historicoCliente.map((h) => (
                  <div className="kv" key={h.idOs}>
                    <span className="kv__k">OS {String(h.idOs).padStart(4, '0')}</span>
                    <span className="kv__v">
                      {dateBr(h.dataInicial)} · {h.status}
                    </span>
                  </div>
                ))}
              </Card>
            ) : null}
          </>
        ) : null}
      </div>

      {/* ---------------- Check-in ---------------- */}
      {modo === 'checkin' ? (
        <Painel titulo="Iniciar atendimento" onClose={() => setModo(null)}>
          <p className="card__meta">
            O horário e sua localização são registrados como início do atendimento na OS.
          </p>
          <Field label="Observação de chegada (opcional)">
            <Textarea
              value={obsCheckin}
              onChange={(e) => setObsCheckin(e.target.value)}
              maxLength={2000}
              placeholder="Ex.: Cheguei ao local, cliente presente."
            />
          </Field>
          <Assinatura
            canvasRef={canvasCheckin}
            onChange={setTemAssinaturaCheckin}
            rotulo="Assinatura do técnico (opcional)"
            altura={140}
          />
          <div className="actionbar">
            <Button variant="ghost" block onClick={() => setModo(null)}>
              Cancelar
            </Button>
            <Button block loading={busy} onClick={() => void fazerCheckin()}>
              Iniciar
            </Button>
          </div>
          {!temAssinaturaCheckin ? (
            <p className="card__meta" style={{ textAlign: 'center' }}>
              A assinatura é opcional no check-in.
            </p>
          ) : null}
        </Painel>
      ) : null}

      {/* ---------------- Check-out ---------------- */}
      {modo === 'checkout' ? (
        <Painel titulo="Finalizar atendimento" onClose={() => setModo(null)}>
          {!Number(os?.nao_programada) ? (
            <div className="hint-box">
              <IconeTec d="M12 9v4m0 4h.01M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" />
              <span>As assinaturas do técnico e do cliente são obrigatórias para encerrar.</span>
            </div>
          ) : (
            <p className="card__meta">
              Esta OS é uma atividade não programada — a assinatura do cliente é opcional.
            </p>
          )}

          <Assinatura canvasRef={canvasTec} onChange={setAssinaturaTecnico} rotulo="Assinatura do técnico" />
          <Assinatura canvasRef={canvasCli} onChange={setAssinaturaCliente} rotulo="Assinatura do cliente" altura={150} />

          <div className="grid-2">
            <Field label="Nome de quem assinou">
              <Input value={nomeCliente} onChange={(e) => setNomeCliente(e.target.value)} maxLength={100} />
            </Field>
            <Field label="Documento">
              <Input value={docCliente} onChange={(e) => setDocCliente(e.target.value)} maxLength={30} />
            </Field>
          </div>

          <Field label="O que foi feito / laudo (opcional)">
            <Textarea
              value={obsCheckout}
              onChange={(e) => setObsCheckout(e.target.value)}
              maxLength={2000}
              placeholder="Ex.: Substituição da fonte, testes realizados."
            />
          </Field>

          <div className="stack">
            <button type="button" className="capture-btn" onClick={() => void abrirFoto()}>
              <IconeTec d="M4 8h3l2-3h6l2 3h3v11H4zM12 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
              {fotosCheckout.length ? `Adicionar outra foto (${fotosCheckout.length})` : 'Tirar foto do serviço'}
            </button>
            {fotosCheckout.length ? (
              <div className="thumbs">
                {fotosCheckout.map((foto, i) => (
                  <div key={i} className="thumb">
                    <img src={foto} alt={`Foto ${i + 1}`} />
                    <button
                      type="button"
                      className="thumb__etapa"
                      onClick={() => setFotosCheckout((lista) => lista.filter((_, idx) => idx !== i))}
                      aria-label="Remover foto"
                    >
                      remover
                    </button>
                  </div>
                ))}
              </div>
            ) : null}
          </div>

          <div className="actionbar">
            <Button variant="ghost" block onClick={() => setModo(null)}>
              Cancelar
            </Button>
            <Button
              block
              loading={busy}
              disabled={!assinaturaTecnico || (!assinaturaCliente && !Number(os?.nao_programada))}
              onClick={() => void fazerCheckout()}
            >
              Finalizar
            </Button>
          </div>
        </Painel>
      ) : null}

      {/* ---------------- Não realizado ---------------- */}
      {modo === 'nao-realizado' ? (
        <Painel titulo="Serviço não realizado" onClose={() => setModo(null)}>
          <Field label="Motivo">
            <Select value={motivo} onChange={(e) => setMotivo(e.target.value)}>
              <option value="">Selecione (ou descreva abaixo)</option>
              {(detalhe?.motivosNaoRealizado ?? []).map((m) => (
                <option key={m.id} value={String(m.id)}>
                  {m.nome}
                </option>
              ))}
            </Select>
          </Field>
          <Field label="O que aconteceu">
            <Textarea
              value={obsMotivo}
              onChange={(e) => setObsMotivo(e.target.value)}
              maxLength={2000}
              placeholder="Ex.: Cliente ausente, local fechado."
            />
          </Field>
          <p className="card__meta">
            A OS sai da sua agenda e vai para "Não Realizadas", onde você pode reagendar ou reabrir.
          </p>
          <div className="actionbar">
            <Button variant="ghost" block onClick={() => setModo(null)}>
              Cancelar
            </Button>
            <Button variant="danger" block loading={busy} onClick={() => void registrarNaoRealizado()}>
              Registrar
            </Button>
          </div>
        </Painel>
      ) : null}

      {/* ---------------- Observação ---------------- */}
      {modo === 'observacao' ? (
        <Painel titulo="Nova observação" onClose={() => setModo(null)}>
          <Field label="Observação do atendimento">
            <Textarea
              value={textoObs}
              onChange={(e) => setTextoObs(e.target.value)}
              maxLength={2000}
              placeholder="Registre o que foi feito, peças usadas, combinados com o cliente…"
            />
          </Field>
          <div className="actionbar">
            <Button variant="ghost" block onClick={() => setModo(null)}>
              Cancelar
            </Button>
            <Button block loading={busy} disabled={!textoObs.trim()} onClick={() => void salvarObservacao()}>
              Salvar
            </Button>
          </div>
        </Painel>
      ) : null}
    </div>
  );
}

function statusTone(status: string): string {
  if (status === 'Em Andamento') return 'andamento';
  if (status === 'Finalizado' || status === 'Faturado') return 'concluida';
  if (status === 'Não Realizado') return 'nao-feita';
  if (status === 'Cancelado') return 'neutro';
  return 'agendada';
}

function IconeTec({ d }: { d: string }) {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
      <path d={d} />
    </svg>
  );
}

/** Painel inferior simples (mesmo desenho do Sheet, com rodapé próprio). */
function Painel({ titulo, onClose, children }: { titulo: string; onClose: () => void; children: React.ReactNode }) {
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [onClose]);

  return (
    <div className="sheet" role="dialog" aria-modal="true" aria-label={titulo}>
      <button type="button" className="sheet__backdrop" aria-label="Fechar" onClick={onClose} />
      <div className="sheet__panel">
        <div className="sheet__head">
          <h2 className="sheet__title">{titulo}</h2>
          <button type="button" className="icon-btn icon-btn--sm" onClick={onClose} aria-label="Fechar">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round">
              <path d="M6 6l12 12M18 6L6 18" />
            </svg>
          </button>
        </div>
        <div className="sheet__body">{children}</div>
      </div>
    </div>
  );
}
