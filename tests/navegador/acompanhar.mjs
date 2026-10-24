/**
 * Acompanhamento publico da OS + avaliacao NPS (ADR-007) no Chrome headless.
 *
 * Variaveis: BASE_URL, EMAIL, SENHA, CHROME (como telas.mjs) e OS_ID (OS Finalizada ou Faturada
 * sem avaliacao). Testa: link pela OS (usuario logado), pagina publica sem login, recusa de token
 * invalido, avaliacao gravada uma vez e o relatorio NPS mostrando a nota.
 */
import puppeteer from 'puppeteer-core';

const BASE = (process.env.BASE_URL || 'http://localhost:8099/').replace(/\/?$/, '/');
const EMAIL = process.env.EMAIL || 'admin@mapos.test';
const SENHA = process.env.SENHA || 'admin123';
const CHROME = process.env.CHROME || '/usr/bin/google-chrome';
const OS_ID = process.env.OS_ID || '1';

let falhas = 0;
const ok = (cond, msg) => {
    console.log((cond ? 'OK    ' : 'FALHA ') + msg);
    if (!cond) falhas++;
};

const navegador = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
const problemas = [];
const nova = async (contexto) => {
    const p = await contexto.newPage();
    p.on('pageerror', (e) => problemas.push('JS: ' + e.message.slice(0, 160)));
    p.on('console', (m) => /Content Security Policy/i.test(m.text()) && problemas.push('CSP: ' + m.text().slice(0, 160)));
    return p;
};

// Usuario logado gera o link pelo botao da OS
const admin = await nova(navegador.defaultBrowserContext());
await admin.goto(BASE + 'index.php/login', { waitUntil: 'networkidle2' });
await admin.type('#email', EMAIL);
await admin.type('#senha', SENHA);
await Promise.all([admin.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => null), admin.click('#btn-acessar')]);
await admin.goto(BASE + 'index.php/os/visualizar/' + OS_ID, { waitUntil: 'networkidle2' });
const href = await admin.$eval('a[href*="os/acompanhamento/"]', (a) => a.href).catch(() => null);
ok(!!href, 'botao Acompanhamento na OS');
await admin.goto(href, { waitUntil: 'networkidle2' });
const link = admin.url();
ok(/acompanhar\/[a-f0-9]{64}$/.test(link), 'link publico gerado (' + link.split('index.php/')[1].slice(0, 22) + '...)');

// Cliente sem login (contexto anonimo)
const anonimo = await navegador.createBrowserContext();
const cli = await nova(anonimo);
const resp = await cli.goto(link, { waitUntil: 'networkidle2' });
const texto = await cli.evaluate(() => document.body.innerText);
ok(resp.status() === 200 && texto.includes('Ordem de Serviço #' + OS_ID), 'pagina publica abre sem login');
ok(!/R\$/.test(texto), 'pagina publica nao mostra valores');
ok((await cli.$$('.ac-etapas li')).length >= 5, 'linha do tempo exibida');
ok((await cli.goto(link.replace(/.$/, link.endsWith('0') ? '1' : '0'))).status() === 404, 'token invalido responde 404');

await cli.goto(link, { waitUntil: 'networkidle2' });
ok(!!(await cli.$('input[name="nota"]')), 'formulario NPS para OS concluida');
await cli.click('label[for="nota9"]');
await cli.type('#comentario', 'Atendimento rapido (teste automatico)');
await Promise.all([cli.waitForNavigation({ waitUntil: 'networkidle2' }), cli.click('form[action$="acompanhar/avaliar"] button')]);
const depois = await cli.evaluate(() => document.body.innerText);
ok(depois.includes('Obrigado pela sua avaliação') && depois.includes('9/10'), 'avaliacao gravada');
ok(!(await cli.$('input[name="nota"]')), 'formulario some depois de avaliar (uma por OS)');

// Relatorio NPS
await admin.goto(BASE + 'index.php/relatorios/nps', { waitUntil: 'networkidle2' });
const rel = await admin.evaluate(() => document.body.innerText);
ok(rel.includes('#' + OS_ID) && rel.includes('Atendimento rapido'), 'relatorio NPS lista a avaliacao');
ok(problemas.length === 0, 'sem erro de JS/CSP ' + JSON.stringify(problemas));

await navegador.close();
console.log(falhas ? `\n${falhas} verificacao(oes) falharam` : '\nAcompanhamento e NPS OK');
process.exit(falhas ? 1 : 0);
