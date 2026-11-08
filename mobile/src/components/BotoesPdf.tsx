/** Botões de PDF (OS, projeto): abrir para imprimir/salvar e compartilhar. */

import { useState } from 'react';
import { isNativeApp } from '../lib/config';
import { abrirNoAparelho, baixarBlob, visualizarBlob } from '../lib/geo';
import { useApi } from '../lib/store';
import { Alert, Button } from './ui';

export function BotoesPdf({ carregar, nome }: { carregar: () => Promise<Blob>; nome: string }) {
  const { handle } = useApi();
  const [ocupado, setOcupado] = useState(false);
  const [erro, setErro] = useState<string | null>(null);

  const executar = async (acao: 'abrir' | 'compartilhar') => {
    setOcupado(true);
    setErro(null);
    try {
      const blob = await handle(carregar);
      if (acao === 'abrir') visualizarBlob(blob, nome);
      else if (isNativeApp()) await abrirNoAparelho(blob, nome, true);
      else baixarBlob(blob, nome);
    } catch (err) {
      setErro((err as Error).message);
    } finally {
      setOcupado(false);
    }
  };

  return (
    <div className="stack" style={{ gap: 8 }}>
      {erro ? <Alert kind="error">{erro}</Alert> : null}
      <div style={{ display: 'flex', gap: 8 }}>
        <Button variant="ghost" block disabled={ocupado} onClick={() => void executar('abrir')}>
          {ocupado ? 'Gerando PDF…' : 'Imprimir / PDF'}
        </Button>
        <Button variant="ghost" block disabled={ocupado} onClick={() => void executar('compartilhar')}>
          {isNativeApp() ? 'Compartilhar' : 'Baixar'}
        </Button>
      </div>
    </div>
  );
}
