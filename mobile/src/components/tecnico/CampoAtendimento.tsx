/**
 * Ações de campo da OS que ficam fora do check-in/check-out, como na Área do
 * Técnico web: "Estou a caminho" (aviso ao cliente por WhatsApp) e a
 * assinatura do solicitante nas atividades não programadas — no aparelho,
 * por link para o solicitante assinar no celular dele ou por e-mail.
 */

import { useCallback, useEffect, useRef, useState } from 'react';
import * as tec from '../../lib/api-tecnico';
import { useApi } from '../../lib/store';
import { Assinatura, lerAssinatura } from '../Assinatura';
import { Alert, Button, Card, Field, Input, Sheet } from '../ui';
import type { AssinaturaSolicitante } from '../../lib/types-tecnico';

const SITUACAO: Record<string, string> = {
  pendente: 'Link enviado, aguardando o solicitante',
  aceito: 'Aprovado pelo link',
  recusado: 'Recusado pelo solicitante',
  expirado: 'Link expirado',
};

export function CampoAtendimento({
  osId,
  status,
  emAtendimento,
  naoProgramada,
}: {
  osId: number | string;
  status: string;
  emAtendimento: boolean;
  naoProgramada: boolean;
}) {
  const { baseUrl, token, handle } = useApi();
  const [msg, setMsg] = useState<{ kind: 'ok' | 'error'; texto: string } | null>(null);
  const [busy, setBusy] = useState<string | null>(null);
  const [info, setInfo] = useState<AssinaturaSolicitante | null>(null);
  const [painel, setPainel] = useState<null | 'assinar' | 'email'>(null);
  const [link, setLink] = useState<{ link: string; expira?: string } | null>(null);
  const [nome, setNome] = useState('');
  const [documento, setDocumento] = useState('');
  const [email, setEmail] = useState('');
  const [temTraco, setTemTraco] = useState(false);
  const canvas = useRef<HTMLCanvasElement | null>(null);

  const finalizada = ['Finalizado', 'Faturado', 'Cancelado'].includes(status);

  const carregar = useCallback(async () => {
    if (!naoProgramada) return;
    try {
      const r = await handle(() => tec.assinaturaSolicitante(baseUrl, token, osId));
      setInfo(r);
      if (r.link && r.situacao === 'pendente') setLink({ link: r.link });
      if (r.email_cliente) setEmail((atual) => atual || r.email_cliente || '');
    } catch {
      setInfo(null);
    }
  }, [naoProgramada, handle, baseUrl, token, osId]);

  useEffect(() => {
    void carregar();
  }, [carregar]);

  const rodar = async (chave: string, fn: () => Promise<string>) => {
    setBusy(chave);
    setMsg(null);
    try {
      setMsg({ kind: 'ok', texto: await handle(fn) });
      return true;
    } catch (err) {
      setMsg({ kind: 'error', texto: (err as Error).message });
      return false;
    } finally {
      setBusy(null);
    }
  };

  const avisarACaminho = () =>
    rodar('caminho', async () => (await tec.aCaminho(baseUrl, token, osId)).message || 'Cliente avisado.');

  const salvarAssinatura = async () => {
    const imagem = lerAssinatura(canvas.current);
    if (!imagem) {
      setMsg({ kind: 'error', texto: 'Assinatura em branco. Peça para o solicitante assinar no quadro.' });
      return;
    }
    const ok = await rodar('assinar', async () => {
      await tec.salvarAssinaturaSolicitante(baseUrl, token, osId, { assinatura: imagem, nome, documento });
      return 'Assinatura registrada com sucesso!';
    });
    if (ok) {
      setPainel(null);
      void carregar();
    }
  };

  const gerarLink = () =>
    rodar('link', async () => {
      const r = await tec.gerarLinkSolicitante(baseUrl, token, osId);
      setLink(r);
      return `Link gerado. Vale até ${r.expira}.`;
    });

  const enviarEmail = async () => {
    const ok = await rodar('email', async () => {
      const r = await tec.enviarLinkSolicitanteEmail(baseUrl, token, osId, email.trim());
      if (r.result?.link) setLink({ link: r.result.link });
      return r.message || 'E-mail enfileirado.';
    });
    if (ok) setPainel(null);
  };

  const copiar = async () => {
    if (!link) return;
    try {
      await navigator.clipboard.writeText(link.link);
      setMsg({ kind: 'ok', texto: 'Link copiado.' });
    } catch {
      setMsg({ kind: 'error', texto: 'Não deu para copiar. Segure o link para copiar.' });
    }
  };

  const mostrarCaminho = !finalizada && !emAtendimento;
  const mostrarAssinatura = naoProgramada && info !== null;
  if (!mostrarCaminho && !mostrarAssinatura) return null;

  return (
    <>
      {msg ? <Alert kind={msg.kind}>{msg.texto}</Alert> : null}

      {mostrarCaminho ? (
        <Button variant="ghost" block loading={busy === 'caminho'} onClick={() => void avisarACaminho()}>
          Estou a caminho (avisar o cliente)
        </Button>
      ) : null}

      {mostrarAssinatura && info ? (
        <Card>
          <p className="section-title">Assinatura do solicitante</p>
          {info.assinado ? (
            <p className="card__meta">
              Assinado
              {info.assinaturas
                .filter((a) => a.tipo === 'solicitante' || a.tipo === 'cliente_aceite')
                .map((a) => (a.nome ? ` por ${a.nome}` : ''))
                .join('')}
              .
            </p>
          ) : (
            <p className="card__meta">
              {SITUACAO[info.situacao] ?? 'Atividade não programada: colete a assinatura de quem pediu o serviço.'}
            </p>
          )}

          {!info.assinado ? (
            <div className="stack" style={{ marginTop: 10 }}>
              <Button block onClick={() => setPainel('assinar')}>
                Assinar no aparelho
              </Button>
              {info.suportado ? (
                <>
                  <Button variant="ghost" block loading={busy === 'link'} onClick={() => void gerarLink()}>
                    {link ? 'Gerar novo link' : 'Gerar link para o solicitante'}
                  </Button>
                  <Button variant="ghost" block onClick={() => setPainel('email')}>
                    Enviar link por e-mail
                  </Button>
                </>
              ) : null}
            </div>
          ) : null}

          {link && !info.assinado ? (
            <div style={{ marginTop: 10 }}>
              <p className="card__meta" style={{ wordBreak: 'break-all' }}>
                {link.link}
              </p>
              <div className="actionbar">
                <button type="button" className="btn btn--ghost btn--sm" onClick={() => void copiar()}>
                  Copiar
                </button>
                <a
                  className="btn btn--ghost btn--sm"
                  href={`https://wa.me/?text=${encodeURIComponent(`Assine o serviço da OS ${String(osId).padStart(4, '0')}: ${link.link}`)}`}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  WhatsApp
                </a>
              </div>
            </div>
          ) : null}
        </Card>
      ) : null}

      <Sheet
        open={painel === 'assinar'}
        title="Assinatura do solicitante"
        onClose={() => setPainel(null)}
        footer={
          <Button block loading={busy === 'assinar'} disabled={!temTraco} onClick={() => void salvarAssinatura()}>
            Salvar assinatura
          </Button>
        }
      >
        <Field label="Nome de quem assina">
          <Input value={nome} onChange={(e) => setNome(e.target.value)} maxLength={120} />
        </Field>
        <Field label="Documento (opcional)">
          <Input value={documento} onChange={(e) => setDocumento(e.target.value)} maxLength={30} />
        </Field>
        <Assinatura canvasRef={canvas} onChange={setTemTraco} rotulo="Assinatura do solicitante" />
      </Sheet>

      <Sheet
        open={painel === 'email'}
        title="Enviar link por e-mail"
        onClose={() => setPainel(null)}
        footer={
          <Button block loading={busy === 'email'} disabled={!email.trim()} onClick={() => void enviarEmail()}>
            Enviar
          </Button>
        }
      >
        <Field label="E-mail do solicitante">
          <Input type="email" inputMode="email" value={email} onChange={(e) => setEmail(e.target.value)} />
        </Field>
      </Sheet>
    </>
  );
}
