/**
 * Configuração de câmeras e NVR Hikvision no projeto.
 *
 * O cadastro prévio do projeto é a base: as câmeras e o NVR previstos vêm de
 * `obra_pontos`. Na rede do cliente o app descobre os equipamentos (SADP) e lê
 * os canais do NVR (ISAPI); cada câmera é casada com o ponto previsto por MAC,
 * série ou IP, e o técnico escolhe o canal livre para cadastrar.
 *
 * A senha do NVR e da câmera fica só no aparelho, durante a chamada nativa.
 */

import { useCallback, useEffect, useMemo, useState } from 'react';
import * as cam from '../../lib/api-camera';
import { useCarga } from '../../lib/hooks';
import { useApi } from '../../lib/store';
import { navigate } from '../../lib/router';
import { Alert, Button, Card, Empty, Field, Input, Progress, SkeletonList, Topbar } from '../../components/ui';
import { Hikvision, aparelhoPronto, mesmoMac, redeEmTexto, type RedeAtual } from '../../lib/hikvision';
import { CameraDescobertaSheet } from '../../components/tecnico/CameraDescobertaSheet';
import { CameraPontoSheet } from '../../components/tecnico/CameraPontoSheet';
import { prefixoDeRede } from '../../lib/rede';
import type { CameraPonto, CameraPlano, CanalNvr, DispositivoDescoberto, EnvioCamera, NvrResumo } from '../../lib/types-camera';

interface Credenciais {
  usuario: string;
  senha: string;
}

interface NvrLido extends NvrResumo {
  canais: CanalNvr[];
}

function numeroCanal(valor: string | null | undefined): number {
  const n = Number(String(valor ?? '').replace(/\D/g, ''));
  return Number.isFinite(n) ? n : 0;
}

/** Ponto previsto que corresponde ao dispositivo descoberto (MAC primeiro, depois IP). */
function acharPrevisto(
  cameras: CameraPonto[],
  dispositivo: DispositivoDescoberto,
): CameraPonto | null {
  const porMac = cameras.find((c) => c.mac && mesmoMac(c.mac, dispositivo.mac));
  if (porMac) return porMac;
  return cameras.find((c) => c.ip && c.ip === dispositivo.ip) ?? null;
}

