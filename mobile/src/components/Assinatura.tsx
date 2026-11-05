/**
 * Captura de assinatura no dedo: o traço vira PNG recortado e é enviado em
 * base64, como as telas de check-in/check-out da versão web.
 */

import { useCallback, useEffect, useRef, useState } from 'react';
import type { MutableRefObject, PointerEvent as ReactPointerEvent } from 'react';

const TINTA = '#0b1b2b';

export function Assinatura({
  canvasRef,
  onChange,
  altura = 180,
  rotulo = 'Assine abaixo',
}: {
  canvasRef: MutableRefObject<HTMLCanvasElement | null>;
  onChange?: (temTraco: boolean) => void;
  altura?: number;
  rotulo?: string;
}) {
  const desenhando = useRef(false);
  const ultimo = useRef<{ x: number; y: number } | null>(null);
  const [vazio, setVazio] = useState(true);

  const dimensionar = useCallback(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const largura = canvas.clientWidth;
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    // Redimensionar limpa o traço; só mexe quando a medida realmente mudou.
    if (canvas.width === Math.round(largura * dpr) && canvas.height === Math.round(altura * dpr)) return;
    canvas.width = Math.round(largura * dpr);
    canvas.height = Math.round(altura * dpr);
    const ctx = canvas.getContext('2d');
    if (ctx) {
      ctx.scale(dpr, dpr);
      ctx.lineWidth = 2.2;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      ctx.strokeStyle = TINTA;
    }
  }, [altura, canvasRef]);

  useEffect(() => {
    dimensionar();
    const aoRedimensionar = () => dimensionar();
    window.addEventListener('resize', aoRedimensionar);
    return () => window.removeEventListener('resize', aoRedimensionar);
  }, [dimensionar]);

  const ponto = (evento: ReactPointerEvent<HTMLCanvasElement>) => {
    const canvas = canvasRef.current;
    if (!canvas) return { x: 0, y: 0 };
    const caixa = canvas.getBoundingClientRect();
    return { x: evento.clientX - caixa.left, y: evento.clientY - caixa.top };
  };

  const iniciar = (evento: ReactPointerEvent<HTMLCanvasElement>) => {
    evento.preventDefault();
    const canvas = canvasRef.current;
    if (!canvas) return;
    canvas.setPointerCapture(evento.pointerId);
    desenhando.current = true;
    const atual = ponto(evento);
    ultimo.current = atual;
    const ctx = canvas.getContext('2d');
    if (ctx) {
      ctx.beginPath();
      ctx.arc(atual.x, atual.y, 1.1, 0, Math.PI * 2);
      ctx.fillStyle = TINTA;
      ctx.fill();
    }
    if (vazio) {
      setVazio(false);
      onChange?.(true);
    }
  };

  const mover = (evento: ReactPointerEvent<HTMLCanvasElement>) => {
    if (!desenhando.current) return;
    evento.preventDefault();
    const anterior = ultimo.current;
    const atual = ponto(evento);
    const ctx = canvasRef.current?.getContext('2d');
    if (ctx && anterior) {
      ctx.beginPath();
      ctx.moveTo(anterior.x, anterior.y);
      ctx.lineTo(atual.x, atual.y);
      ctx.stroke();
    }
    ultimo.current = atual;
  };

  const terminar = (evento: ReactPointerEvent<HTMLCanvasElement>) => {
    if (desenhando.current) evento.preventDefault();
    desenhando.current = false;
    ultimo.current = null;
  };

  const limpar = () => {
    const canvas = canvasRef.current;
    const ctx = canvas?.getContext('2d');
    if (canvas && ctx) ctx.clearRect(0, 0, canvas.width, canvas.height);
    setVazio(true);
    onChange?.(false);
  };

  return (
    <div className="assinatura">
      <div className="row row--between">
        <span className="field__label">{rotulo}</span>
        <button type="button" className="reset-button" onClick={limpar} disabled={vazio}>
          Limpar
        </button>
      </div>
      <canvas
        ref={canvasRef}
        className="assinatura__canvas"
        style={{ height: altura }}
        onPointerDown={iniciar}
        onPointerMove={mover}
        onPointerUp={terminar}
        onPointerCancel={terminar}
        onPointerLeave={terminar}
      />
      {vazio ? <p className="assinatura__hint">Assine na área acima com o dedo.</p> : null}
    </div>
  );
}

/** Lê o traço do canvas como PNG recortado; null quando não há traço válido. */
export function lerAssinatura(canvas: HTMLCanvasElement | null): string | null {
  const ctx = canvas?.getContext('2d');
  if (!canvas || !ctx) return null;

  const { width, height } = canvas;
  const dados = ctx.getImageData(0, 0, width, height).data;

  let minX = width;
  let minY = height;
  let maxX = -1;
  let maxY = -1;
  for (let y = 0; y < height; y++) {
    for (let x = 0; x < width; x++) {
      const alfa = dados[(y * width + x) * 4 + 3] ?? 0;
      if (alfa > 8) {
        if (x < minX) minX = x;
        if (x > maxX) maxX = x;
        if (y < minY) minY = y;
        if (y > maxY) maxY = y;
      }
    }
  }
  if (maxX < 0 || maxY < 0) return null;

  const margem = 8;
  minX = Math.max(0, minX - margem);
  minY = Math.max(0, minY - margem);
  maxX = Math.min(width - 1, maxX + margem);
  maxY = Math.min(height - 1, maxY + margem);

  const recorte = document.createElement('canvas');
  recorte.width = maxX - minX + 1;
  recorte.height = maxY - minY + 1;
  const rctx = recorte.getContext('2d');
  if (!rctx) return null;
  rctx.fillStyle = '#ffffff';
  rctx.fillRect(0, 0, recorte.width, recorte.height);
  rctx.drawImage(canvas, minX, minY, recorte.width, recorte.height, 0, 0, recorte.width, recorte.height);

  const saida = recorte.toDataURL('image/png');
  // O servidor exige conteúdo mínimo de imagem; traço curto demais não serve.
  return saida.length > 600 ? saida : null;
}
