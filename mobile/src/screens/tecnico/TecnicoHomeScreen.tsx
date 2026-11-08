/** Início da Área do Técnico: próxima visita, atenção e visitas do dia. */

import type { ReactNode } from 'react';
import * as tec from '../../lib/api-tecnico';
import { useCarga, useFotoPerfil, useRelogio } from '../../lib/hooks';
import { useApp } from '../../lib/store';
import { navigate } from '../../lib/router';
import { quandoVisita, telHref, whatsHref } from '../../lib/format';
import { dataPorExtenso, saudacao } from '../../lib/geo';
import { Alert, Card, Empty, SkeletonList, StatusPill, Topbar } from '../../components/ui';
import type { OsResumo, TecnicoDashboard } from '../../lib/types-tecnico';
import { AreaSwitcher } from '../../components/AreaSwitcher';

function Icone({ path }: { path: string }) {
  return (
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
      <path d={path} />
    </svg>
  );
}

function ProximaVisita({ os, continuar }: { os: OsResumo; continuar?: boolean }) {
  const celular = os.celular ?? os.telefone ?? undefined;
  const tel = telHref(celular);
  const zap = whatsHref(celular);
  const quando = quandoVisita(os.dataEfetiva ?? os.data_agendamento ?? os.dataInicial);

  return (
    <section className={`hero${continuar ? ' hero--andamento' : ''}`} aria-label="Próxima visita">
      <span className="hero__kicker">
        <Icone path={continuar ? 'M8 5v14l11-7z' : 'm3 11 19-9-9 19-2-8-8-2z'} />
        {continuar ? 'Atendimento em andamento' : 'Próxima visita'}
      </span>
      <h2 className="hero__title">{os.nomeCliente || 'Cliente'}</h2>

      <div className="hero__line">
        <Icone path="M12 7v5l4 2" />
        <span>
          {quando} · OS {String(os.idOs).padStart(4, '0')}
          {os.hora ? ` · ${os.hora}` : ''}
        </span>
      </div>

      {os.endereco ? (
        <div className="hero__line">
          <Icone path="M12 21s-7-6.1-7-11a7 7 0 1 1 14 0c0 4.9-7 11-7 11z" />
          <span>{os.endereco}</span>
        </div>
      ) : null}

      {os.descricaoProduto ? (
        <div className="hero__line">
          <Icone path="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.4 2.4-2-2 2.4-2.4z" />
          <span>{os.descricaoProduto}</span>
        </div>
      ) : null}

      <div className="actionbar" style={{ marginTop: 6 }}>
        <button
          type="button"
          className="btn"
          onClick={() => navigate(`tecnico/atendimento/${os.idOs}`)}
        >
          {continuar ? 'Continuar' : 'Abrir atendimento'}
        </button>
        {os.urlRota ? (
          <a className="btn btn--ghost" href={os.urlRota} target="_blank" rel="noopener noreferrer">
            Ir até lá
          </a>
        ) : tel ? (
          <a className="btn btn--ghost" href={tel}>
            Ligar
          </a>
        ) : zap ? (
          <a className="btn btn--ghost" href={zap} target="_blank" rel="noopener noreferrer">
            WhatsApp
          </a>
        ) : null}
      </div>
    </section>
  );
}

function Atencao({
  tone,
  count,
  texto,
  onClick,
  icon,
}: {
  tone?: 'bad';
  count: number;
  texto: string;
  onClick: () => void;
  icon: ReactNode;
}) {
  return (
    <button type="button" className={`attention${tone === 'bad' ? ' attention--bad' : ''}`} onClick={onClick}>
      {icon}
      <span>{texto}</span>
      <span className="attention__count">{count}</span>
    </button>
  );
}

