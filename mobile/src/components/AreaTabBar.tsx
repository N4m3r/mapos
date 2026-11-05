/** Barra inferior de cada área — o app mostra só as abas do perfil logado. */

import type { ReactNode } from 'react';
import { navigate, type Route } from '../lib/router';
import type { Area } from '../lib/area';

interface Tab {
  path: string;
  label: string;
  names: Route['name'][];
  /** Permissão exigida pelo endpoint da aba; sem ela a aba não aparece. */
  permission?: string;
  icon: ReactNode;
}

const stroke = {
  fill: 'none',
  stroke: 'currentColor',
  strokeWidth: 1.9,
  strokeLinecap: 'round',
  strokeLinejoin: 'round',
} as const;

const ADMIN_TABS: Tab[] = [
  {
    path: 'home',
    label: 'Início',
    names: ['home'],
    icon: (
      <svg viewBox="0 0 24 24" {...stroke}>
        <path d="M3 10.5 12 3l9 7.5" />
        <path d="M5 9.5V21h14V9.5" />
      </svg>
    ),
  },
  {
    path: 'os',
    label: 'OS',
    names: ['os-list', 'os-detail', 'os-edit', 'os-new'],
    permission: 'vOs',
    icon: (
      <svg viewBox="0 0 24 24" {...stroke}>
        <rect x="5" y="3" width="14" height="18" rx="2.5" />
        <path d="M9 8h6M9 12h6M9 16h4" />
      </svg>
    ),
  },
  {
    path: 'clientes',
    label: 'Clientes',
    names: ['clientes', 'cliente-detail', 'cliente-new'],
    permission: 'vCliente',
    icon: (
      <svg viewBox="0 0 24 24" {...stroke}>
        <circle cx="12" cy="8" r="3.6" />
        <path d="M4.5 20c.8-3.6 3.9-5.6 7.5-5.6s6.7 2 7.5 5.6" />
      </svg>
    ),
  },
  {
    path: 'agenda',
    label: 'Agenda',
    names: ['agenda'],
    permission: 'vOs',
    icon: (
      <svg viewBox="0 0 24 24" {...stroke}>
        <rect x="3.5" y="5" width="17" height="16" rx="2.5" />
        <path d="M8 3v4M16 3v4M3.5 10h17" />
      </svg>
    ),
  },
];

const TECNICO_TABS: Tab[] = [
  {
    path: 'tecnico',
    label: 'Início',
    names: ['tec-home'],
    icon: (
      <svg viewBox="0 0 24 24" {...stroke}>
        <path d="M3 10.5 12 3l9 7.5" />
        <path d="M5 9.5V21h14V9.5" />
      </svg>
    ),
  },
  {
    path: 'tecnico/os',
    label: 'Minhas OS',
    names: ['tec-os', 'tec-atendimento', 'tec-atividade', 'tec-nao-realizadas'],
    permission: 'vTecnicoOS',
    icon: (
      <svg viewBox="0 0 24 24" {...stroke}>
        <rect x="5" y="3" width="14" height="18" rx="2.5" />
        <path d="M9 8h6M9 12h6M9 16h4" />
      </svg>
    ),
  },
  {
    path: 'tecnico/projetos',
    label: 'Projetos',
    names: ['tec-projetos', 'tec-projeto', 'tec-ponto', 'tec-camera'],
    icon: (
      <svg viewBox="0 0 24 24" {...stroke}>
        <path d="M12 3v6M12 15v6M3 12h6M15 12h6" />
        <circle cx="12" cy="12" r="3.2" />
      </svg>
    ),
  },
  {
    path: 'tecnico/fila',
    label: 'Fila',
    names: ['tec-fila'],
    icon: (
      <svg viewBox="0 0 24 24" {...stroke}>
        <path d="M4 7h10l-3-3M20 17H10l3 3" />
        <path d="M4 7l16 10" opacity="0.35" />
      </svg>
    ),
  },
  {
    path: 'tecnico/perfil',
    label: 'Perfil',
    names: ['tec-perfil'],
    icon: (
      <svg viewBox="0 0 24 24" {...stroke}>
        <circle cx="12" cy="8" r="3.6" />
        <path d="M4.5 20c.8-3.6 3.9-5.6 7.5-5.6s6.7 2 7.5 5.6" />
      </svg>
    ),
  },
];

const COLABORADOR_TABS: Tab[] = [
  {
    path: 'colaborador',
    label: 'Início',
    names: ['col-home'],
    icon: (
      <svg viewBox="0 0 24 24" {...stroke}>
        <path d="M3 10.5 12 3l9 7.5" />
        <path d="M5 9.5V21h14V9.5" />
      </svg>
    ),
  },
  {
    path: 'colaborador/ponto',
    label: 'Ponto',
    names: ['col-ponto'],
    icon: (
      <svg viewBox="0 0 24 24" {...stroke}>
        <path d="M12 3a9 9 0 1 0 9 9" />
        <path d="M12 7v5l3.2 2" />
      </svg>
    ),
  },
  {
    path: 'colaborador/espelho',
    label: 'Espelho',
    names: ['col-espelho', 'col-holerite'],
    icon: (
      <svg viewBox="0 0 24 24" {...stroke}>
        <rect x="3.5" y="5" width="17" height="16" rx="2.5" />
        <path d="M8 3v4M16 3v4M3.5 10h17M8 14h3M8 17.5h6" />
      </svg>
    ),
  },
  {
    path: 'colaborador/solicitacoes',
    label: 'Solicitar',
    names: ['col-solicitacoes', 'col-ausencias', 'col-dados'],
    icon: (
      <svg viewBox="0 0 24 24" {...stroke}>
        <path d="M4 6.5h16v11H4z" />
        <path d="m4 7.5 8 6 8-6" />
      </svg>
    ),
  },
];

function tabsFor(area: Area, podeBaterPonto: boolean, can: (permission: string) => boolean): Tab[] {
  let tabs: Tab[];
  if (area === 'admin') tabs = ADMIN_TABS;
  else if (area === 'tecnico') tabs = TECNICO_TABS;
  // Colaborador sem a permissão de bater ponto não vê a aba do relógio.
  else tabs = podeBaterPonto ? COLABORADOR_TABS : COLABORADOR_TABS.filter((t) => t.label !== 'Ponto');

  // Aba cujo endpoint responde 401 de permissão só geraria erro: sem a
  // permissão, ela nem aparece. O app não pode oferecer o que o servidor nega.
  return tabs.filter((t) => !t.permission || can(t.permission));
}

export function AreaTabBar({
  area,
  active,
  podeBaterPonto = true,
  can,
}: {
  area: Area;
  active: Route['name'];
  podeBaterPonto?: boolean;
  can: (permission: string) => boolean;
}) {
  const tabs = tabsFor(area, podeBaterPonto, can);
  if (!tabs.length) return null;
  return (
    <nav className="tabbar" style={{ gridTemplateColumns: `repeat(${tabs.length}, 1fr)` }} aria-label="Navegação principal">
      {tabs.map((tab) => {
        const isActive = tab.names.includes(active);
        return (
          <button
            key={tab.path}
            type="button"
            className={`tab${isActive ? ' tab--active' : ''}`}
            onClick={() => navigate(tab.path)}
            aria-current={isActive ? 'page' : undefined}
          >
            {tab.icon}
            <span>{tab.label}</span>
          </button>
        );
      })}
    </nav>
  );
}
