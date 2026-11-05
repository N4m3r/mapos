/** Holerite do mês: proventos, descontos e PDF liberado pelo RH. */

import { useEffect, useState } from 'react';
import * as col from '../../lib/api-colaborador';
import { useApi } from '../../lib/store';
import { navigate } from '../../lib/router';
import { competenciaAtual, competenciaLabel, money, shiftCompetencia } from '../../lib/format';
import { baixarBlob, visualizarBlob } from '../../lib/geo';
import { Alert, Button, Card, SkeletonList, Topbar } from '../../components/ui';
import type { Holerite } from '../../lib/types-tecnico';

export function ColaboradorHoleriteScreen() {
  const { baseUrl, token, handle } = useApi();
  const [competencia, setCompetencia] = useState<string>(() => competenciaAtual());
  const [data, setData] = useState<Holerite | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [baixando, setBaixando] = useState(false);

  useEffect(() => {
    let cancelado = false;
    setLoading(true);
    setError(null);
    handle(() => col.holerite(baseUrl, token, competencia))
      .then((r) => {
        if (!cancelado) setData(r);
      })
      .catch((err: Error) => {
        if (!cancelado) setError(err.message);
      })
      .finally(() => {
        if (!cancelado) setLoading(false);
      });
    return () => {
      cancelado = true;
    };
  }, [baseUrl, token, competencia, handle]);

  const abrirPdf = async (acao: 'ver' | 'baixar') => {
    setBaixando(true);
    setError(null);
    try {
      const blob = await handle(() => col.holeriteArquivo(baseUrl, token, competencia));
      const nome = data?.arquivo?.nome ?? `holerite-${competencia}.pdf`;
      if (acao === 'ver') visualizarBlob(blob, nome);
      else baixarBlob(blob, nome);
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setBaixando(false);
    }
  };

  const atual = competenciaAtual();

  return (
    <div className="screen">
      <Topbar
        title="Holerite"
        subtitle={competenciaLabel(competencia)}
        onBack={() => navigate('colaborador/espelho')}
      />

      <div className="content">
        <div className="row" style={{ gap: 8 }}>
          <Button variant="ghost" small onClick={() => setCompetencia((c) => shiftCompetencia(c, -1))}>
            ‹ Anterior
          </Button>
          <Button
            variant="ghost"
            small
            block
            onClick={() => setCompetencia(competenciaAtual())}
            disabled={competencia === atual}
          >
            Mês atual
          </Button>
          <Button
            variant="ghost"
            small
            onClick={() => setCompetencia((c) => shiftCompetencia(c, 1))}
            disabled={competencia === atual}
          >
            Próximo ›
          </Button>
        </div>

        {error ? <Alert kind="error">{error}</Alert> : null}
        {loading && !data ? <SkeletonList count={4} /> : null}

        {data ? (
          <>
            <Card>
              <div className="stat-grid">
                <div className="stat">
                  <div className="stat__k">Proventos</div>
                  <div className="stat__v">{money(data.resumo.proventos)}</div>
                </div>
                <div className="stat">
                  <div className="stat__k">Descontos</div>
                  <div className="stat__v stat__v--neg">{money(data.resumo.descontos)}</div>
                </div>
              </div>
              <div className="row row--between" style={{ marginTop: 12 }}>
                <span className="section-title">Líquido</span>
                <strong style={{ fontSize: '1.3rem', letterSpacing: '-0.02em' }}>{money(data.resumo.liquido)}</strong>
              </div>
              {data.resumo.fgts ? (
                <p className="card__meta" style={{ marginTop: 6 }}>
                  FGTS: {money(data.resumo.fgts)}
                </p>
              ) : null}
            </Card>

            <Card>
              <p className="section-title">Itens</p>
              {data.resumo.itens.length ? (
                data.resumo.itens.map((item, i) => (
                  <div className="kv" key={`${item.descricao}-${i}`}>
                    <span className="kv__k" style={{ color: 'var(--text)' }}>
                      {item.descricao ?? 'Item'}
                      {item.natureza ? <span className="punch__sub">{item.natureza}</span> : null}
                    </span>
                    <span className={`kv__v${item.natureza === 'desconto' ? ' stat__v--neg' : ''}`}>{money(item.valor)}</span>
                  </div>
                ))
              ) : (
                <p className="card__meta" style={{ marginTop: 6 }}>
                  Nenhum item lançado neste mês.
                </p>
              )}
            </Card>

            {data.arquivo ? (
              <div className="stack">
                <Button block loading={baixando} onClick={() => void abrirPdf('ver')}>
                  Abrir PDF do holerite
                </Button>
                <Button variant="ghost" block loading={baixando} onClick={() => void abrirPdf('baixar')}>
                  Salvar no aparelho
                </Button>
              </div>
            ) : (
              <Alert kind="info">
                O PDF do holerite deste mês ainda não foi liberado pelo RH. Os valores acima já refletem o cálculo.
              </Alert>
            )}
          </>
        ) : null}
      </div>
    </div>
  );
}
