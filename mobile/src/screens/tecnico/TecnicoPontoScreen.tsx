/**
 * Ponto do projeto: avançar status com foto, foto da etiqueta (câmera),
 * checklist de teste de imagem, repasse e histórico. As obrigatoriedades
 * (etiqueta, teste, foto) vêm do servidor em `exigeEtiqueta`/`exigeTeste`.
 */

import { useMemo, useState } from 'react';
import * as tec from '../../lib/api-tecnico';
import { useCarga } from '../../lib/hooks';
import { useApi } from '../../lib/store';
import { navigate } from '../../lib/router';
import { dateTimeBr } from '../../lib/format';
import { escolherFoto } from '../../lib/capture';
import { Alert, Button, Card, Field, Input, Select, SkeletonList, Textarea, Topbar } from '../../components/ui';
import { Fotos } from '../../components/Fotos';
import type { PontoDetalhe } from '../../lib/types-tecnico';

interface Executor {
  idUsuarios: number;
  nome: string;
  encarregado?: boolean;
}

export function TecnicoPontoScreen({ obra, id }: { obra: string; id: string }) {
  const { baseUrl, token, handle } = useApi();
  const { data, loading, error, setData, recarregar } = useCarga<PontoDetalhe>(
    (url, tk) => tec.getPonto(url, tk, id, obra),
    [obra, id],
  );
  const [avancar, setAvancar] = useState(false);
  const [destino, setDestino] = useState('');
  const [busy, setBusy] = useState(false);
  const [erro, setErro] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);

  // Formulário de status
  const [fotos, setFotos] = useState<string[]>([]);
  const [fotosEtiqueta, setFotosEtiqueta] = useState<string[]>([]);
  const [metragem, setMetragem] = useState('');
  const [ip, setIp] = useState('');
  const [canal, setCanal] = useState('');
  const [porta, setPorta] = useState('');
  const [modelo, setModelo] = useState('');
  const [serie, setSerie] = useState('');
  const [local, setLocal] = useState('');
  const [observacao, setObservacao] = useState('');
  const [repassarPara, setRepassarPara] = useState('');
  const [instrucao, setInstrucao] = useState('');
  const [teste, setTeste] = useState<Record<string, boolean>>({});
  const [retencao, setRetencao] = useState('');

  const ponto = data?.ponto;
  const lista = data?.statusLista ?? {};
  const proximo = data?.proximoStatus ?? null;

  const executores = useMemo<Executor[]>(() => data?.executores ?? [], [data]);

  const abrirAvanco = (status: string) => {
    setDestino(status);
    setErro(null);
    setOk(null);
    setFotos([]);
    setFotosEtiqueta([]);
    setMetragem(ponto?.metragem_cabo ? String(ponto.metragem_cabo).replace('.', ',') : '');
    setIp(ponto?.ip ?? '');
    setCanal(ponto?.canal ?? '');
    setPorta(ponto?.porta ?? '');
    setModelo(ponto?.modelo ?? '');
    setSerie(ponto?.serie ?? '');
    setLocal(ponto?.local ?? '');
    setObservacao('');
    setRepassarPara('');
    setInstrucao('');
    setTeste({});
    setRetencao('');
    setAvancar(true);
  };

  const tirarFoto = async (etiqueta: boolean) => {
    const foto = await escolherFoto();
    if (!foto) return;
    if (etiqueta) setFotosEtiqueta((l) => [...l, foto].slice(0, 6));
    else setFotos((l) => [...l, foto].slice(0, 6));
  };

  const salvar = async () => {
    if (!ponto) return;
    setBusy(true);
    setErro(null);
    try {
      const payload: tec.PontoStatusPayload = {
        obra_id: obra,
        status: destino,
        fotos: fotos.length ? fotos : undefined,
        fotos_etiqueta: fotosEtiqueta.length ? fotosEtiqueta : undefined,
        metragem_cabo: metragem || undefined,
        ip: ip || undefined,
        canal: canal || undefined,
        porta: porta || undefined,
        modelo: modelo || undefined,
        serie: serie || undefined,
        local: local || undefined,
        observacao: observacao || undefined,
        repassar_para: repassarPara || undefined,
        instrucao: instrucao || undefined,
      };
      if (requerTeste) {
        payload.teste = {
          itens: Object.fromEntries(Object.entries(teste).map(([k, v]) => [k, v ? 1 : 0])),
          retencao_dias: retencao,
        };
      }
      const r = await handle(() => tec.mudarStatusPonto(baseUrl, token, ponto.idPonto, payload));
      setOk(`Ponto atualizado para ${lista[destino]?.rotulo ?? destino}.`);
      setAvancar(false);
      setData({ ...data!, ponto: r.ponto });
      recarregar();
    } catch (err) {
      setErro((err as Error).message);
    } finally {
      setBusy(false);
    }
  };

  const requerTeste = Boolean(data?.exigeTeste) && destino === 'testado';
  const requerEtiqueta = Boolean(data?.exigeEtiqueta) && destino === 'instalado';
  const exigeFoto = destino === 'instalado' || destino === 'testado';
  const faltando: string[] = [];
  if (exigeFoto && !fotos.length && !fotosEtiqueta.length && !(destino === 'testado' && data?.fotos?.length)) {
    faltando.push('ao menos uma foto');
  }
  if (requerEtiqueta && !fotosEtiqueta.length && !data?.temFotoEtiqueta) faltando.push('foto da etiqueta');
  if (requerTeste) {
    if (!retencao) faltando.push('retenção em dias');
    for (const [chave, rotulo] of Object.entries(data?.testeItens ?? {})) {
      if (!teste[chave] && OBG.has(chave)) faltando.push(rotulo);
    }
  }

  const bloqueado = faltando.length > 0;

  return (
    <div className="screen">
      <Topbar
        title={ponto?.identificacao ?? 'Ponto'}
        subtitle={ponto ? lista[ponto.status]?.rotulo ?? ponto.status : undefined}
        onBack={() => navigate(`tecnico/projeto/${obra}`)}
      />

      <div className="content">
        {loading && !data ? <SkeletonList count={3} /> : null}
        {error ? <Alert kind="error">{error}</Alert> : null}
        {erro ? <Alert kind="error">{erro}</Alert> : null}
        {ok ? <Alert kind="ok">{ok}</Alert> : null}

        {data && ponto ? (
          <>
            <Card>
              <div className="card__head">
                <div style={{ minWidth: 0 }}>
                  <p className="card__title">{ponto.identificacao}</p>
                  <p className="card__meta">
                    {ponto.tipo || 'Ponto'}
                    {ponto.local ? ` · ${ponto.local}` : ''}
                  </p>
                </div>
                <span className={`pill pill--${lista[ponto.status]?.classe ?? 'neutro'}`}>
                  {lista[ponto.status]?.rotulo ?? ponto.status}
                </span>
              </div>

              <div className="kv">
                <span className="kv__k">Responsável</span>
                <span className="kv__v">{ponto.minha ? 'Você' : ponto.responsavel || 'Sem responsável'}</span>
              </div>
              <div className="kv">
                <span className="kv__k">Fotos</span>
                <span className="kv__v">{ponto.qtd_fotos ?? data.fotos.length}</span>
              </div>
              {ponto.modelo || ponto.serie ? (
                <div className="kv">
                  <span className="kv__k">Equipamento</span>
                  <span className="kv__v">
                    {[ponto.modelo, ponto.serie].filter(Boolean).join(' · ')}
                  </span>
                </div>
              ) : null}
              {ponto.ip || ponto.canal || ponto.porta ? (
                <div className="kv">
                  <span className="kv__k">Rede</span>
                  <span className="kv__v">
                    {[ponto.ip && `IP ${ponto.ip}`, ponto.canal && `canal ${ponto.canal}`, ponto.porta && `porta ${ponto.porta}`]
                      .filter(Boolean)
                      .join(' · ')}
                  </span>
                </div>
              ) : null}
              {ponto.metragem_cabo ? (
                <div className="kv">
                  <span className="kv__k">Metragem de cabo</span>
                  <span className="kv__v">{ponto.metragem_cabo} m</span>
                </div>
              ) : null}
            </Card>

            <div className="stack">
              {proximo ? (
                <Button block onClick={() => abrirAvanco(proximo)}>
                  Avançar para {lista[proximo]?.rotulo ?? proximo}
                </Button>
              ) : null}
              <div className="quick">
                {Object.entries(lista)
                  .filter(([chave]) => chave !== ponto.status)
                  .slice(0, 4)
                  .map(([chave, item]) => (
                    <button key={chave} type="button" className="quick__item" onClick={() => abrirAvanco(chave)}>
                      <span className="pill pill--info">{item.rotulo}</span>
                      <span className="quick__meta">Corrigir status</span>
                    </button>
                  ))}
              </div>
              {!ponto.minha ? (
                <Button
                  variant="ghost"
                  block
                  loading={busy}
                  onClick={() => {
                    setBusy(true);
                    setErro(null);
                    handle(() => tec.assumirPonto(baseUrl, token, ponto.idPonto, obra))
                      .then(() => {
                        setOk('Ponto assumido por você.');
                        recarregar();
                      })
                      .catch((err: Error) => setErro(err.message))
                      .finally(() => setBusy(false));
                  }}
                >
                  Assumir este ponto
                </Button>
              ) : null}
            </div>

            {data.fotos.length ? (
              <Card>
                <p className="section-title">Fotos do ponto</p>
                <Fotos
                  itens={data.fotos.map((f) => ({
                    path: f.url ?? '',
                    etapa: f.legenda === 'etiqueta' ? 'etiqueta' : f.etapa,
                    titulo: f.legenda ?? f.etapa ?? 'Foto',
                  }))}
                />
              </Card>
            ) : null}

            {data.historico.length ? (
              <Card>
                <p className="section-title">Histórico</p>
                {data.historico.map((h) => (
                  <div className="kv" key={h.id}>
                    <span className="kv__k" style={{ color: 'var(--text)', fontSize: '0.82rem' }}>
                      {h.evento || `${h.de ?? '—'} → ${h.para ?? '—'}`}
                      {h.destinatario ? ` · ${h.destinatario}` : ''}
                    </span>
                    <span className="kv__v" style={{ fontSize: '0.76rem', fontWeight: 500 }}>
                      {h.usuario ? `${h.usuario} · ` : ''}
                      {dateTimeBr(h.data)}
                    </span>
                  </div>
                ))}
              </Card>
            ) : null}

            {/* Painel de avanço */}
            {avancar ? (
              <Card>
                <div className="section-head">
                  <p className="section-title">Avançar para {lista[destino]?.rotulo ?? destino}</p>
                  <button type="button" className="reset-button" onClick={() => setAvancar(false)}>
                    Fechar
                  </button>
                </div>

                {requerEtiqueta ? (
                  <div className="hint-box" style={{ marginTop: 8 }}>
                    <span>Foto da etiqueta da câmera (modelo e série) é obrigatória para "Instalado".</span>
                  </div>
                ) : null}
                {requerTeste ? (
                  <div className="hint-box" style={{ marginTop: 8 }}>
                    <span>Checklist de teste de imagem é obrigatório para "Testado" (câmeras).</span>
                  </div>
                ) : null}

                <button type="button" className="capture-btn" style={{ marginTop: 10 }} onClick={() => void tirarFoto(false)}>
                  Tirar foto {exigeFoto ? '(obrigatória)' : '(opcional)'}
                </button>
                {fotos.length ? (
                  <div className="thumbs" style={{ marginTop: 8 }}>
                    {fotos.map((f, i) => (
                      <button key={i} type="button" className="thumb" onClick={() => setFotos((l) => l.filter((_, x) => x !== i))}>
                        <img src={f} alt="" />
                        <span className="thumb__etapa">remover</span>
                      </button>
                    ))}
                  </div>
                ) : null}

                {requerEtiqueta ? (
                  <>
                    <button type="button" className="capture-btn" style={{ marginTop: 10 }} onClick={() => void tirarFoto(true)}>
                      Foto da etiqueta (obrigatória)
                    </button>
                    {fotosEtiqueta.length || data.temFotoEtiqueta ? (
                      <p className="card__meta" style={{ marginTop: 4 }}>
                        {fotosEtiqueta.length
                          ? `${fotosEtiqueta.length} foto(s) da etiqueta pronta(s)`
                          : 'Já existe foto da etiqueta salva.'}
                      </p>
                    ) : null}
                  </>
                ) : null}

                {requerTeste ? (
                  <div className="stack" style={{ marginTop: 10 }}>
                    <p className="section-title">Teste de imagem</p>
                    {Object.entries(data.testeItens).map(([chave, rotulo]) => (
                      <label className="checkline" key={chave}>
                        <input
                          type="checkbox"
                          checked={Boolean(teste[chave])}
                          onChange={(e) => setTeste((t) => ({ ...t, [chave]: e.target.checked }))}
                        />
                        <span>{rotulo}</span>
                      </label>
                    ))}
                    <Field label="Retenção da gravação (dias)">
                      <Input
                        inputMode="numeric"
                        value={retencao}
                        onChange={(e) => setRetencao(e.target.value.replace(/\D/g, ''))}
                        placeholder="Ex.: 30"
                      />
                    </Field>
                  </div>
                ) : null}

                <div className="grid-2" style={{ marginTop: 10 }}>
                  <Field label="Local">
                    <Input value={local} onChange={(e) => setLocal(e.target.value)} maxLength={150} />
                  </Field>
                  <Field label="Metragem de cabo (m)">
                    <Input value={metragem} onChange={(e) => setMetragem(e.target.value)} inputMode="decimal" />
                  </Field>
                  <Field label="Modelo">
                    <Input value={modelo} onChange={(e) => setModelo(e.target.value)} maxLength={80} />
                  </Field>
                  <Field label="Número de série">
                    <Input value={serie} onChange={(e) => setSerie(e.target.value)} maxLength={60} />
                  </Field>
                  <Field label="IP">
                    <Input value={ip} onChange={(e) => setIp(e.target.value)} inputMode="decimal" />
                  </Field>
                  <Field label="Canal / porta">
                    <Input
                      value={canal}
                      onChange={(e) => setCanal(e.target.value)}
                      placeholder="canal"
                      maxLength={30}
                    />
                  </Field>
                </div>

                <Field label="Observação">
                  <Textarea value={observacao} onChange={(e) => setObservacao(e.target.value)} maxLength={255} />
                </Field>

                <Field label="Repassar para (opcional)">
                  <Select value={repassarPara} onChange={(e) => setRepassarPara(e.target.value)}>
                    <option value="">Não repassar</option>
                    <option value="equipe">Deixar para a equipe</option>
                    {executores
                      .filter((ex) => ex.nome)
                      .map((ex) => (
                        <option key={ex.idUsuarios} value={String(ex.idUsuarios)}>
                          {ex.nome}
                        </option>
                      ))}
                  </Select>
                </Field>
                {repassarPara ? (
                  <Field label="Instrução para quem recebe">
                    <Input value={instrucao} onChange={(e) => setInstrucao(e.target.value)} maxLength={255} />
                  </Field>
                ) : null}

                {bloqueado ? (
                  <Alert kind="info">Falta: {faltando.join(', ')}.</Alert>
                ) : null}

                <div className="actionbar" style={{ marginTop: 10 }}>
                  <Button variant="ghost" block onClick={() => setAvancar(false)}>
                    Cancelar
                  </Button>
                  <Button block loading={busy} disabled={bloqueado} onClick={() => void salvar()}>
                    Salvar
                  </Button>
                </div>
              </Card>
            ) : null}
          </>
        ) : null}
      </div>
    </div>
  );
}

/** Itens do checklist que o servidor exige marcados (Obra_pontos_model::TESTE_OBRIGATORIOS). */
const OBG = new Set(['imagem_ok', 'gravando_nvr', 'visao_noturna', 'foco_enquadramento', 'data_hora']);
