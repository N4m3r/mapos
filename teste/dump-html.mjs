/**
 * Salva o HTML das telas das áreas do técnico/colaborador em teste/artefatos/html/
 * para inspeção da estrutura (sem alterar nada no servidor).
 *
 * Uso: U=<email> P=<senha> node teste/dump-html.mjs <baseUrl>
 */

import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const base = (process.argv[2] ?? '').replace(/\/+$/, '').replace(/\/index\.php$/i, '');
const email = process.env.U;
const password = process.env.P;
if (!base || !email || !password) {
  console.error('Uso: U=<email> P=<senha> node teste/dump-html.mjs <baseUrl>');
  process.exit(2);
}

const out = join(dirname(fileURLToPath(import.meta.url)), 'artefatos', 'html');
mkdirSync(out, { recursive: true });

const jar = new Map();
const cookies = () => [...jar].map(([k, v]) => `${k}=${v}`).join('; ');
const absorb = (res) => {
  for (const line of res.headers.getSetCookie?.() ?? []) {
    const [pair] = line.split(';');
    const i = pair.indexOf('=');
    if (i > 0) jar.set(pair.slice(0, i).trim(), pair.slice(i + 1).trim());
  }
};

async function req(path, options = {}) {
  const headers = { ...(options.headers ?? {}) };
  if (jar.size) headers.Cookie = cookies();
  const res = await fetch(`${base}${path}`, { ...options, headers, redirect: 'manual', signal: AbortSignal.timeout(30000) });
  absorb(res);
  return { status: res.status, text: await res.text() };
}

const page = await req('/index.php/login');
const csrf = /name="([a-zA-Z0-9_]+)"\s+value="([a-f0-9]{32})"/.exec(page.text);
await req('/index.php/login/verificarLogin?ajax=true', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
  body: new URLSearchParams({ email, senha: password, [csrf[1]]: csrf[2] }).toString(),
});

const pages = [
  ['tecnico-dashboard', '/index.php/tecnico'],
  ['tecnico-projetos', '/index.php/tecnico/projetos'],
  ['tecnico-fila', '/index.php/tecnico/fila'],
  ['tecnico-nao-realizadas', '/index.php/tecnico/nao_realizadas'],
  ['tecnico-perfil', '/index.php/tecnico/perfil'],
  ['tecnico-relatorio', '/index.php/tecnico/meu_relatorio'],
  ['colaborador-dashboard', '/index.php/colaborador'],
  ['colaborador-espelho', '/index.php/colaborador/espelho'],
  ['colaborador-ocorrencias', '/index.php/colaborador/ocorrencias'],
  ['colaborador-ausencias', '/index.php/colaborador/ausencias'],
  ['colaborador-holerite', '/index.php/colaborador/holerite'],
  ['colaborador-meus-dados', '/index.php/colaborador/meusDados'],
  ['ponto', '/index.php/ponto'],
];

for (const [name, path] of pages) {
  const r = await req(path, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
  writeFileSync(join(out, `${name}.html`), r.text, 'utf8');
  console.log(`${r.status} ${String(r.text.length).padStart(7)} ${name}.html`);
}
console.log(`\nSalvo em ${out}`);
process.exit(0);
