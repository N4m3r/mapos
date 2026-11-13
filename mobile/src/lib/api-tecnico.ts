/** Endpoints da Área do Técnico (application/config/routes_api.php, /api/v1/tecnico). */

import { ApiError, request } from './http';
import { leituraGuardada } from './offline';
import type { ApiEnvelope } from './types';
import type {
  AssinaturaSolicitante,
  ClienteBusca,
  FilaResult,
  MotivoNaoRealizado,
  NaoRealizada,
  PontoDetalhe,
  PontoProjeto,
  ProdutoBusca,
  ProjetoDetalhe,
  ProjetoResumo,
  RelatorioTecnico,
  ServicoBusca,
  TecnicoDashboard,
  TecnicoOsDetalhe,
  TecnicoOsLista,
  TecnicoPerfil,
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

/** GET /api/v1/tecnico — painel: próxima visita, agenda do dia e pendências. */
export function dashboard(baseUrl: string, token: string): Promise<TecnicoDashboard> {
  return unwrap<TecnicoDashboard>(baseUrl, '/api/v1/tecnico', { token });
}

/** GET /api/v1/tecnico/os?aba=hoje|proximos|atrasadas|concluidas|todas */
export function listOs(baseUrl: string, token: string, aba: string): Promise<TecnicoOsLista> {
  return unwrap<TecnicoOsLista>(baseUrl, `/api/v1/tecnico/os${query({ aba })}`, { token });
}

/** GET /api/v1/tecnico/os/{id} — atendimento completo da OS. */
export function osDetalhe(baseUrl: string, token: string, id: number | string): Promise<TecnicoOsDetalhe> {
  return unwrap<TecnicoOsDetalhe>(baseUrl, `/api/v1/tecnico/os/${encodeURIComponent(String(id))}`, { token });
}

// ------------------------------------------------------------------
// Atendimento
// ------------------------------------------------------------------

export interface CheckinPayload {
  os_id: number | string;
  observacao?: string;
  assinatura?: string;
  latitude?: number;
  longitude?: number;
}

export function checkin(baseUrl: string, token: string, payload: CheckinPayload) {
  return unwrap<{ checkin_id: number; data_entrada: string; status: string }>(
    baseUrl,
    '/api/v1/tecnico/checkin',
    { method: 'POST', token, body: payload },
  );
}

export interface CheckoutPayload {
  os_id: number | string;
  assinatura: string;
  assinatura_cliente?: string;
  nome_cliente?: string;
  documento_cliente?: string;
  observacao?: string;
  fotos?: string[];
  latitude?: number;
  longitude?: number;
}

export function checkout(baseUrl: string, token: string, payload: CheckoutPayload) {
  return unwrap<{ os_id: number; status: string; data_saida: string }>(
    baseUrl,
    '/api/v1/tecnico/checkout',
    { method: 'POST', token, body: payload },
  );
}

export function enviarFoto(
  baseUrl: string,
  token: string,
  payload: { os_id: number | string; foto: string; etapa?: string; descricao?: string },
): Promise<{ id: number; etapa: string; url: string }> {
  return unwrap(baseUrl, '/api/v1/tecnico/fotos', { method: 'POST', token, body: payload });
}

export function addObservacao(
  baseUrl: string,
  token: string,
  payload: { os_id: number | string; texto: string },
): Promise<{ id: number; texto: string; hora: string; autor: string }> {
  return unwrap(baseUrl, '/api/v1/tecnico/observacoes', { method: 'POST', token, body: payload });
}

// ------------------------------------------------------------------
// Serviço não realizado
// ------------------------------------------------------------------

export function motivos(baseUrl: string, token: string): Promise<MotivoNaoRealizado[]> {
  return unwrap<MotivoNaoRealizado[]>(baseUrl, '/api/v1/tecnico/motivos', { token });
}

export function listNaoRealizadas(baseUrl: string, token: string): Promise<NaoRealizada[]> {
  return unwrap<NaoRealizada[]>(baseUrl, '/api/v1/tecnico/nao-realizadas', { token });
}

export function marcarNaoRealizado(
  baseUrl: string,
  token: string,
  payload: { os_id: number | string; motivo_id?: number; observacao?: string },
) {
  return unwrap<{ id: number; os_id: number; status: string }>(
    baseUrl,
    '/api/v1/tecnico/nao-realizado',
    { method: 'POST', token, body: payload },
  );
}

export function reagendar(baseUrl: string, token: string, ocorrenciaId: number, novaData: string) {
  return unwrap<{ ocorrencia_id: number; os_id: number; nova_data: string }>(
    baseUrl,
    '/api/v1/tecnico/nao-realizadas/reagendar',
    { method: 'POST', token, body: { ocorrencia_id: ocorrenciaId, nova_data: novaData } },
  );
}

export function reabrir(baseUrl: string, token: string, ocorrenciaId: number) {
  return unwrap<{ ocorrencia_id: number; os_id: number }>(
    baseUrl,
    '/api/v1/tecnico/nao-realizadas/reabrir',
    { method: 'POST', token, body: { ocorrencia_id: ocorrenciaId } },
  );
}

// ------------------------------------------------------------------
// Atividade não programada (cadastros sem preço)
// ------------------------------------------------------------------

type Busca = 'clientes' | 'servicos' | 'produtos';

const LIMITE_LISTA = 3000;
const listaPath = (tipo: Busca) => `/api/v1/tecnico/${tipo}${query({ limite: LIMITE_LISTA })}`;

/** Lista inteira de clientes/serviços/produtos, baixada para buscar sem internet. */
export function listaCompletaBusca(baseUrl: string, token: string, tipo: Busca): Promise<unknown[]> {
  return unwrap<unknown[]>(baseUrl, listaPath(tipo), { token, timeoutMs: 60000 });
}

function semAcento(texto: unknown): string {
  return String(texto ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();
}

/**
 * Busca no servidor; sem internet, filtra a lista completa baixada no aparelho
 * (cada palavra digitada precisa aparecer em algum dos campos).
 */
async function buscar<T>(baseUrl: string, token: string, tipo: Busca, q: string, campos: Array<keyof T>): Promise<T[]> {
  try {
    return await unwrap<T[]>(baseUrl, `/api/v1/tecnico/${tipo}${query({ q })}`, { token });
  } catch (err) {
    if (!(err instanceof ApiError) || err.status !== 0) throw err;
    const guardada = (await leituraGuardada(baseUrl, listaPath(tipo)))?.dados as { result?: T[] } | null;
    if (!guardada?.result) throw err;
    const palavras = semAcento(q).split(/\s+/).filter(Boolean);
    return guardada.result
      .filter((item) => {
        const texto = campos.map((c) => semAcento(item[c])).join(' ');
        return palavras.every((p) => texto.includes(p));
      })
      .slice(0, 30);
  }
}

export function buscarClientes(baseUrl: string, token: string, q: string): Promise<ClienteBusca[]> {
  return buscar<ClienteBusca>(baseUrl, token, 'clientes', q, ['nome', 'documento', 'telefone']);
}

export function buscarServicos(baseUrl: string, token: string, q: string): Promise<ServicoBusca[]> {
  return buscar<ServicoBusca>(baseUrl, token, 'servicos', q, ['nome']);
}

export function buscarProdutos(baseUrl: string, token: string, q: string): Promise<ProdutoBusca[]> {
  return buscar<ProdutoBusca>(baseUrl, token, 'produtos', q, ['nome']);
}

export interface AtividadePayload {
  clientes_id: number | string;
  descricaoProduto?: string;
  chamado_id?: string;
  defeito?: string;
  observacoes?: string;
  servicos?: Array<{ id: number; quantidade: number }>;
  produtos?: Array<{ id: number; quantidade: number }>;
  atividade_realizada?: string;
  fotos?: string[];
  assinatura?: string;
  assinatura_nome?: string;
}

export function criarAtividade(baseUrl: string, token: string, payload: AtividadePayload) {
  return unwrap<{ idOs: number; servicos: number; produtos: number; status: string }>(
    baseUrl,
    '/api/v1/tecnico/atividade',
    { method: 'POST', token, body: payload },
  );
}

// ------------------------------------------------------------------
// Projetos (obras) e pontos
// ------------------------------------------------------------------

export function listProjetos(baseUrl: string, token: string): Promise<ProjetoResumo[]> {
  return unwrap<ProjetoResumo[]>(baseUrl, '/api/v1/tecnico/projetos', { token });
}

export function getProjeto(baseUrl: string, token: string, id: number | string): Promise<ProjetoDetalhe> {
  return unwrap<ProjetoDetalhe>(baseUrl, `/api/v1/tecnico/projetos/${encodeURIComponent(String(id))}`, { token });
}

export function getPonto(
  baseUrl: string,
  token: string,
  id: number | string,
  obraId: number | string,
): Promise<PontoDetalhe> {
  return unwrap<PontoDetalhe>(
    baseUrl,
    `/api/v1/tecnico/pontos/${encodeURIComponent(String(id))}${query({ obra_id: obraId })}`,
    { token },
  );
}

export interface PontoStatusPayload {
  obra_id: number | string;
  status: string;
  fotos?: string[];
  fotos_etiqueta?: string[];
  metragem_cabo?: string | number;
  ip?: string;
  canal?: string;
  porta?: string;
  modelo?: string;
  serie?: string;
  local?: string;
  observacao?: string;
  repassar_para?: string;
  instrucao?: string;
  teste?: { itens: Record<string, boolean | number>; retencao_dias: string | number };
}

export function mudarStatusPonto(
  baseUrl: string,
  token: string,
  pontoId: number | string,
  payload: PontoStatusPayload,
): Promise<{ ponto: PontoProjeto; fotos_salvas: number; repassado: string | null }> {
  return unwrap(
    baseUrl,
    `/api/v1/tecnico/pontos/${encodeURIComponent(String(pontoId))}/status`,
    { method: 'POST', token, body: payload },
  );
}

export function assumirPonto(baseUrl: string, token: string, pontoId: number | string, obraId: number | string) {
  return unwrap<{ idPonto: number }>(
    baseUrl,
    `/api/v1/tecnico/pontos/${encodeURIComponent(String(pontoId))}/assumir`,
    { method: 'POST', token, body: { obra_id: obraId } },
  );
}

export function repassarPonto(
  baseUrl: string,
  token: string,
  pontoId: number | string,
  payload: { obra_id: number | string; repassar_para: string; instrucao?: string },
) {
  return unwrap<{ idPonto: number; destinatario_id: number | null }>(
    baseUrl,
    `/api/v1/tecnico/pontos/${encodeURIComponent(String(pontoId))}/repassar`,
    { method: 'POST', token, body: payload },
  );
}

export interface RdoPayload {
  atividades?: string;
  condicao?: 'praticavel' | 'impraticavel';
  materiais?: Array<{ id: number; quantidade: number; nome?: string }>;
  servicos?: Array<{ id: number; quantidade: number; nome?: string }>;
  fotos?: string[];
  assinatura?: string;
}

export function registrarRdo(baseUrl: string, token: string, obraId: number | string, payload: RdoPayload) {
  return unwrap<{ idRdo: number; materiais: number; servicos: number }>(
    baseUrl,
    `/api/v1/tecnico/projetos/${encodeURIComponent(String(obraId))}/rdo`,
    { method: 'POST', token, body: payload },
  );
}

export function fila(baseUrl: string, token: string): Promise<FilaResult> {
  return unwrap<FilaResult>(baseUrl, '/api/v1/tecnico/fila', { token });
}

export function relatorio(
  baseUrl: string,
  token: string,
  tipo: 'diario' | 'semanal' | 'mensal',
  data: string,
): Promise<RelatorioTecnico> {
  return unwrap<RelatorioTecnico>(baseUrl, `/api/v1/tecnico/relatorio${query({ tipo, data })}`, { token });
}

// ------------------------------------------------------------------
// Perfil
// ------------------------------------------------------------------

export function perfil(baseUrl: string, token: string): Promise<TecnicoPerfil> {
  return unwrap<TecnicoPerfil>(baseUrl, '/api/v1/tecnico/perfil', { token });
}

export function atualizarPerfil(
  baseUrl: string,
  token: string,
  payload: { telefone?: string; celular?: string },
): Promise<TecnicoPerfil> {
  return unwrap<TecnicoPerfil>(baseUrl, '/api/v1/tecnico/perfil', { method: 'PUT', token, body: payload });
}

// ------------------------------------------------------------------
// Campo: a caminho, posição e assinatura do solicitante
// ------------------------------------------------------------------

/** POST /api/v1/tecnico/a-caminho — avisa o cliente por WhatsApp. */
export function aCaminho(baseUrl: string, token: string, osId: number | string) {
  return request<{ status: boolean; message: string; result?: { hora: string; repetido: boolean } }>(
    baseUrl,
    '/api/v1/tecnico/a-caminho',
    { method: 'POST', token, body: { os_id: osId } },
  );
}

/** POST /api/v1/tecnico/posicao — posição para o mapa em tempo real. */
export function enviarPosicao(
  baseUrl: string,
  token: string,
  payload: { latitude: number; longitude: number; precisao?: number | null; velocidade?: number | null; bateria?: number | null },
) {
  return unwrap<{ registrado: boolean; em_atendimento: boolean }>(baseUrl, '/api/v1/tecnico/posicao', {
    method: 'POST',
    token,
    body: payload,
    timeoutMs: 15000,
  });
}

const osPath = (id: number | string) => `/api/v1/tecnico/os/${encodeURIComponent(String(id))}`;

export function assinaturaSolicitante(baseUrl: string, token: string, osId: number | string) {
  return unwrap<AssinaturaSolicitante>(baseUrl, `${osPath(osId)}/assinatura-solicitante`, { token });
}

export function salvarAssinaturaSolicitante(
  baseUrl: string,
  token: string,
  osId: number | string,
  payload: { assinatura: string; nome?: string; documento?: string },
) {
  return unwrap<{ assinado: boolean }>(baseUrl, `${osPath(osId)}/assinatura-solicitante`, {
    method: 'POST',
    token,
    body: payload,
    timeoutMs: 45000,
  });
}

export function gerarLinkSolicitante(baseUrl: string, token: string, osId: number | string) {
  return unwrap<{ link: string; expira: string }>(baseUrl, `${osPath(osId)}/link-solicitante`, {
    method: 'POST',
    token,
    body: {},
  });
}

export function enviarLinkSolicitanteEmail(baseUrl: string, token: string, osId: number | string, email: string) {
  return request<{ status: boolean; message: string; result?: { link: string } }>(
    baseUrl,
    `${osPath(osId)}/link-solicitante/email`,
    { method: 'POST', token, body: { email } },
  );
}
