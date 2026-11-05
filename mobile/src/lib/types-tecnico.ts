/** Tipos das áreas do Técnico e do Colaborador (API v1: /api/v1/tecnico e /api/v1/colaborador). */

export interface OsResumo {
  idOs: number | string;
  status: string;
  nomeCliente?: string | null;
  telefone?: string | null;
  celular?: string | null;
  endereco?: string | null;
  rua?: string | null;
  numero?: string | null;
  bairro?: string | null;
  cidade?: string | null;
  estado?: string | null;
  dataInicial?: string | null;
  data_agendamento?: string | null;
  dataEfetiva?: string | null;
  hora?: string | null;
  descricaoProduto?: string | null;
  defeito?: string | null;
  tipo_servico?: string | null;
  prioridade?: string | null;
  nao_programada?: string | number | null;
  urlRota?: string | null;
  urlWhats?: string | null;
  aba?: string | null;
  rotuloStatus?: string | null;
  statusClasse?: string | null;
}

export interface TecnicoPerfil {
  id: number;
  nome: string;
  primeiroNome: string;
  email?: string | null;
  telefone?: string | null;
  celular?: string | null;
  fotoUrl: string | null;
  cargo?: string | null;
  colaboradorVinculado?: boolean;
  iniciais?: string | null;
  equipes?: Array<{ id: number; nome: string | null; encarregado: boolean }>;
  numeros?: { mes: string; osAtendidas: number; pontosAvancados: number };
}

export interface TecnicoDashboard {
  perfil: TecnicoPerfil;
  hoje: string;
  proxima: (OsResumo & { continuar?: boolean }) | null;
  agenda: OsResumo[];
  doDia: OsResumo[];
  grupos: { hoje: number; proximos: number; atrasadas: number; concluidas: number };
  naoRealizadas: NaoRealizada[];
  qtdNaoRealizadas: number;
  filaQtd: number;
  podeCriarAtividade?: boolean;
}

export interface TecnicoOsLista {
  aba: string;
  hoje: string;
  contagens: { hoje: number; proximos: number; atrasadas: number; concluidas: number };
  ordens: OsResumo[];
}

export interface NaoRealizada {
  id: number;
  os_id: number | string;
  idOs?: number | string;
  nomeCliente?: string | null;
  telefone?: string | null;
  celular?: string | null;
  motivo?: string | null;
  observacao?: string | null;
  data_registro?: string | null;
  status_anterior?: string | null;
  descricaoProduto?: string | null;
  dataInicial?: string | null;
}

export interface MotivoNaoRealizado {
  id: number;
  nome: string;
}

export interface PrevisaoItem {
  nome: string;
  quantidade: number | string;
}

export interface HistoricoOs {
  idOs: number | string;
  dataInicial?: string | null;
  status?: string | null;
  defeito?: string | null;
}

export interface TecnicoOsDetalhe {
  os: {
    idOs: number | string;
    status: string;
    dataInicial?: string | null;
    dataFinal?: string | null;
    data_agendamento?: string | null;
    descricaoProduto?: string | null;
    defeito?: string | null;
    observacoes?: string | null;
    laudoTecnico?: string | null;
    tipo_servico?: string | null;
    prioridade?: string | null;
    nao_programada?: string | number | null;
    latitude?: string | number | null;
    longitude?: string | number | null;
    garantia?: string | number | null;
  };
  cliente: {
    idClientes: number | string;
    nomeCliente: string;
    telefone?: string | null;
    celular?: string | null;
    documento?: string | null;
    email?: string | null;
    rua?: string | null;
    numero?: string | null;
    complemento?: string | null;
    bairro?: string | null;
    cidade?: string | null;
    estado?: string | null;
    cep?: string | null;
  } | null;
  endereco: string;
  urlRota: string;
  urlWhats?: string;
  checkinAtivo: {
    idCheckin: number | string;
    data_entrada?: string | null;
    status?: string | null;
  } | null;
  observacoes: ObservacaoTecnico[];
  fotos: FotoAtendimento[];
  servicosPrevistos: PrevisaoItem[];
  produtosPrevistos: PrevisaoItem[];
  historicoCliente: HistoricoOs[];
  podeCheckin: boolean;
  podeCheckout: boolean;
  podeFoto: boolean;
  podeNaoRealizado: boolean;
  motivosNaoRealizado: MotivoNaoRealizado[];
}

export interface ObservacaoTecnico {
  id: number;
  texto: string;
  hora: string;
  data_hora?: string;
  autor: string;
}

