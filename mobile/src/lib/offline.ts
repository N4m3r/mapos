/**
 * Modo offline do app: leituras guardadas no aparelho e fila de envios.
 *
 * - Toda leitura (GET) que dá certo é guardada. Sem internet, a tela recebe a
 *   última cópia guardada e o app avisa de quando ela é.
 * - Envio feito sem internet entra na fila (regras em offline-regras.ts) e é
 *   entregue sozinho quando a rede volta (offline-sync.ts).
 * - A cópia e a fila são de um usuário: outro login no mesmo aparelho apaga
 *   as leituras guardadas, e cada envio só sai com o token de quem o fez.
 */

import * as db from './offline-db';
import { caminhoLimpo, corpoComHorario, regraPara } from './offline-regras';

export interface EstadoOffline {
  /** A última tentativa de falar com o servidor falhou por falta de rede. */
  semRede: boolean;
  /** A tela atual está mostrando dados guardados (e de quando). */
  usandoGuardado: number | null;
  pendentes: number;
  falhas: number;
  sincronizando: boolean;
  baixando: boolean;
  ultimoDownload: number | null;
  progresso: string | null;
}

const CHAVE_DOWNLOAD = 'mapos.offline.download';
const CHAVE_DONO = 'mapos.offline.dono';

function lerNumero(chave: string): number | null {
  try {
    const v = Number(window.localStorage.getItem(chave));
    return Number.isFinite(v) && v > 0 ? v : null;
  } catch {
    return null;
  }
}

let estado: EstadoOffline = {
  semRede: typeof navigator !== 'undefined' ? navigator.onLine === false : false,
  usandoGuardado: null,
  pendentes: 0,
  falhas: 0,
  sincronizando: false,
  baixando: false,
  ultimoDownload: lerNumero(CHAVE_DOWNLOAD),
  progresso: null,
};

const ouvintes = new Set<(e: EstadoOffline) => void>();

export function estadoOffline(): EstadoOffline {
  return estado;
}

export function mudarEstado(parcial: Partial<EstadoOffline>): void {
  estado = { ...estado, ...parcial };
  ouvintes.forEach((fn) => fn(estado));
}

export function ouvirOffline(fn: (e: EstadoOffline) => void): () => void {
  ouvintes.add(fn);
  return () => ouvintes.delete(fn);
}

export function registrarDownload(quando = Date.now()): void {
  try {
    window.localStorage.setItem(CHAVE_DOWNLOAD, String(quando));
  } catch {
    /* ignora */
  }
  mudarEstado({ ultimoDownload: quando });
}

// ------------------------------------------------------------------
// Usuário dono dos dados guardados
// ------------------------------------------------------------------

let usuarioAtual = '';

/** Chamado no login/abertura: outro usuário no aparelho apaga as leituras guardadas. */
export async function definirUsuarioOffline(baseUrl: string, idUsuario: string | number | null | undefined): Promise<void> {
  usuarioAtual = idUsuario ? `${baseUrl}#${idUsuario}` : '';
  if (!usuarioAtual) return;
  let dono: string | null = null;
  try {
    dono = window.localStorage.getItem(CHAVE_DONO);
  } catch {
    /* ignora */
  }
  if (dono && dono !== usuarioAtual) {
    await db.limparCache();
    try {
      window.localStorage.removeItem(CHAVE_DOWNLOAD);
    } catch {
      /* ignora */
    }
    mudarEstado({ ultimoDownload: null });
  }
  try {
    window.localStorage.setItem(CHAVE_DONO, usuarioAtual);
  } catch {
    /* ignora */
  }
  await contarFila();
}

export function usuarioOffline(): string {
  return usuarioAtual;
}

// ------------------------------------------------------------------
// Leituras guardadas
// ------------------------------------------------------------------

export function chaveCache(baseUrl: string, caminho: string): string {
  const limpo = caminho.replace(/^\/index\.php/, '');
  return `${baseUrl.replace(/\/+$/, '')}${limpo}`;
}

export async function guardarLeitura(baseUrl: string, caminho: string, dados: unknown): Promise<void> {
  if (!usuarioAtual) return;
  await db.gravarCache({ chave: chaveCache(baseUrl, caminho), dados, salvoEm: Date.now() });
}

export async function leituraGuardada(baseUrl: string, caminho: string): Promise<db.ItemCache | null> {
  if (!usuarioAtual) return null;
  return db.lerCache(chaveCache(baseUrl, caminho));
}

export async function guardarArquivo(baseUrl: string, caminho: string, blob: Blob): Promise<void> {
  if (!usuarioAtual || blob.size > 8 * 1024 * 1024) return;
  await db.gravarCache({ chave: `arquivo:${chaveCache(baseUrl, caminho)}`, dados: null, blob, salvoEm: Date.now() });
}

export async function arquivoGuardado(baseUrl: string, caminho: string): Promise<Blob | null> {
  if (!usuarioAtual) return null;
  return (await db.lerCache(`arquivo:${chaveCache(baseUrl, caminho)}`))?.blob ?? null;
}

// ------------------------------------------------------------------
// Fila de envios
// ------------------------------------------------------------------

export async function contarFila(): Promise<void> {
  const itens = (await db.listarFila()).filter((i) => i.usuario === usuarioAtual);
  mudarEstado({ pendentes: itens.filter((i) => !i.erro).length, falhas: itens.filter((i) => i.erro).length });
}

export async function itensDaFila(): Promise<db.ItemFila[]> {
  return (await db.listarFila()).filter((i) => i.usuario === usuarioAtual);
}

export async function descartarDaFila(id: number): Promise<void> {
  await db.removerFila(id);
  await contarFila();
}

/**
 * Tenta tratar um envio que falhou por falta de rede.
 * Devolve o envelope provisório, ou null quando o envio precisa de internet.
 */
export async function tratarEnvioSemRede(
  baseUrl: string,
  caminho: string,
  metodo: 'POST' | 'PUT' | 'DELETE',
  corpo: unknown,
): Promise<Record<string, unknown> | null> {
  const regra = regraPara(metodo, caminho);
  if (!regra || !usuarioAtual) return null;

  if (regra.tipo === 'descartar') {
    return { status: true, message: 'Sem internet.', result: { offline: true }, offline: true };
  }

  const dados = corpoComHorario(regra, corpo);
  await db.adicionarFila({
    usuario: usuarioAtual,
    baseUrl,
    caminho: caminhoLimpo(caminho) + (caminho.includes('?') ? caminho.slice(caminho.indexOf('?')) : ''),
    metodo,
    corpo: dados,
    rotulo: regra.rotulo,
    criadoEm: Date.now(),
    tentativas: 0,
    erro: null,
  });

  // Ajusta as leituras guardadas para a tela já mostrar o efeito do envio.
  for (const ajuste of regra.ajustes?.(dados) ?? []) {
    const guardado = await leituraGuardada(baseUrl, ajuste.caminho);
    if (guardado?.dados && typeof guardado.dados === 'object') {
      await db.gravarCache({ ...guardado, dados: ajuste.aplicar(guardado.dados as Record<string, unknown>) });
    }
  }

  const resultado = regra.resultado
    ? await regra.resultado(dados, async (c) => (await leituraGuardada(baseUrl, c))?.dados ?? null)
    : { offline: true };
  await contarFila();
  mudarEstado({ semRede: true });

  return {
    status: true,
    message: `Sem internet: ${regra.rotulo.toLowerCase()} guardado no aparelho. Será enviado sozinho quando a internet voltar.`,
    result: resultado,
    offline: true,
  };
}
