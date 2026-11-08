/**
 * Validação da Área do Técnico no app (navegador real, headless).
 *
 * Cobre: login caindo na área certa, painel (próxima visita, atenção, dia),
 * minhas OS com abas, atendimento com check-in, observação, check-out com as
 * duas assinaturas, serviço não realizado, projetos/pontos, fila e perfil.
 *
 * Uso: node teste/run-tecnico.mjs  (após `npm --prefix mobile run build`)
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
const shots = join(here, 'artefatos', 'tecnico');

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

/** Desenha um traço de assinatura com o mouse (eventos de ponteiro reais). */
async function assinar(page, canvas) {
  const box = await canvas.boundingBox();
  if (!box) throw new Error('canvas de assinatura sem medidas');
  const x0 = box.x + 24;
  const y0 = box.y + box.height / 2;
  await page.mouse.move(x0, y0);
  await page.mouse.down();
  for (let i = 0; i < 26; i++) {
    await page.mouse.move(x0 + i * 6, y0 + Math.sin(i / 2) * 22);
  }
  await page.mouse.up();
}

async function main() {
  console.log('MAPOS Mobile — Área do Técnico\n');
  if (!existsSync(join(dist, 'index.html'))) {
    console.error(`Build não encontrado em ${dist}. Rode: npm --prefix mobile run build`);
    process.exit(1);
  }

  rmSync(shots, { recursive: true, force: true });
  mkdirSync(shots, { recursive: true });

  const { server, state } = createFakeApi({ area: 'tecnico' });
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
    permissions: ['geolocation'],
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
  await step('Login cai direto na Área do Técnico', async () => {
    await page.goto(`${base}/`, { waitUntil: 'networkidle' });
    await page.getByLabel('Endereço do MAPOS').fill(base);
    await page.getByRole('button', { name: 'Continuar' }).click();
    await page.getByLabel('E-mail').fill('tecnico@empresa.com');
    await page.getByLabel('Senha').fill('senha123');
    await page.getByRole('button', { name: 'Entrar' }).click();

    await page.getByText('Ana Beatriz Souza').first().waitFor({ timeout: 9000 });
    check('Rota do técnico depois do login', page.url().endsWith('#/tecnico'), page.url());
    check('Não abre o painel administrativo', (await page.getByText('Ordens de serviço').count()) === 0);
    check('Sem rolagem horizontal', await semScroll());
    await shot('01-inicio');
  });

  // ------------------------------------------------------------ painel
  await step('Painel: próxima visita, atenção e dia', async () => {
    check('Próxima visita em andamento', await tem('Atendimento em andamento'));
    check('Cliente da próxima visita', await tem('Ana Beatriz Souza'));
    check('Endereço curto', await tem('Rua das Flores'));
    check('Bloco de atenção', await tem('Visitas atrasadas'));
    check('Linha do tempo do dia', await tem('09:30'));
    check('Números do dia', await tem('visitas hoje'));
    const tabs = await page.locator('.tabbar .tab').allInnerTexts();
    check('Abas do técnico', ['Início', 'Minhas OS', 'Projetos', 'Fila', 'Perfil'].every((t) => tabs.includes(t)), tabs.join(' | '));
  });

  // ------------------------------------------------------------ minhas OS
  await step('Minhas OS com abas e contadores', async () => {
    await page.locator('.tabbar .tab', { hasText: 'Minhas OS' }).click();
    await page.getByRole('tab', { name: /Hoje/ }).waitFor({ timeout: 6000 });
    check('Aba abre a lista', page.url().endsWith('#/tecnico/os'), page.url());
    const cards = await page.locator('.content .card .card__title').allInnerTexts();
    check('Lista as OS do técnico', cards.length >= 1, cards.slice(0, 3).join(' | '));
    check('Contador na aba', (await page.locator('.chip--active .chip__count').innerText()).length > 0);

    await page.getByRole('tab', { name: /Atrasadas/ }).click();
    await page.waitForTimeout(700);
    check('Aba atrasadas vazia mostra estado', (await tem('Nada atrasado')) || (await tem('Nenhuma OS')));
    await page.getByRole('tab', { name: /Todas/ }).click();
    await page.waitForTimeout(700);
    await shot('02-os');
  });

  // ------------------------------------------------------------ atendimento
  await step('Estou a caminho avisa o cliente', async () => {
    await page.goto(`${base}/#/tecnico/atendimento/1000`);
    await page.getByText('Ordem de serviço').waitFor({ timeout: 8000 });
    await page.getByRole('button', { name: /Estou a caminho/ }).click();
    await page.getByText('Cliente avisado às 09:10').waitFor({ timeout: 6000 });
    check('Aviso enviado para a OS certa', Number(state.aCaminho?.os_id) === 1000, JSON.stringify(state.aCaminho));
  });

  await step('Atendimento: check-in com observação', async () => {
    await page.goto(`${base}/#/tecnico/atendimento/1000`);
    await page.getByText('Ordem de serviço').waitFor({ timeout: 8000 });
    check('Detalhe carrega o cliente', await tem('Ana Beatriz Souza'));
    check('Mostra defeito relatado', await tem('Não liga após queda'));
    check('Mostra serviço previsto', await tem('Diagnóstico e reparo'));

    await page.getByRole('button', { name: /Iniciar atendimento/ }).click();
    const painel = page.locator('.sheet');
    await painel.getByLabel('Observação de chegada (opcional)').fill('Cheguei, cliente presente.');
    await painel.getByRole('button', { name: 'Iniciar', exact: true }).click();

    await page.getByText('Atendimento iniciado.').waitFor({ timeout: 8000 });
    check('Check-in confirmado na tela', true);
    check('Servidor recebeu o check-in', state.checkin?.os_id === 1000, JSON.stringify(state.ultimoCheckin));
    check('GPS enviado no check-in', Number(state.ultimoCheckin?.latitude) < -23, String(state.ultimoCheckin?.latitude));
    check('Observação enviada', state.ultimoCheckin?.observacao === 'Cheguei, cliente presente.');
    await shot('03-checkin');
  });

  await step('Observação do atendimento', async () => {
    const antes = state.observacoes.length;
    await page.getByRole('button', { name: /^Observação/ }).click();
    const painel = page.locator('.sheet');
    await painel.getByLabel('Observação do atendimento').fill('Fonte substituída e testada.');
    await painel.getByRole('button', { name: 'Salvar' }).click();
    // .first(): durante o recarregamento o aviso pode aparecer duas vezes por um instante.
    await page.getByText('Observação registrada.').first().waitFor({ timeout: 8000 });
    check('Observação salva no servidor', state.observacoes.length === antes + 1);
    // a tela recarrega o detalhe depois do aviso; espera o texto chegar
    await page.getByText('Fonte substituída e testada.').waitFor({ timeout: 8000 });
    check('Observação aparece na tela', true);
  });

  await step('Check-out exige assinatura do técnico', async () => {
    await page.getByRole('button', { name: /^Finalizar/ }).click();
    const painel = page.locator('.sheet');
    await painel.getByRole('heading', { name: 'Finalizar atendimento' }).waitFor();
    const finalizar = painel.getByRole('button', { name: 'Finalizar', exact: true });
    check('Botão finalizar bloqueado sem assinatura', await finalizar.isDisabled());

    const canvases = painel.locator('canvas');
    await assinar(page, canvases.nth(0));
    await page.waitForTimeout(200);
    check('Ainda bloqueado sem a assinatura do cliente', await finalizar.isDisabled());

    await assinar(page, canvases.nth(1));
    await page.waitForTimeout(300);
    check('Libera com as duas assinaturas', await finalizar.isEnabled());

    await finalizar.click();
    await page.getByText('Atendimento finalizado.').waitFor({ timeout: 9000 });
    check('Check-out gravado no servidor', Number(state.ultimoCheckout?.os_id) === 1000, String(state.ultimoCheckout?.os_id));
    check('Assinatura do técnico em PNG', String(state.ultimoCheckout?.assinatura ?? '').startsWith('data:image/png;base64,'));
    check('Assinatura do cliente em PNG', String(state.ultimoCheckout?.assinatura_cliente ?? '').startsWith('data:image/png;base64,'));
    await shot('04-checkout');
  });

  // ------------------------------------------------------------ não realizado
  await step('Serviço não realizado', async () => {
    await page.goto(`${base}/#/tecnico/atendimento/1001`);
    await page.getByText('Ordem de serviço').waitFor({ timeout: 8000 });
    await page.getByRole('button', { name: /Não deu para fazer/ }).click();
    const painel = page.locator('.sheet');
    await painel.getByLabel('Motivo').selectOption('1');
    await painel.getByLabel('O que aconteceu').fill('Portão fechado, ninguém atendeu.');
    await painel.getByRole('button', { name: 'Registrar' }).click();
    await page.getByText('Registrado. A OS foi para "Não Realizadas".').waitFor({ timeout: 8000 });
    check('Registro no servidor', state.naoRealizadas.length === 1);
    check('Motivo padronizado enviado', state.naoRealizadas[0]?.motivo === 'Cliente ausente');

    await page.goto(`${base}/#/tecnico/nao-realizadas`);
    await page.getByText('Não realizadas').first().waitFor({ timeout: 6000 });
    check('Lista mostra o registro', await tem('Portão fechado'));
    check('Oferece reagendar e reabrir', (await tem('Reagendar')) && (await tem('Reabrir')));
    await shot('05-nao-realizadas');
  });

  // ------------------------------------------------------------ projetos
  await step('Projetos, pontos e fila', async () => {
    await page.goto(`${base}/#/tecnico/projetos`);
    await page.getByText('Condomínio Vila Verde').first().waitFor({ timeout: 6000 });
    check('Projeto com progresso', await tem('40%'));
    await shot('06-projetos');

    await page.getByText('Condomínio Vila Verde').first().click();
    await page.getByText('Andamento').first().waitFor({ timeout: 6000 });
    check('Detalhe lista pontos', (await tem('CAM-01')) && (await tem('NVR-01')));
    check('Mostra OS vinculada', await tem('OS 1001'));
    check('Mostra consumo', await tem('Cabo UTP'));

    await page.getByText('CAM-01').first().click();
    await page.getByText('Responsável').waitFor({ timeout: 6000 });
    check('Ponto carrega status e avanço', (await tem('Previsto')) && (await tem('Avançar para')));
    check('Ponto abre rota própria', page.url().includes('#/tecnico/ponto/1/1'), page.url());
    await shot('07-ponto');

    await page.goto(`${base}/#/tecnico/fila`);
    await page.getByText('Minha fila').first().waitFor({ timeout: 6000 });
    check('Fila separa comigo e equipe', (await tem('Com você')) && (await tem('Livres na equipe')));
    await shot('08-fila');
  });

  // ------------------------------------------------------------ câmeras/NVR
  await step('Câmeras e NVR do projeto', async () => {
    await page.goto(`${base}/#/tecnico/camera/1`);
    await page.getByText('Câmeras e NVR').first().waitFor({ timeout: 6000 });
    check('Lista as câmeras previstas', (await tem('CAM-01')) && (await tem('CAM-02')));
    check('Mostra o NVR do projeto', await tem('NVR-01'));
    check('Avisa que a busca é só no APK', await tem('só funciona no aplicativo Android'));
    check('Sem rolagem horizontal', await semScroll());
    await shot('10-cameras');

    await page.getByText('CAM-01').first().click();
    const painelCam = page.locator('.sheet');
    await painelCam.getByLabel('IP da câmera').fill('192.168.1.32');
    await painelCam.getByLabel('Canal no NVR').fill('3');
    await painelCam.getByRole('button', { name: 'Salvar' }).click();
    await page.getByText('CAM-01 configurado.').waitFor({ timeout: 8000 });
    check(
      'Envia a configuração ao servidor',
      state.ultimaCamera?.ip === '192.168.1.32' && state.ultimaCamera?.canal === '3',
      JSON.stringify(state.ultimaCamera),
    );
  });

  // ------------------------------------------------------------ perfil
  await step('Perfil: contato editável', async () => {
    await page.goto(`${base}/#/tecnico/perfil`);
    await page.getByText('Meu perfil').first().waitFor({ timeout: 6000 });
    check('Mostra cargo e números do mês', (await tem('Técnico de campo')) && (await tem('OS atendidas no mês')));
    check('Mostra equipes', await tem('Equipe A'));

    await page.getByRole('button', { name: 'Editar' }).click();
    await page.getByLabel('Celular').fill('(11) 91234-0000');
    await page.getByRole('button', { name: 'Salvar' }).click();
    await page.getByText('Contato atualizado.').waitFor({ timeout: 6000 });
    check('PUT do perfil enviado', state.perfilAtualizado?.celular === '(11) 91234-0000', JSON.stringify(state.perfilAtualizado));
    await shot('09-perfil');
  });

  // ------------------------------------------------------------ relatório
  await step('Meu relatório: totais, projetos e troca de período', async () => {
    await page.goto(`${base}/#/tecnico`);
    await page.getByRole('button', { name: 'Meu relatório' }).click();
    await page.getByText('Condomínio Vila Verde').waitFor({ timeout: 6000 });
    check('Mostra os números do período', (await tem('Pontos avançados')) && (await tem('35,5')));
    check('Mostra registro e ocorrência', (await tem('Passagem de cabos no bloco B')) && (await tem('Falta de escada')));
    check('Mostra o avanço do ponto', await tem('CAM-07 (Corredor B) → Cabo passado · 35 m'));
    await page.getByRole('tab', { name: 'Semana' }).click();
    await page.waitForTimeout(600);
    check('Pede o relatório semanal', state.relatorioPedidos?.at(-1)?.tipo === 'semanal', JSON.stringify(state.relatorioPedidos?.at(-1)));
    await page.getByRole('button', { name: 'Período anterior' }).click();
    await page.waitForTimeout(600);
    check('Volta para o período anterior', state.relatorioPedidos?.at(-1)?.data === '2026-11-03');
    check('Sem rolagem horizontal no relatório', await semScroll());
    await shot('10-relatorio');
  });

  // ------------------------------------------------------------ solicitante
  await step('Assinatura do solicitante na atividade não programada', async () => {
    state.naoProgramada = true;
    await page.goto(`${base}/#/tecnico/atendimento/1000`);
    await page.getByText('Assinatura do solicitante').first().waitFor({ timeout: 8000 });

    await page.getByRole('button', { name: 'Gerar link para o solicitante' }).click();
    await page.getByText(/Link gerado/).waitFor({ timeout: 6000 });
    check('Link aparece para copiar', await tem('/aceite/abc123'));

    await page.getByRole('button', { name: 'Enviar link por e-mail' }).click();
    const painelEmail = page.locator('.sheet');
    check('E-mail do cliente já preenchido', (await painelEmail.getByLabel('E-mail do solicitante').inputValue()) === 'ana@cliente.com');
    await painelEmail.getByRole('button', { name: 'Enviar' }).click();
    await page.getByText(/E-mail enfileirado/).waitFor({ timeout: 6000 });
    check('Servidor recebeu o e-mail', state.solicitante?.email === 'ana@cliente.com');

    await page.getByRole('button', { name: 'Assinar no aparelho' }).click();
    const painel = page.locator('.sheet');
    await painel.getByLabel('Nome de quem assina').fill('João Solicitante');
    const salvar = painel.getByRole('button', { name: 'Salvar assinatura' });
    check('Salvar bloqueado sem traço', await salvar.isDisabled());
    await assinar(page, painel.locator('canvas'));
    await page.waitForTimeout(300);
    await salvar.click();
    await page.getByText('Assinado por João Solicitante').waitFor({ timeout: 6000 });
    check('Assinatura em PNG com o nome', state.solicitante?.nome === 'João Solicitante' && String(state.solicitante?.imagem).startsWith('data:image/png;base64,'));
    await shot('11-solicitante');
    state.naoProgramada = false;
  });

  await step('Cliente que exige chamado bloqueia atividade sem o Nº', async () => {
    state.clientes.push({ idClientes: 9, nomeCliente: 'Pátio Gourmet TGFoods', celular: '(11) 95555-4444', documento: '98.765.432/0001-10', exige_chamado: 1 });
    state.ultimaAtividade = null;
    await page.goto(`${base}/#/tecnico/atividade`);
    await page.getByLabel('Buscar cliente').fill('Pátio');
    await page.getByRole('button', { name: /Pátio Gourmet TGFoods/ }).click();
    await page.getByText('Nº do chamado (obrigatório)').waitFor({ timeout: 6000 });
    check('Campo do chamado vira obrigatório', true);

    await page.getByRole('button', { name: 'Abrir atividade' }).click();
    await page.getByText('Este cliente exige o Nº do chamado').first().waitFor({ timeout: 6000 });
    check('Não envia sem o chamado', state.ultimaAtividade === null);

    await page.getByLabel('Nº do chamado (obrigatório)').fill('CH-2026-118');
    await page.getByLabel('Atividade realizada').fill('Troca de fonte do DVR');
    await page.getByRole('button', { name: 'Abrir atividade' }).click();
    await page.waitForTimeout(1200);
    check('Envia com o chamado', state.ultimaAtividade?.chamado_id === 'CH-2026-118', JSON.stringify(state.ultimaAtividade?.chamado_id));
    await shot('12-atividade-chamado');
  });

  await step('Posição enviada para o mapa em tempo real', async () => {
    const p = state.posicoes?.[0];
    check('App enviou a posição com GPS já permitido', Boolean(p) && Number(p.latitude) < -23, JSON.stringify(p));
  });

  // ------------------------------------------------------------ limpeza
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