export interface FotoAtendimento {
  id?: number | string;
  etapa?: string | null;
  url?: string | null;
  descricao?: string | null;
  data_hora?: string | null;
  legenda?: string | null;
  data?: string | null;
}

/** Status de ponto de projeto (Obra_pontos_model::statusLista). */
export interface PontoStatus {
  rotulo: string;
  peso?: number;
  acao?: string;
  classe?: string;
}

export interface PontoProjeto {
  idPonto: number;
  obra_id?: number | string | null;
  obra_nome?: string | null;
  cliente?: string | null;
  identificacao: string;
  tipo?: string | null;
  local?: string | null;
  status: string;
  status_rotulo?: string | null;
  aguardando?: string | null;
  responsavel?: string | null;
  qtd_fotos?: number;
  minha?: boolean;
  tem_foto?: boolean;
  instrucao?: string | null;
  ip?: string | null;
  canal?: string | null;
  porta?: string | null;
  modelo?: string | null;
  serie?: string | null;
  metragem_cabo?: number | string | null;
  observacao?: string | null;
}

export interface ProjetoResumo {
  idObra: number | string;
  codigo?: string | null;
  nome: string;
  nomeCliente?: string | null;
  status?: string | null;
  percentual_concluido?: number | string | null;
  endereco?: string | null;
  cidade?: string | null;
  uf?: string | null;
  latitude?: string | number | null;
  longitude?: string | number | null;
  data_inicio?: string | null;
  data_previsao?: string | null;
  qtdPontos?: number;
  minha?: boolean;
}

export interface ProjetoDetalhe {
  obra: ProjetoResumo;
  osVinculadas: Array<{ idOs: number | string; status: string; dataInicial?: string | null; defeito?: string | null }>;
  consumo: Array<{ nome: string; quantidade?: number | string | null; tipo?: string | null }>;
  rdos: Array<{ id: number; numero?: string | number | null; data?: string | null; atividades?: string | null }>;
  pontos: PontoProjeto[];
  statusLista: Record<string, PontoStatus>;
  executores: Record<string, Executor[]>;
}

export interface Executor {
  idUsuarios: number;
  nome: string;
  encarregado?: boolean;
}

export interface PontoDetalhe {
  ponto: PontoProjeto;
  statusLista: Record<string, PontoStatus>;
  proximoStatus: string | null;
  testeItens: Record<string, string>;
  exigeTeste: boolean;
  exigeEtiqueta: boolean;
  temFotoEtiqueta: boolean;
  historico: Array<{
    id: number;
    de?: string | null;
    para?: string | null;
    data?: string | null;
    usuario?: string | null;
    observacao?: string | null;
    evento?: string | null;
    destinatario?: string | null;
  }>;
  fotos: FotoAtendimento[];
  executores: Executor[];
}

export interface FilaResult {
  pontos: PontoProjeto[];
  statusLista: Record<string, PontoStatus>;
}

export interface RelatorioTecnico {
  tipo: string;
  periodo: { inicio: string; fim: string };
  totais: Record<string, number | string | null>;
}

// ------------------------------------------------------------------
// Colaborador / RH
// ------------------------------------------------------------------

export interface PontoBatida {
  id: number;
  tipo: 'entrada' | 'saida' | 'inicio_intervalo' | 'fim_intervalo' | string;
  label: string;
  hora: string;
  data_hora: string;
  latitude?: string | number | null;
  longitude?: string | number | null;
  os_id?: number | string | null;
  obra_id?: number | string | null;
  dentro_geofence?: number | string | null;
  distancia_metros?: number | null;
}

export type ProximoTipo = 'entrada' | 'saida' | 'inicio_intervalo' | 'fim_intervalo';

export const PONTO_LABELS: Record<string, string> = {
  entrada: 'Entrada',
  saida: 'Saída',
  inicio_intervalo: 'Início do intervalo',
  fim_intervalo: 'Fim do intervalo',
};

export interface ColaboradorDashboard {
  colaborador: {
    id: number;
    nome: string;
    primeiroNome?: string;
    cargo?: string | null;
    fotoUrl: string | null;
  };
  batidasHoje: PontoBatida[];
  proximoTipo: ProximoTipo;
  totaisMes: TotaisCompetencia | null;
  totaisSemana: TotaisCompetencia | null;
  horas: HorasCompetencia | null;
  pontoInicio: string | null;
  pendentes: number;
  podeBaterPonto: boolean;
  hoje: string;
}

export interface HorasCompetencia {
  normais_min?: number;
  extras_50_min?: number;
  extras_100_min?: number;
  saldo_min?: number;
  fechado?: boolean;
}

