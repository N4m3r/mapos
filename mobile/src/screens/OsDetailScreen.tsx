/** Detalhe da OS: dados, itens, anotações, ações rápidas (ligar/WhatsApp/status). */

import { useCallback, useEffect, useState } from 'react';
import * as api from '../lib/api';
import { useApi, useApp } from '../lib/store';
import { dateBr, dateTimeBr, money, statusColor, telHref, toApiDate, whatsHref } from '../lib/format';
import { navigate } from '../lib/router';
import { Alert, Badge, Button, Card, Kv, SkeletonList, Textarea, Topbar } from '../components/ui';
import { OS_STATUS, type Os } from '../lib/types';
import { BotoesPdf } from '../components/BotoesPdf';

export function OsDetailScreen({ id }: { id: string }) {
  const { baseUrl, token, handle } = useApi();
  const { can } = useApp();
  const [os, setOs] = useState<Os | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [nota, setNota] = useState('');
  const [saving, setSaving] = useState(false);
  const [feedback, setFeedback] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await handle(() => api.getOs(baseUrl, token, id));
      setOs(result);
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  }, [baseUrl, token, id, handle]);

  useEffect(() => {
    void load();
  }, [load]);

  const changeStatus = async (status: string) => {
    if (!os) return;
    setSaving(true);
    setFeedback(null);
    try {
      const updated = await handle(() =>
        api.updateOs(baseUrl, token, id, {
          clientes_id: os.clientes_id,
          usuarios_id: os.usuarios_id,
          status,
          dataInicial: toApiDate(os.dataInicial),
          dataFinal: toApiDate(os.dataFinal),
          descricaoProduto: os.descricaoProduto ?? '',
          defeito: os.defeito ?? '',
          observacoes: os.observacoes ?? '',
          laudoTecnico: os.laudoTecnico ?? '',
          garantia: os.garantia ?? '',
        }),
      );
      setOs(updated);
      setFeedback(`Status alterado para ${status}.`);
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setSaving(false);
    }
  };

  const addNota = async () => {
    if (!nota.trim()) return;
    setSaving(true);
    setFeedback(null);
    try {
      await handle(() => api.addAnotacao(baseUrl, token, id, nota.trim()));
      setNota('');
      setFeedback('Anotação registrada.');
      await load();
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setSaving(false);
    }
  };

  const tel = telHref(os?.celular_cliente ?? os?.telefone_cliente);
  const zap = whatsHref(os?.celular_cliente ?? os?.telefone_cliente, os?.textoWhatsApp);

  return (
    <div className="screen">
      <Topbar title={os ? `OS #${os.idOs}` : 'Ord. de serviço'} subtitle={os?.nomeCliente} onBack={() => navigate('os')} />

      <div className="content">
        <BotoesPdf carregar={() => api.osPdf(baseUrl, token, Number(id))} nome={`OS-${String(id).padStart(4, '0')}.pdf`} />
        {loading ? <SkeletonList count={3} /> : null}
        {error ? <Alert kind="error">{error}</Alert> : null}
        {feedback ? <Alert kind="ok">{feedback}</Alert> : null}

        {os ? (
          <>
            <Card>
              <div className="card__head">
                <div>
                  <p className="card__title">{os.nomeCliente ?? 'Cliente'}</p>
                  <p className="card__meta">{os.nome ? `Técnico: ${os.nome}` : 'Sem técnico'}</p>
                </div>
                <Badge color={statusColor(os.status)}>{os.status}</Badge>
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
              <Kv k="Abertura" v={dateBr(os.dataInicial)} />
              <Kv k="Previsão/final" v={dateBr(os.dataFinal)} />
              <Kv k="Faturado" v={Number(os.faturado) === 1 ? 'Sim' : 'Não'} />
              {os.garantia ? <Kv k="Garantia" v={os.garantia} /> : null}
              {os.refGarantia ? <Kv k="Termo" v={os.refGarantia} /> : null}
              <Kv k="Total" v={<strong>{money(Number(os.calcTotal ?? Number(os.totalProdutos ?? 0) + Number(os.totalServicos ?? 0)))}</strong>} />
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

            {os.observacoes ? (
              <Card>
                <p className="section-title">Observações</p>
                <p className="prose">{os.observacoes}</p>
              </Card>
            ) : null}

            {os.laudoTecnico ? (
              <Card>
                <p className="section-title">Laudo técnico</p>
                <p className="prose">{os.laudoTecnico}</p>
              </Card>
            ) : null}

            {Array.isArray(os.servicos) && os.servicos.length > 0 ? (
              <Card>
                <p className="section-title">Serviços</p>
                {os.servicos.map((s, i) => (
                  <div className="kv" key={`${s.servicos_id}-${i}`}>
                    <span className="kv__k" style={{ color: 'var(--text)' }}>
                      {s.nome ?? `Serviço ${s.servicos_id}`}
                      {Number(s.quantidade) > 1 ? ` ×${s.quantidade}` : ''}
                    </span>
                    <span className="kv__v">{money(Number(s.preco) * Number(s.quantidade))}</span>
                  </div>
                ))}
              </Card>
            ) : null}

            {Array.isArray(os.produtos) && os.produtos.length > 0 ? (
              <Card>
                <p className="section-title">Produtos</p>
                {os.produtos.map((p, i) => (
                  <div className="kv" key={`${p.produtos_id}-${i}`}>
                    <span className="kv__k" style={{ color: 'var(--text)' }}>
                      {p.descricao ?? `Produto ${p.produtos_id}`}
                      {Number(p.quantidade) > 1 ? ` ×${p.quantidade}` : ''}
                    </span>
                    <span className="kv__v">{money(Number(p.preco) * Number(p.quantidade))}</span>
                  </div>
                ))}
              </Card>
            ) : null}

            {can('eOs') ? (
              <Card>
                <p className="section-title">Situação</p>
                <div className="chips" style={{ flexWrap: 'wrap', overflow: 'visible' }}>
                  {OS_STATUS.map((st) => (
                    <button
                      key={st}
                      type="button"
                      className={`chip${os.status === st ? ' chip--active' : ''}`}
                      disabled={saving}
                      onClick={() => void changeStatus(st)}
                    >
                      {st}
                    </button>
                  ))}
                </div>
                {can('eOs') ? (
                  <Button variant="ghost" block small onClick={() => navigate(`os-editar/${id}`)} style={{ marginTop: 10 }}>
                    Editar OS completa
                  </Button>
                ) : null}
              </Card>
            ) : null}

            <Card>
              <p className="section-title">Anotações</p>
              {Array.isArray(os.anotacoes) && os.anotacoes.length > 0 ? (
                <div className="stack" style={{ marginBottom: 12 }}>
                  {os.anotacoes.map((a) => (
                    <div key={a.idAnotacoes} style={{ borderLeft: '3px solid var(--brand)', paddingLeft: 10 }}>
                      <p className="prose">{a.anotacao}</p>
                      <p className="card__meta">{dateTimeBr(a.data_hora)}</p>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="card__meta">Nenhuma anotação ainda.</p>
              )}

              {can('eOs') ? (
                <div className="stack">
                  <Textarea
                    placeholder="Escreva uma anotação técnica…"
                    aria-label="Nova anotação da OS"
                    value={nota}
                    onChange={(e) => setNota(e.target.value)}
                    maxLength={2000}
                  />
                  <Button block loading={saving} disabled={!nota.trim()} onClick={() => void addNota()}>
                    Adicionar anotação
                  </Button>
                </div>
              ) : null}
            </Card>
          </>
        ) : null}
      </div>
    </div>
  );
}
