/** Roteador mínimo baseado em hash — sem dependências, funciona em qualquer subpasta. */

import { useEffect, useState } from 'react';

export type Route =
  | { name: 'login' }
  | { name: 'home' }
  | { name: 'os-list' }
  | { name: 'os-new' }
  | { name: 'os-detail'; id: string }
  | { name: 'os-edit'; id: string }
  | { name: 'clientes' }
  | { name: 'cliente-detail'; id: string }
  | { name: 'cliente-new' }
  | { name: 'agenda' }
  | { name: 'settings' }
  // Área do técnico
  | { name: 'tec-home' }
  | { name: 'tec-os'; aba: string }
  | { name: 'tec-atendimento'; id: string }
  | { name: 'tec-atividade' }
  | { name: 'tec-nao-realizadas' }
  | { name: 'tec-projetos' }
  | { name: 'tec-projeto'; id: string }
  | { name: 'tec-ponto'; obra: string; id: string }
  | { name: 'tec-camera'; id: string }
  | { name: 'tec-fila' }
  | { name: 'tec-perfil' }
  // Área do colaborador
  | { name: 'col-home' }
  | { name: 'col-ponto' }
  | { name: 'col-espelho' }
  | { name: 'col-holerite' }
  | { name: 'col-solicitacoes' }
  | { name: 'col-ausencias' }
  | { name: 'col-dados' };

const ABAS_TECNICO = ['hoje', 'proximos', 'atrasadas', 'concluidas', 'todas'];

export function parseRoute(hash: string): Route {
  const clean = hash.replace(/^#\/?/, '').replace(/\/+$/, '');
  if (!clean) return { name: 'home' };
  const parts = clean.split('/');
  const head = parts[0] ?? '';
  const second = parts[1] ?? '';
  const third = parts[2] ?? '';
  const quarto = parts[3] ?? '';

  switch (head) {
    case 'login':
      return { name: 'login' };
    case 'os':
      if (!second) return { name: 'os-list' };
      if (second === 'nova') return { name: 'os-new' };
      return { name: 'os-detail', id: second };
    case 'os-editar':
      return second ? { name: 'os-edit', id: second } : { name: 'os-list' };
    case 'clientes':
      if (!second) return { name: 'clientes' };
      if (second === 'novo') return { name: 'cliente-new' };
      return { name: 'cliente-detail', id: second };
    case 'agenda':
      return { name: 'agenda' };
    case 'config':
      return { name: 'settings' };
    case 'home':
      return { name: 'home' };
    case 'tecnico':
      return parseTecnico(second, third, quarto);
    case 'colaborador':
      return parseColaborador(second);
    default:
      return { name: 'home' };
  }
}

function parseTecnico(second: string, third: string, quarto: string): Route {
  switch (second) {
    case '':
      return { name: 'tec-home' };
    case 'os':
      return { name: 'tec-os', aba: ABAS_TECNICO.includes(third) ? third : 'hoje' };
    case 'atendimento':
      return { name: 'tec-atendimento', id: third };
    case 'atividade':
      return { name: 'tec-atividade' };
    case 'nao-realizadas':
      return { name: 'tec-nao-realizadas' };
    case 'projetos':
      return { name: 'tec-projetos' };
    case 'projeto':
      return { name: 'tec-projeto', id: third };
    case 'ponto':
      // tecnico/ponto/{obra}/{ponto}
      return { name: 'tec-ponto', obra: third, id: quarto };
    case 'camera':
      // tecnico/camera/{obra} — configuração de câmeras e NVR
      return { name: 'tec-camera', id: third };
    case 'fila':
      return { name: 'tec-fila' };
    case 'perfil':
      return { name: 'tec-perfil' };
    default:
      return { name: 'tec-home' };
  }
}

function parseColaborador(second: string): Route {
  switch (second) {
    case '':
      return { name: 'col-home' };
    case 'ponto':
      return { name: 'col-ponto' };
    case 'espelho':
      return { name: 'col-espelho' };
    case 'holerite':
      return { name: 'col-holerite' };
    case 'solicitacoes':
      return { name: 'col-solicitacoes' };
    case 'ausencias':
      return { name: 'col-ausencias' };
    case 'dados':
      return { name: 'col-dados' };
    default:
      return { name: 'col-home' };
  }
}

export function navigate(path: string): void {
  const target = path.startsWith('#') ? path : `#/${path.replace(/^\/+/, '')}`;
  if (window.location.hash === target) return;
  window.location.hash = target;
}

export function useRoute(): Route {
  const [route, setRoute] = useState<Route>(() => parseRoute(window.location.hash));

  useEffect(() => {
    const onChange = () => setRoute(parseRoute(window.location.hash));
    window.addEventListener('hashchange', onChange);
    return () => window.removeEventListener('hashchange', onChange);
  }, []);

  return route;
}
