/**
 * Posição do técnico para o mapa em tempo real do administrativo, como o
 * assets/js/tecnico-posicao.js da Área do Técnico web: só envia se a
 * permissão de GPS JÁ foi concedida (nunca abre o pedido), no máximo a cada
 * 20 s, e para quando o app vai para segundo plano.
 */

import { useEffect, useRef } from 'react';
import { enviarPosicao } from './api-tecnico';
import { useApp } from './store';

const INTERVALO_MS = 20000;

interface BateriaNavegador {
  level: number;
}

async function nivelBateria(): Promise<number | null> {
  const nav = navigator as Navigator & { getBattery?: () => Promise<BateriaNavegador> };
  if (!nav.getBattery) return null;
  try {
    return Math.round((await nav.getBattery()).level * 100);
  } catch {
    return null;
  }
}

async function gpsJaPermitido(): Promise<boolean> {
  if (!navigator.geolocation || !navigator.permissions?.query) return false;
  try {
    const estado = await navigator.permissions.query({ name: 'geolocation' as PermissionName });
    return estado.state === 'granted';
  } catch {
    return false;
  }
}

export function usePosicaoTecnico(ativo: boolean): void {
  // useApp, não useApi: o hook roda no Shell também antes do login.
  const { serverUrl, session } = useApp();
  const baseUrl = serverUrl ?? '';
  const token = session?.token ?? '';
  const ultimo = useRef(0);
  const ocupado = useRef(false);

  useEffect(() => {
    if (!ativo || !token || !baseUrl) return;
    let watchId: number | null = null;
    let cancelado = false;

    const enviar = async (coords: GeolocationCoordinates) => {
      if (ocupado.current || Date.now() - ultimo.current < INTERVALO_MS) return;
      ocupado.current = true;
      ultimo.current = Date.now();
      try {
        await enviarPosicao(baseUrl, token, {
          latitude: coords.latitude,
          longitude: coords.longitude,
          precisao: coords.accuracy ?? null,
          velocidade: coords.speed ?? null,
          bateria: await nivelBateria(),
        });
      } catch {
        /* sem rede ou servidor antigo: o próximo ponto tenta de novo */
      } finally {
        ocupado.current = false;
      }
    };

    const parar = () => {
      if (watchId !== null) navigator.geolocation.clearWatch(watchId);
      watchId = null;
    };

    const iniciar = async () => {
      if (watchId !== null || document.hidden || !(await gpsJaPermitido()) || cancelado) return;
      watchId = navigator.geolocation.watchPosition(
        (p) => void enviar(p.coords),
        () => undefined,
        { enableHighAccuracy: false, maximumAge: 15000, timeout: 30000 },
      );
    };

    const visibilidade = () => (document.hidden ? parar() : void iniciar());
    document.addEventListener('visibilitychange', visibilidade);
    void iniciar();

    return () => {
      cancelado = true;
      document.removeEventListener('visibilitychange', visibilidade);
      parar();
    };
  }, [ativo, baseUrl, token]);
}
