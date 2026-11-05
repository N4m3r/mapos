/**
 * Imagens dos endpoints autenticados (fotos do atendimento, foto de perfil).
 *
 * O <img src> não envia o Bearer token, então a imagem é baixada por fetch e
 * vira um object URL enquanto o componente estiver montado.
 */

import { useEffect, useState } from 'react';
import { requestBlob } from '../lib/http';
import { useApi } from '../lib/store';

export function useImageUrl(
  baseUrl: string,
  token: string,
  path: string | null | undefined,
): string | null {
  const [url, setUrl] = useState<string | null>(null);

  useEffect(() => {
    if (!path) {
      setUrl(null);
      return;
    }
    let ativo = true;
    let criado: string | null = null;

    requestBlob(baseUrl, path, { token })
      .then((blob) => {
        if (!ativo) return;
        criado = URL.createObjectURL(blob);
        setUrl(criado);
      })
      .catch(() => {
        if (ativo) setUrl(null);
      });

    return () => {
      ativo = false;
      if (criado) URL.revokeObjectURL(criado);
      setUrl(null);
    };
  }, [baseUrl, token, path]);

  return url;
}

/** Miniatura clicável de uma foto do atendimento. */
export function ThumbAberta({
  baseUrl,
  token,
  path,
  etapa,
  onOpen,
}: {
  baseUrl: string;
  token: string;
  path: string;
  etapa?: string | null;
  onOpen?: () => void;
}) {
  const url = useImageUrl(baseUrl, token, path);
  return (
    <button type="button" className="thumb" onClick={onOpen} aria-label={`Foto ${etapa ?? ''}`}>
      {url ? <img src={url} alt="" loading="lazy" /> : <span className="spinner" style={{ margin: 30 }} />}
      {etapa ? <span className="thumb__etapa">{etapa}</span> : null}
    </button>
  );
}

export interface FotoItem {
  path: string;
  etapa?: string | null;
  titulo?: string;
}

/** Lista de fotos com ampliação por cima da tela. */
export function Fotos({ itens }: { itens: FotoItem[] }) {
  const { baseUrl, token } = useApi();
  const [aberta, setAberta] = useState<FotoItem | null>(null);

  if (!itens.length) return null;

  return (
    <>
      <div className="thumbs">
        {itens.map((item, i) => (
          <ThumbAberta
            key={`${item.path}-${i}`}
            baseUrl={baseUrl}
            token={token}
            path={item.path}
            etapa={item.etapa}
            onOpen={() => setAberta(item)}
          />
        ))}
      </div>
      {aberta ? (
        <FotoAmpliada
          baseUrl={baseUrl}
          token={token}
          path={aberta.path}
          titulo={aberta.titulo ?? aberta.etapa ?? 'Foto'}
          onClose={() => setAberta(null)}
        />
      ) : null}
    </>
  );
}

/** Amplia a foto por cima da tela. */
export function FotoAmpliada({
  baseUrl,
  token,
  path,
  titulo,
  onClose,
}: {
  baseUrl: string;
  token: string;
  path: string;
  titulo?: string;
  onClose: () => void;
}) {
  const url = useImageUrl(baseUrl, token, path);
  return (
    <div className="sheet" role="dialog" aria-modal="true" aria-label={titulo ?? 'Foto'}>
      <button type="button" className="sheet__backdrop" aria-label="Fechar" onClick={onClose} />
      <div className="sheet__panel" style={{ padding: 12 }}>
        <div className="row row--between" style={{ marginBottom: 8 }}>
          <strong>{titulo ?? 'Foto'}</strong>
          <button type="button" className="reset-button" onClick={onClose}>
            Fechar
          </button>
        </div>
        {url ? (
          <img src={url} alt={titulo ?? ''} style={{ width: '100%', borderRadius: 12, display: 'block' }} />
        ) : (
          <div className="empty">
            <span className="spinner" />
            <p className="card__meta">Carregando imagem…</p>
          </div>
        )}
      </div>
    </div>
  );
}
