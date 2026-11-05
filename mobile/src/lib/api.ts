/** Endpoints da API v1 do MAPOS, um por rota real (application/config/routes_api.php). */

import { ApiError, request } from './http';
import type {
  ApiEnvelope,
  Cliente,
  ContaResult,
  DashboardResult,
  Emitente,
  LoginResult,
  Os,
  Produto,
  Servico,
  OsAnotacao,
  OsStatus,
  Usuario,
} from './types';

export interface Paginacao {
  page?: number;
  perPage?: number;
}

function query(params: Record<string, string | number | undefined>): string {
  const parts = Object.entries(params)
    .filter(([, v]) => v !== undefined && v !== null && v !== '')
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(String(v))}`);
  return parts.length ? `?${parts.join('&')}` : '';
}

/** Desembrulha {status, message, result} e transforma status=false em erro. */
async function unwrap<T>(baseUrl: string, path: string, init?: Parameters<typeof request>[2]): Promise<T> {
  const payload = await request<ApiEnvelope<T> | null>(baseUrl, path, init);
  if (!payload || typeof payload !== 'object' || !('status' in payload)) {
    throw new ApiError('Resposta inesperada do servidor.', 0);
  }
  if (!payload.status) {
    throw new ApiError(payload.message || 'Operação não concluída.', 200);
  }
  return payload.result;
}

export interface CreateClientPayload {
  nomeCliente: string;
  documento?: string;
  telefone?: string;
  celular?: string;
  email?: string;
  rua?: string;
  numero?: string;
  complemento?: string;
  bairro?: string;
  cidade?: string;
  estado?: string;
  cep?: string;
}

export interface CreateOsPayload {
  clientes_id: number | string;
  usuarios_id: number | string;
  status: OsStatus | string;
  dataInicial: string;
  dataFinal?: string;
  descricaoProduto?: string;
  defeito?: string;
  observacoes?: string;
  laudoTecnico?: string;
  garantia?: string;
}

export interface UpdateOsPayload extends CreateOsPayload {
  garantias_id?: number | string | null;
  termoGarantia?: string;
}

export interface LoginPayload {
  email: string;
  password: string;
  codigo?: string;
}

/** /api/v1/login — POST { email, password, codigo? } */
export async function login(baseUrl: string, payload: LoginPayload): Promise<LoginResult> {
  const body: Record<string, string> = { email: payload.email, password: payload.password };
  if (payload.codigo) body.codigo = payload.codigo;
  return unwrap<LoginResult>(baseUrl, '/api/v1/login', { method: 'POST', body });
}

/** /api/v1/reGenToken — renova o token com um token válido. */
export async function regenToken(baseUrl: string, token: string): Promise<LoginResult> {
  return unwrap<LoginResult>(baseUrl, '/api/v1/reGenToken', { token });
}

/** /api/v1/conta */
export async function conta(baseUrl: string, token: string): Promise<ContaResult> {
  return unwrap<ContaResult>(baseUrl, '/api/v1/conta', { token });
}

/** /api/v1 — dashboard (contadores + listas de OS e estoque baixo). */
export async function dashboard(baseUrl: string, token: string): Promise<DashboardResult> {
  return unwrap<DashboardResult>(baseUrl, '/api/v1', { token });
}

/** /api/v1/emitente — branding do sistema. */
export async function emitente(baseUrl: string, token: string): Promise<{ appName: string; emitente: Emitente | false }> {
  return unwrap<{ appName: string; emitente: Emitente | false }>(baseUrl, '/api/v1/emitente', { token });
}

/** /api/v1/os — listagem com search/status/from/to em dd/mm/aaaa. */
export async function listOs(
  baseUrl: string,
  token: string,
  params: Paginacao & { search?: string; status?: string; from?: string; to?: string } = {},
): Promise<Os[]> {
  const result = await unwrap<Os[] | null>(
    baseUrl,
    `/api/v1/os${query({ ...params })}`,
    { token },
  );
  return result ?? [];
}

/** /api/v1/os/{id} — detalhe com produtos, serviços, anexos e anotações. */
export async function getOs(baseUrl: string, token: string, id: number | string): Promise<Os> {
  return unwrap<Os>(baseUrl, `/api/v1/os/${encodeURIComponent(String(id))}`, { token });
}

/** /api/v1/os — POST. Datas vão em dd/mm/aaaa (o controller converte). */
export async function createOs(baseUrl: string, token: string, payload: CreateOsPayload): Promise<Os> {
  return unwrap<Os>(baseUrl, '/api/v1/os', { method: 'POST', token, body: payload });
}

/** /api/v1/os/{id} — PUT. Exige dataInicial, dataFinal, status, clientes_id e usuarios_id. */
export async function updateOs(
  baseUrl: string,
  token: string,
  id: number | string,
  payload: UpdateOsPayload,
): Promise<Os> {
  return unwrap<Os>(baseUrl, `/api/v1/os/${encodeURIComponent(String(id))}`, {
    method: 'PUT',
    token,
    body: payload,
  });
}

export async function deleteOs(baseUrl: string, token: string, id: number | string): Promise<void> {
  await unwrap<unknown>(baseUrl, `/api/v1/os/${encodeURIComponent(String(id))}`, {
    method: 'DELETE',
    token,
  });
}

/** /api/v1/os/{id}/anotacoes — POST { anotacao }. */
export async function addAnotacao(
  baseUrl: string,
  token: string,
  osId: number | string,
  anotacao: string,
): Promise<{ idAnotacoes: number | string; anotacao: string }> {
  return unwrap(baseUrl, `/api/v1/os/${encodeURIComponent(String(osId))}/anotacoes`, {
    method: 'POST',
    token,
    body: { anotacao },
  });
}

export async function deleteAnotacao(
  baseUrl: string,
  token: string,
  osId: number | string,
  anotacaoId: number | string,
): Promise<void> {
  await unwrap<unknown>(
    baseUrl,
    `/api/v1/os/${encodeURIComponent(String(osId))}/anotacoes/${encodeURIComponent(String(anotacaoId))}`,
    { method: 'DELETE', token },
  );
}

/** /api/v1/clientes */
export async function listClientes(
  baseUrl: string,
  token: string,
  params: Paginacao & { search?: string } = {},
): Promise<Cliente[]> {
  const result = await unwrap<Cliente[] | null>(baseUrl, `/api/v1/clientes${query({ ...params })}`, { token });
  return result ?? [];
}

export async function getCliente(baseUrl: string, token: string, id: number | string): Promise<Cliente> {
  return unwrap<Cliente>(baseUrl, `/api/v1/clientes/${encodeURIComponent(String(id))}`, { token });
}

export async function createCliente(
  baseUrl: string,
  token: string,
  payload: CreateClientPayload,
): Promise<Cliente> {
  return unwrap<Cliente>(baseUrl, '/api/v1/clientes', { method: 'POST', token, body: payload });
}

export async function updateCliente(
  baseUrl: string,
  token: string,
  id: number | string,
  payload: Partial<CreateClientPayload>,
): Promise<Cliente> {
  return unwrap<Cliente>(baseUrl, `/api/v1/clientes/${encodeURIComponent(String(id))}`, {
    method: 'PUT',
    token,
    body: payload,
  });
}

/** /api/v1/usuarios — exige permissão cUsuario; usado para escolher o técnico. */
export async function listUsuarios(
  baseUrl: string,
  token: string,
  params: Paginacao & { search?: string } = {},
): Promise<Usuario[]> {
  const result = await unwrap<Usuario[] | null>(baseUrl, `/api/v1/usuarios${query({ ...params })}`, { token });
  return result ?? [];
}

/** /api/v1/produtos e /api/v1/servicos */
export async function listProdutos(
  baseUrl: string,
  token: string,
  params: Paginacao & { search?: string } = {},
): Promise<Produto[]> {
  const result = await unwrap<Produto[] | null>(baseUrl, `/api/v1/produtos${query({ ...params })}`, { token });
  return result ?? [];
}

export async function listServicos(
  baseUrl: string,
  token: string,
  params: Paginacao & { search?: string } = {},
): Promise<Servico[]> {
  const result = await unwrap<Servico[] | null>(baseUrl, `/api/v1/servicos${query({ ...params })}`, { token });
  return result ?? [];
}

export type { OsAnotacao };
