/**
 * Diagnóstico de login contra uma instalação MAPOS real.
 *
 * Uso:  node teste/probe-login.mjs <baseUrl> <email> <senha>
 * Ex.:  node teste/probe-login.mjs https://empresa.com.br/mapos eu@eu.com 'senha'
 *
 * Testa as duas formas de rota (com e sem index.php), mostra status e corpo, e
 * — quando o login funciona — valida os endpoints que o app usa na abertura.
 */

const [baseArg, email, password] = process.argv.slice(2);

if (!baseArg || !email || !password) {
  console.error('Uso: node teste/probe-login.mjs <baseUrl> <email> <senha>');
  process.exit(2);
}

const base = baseArg.replace(/\/+$/, '').replace(/\/index\.php$/i, '');
const variants = [`${base}/api/v1`, `${base}/index.php/api/v1`];

async function call(url, options = {}) {
  const started = Date.now();
  try {
    const res = await fetch(url, { ...options, signal: AbortSignal.timeout(30000) });
    const text = await res.text();
    return { status: res.status, text, ms: Date.now() - started };
  } catch (error) {
    return { status: 0, text: `ERRO DE REDE: ${error.message}`, ms: Date.now() - started };
  }
}

const show = (label, r) => {
  console.log(`\n${label}\n  HTTP ${r.status} (${r.ms}ms)\n  ${r.text.slice(0, 400).replace(/\n/g, ' ')}`);
};

console.log(`Instalação: ${base}\n`);

let apiBase = null;
let login = null;

for (const candidate of variants) {
  const r = await call(`${candidate}/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ email, password }),
  });
  show(`POST ${candidate}/login`, r);
  if (r.status === 200 && !login) {
    apiBase = candidate;
    try {
      login = JSON.parse(r.text);
    } catch {
      /* corpo não é JSON */
    }
  }
}

if (!login?.result?.access_token) {
  console.log('\nRESULTADO: login não retornou access_token — não há como seguir aos endpoints.');
  process.exit(1);
}

const token = login.result.access_token;
console.log(`\nOK: token obtido via ${apiBase}`);
console.log(`Permissões: ${JSON.stringify(login.result.permissions)}`);

const auth = { Authorization: `Bearer ${token}`, Accept: 'application/json' };

for (const path of ['/conta', '', '/emitente', '/os', '/clientes', '/usuarios', '/produtos', '/servicos']) {
  const r = await call(`${apiBase}${path}`, { headers: auth });
  show(`GET ${apiBase}${path || '/'}`, r);
}

process.exit(0);
