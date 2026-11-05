/**
 * Captura no dispositivo: GPS, foto da câmera e assinatura no dedo.
 *
 * As fotos saem reduzidas (lado maior 1280px, JPEG ~0,82) porque o servidor
 * grava a imagem em base64 no banco — enviar o original de 6 MB telefonema
 * custa caro e estoura o limite do upload em conexão de campo.
 */

export interface Coordenada {
  latitude: number;
  longitude: number;
  precisao: number;
}

export interface ErroCaptura {
  codigo: 'sem-suporte' | 'permissao' | 'indisponivel' | 'tempo' | 'cancelado';
  message: string;
}

/** Posição atual com timeout curto — no campo o atendimento não pode travar. */
export function obterLocalizacao(timeoutMs = 12000): Promise<Coordenada | null> {
  if (typeof navigator === 'undefined' || !navigator.geolocation) {
    return Promise.resolve(null);
  }

  return new Promise((resolve) => {
    navigator.geolocation.getCurrentPosition(
      (pos) =>
        resolve({
          latitude: pos.coords.latitude,
          longitude: pos.coords.longitude,
          precisao: pos.coords.accuracy,
        }),
      () => resolve(null),
      { enableHighAccuracy: true, timeout: timeoutMs, maximumAge: 30000 },
    );
  });
}

/** Formata a coordenada para exibição curta (ex.: -23,5502). */
export function coordenadaTexto(c: Coordenada | null): string | null {
  if (!c) return null;
  const f = (n: number) => n.toFixed(5).replace('.', ',');
  return `${f(c.latitude)}, ${f(c.longitude)}`;
}

/**
 * Lê um File de imagem, corrige a orientação EXIF redimensionando pelo canvas
 * e devolve data URL JPEG. Devolve null quando o arquivo não é imagem.
 */
export async function comprimirImagem(arquivo: File, ladoMaximo = 1280, qualidade = 0.82): Promise<string | null> {
  if (!arquivo.type.startsWith('image/')) return null;

  const bitmap = await carregarBitmap(arquivo);
  if (!bitmap) return null;

  const escala = Math.min(1, ladoMaximo / Math.max(bitmap.width, bitmap.height));
  const largura = Math.max(1, Math.round(bitmap.width * escala));
  const altura = Math.max(1, Math.round(bitmap.height * escala));

  const canvas = document.createElement('canvas');
  canvas.width = largura;
  canvas.height = altura;
  const ctx = canvas.getContext('2d');
  if (!ctx) return null;

  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, largura, altura);
  ctx.drawImage(bitmap as CanvasImageSource, 0, 0, largura, altura);
  if ('close' in bitmap && typeof bitmap.close === 'function') bitmap.close();

  return canvas.toDataURL('image/jpeg', qualidade);
}

type BitmapLike = ImageBitmap | HTMLImageElement;

async function carregarBitmap(arquivo: File): Promise<BitmapLike | null> {
  if (typeof createImageBitmap === 'function') {
    try {
      return await createImageBitmap(arquivo, { imageOrientation: 'from-image' } as ImageBitmapOptions);
    } catch {
      /* Safari antigo: cai no <img> */
    }
  }

  const url = URL.createObjectURL(arquivo);
  try {
    const img = new Image();
    img.decoding = 'async';
    await new Promise<void>((resolve, reject) => {
      img.onload = () => resolve();
      img.onerror = () => reject(new Error('imagem inválida'));
      img.src = url;
    });
    return img;
  } catch {
    return null;
  } finally {
    window.setTimeout(() => URL.revokeObjectURL(url), 5000);
  }
}

/**
 * Abre a câmera/galeria no navegador e devolve data URL já comprimida.
 * `accept="image/*"` + `capture="environment"` abre a câmera traseira no Android.
 */
export function escolherFoto(): Promise<string | null> {
  return new Promise((resolve) => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.setAttribute('capture', 'environment');
    input.style.display = 'none';

    let finalizado = false;
    const concluir = (valor: string | null) => {
      if (finalizado) return;
      finalizado = true;
      input.remove();
      resolve(valor);
    };

    input.addEventListener('change', () => {
      const arquivo = input.files?.[0];
      if (!arquivo) {
        concluir(null);
        return;
      }
      void comprimirImagem(arquivo).then(concluir);
    });
    // Android nem sempre dispara "cancel"; o foco de volta sinaliza desistência.
    window.addEventListener(
      'focus',
      () => window.setTimeout(() => concluir(null), 1500),
      { once: true },
    );

    document.body.appendChild(input);
    input.click();
  });
}

/** Data URL de imagem é aceita pelo servidor só até 8 MB; aqui o teto é bem menor. */
export function tamanhoDataUrl(dataUrl: string): number {
  const virgula = dataUrl.indexOf(',');
  const base64 = virgula >= 0 ? dataUrl.slice(virgula + 1) : dataUrl;
  return Math.floor((base64.length * 3) / 4);
}
