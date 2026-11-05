/**
 * Mostra as permissões que o login da API v1 devolve para o usuário.
 * Somente leitura.
 *
 * Uso: U=<email> P=<senha> node teste/probe-permissoes.mjs <baseUrl>
 */

const base = (process.argv[2] ?? '').replace(/\/+$/, '').replace(/\/index\.php$/i, '');
const email = process.env.U;
const password = process.env.P;

if (!base || !email || !password) {
  console.error('Uso: U=<email> P=<senha> node teste/probe-permissoes.mjs <baseUrl>');
  process.exit(2);
}

const res = await fetch(`${base}/index.php/api/v1/login`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  body: JSON.stringify({ email, password }),
});
const login = await res.json().catch(() => null);
console.log(`login: HTTP ${res.status} ${login?.message ?? ''}`);
if (!login?.status) process.exit(1);

const perms = login.result.permissions?.[0] ?? {};
const on = Object.entries(perms).filter(([, v]) => String(v) === '1').map(([k]) => k);
console.log(`\nPermissões ativas (${on.length}):`);
console.log(on.sort().join(' '));

const areas = on.filter((p) => /Tecnico|Colaborador|Ponto|Obra|Rdo|Rh/i.test(p));
console.log(`\nÁreas relevantes (${areas.length}): ${areas.join(' ')}`);

const t = await fetch(`${base}/index.php/api/v1/conta`, {
  headers: { Authorization: `Bearer ${login.result.access_token}`, Accept: 'application/json' },
});
const conta = await t.json().catch(() => null);
console.log(`\nconta: HTTP ${t.status} ${conta?.status} → ${conta?.result?.usuario?.nome ?? '—'} (permissoes_id ${conta?.result?.usuario?.permissoes_id ?? '—'})`);

process.exit(0);
