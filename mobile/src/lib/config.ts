/**
 * Preferências do app: URL da instalação MAPOS e dados do branding.
 * Persistidas no dispositivo para não pedir a cada abertura.
 */

const KEY_SERVER = 'mapos.serverUrl';
const KEY_BRAND = 'mapos.brand';
const KEY_AREA = 'mapos.area';

export interface Brand {
  appName: string;
  emitente: {
    nome?: string;
    telefone?: string;
    email?: string;
    url_logo?: string;
    cidade?: string;
    estado?: string;
  } | null;
}

function read(key: string): string | null {
  try {
    return window.localStorage.getItem(key);
  } catch {
    return null;
  }
}

function write(key: string, value: string): void {
  try {
    window.localStorage.setItem(key, value);
  } catch {
    /* modo privado / storage bloqueado: segue sem persistir */
  }
}

function remove(key: string): void {
  try {
    window.localStorage.removeItem(key);
  } catch {
    /* ignora */
  }
}

/**
 * Instalação MAPOS padrão deste app (exclusiva do usuário).
 * Usada quando o app não é servido de dentro da instalação, para que ele
 * funcione sem configurar nada.
 */
export const DEFAULT_SERVER_URL = 'https://jj-ferreiras.com.br/mapos';

/**
 * Normaliza a URL: sem barra final, sem /api/v1, sem /index.php e — se a pessoa
 * colar o endereço do próprio app (/mobile-app) — volta para a raiz do sistema.
 */
export function normalizeServerUrl(input: string): string {
  let value = input.trim();
  if (!value) return '';
  if (!/^https?:\/\//i.test(value)) value = `https://${value}`;
  value = value.replace(/\/+$/, '');
  value = value.replace(/\/mobile-app$/i, '');
  value = value.replace(/\/index\.php(\/.*)?$/i, '');
  value = value.replace(/\/api\/v1$/i, '');
  return value.replace(/\/+$/, '');
}

/** Ambiente local (dev/validação): nunca assume a instalação padrão. */
function isLocalHost(host: string): boolean {
  return /^(localhost|127\.0\.0\.1|\[::1\]|0\.0\.0\.0)(:\d+)?$/i.test(host);
}

/**
 * Rodando dentro do APK (Capacitor), a página é servida de https://localhost —
 * que é "local" para o navegador mas não é o ambiente de desenvolvimento.
 */
export function isNativeApp(): boolean {
  const cap = (window as unknown as { Capacitor?: { isNativePlatform?: () => boolean } }).Capacitor;
  return Boolean(cap?.isNativePlatform?.());
}

/**
 * Quando o app é publicado dentro da instalação MAPOS (ex.: /mapos/mobile/),
 * a API fica um nível acima e na mesma origem — sem CORS e sem digitar a URL.
 * Em desenvolvimento (portas do Vite) não detecta nada e mantém o fluxo manual.
 */
export function detectSameOrigin(): string | null {
  if (typeof window === 'undefined') return null;
  const { protocol, host, href } = window.location;
  if (protocol !== 'http:' && protocol !== 'https:') return null;
  // portas de desenvolvimento do Vite
  if (/:(5173|5180|4173)$/.test(host)) return null;
  try {
    const appDir = new URL('.', href);
    // Só faz sentido em subpasta (/mapos/mobile-app/). Na raiz do domínio não há
    // instalação "um nível acima" — nesse caso a URL é digitada uma vez.
    if (appDir.pathname.replace(/\/+$/, '') === '') return null;
    const installRoot = new URL('..', appDir);
    return `${installRoot.origin}${installRoot.pathname}`.replace(/\/+$/, '');
  } catch {
    return null;
  }
}

export function getServerUrl(): string | null {
  return read(KEY_SERVER);
}

/**
 * URL efetiva da instalação: o que a pessoa configurou, senão a instalação
 * detectada (mesma origem), senão a padrão — esta última só fora do ambiente
 * local, para que dev e testes continuem apontando para o servidor de teste.
 */
export function resolveServerUrl(): string | null {
  const stored = read(KEY_SERVER);
  if (stored) return stored;
  // No APK não existe "instalação um nível acima": vale o endereço padrão.
  if (isNativeApp()) return DEFAULT_SERVER_URL;
  const detected = detectSameOrigin();
  if (detected) return detected;
  return isLocalHost(window.location.host) ? null : DEFAULT_SERVER_URL;
}

export function setServerUrl(url: string): void {
  write(KEY_SERVER, normalizeServerUrl(url));
}

export function clearServerUrl(): void {
  remove(KEY_SERVER);
}

export function getBrand(): Brand | null {
  const raw = read(KEY_BRAND);
  if (!raw) return null;
  try {
    return JSON.parse(raw) as Brand;
  } catch {
    return null;
  }
}

export function setBrand(brand: Brand): void {
  write(KEY_BRAND, JSON.stringify(brand));
}

export function clearBrand(): void {
  remove(KEY_BRAND);
}

/** Área escolhida pelo usuário que tem mais de uma (sistema, técnico, RH). */
export function getAreaPreferida(): string | null {
  return read(KEY_AREA);
}

export function setAreaPreferida(area: string | null): void {
  if (area) write(KEY_AREA, area);
  else remove(KEY_AREA);
}
