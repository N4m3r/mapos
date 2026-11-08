/** Componentes de UI reutilizáveis — alvos de toque grandes, foco em mobile. */

import type { ButtonHTMLAttributes, CSSProperties, InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react';
import { useEffect } from 'react';

export function Topbar({
  title,
  subtitle,
  onBack,
  right,
  logo,
}: {
  title: string;
  subtitle?: string;
  onBack?: () => void;
  right?: ReactNode;
  /** Logo da empresa (emitente); some sozinha se a imagem não carregar. */
  logo?: string | null;
}) {
  return (
    <header className="topbar">
      {onBack ? (
        <button type="button" className="icon-btn" onClick={onBack} aria-label="Voltar">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M15 18l-6-6 6-6" />
          </svg>
        </button>
      ) : null}
      {logo ? (
        <img
          src={logo}
          alt=""
          className="topbar__logo"
          onError={(e) => {
            e.currentTarget.style.display = 'none';
          }}
        />
      ) : null}
      <div className="topbar__grow">
        <h1 className="topbar__title">{title}</h1>
        {subtitle ? <p className="topbar__sub">{subtitle}</p> : null}
      </div>
      {right}
    </header>
  );
}

export function Button({
  variant = 'primary',
  block,
  small,
  loading,
  children,
  ...rest
}: ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: 'primary' | 'ghost' | 'danger';
  block?: boolean;
  small?: boolean;
  loading?: boolean;
}) {
  const classes = ['btn'];
  if (variant === 'ghost') classes.push('btn--ghost');
  if (variant === 'danger') classes.push('btn--danger');
  if (block) classes.push('btn--block');
  if (small) classes.push('btn--sm');

  return (
    <button type="button" className={classes.join(' ')} disabled={loading || rest.disabled} {...rest}>
      {loading ? <span className="spinner" aria-hidden /> : null}
      {children}
    </button>
  );
}

export function Field({
  label,
  hint,
  children,
}: {
  label: string;
  hint?: string;
  children: ReactNode;
}) {
  return (
    <label className="field">
      <span className="field__label">{label}</span>
      {children}
      {hint ? <span className="field__label" style={{ fontWeight: 400 }}>{hint}</span> : null}
    </label>
  );
}

export function Input(props: InputHTMLAttributes<HTMLInputElement>) {
  return <input className="input" {...props} />;
}

export function Textarea(props: TextareaHTMLAttributes<HTMLTextAreaElement>) {
  return <textarea className="textarea" {...props} />;
}

export function Select(props: SelectHTMLAttributes<HTMLSelectElement>) {
  return <select className="select" {...props} />;
}

export function Card({
  children,
  onClick,
  className = '',
  style,
}: {
  children: ReactNode;
  onClick?: () => void;
  className?: string;
  style?: CSSProperties;
}) {
  if (onClick) {
    return (
      <button type="button" className={`card card--tap ${className}`} style={style} onClick={onClick}>
        {children}
      </button>
    );
  }
  return (
    <div className={`card ${className}`} style={style}>
      {children}
    </div>
  );
}

export function Badge({ children, color }: { children: ReactNode; color?: string }) {
  return (
    <span className="badge badge--dot" style={color ? { background: `${color}22`, color } : undefined}>
      {children}
    </span>
  );
}

export function Empty({ title, message, action }: { title: string; message?: string; action?: ReactNode }) {
  return (
    <div className="empty">
      <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round">
        <path d="M4 7h16M4 12h16M4 17h10" />
      </svg>
      <p className="empty__title">{title}</p>
      {message ? <p style={{ margin: 0, fontSize: '0.85rem' }}>{message}</p> : null}
      {action}
    </div>
  );
}

export function Alert({ kind = 'error', children }: { kind?: 'error' | 'ok' | 'info'; children: ReactNode }) {
  return <div className={`alert alert--${kind}`}>{children}</div>;
}

export function SkeletonList({ count = 3 }: { count?: number }) {
  return (
    <div className="stack" aria-busy="true">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="skeleton" />
      ))}
    </div>
  );
}

export function Kv({ k, v }: { k: string; v: ReactNode }) {
  return (
    <div className="kv">
      <span className="kv__k">{k}</span>
      <span className="kv__v">{v}</span>
    </div>
  );
}

/** Abas em pílula com contador opcional (ex.: Hoje 3). */
export function Segmented<T extends string>({
  items,
  value,
  onChange,
  label,
}: {
  items: ReadonlyArray<{ key: T; label: string; count?: number }>;
  value: T;
  onChange: (key: T) => void;
  label?: string;
}) {
  return (
    <div className="chips" role="tablist" aria-label={label}>
      {items.map((item) => (
        <button
          key={item.key}
          type="button"
          role="tab"
          aria-selected={value === item.key}
          className={`chip${value === item.key ? ' chip--active' : ''}`}
          onClick={() => onChange(item.key)}
        >
          {item.label}
          {typeof item.count === 'number' ? <span className="chip__count">{item.count}</span> : null}
        </button>
      ))}
    </div>
  );
}

/** Barra de progresso 0..100 (etapas do projeto). */
export function Progress({ value }: { value: number | string | null | undefined }) {
  const pct = Math.max(0, Math.min(100, Math.round(Number(value) || 0)));
  return (
    <div
      className="progress"
      role="progressbar"
      aria-valuenow={pct}
      aria-valuemin={0}
      aria-valuemax={100}
    >
      <span className="progress__bar" style={{ width: `${pct}%` }} />
    </div>
  );
}

/** Painel que sobe do rodapé — ações de campo sem sair da tela. */
export function Sheet({
  open,
  title,
  onClose,
  children,
  footer,
}: {
  open: boolean;
  title: string;
  onClose: () => void;
  children: ReactNode;
  footer?: ReactNode;
}) {
  useEffect(() => {
    if (!open) return;
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onClose();
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [open, onClose]);

  if (!open) return null;

  return (
    <div className="sheet" role="dialog" aria-modal="true" aria-label={title}>
      <button type="button" className="sheet__backdrop" aria-label="Fechar" onClick={onClose} />
      <div className="sheet__panel">
        <div className="sheet__head">
          <h2 className="sheet__title">{title}</h2>
          <button type="button" className="icon-btn icon-btn--sm" onClick={onClose} aria-label="Fechar">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round">
              <path d="M6 6l12 12M18 6L6 18" />
            </svg>
          </button>
        </div>
        <div className="sheet__body">{children}</div>
        {footer ? <div className="sheet__foot">{footer}</div> : null}
      </div>
    </div>
  );
}

/** Botão flutuante de ação primária. */
export function Fab({ onClick, label, children }: { onClick: () => void; label: string; children?: ReactNode }) {
  return (
    <button type="button" className="fab" onClick={onClick} aria-label={label}>
      {children ?? (
        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round">
          <path d="M12 5v14M5 12h14" />
        </svg>
      )}
    </button>
  );
}

/** Selo de status com a classe visual do servidor (agendada, atrasada, concluída...). */
export function StatusPill({ text, tone }: { text: string; tone?: string | null }) {
  return <span className={`pill pill--${tone || 'neutro'}`}>{text}</span>;
}
