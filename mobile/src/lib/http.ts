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
function mensagemDeErro(payload: unknown, status: number): string {
  if (payload && typeof payload === 'object' && 'message' in payload) {
    const texto = String((payload as { message: unknown }).message);
    if (texto.trim()) return stripTags(texto);
  }
  return `Falha na requisição (HTTP ${status}).`;
}

export async function request<T>(baseUrl: string, path: string, options: RequestOptions = {}): Promise<T> {
  const response = await rawRequest(baseUrl, path, options);
  const text = await response.text();
  let payload: unknown = null;
  if (text) {
    try {
      payload = JSON.parse(text);
    } catch {
      payload = null;
    }
  }

  if (!response.ok) {
    throw new ApiError(mensagemDeErro(payload, response.status), response.status);
  }
  if (payload === null) {
    throw new ApiError('Resposta inválida do servidor (não é JSON).', response.status);
  }

  return payload as T;
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
    let payload: unknown = null;
    try {
      payload = text ? JSON.parse(text) : null;
    } catch {
      payload = null;
    }
    throw new ApiError(mensagemDeErro(payload, response.status), response.status);
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