export interface TotaisCompetencia {
  dias_trabalhados?: number;
  minutos_trabalhados?: number;
  minutos_previstos?: number;
  minutos_extras_50?: number;
  minutos_extras_100?: number;
  minutos_faltas?: number;
  saldo_banco_min?: number;
  inicio?: string | null;
  fim?: string | null;
}

export interface EspelhoLinha {
  data: string;
  dia_semana: number;
  eh_util: boolean;
  abonado: boolean;
  cobra_falta: boolean;
  batidas: PontoBatida[];
  calc: {
    trabalhado: number;
    previsto: number;
    extra50: number;
    extra100: number;
    falta: number;
    saldo: number;
  };
}

export interface EspelhoResult {
  competencia: string;
  linhas: EspelhoLinha[];
  totais: TotaisCompetencia;
  totaisSemana: TotaisCompetencia;
  pontoInicio: string | null;
  horas?: HorasCompetencia | null;
}

export interface Ocorrencia {
  id: number;
  tipo: 'correcao_ponto' | 'justificativa_falta' | 'abono' | string;
  data_referencia?: string | null;
  correcao_tipo?: string | null;
  correcao_data_hora?: string | null;
  descricao?: string | null;
  status: string;
  resposta?: string | null;
  tem_anexo?: boolean;
  anexo?: string | null;
  created_at?: string | null;
}

export interface Ausencia {
  id: number;
  tipo: 'ferias' | 'folga' | 'atestado' | 'licenca' | string;
  data_inicio: string;
  data_fim: string;
  dias: number;
  motivo?: string | null;
  status: string;
  resposta?: string | null;
  tem_anexo?: boolean;
  anexo?: string | null;
  created_at?: string | null;
}

export interface HoleriteItem {
  tipo?: string | null;
  natureza?: string | null;
  descricao?: string | null;
  valor: number;
}

export interface Holerite {
  competencia: string;
  resumo: {
    proventos: number;
    descontos: number;
    liquido: number;
    fgts?: number | null;
    itens: HoleriteItem[];
  };
  arquivo: { nome: string; mime: string; url?: string } | null;
}

export interface MeusDados {
  id: number;
  nome: string;
  primeiroNome?: string;
  cargo?: string | null;
  email?: string | null;
  celular?: string | null;
  pix_tipo?: string | null;
  pix_chave?: string | null;
  fotoUrl: string | null;
  admissao?: string | null;
  unidade?: string | null;
}

export interface PontoEstado {
  colaborador: { id: number; nome: string };
  hoje: string;
  batidasHoje: PontoBatida[];
  proximoTipo: ProximoTipo;
  unidades: Array<{
    id: number;
    nome: string;
    latitude?: string | number | null;
    longitude?: string | number | null;
    raio_metros?: number | null;
  }>;
  os: Array<{ idOs: number | string; status: string; latitude?: string | number | null; longitude?: string | number | null }>;
  obras: Array<{ idObra: number | string; nome: string; status?: string | null }>;
  temBiometria: boolean;
  config: {
    geofenceObrigatorio: boolean;
    faceObrigatorio: boolean;
    faceScoreMinimo: number;
  };
}

export interface PontoRegistro {
  registro_id: number;
  tipo: string;
  hora: string;
  proximoTipo: ProximoTipo;
  fora_area: boolean;
  distancia_metros?: number | null;
  os_id?: number | null;
  obra_id?: number | null;
  batidasHoje: PontoBatida[];
}

export interface ClienteBusca {
  id: number;
  nome: string;
  documento?: string | null;
  telefone?: string | null;
}

export interface ServicoBusca {
  id: number;
  nome: string;
}

export interface ProdutoBusca {
  id: number;
  nome: string;
  unidade?: string | null;
  estoque?: string | number | null;
}

export const ABAS_TECNICO = [
  { key: 'hoje', label: 'Hoje' },
  { key: 'proximos', label: 'Próximos' },
  { key: 'atrasadas', label: 'Atrasadas' },
  { key: 'concluidas', label: 'Concluídas' },
  { key: 'todas', label: 'Todas' },
] as const;

export const MOTIVOS_AUSENCIA = [
  { key: 'ferias', label: 'Férias' },
  { key: 'folga', label: 'Folga' },
  { key: 'atestado', label: 'Atestado' },
  { key: 'licenca', label: 'Licença' },
] as const;

export const TIPOS_OCORRENCIA = [
  { key: 'correcao_ponto', label: 'Correção de ponto' },
  { key: 'justificativa_falta', label: 'Justificativa de falta' },
  { key: 'abono', label: 'Pedido de abono' },
] as const;
