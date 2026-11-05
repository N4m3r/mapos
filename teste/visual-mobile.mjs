/**
 * Abre a instalação MAPOS real num viewport de celular e extrai as evidências
 * de usabilidade: screenshot, largura de rolagem horizontal, tamanho de alvos
 * de toque e presença dos assets do PWA.
 *
 * Somente leitura: navega, mas não envia nenhuma ação que altere dados.
 *
 * Uso: U=<email> P=<senha> node teste/visual-mobile.mjs <baseUrl>
 */

import { chromium } from 'playwright-core';
import { existsSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const base = (process.argv[2] ?? '').replace(/\/+$/, '').replace(/\/index\.php$/i, '');
const email = process.env.U;
const password = process.env.P;

if (!base || !email || !password) {
  console.error('Uso: U=<email> P=<senha> node teste/visual-mobile.mjs <baseUrl>');
  process.exit(2);
}

const here = dirname(fileURLToPath(import.meta.url));
const shots = join(here, 'artefatos', 'real');
mkdirSync(shots, { recursive: true });

const candidates = [
  process.env.CHROME_PATH,
  'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
].filter(Boolean);
const executablePath = candidates.find((p) => existsSync(p));
if (!executablePath) {
  console.error('Chrome/Edge não encontrado.');
  process.exit(1);
}

const browser = await chromium.launch({ executablePath, headless: true });
const context = await browser.newContext({
  viewport: { width: 390, height: 844 },
  deviceScaleFactor: 3,
  isMobile: true,
  hasTouch: true,
  locale: 'pt-BR',
  timezoneId: 'America/Manaus',
});
const page = await context.newPage();
page.setDefaultTimeout(20000);

const measure = async () =>
  page.evaluate(() => {
    const doc = document.documentElement;
    const overflowing = [...document.querySelectorAll('*')]
      .filter((el) => el.getBoundingClientRect().right > window.innerWidth + 1)
      .slice(0, 5)
      .map((el) => `${el.tagName.toLowerCase()}.${(el.className || '').toString().split(' ')[0]}`);
    const smallTaps = [...document.querySelectorAll('a,button,input,select')].filter((el) => {
      const r = el.getBoundingClientRect();
      return r.width > 0 && r.height > 0 && (r.height < 40 || r.width < 40);
    }).length;
    const totalTaps = document.querySelectorAll('a,button,input,select').length;
    return {
      scrollWidth: doc.scrollWidth,
      innerWidth: window.innerWidth,
      horizontalScroll: doc.scrollWidth > window.innerWidth + 1,
      overflowing,
      smallTaps,
      totalTaps,
      manifest: document.querySelector('link[rel="manifest"]')?.getAttribute('href') ?? null,
      themeColor: document.querySelector('meta[name="theme-color"]')?.getAttribute('content') ?? null,
      serviceWorker: 'serviceWorker' in navigator,
    };
  });

async function visit(label, path, file) {
  await page.goto(`${base}${path}`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1500);
  const m = await measure();
  await page.screenshot({ path: join(shots, file), fullPage: false });
  console.log(
    `\n${label}\n  url: ${page.url()}\n  rolagem horizontal: ${m.horizontalScroll ? `SIM (${m.scrollWidth}px > ${m.innerWidth}px)` : 'não'}` +
      `\n  elementos vazando: ${m.overflowing.join(', ') || 'nenhum'}` +
      `\n  alvos de toque pequenos: ${m.smallTaps}/${m.totalTaps}` +
      `\n  manifest: ${m.manifest ?? '—'} | theme-color: ${m.themeColor ?? '—'}`,
  );
  return m;
}

// 1) Login pela tela real
await page.goto(`${base}/index.php/login`, { waitUntil: 'domcontentloaded' });
await page.screenshot({ path: join(shots, '00-login.png') });
await page.fill('#email', email);
await page.fill('#senha', password);
await Promise.all([
  page.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => undefined),
  page.click('button[type="submit"], #btn-login, .btn-login'),
]);
await page.waitForTimeout(2500);
console.log(`\nLogin → ${page.url()}`);

const results = [];
results.push(['Dashboard do técnico', await visit('Área do Técnico (dashboard)', '/index.php/tecnico', '01-tecnico.png')]);
results.push(['Minhas OS', await visit('Minhas OS', '/index.php/tecnico/os', '02-os.png')]);
results.push(['Perfil', await visit('Perfil', '/index.php/tecnico/perfil', '03-perfil.png')]);

const osLink = await page.locator('a[href*="/tecnico/visualizar/"]').first().getAttribute('href').catch(() => null);
if (osLink) {
  const path = osLink.replace(/^https?:\/\/[^/]+/, '').replace(/^\/mapos/, '');
  results.push(['Detalhe da OS', await visit('Atendimento (OS)', path, '04-atendimento.png')]);
} else {
  console.log('\n(sem link de OS visível nesta tela)');
}

results.push(['Colaborador', await visit('Área do Colaborador', '/index.php/colaborador', '05-colaborador.png')]);
results.push(['Espelho de ponto', await visit('Espelho de ponto', '/index.php/colaborador/espelho', '06-espelho.png')]);

console.log('\n--- resumo ---');
for (const [name, m] of results) {
  console.log(`${m.horizontalScroll ? 'ROLA ' : 'ok    '} ${name}`);
}
console.log(`\nScreenshots: ${shots}`);

await browser.close();
process.exit(0);
