/**
 * Área do usuário no app: o mesmo login serve ao painel administrativo, ao
 * técnico e ao colaborador. A regra espelha Login::concluirLogin da versão web
 * — sem vOs, quem tem vTecnicoDashboard só entra na área do técnico; sem
 * vTecnicoDashboard e sem vOs, quem tem vAreaColaborador só entra no RH.
 */

import { can, type Session } from './session';

export type Area = 'admin' | 'tecnico' | 'colaborador';

export function detectArea(session: Session | null): Area {
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
