/** Endpoints da Área do Colaborador / RH (application/config/routes_api.php). */

import { ApiError, request, requestBlob } from './http';
import type { ApiEnvelope } from './types';
import type {
  Ausencia,
  ColaboradorDashboard,
  EspelhoResult,
  Holerite,
  MeusDados,
  Ocorrencia,
  PontoBatida,
  PontoEstado,
  PontoRegistro,
} from './types-tecnico';

function query(params: Record<string, string | number | undefined>): string {
  const parts = Object.entries(params)
    .filter(([, v]) => v !== undefined && v !== null && v !== '')
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(String(v))}`);
  return parts.length ? `?${parts.join('&')}` : '';
}

async function unwrap<T>(
  baseUrl: string,
  path: string,
  init?: Parameters<typeof request>[2],
): Promise<T> {
  const payload = await request<ApiEnvelope<T> | null>(baseUrl, path, init);
  if (!payload || typeof payload !== 'object' || !('status' in payload)) {
    throw new ApiError('Resposta inesperada do servidor.', 0);
  }
  if (!payload.status) {
    throw new ApiError(payload.message || 'Operação não concluída.', 200);
  }
  return payload.result;
}

/** GET /api/v1/colaborador — painel: batidas de hoje, totais e pendências. */
export function dashboard(baseUrl: string, token: string): Promise<ColaboradorDashboard> {
  return unwrap<ColaboradorDashboard>(baseUrl, '/api/v1/colaborador', { token });
}

/** GET /api/v1/colaborador/espelho?competencia=AAAA-MM */
export function espelho(baseUrl: string, token: string, competencia: string): Promise<EspelhoResult> {
  return unwrap<EspelhoResult>(baseUrl, `/api/v1/colaborador/espelho${query({ competencia })}`, { token });
}

/** GET /api/v1/colaborador/batidas?data=AAAA-MM-DD */
export function batidas(baseUrl: string, token: string, data?: string): Promise<{ data: string; batidas: PontoBatida[] }> {
  return unwrap(baseUrl, `/api/v1/colaborador/batidas${query({ data })}`, { token });
}

/** GET /api/v1/colaborador/holerite?competencia=AAAA-MM */
export function holerite(baseUrl: string, token: string, competencia: string): Promise<Holerite> {
  return unwrap<Holerite>(baseUrl, `/api/v1/colaborador/holerite${query({ competencia })}`, { token });
}

/** GET /api/v1/colaborador/holerite/arquivo — PDF liberado pelo RH. */
export function holeriteArquivo(baseUrl: string, token: string, competencia: string): Promise<Blob> {
  return requestBlob(baseUrl, `/api/v1/colaborador/holerite/arquivo${query({ competencia })}`, { token });
}

/** GET /api/v1/colaborador/dados */
export function meusDados(baseUrl: string, token: string): Promise<MeusDados> {
  return unwrap<MeusDados>(baseUrl, '/api/v1/colaborador/dados', { token });
}

export interface DadosPayload {
  email?: string;
  celular?: string;
  pix_tipo?: string;
  pix_chave?: string;
}

/** PUT /api/v1/colaborador/dados */
export function salvarDados(baseUrl: string, token: string, payload: DadosPayload): Promise<MeusDados> {
  return unwrap<MeusDados>(baseUrl, '/api/v1/colaborador/dados', { method: 'PUT', token, body: payload });
}

// ------------------------------------------------------------------
// Solicitações
// ------------------------------------------------------------------

/** GET /api/v1/colaborador/ocorrencias */
export function ocorrencias(baseUrl: string, token: string): Promise<Ocorrencia[]> {
  return unwrap<Ocorrencia[]>(baseUrl, '/api/v1/colaborador/ocorrencias', { token });
}

export interface OcorrenciaPayload {
  tipo: 'correcao_ponto' | 'justificativa_falta' | 'abono';
  data_referencia?: string;
  registro_id?: number;
  justificar_tipo?: string;
  correcao_tipo?: string;
  correcao_data_hora?: string;
  descricao: string;
  anexo?: string;
  anexo_nome?: string;
}

/** POST /api/v1/colaborador/ocorrencias */
export function solicitarOcorrencia(baseUrl: string, token: string, payload: OcorrenciaPayload): Promise<Ocorrencia> {
  return unwrap<Ocorrencia>(baseUrl, '/api/v1/colaborador/ocorrencias', { method: 'POST', token, body: payload });
}

/** GET /api/v1/colaborador/ausencias */
export function ausencias(baseUrl: string, token: string): Promise<Ausencia[]> {
  return unwrap<Ausencia[]>(baseUrl, '/api/v1/colaborador/ausencias', { token });
}

export interface AusenciaPayload {
  tipo: 'ferias' | 'folga' | 'atestado' | 'licenca';
  data_inicio: string;
  data_fim?: string;
  motivo?: string;
  anexo?: string;
  anexo_nome?: string;
}

/** POST /api/v1/colaborador/ausencias */
export function solicitarAusencia(baseUrl: string, token: string, payload: AusenciaPayload): Promise<Ausencia> {
  return unwrap<Ausencia>(baseUrl, '/api/v1/colaborador/ausencias', { method: 'POST', token, body: payload });
}

// ------------------------------------------------------------------
// Ponto
// ------------------------------------------------------------------

/** GET /api/v1/ponto — estado atual, unidades, OS/obras e regras de geofence/facial. */
export function pontoEstado(baseUrl: string, token: string): Promise<PontoEstado> {
  return unwrap<PontoEstado>(baseUrl, '/api/v1/ponto', { token });
}

export interface RegistrarPontoPayload {
  tipo: string;
  foto?: string;
  latitude?: number;
  longitude?: number;
  unidade_id?: number;
  os_id?: number | string;
  obra_id?: number | string;
  face_score?: number;
}

/** POST /api/v1/ponto/registrar */
export function registrarPonto(baseUrl: string, token: string, payload: RegistrarPontoPayload): Promise<PontoRegistro> {
  return unwrap<PontoRegistro>(baseUrl, '/api/v1/ponto/registrar', { method: 'POST', token, body: payload });
}
