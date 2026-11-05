/**
 * Descobre o contrato real da Área do Técnico / Área do Colaborador: para cada
 * caminho, informa se a resposta é JSON (e um resumo) ou HTML (e o tamanho).
 * Somente leitura — nenhuma ação é executada.
 *
 * Uso: U=<email> P=<senha> node teste/probe-areas.mjs <baseUrl>
 */

const base = (process.argv[2] ?? '').replace(/\/+$/, '').replace(/\/index\.php$/i, '');
const email = process.env.U;
const password = process.env.P;

if (!base || !email || !password) {
  console.error('Uso: U=<email> P=<senha> node teste/probe-areas.mjs <baseUrl>');
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
const csrf = /name="([a-zA-Z0-9_]+)"\s+value="([a-f0-9]{32})"/.exec(page.text);
if (!csrf) {
  console.error('CSRF não encontrado.');
  process.exit(1);
}

const login = await req('/index.php/login/verificarLogin?ajax=true', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
  body: new URLSearchParams({ email, senha: password, [csrf[1]]: csrf[2] }).toString(),
});
console.log(`login: HTTP ${login.status} ${login.text.slice(0, 100)}\n`);

const paths = [
  '/index.php/tecnico',
  '/index.php/tecnico/api_listar_os',
  '/index.php/tecnico/projetos',
  '/index.php/tecnico/fila',
  '/index.php/tecnico/nao_realizadas',
  '/index.php/tecnico/motivos_nao_realizado',
  '/index.php/tecnico/buscar_clientes?term=a',
  '/index.php/tecnico/buscar_servicos?term=a',
  '/index.php/tecnico/buscar_produtos?term=a',
  '/index.php/tecnico/perfil',
  '/index.php/tecnico/meu_relatorio',
  '/index.php/colaborador',
  '/index.php/colaborador/batidasDoDia',
  '/index.php/colaborador/espelho',
  '/index.php/colaborador/ocorrencias',
  '/index.php/colaborador/ausencias',
  '/index.php/colaborador/holerite',
  '/index.php/colaborador/meusDados',
  '/index.php/ponto',
  '/index.php/ponto/descriptor',
];

for (const path of paths) {
  const r = await req(path, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
  const t = r.text.trim();
  let kind = 'HTML';
  let summary = `${r.text.length} bytes`;
  if (t.startsWith('{') || t.startsWith('[')) {
    kind = 'JSON';
    try {
      const j = JSON.parse(t);
      const keys = Object.keys(j).slice(0, 8);
      const arr = Array.isArray(j) ? j.length : j.data?.length ?? j.itens?.length;
      summary = `chaves=[${keys.join(', ')}]` + (arr !== undefined ? ` n=${arr}` : '');
      if (Array.isArray(j) && j[0]) summary += ` item0=${JSON.stringify(j[0]).slice(0, 220)}`;
    } catch {
      kind = 'JSON-INVÁLIDO';
    }
  } else if (/^\s*<html|^\s*<!doctype|^\s*<!DOCTYPE/i.test(t)) {
    // página HTML: marca se é redirect para login (sessão perdida)
    if (/name="email"|id="email"|verificarLogin/i.test(t) && !/tecnico|colaborador|Dashboard/i.test(t)) {
      kind = 'HTML(login)';
    }
  }
  console.log(`${String(r.status).padEnd(3)} ${kind.padEnd(13)} ${path}\n     ${summary}`);
}

process.exit(0);
