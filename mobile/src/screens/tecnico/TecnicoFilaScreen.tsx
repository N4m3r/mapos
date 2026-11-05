/** Fila do técnico: pontos repassados para mim e pontos livres da equipe. */

import { useState } from 'react';
import * as tec from '../../lib/api-tecnico';
import { useCarga } from '../../lib/hooks';
import { useApi } from '../../lib/store';
import { navigate } from '../../lib/router';
import { Alert, Badge, Button, Card, Empty, Sheet, SkeletonList, Topbar } from '../../components/ui';
import type { FilaResult, PontoProjeto } from '../../lib/types-tecnico';

export function TecnicoFilaScreen() {
  const { baseUrl, token, handle } = useApi();
  const { data, loading, error, recarregar } = useCarga<FilaResult>((url, tk) => tec.fila(url, tk));
  const [alvo, setAlvo] = useState<PontoProjeto | null>(null);
  const [busy, setBusy] = useState(false);
  const [erro, setErro] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);

  const pontos = data?.pontos ?? [];
  const lista = data?.statusLista ?? {};
  const minhas = pontos.filter((p) => p.minha);
  const equipe = pontos.filter((p) => !p.minha);

  const assumir = async () => {
    if (!alvo?.obra_id) return;
    setBusy(true);
    setErro(null);
    try {
      await handle(() => tec.assumirPonto(baseUrl, token, alvo.idPonto, alvo.obra_id as number | string));
      setOk(`${alvo.identificacao} agora está com você.`);
      setAlvo(null);
      recarregar();
    } catch (err) {
      setErro((err as Error).message);
    } finally {
      setBusy(false);
    }
  };

  const cartao = (ponto: PontoProjeto, minha: boolean) => (
    <Card key={ponto.idPonto} onClick={() => (minha && ponto.obra_id ? navigate(`tecnico/ponto/${ponto.obra_id}/${ponto.idPonto}`) : setAlvo(ponto))}>
      <div className="card__head">
        <div style={{ minWidth: 0 }}>
          <p className="card__title">{ponto.identificacao}</p>
          <p className="card__meta">
            {ponto.obra_nome ?? 'Projeto'}
            {ponto.cliente ? ` · ${ponto.cliente}` : ''}
          </p>
        </div>
        <Badge color={undefined}>{lista[ponto.status]?.rotulo ?? ponto.status_rotulo ?? ponto.status}</Badge>
      </div>
      {ponto.local ? (
        <p className="card__meta" style={{ marginTop: 4 }}>
          {ponto.local}
        </p>
      ) : null}
      {ponto.instrucao ? (
        <p className="card__meta" style={{ marginTop: 4 }}>
          Instrução: {ponto.instrucao}
        </p>
      ) : null}
      <div className="row row--between" style={{ marginTop: 10 }}>
        <span className="card__meta">
          {minha ? (ponto.responsavel ? `Repassado · ${ponto.responsavel}` : 'Com você') : 'Da equipe'}
        </span>
        <span className="reset-button">{minha ? 'Abrir ponto' : 'Assumir'}</span>
      </div>
    </Card>
  );

  return (
    <div className="screen">
      <Topbar
        title="Minha fila"
        subtitle={data ? `${minhas.length} com você · ${equipe.length} na equipe` : 'Carregando…'}
      />

      <div className="content">
        {error ? <Alert kind="error">{error}</Alert> : null}
        {erro ? <Alert kind="error">{erro}</Alert> : null}
        {ok ? <Alert kind="ok">{ok}</Alert> : null}

        {loading ? (
          <SkeletonList count={3} />
        ) : !pontos.length ? (
          <Empty
            title="Fila vazia"
            message="Nenhum ponto repassado para você e nada livre na equipe agora."
          />
        ) : (
          <>
            {minhas.length ? (
              <>
                <p className="section-title">Com você ({minhas.length})</p>
                <div className="stack">{minhas.map((p) => cartao(p, true))}</div>
              </>
            ) : null}
            {equipe.length ? (
              <>
                <p className="section-title">Livres na equipe ({equipe.length})</p>
                <div className="stack">{equipe.map((p) => cartao(p, false))}</div>
              </>
            ) : null}
          </>
        )}
      </div>

      <Sheet
        open={Boolean(alvo)}
        title={alvo ? `Assumir ${alvo.identificacao}` : 'Assumir ponto'}
        onClose={() => setAlvo(null)}
        footer={
          <>
            <Button variant="ghost" block onClick={() => setAlvo(null)}>
              Cancelar
            </Button>
            <Button block loading={busy} onClick={() => void assumir()}>
              Assumir
            </Button>
          </>
        }
      >
        {alvo ? (
          <>
            <p className="card__meta">
              {alvo.obra_nome ?? 'Projeto'}
              {alvo.local ? ` · ${alvo.local}` : ''}
              {alvo.responsavel ? ` · hoje com ${alvo.responsavel}` : ''}
            </p>
            <p className="card__meta">
              Ao assumir, o ponto passa a aparecer na sua fila e você poderá avançar o status com foto.
            </p>
          </>
        ) : null}
      </Sheet>
    </div>
  );
}
