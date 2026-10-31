/**
 * Relatorio de cobranca do mes (ADR-011) no Chrome headless.
 *
 * Variaveis: BASE_URL, EMAIL, SENHA, CHROME e COMPETENCIA (YYYY-MM; padrao: mes atual).
 * Testa: abertura da tela, cabecalho, cartoes de situacao, botao imprimir, filtro por situacao,
 * midia print (filtros ocultos, cabecalho de impressao visivel) e geracao de PDF.
 */
import puppeteer from 'puppeteer-core';

const BASE = (process.env.BASE_URL || 'http://localhost:8099/').replace(/\/?$/, '/');
const EMAIL = process.env.EMAIL || 'admin@mapos.test';
const SENHA = process.env.SENHA || 'admin123';
const CHROME = process.env.CHROME || '/usr/bin/google-chrome';
const agora = new Date();
const COMPETENCIA = process.env.COMPETENCIA || `${agora.getFullYear()}-${String(agora.getMonth() + 1).padStart(2, '0')}`;

let falhas = 0;
const ok = (cond, msg) => {
    console.log((cond ? 'OK    ' : 'FALHA ') + msg);
    if (!cond) falhas++;
};

const navegador = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
const problemas = [];
const page = await navegador.newPage();
page.on('pageerror', (e) => problemas.push('JS: ' + e.message.slice(0, 160)));
page.on('console', (m) => /Content Security Policy/i.test(m.text()) && problemas.push('CSP: ' + m.text().slice(0, 160)));

await page.goto(BASE + 'index.php/login', { waitUntil: 'networkidle2' });
await page.type('#email', EMAIL);
await page.type('#senha', SENHA);
await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => null), page.click('#btn-acessar')]);

const url = BASE + 'index.php/relatoriocobranca?competencia=' + COMPETENCIA;
const resp = await page.goto(url, { waitUntil: 'networkidle2' });
ok(resp.status() === 200, 'tela abre com status 200 (' + resp.status() + ')');
const texto = await page.evaluate(() => document.body.innerText);
ok(texto.includes('Cobrança do mês'), 'cabecalho "Cobrança do mês"');
ok((await page.$$('.ui-kpis .ui-kpi, .ui-kpis > *')).length >= 5, 'cartoes de situacao');
ok(!!(await page.$('[data-acao="imprimir"]')), 'botao imprimir');

// Filtro por situacao
const marcou = await page.evaluate(() => {
    const c = document.querySelector('input[name="situacao[]"][value="nao_cobrado"]');
    if (!c) return false;
    document.querySelectorAll('input[name="situacao[]"]').forEach((i) => (i.checked = false));
    c.checked = true;
    return true;
});
ok(marcou, 'checkbox situacao[] nao_cobrado existe');
if (marcou) {
    const form = await page.evaluateHandle(() => document.querySelector('input[name="situacao[]"][value="nao_cobrado"]').form);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle2' }),
        form.evaluate((f) => (f.requestSubmit ? f.requestSubmit() : f.submit())),
    ]);
    const selos = await page.$$eval('table .ui-badge, table .ui-selo, table [class*="selo"], table [class*="badge"]', (els) => els.map((e) => e.textContent.trim()));
    const vazio = await page.evaluate(() => !!document.querySelector('.ui-vazio, .ui-empty, [class*="vazio"]'));
    ok(selos.length ? selos.every((s) => s === 'Não cobrado') : vazio || !document.querySelector('table tbody tr'), `filtro nao_cobrado (${selos.length} selos)`);
}

// Impressao
await page.emulateMediaType('print');
const imp = await page.evaluate(() => {
    const visivel = (el) => !!el && getComputedStyle(el).display !== 'none' && getComputedStyle(el).visibility !== 'hidden';
    const filtros = [...document.querySelectorAll('.ui-filtros')];
    const cab = document.querySelector('.so-impressao');
    return { filtrosOcultos: filtros.every((f) => !visivel(f)), cabecalho: visivel(cab) };
});
ok(imp.filtrosOcultos, 'midia print oculta .ui-filtros');
ok(imp.cabecalho, 'midia print mostra .so-impressao');
const pdf = await page.pdf({ format: 'A4' });
ok(pdf.length > 0, 'PDF gerado (' + pdf.length + ' bytes)');
await page.emulateMediaType('screen');

ok(problemas.length === 0, 'sem erro de JS/CSP ' + JSON.stringify(problemas));

await navegador.close();
console.log(falhas ? `\n${falhas} verificacao(oes) falharam` : '\nRelatorio de cobranca OK');
process.exit(falhas ? 1 : 0);