export function TecnicoHomeScreen() {
  const { brand, session, signOut } = useApp();
  const agora = useRelogio();
  const { data, loading, error, recarregar } = useCarga<TecnicoDashboard>((baseUrl, token) =>
    tec.dashboard(baseUrl, token),
  );
  const perfil = data?.perfil ?? null;
  const foto = useFotoPerfil(perfil?.fotoUrl);

  const primeiro = perfil?.primeiroNome || session?.user.nome?.split(' ')[0] || '';
  const grupos = data?.grupos;
  const doDia = data?.doDia ?? [];

  return (
    <div className="screen">
      <Topbar
        logo={brand?.emitente?.url_logo}
        title={saudacao(agora)}
        subtitle={primeiro ? `${primeiro} · ${dataPorExtenso(agora)}` : dataPorExtenso(agora)}
        right={
          <button type="button" className="icon-btn" onClick={() => navigate('tecnico/perfil')} aria-label="Meu perfil">
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
            {data.proxima ? (
              <ProximaVisita os={data.proxima} continuar={Boolean(data.proxima.continuar)} />
            ) : (
              <Card>
                <div className="empty" style={{ padding: '18px 8px' }}>
                  <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round">
                    <path d="M20 6 9 17l-5-5" />
                  </svg>
                  <p className="empty__title">Tudo em ordem</p>
                  <p className="card__meta" style={{ margin: 0 }}>
                    Nenhuma visita marcada para você agora.
                  </p>
                  <button type="button" className="reset-button" onClick={() => navigate('tecnico/os/proximos')}>
                    Ver próximos dias
                  </button>
                </div>
              </Card>
            )}

            {grupos && (grupos.atrasadas > 0 || data.qtdNaoRealizadas > 0 || data.filaQtd > 0) ? (
              <>
                <p className="section-title">Precisa da sua atenção</p>
                {grupos.atrasadas > 0 ? (
                  <Atencao
                    tone="bad"
                    count={grupos.atrasadas}
                    texto="Visitas atrasadas"
                    onClick={() => navigate('tecnico/os/atrasadas')}
                    icon={<Icone path="M12 9v4m0 4h.01M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" />}
                  />
                ) : null}
                {data.qtdNaoRealizadas > 0 ? (
                  <Atencao
                    tone="bad"
                    count={data.qtdNaoRealizadas}
                    texto="Não deu para fazer: reagendar"
                    onClick={() => navigate('tecnico/nao-realizadas')}
                    icon={<Icone path="M8 3v4M16 3v4M3.5 10h17M5 5h14v16H5zM9 15l6-3" />}
                  />
                ) : null}
                {data.filaQtd > 0 ? (
                  <Atencao
                    count={data.filaQtd}
                    texto="Pontos de projeto esperando você"
                    onClick={() => navigate('tecnico/fila')}
                    icon={<Icone path="M4 7h10l-3-3M20 17H10l3 3" />}
                  />
                ) : null}
              </>
            ) : null}

            <div className="section-head">
              <p className="section-title">Hoje</p>
              <button type="button" className="reset-button" onClick={() => navigate('tecnico/os/hoje')}>
                Ver todas
              </button>
            </div>

            {doDia.length ? (
              <Card>
                <div className="timeline">
                  {doDia.map((os) => (
                    <button
                      key={os.idOs}
                      type="button"
                      className="timeline__row"
                      onClick={() => navigate(`tecnico/atendimento/${os.idOs}`)}
                    >
                      <span className="timeline__hora">{os.hora || '--:--'}</span>
                      <span className="timeline__cli">{os.nomeCliente || 'Cliente'}</span>
                      <StatusPill text={os.rotuloStatus ?? os.status} tone={os.statusClasse} />
                    </button>
                  ))}
                </div>
              </Card>
            ) : (
              <Empty title="Nenhuma visita hoje" message="Aproveite para revisar os próximos dias." />
            )}

            <div className="números" aria-label="Números do dia">
              <span>
                <b>{data.doDia.length}</b> visitas hoje
              </span>
              <span>
                <b>{grupos?.concluidas ?? 0}</b> concluídas
              </span>
            </div>

            {data.podeCriarAtividade ? (
              <button type="button" className="btn btn--ghost btn--block" onClick={() => navigate('tecnico/atividade')}>
                + Nova atividade não programada
              </button>
            ) : null}
            <button type="button" className="btn btn--ghost btn--block" onClick={() => navigate('tecnico/os')}>
              Ver todas as minhas OS
            </button>

            {brand?.emitente?.nome ? (
              <p className="card__meta" style={{ textAlign: 'center' }}>
                {brand.emitente.nome}
              </p>
            ) : null}

            <button
              type="button"
              className="reset-button"
              style={{ color: 'var(--danger)', marginTop: 4 }}
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
