/**
 * Regras do modo offline para cada envio (POST/PUT/DELETE) da API.
 *
 * - `fila`: sem internet, o envio fica guardado no aparelho e sai sozinho
 *   quando a rede voltar. A tela recebe uma resposta provisória (`resultado`)
 *   e os dados guardados podem ser ajustados (`ajustar`) para a tela já
 *   mostrar o efeito (ex.: atendimento iniciado).
 * - `descartar`: perder não faz falta (posição para o mapa ao vivo).
 * - Sem regra: precisa de internet na hora (login, cadastro do administrativo,
 *   WhatsApp ao cliente, links públicos, câmeras).
 */

import { agoraIso } from './geo';

export interface Ajuste {
  /** Caminho da leitura guardada que muda com este envio. */
  caminho: string;
  aplicar: (envelope: Record<string, unknown>) => Record<string, unknown>;
}

export interface Regra {
  tipo: 'fila' | 'descartar';
  rotulo: string;
  /** Envia o horário do aparelho (o servidor aceita até 72 h atrás). */
  comHorario?: boolean;
  resultado?: (corpo: Record<string, unknown>, guardado: (caminho: string) => Promise<unknown>) => Promise<unknown> | unknown;
  ajustes?: (corpo: Record<string, unknown>) => Ajuste[];
}

type Corpo = Record<string, unknown>;

const T = '/api/v1/tecnico';

function hora(): string {
  return agoraIso().slice(11, 16);
}

/** Muda o `result` de um envelope guardado sem perder o resto. */
function noResultado(fn: (r: Corpo) => Corpo) {
  return (env: Corpo): Corpo => ({ ...env, result: fn({ ...((env.result as Corpo) ?? {}) }) });
}

function detalheOs(corpo: Corpo, fn: (r: Corpo) => Corpo): Ajuste[] {
  const id = corpo.os_id;
  return id ? [{ caminho: `${T}/os/${encodeURIComponent(String(id))}`, aplicar: noResultado(fn) }] : [];
}

const PROXIMO: Record<string, string> = {
  entrada: 'inicio_intervalo',
  inicio_intervalo: 'fim_intervalo',
  fim_intervalo: 'inicio_intervalo',
  saida: 'entrada',
};

const ROTULO_PONTO: Record<string, string> = {
  entrada: 'Entrada',
  saida: 'Saída',
  inicio_intervalo: 'Início do intervalo',
  fim_intervalo: 'Fim do intervalo',
};

/** Batida de ponto feita sem internet, no formato que a tela já mostra. */
function batidaLocal(corpo: Corpo) {
  const tipo = String(corpo.tipo ?? 'entrada');
  const momento = String(corpo.registrado_em ?? agoraIso());
  return { id: 0, tipo, label: ROTULO_PONTO[tipo] ?? tipo, hora: momento.slice(11, 16), data_hora: momento, offline: true };
}

