/**
 * Dump dos endpoints JSON somente-leitura da área do técnico/colaborador.
 * NÃO executa nenhuma ação que altere dados (sem iniciar/finalizar atendimento).
 *
 * Uso: U=<email> P=<senha> node teste/probe-tecnico.mjs <baseUrl>
 */

const base = (process.argv[2] ?? '').replace(/\/+$/, '').replace(/\/index\.php$/i, '');
const email = process.env.U;
const password = process.env.P;

if (!base || !email || !password) {
  console.error('Uso: U=<email> P=<senha> node teste/probe-tecnico.mjs <baseUrl>');
  process.exit(2);
}

const jar = new Map();
const cookieHeader = () => [...jar].map(([k, v]) => `${k}=${v}`).join('; ');

function absorb(res) {
  for (const line of res.headers.getSetCookie?.() ?? []) {
    const [pair] = line.split(';');
    const i = pair.indexOf('=');
    if (i > 0) jar.set(pair.slice(0, i).trim(), pair.slice(i + 1).trim());
  }
}

async function req(path, options = {}) {
  const headers = { ...(options.headers ?? {}) };
  if (jar.size) headers.Cookie = cookieHeader();
  const res = await fetch(`${base}${path}`, { ...options, headers, redirect: 'manual', signal: AbortSignal.timeout(30000) });
  absorb(res);
  return { status: res.status, text: await res.text() };
}

const page = await req('/index.php/login');
const m = /name="([a-zA-Z0-9_]+)"\s+value="([a-f0-9]{32})"/.exec(page.text);
if (!m) {
  console.error('CSRF não encontrado.');
  process.exit(1);
}

const login = await req('/index.php/login/verificarLogin?ajax=true', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
  body: new URLSearchParams({ email, senha: password, [m[1]]: m[2] }).toString(),
});
console.log(`login: HTTP ${login.status} ${login.text.slice(0, 120)}`);

const getJson = async (path) => {
  const r = await req(path, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
  try {
    return { status: r.status, json: JSON.parse(r.text), raw: r.text };
  } catch {
    return { status: r.status, json: null, raw: r.text };
  }
};

// ---- lista de OS do técnico
const lista = await getJson('/index.php/tecnico/api_listar_os');
console.log(`\n=== api_listar_os (HTTP ${lista.status}) ===`);
if (lista.json?.data?.length) {
  console.log(`Total: ${lista.json.data.length} OS`);
  console.log('Campos de uma OS:');
  console.log(JSON.stringify(lista.json.data[0], null, 2).slice(0, 1600));
  console.log('\nIds disponíveis:', lista.json.data.slice(0, 12).map((o) => o.idOs).join(', '));
} else {
  console.log(lista.raw.slice(0, 400));
}

// ---- detalhe da primeira OS
const firstId = lista.json?.data?.[0]?.idOs;
if (firstId) {
  const det = await getJson(`/index.php/tecnico/api_os_detalhes/${firstId}`);
  console.log(`\n=== api_os_detalhes/${firstId} (HTTP ${det.status}) ===`);
  console.log(JSON.stringify(det.json ?? det.raw, null, 2).slice(0, 2600));

  const obs = await getJson(`/index.php/tecnico/listar_observacoes/${firstId}`);
  console.log(`\n=== listar_observacoes/${firstId} (HTTP ${obs.status}) ===`);
  console.log(JSON.stringify(obs.json ?? obs.raw).slice(0, 600));
}

// ---- colaborador
for (const path of ['/index.php/colaborador/batidasDoDia', '/index.php/colaborador/espelho']) {
  const r = await req(path, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
  const json = (() => {
    try {
      return JSON.parse(r.text);
    } catch {
      return null;
    }
  })();
  console.log(`\n=== ${path} (HTTP ${r.status}) ===`);
  console.log(json ? JSON.stringify(json).slice(0, 600) : 'HTML (não JSON), ' + r.text.length + ' bytes');
}

process.exit(0);
