/** Sessão persistida: token JWT + perfil + permissões do usuário. */

import type { LoginResult, Usuario } from './types';

const KEY = 'mapos.session';

export interface Session {
  token: string;
  user: Usuario;
  permissions: Record<string, string>;
  createdAt: number;
}

function read(): Session | null {
  try {
    const raw = window.localStorage.getItem(KEY);
    if (!raw) return null;
    return JSON.parse(raw) as Session;
  } catch {
    return null;
  }
}

export function getSession(): Session | null {
  return read();
}

export function saveSession(token: string, user: Usuario, permissions: Record<string, string>): Session {
  const session: Session = { token, user, permissions, createdAt: Date.now() };
  try {
    window.localStorage.setItem(KEY, JSON.stringify(session));
  } catch {
    /* storage indisponível: sessão fica só em memória */
  }
  return session;
}

export function clearSession(): void {
  try {
    window.localStorage.removeItem(KEY);
  } catch {
    /* ignora */
  }
}

/** O login devolve permissions: [ { vOs: "1", ... } ]; achatamos para consulta direta. */
export function flattenPermissions(result: LoginResult): Record<string, string> {
  const merged: Record<string, string> = {};
  for (const group of result.permissions ?? []) {
    if (!group || typeof group !== 'object') continue;
    for (const [key, value] of Object.entries(group)) {
      merged[key] = String(value);
    }
  }
  return merged;
}

export function can(session: Session | null, permission: string): boolean {
  if (!session) return false;
  return session.permissions[permission] === '1';
}
