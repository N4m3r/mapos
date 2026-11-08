/** Início do colaborador: batidas de hoje, totais do mês e atalhos. */

import * as col from '../../lib/api-colaborador';
import { useCarga, useFotoPerfil, useRelogio } from '../../lib/hooks';
import { useApp } from '../../lib/store';
import { navigate } from '../../lib/router';
import { competenciaLabel, minutesToHm } from '../../lib/format';
import { dataPorExtenso, horaAgora, saudacao } from '../../lib/geo';
import { Alert, Button, Card, SkeletonList, Topbar } from '../../components/ui';
import { PONTO_LABELS, type ColaboradorDashboard } from '../../lib/types-tecnico';
import { AreaSwitcher } from '../../components/AreaSwitcher';

function Atalho({ titulo, sub, onClick, d }: { titulo: string; sub: string; onClick: () => void; d: string }) {
  return (
    <button type="button" className="quick__item" onClick={onClick}>
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
        <path d={d} />
      </svg>
      {titulo}
      <span className="quick__meta">{sub}</span>
    </button>
  );
}

export function ColaboradorHomeScreen() {
  const { brand, session, signOut } = useApp();
  const agora = useRelogio();
  const { data, loading, error, recarregar } = useCarga<ColaboradorDashboard>((baseUrl, token) =>
    col.dashboard(baseUrl, token),
  );
  const foto = useFotoPerfil(data?.colaborador.fotoUrl);
  const mes = data?.totaisMes;
  const semana = data?.totaisSemana;
  const extras = (Number(mes?.minutos_extras_50) || 0) + (Number(mes?.minutos_extras_100) || 0);
  const saldo = Number(mes?.saldo_banco_min) || 0;

  return (
    <div className="screen">
      <Topbar
        logo={brand?.emitente?.url_logo}
        title={saudacao(agora)}
        subtitle={
          data?.colaborador.primeiroNome
            ? `${data.colaborador.primeiroNome} · ${dataPorExtenso(agora)}`
            : dataPorExtenso(agora)
        }
        right={
          <button type="button" className="icon-btn" onClick={() => navigate('colaborador/dados')} aria-label="Meus dados">
            {foto ? (
              <img src={foto} alt="" style={{ width: 32, height: 32, borderRadius: '50%', objectFit: 'cover' }} />
            ) : (
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round">
                <circle cx="12" cy="8" r="3.6" />
                <path d="M4.5 20c.8-3.6 3.9-5.6 7.5-5.6s6.7 2 7.5 5.6" />
              </svg>
            )}
          </button>
        }
      />

      <div className="content">
        <AreaSwitcher />
        {error ? (
          <div className="stack">
            <Alert kind="error">{error}</Alert>
            <button type="button" className="reset-button" onClick={recarregar}>
              Tentar novamente
            </button>
          </div>
        ) : null}

        {loading && !data ? <SkeletonList count={4} /> : null}

        {data ? (
          <>
            <Card>
              <div className="row row--between">
                <div>
                  <p className="section-title">Agora</p>
                  <div className="clock">{horaAgora(agora)}</div>
                </div>
                <div style={{ textAlign: 'right' }}>
                  <p className="card__meta">{data.colaborador.cargo || 'Colaborador'}</p>
                  <p className="card__meta">{data.colaborador.nome}</p>
                </div>
              </div>

              {data.pontoInicio ? null : (
                <p className="card__meta" style={{ marginTop: 8 }}>
                  O controle de faltas ainda não foi iniciado pelo RH: dias sem batida não geram dívida.
                </p>
              )}

              {data.podeBaterPonto ? (
                <Button block style={{ marginTop: 12 }} onClick={() => navigate('colaborador/ponto')}>
                  Bater ponto · {PONTO_LABELS[data.proximoTipo] ?? 'Entrada'}
                </Button>
              ) : (
                <p className="card__meta" style={{ marginTop: 10 }}>
                  Você não tem permissão para registrar ponto.
                </p>
              )}
            </Card>

            <div className="stat-grid">
              <div className="stat">
                <div className="stat__k">Semana</div>
                <div className="stat__v">{minutesToHm(semana?.minutos_trabalhados ?? 0)}</div>
                <div className="stat__sub">
                  Falta {minutesToHm(semana?.minutos_faltas ?? 0)}
                </div>
              </div>
              <div className="stat">
                <div className="stat__k">Banco de horas</div>
                <div className={`stat__v${saldo < 0 ? ' stat__v--neg' : ''}`}>{minutesToHm(saldo)}</div>
                <div className="stat__sub">Faltas no mês: {minutesToHm(mes?.minutos_faltas ?? 0)}</div>
              </div>
              <div className="stat">
                <div className="stat__k">Extras no mês</div>
                <div className="stat__v">{minutesToHm(extras)}</div>
                <div className="stat__sub">
                  50% {minutesToHm(mes?.minutos_extras_50 ?? 0)} · 100% {minutesToHm(mes?.minutos_extras_100 ?? 0)}
                </div>
              </div>
              <div className="stat">
                <div className="stat__k">Horas no mês</div>
                <div className="stat__v">{minutesToHm(mes?.minutos_trabalhados ?? 0)}</div>
                <div className="stat__sub">
                  {data.pendentes > 0 ? `${data.pendentes} solicitação(ões) pendente(s)` : 'Sem pendências'}
                </div>
              </div>
            </div>

            <Card>
              <p className="section-title">Batidas de hoje</p>
              {data.batidasHoje.length ? (
                <div style={{ marginTop: 4 }}>
                  {data.batidasHoje.map((b) => (
                    <div className={`punch${b.dentro_geofence === '0' || b.dentro_geofence === 0 ? ' punch--fora' : ''}`} key={b.id}>
                      <span className="punch__dot" />
                      <span className="punch__tipo">
                        {PONTO_LABELS[b.tipo] ?? b.label}
                        {b.os_id ? <span className="punch__sub">OS {String(b.os_id).padStart(4, '0')}</span> : null}
                        {b.dentro_geofence === '0' || b.dentro_geofence === 0 ? (
                          <span className="punch__sub">fora da área{b.distancia_metros ? ` (${b.distancia_metros} m)` : ''}</span>
                        ) : null}
                      </span>
                      <span className="punch__hora">{b.hora}</span>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="card__meta" style={{ marginTop: 6 }}>
                  Nenhuma batida hoje.
                </p>
              )}
            </Card>

            <p className="section-title">Atalhos</p>
            <div className="quick">
              <Atalho
                titulo="Espelho de ponto"
                sub={competenciaLabel(data.hoje.slice(0, 7))}
                onClick={() => navigate('colaborador/espelho')}
                d="M3.5 5h17v16h-17zM8 3v4M16 3v4M3.5 10h17M8 14h3M8 17.5h6"
              />
              <Atalho
                titulo="Holerite"
                sub="Ver e baixar o PDF"
                onClick={() => navigate('colaborador/holerite')}
                d="M6 3h12v18H6zM9 8h6M9 12h6M9 16h4"
              />
              <Atalho
                titulo="Justificar / corrigir"
                sub="Ponto e falta"
                onClick={() => navigate('colaborador/solicitacoes')}
                d="M12 9v4m0 4h.01M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"
              />
              <Atalho
                titulo="Folga / férias"
                sub="Pedir ausência"
                onClick={() => navigate('colaborador/ausencias')}
                d="M12 3v3M12 18v3M4.2 12H3M21 12h-1.2M5.6 5.6 4.8 4.8M19.2 19.2l-.8-.8M18.4 5.6l.8-.8M4.8 19.2l.8-.8M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z"
              />
            </div>

            <p className="card__meta" style={{ textAlign: 'center' }}>
              {session?.user.email ?? ''}
            </p>

            <button
              type="button"
              className="reset-button"
              style={{ color: 'var(--danger)' }}
              onClick={signOut}
            >
              Sair da conta
            </button>
          </>
        ) : null}
      </div>
    </div>
  );
}
