/** Hooks compartilhados pelas telas do técnico e do colaborador. */

import { useCallback, useEffect, useState } from 'react';
import { useApi } from './store';
import { useImageUrl } from '../components/Fotos';

/** Carrega um recurso da API com estado de carregando/erro e recarga manual. */
export function useCarga<T>(fn: (baseUrl: string, token: string) => Promise<T>, deps: unknown[] = []) {
  const { baseUrl, token, handle } = useApi();
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [tick, setTick] = useState(0);

  useEffect(() => {
    let cancelado = false;
    setLoading(true);
    setError(null);
    handle(() => fn(baseUrl, token))
      .then((result) => {
        if (!cancelado) setData(result);
      })
      .catch((err: Error) => {
        if (!cancelado) setError(err.message);
      })
      .finally(() => {
        if (!cancelado) setLoading(false);
      });
    return () => {
      cancelado = true;
    };
    // fn é recriada a cada render pelas telas; as dependências reais vêm em deps.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [baseUrl, token, handle, tick, ...deps]);

  const recarregar = useCallback(() => setTick((t) => t + 1), []);

  return { data, loading, error, setData, recarregar };
}

/** Foto de perfil vinda de um endpoint autenticado (ex.: tecnico/perfil/foto). */
export function useFotoPerfil(path: string | null | undefined) {
  const { baseUrl, token } = useApi();
  return useImageUrl(baseUrl, token, path);
}

/** Relógio de 1 em 1 segundo (tela do ponto). */
export function useRelogio(): Date {
  const [agora, setAgora] = useState(() => new Date());
  useEffect(() => {
    const timer = window.setInterval(() => setAgora(new Date()), 1000);
    return () => window.clearInterval(timer);
  }, []);
  return agora;
}
