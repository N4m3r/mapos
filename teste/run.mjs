/**
 * Validação do build do MAPOS Mobile em navegador real (headless).
 *
 * Cobre: PWA (manifest/SW), setup de servidor, login, navegação, busca,
 * detalhe de OS, alteração de status, anotação, criação de OS e cliente,
 * agenda e configurações. Tira screenshots em teste/artefatos.
 *
 * Uso: node teste/run.mjs  (após `npm --prefix mobile run build`)
 */

import { mkdirSync, rmSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { once } from 'node:events';
import { chromium } from 'playwright-core';
import { createFakeApi } from './fake-api.mjs';

const here = dirname(fileURLToPath(import.meta.url));
const projectRoot = dirname(here);
const dist = join(projectRoot, 'mobile', 'dist');
const shots = join(here, 'artefatos');

process.env.MAPOS_DIST = dist;

const results = [];
let browser;

function check(name, passed, detail = '') {
  results.push({ name, passed, detail });
  const icon = passed ? 'PASS' : 'FAIL';
  console.log(`  [${icon}] ${name}${detail ? ` — ${detail}` : ''}`);
}

async function step(name, fn) {
  console.log(`\n▸ ${name}`);
  try {
    await fn();
  } catch (error) {
    check(name, false, error?.message?.split('\n')[0] ?? String(error));
  }
}

function findChrome() {
  const candidates = [
    process.env.CHROME_PATH,
    join(process.env.PROGRAMFILES ?? '', 'Google/Chrome/Application/chrome.exe'),
    join(process.env['PROGRAMFILES(X86)'] ?? '', 'Google/Chrome/Application/chrome.exe'),
    join(process.env.LOCALAPPDATA ?? '', 'Google/Chrome/Application/chrome.exe'),
    join(process.env.PROGRAMFILES ?? '', 'Microsoft/Edge/Application/msedge.exe'),
    join(process.env['PROGRAMFILES(X86)'] ?? '', 'Microsoft/Edge/Application/msedge.exe'),
    '/usr/bin/google-chrome',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  ].filter(Boolean);

  for (const candidate of candidates) {
    if (candidate && existsSync(candidate)) return candidate;
  }
  return null;
}

async function main() {
  console.log('MAPOS Mobile — validação de build\n');

  if (!existsSync(join(dist, 'index.html'))) {
    console.error(`Build não encontrado em ${dist}. Rode: npm --prefix mobile run build`);
    process.exit(1);
  }

  rmSync(shots, { recursive: true, force: true });
  mkdirSync(shots, { recursive: true });

  const published = join(projectRoot, 'mobile-app');
  const { server, state } = createFakeApi({ dist: existsSync(join(published, 'index.html')) ? published : undefined });
  server.listen(0, '127.0.0.1');
  await once(server, 'listening');
  const { port } = server.address();
  const base = `http://127.0.0.1:${port}`;
  console.log(`Servidor de teste: ${base}`);

  const executablePath = findChrome();
  if (!executablePath) {
    console.error('Chrome/Edge não encontrado. Defina CHROME_PATH.');
    server.close();
    process.exit(1);
  }

  browser = await chromium.launch({ executablePath, headless: true });
  const context = await browser.newContext({
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 2,
    isMobile: true,
    hasTouch: true,
    locale: 'pt-BR',
    timezoneId: 'America/Sao_Paulo',
  });
  const page = await context.newPage();
  // Nenhuma espera pode pendurar a suíte: 8s é folga de sobra para um app local.
  page.setDefaultTimeout(8000);
  page.setDefaultNavigationTimeout(12000);

  const consoleErrors = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') consoleErrors.push(msg.text());
  });
  page.on('pageerror', (error) => consoleErrors.push(error.message));

  const shot = async (name) => {
    await page.screenshot({ path: join(shots, `${name}.png`), fullPage: false });
  };

  // ---------------------------------------------------------------- PWA
  await step('Abrir o app e validar o shell', async () => {
    await page.goto(`${base}/`, { waitUntil: 'networkidle' });
    const title = await page.title();
    check('Título da página', title.includes('MAPOS'), title);

    const manifest = await page.evaluate(async () => {
      const link = document.querySelector('link[rel="manifest"]');
      if (!link) return null;
      const response = await fetch(link.href);
      return response.ok ? await response.json() : null;
    });
    check('Manifest do PWA carregado', Boolean(manifest?.icons?.length), manifest ? `${manifest.short_name}` : 'sem manifest');
    check(
      'Manifest em modo standalone',
      manifest?.display === 'standalone',
      String(manifest?.display ?? ''),
    );

    const viewport = page.viewportSize();
    check('Viewport mobile 390x844', viewport?.width === 390 && viewport?.height === 844);

    const hasHorizontalScroll = await page.evaluate(
      () => document.documentElement.scrollWidth > window.innerWidth + 1,
    );
    check('Sem rolagem horizontal na tela de setup', !hasHorizontalScroll);

    await shot('01-setup');
  });

  // ---------------------------------------------------------------- setup + login
  await step('Conectar ao servidor e autenticar', async () => {
    await page.getByLabel('Endereço do MAPOS').fill(base);
    await page.getByRole('button', { name: 'Continuar' }).click();

    await page.getByLabel('E-mail').waitFor({ timeout: 5000 });
    check('Avançou para o formulário de credenciais', true);

    await page.getByLabel('E-mail').fill('tecnico@empresa.com');
    await page.getByLabel('Senha').fill('senha-errada');
    await page.getByRole('button', { name: 'Entrar' }).click();

    const alert = page.locator('.alert--error');
    await alert.waitFor({ timeout: 5000 });
    const message = (await alert.textContent()) ?? '';
    check('Login inválido mostra erro da API', message.includes('incorretos'), message.trim());
    await shot('02-login-erro');

    await page.getByLabel('Senha').fill('senha123');
    await page.getByRole('button', { name: 'Entrar' }).click();

    await page.getByText('Ordens de serviço').first().waitFor({ timeout: 8000 });
    check('Login válido abre o início', true);
    check('Token gravado na sessão', (await page.evaluate(() => localStorage.getItem('mapos.session'))) !== null);
  });

  // ---------------------------------------------------------------- home
  await step('Início (dashboard, branding, KPIs)', async () => {
    const body = (await page.locator('body').innerText()).toLowerCase();
    check('Nome da empresa via /emitente', body.includes('assistência tech'));
    check('Contador de OS', body.includes('ordens de serviço'));
    check('Contador de clientes', body.includes('clientes'));
    check('OS em andamento listada', body.includes('ana beatriz souza'));
    check('Aviso de estoque baixo', body.includes('estoque baixo'));
    check('Sem rolagem horizontal no início', !(await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1)));
    await shot('03-inicio');
  });

  // ---------------------------------------------------------------- navegação por abas
  await step('Navegação pelas abas', async () => {
    await page.getByRole('button', { name: 'OS', exact: true }).click();
    await page.getByText('Ordens de serviço').waitFor({ timeout: 5000 });
    check('Aba OS abre a listagem', page.url().endsWith('#/os'));

    await page.getByRole('button', { name: 'Clientes' }).click();
    await page.getByText('Clientes', { exact: true }).first().waitFor({ timeout: 5000 });
    check('Aba Clientes abre a listagem', page.url().endsWith('#/clientes'));

    await page.getByRole('button', { name: 'Agenda' }).click();
    await page.getByText('Agenda').first().waitFor({ timeout: 5000 });
    check('Aba Agenda abre a agenda', page.url().endsWith('#/agenda'));

    await page.getByRole('button', { name: 'Início' }).click();
    await page.getByText('Ordens de serviço').first().waitFor({ timeout: 5000 });
    check('Aba Início volta ao dashboard', page.url().endsWith('#/home'));
  });

  // ---------------------------------------------------------------- busca e filtro
  await step('Busca e filtro de OS', async () => {
    await page.goto(`${base}/#/os`);
    await page.getByLabel('Buscar ordens de serviço').waitFor({ timeout: 5000 });

    const listed = () => page.$$eval('.content .card .card__title', (nodes) => nodes.map((n) => n.textContent ?? ''));

    await page.getByLabel('Buscar ordens de serviço').fill('Mercado');
    await page.waitForTimeout(900);
    let titles = await listed();
    check('Busca filtra por cliente', titles.length === 1 && titles[0].includes('Mercado Bom Preço'), titles.join(', '));

    await page.getByLabel('Buscar ordens de serviço').fill('');
    await page.waitForTimeout(900);
    await page.getByRole('tab', { name: 'Aberto', exact: true }).click();
    await page.waitForTimeout(900);
    titles = await listed();
    check('Filtro por status funciona', titles.length === 1 && titles[0].includes('#1001'), titles.join(', '));
    await shot('04-lista-os-filtrada');

    await page.getByRole('tab', { name: 'Todas' }).click();
    await page.waitForTimeout(900);
  });

  // ---------------------------------------------------------------- detalhe OS
  await step('Detalhe da OS', async () => {
    await page.getByText('#1000 · Ana Beatriz Souza').click();
    await page.getByText('Produto / descrição').waitFor({ timeout: 6000 });

    const text = await page.locator('body').innerText();
    check('Mostra cliente', text.includes('Ana Beatriz Souza'));
    check('Mostra status', text.includes('Em Andamento'));
    check('Mostra defeito', text.includes('Não liga após queda'));
    check('Mostra serviço com valor', text.includes('Diagnóstico e reparo'));
    check('Mostra produto', text.includes('Fonte 65W'));
    check('Mostra total da OS', text.includes('350,00') || text.includes('R$ 350'));
    check('Mostra anotação existente', text.includes('Testes iniciais ok'));
    check('Ação Ligar presente', (await page.getByRole('link', { name: 'Ligar' }).count()) === 1);
    check('Ação WhatsApp presente', (await page.getByRole('link', { name: 'WhatsApp' }).count()) === 1);
    await shot('05-detalhe-os');
  });

  await step('Alterar status da OS', async () => {
    await page.getByRole('button', { name: 'Finalizado', exact: true }).click();
    await page.waitForTimeout(900);
    const text = await page.locator('body').innerText();
    check('Status atualizado na tela', text.includes('Status alterado para Finalizado'));
    check('Servidor recebeu o PUT com datas dd/mm/aaaa', /^\d{2}\/\d{2}\/\d{4}$/.test(state.lastUpdate?.body?.dataInicial ?? ''));
    check('Servidor recebeu status Finalizado', state.lastUpdate?.body?.status === 'Finalizado');
    await shot('06-os-status');
  });

  await step('Adicionar anotação', async () => {
    const before = state.os[0].anotacoes.length;
    await page.getByPlaceholder('Escreva uma anotação técnica…').fill('Cliente aprovou o orçamento');
    await page.getByRole('button', { name: 'Adicionar anotação' }).click();
    await page.waitForTimeout(900);
    const text = await page.locator('body').innerText();
    check('Anotação registrada', text.includes('Anotação registrada') && text.includes('Cliente aprovou o orçamento'));
    check('Anotação persistida no servidor', state.os[0].anotacoes.length === before + 1);
  });

  // ---------------------------------------------------------------- nova OS
  await step('Criar nova OS', async () => {
    await page.goto(`${base}/#/os/nova`);
    await page.getByText('Nova OS').waitFor({ timeout: 5000 });

    await page.getByLabel('Técnico responsável *').selectOption('1');
    await page.getByLabel('Situação da OS').selectOption('Orçamento');
    await page.getByLabel('Produto / descrição').fill('Impressora HP LaserJet');
    await page.getByLabel('Defeito relatado').fill('Puxa duas folhas');

    // cliente: busca e seleção
    await page.getByPlaceholder('Buscar cliente…').fill('Ana');
    await page.waitForTimeout(900);
    await page.getByLabel('Cliente *').selectOption('1');

    await page.getByRole('button', { name: 'Criar OS' }).click();
    await page.waitForTimeout(1200);

    const created = state.os.find((o) => o.idOs === 1002);
    check('OS criada no servidor', Boolean(created));
    check('Status enviado corretamente', created?.status === 'Orçamento', created?.status ?? '');
    check('Data no formato da API', /^\d{4}-\d{2}-\d{2}$/.test(created?.dataInicial ?? ''), created?.dataInicial ?? '');
    check('App abriu o detalhe da nova OS', page.url().includes('#/os/1002'));
    await shot('07-os-criada');
  });

  // ---------------------------------------------------------------- clientes
  await step('Clientes: busca, detalhe e cadastro', async () => {
    await page.goto(`${base}/#/clientes`);
    await page.getByLabel('Buscar clientes').waitFor({ timeout: 5000 });
    await page.getByLabel('Buscar clientes').fill('Mercado');
    await page.waitForTimeout(900);
    check('Busca de clientes filtra', (await page.locator('body').innerText()).includes('Mercado Bom Preço'));

    await page.getByText('Mercado Bom Preço').first().click();
    await page.getByText('Ordens de serviço (1)').waitFor({ timeout: 6000 });
    const detail = await page.locator('body').innerText();
    check('Detalhe mostra documento', detail.includes('12.345.678/0001-90'));
    check('Detalhe lista OS do cliente', detail.includes('#1001'));
    await shot('08-cliente-detalhe');

    await page.goto(`${base}/#/clientes/novo`);
    await page.getByText('Novo cliente').waitFor({ timeout: 5000 });
    await page.getByLabel('Nome *').fill('João da Silva');
    await page.getByLabel('Celular').fill('(11) 91234-5678');
    await page.getByLabel('E-mail').fill('joao@exemplo.com');
    await page.getByRole('button', { name: 'Salvar' }).click();
    await page.waitForTimeout(1200);

    const novo = state.clientes.find((c) => c.nomeCliente === 'João da Silva');
    check('Cliente criado no servidor', Boolean(novo));
    check('App abriu o detalhe do cliente', page.url().includes(`#/clientes/${novo?.idClientes}`), page.url());
  });

  // ---------------------------------------------------------------- agenda
  await step('Agenda por período', async () => {
    await page.goto(`${base}/#/agenda`);
    await page.getByText('Agenda').first().waitFor({ timeout: 6000 });
    const text = await page.locator('body').innerText();
    check('Agenda carrega período', text.includes('–'));
    await page.getByRole('button', { name: 'Este mês' }).click();
    await page.waitForTimeout(900);
    check('Troca para visão mensal', (await page.locator('body').innerText()).includes('Este mês'));
    await shot('09-agenda');
  });

  // ---------------------------------------------------------------- permissão nova sem novo login
  await step('Área do RH liberada depois do login aparece no seletor', async () => {
    await page.goto(`${base}/#/home`);
    await page.getByRole('heading', { name: /Assistência Tech/ }).waitFor({ timeout: 8000 });
    check('Sem a permissão, não há aba RH', (await page.getByRole('tab', { name: 'RH' }).count()) === 0);
    state.permissoesAtuais = { ...state.permissoes, vAreaColaborador: '1' };
    await page.reload();
    await page.getByRole('tab', { name: 'RH' }).waitFor({ timeout: 8000 });
    check('Seletor mostra Sistema e RH', (await page.getByRole('tab', { name: 'Sistema' }).count()) === 1);
    await page.getByRole('tab', { name: 'RH' }).click();
    await page.waitForFunction(() => location.hash.startsWith('#/colaborador'), null, { timeout: 8000 });
    check('Abre a área do colaborador', true);
    await page.getByRole('tab', { name: 'Sistema' }).click();
    await page.waitForFunction(() => location.hash === '#/home', null, { timeout: 8000 });
    check('Volta para o sistema', true);
  });

  // ---------------------------------------------------------------- configurações e tema
  await step('Configurações e tema', async () => {
    await page.goto(`${base}/#/config`);
    await page.getByText('Configurações').first().waitFor({ timeout: 5000 });
    const text = await page.locator('body').innerText();
    check('Mostra usuário logado', text.includes('Carlos Técnico'));
    check('Mostra servidor configurado', text.includes(base.replace('http://', '')) || text.includes('127.0.0.1'));
    check('Lista permissões', text.includes('vOs'));

    await page.getByRole('button', { name: 'Escuro' }).click();
    await page.waitForTimeout(300);
    check('Tema escuro aplicado', (await page.evaluate(() => document.documentElement.dataset.theme)) === 'dark');
    await shot('10-config-escuro');

    await page.getByRole('button', { name: 'Automático' }).click();
    await page.waitForTimeout(200);
    check('Tema automático remove atributo', (await page.evaluate(() => document.documentElement.dataset.theme)) === undefined);
  });

  // ---------------------------------------------------------------- sessão inválida
  await step('Sessão expirada volta ao login', async () => {
    await page.evaluate(() => {
      const raw = localStorage.getItem('mapos.session');
      if (!raw) return;
      const session = JSON.parse(raw);
      session.token = 'token.invalido';
      localStorage.setItem('mapos.session', JSON.stringify(session));
    });
    // recarrega de fato: só assim o app lê a sessão adulterada do storage
    await page.goto(`${base}/#/os`);
    await page.reload({ waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);

    const text = await page.locator('body').innerText();
    check('Token inválido derruba a sessão', (await page.getByLabel('E-mail').count()) === 1 || text.includes('Endereço do MAPOS'));
    check('Sessão foi limpa', (await page.evaluate(() => localStorage.getItem('mapos.session'))) === null);
    await shot('11-sessao-expirada');
  });

  // ---------------------------------------------------------------- deploy em subpasta
  await step('Publicado dentro da instalação (/mobile-app/)', async () => {
    const deploy = await browser.newContext({
      viewport: { width: 390, height: 844 },
      deviceScaleFactor: 2,
      isMobile: true,
      hasTouch: true,
      locale: 'pt-BR',
    });
    const dp = await deploy.newPage();
    dp.setDefaultTimeout(8000);
    const apiRequests = [];
    dp.on('request', (req) => {
      if (req.url().includes('/api/')) apiRequests.push(req.url());
    });
    await dp.goto(`${base}/mobile-app/`, { waitUntil: 'networkidle' });

    // abre direto no login, sem passar pela tela de URL
    const hasUrlField = (await dp.getByLabel('Endereço do MAPOS').count()) === 0;
    check('App abre direto no login (servidor detectado)', hasUrlField);
    await dp.getByLabel('E-mail').fill('tecnico@empresa.com');
    await dp.getByLabel('Senha').fill('senha123');
    await dp.getByRole('button', { name: 'Entrar' }).click();
    await dp.getByText('Ordens de serviço').first().waitFor({ timeout: 8000 });
    check('Login funciona publicado em subpasta', true);

    // a API tem de ser chamada na raiz da instalação, não em /mobile-app/
    const loginCall = apiRequests.find((u) => /\/api\/v1\/login$/.test(u.split('?')[0]));
    check(
      'API chamada na raiz da instalação',
      Boolean(loginCall) && loginCall.startsWith(`${base}/api/`),
      loginCall ?? (apiRequests.slice(0, 2).join(' | ') || 'nenhuma chamada /api/'),
    );

    const swUrl = await dp.evaluate(async () => {
      const race = await Promise.race([
        navigator.serviceWorker.ready.then(() => navigator.serviceWorker.controller?.scriptURL ?? 'registered'),
        new Promise((r) => setTimeout(() => r('timeout'), 4000)),
      ]);
      return String(race);
    });
    check('Service worker no caminho da subpasta', swUrl.includes('/mobile-app/sw.js'), swUrl);

    // assets em caminho relativo: a tela precisa ter estilos aplicados
    const styled = await dp.evaluate(() => {
      const el = document.querySelector('.btn, .tabbar, .topbar');
      if (!el) return 0;
      const bg = getComputedStyle(el).backgroundColor;
      return bg && bg !== 'rgba(0, 0, 0, 0)' ? 1 : 0;
    });
    check('CSS carregado a partir da subpasta', styled === 1);

    await dp.screenshot({ path: join(shots, '12-deploy-subpasta.png') });
    await deploy.close();
  });

  // ---------------------------------------------------------------- sem mod_rewrite
  await step('Instalação sem mod_rewrite (rota só via index.php)', async () => {
    // Caso real de jj-ferreiras.com.br/mapos: /api/v1 responde 404 e só
    // /index.php/api/v1 chega ao PHP. O cliente HTTP precisa cair para o index.php.
    const semRewrite = createFakeApi({
      noRewrite: true,
      dist: existsSync(join(published, 'index.html')) ? published : dist,
    });
    semRewrite.server.listen(0, '127.0.0.1');
    await once(semRewrite.server, 'listening');
    const altBase = `http://127.0.0.1:${semRewrite.server.address().port}`;

    const prettyStatus = await fetch(`${altBase}/api/v1/login`, { method: 'POST' }).then((r) => r.status);
    check('Fake sem rewrite devolve 404 na rota bonita', prettyStatus === 404, String(prettyStatus));

    const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
    const p = await ctx.newPage();
    p.setDefaultTimeout(10000);
    await p.goto(`${altBase}/`, { waitUntil: 'networkidle' });
    await p.getByLabel('Endereço do MAPOS').fill(altBase);
    await p.getByRole('button', { name: 'Continuar' }).click();
    await p.getByLabel('E-mail').fill('tecnico@empresa.com');
    await p.getByLabel('Senha').fill('senha123');
    await p.getByRole('button', { name: 'Entrar' }).click();
    await p.getByText('Ordens de serviço').first().waitFor({ timeout: 10000 });
    check('Login funciona com fallback para index.php', true);
    await p.screenshot({ path: join(shots, '13-sem-rewrite.png') });
    await ctx.close();
    semRewrite.server.close();
  });

  // ---------------------------------------------------------------- offline (PWA)
  await step('Abertura offline (service worker)', async () => {
    const fresh = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
    const offlinePage = await fresh.newPage();
    offlinePage.setDefaultTimeout(8000);
    await offlinePage.goto(`${base}/`, { waitUntil: 'networkidle' });

    // navigator.serviceWorker.ready nunca resolve se nada foi registrado — corre com timeout.
    const swState = await offlinePage.evaluate(async () => {
      if (!('serviceWorker' in navigator)) return 'unsupported';
      const race = await Promise.race([
        navigator.serviceWorker.ready.then(() => navigator.serviceWorker.controller?.scriptURL ?? 'registered'),
        new Promise((resolve) => setTimeout(() => resolve('timeout'), 4000)),
      ]);
      return String(race);
    });
    check('Service worker registrado no build', swState.includes('sw.js'), swState);

    await offlinePage.reload({ waitUntil: 'domcontentloaded' });
    await fresh.setOffline(true);
    await offlinePage.reload({ waitUntil: 'domcontentloaded' }).catch(() => undefined);
    await offlinePage.waitForTimeout(800);
    const offlineText = await offlinePage.locator('body').innerText().catch(() => '');
    check(
      'Shell carrega com a rede desligada',
      offlineText.includes('Endereço do MAPOS') || offlineText.includes('E-mail'),
      offlineText.slice(0, 60).replace(/\n/g, ' '),
    );
    await fresh.close();
  });

  // ---------------------------------------------------------------- console limpo
  await step('Console do navegador', async () => {
    // 401/403 são respostas esperadas (login inválido e token expirado no fluxo acima)
    const relevant = consoleErrors.filter(
      (msg) => !/favicon|ERR_INTERNET_DISCONNECTED|Failed to fetch|net::ERR|401|403|Unauthorized/i.test(msg),
    );
    check('Sem erros de JS no console', relevant.length === 0, relevant.slice(0, 3).join(' | '));
  });

  await browser.close();
  server.close();

  // ---------------------------------------------------------------- resumo
  const failed = results.filter((r) => !r.passed);
  const passed = results.length - failed.length;
  console.log(`\n${'─'.repeat(60)}`);
  console.log(`Resultado: ${passed} passaram, ${failed.length} falharam, de ${results.length} verificações.`);
  if (failed.length) {
    console.log('\nFalhas:');
    for (const f of failed) console.log(`  - ${f.name}${f.detail ? `: ${f.detail}` : ''}`);
    process.exit(1);
  }
  console.log(`Screenshots: ${shots}`);
  process.exit(0);
}

main().catch(async (error) => {
  console.error('\nErro fatal na validação:', error);
  if (browser) await browser.close().catch(() => undefined);
  process.exit(1);
});
