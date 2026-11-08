/** Conexão com o servidor MAPOS + login (com suporte a verificação em duas etapas). */

import { useState } from 'react';
import type { FormEvent } from 'react';
import { useApp } from '../lib/store';
import { DEFAULT_SERVER_URL, detectSameOrigin, normalizeServerUrl } from '../lib/config';
import { Alert, Button, Field, Input } from '../components/ui';

export function LoginScreen() {
  const { serverUrl, connect, signIn, error, twoFactor, clearError, brand } = useApp();
  const [url, setUrl] = useState(serverUrl ?? detectSameOrigin() ?? '');
  const [step, setStep] = useState<'server' | 'credentials'>(serverUrl ? 'credentials' : 'server');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [codigo, setCodigo] = useState('');
  const [busy, setBusy] = useState(false);
  const [localError, setLocalError] = useState<string | null>(null);

  const submitServer = async (event: FormEvent) => {
    event.preventDefault();
    const normalized = normalizeServerUrl(url);
    if (!normalized) {
      setLocalError('Informe o endereço do seu MAPOS.');
      return;
    }
    setLocalError(null);
    setBusy(true);
    try {
      await connect(normalized);
      setStep('credentials');
    } finally {
      setBusy(false);
    }
  };

  const submitCredentials = async (event: FormEvent) => {
    event.preventDefault();
    setLocalError(null);
    setBusy(true);
    try {
      await signIn(email, password, codigo || undefined);
    } catch {
      /* a mensagem vem do contexto */
    } finally {
      setBusy(false);
    }
  };

  const message = localError ?? error;

  return (
    <div className="screen screen--plain">
      <div className="content" style={{ justifyContent: 'center', minHeight: '100dvh', maxWidth: 460, margin: '0 auto', width: '100%' }}>
        <div className="stack" style={{ alignItems: 'center', textAlign: 'center', marginBottom: 8 }}>
          <div className="avatar" style={{ width: 64, height: 64, fontSize: '1.3rem', borderRadius: 20 }}>
            {brand?.emitente?.url_logo ? (
              <img
                src={brand.emitente.url_logo}
                alt=""
                style={{ width: '100%', height: '100%', objectFit: 'contain', borderRadius: 20 }}
                onError={(e) => {
                  e.currentTarget.style.display = 'none';
                }}
              />
            ) : (
              'M'
            )}
          </div>
          <div>
            <h1 style={{ margin: 0, fontSize: '1.4rem', letterSpacing: '-0.02em' }}>{brand?.appName ?? 'MAPOS Mobile'}</h1>
            <p className="card__meta" style={{ marginTop: 4 }}>
              {step === 'server' ? 'Conecte o aplicativo à sua instalação' : 'Entre com seu usuário do sistema'}
            </p>
          </div>
        </div>

        {message ? <Alert kind="error">{message}</Alert> : null}

        {step === 'server' ? (
          <form className="stack" onSubmit={submitServer}>
            <Field label="Endereço do MAPOS" hint="Ex.: https://suaempresa.com.br/mapos">
              <Input
                type="url"
                inputMode="url"
                autoCapitalize="none"
                autoCorrect="off"
                placeholder="https://..."
                value={url}
                onChange={(e) => {
                  setUrl(e.target.value);
                  setLocalError(null);
                  clearError();
                }}
                required
              />
            </Field>
            <p className="card__meta" style={{ textAlign: 'center' }}>
              {url === DEFAULT_SERVER_URL
                ? 'Endereço padrão da sua instalação.'
                : 'Altere apenas se o seu MAPOS estiver em outro endereço.'}
            </p>
            <Button type="submit" block loading={busy}>
              Continuar
            </Button>
          </form>
        ) : (
          <form className="stack" onSubmit={submitCredentials}>
            <Field label="E-mail">
              <Input
                type="email"
                inputMode="email"
                autoCapitalize="none"
                autoComplete="username"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </Field>
            <Field label="Senha">
              <Input
                type="password"
                autoComplete="current-password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
              />
            </Field>
            {twoFactor ? (
              <Field label="Código de verificação" hint="Código do app autenticador ou de recuperação">
                <Input inputMode="numeric" autoComplete="one-time-code" value={codigo} onChange={(e) => setCodigo(e.target.value)} />
              </Field>
            ) : null}
            <Button type="submit" block loading={busy}>
              Entrar
            </Button>
            <button
              type="button"
              className="reset-button"
              onClick={() => {
                setStep('server');
                clearError();
              }}
            >
              Trocar servidor
            </button>
            <p className="card__meta" style={{ textAlign: 'center' }}>
              {serverUrl}
            </p>
          </form>
        )}
      </div>
    </div>
  );
}
