/**
 * Contrato da configuração de câmeras/NVR no app do técnico.
 *
 * O servidor guarda apenas dados técnicos (IP, canal, MAC, modelo). Senha de
 * NVR e de câmera nunca sai do aparelho: o plugin nativo usa a senha só durante
 * a chamada ISAPI e ela não é enviada para a API do MAPOS.
 */

export interface CameraPonto {
  idPonto: number;
  identificacao: string;
  tipo: string;
  local?: string | null;
  status: string;
  status_rotulo?: string | null;
  ip?: string | null;
  canal?: string | null;
  porta?: string | null;
  modelo?: string | null;
  serie?: string | null;
  mac?: string | null;
  nvr_id?: number | null;
  fabricante?: string | null;
  firmware?: string | null;
  ativada?: number | null;
  configurada_em?: string | null;
  observacao?: string | null;
  configurada: boolean;
}

export interface CameraPlano {
  obra: {
    idObra: number;
    nome?: string | null;
    nomeCliente?: string | null;
    cidade?: string | null;
    uf?: string | null;
    endereco?: string | null;
  };
  nvr: CameraPonto[];
  cameras: CameraPonto[];
  resumo: { total: number; configuradas: number; pendentes: number };
  canaisOcupados: Record<string, Record<string, string>>;
}

/** Dispositivo devolvido pela descoberta SADP (antes de autenticar). */
export interface DispositivoDescoberto {
  ip: string;
  mac: string;
  serie: string;
  sadpId: string;
  modelo: string;
  fabricante: string;
  firmware: string;
  ativado: boolean;
  porta: number;
  mascara?: string | null;
  gateway?: string | null;
  dhcp?: boolean;
}

export interface CanalNvr {
  id: number;
  nome?: string | null;
  ip?: string | null;
  online: boolean;
  habilitado: boolean;
  ocupado: boolean;
  protocolo?: string | null;
  porta?: number | null;
  modelo?: string | null;
}

export interface NvrResumo {
  ip: string;
  nome?: string | null;
  modelo?: string | null;
  serie?: string | null;
  firmware?: string | null;
  quantosCanais?: number | null;
}

export interface EnvioCamera {
  ponto_id?: number;
  ip?: string;
  canal?: string;
  porta?: string;
  modelo?: string | null;
  serie?: string | null;
  mac?: string | null;
  sadp_id?: string | null;
  firmware?: string | null;
  ativada?: boolean;
  observacao?: string | null;
  nvr_id?: number | null;
  status?: string;
}

export interface ResultadoLote {
  salvas: number;
  criadas: number;
  falhas: Array<{ ponto_id?: number; ip?: string | null; erro: string }>;
  plano: { nvr: CameraPonto[]; cameras: CameraPonto[]; resumo: CameraPlano['resumo'] };
}
