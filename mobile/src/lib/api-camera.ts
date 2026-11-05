/** Configuração de câmeras/NVR do projeto (application/controllers/api/v1/TecnicoCameraController.php). */

import { ApiError, request } from './http';
import type { ApiEnvelope } from './types';
import type { CameraPlano, CameraPonto, EnvioCamera, ResultadoLote } from './types-camera';

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

function base(obraId: number | string): string {
  return `/api/v1/tecnico/projetos/${encodeURIComponent(String(obraId))}/camera-config`;
}

/** GET — câmeras e NVR previstos no projeto, com o que já foi configurado. */
export function plano(baseUrl: string, token: string, obraId: number | string): Promise<CameraPlano> {
  return unwrap<CameraPlano>(baseUrl, base(obraId), { token });
}

/** POST — grava a configuração de um ponto que já existe no projeto. */
export function salvarPonto(
  baseUrl: string,
  token: string,
  obraId: number | string,
  pontoId: number | string,
  dados: EnvioCamera,
): Promise<{ ponto: CameraPonto }> {
  return unwrap(baseUrl, `${base(obraId)}/pontos/${encodeURIComponent(String(pontoId))}`, {
    method: 'POST',
    token,
    body: dados,
  });
}

/** POST — cria um ponto novo para uma câmera descoberta que o projeto não previu. */
export function criarPonto(
  baseUrl: string,
  token: string,
  obraId: number | string,
  dados: EnvioCamera & { identificacao?: string; tipo?: string },
): Promise<{ idPonto: number; ponto: CameraPonto }> {
  return unwrap(baseUrl, `${base(obraId)}/nova`, { method: 'POST', token, body: dados });
}

/** POST — manda de uma vez o que foi configurado em campo. */
export function salvarLote(
  baseUrl: string,
  token: string,
  obraId: number | string,
  cameras: EnvioCamera[],
): Promise<ResultadoLote> {
  return unwrap<ResultadoLote>(baseUrl, `${base(obraId)}/lote`, {
    method: 'POST',
    token,
    body: { cameras },
  });
}
