/**
 * Sincronização do modo offline.
 *
 * - `sincronizarFila`: entrega os envios guardados, em ordem. Sem rede, para e
 *   tenta de novo depois; recusa do servidor (ex.: OS já finalizada) fica
 *   marcada como falha para o usuário ver e descartar.
 * - `baixarParaOffline`: busca tudo o que as telas da área do usuário usam
 *   (mesmas funções das telas, então ficam guardadas com as mesmas chaves).
 */

import * as api from './api';
import * as tec from './api-tecnico';
import * as col from './api-colaborador';
import { ApiError, requestRede } from './http';
import { atualizarFila, removerFila } from './offline-db';
import { contarFila, estadoOffline, itensDaFila, mudarEstado, registrarDownload } from './offline';
import { competenciaAtual } from './format';
import type { Area } from './area';

/** Limites para o download não virar horas em projeto grande. */
const MAX_OS = 120;
const MAX_PONTOS = 400;
export const LIMITE_LISTA_BUSCA = 3000;

export interface ResultadoSync {
  enviados: number;
  falhas: number;
  sessaoVencida: boolean;
}

export async function sincronizarFila(baseUrl: string, token: string): Promise<ResultadoSync> {
  const resultado: ResultadoSync = { enviados: 0, falhas: 0, sessaoVencida: false };
  if (estadoOffline().sincronizando || !token) return resultado;
  mudarEstado({ sincronizando: true });
  try {
    for (const item of await itensDaFila()) {
      if (item.erro || item.baseUrl !== baseUrl || item.id === undefined) continue;
      try {
        const payload = await requestRede<{ status?: boolean; message?: string } | null>(item.baseUrl, item.caminho, {
          method: item.metodo,
          token,
          body: item.corpo,
          timeoutMs: 60000,
        });
        if (payload && typeof payload === 'object' && payload.status === false) {
          await atualizarFila({ ...item, tentativas: item.tentativas + 1, erro: payload.message || 'Recusado pelo servidor.' });
          resultado.falhas++;
          continue;
        }
        await removerFila(item.id);
        resultado.enviados++;
        mudarEstado({ semRede: false, usandoGuardado: null });
      } catch (err) {
        if (err instanceof ApiError && err.status === 0) break; // continua sem rede
        if (err instanceof ApiError && err.isAuthFailure) {
          resultado.sessaoVencida = true;
          break;
        }
        await atualizarFila({ ...item, tentativas: item.tentativas + 1, erro: (err as Error).message || 'Recusado pelo servidor.' });
        resultado.falhas++;
      }
    }
  } finally {
    await contarFila();
    mudarEstado({ sincronizando: false });
  }
  return resultado;
}

/** Executa leituras ignorando as que falharem (permissão, recurso desligado). */
async function cada<T>(lista: T[], fn: (item: T) => Promise<unknown>): Promise<void> {
  for (const item of lista) {
    try {
      await fn(item);
    } catch (err) {
      if (err instanceof ApiError && err.status === 0) throw err; // sem rede: para o download
    }
  }
}

async function tentar<T>(fn: () => Promise<T>): Promise<T | null> {
  try {
    return await fn();
  } catch (err) {
    if (err instanceof ApiError && err.status === 0) throw err;
    return null;
  }
}

function hojeIso(): string {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

async function baixarTecnico(baseUrl: string, token: string, passo: (t: string) => void): Promise<void> {
  passo('Painel e minhas OS');
  await tentar(() => tec.dashboard(baseUrl, token));
  const ids = new Set<string>();
  for (const aba of ['hoje', 'proximos', 'atrasadas', 'concluidas', 'todas']) {
    const lista = await tentar(() => tec.listOs(baseUrl, token, aba));
    for (const os of lista?.ordens ?? []) ids.add(String(os.idOs));
  }
  const naoRealizadas = await tentar(() => tec.listNaoRealizadas(baseUrl, token));
  for (const n of naoRealizadas ?? []) ids.add(String(n.os_id));
  await tentar(() => tec.motivos(baseUrl, token));

  const listaIds = [...ids].slice(0, MAX_OS);
  let feitas = 0;
  await cada(listaIds, async (id) => {
    passo(`OS ${++feitas}/${listaIds.length}`);
    await tec.osDetalhe(baseUrl, token, id);
  });

  passo('Projetos');
  const projetos = (await tentar(() => tec.listProjetos(baseUrl, token))) ?? [];
  const pontos: Array<{ id: number | string; obra: number | string }> = [];
  await cada(projetos, async (p) => {
    const detalhe = await tec.getProjeto(baseUrl, token, p.idObra);
    for (const ponto of detalhe.pontos ?? []) pontos.push({ id: ponto.idPonto, obra: p.idObra });
  });
  const fila = await tentar(() => tec.fila(baseUrl, token));
  for (const ponto of fila?.pontos ?? []) {
    if (ponto.obra_id) pontos.push({ id: ponto.idPonto, obra: ponto.obra_id });
  }
  const unicos = [...new Map(pontos.map((p) => [`${p.obra}:${p.id}`, p])).values()].slice(0, MAX_PONTOS);
  feitas = 0;
  await cada(unicos, async (p) => {
    passo(`Pontos dos projetos ${++feitas}/${unicos.length}`);
    await tec.getPonto(baseUrl, token, p.id, p.obra);
  });

  passo('Clientes, serviços e produtos');
  await tentar(() => tec.listaCompletaBusca(baseUrl, token, 'clientes'));
  await tentar(() => tec.listaCompletaBusca(baseUrl, token, 'servicos'));
  await tentar(() => tec.listaCompletaBusca(baseUrl, token, 'produtos'));
  await tentar(() => tec.perfil(baseUrl, token));
  await tentar(() => tec.relatorio(baseUrl, token, 'diario', hojeIso()));
}

async function baixarColaborador(baseUrl: string, token: string, passo: (t: string) => void): Promise<void> {
  passo('Ponto e RH');
  await tentar(() => col.dashboard(baseUrl, token));
  await tentar(() => col.pontoEstado(baseUrl, token));
  await tentar(() => col.batidas(baseUrl, token));
  await tentar(() => col.espelho(baseUrl, token, competenciaAtual()));
  await tentar(() => col.holerite(baseUrl, token, competenciaAtual()));
  await tentar(() => col.ocorrencias(baseUrl, token));
  await tentar(() => col.ausencias(baseUrl, token));
  await tentar(() => col.meusDados(baseUrl, token));
}

async function baixarAdmin(baseUrl: string, token: string, passo: (t: string) => void): Promise<void> {
  passo('Painel, OS e clientes');
  await tentar(() => api.dashboard(baseUrl, token));
  await tentar(() => api.emitente(baseUrl, token));
}

/**
 * Baixa os dados da área para usar sem internet. Devolve false se a rede caiu
 * no meio (o que já veio continua guardado).
 */
export async function baixarParaOffline(baseUrl: string, token: string, areas: Area[]): Promise<boolean> {
  if (estadoOffline().baixando || !token) return false;
  const passo = (t: string) => mudarEstado({ progresso: t });
  mudarEstado({ baixando: true, progresso: 'Preparando…' });
  try {
    await tentar(() => api.conta(baseUrl, token));
    if (areas.includes('tecnico')) await baixarTecnico(baseUrl, token, passo);
    if (areas.includes('colaborador')) await baixarColaborador(baseUrl, token, passo);
    if (areas.includes('admin')) await baixarAdmin(baseUrl, token, passo);
    registrarDownload();
    return true;
  } catch {
    return false;
  } finally {
    mudarEstado({ baixando: false, progresso: null });
  }
}
