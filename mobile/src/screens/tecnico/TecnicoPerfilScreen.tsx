/** Perfil do técnico: dados, contato editável, equipes e números do mês. */

import { useState } from 'react';
import * as tec from '../../lib/api-tecnico';
import { useCarga, useFotoPerfil } from '../../lib/hooks';
import { useApi, useApp } from '../../lib/store';
import { navigate } from '../../lib/router';
import { telHref, whatsHref } from '../../lib/format';
import { Alert, Button, Card, Field, Input, Kv, SkeletonList, Topbar } from '../../components/ui';
import type { TecnicoPerfil } from '../../lib/types-tecnico';

export function TecnicoPerfilScreen() {
  const { baseUrl, token, handle } = useApi();
  const { brand, signOut, disconnect } = useApp();
  const { data, loading, error, setData, recarregar } = useCarga<TecnicoPerfil>((url, tk) => tec.perfil(url, tk));
  const foto = useFotoPerfil(data?.fotoUrl);

  const [editando, setEditando] = useState(false);
  const [telefone, setTelefone] = useState('');
  const [celular, setCelular] = useState('');
  const [busy, setBusy] = useState(false);
  const [erro, setErro] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [confirmar, setConfirmar] = useState(false);

  const abrirEdicao = () => {
    setTelefone(data?.telefone ?? '');
    setCelular(data?.celular ?? '');
    setEditando(true);
    setErro(null);
    setOk(null);
  };

  const salvar = async () => {
    setBusy(true);
    setErro(null);
    try {
      const atualizado = await handle(() =>
        tec.atualizarPerfil(baseUrl, token, { telefone, celular }),
      );
      setData({ ...data!, ...atualizado });
      setOk('Contato atualizado.');
      setEditando(false);
      recarregar();
    } catch (err) {
      setErro((err as Error).message);
    } finally {
      setBusy(false);
    }
  };

  const tel = telHref(data?.celular ?? data?.telefone ?? undefined);
  const zap = whatsHref(data?.celular ?? data?.telefone ?? undefined, 'Olá!');

  return (
    <div className="screen">
      <Topbar title="Meu perfil" onBack={() => navigate('tecnico')} />

      <div className="content">
        {loading && !data ? <SkeletonList count={4} /> : null}
        {error ? <Alert kind="error">{error}</Alert> : null}
        {erro ? <Alert kind="error">{erro}</Alert> : null}
        {ok ? <Alert kind="ok">{ok}</Alert> : null}

        {data ? (
          <>
            <Card>
              <div className="row">
                <span className="foto-perfil" style={{ width: 60, height: 60 }}>
                  {foto ? <img src={foto} alt="" /> : data.iniciais || data.nome.slice(0, 2).toUpperCase()}
                </span>
                <div style={{ minWidth: 0 }}>
                  <p className="card__title">{data.nome}</p>
                  <p className="card__meta">
                    {data.cargo ? `${data.cargo} · ` : ''}
                    {data.email ?? ''}
                  </p>
                  {data.colaboradorVinculado ? <span className="pill pill--info">Colaborador vinculado</span> : null}
                </div>
              </div>

              {tel || zap ? (
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
              ) : null}
            </Card>

            <Card>
              <div className="section-head">
                <p className="section-title">Contato</p>
                <button type="button" className="reset-button" onClick={abrirEdicao}>
                  Editar
                </button>
              </div>
              <Kv k="Telefone" v={data.telefone || '—'} />
              <Kv k="Celular" v={data.celular || '—'} />
              <Kv k="E-mail" v={data.email || '—'} />
            </Card>

            {editando ? (
              <Card>
                <div className="stack">
                  <Field label="Telefone" hint="DDD + número, ex.: (11) 3333-4444">
                    <Input value={telefone} onChange={(e) => setTelefone(e.target.value)} inputMode="tel" />
                  </Field>
                  <Field label="Celular" hint="DDD + número, ex.: (11) 91234-5678">
                    <Input value={celular} onChange={(e) => setCelular(e.target.value)} inputMode="tel" />
                  </Field>
                  <div className="actionbar">
                    <Button variant="ghost" block onClick={() => setEditando(false)}>
                      Cancelar
                    </Button>
                    <Button block loading={busy} onClick={() => void salvar()}>
                      Salvar
                    </Button>
                  </div>
                </div>
              </Card>
            ) : null}

            {data.numeros ? (
              <div className="stat-grid">
                <div className="stat">
                  <div className="stat__k">OS atendidas no mês</div>
                  <div className="stat__v">{data.numeros.osAtendidas}</div>
                  <div className="stat__sub">{data.numeros.mes}</div>
                </div>
                <div className="stat">
                  <div className="stat__k">Pontos avançados</div>
                  <div className="stat__v">{data.numeros.pontosAvancados}</div>
                  <div className="stat__sub">execução de projetos</div>
                </div>
              </div>
            ) : null}

            {data.equipes?.length ? (
              <Card>
                <p className="section-title">Equipes</p>
                {data.equipes.map((e) => (
                  <div className="kv" key={e.id}>
                    <span className="kv__k" style={{ color: 'var(--text)' }}>
                      {e.nome ?? `Equipe ${e.id}`}
                    </span>
                    <span className="kv__v">{e.encarregado ? 'Encarregado' : 'Integrante'}</span>
                  </div>
                ))}
              </Card>
            ) : null}

            <Card>
              <p className="section-title">Sistema</p>
              <Kv k="Empresa" v={brand?.emitente?.nome ?? brand?.appName ?? '—'} />
              <Kv k="Versão" v="MAPOS Mobile" />
            </Card>

            <div className="stack">
              <Button variant="ghost" block onClick={signOut}>
                Sair da conta
              </Button>
              {confirmar ? (
                <div className="stack">
                  <Alert kind="error">
                    Isso remove a URL e a sessão salvas neste aparelho. A instalação MAPOS não é afetada.
                  </Alert>
                  <div className="actionbar">
                    <Button variant="ghost" block small onClick={() => setConfirmar(false)}>
                      Cancelar
                    </Button>
                    <Button variant="danger" block small onClick={disconnect}>
                      Desconectar
                    </Button>
                  </div>
                </div>
              ) : (
                <Button variant="danger" block onClick={() => setConfirmar(true)}>
                  Desconectar deste servidor
                </Button>
              )}
            </div>
          </>
        ) : null}
      </div>
    </div>
  );
}
