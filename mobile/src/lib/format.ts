/** Formatação pt-BR e utilitários de apresentação. */

import type { Os } from './types';

const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
const int = new Intl.NumberFormat('pt-BR');

export function money(value: string | number | null | undefined): string {
  const parsed = typeof value === 'number' ? value : Number.parseFloat(String(value ?? '0').replace(',', '.'));
  return brl.format(Number.isFinite(parsed) ? parsed : 0);
}

export function count(value: string | number | null | undefined): string {
  const parsed = typeof value === 'number' ? value : Number.parseInt(String(value ?? '0'), 10);
  return int.format(Number.isFinite(parsed) ? parsed : 0);
}

/** MySQL "YYYY-MM-DD" ou "YYYY-MM-DD HH:mm:ss" -> "DD/MM/AAAA". */
export function dateBr(value: string | null | undefined): string {
  if (!value) return '—';
  const iso = String(value).slice(0, 10);
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso);
  if (!match) return String(value);
  const [, y, m, d] = match;
  return `${d}/${m}/${y}`;
}

export function dateTimeBr(value: string | null | undefined): string {
  if (!value) return '—';
  const [datePart, timePart] = String(value).split(' ');
  const date = dateBr(datePart);
  return timePart ? `${date} ${timePart.slice(0, 5)}` : date;
}

export function todayIso(): string {
  const now = new Date();
  const offset = now.getTimezoneOffset() * 60000;
  return new Date(now.getTime() - offset).toISOString().slice(0, 10);
}

export function isoToBr(iso: string): string {
  return dateBr(iso);
}

/**
 * Converte "YYYY-MM-DD" no formato que a API espera (dd/mm/aaaa).
 * Vazio/ inválido cai em hoje; as datas nunca podem ser inválidas porque o
 * controller faz explode('/') e usa os índices [2][1][0].
 */
export function toApiDate(value: string | null | undefined): string {
  const iso = value ? String(value).slice(0, 10) : '';
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso);
  if (match) {
    const [, y, m, d] = match;
    return `${d}/${m}/${y}`;
  }
  const today = todayIso();
  const [, ty, tm, td] = /^(\d{4})-(\d{2})-(\d{2})$/.exec(today)!;
  return `${td}/${tm}/${ty}`;
}

/** Cor por status, alinhada ao dashboard PHP (ApiController::calendario_get). */
export const STATUS_COLORS: Record<string, string> = {
  Aberto: '#00cd00',
  Negociação: '#AEB404',
  'Em Andamento': '#436eee',
  Orçamento: '#CDB380',
  Cancelado: '#CD0000',
  Finalizado: '#256a56',
  Faturado: '#B266FF',
  'Aguardando Peças': '#FF7F00',
  Aprovado: '#0e7490',
  'Não Realizado': '#6b7280',
};

export function statusColor(status: string | undefined): string {
  return (status && STATUS_COLORS[status]) || '#E0E4CC';
}

/** Total exibível da OS: usa calcTotal quando presente, senão produtos + serviços. */
export function osTotal(os: Os): number {
  if (os.calcTotal !== undefined && os.calcTotal !== null) return Number(os.calcTotal) || 0;
  const produtos = os.produtos?.reduce((sum, p) => sum + Number(p.preco) * Number(p.quantidade), 0) ?? 0;
  const servicos = os.servicos?.reduce((sum, s) => sum + Number(s.preco) * Number(s.quantidade), 0) ?? 0;
  const fallback = Number(os.totalProdutos ?? 0) + Number(os.totalServicos ?? 0);
  return produtos + servicos || fallback;
}

/** Minutos -> "7h05" (ou "0min"); negativo ganha o sinal na frente. */
export function minutesToHm(min: number | string | null | undefined): string {
  const total = Math.round(Number(min) || 0);
  const sinal = total < 0 ? '-' : '';
  const abs = Math.abs(total);
  const horas = Math.floor(abs / 60);
  const mins = abs % 60;
  if (!horas) return `${sinal}${mins}min`;
  return `${sinal}${horas}h${String(mins).padStart(2, '0')}`;
}

/** "2026-11" -> "novembro/2026" (rótulo de competência). */
const MESES = [
  'janeiro',
  'fevereiro',
  'março',
  'abril',
  'maio',
  'junho',
  'julho',
  'agosto',
  'setembro',
  'outubro',
  'novembro',
  'dezembro',
];

export function competenciaLabel(value: string | null | undefined): string {
  const match = /^(\d{4})-(\d{2})$/.exec(String(value ?? ''));
  if (!match) return String(value ?? '—');
  const mes = MESES[Number(match[2]) - 1];
  return mes ? `${mes}/${match[1]}` : String(value);
}

export function competenciaAtual(): string {
  return todayIso().slice(0, 7);
}

export function shiftCompetencia(value: string, delta: number): string {
  const match = /^(\d{4})-(\d{2})$/.exec(value);
  const hoje = new Date();
  const ano = match ? Number(match[1]) : hoje.getFullYear();
  const mes = match ? Number(match[2]) - 1 : hoje.getMonth();
  const d = new Date(ano, mes + delta, 1);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
}

export const DIAS_SEMANA = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];

/** "2026-11-04" -> "qua, 04/11". */
export function diaCurto(iso: string | null | undefined): string {
  if (!iso) return '—';
  const d = String(iso).slice(0, 10);
  const [y, m, day] = d.split('-');
  const date = new Date(`${y}-${m}-${day}T12:00:00`);
  const nome = DIAS_SEMANA[date.getDay()] ?? '';
  return `${nome}, ${day}/${m}`;
}

/** Data/hora de campo: hoje vira "Hoje às 14:30", demais "04/11 às 14:30". */
export function quandoVisita(iso: string | null | undefined): string {
  if (!iso) return 'Sem data';
  const texto = String(iso);
  const dia = texto.slice(0, 10);
  const hora = texto.length >= 16 ? texto.slice(11, 16) : null;
  const base = dia === todayIso() ? 'Hoje' : dateBr(dia);
  return hora && hora !== '00:00' ? `${base} às ${hora}` : base;
}

export function initials(name: string | undefined): string {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/).slice(0, 2);
  return parts.map((p) => p[0]?.toUpperCase() ?? '').join('') || '?';
}

export function digits(value: string): string {
  return value.replace(/\D/g, '');
}

export function telHref(value: string | undefined): string | null {
  if (!value) return null;
  const clean = digits(value);
  return clean.length >= 8 ? `tel:${clean}` : null;
}

export function whatsHref(value: string | undefined, text?: string): string | null {
  if (!value) return null;
  let clean = digits(value);
  if (clean.length < 10) return null;
  if (clean.length <= 11) clean = `55${clean}`;
  const suffix = text ? `?text=${encodeURIComponent(text)}` : '';
  return `https://wa.me/${clean}${suffix}`;
}
