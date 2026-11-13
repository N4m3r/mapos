/**
 * Validação da Área do Colaborador no app (navegador real, headless).
 *
 * Cobre: login caindo na área do colaborador, painel com totais, bater ponto
 * (selfie + GPS), espelho do mês, holerite com PDF, solicitações, ausências e
 * meus dados.
 *
 * Uso: node teste/run-colaborador.mjs  (após `npm --prefix mobile run build`)
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
const shots = join(here, 'artefatos', 'colaborador');

// O servidor falso serve o build desta pasta (padrão do fake-api).
process.env.MAPOS_DIST = dist;

const results = [];
let browser;

function check(name, passed, detail = '') {
  results.push({ name, passed, detail });
  console.log(`  [${passed ? 'PASS' : 'FAIL'}] ${name}${detail ? ` — ${detail}` : ''}`);
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
    '/usr/bin/google-chrome',
    '/usr/bin/chromium',
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  ].filter(Boolean);
  return candidates.find((c) => c && existsSync(c)) ?? null;
}

async function main() {
  console.log('MAPOS Mobile — Área do Colaborador\n');
  if (!existsSync(join(dist, 'index.html'))) {
    console.error(`Build não encontrado em ${dist}. Rode: npm --prefix mobile run build`);
    process.exit(1);
  }

  rmSync(shots, { recursive: true, force: true });
  mkdirSync(shots, { recursive: true });

  const { server, state } = createFakeApi({ area: 'colaborador' });
  server.listen(0, '127.0.0.1');
  await once(server, 'listening');
  const base = `http://127.0.0.1:${server.address().port}`;
  console.log(`Servidor de teste: ${base}`);

  const executablePath = findChrome();
  if (!executablePath) {
    console.error('Chrome/Edge não encontrado. Defina CHROME_PATH.');
    server.close();
    process.exit(1);
  }

  browser = await chromium.launch({
    executablePath,
    headless: true,
    args: ['--use-fake-ui-for-media-stream', '--use-fake-device-for-media-stream'],
  });
  const context = await browser.newContext({
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 2,
    isMobile: true,
    hasTouch: true,
    locale: 'pt-BR',
    timezoneId: 'America/Sao_Paulo',
    permissions: ['geolocation', 'camera'],
    geolocation: { latitude: -23.55, longitude: -46.63, accuracy: 12 },
  });
  const page = await context.newPage();
  page.setDefaultTimeout(9000);
  page.setDefaultNavigationTimeout(15000);

  const consoleErrors = [];
  page.on('console', (m) => {
    if (m.type() === 'error') consoleErrors.push(m.text());
  });
  page.on('pageerror', (e) => consoleErrors.push(e.message));

  const shot = (name) => page.screenshot({ path: join(shots, `${name}.png`), fullPage: false });
  const texto = () => page.locator('body').innerText();
  /** Compara ignorando caixa: os títulos usam text-transform: uppercase. */
  const tem = async (trecho) => (await texto()).toLowerCase().includes(trecho.toLowerCase());
  const semScroll = () => page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1);

  // ------------------------------------------------------------ login
  await step('Login cai direto na Área do Colaborador', async () => {
    await page.goto(`${base}/`, { waitUntil: 'networkidle' });
    await page.getByLabel('Endereço do MAPOS').fill(base);
    await page.getByRole('button', { name: 'Continuar' }).click();
    await page.getByLabel('E-mail').fill('tecnico@empresa.com');
    await page.getByLabel('Senha').fill('senha123');
    await page.getByRole('button', { name: 'Entrar' }).click();

    await page.getByText('Maria Colaboradora').first().waitFor({ timeout: 9000 });
    check('Rota do colaborador depois do login', page.url().endsWith('#/colaborador'), page.url());
    check('Não abre o painel administrativo', (await page.getByText('Ordens de serviço').count()) === 0);
    check('Sem rolagem horizontal', await semScroll());
    await shot('01-inicio');
  });

  // ------------------------------------------------------------ painel
  await step('Painel: batidas, totais e atalhos', async () => {
    check('Colaborador identificado', await tem('Maria'));
    check('Cargo exibido', await tem('Auxiliar administrativo'));
    check('Totais do mês', (await tem('Banco de horas')) && (await tem('Extras no mês')));
    check('Atalhos da área', (await tem('Espelho de ponto')) && (await tem('Holerite')));
    check('Sem batida hoje mostra estado', await tem('Nenhuma batida hoje'));
    const tabs = await page.locator('.tabbar .tab').allInnerTexts();
    check('Abas do colaborador', ['Início', 'Ponto', 'Espelho', 'Solicitar'].every((t) => tabs.includes(t)), tabs.join(' | '));
  });

  // ------------------------------------------------------------ ponto
  await step('Bater ponto: selfie e GPS', async () => {
    await page.locator('.tabbar .tab', { hasText: 'Ponto' }).click();
    await page.getByText('Próxima batida').waitFor({ timeout: 8000 });
    check('Abre a tela do ponto', page.url().endsWith('#/colaborador/ponto'), page.url());
    await page.waitForTimeout(1200);

    const antes = state.batidas.length;
    await page.getByRole('button', { name: 'Registrar Entrada' }).click();
    await page.getByText(/registrada às/).waitFor({ timeout: 9000 });

    check('Batida gravada no servidor', state.batidas.length === antes + 1, String(state.batidas.length));
    check('Tipo da primeira batida', state.batidas[0]?.tipo === 'entrada', state.batidas[0]?.tipo);
    check('GPS enviado', Number(state.ultimaBatida?.latitude) < -23, String(state.ultimaBatida?.latitude));
    check('Selfie em JPEG enviada', String(state.ultimaBatida?.foto ?? '').startsWith('data:image/jpeg;base64,'));
    check('Próxima batida vira intervalo', (await texto()).includes('Início do intervalo'));
    await shot('02-ponto');

    await page.getByRole('button', { name: 'Encerrar turno (saída)' }).click();
    await page.waitForTimeout(1200);
    check('Saída registrada', state.batidas.some((b) => b.tipo === 'saida'));
  });

  // ------------------------------------------------------------ espelho
  await step('Espelho de ponto por competência', async () => {
    await page.locator('.tabbar .tab', { hasText: 'Espelho' }).click();
    await page.getByText('Espelho de ponto').first().waitFor({ timeout: 8000 });
    check('Mostra totais da competência', (await tem('Trabalhado')) && (await tem('Saldo')));
    check('Lista os dias do mês', (await tem('03/11')) && (await tem('04/11')));
    check('Dia sem batida fica legível', await tem('02/11'));
    check('Saldo negativo destacado', (await page.locator('.day__saldo--neg').count()) >= 1);

    await page.getByRole('button', { name: '‹ Anterior' }).click();
    await page.waitForTimeout(900);
    check('Navega para o mês anterior', await tem('outubro/2026'));
    await shot('03-espelho');
  });

  // ------------------------------------------------------------ holerite
  await step('Holerite com resumo e PDF', async () => {
    await page.goto(`${base}/#/colaborador/holerite`);
    await page.getByText('Holerite').first().waitFor({ timeout: 8000 });
    check('Proventos e descontos', (await tem('Proventos')) && (await tem('Descontos')));
    check('Líquido calculado', await tem('2.720'));
    check('Itens detalhados', (await tem('Salário base')) && (await tem('INSS')));
    check('PDF liberado', (await page.getByRole('button', { name: 'Abrir PDF do holerite' }).count()) === 1);
    await shot('04-holerite');
  });

  // ------------------------------------------------------------ solicitações
  await step('Solicitação de correção de ponto', async () => {
    await page.goto(`${base}/#/colaborador/solicitacoes`);
    await page.getByText('Justificar / corrigir').first().waitFor({ timeout: 8000 });
    await page.getByRole('button', { name: 'Nova correção de ponto' }).click();

    const card = page.locator('.card').filter({ hasText: 'Nova solicitação' });
    await card.getByLabel('O que deseja justificar / corrigir?').selectOption('entrada');
    await card.getByLabel('Motivo').fill('Esqueci de bater a entrada antes de sair para o cliente.');
    await card.getByRole('button', { name: 'Enviar para o RH' }).click();

    await page.getByText('Solicitação enviada para o RH.').waitFor({ timeout: 9000 });
    check('Solicitação gravada', state.ocorrencias.length === 1);
    check('Tipo correto', state.ocorrencias[0]?.tipo === 'correcao_ponto');
    await page.getByText('Esqueci de bater a entrada').waitFor({ timeout: 8000 });
    check('Aparece no histórico', true);
    await shot('05-solicitacoes');
  });

  await step('Pedido de férias', async () => {
    await page.goto(`${base}/#/colaborador/ausencias`);
    await page.getByText('Folga / férias').first().waitFor({ timeout: 8000 });
    await page.getByRole('button', { name: 'Novo pedido' }).click();

    const card = page.locator('.card').filter({ hasText: 'Nova ausência' });
    await card.getByLabel('Tipo').selectOption('ferias');
    await card.getByRole('button', { name: 'Enviar pedido' }).click();
    await page.getByText('Solicitação enviada para o RH.').waitFor({ timeout: 9000 });
    check('Ausência gravada', state.ausencias.length === 1);
    check('Tipo férias', state.ausencias[0]?.tipo === 'ferias');
    await shot('06-ausencias');
  });

  // ------------------------------------------------------------ meus dados
  await step('Meus dados: contato e PIX', async () => {
    await page.goto(`${base}/#/colaborador/dados`);
    await page.getByText('Meus dados').first().waitFor({ timeout: 8000 });
    check('Mostra admissão e unidade', (await tem('01/03/2024')) && (await tem('Matriz')));
    check('Mostra chave PIX', await tem('maria@empresa.com'));

    await page.getByRole('button', { name: 'Editar' }).click();
    await page.getByLabel('Chave PIX', { exact: true }).fill('11977776666');
    await page.getByLabel('Tipo de chave PIX').selectOption('telefone');
    await page.getByRole('button', { name: 'Salvar' }).click();
    await page.getByText('Dados atualizados.').waitFor({ timeout: 9000 });
    check('Dados enviados ao servidor', state.dadosAtualizados?.pix_tipo === 'telefone', JSON.stringify(state.dadosAtualizados));
    await shot('07-dados');
  });

  // ------------------------------------------------------------ limpeza
  await step('Sem internet: batida guardada no aparelho sai com a hora certa', async () => {
    await page.waitForFunction(() => Boolean(localStorage.getItem('mapos.offline.download')), null, { timeout: 15000 });
    await context.setOffline(true);
    await page.evaluate(() => { location.hash = '#/colaborador/ponto'; });
    await page.getByText('Próxima batida').waitFor({ timeout: 8000 });
    await page.waitForTimeout(1200);

    const antes = state.batidas.length;
    await page.getByRole('button', { name: 'Registrar Entrada' }).click();
    await page.getByText(/registrada às/).waitFor({ timeout: 9000 });
    check('Batida aceita sem internet', state.batidas.length === antes);
    const pendente = await page.getByText(/aguardando internet/).first().waitFor({ timeout: 6000 }).then(() => true, () => false);
    check('Batida fica aguardando internet', pendente);
    await shot('09-ponto-offline');

    await context.setOffline(false);
    const entregue = await page
      .waitForFunction(() => !document.body.innerText.includes('aguardando internet'), null, { timeout: 20000 })
      .then(() => true, () => false);
    check('Internet voltou: batida entregue', entregue && state.batidas.length === antes + 1, String(state.batidas.length - antes));
    check('Batida leva a hora do aparelho', /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(String(state.ultimaBatida?.registrado_em)), String(state.ultimaBatida?.registrado_em));
  });

  await step('Console do navegador', async () => {
    const relevantes = consoleErrors.filter((m) => !/favicon|Failed to fetch|net::ERR|401|403/i.test(m));
    check('Sem erros de JS', relevantes.length === 0, relevantes.slice(0, 3).join(' | '));
  });

  await browser.close();
  server.close();

  const failed = results.filter((r) => !r.passed);
  console.log(`\n${'─'.repeat(60)}`);
  console.log(`Resultado: ${results.length - failed.length} passaram, ${failed.length} falharam, de ${results.length}.`);
  if (failed.length) {
    console.log('\nFalhas:');
    for (const f of failed) console.log(`  - ${f.name}${f.detail ? `: ${f.detail}` : ''}`);
    process.exit(1);
  }
  console.log(`Screenshots: ${shots}`);
  process.exit(0);
}

main().catch(async (error) => {
  console.error('\nErro fatal:', error);
  if (browser) await browser.close().catch(() => undefined);
  process.exit(1);
});