export function TecnicoCameraConfigScreen({ id }: { id: string }) {
  const { baseUrl, token, handle } = useApi();
  const { data, loading, error, recarregar } = useCarga<CameraPlano>(
    (url, tk) => cam.plano(url, tk, id),
    [id],
  );

  const [pronto, setPronto] = useState<{ disponivel: boolean; motivo?: string } | null>(null);
  const [rede, setRede] = useState<RedeAtual | null>(null);
  const [descobertos, setDescobertos] = useState<DispositivoDescoberto[]>([]);
  const [descobertoAberto, setDescobertoAberto] = useState<DispositivoDescoberto | null>(null);
  const [buscando, setBuscando] = useState(false);

  const [nvrAtivo, setNvrAtivo] = useState<CameraPonto | null>(null);
  const [credenciais, setCredenciais] = useState<Credenciais>({ usuario: 'admin', senha: '' });
  const [nvrLido, setNvrLido] = useState<NvrLido | null>(null);
  const [lendoNvr, setLendoNvr] = useState(false);

  const [editando, setEditando] = useState<CameraPonto | null>(null);
  const [form, setForm] = useState<EnvioCamera>({});
  const [senhaCamera, setSenhaCamera] = useState('');
  const [busy, setBusy] = useState(false);
  const [erro, setErro] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);

  const nvrs = data?.nvr ?? [];
  const cameras = data?.cameras ?? [];
  const resumo = data?.resumo ?? { total: 0, configuradas: 0, pendentes: 0 };

  useEffect(() => {
    let cancelado = false;
    void aparelhoPronto().then((r) => {
      if (!cancelado) setPronto(r);
    });
    void Hikvision.redeAtual()
      .then((r) => {
        if (!cancelado) setRede(r);
      })
      .catch(() => {
        if (!cancelado) setRede(null);
      });
    return () => {
      cancelado = true;
    };
  }, []);

  useEffect(() => {
    if (!nvrAtivo && nvrs.length === 1) setNvrAtivo(nvrs[0] ?? null);
  }, [nvrs, nvrAtivo]);

  const canaisLivres = useMemo<CanalNvr[]>(
    () => (nvrLido?.canais ?? []).filter((c) => !c.ocupado),
    [nvrLido],
  );

  const buscarRede = useCallback(async () => {
    setBuscando(true);
    setErro(null);
    setOk(null);
    try {
      const r = await Hikvision.descobrir({ duracaoMs: 12000 });
      setDescobertos(r.dispositivos);
      setOk(
        r.dispositivos.length
          ? `${r.dispositivos.length} equipamento(s) encontrado(s) na rede.`
          : 'Nenhum equipamento Hikvision respondeu. Confira o cabo e se as câmeras estão no mesmo switch.',
      );
    } catch (err) {
      setErro((err as Error).message);
    } finally {
      setBuscando(false);
    }
  }, []);

  const lerNvr = useCallback(async () => {
    if (!nvrAtivo?.ip) {
      setErro('Escolha um NVR cadastrado no projeto (com IP) para conectar.');
      return;
    }
    if (!credenciais.senha) {
      setErro('Informe a senha do NVR.');
      return;
    }
    setLendoNvr(true);
    setErro(null);
    setOk(null);
    try {
      const r = await Hikvision.lerNvr({
        ip: nvrAtivo.ip,
        usuario: credenciais.usuario || 'admin',
        senha: credenciais.senha,
      });
      setNvrLido(r);
      setOk(`${r.nome || nvrAtivo.identificacao}: ${r.canais.length} canal(is) lido(s).`);
      // Guarda o que o NVR informou no ponto previsto (sem senha).
      await handle(() =>
        cam.salvarPonto(baseUrl, token, id, nvrAtivo.idPonto, {
          ip: nvrAtivo.ip ?? undefined,
          modelo: r.modelo ?? undefined,
          serie: r.serie ?? undefined,
          firmware: r.firmware ?? undefined,
          status: 'configurado',
        }),
      );
      recarregar();
    } catch (err) {
      setErro((err as Error).message);
    } finally {
      setLendoNvr(false);
    }
  }, [nvrAtivo, credenciais, handle, baseUrl, token, id, recarregar]);

  const abrir = (camera: CameraPonto, descoberto?: DispositivoDescoberto) => {
    setEditando(camera);
    setErro(null);
    setOk(null);
    setSenhaCamera(credenciais.senha);
    setForm({
      ip: camera.ip ?? descoberto?.ip ?? '',
      canal: camera.canal ?? '',
      modelo: camera.modelo ?? descoberto?.modelo ?? '',
      serie: camera.serie ?? descoberto?.serie ?? '',
      mac: camera.mac ?? descoberto?.mac ?? '',
      firmware: camera.firmware ?? descoberto?.firmware ?? '',
      nvr_id: camera.nvr_id ?? (nvrAtivo ? nvrAtivo.idPonto : null),
    });
  };

  const salvar = async () => {
    if (!editando) return;
    setBusy(true);
    setErro(null);
    setOk(null);
    try {
      const canal = String(form.canal ?? '').trim();
      const nvr = nvrs.find((n) => Number(n.idPonto) === Number(form.nvr_id)) ?? nvrAtivo;

      // Quando o NVR já foi lido e há IP de câmera, grava o canal no equipamento.
      if (nvr?.ip && nvrLido && canal && form.ip && credenciais.senha) {
        try {
          await Hikvision.adicionarCanal({
            nvr: { ip: nvr.ip, usuario: credenciais.usuario || 'admin', senha: credenciais.senha },
            canal: numeroCanal(canal),
            cameraIp: String(form.ip),
            cameraUsuario: credenciais.usuario || 'admin',
            cameraSenha: senhaCamera || credenciais.senha,
          });
        } catch (err) {
          // O cadastro no MAPOS continua: o técnico pode gravar o canal e
          // avisar depois se o NVR recusou a câmera.
          setErro(`O NVR recusou a câmera no canal ${canal}: ${(err as Error).message}. O cadastro no projeto será salvo.`);
        }
      }

      const r = await handle(() =>
        cam.salvarPonto(baseUrl, token, id, editando.idPonto, {
          ...form,
          canal: canal || undefined,
          status: 'configurado',
        }),
      );
      setOk(`${r.ponto.identificacao} configurado.`);
      setEditando(null);
      setNvrLido(null);
      recarregar();
    } catch (err) {
      setErro((err as Error).message);
    } finally {
      setBusy(false);
    }
  };

  const criarDoDescoberto = async (dados: EnvioCamera) => {
    const r = await handle(() => cam.criarPonto(baseUrl, token, id, dados));
    setOk(`${r.ponto.identificacao} criado e configurado.`);
    setDescobertoAberto(null);
    recarregar();
  };

  const semVinculo = descobertos.filter((d) => !acharPrevisto(cameras, d));
  const pct = resumo.total ? Math.round((resumo.configuradas / resumo.total) * 100) : 0;

  return (
    <div className="screen">
      <Topbar
        title="Câmeras e NVR"
        subtitle={data?.obra.nome ?? 'Projeto'}
        onBack={() => navigate(`tecnico/projeto/${id}`)}
        right={
          <button type="button" className="icon-btn" onClick={recarregar} aria-label="Atualizar">
            <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
              <path d="M20 12a8 8 0 1 1-2.3-5.7M20 4v5h-5" />
            </svg>
          </button>
        }
      />

      <div className="content">
        {error ? <Alert kind="error">{error}</Alert> : null}
        {erro ? <Alert kind="error">{erro}</Alert> : null}
        {ok ? <Alert kind="ok">{ok}</Alert> : null}

        {loading && !data ? <SkeletonList count={4} /> : null}

        {pronto && !pronto.disponivel ? (
          <Alert kind="info">{pronto.motivo ?? 'A descoberta na rede só funciona no aplicativo Android (APK).'}</Alert>
        ) : null}

        {data ? (
          <>
            <Card>
              <div className="card__head">
                <div style={{ minWidth: 0 }}>
                  <p className="card__title">Andamento da configuração</p>
                  <p className="card__meta">
                    {resumo.configuradas} de {resumo.total} câmera(s) configurada(s)
                    {resumo.pendentes ? ` · ${resumo.pendentes} pendente(s)` : ''}
                  </p>
                </div>
                <span className="pill pill--info">{pct}%</span>
              </div>
              <div style={{ marginTop: 10 }}>
                <Progress value={pct} />
              </div>
              <p className="card__meta" style={{ marginTop: 8 }}>
                Rede: {redeEmTexto(rede)}
              </p>
              <div className="actionbar" style={{ marginTop: 12 }}>
                <Button small block loading={buscando} disabled={!pronto?.disponivel} onClick={() => void buscarRede()}>
                  Buscar na rede
                </Button>
                <Button small block variant="ghost" onClick={() => navigate(`tecnico/projeto/${id}`)}>
                  Ver projeto
                </Button>
              </div>
            </Card>

            {nvrs.length === 0 ? (
              <Empty
                title="Projeto sem NVR"
                message="Cadastre o NVR (tipo NVR / DVR) nos pontos do projeto para usar a configuração de canais."
              />
            ) : (
              <Card>
                <p className="section-title">NVR do projeto</p>
                <div style={{ marginTop: 8 }}>
                  {nvrs.map((nvr) => (
                    <button
                      key={nvr.idPonto}
                      type="button"
                      className="listrow"
                      onClick={() => {
                        setNvrAtivo(nvr);
                        setNvrLido(null);
                      }}
                    >
                      <span className="listrow__main">
                        <span className="listrow__title">{nvr.identificacao}</span>
                        <span className="listrow__meta">
                          {nvr.ip ? `IP ${nvr.ip}` : 'Sem IP cadastrado'}
                          {nvr.modelo ? ` · ${nvr.modelo}` : ''}
                        </span>
                      </span>
                      <span className={`pill pill--${nvr.idPonto === nvrAtivo?.idPonto ? 'info' : 'neutro'}`}>
                        {nvr.configurada ? 'Configurado' : nvr.idPonto === nvrAtivo?.idPonto ? 'Selecionado' : 'Selecionar'}
                      </span>
                    </button>
                  ))}
                </div>

                <div className="grid-2" style={{ marginTop: 10 }}>
                  <Field label="Usuário do NVR">
                    <Input
                      value={credenciais.usuario}
                      autoComplete="off"
                      onChange={(e) => setCredenciais((c) => ({ ...c, usuario: e.target.value }))}
                    />
                  </Field>
                  <Field label="Senha do NVR">
                    <Input
                      type="password"
                      autoComplete="off"
                      value={credenciais.senha}
                      onChange={(e) => setCredenciais((c) => ({ ...c, senha: e.target.value }))}
                    />
                  </Field>
                </div>
                <p className="card__meta" style={{ marginTop: 4 }}>
                  A senha não é enviada ao MAPOS: ela só é usada no aparelho para falar com o NVR.
                </p>
                <Button block style={{ marginTop: 10 }} loading={lendoNvr} disabled={!pronto?.disponivel || !nvrAtivo} onClick={() => void lerNvr()}>
                  Conectar e ver canais
                </Button>
                {nvrLido ? (
                  <p className="card__meta" style={{ marginTop: 8 }}>
                    {nvrLido.nome || 'NVR'}
                    {nvrLido.modelo ? ` · ${nvrLido.modelo}` : ''}
                    {nvrLido.firmware ? ` · firmware ${nvrLido.firmware}` : ''} · {canaisLivres.length} canal(is) livre(s)
                  </p>
                ) : null}
              </Card>
            )}

            <div className="section-head">
              <p className="section-title">Câmeras do projeto ({cameras.length})</p>
            </div>

            {cameras.length === 0 ? (
              <Empty
                title="Nenhuma câmera prevista"
                message="Cadastre os pontos do tipo câmera no projeto, ou crie abaixo a partir do que foi encontrado na rede."
              />
            ) : (
              <Card style={{ padding: '4px 14px' }}>
                {cameras.map((camera) => {
                  const descoberto = descobertos.find((d) => mesmoMac(camera.mac, d.mac) || camera.ip === d.ip);
                  return (
                    <button
                      key={camera.idPonto}
                      type="button"
                      className="listrow"
                      onClick={() => abrir(camera, descoberto)}
                    >
                      <span className="listrow__main">
                        <span className="listrow__title">{camera.identificacao}</span>
                        <span className="listrow__meta">
                          {[camera.local, camera.ip && `IP ${camera.ip}`, camera.canal && `canal ${camera.canal}`]
                            .filter(Boolean)
                            .join(' · ') || 'Sem local, IP ou canal'}
                          {descoberto ? ' · encontrada na rede' : ''}
                        </span>
                      </span>
                      <span className={`pill pill--${camera.configurada ? 'sucesso' : 'agendada'}`}>
                        {camera.configurada ? 'Configurada' : 'Pendente'}
                      </span>
                    </button>
                  );
                })}
              </Card>
            )}

            {descobertos.length ? (
              <Card>
                <p className="section-title">Encontrados na rede ({descobertos.length})</p>
                <p className="card__meta" style={{ marginTop: 4 }}>
                  Equipamentos que ainda não têm ponto no projeto podem ser cadastrados agora.
                </p>
                <div className="stack" style={{ marginTop: 8 }}>
                  {descobertos.map((d) => {
                    const previsto = acharPrevisto(cameras, d);
                    return (
                      <div key={d.sadpId || d.mac || d.ip} className="listrow" style={{ cursor: 'default' }}>
                        <span className="listrow__main">
                          <span className="listrow__title">{d.ip}</span>
                          <span className="listrow__meta">
                            {[d.modelo, d.serie, d.firmware].filter(Boolean).join(' · ') || 'Hikvision'}
                            {d.ativado ? '' : ' · não ativada'}
                          </span>
                        </span>
                        {previsto ? (
                          <span className="pill pill--info">{previsto.identificacao}</span>
                        ) : (
                          <Button small onClick={() => setDescobertoAberto(d)}>
                            {d.ativado ? 'Cadastrar' : 'Ativar e cadastrar'}
                          </Button>
                        )}
                      </div>
                    );
                  })}
                </div>
                {semVinculo.length === 0 ? (
                  <p className="card__meta" style={{ marginTop: 6 }}>
                    Todas as câmeras encontradas já estão no projeto.
                  </p>
                ) : null}
              </Card>
            ) : null}
          </>
        ) : null}
      </div>

      <CameraPontoSheet
        camera={editando}
        form={form}
        senha={senhaCamera}
        nvrs={nvrs}
        canaisLivres={canaisLivres}
        nvrLido={Boolean(nvrLido)}
        busy={busy}
        onForm={setForm}
        onSenha={setSenhaCamera}
        onFechar={() => setEditando(null)}
        onSalvar={() => void salvar()}
      />

      <CameraDescobertaSheet
        dispositivo={descobertoAberto}
        nvr={nvrAtivo}
        prefixo={prefixoDeRede(rede?.ip) ?? prefixoDeRede(cameras.find((c) => c.ip)?.ip)}
        usados={[
          ...cameras.map((c) => c.ip ?? ''),
          ...descobertos.map((d) => d.ip),
        ]}
        canaisLivres={canaisLivres}
        nvrCredenciais={credenciais}
        onFechar={() => setDescobertoAberto(null)}
        onCadastrar={criarDoDescoberto}
      />
    </div>
  );
}
