/**
 * Sonda quais rotas a instalação MAPOS real expõe além do contrato conhecido.
 * A instalação de produção é um fork mais novo que o repositório local, então
 * o que o app pode usar precisa ser descoberto na fonte, não no código.
 *
 * Uso: node teste/probe-endpoints.mjs <baseUrl> <email> <senha>
 */

const [baseArg, email, password] = process.argv.slice(2);
if (!baseArg || !email || !password) {
  console.error('Uso: node teste/probe-endpoints.mjs <baseUrl> <email> <senha>');
  process.exit(2);
}

const base = baseArg.replace(/\/+$/, '').replace(/\/index\.php$/i, '');
const api = `${base}/index.php/api/v1`;

async function call(url, options = {}) {
  try {
    const res = await fetch(url, { ...options, signal: AbortSignal.timeout(25000) });
    return { status: res.status, text: await res.text() };
  } catch (error) {
    return { status: 0, text: `ERRO: ${error.message}` };
  }
}

const login = await call(`${api}/login`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  body: JSON.stringify({ email, password }),
});

let token = null;
try {
  token = JSON.parse(login.text)?.result?.access_token ?? null;
} catch {
  /* não é JSON */
}

if (!token) {
  console.log(`Login falhou: HTTP ${login.status} — ${login.text.slice(0, 200)}`);
  process.exit(1);
}
console.log('Token obtido.\n');

const auth = { Authorization: `Bearer ${token}`, Accept: 'application/json' };

const paths = [
  'tecnico', 'tecnico/os', 'tecnico/perfil', 'tecnico/fila', 'tecnico/dashboard',
  'tecnico/agenda', 'tecnico/projetos', 'tecnico/relatorio', 'tecnico/ponto',
  'colaborador', 'colaborador/espelho', 'colaborador/batidas', 'colaborador/ocorrencias',
  'colaborador/ausencias', 'colaborador/holerite', 'colaborador/meus-dados',
  'rh', 'rh/colaboradores', 'rh/folha', 'rh/ponto',
  'ponto', 'ponto/status', 'ponto/batidas',
  'obras', 'obras/rdo', 'obra', 'rdo',
  'calendario', 'agenda', 'dashboard', 'relatorios',
  'clientes', 'produtos', 'servicos', 'usuarios', 'os',
  'conta', 'emitente', 'audit', 'permissoes', 'checkin',
];

const rows = [];
for (const path of paths) {
  const r = await call(`${api}/${path}`, { headers: auth });
  let hint = '';
  if (r.status === 200) {
    try {
      const body = JSON.parse(r.text);
      hint = body?.message ?? '';
      const result = body?.result;
      if (Array.isArray(result)) hint += ` [${result.length} itens]`;
      else if (result && typeof result === 'object') hint += ` {${Object.keys(result).slice(0, 6).join(',')}}`;
    } catch {
      hint = 'HTML (não é JSON)';
    }
  } else {
    try {
      hint = JSON.parse(r.text)?.message ?? r.text.slice(0, 80).replace(/\s+/g, ' ');
    } catch {
      hint = r.text.slice(0, 80).replace(/\s+/g, ' ');
    }
  }
  rows.push({ path, status: r.status, hint });
}

const width = Math.max(...rows.map((r) => r.path.length));
for (const r of rows) {
  const mark = r.status === 200 ? 'OK  ' : r.status === 0 ? 'ERR ' : `${r.status} `;
  console.log(`${mark} /api/v1/${r.path.padEnd(width)}  ${r.hint.slice(0, 90)}`);
}

process.exit(0);
