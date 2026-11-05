/** Tipos do contrato da API v1 do MAPOS (application/controllers/api/v1). */

export interface ApiEnvelope<T> {
  status: boolean;
  message: string;
  result: T;
}

export interface LoginResult {
  access_token: string;
  permissions: Array<Record<string, string>>;
}

export interface Usuario {
  idUsuarios: number | string;
  nome: string;
  email: string;
  url_image_user: string | null;
  permissoes_id: number | string;
}

/** Resposta de /api/v1/conta — `usuario` vem aninhado. */
export interface ContaResult {
  status: boolean;
  data: { uid: number; email: string; permissao: number };
  usuario: Usuario;
  level: number;
}

export interface DashboardResult {
  countOs: string | number;
  clientes: string | number;
  produtos: string | number;
  servicos: string | number;
  garantias: string | number;
  vendas: string | number;
  osAbertas?: Os[];
  osAndamento?: Os[];
  estoqueBaixo?: Produto[];
}

export interface Emitente {
  nome: string;
  cnpj?: string;
  telefone?: string;
  email?: string;
  rua?: string;
  numero?: string;
  bairro?: string;
  cidade?: string;
  estado?: string;
  url_logo?: string;
}

export interface Cliente {
  idClientes: number | string;
  nomeCliente: string;
  documento?: string;
  telefone?: string;
  celular?: string;
  email?: string;
  contato?: string;
  rua?: string;
  numero?: string;
  complemento?: string;
  bairro?: string;
  cidade?: string;
  estado?: string;
  cep?: string;
  fornecedor?: string | number;
  dataCadastro?: string;
  ordensServicos?: Os[];
}

export interface Os {
  idOs: number | string;
  clientes_id: number | string;
  usuarios_id: number | string;
  nomeCliente?: string;
  celular_cliente?: string;
  telefone_cliente?: string;
  contato_cliente?: string;
  email?: string;
  nome?: string;
  status: string;
  dataInicial: string;
  dataFinal: string;
  garantia?: string;
  garantias_id?: number | string | null;
  descricaoProduto?: string;
  defeito?: string;
  observacoes?: string;
  laudoTecnico?: string;
  faturado?: string | number;
  valorTotal?: string | number;
  desconto?: string | number;
  tipo_desconto?: string;
  totalProdutos?: string | number;
  totalServicos?: string | number;
  refGarantia?: string;
  textoGarantia?: string;
  produtos?: OsProduto[];
  servicos?: OsServico[];
  anexos?: OsAnexo[];
  anotacoes?: OsAnotacao[];
  calcTotal?: number | string;
  textoWhatsApp?: string;
}

export interface OsProduto {
  idProdutos_os?: number | string;
  produtos_id: number | string;
  descricao?: string;
  preco: string | number;
  quantidade: string | number;
  subTotal?: string | number;
}

export interface OsServico {
  idServicos_os?: number | string;
  servicos_id: number | string;
  nome?: string;
  preco: string | number;
  quantidade: string | number;
  subTotal?: string | number;
}

export interface OsAnotacao {
  idAnotacoes: number | string;
  os_id: number | string;
  anotacao: string;
  data_hora: string;
}

export interface OsAnexo {
  idAnexos?: number | string;
  os_id?: number | string;
  anexo: string;
  url?: string;
}

export interface Produto {
  idProdutos: number | string;
  descricao: string;
  unidade?: string;
  precoVenda: string | number;
  estoque?: string | number;
  estoqueMinimo?: string | number;
  codDeBarra?: string;
}

export interface Servico {
  idServicos: number | string;
  nome: string;
  descricao?: string;
  preco: string | number;
}

/** Status de OS aceitos pelo MAPOS (views/os/adicionarOs.php). */
export const OS_STATUS = [
  'Aberto',
  'Orçamento',
  'Negociação',
  'Aprovado',
  'Aguardando Peças',
  'Em Andamento',
  'Não Realizado',
  'Finalizado',
  'Faturado',
  'Cancelado',
] as const;

export type OsStatus = (typeof OS_STATUS)[number];
