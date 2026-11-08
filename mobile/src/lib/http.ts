/** Cliente HTTP do app: Bearer JWT, timeout, erros tipados e retry de sessão. */

export class ApiError extends Error {
  readonly status: number;

  constructor(message: string, status: number) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
  }

  /**
   * Sessão inválida/expirada — o app deve voltar para o login.
   *
   * Vários controllers devolvem 401 também para falta de permissão
   * ("Você não está autorizado a Visualizar Clientes"), com a sessão ainda
   * válida. Por isso o status sozinho não basta: só a mensagem de token/login
   * derruba a sessão. As áreas do técnico e do colaborador usam 403 com a mesma
   * mensagem, e ficam cobertas pelo mesmo teste.
   */
  get isAuthFailure(): boolean {
    if (this.status !== 401 && this.status !== 403) return false;
    return /fa[çc]a (o )?login|login novamente|token (inv[áa]lido|expirado)|n[ãa]o autenticado/i.test(
      this.message,
    );
  }
}

export interface RequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
  body?: unknown;
  token?: string | null;
  timeoutMs?: number;
  signal?: AbortSignal;
}

const DEFAULT_TIMEOUT = 20000;

/**
 * Instalações sem mod_rewrite (caso comum em hospedagem compartilhada) respondem
 * 404 em /api/v1 e só aceitam /index.php/api/v1. Guardamos por URL qual forma
 * funcionou para não pagar o 404 em toda requisição.
 */
const needsIndexPhp = new Map<string, boolean>();

/** Mescla sinais do chamador com o timeout interno (sem depender de AbortSignal.any). */
function withTimeout(signal: AbortSignal | undefined, timeoutMs: number) {
  const controller = new AbortController();
  const timer = window.setTimeout(() => controller.abort(), timeoutMs);
  if (signal) {
    if (signal.aborted) controller.abort();
    else signal.addEventListener('abort', () => controller.abort(), { once: true });
  }
  return { signal: controller.signal, done: () => window.clearTimeout(timer) };
}

/**
 * Normaliza o caminho do endpoint.
 *
 * A API devolve caminhos relativos à API v1 (fotos, PDF do holerite), como
 * "tecnico/foto/12". Sem barra inicial, a concatenação com a base virava
 * "https://host/mapostecnico/foto/12" e a imagem nunca carregava.
 */