const REGRAS: Array<{ metodo: string; caminho: RegExp; regra: Regra }> = [
  { metodo: 'POST', caminho: /^\/api\/v1\/tecnico\/posicao$/, regra: { tipo: 'descartar', rotulo: 'Posição' } },
  {
    metodo: 'POST',
    caminho: /^\/api\/v1\/tecnico\/checkin$/,
    regra: {
      tipo: 'fila',
      rotulo: 'Início do atendimento',
      comHorario: true,
      resultado: () => ({ offline: true }),
      ajustes: (c) =>
        detalheOs(c, (r) => ({
          ...r,
          checkinAtivo: { data_entrada: c.registrado_em, offline: true },
          podeCheckin: false,
          podeCheckout: true,
          podeFoto: true,
          os: { ...((r.os as Corpo) ?? {}), status: 'Em Andamento' },
        })),
    },
  },
  {
    metodo: 'POST',
    caminho: /^\/api\/v1\/tecnico\/checkout$/,
    regra: {
      tipo: 'fila',
      rotulo: 'Fim do atendimento',
      comHorario: true,
      resultado: () => ({ offline: true }),
      ajustes: (c) =>
        detalheOs(c, (r) => ({
          ...r,
          checkinAtivo: null,
          podeCheckin: false,
          podeCheckout: false,
          podeNaoRealizado: false,
          os: { ...((r.os as Corpo) ?? {}), status: 'Finalizado' },
        })),
    },
  },
  { metodo: 'POST', caminho: /^\/api\/v1\/tecnico\/fotos$/, regra: { tipo: 'fila', rotulo: 'Foto do atendimento', comHorario: true, resultado: () => ({ offline: true }) } },
  {
    metodo: 'POST',
    caminho: /^\/api\/v1\/tecnico\/observacoes$/,
    regra: {
      tipo: 'fila',
      rotulo: 'Observação',
      comHorario: true,
      resultado: () => ({ offline: true }),
      ajustes: (c) =>
        detalheOs(c, (r) => ({
          ...r,
          observacoes: [
            ...((r.observacoes as unknown[]) ?? []),
            { id: -Date.now(), texto: c.texto, data_hora: c.registrado_em, hora: `${hora()} (aguardando envio)`, autor: 'Você' },
          ],
        })),
    },
  },
  {
    metodo: 'POST',
    caminho: /^\/api\/v1\/tecnico\/nao-realizado$/,
    regra: {
      tipo: 'fila',
      rotulo: 'Serviço não realizado',
      comHorario: true,
      resultado: () => ({ offline: true }),
      ajustes: (c) =>
        detalheOs(c, (r) => ({
          ...r,
          checkinAtivo: null,
          podeCheckin: false,
          podeCheckout: false,
          podeNaoRealizado: false,
          os: { ...((r.os as Corpo) ?? {}), status: 'Não Realizado' },
        })),
    },
  },
  {
    metodo: 'POST',
    caminho: /^\/api\/v1\/tecnico\/nao-realizadas\/reagendar$/,
    regra: { tipo: 'fila', rotulo: 'Reagendamento', resultado: (c) => ({ offline: true, nova_data: c.nova_data }) },
  },
  { metodo: 'POST', caminho: /^\/api\/v1\/tecnico\/nao-realizadas\/reabrir$/, regra: { tipo: 'fila', rotulo: 'Reabertura de OS', resultado: () => ({ offline: true }) } },
  {
    metodo: 'POST',
    caminho: /^\/api\/v1\/tecnico\/atividade$/,
    regra: { tipo: 'fila', rotulo: 'Nova atividade não programada', comHorario: true, resultado: () => ({ offline: true, idOs: null }) },
  },
  {
    metodo: 'POST',
    caminho: /^\/api\/v1\/tecnico\/pontos\/\d+\/(status|assumir|repassar)$/,
    regra: { tipo: 'fila', rotulo: 'Ponto do projeto', comHorario: true, resultado: () => ({ offline: true }) },
  },
  {
    metodo: 'POST',
    caminho: /^\/api\/v1\/tecnico\/projetos\/\d+\/rdo$/,
    regra: { tipo: 'fila', rotulo: 'Registro do dia (RDO)', comHorario: true, resultado: () => ({ offline: true, idRdo: null, materiais: 0, servicos: 0 }) },
  },
  {
    metodo: 'POST',
    caminho: /^\/api\/v1\/tecnico\/os\/\d+\/assinatura-solicitante$/,
    regra: { tipo: 'fila', rotulo: 'Assinatura do solicitante', resultado: () => ({ offline: true, assinado: true }) },
  },
  { metodo: 'PUT', caminho: /^\/api\/v1\/tecnico\/perfil$/, regra: { tipo: 'fila', rotulo: 'Contato do perfil', resultado: (c) => ({ offline: true, ...c }) } },
  {
    metodo: 'POST',
    caminho: /^\/api\/v1\/ponto\/registrar$/,
    regra: {
      tipo: 'fila',
      rotulo: 'Batida de ponto',
      comHorario: true,
      resultado: async (c, guardado) => {
        const estado = ((await guardado('/api/v1/ponto')) as Corpo | null)?.result as Corpo | undefined;
        const batidas = [...(((estado?.batidasHoje as unknown[]) ?? []) as unknown[]), batidaLocal(c)];
        const tipo = String(c.tipo ?? 'entrada');
        return {
          offline: true,
          registro_id: 0,
          tipo,
          hora: String(c.registrado_em ?? agoraIso()).slice(11, 16),
          proximoTipo: PROXIMO[tipo] ?? 'entrada',
          fora_area: false,
          batidasHoje: batidas,
        };
      },
      ajustes: (c) => [
        {
          caminho: '/api/v1/ponto',
          aplicar: noResultado((r) => {
            const tipo = String(c.tipo ?? 'entrada');
            return { ...r, batidasHoje: [...((r.batidasHoje as unknown[]) ?? []), batidaLocal(c)], proximoTipo: PROXIMO[tipo] ?? 'entrada' };
          }),
        },
      ],
    },
  },
  { metodo: 'POST', caminho: /^\/api\/v1\/colaborador\/(ocorrencias|ausencias)$/, regra: { tipo: 'fila', rotulo: 'Solicitação ao RH', resultado: (c) => ({ offline: true, ...c, status: 'pendente' }) } },
  { metodo: 'PUT', caminho: /^\/api\/v1\/colaborador\/dados$/, regra: { tipo: 'fila', rotulo: 'Meus dados', resultado: (c) => ({ offline: true, ...c }) } },
];

/** Caminho sem o "/index.php" e sem a query string, para comparar com as regras. */
export function caminhoLimpo(caminho: string): string {
  return caminho.replace(/^\/index\.php/, '').split('?')[0] ?? caminho;
}

export function regraPara(metodo: string, caminho: string): Regra | null {
  const limpo = caminhoLimpo(caminho);
  const achada = REGRAS.find((r) => r.metodo === metodo && r.caminho.test(limpo));
  return achada?.regra ?? null;
}

/** Corpo do envio com o horário do aparelho, quando a regra pede. */
export function corpoComHorario(regra: Regra, corpo: unknown): Corpo {
  const base: Corpo = corpo && typeof corpo === 'object' ? { ...(corpo as Corpo) } : {};
  if (regra.comHorario && !base.registrado_em) base.registrado_em = agoraIso();
  return base;
}
