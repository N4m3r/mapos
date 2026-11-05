/**
 * Sonda a API v1 com o token do usuário e informa HTTP + resumo de cada rota.
 * Somente leitura.
 *
 * Uso: U=<email> P=<senha> node teste/probe-v1.mjs <baseUrl>
 */

const base = (process.argv[2] ?? '').replace(/\/+$/, '').replace(/\/index\.php$/i, '');
const email = process.env.U;
const password = process.env.P;
if (!base || !email || !password) {
  console.error('Uso: U=<email> P=<senha> node teste/probe-v1.mjs <baseUrl>');
  process.exit(2);
}

const res = await fetch(`${base}/index.php/api/v1/login`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  body: JSON.stringify({ email, password }),
});
const login = await res.json();
if (!login?.status) {
  console.error('login falhou', login);
  process.exit(1);
}
const token = login.result.access_token;

const routes = [
  '/index.php/api/v1',
  '/index.php/api/v1/conta',
  '/index.php/api/v1/emitente',
  '/index.php/api/v1/os',
  '/index.php/api/v1/os/1105',
  '/index.php/api/v1/os/1105/anotacoes',
  '/index.php/api/v1/clientes',
  '/index.php/api/v1/clientes/21',
  '/index.php/api/v1/produtos',
  '/index.php/api/v1/servicos',
  '/index.php/api/v1/usuarios',
  '/index.php/api/v1/calendario',
  '/index.php/api/v1/audit',
];

for (const path of routes) {
  const r = await fetch(`${base}${path}`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    signal: AbortSignal.timeout(30000),
  });
  const text = await r.text();
  let summary = `${text.length} bytes`;
  try {
    const j = JSON.parse(text);
    if (Array.isArray(j)) summary = `array n=${j.length}`;
    else summary = `status=${j.status} msg=${String(j.message).slice(0, 60)}`;
    if (j.result && Array.isArray(j.result)) summary += ` result n=${j.result.length}`;
    else if (j.result && typeof j.result === 'object') summary += ` result=[${Object.keys(j.result).slice(0, 6).join(',')}]`;
  } catch {
    summary = `NÃO JSON: ${text.slice(0, 90).replace(/\s+/g, ' ')}`;
  }
  console.log(`${String(r.status).padEnd(3)} ${path.padEnd(38)} ${summary}`);
}
process.exit(0);
