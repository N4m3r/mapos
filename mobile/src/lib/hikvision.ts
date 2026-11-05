/**
 * Ponte com a rede local e os equipamentos Hikvision (plugin nativo do APK).
 *
 * O navegador/PWA não tem acesso à rede local do cliente: fora do APK todas as
 * chamadas recusam com uma mensagem clara, e `disponivel()` responde false para
 * a tela explicar o motivo em vez de mostrar um botão que não funciona.
 *
 * A senha do NVR e da câmera só existe nesta chamada, em memória, no aparelho.
 * Nada dela é enviado para a API do MAPOS nem gravado no banco.
 */

import { Capacitor, registerPlugin } from '@capacitor/core';
import type { CanalNvr, DispositivoDescoberto, NvrResumo } from './types-camera';

export interface InformacaoCamera {
  ip: string;
  mac?: string | null;
  serie?: string | null;
  modelo?: string | null;
  fabricante?: string | null;
  firmware?: string | null;
  ativada: boolean;
}

export interface RedeAtual {
  conectado: boolean;
  /** 'wifi' | 'ethernet' | 'celular' | 'offline' */
  tipo: string;
  ip?: string | null;
  mascara?: string | null;
  gateway?: string | null;
  /** Nome da interface, ex.: eth0 (adaptador USB) ou wlan0. */
  interface?: string | null;
}

export interface Credenciais {
  ip: string;
  usuario: string;
  senha: string;
  porta?: number;
}

export interface NovaCameraNoNvr {
  /** IP/usuário/senha do NVR que vai receber a câmera. */
  nvr: Credenciais;
  canal: number;
  cameraIp: string;
  cameraUsuario: string;
  cameraSenha: string;
  protocolo?: 'HIKVISION' | 'ONVIF';
  portaCamera?: number;
}

export interface HikvisionPlugin {
  disponivel(): Promise<{ disponivel: boolean; motivo?: string }>;
  redeAtual(): Promise<RedeAtual>;
  /** SADP: equipamentos Hikvision que responderam no segmento de rede. */
  descobrir(opts: { duracaoMs?: number }): Promise<{ dispositivos: DispositivoDescoberto[] }>;
  /** ISAPI: identificação do NVR e todos os canais, com ocupação. */
  lerNvr(opts: Credenciais): Promise<NvrResumo & { canais: CanalNvr[] }>;
  /** ISAPI: adiciona a câmera IP a um canal do NVR. */
  adicionarCanal(opts: NovaCameraNoNvr): Promise<{ ok: boolean; canal: number; mensagem?: string }>;
  /** ISAPI: modelo, série, firmware e se a câmera já foi ativada. */
  lerCamera(opts: Credenciais): Promise<InformacaoCamera>;
  /** ISAPI: troca IP/máscara/gateway/DHCP da câmera. */
  definirRedeCamera(
    opts: Credenciais & { novoIp: string; mascara?: string; gateway?: string; dhcp?: boolean },
  ): Promise<{ ok: boolean; mensagem?: string }>;
  /** ISAPI: ativa a câmera definindo a senha inicial (câmera nova de fábrica). */
  ativarCamera(opts: { ip: string; senha: string; porta?: number }): Promise<{ ok: boolean; mensagem?: string }>;
}

/** Implementação usada fora do APK (PWA/navegador de teste). */
const WebHikvision: HikvisionPlugin = {
  async disponivel() {
    return {
      disponivel: false,
      motivo: 'A descoberta na rede local só funciona no aplicativo Android (APK) conectado por cabo ou Wi-Fi à rede do cliente.',
    };
  },
  async redeAtual() {
    return { conectado: false, tipo: 'offline' };
  },
  async descobrir() {
    throw new Error(SEM_APK);
  },
  async lerNvr() {
    throw new Error(SEM_APK);
  },
  async adicionarCanal() {
    throw new Error(SEM_APK);
  },
  async lerCamera() {
    throw new Error(SEM_APK);
  },
  async definirRedeCamera() {
    throw new Error(SEM_APK);
  },
  async ativarCamera() {
    throw new Error(SEM_APK);
  },
};

const SEM_APK =
  'A configuração de câmeras precisa do aplicativo Android (APK) na rede do cliente.';

/**
 * No APK o Capacitor injeta `window.androidBridge`; no navegador a chave não
 * existe e o plugin nativo nunca seria encontrado.
 */
function nativo(): boolean {
  try {
    return Capacitor.isNativePlatform();
  } catch {
    return false;
  }
}

export const Hikvision: HikvisionPlugin = registerPlugin<HikvisionPlugin>('HikvisionCamera', {
  web: () => WebHikvision,
});

/** A tela usa isto para avisar antes de o técnico tentar configurar. */
export async function aparelhoPronto(): Promise<{ disponivel: boolean; motivo?: string }> {
  if (!nativo()) {
    return WebHikvision.disponivel();
  }
  try {
    return await Hikvision.disponivel();
  } catch (erro) {
    return { disponivel: false, motivo: (erro as Error).message || SEM_APK };
  }
}

/** Dica de rede para o topo da tela ("Rede 192.168.1.50 · ethernet"). */
export function redeEmTexto(rede: RedeAtual | null): string {
  if (!rede || !rede.conectado) return 'Sem rede local';
  const tipo =
    rede.tipo === 'ethernet' ? 'cabo' : rede.tipo === 'wifi' ? 'Wi-Fi' : rede.tipo || 'rede';
  return rede.ip ? `${tipo} · ${rede.ip}` : tipo;
}

/** Normaliza o MAC para aa:bb:cc:dd:ee:ff (o banco guarda maiúsculo). */
export function macNormalizado(mac: string | null | undefined): string {
  const hex = String(mac ?? '')
    .toUpperCase()
    .replace(/[^0-9A-F]/g, '');
  if (hex.length !== 12) return '';
  return hex.match(/.{2}/g)?.join(':') ?? '';
}

/** Compara MACs ignorando separador e caixa (SADP ora devolve com ':', ora '-'). */
export function mesmoMac(a: string | null | undefined, b: string | null | undefined): boolean {
  const x = macNormalizado(a);
  return Boolean(x) && x === macNormalizado(b);
}
