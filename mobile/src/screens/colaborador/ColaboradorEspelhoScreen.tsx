/** Espelho de ponto do mês: dias, batidas, saldo e totais. */

import { useEffect, useState } from 'react';
import * as col from '../../lib/api-colaborador';
import { useApi } from '../../lib/store';
import { navigate } from '../../lib/router';
import { competenciaAtual, competenciaLabel, dateBr, minutesToHm, shiftCompetencia, DIAS_SEMANA } from '../../lib/format';
import { Alert, Button, Card, SkeletonList, Topbar } from '../../components/ui';
import { PONTO_LABELS, type EspelhoResult } from '../../lib/types-tecnico';

export function ColaboradorEspelhoScreen() {
  const { baseUrl, token, handle } = useApi();
  const [competencia, setCompetencia] = useState<string>(() => competenciaAtual());
  const [data, setData] = useState<EspelhoResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelado = false;
    setLoading(true);
    setError(null);
    handle(() => col.espelho(baseUrl, token, competencia))
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

  const totais = data?.totais;
  const atual = competenciaAtual();

  return (
    <div className="screen">
      <Topbar
        title="Espelho de ponto"
        subtitle={competenciaLabel(competencia)}
        onBack={() => navigate('colaborador')}
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
        {loading && !data ? <SkeletonList count={5} /> : null}

        {data ? (
          <>
            <Card>
              <div className="stat-grid">
                <div className="stat">
                  <div className="stat__k">Trabalhado</div>
                  <div className="stat__v">{minutesToHm(totais?.minutos_trabalhados ?? 0)}</div>
                </div>
                <div className="stat">
                  <div className="stat__k">Previsto</div>
                  <div className="stat__v">{minutesToHm(totais?.minutos_previstos ?? 0)}</div>
                </div>
                <div className="stat">
                  <div className="stat__k">Faltas</div>
                  <div className="stat__v">{minutesToHm(totais?.minutos_faltas ?? 0)}</div>
                </div>
                <div className="stat">
                  <div className="stat__k">Saldo</div>
                  <div className={`stat__v${Number(totais?.saldo_banco_min ?? 0) < 0 ? ' stat__v--neg' : ''}`}>
                    {minutesToHm(totais?.saldo_banco_min ?? 0)}
                  </div>
                </div>
              </div>
              <p className="card__meta" style={{ marginTop: 10 }}>
                Extras 50% {minutesToHm(totais?.minutos_extras_50 ?? 0)} · 100% {minutesToHm(totais?.minutos_extras_100 ?? 0)}
              </p>
            </Card>

            <Card style={{ padding: '4px 14px' }}>
              {data.linhas.map((linha) => {
                const saldo = Number(linha.calc.saldo) || 0;
                const semBatida = !linha.batidas.length;
                return (
                  <div
                    className={`day${semBatida && linha.eh_util ? ' day--util-sem-batida' : ''}`}
                    key={linha.data}
                  >
                    <span className="day__data">
                      {String(linha.data).slice(8, 10)}/{String(linha.data).slice(5, 7)}
                      <small>{DIAS_SEMANA[linha.dia_semana] ?? ''}</small>
                    </span>
                    <span className="day__batidas">
                      {linha.batidas.length ? (
                        linha.batidas.map((b) => (
                          <span
                            className={`day__batida${b.dentro_geofence === '0' || b.dentro_geofence === 0 ? ' day__batida--fora' : ''}`}
                            key={b.id}
                            title={PONTO_LABELS[b.tipo] ?? b.tipo}
                          >
                            {b.hora}
                          </span>
                        ))
                      ) : linha.abonado ? (
                        <span className="day__batida">abonado</span>
                      ) : (
                        <span className="card__meta">—</span>
                      )}
                    </span>
                    <span className={`day__saldo${saldo < 0 ? ' day__saldo--neg' : ''}`}>
                      {saldo === 0 ? '—' : minutesToHm(saldo)}
                    </span>
                  </div>
                );
              })}
            </Card>

            <p className="card__meta">
              {data.pontoInicio
                ? `Controle de faltas iniciado em ${dateBr(data.pontoInicio)}.`
                : 'O controle de faltas ainda não foi iniciado pelo RH.'}
            </p>
            <p className="card__meta">
              Alguma batida faltando? Abra uma correção em Justificar / corrigir.
            </p>
            <Button variant="ghost" block onClick={() => navigate('colaborador/solicitacoes')}>
              Correção de ponto
            </Button>
          </>
        ) : null}
      </div>
    </div>
  );
}