function normalizePath(path: string): string {
  const p = path.trim();
  if (!p) return p;
  if (/^https?:\/\//i.test(p)) return p;
  if (p.startsWith('/')) return p;
  return `/api/v1/${p}`;
}

/** Executa a requisição, descobrindo uma vez por servidor se precisa de /index.php. */
async function rawRequest(baseUrl: string, path: string, options: RequestOptions): Promise<Response> {
  const { method = 'GET', body, token, timeoutMs = DEFAULT_TIMEOUT, signal } = options;
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  if (token) headers.Authorization = `Bearer ${token}`;

  const attempt = async (useIndexPhp: boolean): Promise<Response> => {
    const { signal: merged, done } = withTimeout(signal, timeoutMs);
    try {
      return await fetch(`${baseUrl}${useIndexPhp ? '/index.php' : ''}${normalizePath(path)}`, {
        method,
        headers,
        body: body === undefined ? undefined : JSON.stringify(body),
        signal: merged,
        mode: 'cors',
        credentials: 'omit',
        cache: 'no-store',
      });
    } catch (error) {
      if ((error as Error).name === 'AbortError') {
        throw new ApiError('Tempo de resposta esgotado. Verifique sua conexão.', 0);
      }
      throw new ApiError(
        'Não foi possível falar com o servidor. Verifique a URL e se a API está liberada para este aplicativo.',
        0,
      );
    } finally {
      done();
    }
  };

  let useIndexPhp = needsIndexPhp.get(baseUrl) ?? false;
  let response = await attempt(useIndexPhp);

  // 404 na forma "bonita" = servidor sem rewrite: tenta pelo index.php e memoriza.
  if (response.status === 404 && !useIndexPhp) {
    const retry = await attempt(true);
    if (retry.ok || retry.status !== 404) {
      needsIndexPhp.set(baseUrl, true);
      useIndexPhp = true;
      response = retry;
    }
  }

  return response;
}

/** Mensagem de erro do envelope, já sem HTML de validation_errors(). */
function mensagemDeErro(payload: unknown, status: number, bruto = ''): string {
  if (payload && typeof payload === 'object' && 'message' in payload) {
    const texto = String((payload as { message: unknown }).message);
    if (texto.trim()) return stripTags(texto);
  }
  const detalhe = status >= 500 ? detalheDoErro(bruto) : '';
  return detalhe ? `Falha na requisição (HTTP ${status}): ${detalhe}` : `Falha na requisição (HTTP ${status}).`;
}

/**
 * Erro fatal/banco do CodeIgniter vem em HTML. Extrai a linha útil ("Table
 * ... doesn't exist", "Class ... not found") para quem estiver testando saber
 * o que corrigir no servidor, em vez de um 500 sem explicação.
 */
function detalheDoErro(bruto: string): string {
  if (!bruto) return '';
  const semComentario = bruto.replace(/<!--([\s\S]*?)-->/g, ' $1 ');
  const texto = stripTags(
    semComentario
      .replace(/<(style|script)[\s\S]*?<\/\1>/gi, ' ')
      .replace(/<\/?(p|h\d|div|br)[^>]*>/gi, ' ')
      .replace(/&quot;/g, '"')
      .replace(/&#0?39;/g, "'"),
  );
  const achado =
    texto.match(/(Error Number:.*?)(?:Filename:|SELECT |INSERT |UPDATE |$)/i) ??
    texto.match(/(?:Message|Error):\s*(.*?)(?:Filename:| in \/|$)/i);
  const linha = (achado?.[1] ?? '').trim();
  return linha.length > 220 ? `${linha.slice(0, 220)}…` : linha;
}

export async function request<T>(baseUrl: string, path: string, options: RequestOptions = {}): Promise<T> {
  const response = await rawRequest(baseUrl, path, options);
  const text = await response.text();
  const payload = lerJson(text);

  if (!response.ok) {
    throw new ApiError(mensagemDeErro(payload, response.status, text), response.status);
  }
  if (payload === null) {
    const detalhe = detalheDoErro(text) || resumoTexto(text);
    throw new ApiError(
      detalhe ? `Resposta inválida do servidor (não é JSON): ${detalhe}` : 'Resposta inválida do servidor (não é JSON).',
      response.status,
    );
  }

  return payload as T;
}

/**
 * Lê o JSON da resposta. Servidor com display_errors ligado imprime avisos do
 * PHP (Warning, Deprecated) antes do JSON; a resposta continua válida depois
 * deles, então o envelope é recuperado em vez de virar "não é JSON".
 */
export function lerJson(text: string): unknown {
  if (!text) return null;
  try {
    return JSON.parse(text);
  } catch {
    /* tenta achar o envelope depois do aviso */
  }
  const inicio = text.search(/\{\s*"status"\s*:/);
  if (inicio < 0) return null;
  const fim = text.lastIndexOf('}');
  if (fim <= inicio) return null;
  try {
    const payload = JSON.parse(text.slice(inicio, fim + 1));
    console.warn('[api] aviso do servidor antes do JSON:', resumoTexto(text.slice(0, inicio)));
    return payload;
  } catch {
    return null;
  }
}

/** Primeiros caracteres úteis de uma resposta que não é JSON (sem HTML). */
function resumoTexto(text: string): string {
  const limpo = stripTags(text.replace(/<(style|script)[\s\S]*?<\/\1>/gi, ' '));
  return limpo.length > 200 ? `${limpo.slice(0, 200)}…` : limpo;
}

/**
 * Baixa um endpoint binário (foto, assinatura, PDF de holerite) com o token no
 * cabeçalho — <img src> não carrega Authorization, então a imagem vem por blob.
 */
export async function requestBlob(
  baseUrl: string,
  path: string,
  options: RequestOptions = {},
): Promise<Blob> {
  const response = await rawRequest(baseUrl, path, options);
  if (!response.ok) {
    const text = await response.text();
    throw new ApiError(mensagemDeErro(lerJson(text), response.status, text), response.status);
  }

  return response.blob();
}

/** Os controllers devolvem HTML em validation_errors(); limpamos para exibir texto. */
export function stripTags(value: string): string {
  return value
    .replace(/<br\s*\/?>/gi, ' ')
    .replace(/<\/?[^>]+>/g, '')
    .replace(/\s+/g, ' ')
    .trim();
}
