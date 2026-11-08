/** Estado global do app: servidor, sessão, área, branding e permissões. */

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import * as api from './api';
import { ApiError } from './http';
import { clearBrand, clearServerUrl, getAreaPreferida, getBrand, setAreaPreferida, getServerUrl, resolveServerUrl, setBrand, setServerUrl, type Brand } from './config';
import { can as canPermission, clearSession, flattenPermissions, getSession, saveSession, type Session } from './session';
import { areasDisponiveis, detectArea, homePathFor, type Area } from './area';
import { navigate } from './router';

export type Status = 'booting' | 'no-server' | 'logged-out' | 'ready';

interface AppState {
  status: Status;
  serverUrl: string | null;
  session: Session | null;
  area: Area;
  areas: Area[];
  trocarArea: (area: Area) => void;
  brand: Brand | null;
  error: string | null;
  twoFactor: boolean;
  signIn: (email: string, password: string, codigo?: string) => Promise<void>;
  signOut: () => void;
  connect: (url: string) => Promise<void>;
  disconnect: () => void;
  can: (permission: string) => boolean;
  refreshBrand: () => Promise<void>;
  clearError: () => void;
}

const AppContext = createContext<AppState | null>(null);

export function AppProvider({ children }: { children: ReactNode }) {
  const [serverUrl, setServer] = useState<string | null>(() => resolveServerUrl());
  const [session, setSessionState] = useState<Session | null>(() => getSession());
  const [brand, setBrandState] = useState<Brand | null>(() => getBrand());
  const [status, setStatus] = useState<Status>('booting');
  const [error, setError] = useState<string | null>(null);
  const [twoFactor, setTwoFactor] = useState(false);
  const [areaPreferida, setAreaPreferidaState] = useState<Area | null>(() => getAreaPreferida() as Area | null);

  const refreshBrand = useCallback(async () => {
    const url = serverUrl;
    const token = session?.token;
    if (!url) return;
    if (!token) {
      // Antes do login só a marca pública: assim a logo trocada no sistema
      // já aparece na tela de login, sem esperar o usuário entrar.
      try {
        const marca = await api.marca(url);
        setBrandState((prev) => {
          const next: Brand = {
            appName: marca.appName || marca.nome || 'MAPOS',
            emitente: { ...(prev?.emitente ?? {}), nome: marca.nome || prev?.emitente?.nome, url_logo: marca.url_logo ?? undefined },
          };
          setBrand(next);
          return next;
        });
      } catch {
        /* servidor sem /marca (versão antiga): mantém o que já havia */
      }
      return;
    }
    try {
      const data = await api.emitente(url, token);
      const emitente = data.emitente === false ? null : data.emitente;
      const next: Brand = {
        appName: data.appName || emitente?.nome || 'MAPOS',
        emitente: emitente
          ? {
              nome: emitente.nome,
              telefone: emitente.telefone,
              email: emitente.email,
              url_logo: emitente.url_logo,
              cidade: emitente.cidade,
              estado: emitente.estado,
            }
          : null,
      };
      setBrandState(next);
      setBrand(next);
    } catch {
      /* branding é opcional: mantém o que já havia */
    }
  }, [serverUrl, session?.token]);

  useEffect(() => {
    if (!serverUrl) {
      setStatus('no-server');
      return;
    }
    setStatus(session ? 'ready' : 'logged-out');
    void refreshBrand();
  }, [serverUrl, session, refreshBrand]);

  // A logo pode mudar no sistema com o app aberto: busca de novo quando ele
  // volta ao primeiro plano.
  useEffect(() => {
    const onVisible = () => {
      if (document.visibilityState === 'visible') void refreshBrand();
    };
    document.addEventListener('visibilitychange', onVisible);
    return () => document.removeEventListener('visibilitychange', onVisible);
  }, [refreshBrand]);

  // Ícone da aba/PWA acompanha a logo da empresa.
  useEffect(() => {
    const logo = brand?.emitente?.url_logo;
    document.querySelectorAll<HTMLLinkElement>('link[rel="icon"], link[rel="apple-touch-icon"]').forEach((link) => {
      if (!link.dataset.padrao) link.dataset.padrao = link.getAttribute('href') ?? '';
      link.href = logo || link.dataset.padrao;
      if (link.rel === 'icon') link.type = logo ? '' : 'image/svg+xml';
    });
  }, [brand?.emitente?.url_logo]);

  const signIn = useCallback(
    async (email: string, password: string, codigo?: string) => {
      if (!serverUrl) throw new Error('Configure a URL do servidor.');
      setError(null);
      try {
        const result = await api.login(serverUrl, { email, password, codigo });
        const permissions = flattenPermissions(result);
        const conta = await api.conta(serverUrl, result.access_token);
        const next = saveSession(result.access_token, conta.usuario, permissions);
        setSessionState(next);
        setTwoFactor(false);
        setStatus('ready');
        // Cada perfil cai na sua área — a URL antiga do hash não pode manter
        // um técnico numa tela administrativa que ele não enxerga.
        navigate(homePathFor(detectArea(next, areaPreferida)));
      } catch (err) {
        if (err instanceof ApiError) {
          setTwoFactor(/duas etapas|codigo/i.test(err.message));
          setError(err.message);
        } else {
          setError('Não foi possível efetuar o login.');
        }
        throw err;
      }
    },
    [serverUrl, areaPreferida],
  );

  const signOut = useCallback(() => {
    clearSession();
    setSessionState(null);
    setStatus(serverUrl ? 'logged-out' : 'no-server');
  }, [serverUrl]);

  const connect = useCallback(async (url: string) => {
    clearSession();
    clearBrand();
    setSessionState(null);
    setBrandState(null);
    setServerUrl(url);
    const normalized = getServerUrl();
    setServer(normalized);
    setStatus(normalized ? 'logged-out' : 'no-server');
  }, []);

  const disconnect = useCallback(() => {
    clearSession();
    clearServerUrl();
    clearBrand();
    setSessionState(null);
    setBrandState(null);
    setServer(null);
    setStatus('no-server');
  }, []);

  const can = useCallback((permission: string) => canPermission(session, permission), [session]);
  const area = useMemo(() => detectArea(session, areaPreferida), [session, areaPreferida]);
  const areas = useMemo(() => areasDisponiveis(session), [session]);
  const trocarArea = useCallback((nova: Area) => {
    setAreaPreferida(nova);
    setAreaPreferidaState(nova);
    navigate(homePathFor(nova));
  }, []);

  const value = useMemo<AppState>(
    () => ({
      status,
      serverUrl,
      session,
      area,
      areas,
      trocarArea,
      brand,
      error,
      twoFactor,
      signIn,
      signOut,
      connect,
      disconnect,
      can,
      refreshBrand,
      clearError: () => setError(null),
    }),
    [status, serverUrl, session, area, areas, trocarArea, brand, error, twoFactor, signIn, signOut, connect, disconnect, can, refreshBrand],
  );

  return <AppContext.Provider value={value}>{children}</AppContext.Provider>;
}

export function useApp(): AppState {
  const ctx = useContext(AppContext);
  if (!ctx) throw new Error('useApp deve ser usado dentro de <AppProvider>');
  return ctx;
}

/**
 * Hook para telas que exigem servidor + sessão.
 * `handle` é estável entre renders: as telas o usam em useCallback/useEffect, e
 * uma identidade nova a cada render disparava o efeito em loop (fetch infinito).
 */
export function useApi() {
  const { serverUrl, session, signOut } = useApp();
  const baseUrl = serverUrl ?? '';
  const token = session?.token ?? '';

  const handle = useCallback(
    async <T,>(fn: () => Promise<T>): Promise<T> => {
      try {
        return await fn();
      } catch (err) {
        if (err instanceof ApiError && err.isAuthFailure) signOut();
        throw err;
      }
    },
    [signOut],
  );

  if (!serverUrl || !session) throw new Error('Sessão indisponível');

  return { baseUrl, token, session, handle };
}
