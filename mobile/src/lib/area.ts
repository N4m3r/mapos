/**
 * Área do usuário no app: o mesmo login serve ao painel administrativo, ao
 * técnico e ao colaborador. A regra espelha Login::concluirLogin da versão web
 * — sem vOs, quem tem vTecnicoDashboard só entra na área do técnico; sem
 * vTecnicoDashboard e sem vOs, quem tem vAreaColaborador só entra no RH.
 */

import { can, type Session } from './session';

export type Area = 'admin' | 'tecnico' | 'colaborador';

/**
 * Áreas que o usuário pode abrir. Quem tem mais de uma (ex.: técnico que
 * também é colaborador do RH) escolhe pelo seletor das telas iniciais.
 */
export function areasDisponiveis(session: Session | null): Area[] {
  const lista: Area[] = [];
  if (can(session, 'vOs')) lista.push('admin');
  if (can(session, 'vTecnicoDashboard')) lista.push('tecnico');
  if (can(session, 'vAreaColaborador')) lista.push('colaborador');
  return lista;
}

export const ROTULO_AREA: Record<Area, string> = {
  admin: 'Sistema',
  tecnico: 'Técnico',
  colaborador: 'RH',
};

export function detectArea(session: Session | null, preferida?: Area | null): Area {
  if (preferida && areasDisponiveis(session).includes(preferida)) return preferida;

  const hasTecnico = can(session, 'vTecnicoDashboard');
  const hasSistema = can(session, 'vOs');
  const hasColaborador = can(session, 'vAreaColaborador');

  // Mesma ordem do Login web: quem enxerga OS usa o sistema; técnico só
  // quando NÃO tem vOs; colaborador só quando não é nenhum dos dois.
  if (hasSistema) return 'admin';
  if (hasTecnico) return 'tecnico';
  if (hasColaborador) return 'colaborador';
  // Sem permissão conhecida: deixa o usuário ver o painel e o servidor
  // responde o que ele pode fazer.
  return 'admin';
}

/** Rota inicial de cada área. */
export function homePathFor(area: Area): string {
  switch (area) {
    case 'tecnico':
      return 'tecnico';
    case 'colaborador':
      return 'colaborador';
    default:
      return 'home';
  }
}
