/**
 * Apoio ao trabalho de campo: distância ao local, câmera do dispositivo e
 * download/visualização de arquivos (PDF do holerite) tanto no navegador
 * quanto dentro do APK, onde window.open não abre nada.
 */

import { isNativeApp } from './config';

/** Distância em metros entre dois pontos (Haversine), igual ao servidor. */
export function distanciaMetros(lat1: number, lon1: number, lat2: number, lon2: number): number {
  const r = 6371000;
  const dLat = ((lat2 - lat1) * Math.PI) / 180;
  const dLon = ((lon2 - lon1) * Math.PI) / 180;
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos((lat1 * Math.PI) / 180) * Math.cos((lat2 * Math.PI) / 180) * Math.sin(dLon / 2) ** 2;
  return r * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

/** "1,2 km" / "340 m" — leitura rápida da distância. */
export function distanciaTexto(metros: number | null | undefined): string {
  if (metros === null || metros === undefined || !Number.isFinite(metros)) return '—';
  if (metros >= 1000) return `${(metros / 1000).toFixed(1).replace('.', ',')} km`;
  return `${Math.round(metros)} m`;
}

/** Câmera frontal para a selfie do ponto; null quando indisponível. */
export async function abrirCameraFrontal(): Promise<MediaStream | null> {
  try {
    if (!navigator.mediaDevices?.getUserMedia) return null;
    return await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'user', width: 480, height: 640 },
      audio: false,
    });
  } catch {
    return null;
  }
}

export function fecharStream(stream: MediaStream | null): void {
  stream?.getTracks().forEach((track) => track.stop());
}

/** JPG da câmera para a selfie; null se ainda não há quadro. */
export function selfieDoVideo(video: HTMLVideoElement | null, qualidade = 0.8): string | null {
  if (!video || !video.videoWidth) return null;
  const canvas = document.createElement('canvas');
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  const ctx = canvas.getContext('2d');
  if (!ctx) return null;
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
  return canvas.toDataURL('image/jpeg', qualidade);
}

/**
 * Baixa um blob como arquivo. No navegador o link com download basta; no APK
 * o WebView baixa e entrega pelo DownloadManager/visualizador do sistema.
 */
export function baixarBlob(blob: Blob, nome: string): void {
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = nome;
  link.rel = 'noopener';
  // No APK o download de blob exige o atributo target para o WebView delegar.
  if (isNativeApp()) link.target = '_blank';
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.setTimeout(() => URL.revokeObjectURL(url), 30000);
}

/** Abre o PDF numa nova aba (navegador) — no APK, cai para download. */
export function visualizarBlob(blob: Blob, nome: string): void {
  if (isNativeApp()) {
    baixarBlob(blob, nome);
    return;
  }
  const url = URL.createObjectURL(blob);
  const win = window.open(url, '_blank', 'noopener');
  if (!win) {
    // popup bloqueado: entrega o arquivo do mesmo jeito
    baixarBlob(blob, nome);
    window.setTimeout(() => URL.revokeObjectURL(url), 60000);
    return;
  }
  window.setTimeout(() => URL.revokeObjectURL(url), 60000);
}

/** Hora atual "HH:MM" para o relógio do ponto. */
export function horaAgora(date = new Date()): string {
  return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}

export function dataPorExtenso(date = new Date()): string {
  try {
    return date.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long' });
  } catch {
    return '';
  }
}

/** Saudação pelo horário (usada no painel do técnico, como na web). */
export function saudacao(date = new Date()): string {
  const h = date.getHours();
  if (h < 12) return 'Bom dia';
  if (h < 18) return 'Boa tarde';
  return 'Boa noite';
}

/**
 * Ponto do horário para o campo `registrado_em` (fila offline): o servidor
 * aceita até 72h atrás e 5 min no futuro.
 */
export function agoraIso(): string {
  const d = new Date();
  const offset = d.getTimezoneOffset() * 60000;
  return new Date(d.getTime() - offset).toISOString().slice(0, 19).replace('T', ' ');
}
