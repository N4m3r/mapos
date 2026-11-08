/** Projetos (obras) do técnico: progresso dos pontos por projeto. */

import * as tec from '../../lib/api-tecnico';
import { useCarga } from '../../lib/hooks';
import { navigate } from '../../lib/router';
import { dateBr } from '../../lib/format';
import { Alert, Card, Empty, Progress, SkeletonList, Topbar } from '../../components/ui';
import type { ProjetoResumo } from '../../lib/types-tecnico';

const STATUS_PROJETO: Record<string, string> = {
  planejamento: 'Planejamento',
  em_execucao: 'Em execução',
  paralisada: 'Paralisada',
  concluida: 'Concluída',
  cancelada: 'Cancelada',
};

function progresso(p: ProjetoResumo): number {
  const valor = Number(p.percentual_concluido);
  return Number.isFinite(valor) ? valor : 0;
}

export function TecnicoProjetosScreen() {
  const { data, loading, error, recarregar } = useCarga<ProjetoResumo[]>((baseUrl, token) =>
    tec.listProjetos(baseUrl, token),
  );

  return (
    <div className="screen">
      <Topbar
        title="Meus projetos"
        subtitle={data ? `${data.length} projeto(s)` : 'Carregando…'}
      />

      <div className="content">
        <button type="button" className="btn btn--ghost btn--block" onClick={() => navigate('tecnico/relatorio')}>
          Meu relatório
        </button>
        {error ? (
          <div className="stack">
            <Alert kind="error">{error}</Alert>
            <button type="button" className="reset-button" onClick={recarregar}>
              Tentar novamente
            </button>
          </div>
        ) : null}

        {loading && !data ? <SkeletonList count={3} /> : null}

        {data && data.length === 0 ? (
          <Empty
            title="Nenhum projeto"
            message="Quando você for alocado a uma obra, ela aparece aqui com os pontos a executar."
          />
        ) : null}

        {data && data.length > 0 ? (
          <div className="stack">
            {data.map((projeto) => (
              <Card key={projeto.idObra} onClick={() => navigate(`tecnico/projeto/${projeto.idObra}`)}>
                <div className="card__head">
                  <div style={{ minWidth: 0 }}>
                    <p className="card__title">{projeto.nome}</p>
                    <p className="card__meta">
                      {projeto.nomeCliente ? `${projeto.nomeCliente} · ` : ''}
                      {STATUS_PROJETO[String(projeto.status)] ?? projeto.status ?? 'Projeto'}
                      {projeto.minha ? ' · você é o responsável' : ''}
                    </p>
                  </div>
                  <span className="pill pill--info">{progresso(projeto)}%</span>
                </div>

                <div style={{ marginTop: 10 }}>
                  <Progress value={progresso(projeto)} />
                </div>

                <div className="row row--between" style={{ marginTop: 8 }}>
                  <span className="card__meta">{projeto.qtdPontos ?? 0} ponto(s)</span>
                  {projeto.data_previsao ? (
                    <span className="card__meta">Previsão {dateBr(projeto.data_previsao)}</span>
                  ) : null}
                </div>

                {projeto.endereco ? (
                  <p className="card__meta" style={{ marginTop: 4 }}>
                    {projeto.endereco}
                    {projeto.cidade ? ` · ${projeto.cidade}${projeto.uf ? `/${projeto.uf}` : ''}` : ''}
                  </p>
                ) : null}
              </Card>
            ))}
          </div>
        ) : null}
      </div>
    </div>
  );
}
