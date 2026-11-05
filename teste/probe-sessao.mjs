/**
 * Verifica se a área do técnico/colaborador pode ser acessada por sessão
 * (cookie) a partir de um cliente externo — sem alterar o servidor.
 *
 * Fluxo: GET /login (colhe CSRF) -> POST /login/verificarLogin (cookie de sessão)
 *        -> GET endpoints /tecnico/* e /colaborador/*.
 *
 * Uso: node teste/probe-sessao.mjs <baseUrl> <email> <senha>
 */

const [baseArg, emailArg, passwordArg] = process.argv.slice(2);
const email = emailArg ?? process.env.U;
const password = passwordArg ?? process.env.P;

if (!baseArg || !email || !password) {
  console.error('Uso: node teste/probe-sessao.mjs <baseUrl> <email> <senha>');
  console.error('Ou:  U=<email> P=<senha> node teste/probe-sessao.mjs <baseUrl>');
  process.exit(2);
}

const base = baseArg.replace(/\/+$/, '').replace(/\/index\.php$/i, '');

/** Jar de cookies mínimo — guarda tudo o que o servidor mandar. */
const jar = new Map();

function cookies() {
  return [...jar].map(([k, v]) => `${k}=${v}`).join('; ');
}

function absorb(res) {
  const raw = res.headers.getSetCookie?.() ?? [];
  for (const line of raw) {
    const [pair] = line.split(';');
    const idx = pair.indexOf('=');
    if (idx > 0) jar.set(pair.slice(0, idx).trim(), pair.slice(idx + 1).trim());
  }
}

async function req(path, options = {}) {
  const headers = { ...(options.headers ?? {}) };
  if (jar.size) headers.Cookie = cookies();
  const res = await fetch(`${base}${path}`, { ...options, headers, redirect: 'manual', signal: AbortSignal.timeout(30000) });
  absorb(res);
  const text = await res.text();
  return { status: res.status, text, location: res.headers.get('location') };
}

const show = (label, r) => {
  const body = r.location ? `<redirect ${r.location}>` : r.text.slice(0, 220).replace(/\s+/g, ' ');
  console.log(`\n${label}\n  HTTP ${r.status}\n  ${body}`);
};

// 1) Página de login: pega o token CSRF do HTML
const page = await req('/index.php/login');
const token =
  /name="([a-zA-Z0-9_]+)"\s+value="([a-f0-9]{32})"/.exec(page.text) ??
  /csrf[^>]*value="([a-f0-9]{32})"/i.exec(page.text);

console.log(`GET /login -> HTTP ${page.status}, cookies: ${[...jar.keys()].join(', ') || 'nenhum'}`);
const csrfName = token?.[1] ?? token?.[2] ? token[1] : null;
const csrfValue = token?.[2] ?? token?.[1] ?? null;
console.log(`CSRF: ${csrfName} = ${csrfValue}`);

if (!csrfValue) {
  console.log('\nNão achei o token CSRF no HTML. Amostra:');
  console.log(page.text.slice(0, 600));
  process.exit(1);
}

// 2) Login
const form = new URLSearchParams({ email, senha: password, [csrfName]: csrfValue });
const login = await req('/index.php/login/verificarLogin?ajax=true', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
  body: form.toString(),
});
show('POST /login/verificarLogin', login);
console.log(`  cookies agora: ${[...jar.keys()].join(', ') || 'nenhum'}`);

// 3) Endpoints das áreas
const paths = [
  '/index.php/tecnico',
  '/index.php/tecnico/os',
  '/index.php/tecnico/api_listar_os',
  '/index.php/tecnico/api_os_detalhes/1',
  '/index.php/tecnico/perfil',
  '/index.php/tecnico/projetos',
  '/index.php/colaborador',
  '/index.php/colaborador/batidasDoDia',
  '/index.php/colaborador/espelho',
];

for (const path of paths) {
  const r = await req(path, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
  const isJson = r.text.trim().startsWith('{') || r.text.trim().startsWith('[');
  show(`GET ${path}${isJson ? '' : '  (HTML)'}`, r);
}

process.exit(0);
