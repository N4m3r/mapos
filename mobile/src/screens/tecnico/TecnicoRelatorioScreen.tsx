/** Meu relatório: o que o técnico registrou nos projetos no dia, semana ou mês. */

import { useState } from 'react';
import * as tec from '../../lib/api-tecnico';
import { useCarga } from '../../lib/hooks';
import { navigate } from '../../lib/router';
import { Alert, Button, Card, Empty, Progress, Segmented, SkeletonList, Topbar } from '../../components/ui';
import type { RelatorioAvanco, RelatorioTecnico } from '../../lib/types-tecnico';

type Tipo = 'diario' | 'semanal' | 'mensal';

const TIPOS: ReadonlyArray<{ key: Tipo; label: string }> = [
  { key: 'diario', label: 'Dia' },
  { key: 'semanal', label: 'Semana' },
  { key: 'mensal', label: 'Mês' },
];

const CARTOES: ReadonlyArray<[string, string]> = [
  ['registros', 'Registros'],
  ['projetos', 'Projetos'],
  ['fotos', 'Fotos'],
  ['ocorrencias', 'Ocorrências'],
  ['avancos', 'Pontos avançados'],
  ['cabo_m', 'Cabo (m)'],
];

function hojeIso(): string {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function qtd(v: unknown): string {
  const n = Number(v) || 0;
  return n.toLocaleString('pt-BR', { maximumFractionDigits: 3 });
}

function diaMes(d?: string | null): string {
  if (!d) return '';
  const [, m, dia] = d.slice(0, 10).split('-');
  return dia && m ? `${dia}/${m}` : d;
}

function horaCurta(d?: string | null): string {
  return d && d.length >= 16 ? `${diaMes(d)} ${d.slice(11, 16)}` : diaMes(d);
}

/** "29/10 14:05 · CAM-07 (Corredor B) → Cabo passado · 35 m", como no web. */
export function linhaAvanco(av: RelatorioAvanco): string {
  const hora = horaCurta(av.data);
  if (av.evento === 'repasse') return `${hora} · ${av.identificacao} repassado para ${av.destinatario || 'a equipe'}`;
  if (av.evento === 'assumiu') return `${hora} · assumiu ${av.identificacao}`;
  let linha = `${hora} · ${av.identificacao}`;
  if (av.local) linha += ` (${av.local})`;
  linha += ` → ${av.para_rotulo ?? ''}`;
  if (Number(av.metragem_cabo) > 0) linha += ` · ${qtd(av.metragem_cabo)} m`;
  return linha;
}

function textoResumo(rel: RelatorioTecnico): string {
  const t = rel.totais;
  const n = (k: string) => Number(t[k] ?? 0) || 0;
  let txt = `Meu relatório - ${rel.periodo.rotulo ?? `${rel.periodo.inicio} a ${rel.periodo.fim}`}\n`;
  txt += `${n('registros')} registro(s), ${n('projetos')} projeto(s), ${n('fotos')} foto(s), ${n('ocorrencias')} ocorrência(s)\n`;
  txt += `${n('avancos')} ponto(s) avançado(s), ${qtd(t.cabo_m)} m de cabo\n`;
  for (const pj of rel.projetos ?? []) {
    txt += `\n${pj.nome}\n`;
    for (const r of pj.registros) {
      txt += `- ${diaMes(r.data)}: ${r.atividades.replace(/\s+/g, ' ').trim()}\n`;
      if (r.ocorrencias.trim()) txt += `  Ocorrência: ${r.ocorrencias.replace(/\s+/g, ' ').trim()}\n`;
    }
    if (pj.avancos.length) {
      txt += 'Pontos avançados:\n';
      for (const av of pj.avancos) txt += `- ${linhaAvanco(av)}\n`;
    }
  }
  return txt;
}

export function TecnicoRelatorioScreen() {
  const [tipo, setTipo] = useState<Tipo>('diario');
  const [data, setData] = useState(hojeIso());
  const carga = useCarga<RelatorioTecnico>((url, tk) => tec.relatorio(url, tk, tipo, data), [tipo, data]);
  const rel = carga.data;
  const projetos = rel?.projetos ?? [];

  const compartilhar = async () => {
    if (!rel) return;
    const texto = textoResumo(rel);
    if (navigator.share) {
      try {
        await navigator.share({ title: 'Meu relatório', text: texto });
        return;
      } catch {
        /* cancelado: cai no WhatsApp */
      }
    }
    window.open(`https://wa.me/?text=${encodeURIComponent(texto)}`, '_blank', 'noopener');
  };

  return (
    <div className="screen">
      <Topbar title="Meu relatório" subtitle="O que você registrou nos projetos" onBack={() => navigate('tecnico')} />

      <div className="content">
        <Card>
          <div className="row row--between">
            <Button
              variant="ghost"
              aria-label="Período anterior"
              disabled={!rel?.periodo.anterior}
              onClick={() => rel?.periodo.anterior && setData(rel.periodo.anterior)}
            >
              ‹
            </Button>
            <strong style={{ flex: 1, textAlign: 'center' }}>
              {rel?.periodo.rotulo ?? (rel ? `${diaMes(rel.periodo.inicio)} a ${diaMes(rel.periodo.fim)}` : '…')}
            </strong>
            <Button
              variant="ghost"
              aria-label="Próximo período"
              disabled={!rel?.periodo.proximo}
              onClick={() => rel?.periodo.proximo && setData(rel.periodo.proximo)}
            >
              ›
            </Button>
          </div>
          <Segmented items={TIPOS} value={tipo} onChange={setTipo} label="Período do relatório" />
        </Card>

        {carga.error ? <Alert kind="error">{carga.error}</Alert> : null}

        {carga.loading && !rel ? (
          <SkeletonList count={3} />
        ) : rel ? (
          <>
            <div className="relatorio-grid">
              {CARTOES.map(([k, rotulo]) => (
                <Card key={k}>
                  <p className="relatorio-grid__num">{k === 'cabo_m' ? qtd(rel.totais[k]) : Number(rel.totais[k] ?? 0) || 0}</p>
                  <p className="card__meta">{rotulo}</p>
                </Card>
              ))}
            </div>

            {!projetos.length ? (
              <Empty
                title="Nenhum registro neste período."
                action={
                  <Button variant="ghost" onClick={() => navigate('tecnico/projetos')}>
                    Ir para meus projetos
                  </Button>
                }
              />
            ) : (
              <div className="stack">
                {projetos.map((pj) => (
                  <Card key={pj.idObra}>
                    <p className="card__title">{pj.nome}</p>
                    <p className="card__meta">{pj.cliente || '—'}</p>
                    <Progress value={pj.percentual_concluido} />
                    <p className="card__meta">{qtd(pj.percentual_concluido)}% concluído</p>
                    {pj.avancos.length ? (
                      <>
                        <p className="section-title">Pontos avançados</p>
                        {pj.avancos.map((av, i) => (
                          <p key={i} className="card__meta">
                            {linhaAvanco(av)}
                          </p>
                        ))}
                      </>
                    ) : null}
                    {pj.registros.map((r, i) => (
                      <div key={i} style={{ marginTop: 8 }}>
                        <strong>{diaMes(r.data)}</strong>
                        <p style={{ whiteSpace: 'pre-line', margin: '4px 0' }}>{r.atividades}</p>
                        {r.ocorrencias.trim() ? (
                          <p className="card__meta" style={{ whiteSpace: 'pre-line' }}>
                            Ocorrência: {r.ocorrencias}
                          </p>
                        ) : null}
                      </div>
                    ))}
                  </Card>
                ))}
              </div>
            )}

            <Button variant="ghost" block onClick={() => void compartilhar()}>
              Compartilhar resumo
            </Button>
          </>
        ) : null}
      </div>
    </div>
  );
}
