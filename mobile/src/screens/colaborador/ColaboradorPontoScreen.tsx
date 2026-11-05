/**
 * Bater ponto: relógio, câmera frontal (selfie), GPS e vínculo opcional com OS
 * ou obra. O geofence e o reconhecimento facial seguem a configuração do RH —
 * o servidor é quem decide, mas avisamos antes para não perder a batida.
 */

import { useEffect, useRef, useState } from 'react';
import * as col from '../../lib/api-colaborador';
import { useCarga, useRelogio } from '../../lib/hooks';
import { useApi } from '../../lib/store';
import { navigate } from '../../lib/router';
import { abrirCameraFrontal, dataPorExtenso, distanciaMetros, distanciaTexto, fecharStream, horaAgora, selfieDoVideo } from '../../lib/geo';
import { obterLocalizacao } from '../../lib/capture';
import { Alert, Button, Card, Field, Select, SkeletonList, Topbar } from '../../components/ui';
import { PONTO_LABELS, type PontoEstado } from '../../lib/types-tecnico';

export function ColaboradorPontoScreen() {
  const { baseUrl, token, handle } = useApi();
  const { data, loading, error, setData } = useCarga<PontoEstado>((url, tk) => col.pontoEstado(url, tk));
  const agora = useRelogio();

  const videoRef = useRef<HTMLVideoElement | null>(null);
  const streamRef = useRef<MediaStream | null>(null);
  const [camOk, setCamOk] = useState(false);
  const [geo, setGeo] = useState<{ lat: number; lng: number } | null>(null);
  const [unidade, setUnidade] = useState('');
  const [osId, setOsId] = useState('');
  const [obraId, setObraId] = useState('');
  const [busy, setBusy] = useState(false);
  const [ok, setOk] = useState<string | null>(null);
  const [erro, setErro] = useState<string | null>(null);

  useEffect(() => {
    let ativo = true;
    void abrirCameraFrontal().then((stream) => {
      if (!ativo) {
        fecharStream(stream);
        return;
      }
      streamRef.current = stream;
      if (stream && videoRef.current) {
        videoRef.current.srcObject = stream;
        setCamOk(true);
      }
    });
    return () => {
      ativo = false;
      fecharStream(streamRef.current);
      streamRef.current = null;
    };
  }, []);

  useEffect(() => {
    void obterLocalizacao().then((pos) => {
      if (pos) setGeo({ lat: pos.latitude, lng: pos.longitude });
    });
  }, []);

  useEffect(() => {
    if (data?.unidades?.length && !unidade) {
      setUnidade(String(data.unidades[0]?.id ?? ''));
    }
  }, [data, unidade]);

  const unidadeSel = data?.unidades.find((u) => String(u.id) === unidade) ?? null;
  const distancia =
    geo && unidadeSel?.latitude && unidadeSel?.longitude
      ? distanciaMetros(geo.lat, geo.lng, Number(unidadeSel.latitude), Number(unidadeSel.longitude))
      : null;
  const foraArea = distancia !== null && unidadeSel?.raio_metros ? distancia > unidadeSel.raio_metros : false;
  const bloqueiaArea = Boolean(data?.config.geofenceObrigatorio) && foraArea && !osId && !obraId;

  const registrar = async (tipo: string) => {
    if (!data) return;
    setBusy(true);
    setErro(null);
    setOk(null);
    try {
      // GPS fresco quando possível; senão usa o último obtido na tela.
      const atual = await obterLocalizacao();
      const coords = atual
        ? { latitude: atual.latitude, longitude: atual.longitude }
        : geo
          ? { latitude: geo.lat, longitude: geo.lng }
          : null;
      const selfie = selfieDoVideo(videoRef.current);
      const payload: col.RegistrarPontoPayload = {
        tipo,
        foto: selfie ?? undefined,
        latitude: coords?.latitude,
        longitude: coords?.longitude,
      };
      if (osId) payload.os_id = osId;
      else if (obraId) payload.obra_id = obraId;
      else if (unidade) payload.unidade_id = Number(unidade);

      const r = await handle(() => col.registrarPonto(baseUrl, token, payload));
      setOk(`${PONTO_LABELS[r.tipo] ?? r.tipo} registrada às ${r.hora}.${r.fora_area ? ' Fora da área da unidade.' : ''}`);
      setData({ ...data, batidasHoje: r.batidasHoje, proximoTipo: r.proximoTipo });
      setOsId('');
      setObraId('');
    } catch (err) {
      setErro((err as Error).message);
    } finally {
      setBusy(false);
    }
  };

  const proximo = data?.proximoTipo ?? 'entrada';

  return (
    <div className="screen">
      <Topbar title="Bater ponto" subtitle={data?.colaborador.nome} onBack={() => navigate('colaborador')} />

      <div className="content">
        {loading && !data ? <SkeletonList count={3} /> : null}
        {error ? <Alert kind="error">{error}</Alert> : null}
        {erro ? <Alert kind="error">{erro}</Alert> : null}
        {ok ? <Alert kind="ok">{ok}</Alert> : null}

        {data ? (
          <>
            <Card>
              <div className="row row--between">
                <div>
                  <div className="clock">{horaAgora(agora)}</div>
                  <p className="card__meta" style={{ marginTop: 4 }}>
                    {dataPorExtenso(agora)}
                  </p>
                </div>
                <div style={{ textAlign: 'right' }}>
                  <p className="section-title">Próxima batida</p>
                  <p className="card__title">{PONTO_LABELS[proximo] ?? 'Entrada'}</p>
                </div>
              </div>
            </Card>

            <div className="camera-box">
              <video ref={videoRef} autoPlay playsInline muted />
              {!camOk ? (
                <div className="camera-box__off">
                  <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round">
                    <path d="M4 8h3l2-3h6l2 3h3v11H4z" />
                    <path d="m3 3 18 18" />
                  </svg>
                  <span>Câmera indisponível. A batida segue com GPS.</span>
                </div>
              ) : null}
              <div className="camera-badge">
                <span>{geo ? 'GPS obtido' : 'Localizando…'}</span>
                {distancia !== null ? (
                  <span style={{ color: foraArea ? '#fec84b' : '#6ce9a6' }}>
                    {foraArea ? 'Fora' : 'Dentro'} · {distanciaTexto(distancia)}
                  </span>
                ) : null}
              </div>
            </div>

            {data.unidades.length ? (
              <Card>
                <Field label="Local de trabalho">
                  <Select value={unidade} onChange={(e) => setUnidade(e.target.value)}>
                    {data.unidades.map((u) => (
                      <option key={u.id} value={String(u.id)}>
                        {u.nome}
                      </option>
                    ))}
                  </Select>
                </Field>
              </Card>
            ) : null}

            {data.os.length || data.obras.length ? (
              <Card>
                <div className="stack">
                  {data.os.length ? (
                    <Field label="Vincular a uma OS (opcional)" hint="Atendimento em campo: o local vira prova da visita.">
                      <Select
                        value={osId}
                        onChange={(e) => {
                          setOsId(e.target.value);
                          if (e.target.value) setObraId('');
                        }}
                      >
                        <option value="">Não — ponto normal</option>
                        {data.os.map((os) => (
                          <option key={os.idOs} value={String(os.idOs)}>
                            OS {String(os.idOs).padStart(4, '0')} · {os.status}
                          </option>
                        ))}
                      </Select>
                    </Field>
                  ) : null}
                  {data.obras.length ? (
                    <Field label="Vincular a uma obra (opcional)">
                      <Select
                        value={obraId}
                        onChange={(e) => {
                          setObraId(e.target.value);
                          if (e.target.value) setOsId('');
                        }}
                      >
                        <option value="">Não — ponto normal</option>
                        {data.obras.map((ob) => (
                          <option key={ob.idObra} value={String(ob.idObra)}>
                            {ob.nome}
                          </option>
                        ))}
                      </Select>
                    </Field>
                  ) : null}
                </div>
              </Card>
            ) : null}

            {bloqueiaArea ? (
              <Alert kind="error">
                Você está fora da área permitida ({distanciaTexto(distancia)} da unidade). Aproxime-se do local ou vincule
                uma OS/obra.
              </Alert>
            ) : null}

            {data.config.faceObrigatorio && !data.temBiometria ? (
              <Alert kind="info">
                O RH exige reconhecimento facial, mas não há biometria cadastrada para você. Procure o RH.
              </Alert>
            ) : null}

            <Button
              block
              loading={busy}
              disabled={bloqueiaArea}
              onClick={() => void registrar(proximo)}
            >
              Registrar {PONTO_LABELS[proximo] ?? 'Entrada'}
            </Button>

            {proximo !== 'saida' ? (
              <Button variant="ghost" block loading={busy} disabled={bloqueiaArea} onClick={() => void registrar('saida')}>
                Encerrar turno (saída)
              </Button>
            ) : null}

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
                          <span className="punch__sub">fora da área</span>
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

            {data.config.geofenceObrigatorio || data.config.faceObrigatorio ? (
              <p className="card__meta">
                Regras do RH: {data.config.geofenceObrigatorio ? 'geofence obrigatório' : 'geofence opcional'}
                {data.config.faceObrigatorio ? ` · facial obrigatório (score mínimo ${data.config.faceScoreMinimo.toFixed(2)})` : ''}
              </p>
            ) : null}
          </>
        ) : null}
      </div>
    </div>
  );
}
