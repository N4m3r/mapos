/** Detalhe do projeto: pontos a executar, OS vinculadas, consumo e RDO. */

import { useState } from 'react';
import * as tec from '../../lib/api-tecnico';
import { useCarga } from '../../lib/hooks';
import { useApi } from '../../lib/store';
import { navigate } from '../../lib/router';
import { dateBr } from '../../lib/format';
import { Alert, Button, Card, Empty, Progress, Sheet, SkeletonList, Textarea, Topbar } from '../../components/ui';
import type { PontoProjeto, ProjetoDetalhe } from '../../lib/types-tecnico';

function progresso(pontos: PontoProjeto[], lista: Record<string, { rotulo: string; peso?: number }>): number {
  if (!pontos.length) return 0;
  const soma = pontos.reduce((acc, p) => acc + (Number(lista[p.status]?.peso) || 0), 0);
  return Math.round(soma / pontos.length);
}

export function TecnicoProjetoScreen({ id }: { id: string }) {
  const { baseUrl, token, handle } = useApi();
  const { data, loading, error, recarregar } = useCarga<ProjetoDetalhe>((url, tk) => tec.getProjeto(url, tk, id), [id]);
  const [assumindo, setAssumindo] = useState<PontoProjeto | null>(null);
  const [acaoErro, setAcaoErro] = useState<string | null>(null);
  const [rdo, setRdo] = useState(false);
  const [atividades, setAtividades] = useState('');
  const [busy, setBusy] = useState(false);
  const [ok, setOk] = useState<string | null>(null);

  const pontos = data?.pontos ?? [];
  const lista = data?.statusLista ?? {};
  const pct = progresso(pontos, lista);

  const assumir = async (ponto: PontoProjeto) => {
    if (!data) return;
    setBusy(true);
    setAcaoErro(null);
    try {
      await handle(() => tec.assumirPonto(baseUrl, token, ponto.idPonto, data.obra.idObra));
      setOk(`${ponto.identificacao} assumido por você.`);
      setAssumindo(null);
      recarregar();
    } catch (err) {
      setAcaoErro((err as Error).message);
    } finally {
      setBusy(false);
    }
  };

  const repassar = async (ponto: PontoProjeto, destino: string) => {
    if (!data) return;
    setBusy(true);
    setAcaoErro(null);
    try {
      const r = await handle(() =>
        tec.repassarPonto(baseUrl, token, ponto.idPonto, {
          obra_id: data.obra.idObra,
          repassar_para: destino,
        }),
      );
      setOk(r.destinatario_id ? 'Ponto repassado.' : 'Ponto devolvido para a equipe.');
      setAssumindo(null);
      recarregar();
    } catch (err) {
      setAcaoErro((err as Error).message);
    } finally {
      setBusy(false);
    }
  };

  const salvarRdo = async () => {
    if (!data || !atividades.trim()) return;
    setBusy(true);
    setAcaoErro(null);
    try {
      await handle(() =>
        tec.registrarRdo(baseUrl, token, data.obra.idObra, {
          atividades: atividades.trim(),
          condicao: 'praticavel',
        }),
      );
      setOk('RDO registrado.');
      setRdo(false);
      setAtividades('');
      recarregar();
    } catch (err) {
      setAcaoErro((err as Error).message);
    } finally {
      setBusy(false);
    }
  };

  const executores = data?.executores ?? {};

  return (
    <div className="screen">
      <Topbar
        title={data?.obra.nome ?? 'Projeto'}
        subtitle={data?.obra.nomeCliente ?? undefined}
        onBack={() => navigate('tecnico/projetos')}
      />

      <div className="content">
        {error ? (
          <div className="stack">
            <Alert kind="error">{error}</Alert>
            <button type="button" className="reset-button" onClick={recarregar}>
              Tentar novamente
            </button>
          </div>
        ) : null}
        {acaoErro ? <Alert kind="error">{acaoErro}</Alert> : null}
        {ok ? <Alert kind="ok">{ok}</Alert> : null}

        {loading && !data ? <SkeletonList count={4} /> : null}

        {data ? (
          <>
            <Card>
              <div className="card__head">
                <p className="card__title">Andamento</p>
                <span className="pill pill--info">{pct}%</span>
              </div>
              <div style={{ marginTop: 10 }}>
                <Progress value={pct} />
              </div>
              <p className="card__meta" style={{ marginTop: 8 }}>
                {pontos.length} ponto(s)
                {data.obra.endereco ? ` · ${data.obra.endereco}` : ''}
                {data.obra.data_previsao ? ` · previsão ${dateBr(data.obra.data_previsao)}` : '' }
              </p>
              <div className="actionbar" style={{ marginTop: 12 }}>
                <Button small block onClick={() => setRdo(true)}>
                  Registrar RDO
                </Button>
                <Button small block variant="ghost" onClick={() => navigate('tecnico/fila')}>
                  Minha fila
                </Button>
              </div>
              <Button
                small
                block
                variant="ghost"
                style={{ marginTop: 10 }}
                onClick={() => navigate(`tecnico/camera/${data.obra.idObra}`)}
              >
                Configurar câmeras e NVR
              </Button>
            </Card>

            <div className="section-head">
              <p className="section-title">Pontos ({pontos.length})</p>
            </div>

            {pontos.length ? (
              <Card style={{ padding: '4px 14px' }}>
                <div className="timeline">
                  {pontos.map((ponto) => {
                    const status = lista[ponto.status];
                    return (
                      <button
                        key={ponto.idPonto}
                        type="button"
                        className="timeline__row"
                        style={{ gridTemplateColumns: '1fr auto' }}
                        onClick={() => navigate(`tecnico/ponto/${data.obra.idObra}/${ponto.idPonto}`)}
                      >
                        <span style={{ minWidth: 0 }}>
                          <span className="timeline__cli" style={{ display: 'block' }}>
                            {ponto.identificacao}
                          </span>
                          <span className="card__meta">
                            {ponto.local || ponto.tipo || 'Ponto'}
                            {ponto.minha ? ' · com você' : ponto.responsavel ? ` · ${ponto.responsavel}` : ''}
                          </span>
                        </span>
                        <span className={`pill pill--${status?.classe ?? 'neutro'}`}>
                          {status?.rotulo ?? ponto.status_rotulo ?? ponto.status}
                        </span>
                      </button>
                    );
                  })}
                </div>
              </Card>
            ) : (
              <Empty title="Sem pontos cadastrados" message="O projeto ainda não tem pontos para executar." />
            )}

            {pontos.some((p) => !p.minha) ? (
              <Card>
                <p className="section-title">Pontos livres / da equipe</p>
                <div className="stack" style={{ marginTop: 8 }}>
                  {pontos
                    .filter((p) => !p.minha)
                    .slice(0, 6)
                    .map((p) => (
                      <button
                        key={`assumir-${p.idPonto}`}
                        type="button"
                        className="listrow"
                        onClick={() => setAssumindo(p)}
                      >
                        <span className="listrow__main">
                          <span className="listrow__title">{p.identificacao}</span>
                          <span className="listrow__meta">
                            {p.responsavel ? `Com ${p.responsavel}` : 'Sem responsável'}
                          </span>
                        </span>
                        <span className="pill pill--agendada">Assumir</span>
                      </button>
                    ))}
                </div>
              </Card>
            ) : null}

            {data.osVinculadas.length ? (
              <Card>
                <p className="section-title">OS vinculadas</p>
                {data.osVinculadas.map((os) => (
                  <button
                    key={os.idOs}
                    type="button"
                    className="listrow"
                    onClick={() => navigate(`tecnico/atendimento/${os.idOs}`)}
                  >
                    <span className="listrow__main">
                      <span className="listrow__title">OS {String(os.idOs).padStart(4, '0')}</span>
                      <span className="listrow__meta">{os.defeito || dateBr(os.dataInicial)}</span>
                    </span>
                    <span className="pill pill--agendada">{os.status}</span>
                  </button>
                ))}
              </Card>
            ) : null}

            {data.consumo.length ? (
              <Card>
                <p className="section-title">Material e serviços lançados</p>
                {data.consumo.slice(0, 12).map((c, i) => (
                  <div className="kv" key={`${c.nome}-${i}`}>
                    <span className="kv__k" style={{ color: 'var(--text)' }}>
                      {c.nome}
                    </span>
                    <span className="kv__v">
                      {c.quantidade} {c.tipo === 'material' ? 'un.' : ''}
                    </span>
                  </div>
                ))}
              </Card>
            ) : null}

            {data.rdos.length ? (
              <Card>
                <p className="section-title">RDOs</p>
                {data.rdos.map((r) => (
                  <div className="kv" key={r.id}>
                    <span className="kv__k">
                      RDO {r.numero ?? r.id} · {dateBr(r.data)}
                    </span>
                    <span className="kv__v" style={{ fontSize: '0.8rem' }}>
                      {String(r.atividades ?? '').slice(0, 60)}
                    </span>
                  </div>
                ))}
              </Card>
            ) : null}
          </>
        ) : null}
      </div>

      <Sheet
        open={Boolean(assumindo)}
        title={assumindo ? `Ponto ${assumindo.identificacao}` : 'Ponto'}
        onClose={() => setAssumindo(null)}
      >
        {assumindo ? (
          <>
            <p className="card__meta">
              {assumindo.local || 'Sem local informado'}
              {assumindo.responsavel ? ` · hoje com ${assumindo.responsavel}` : ' · sem responsável'}
            </p>
            {assumindo.responsavel ? (
              <div className="stack">
                <Button block loading={busy} onClick={() => void repassar(assumindo, 'equipe')}>
                  Devolver para a equipe
                </Button>
                {(executores[String(data?.obra.idObra)] ?? [])
                  .filter((ex) => ex.nome !== assumindo.responsavel)
                  .slice(0, 3)
                  .map((ex) => (
                    <Button key={ex.idUsuarios} block variant="ghost" loading={busy} onClick={() => void repassar(assumindo, String(ex.idUsuarios))}>
                      Repassar para {ex.nome.split(' ')[0]}
                    </Button>
                  ))}
              </div>
            ) : (
              <Button block loading={busy} onClick={() => void assumir(assumindo)}>
                Assumir este ponto
              </Button>
            )}
          </>
        ) : null}
      </Sheet>

      <Sheet
        open={rdo}
        title="Registro diário (RDO)"
        onClose={() => setRdo(false)}
        footer={
          <>
            <Button variant="ghost" block onClick={() => setRdo(false)}>
              Cancelar
            </Button>
            <Button block loading={busy} disabled={!atividades.trim()} onClick={() => void salvarRdo()}>
              Registrar
            </Button>
          </>
        }
      >
        <Textarea
          value={atividades}
          onChange={(e) => setAtividades(e.target.value)}
          maxLength={4000}
          placeholder="O que foi executado hoje neste projeto?"
        />
        <p className="card__meta">
          Material e serviços podem ser lançados depois, pela versão web do projeto.
        </p>
      </Sheet>
    </div>
  );
}
