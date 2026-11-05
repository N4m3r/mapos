/** Configurações: servidor, conta, tema e informações de versão. */

import { useEffect, useState } from 'react';
import { useApp } from '../lib/store';
import { navigate } from '../lib/router';
import { Button, Card, Kv, Topbar } from '../components/ui';

type Theme = 'system' | 'light' | 'dark';
const THEME_KEY = 'mapos.theme';

function applyTheme(theme: Theme) {
  const root = document.documentElement;
  if (theme === 'system') root.removeAttribute('data-theme');
  else root.setAttribute('data-theme', theme);
  try {
    window.localStorage.setItem(THEME_KEY, theme);
  } catch {
    /* ignora */
  }
}

export function SettingsScreen() {
  const { serverUrl, session, brand, signOut, disconnect } = useApp();
  const [theme, setTheme] = useState<Theme>(() => {
    try {
      return (window.localStorage.getItem(THEME_KEY) as Theme) || 'system';
    } catch {
      return 'system';
    }
  });
  const [confirming, setConfirming] = useState(false);

  useEffect(() => {
    applyTheme(theme);
  }, [theme]);

  const granted = Object.entries(session?.permissions ?? {})
    .filter(([, value]) => value === '1')
    .map(([key]) => key);

  return (
    <div className="screen">
      <Topbar title="Configurações" onBack={() => navigate('home')} />

      <div className="content">
        <Card>
          <p className="section-title">Conta</p>
          <Kv k="Usuário" v={session?.user.nome ?? '—'} />
          <Kv k="E-mail" v={session?.user.email ?? '—'} />
          <Kv k="Servidor" v={serverUrl ?? '—'} />
          <Kv k="Empresa" v={brand?.emitente?.nome ?? brand?.appName ?? '—'} />
        </Card>

        <Card>
          <p className="section-title">Tema</p>
          <div className="chips" style={{ marginTop: 8 }}>
            {(['system', 'light', 'dark'] as const).map((option) => (
              <button
                key={option}
                type="button"
                className={`chip${theme === option ? ' chip--active' : ''}`}
                onClick={() => setTheme(option)}
              >
                {option === 'system' ? 'Automático' : option === 'light' ? 'Claro' : 'Escuro'}
              </button>
            ))}
          </div>
        </Card>

        <Card>
          <p className="section-title">Permissões ({granted.length})</p>
          <p className="card__meta" style={{ marginTop: 6, overflowWrap: 'anywhere' }}>
            {granted.length ? granted.join(' · ') : 'Nenhuma permissão informada pelo servidor.'}
          </p>
        </Card>

        <div className="stack">
          <Button variant="ghost" block onClick={signOut}>
            Sair da conta
          </Button>
          {confirming ? (
            <div className="stack">
              <div className="alert alert--error">
                Isso remove a URL e a sessão salvas neste aparelho. A instalação MAPOS não é afetada.
              </div>
              <div className="actionbar">
                <Button variant="ghost" block small onClick={() => setConfirming(false)}>
                  Cancelar
                </Button>
                <Button variant="danger" block small onClick={disconnect}>
                  Desconectar
                </Button>
              </div>
            </div>
          ) : (
            <Button variant="danger" block onClick={() => setConfirming(true)}>
              Desconectar deste servidor
            </Button>
          )}
        </div>

        <p className="card__meta" style={{ textAlign: 'center' }}>
          MAPOS Mobile · consome a API v1 da sua instalação
        </p>
      </div>
    </div>
  );
}
