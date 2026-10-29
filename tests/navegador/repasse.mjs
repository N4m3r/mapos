/**
 * Repasse de pontos entre tecnicos (ADR-014) no Chrome headless.
 *
 * Fluxo: o instalador marca "Instalei" com foto e repassa ao configurador; o configurador
 * ve na fila, configura (IP/canal); o gestor repassa outro ponto para a equipe e o
 * configurador assume. Confere fotos, painel e relatorio.
 *
 * Variaveis: BASE_URL, CHROME, FOTO (jpg grande), OBRA_ID,
 *   PONTO_CABEADO (ponto em "cabeado"), PONTO_INSTALADO (ponto em "instalado", outro),
 *   EMAIL/SENHA (gestor com vObras/eObras), EMAIL_INST/EMAIL_CONF/SENHA_TEC (tecnicos com
 *   acesso ao projeto), CONF_ID (idUsuarios do configurador), IGNORAR_CSP (trecho de URL).
 */
import puppeteer from 'puppeteer-core';

const env = process.env;
const BASE = (env.BASE_URL || 'http://localhost:8099/').replace(/\/?$/, '/');
const OBRA = env.OBRA_ID;
const P1 = env.PONTO_CABEADO;
const P2 = env.PONTO_INSTALADO;
const IGNORAR = env.IGNORAR_CSP || '';

let falhas = 0;
const ok = (cond, msg) => {
    console.log((cond ? 'OK    ' : 'FALHA ') + msg);
    if (!cond) falhas++;
};

const navegador = await puppeteer.launch({ executablePath: env.CHROME, headless: 'new', args: ['--no-sandbox'] });
const problemas = [];

async function sessao(email, senha, largura) {
    const ctx = await navegador.createBrowserContext();
    const page = await ctx.newPage();
    await page.setViewport({ width: largura, height: 900 });
    page.on('pageerror', (e) => problemas.push('JS: ' + e.message.slice(0, 160)));
    page.on('console', (m) => {
        const t = m.text();
        if (/Content Security Policy/i.test(t) && !(IGNORAR && t.includes(IGNORAR))) problemas.push('CSP: ' + t.slice(0, 160));
    });
    await page.goto(BASE + 'index.php/login', { waitUntil: 'networkidle2' });
    await page.type('#email', email);
    await page.type('#senha', senha);
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => null), page.click('#btn-acessar')]);
    return page;
}

const texto = (p) => p.evaluate(() => document.body.innerText);
const enviar = (p, sel) => Promise.all([
    p.waitForNavigation({ waitUntil: 'networkidle2' }),
    p.$eval(sel, (f) => (f.requestSubmit ? f.requestSubmit() : f.submit())),
]);
const fotoCarregou = (p, sel) => p.$$eval(sel, (imgs) => imgs.length > 0 && imgs.every((i) => i.complete && i.naturalWidth > 0));

// ---------- 1. Instalador ----------
const inst = await sessao(env.EMAIL_INST, env.SENHA_TEC, 390);
await inst.goto(BASE + `index.php/tecnico/projeto/${OBRA}#ponto-${P1}`, { waitUntil: 'networkidle2' });
await inst.evaluate(() => document.querySelectorAll('details').forEach((d) => (d.open = true)));
const fInst = `#ponto-${P1} form[data-exige-foto]`;
ok(!!(await inst.$(fInst)), 'instalador: form "Instalei" com foto obrigatoria');

// Sem foto: o envio e barrado no navegador
const urlAntes = inst.url();
await inst.$eval(fInst, (f) => f.requestSubmit());
await new Promise((r) => setTimeout(r, 600));
const erroVisivel = await inst.$eval(`${fInst} [data-foto-erro]`, (e) => getComputedStyle(e).display !== 'none' && e.textContent.trim() !== '').catch(() => false);
ok(inst.url() === urlAntes && erroVisivel, 'instalador: sem foto nao envia e mostra aviso');

// Com foto grande: comprime no navegador
const input = await inst.$(`${fInst} input[type=file][data-foto-compacta]`);
await input.uploadFile(env.FOTO);
await inst.waitForSelector(`${fInst} input[name="fotos[]"]`, { timeout: 15000 });
const tam = await inst.$eval(`${fInst} input[name="fotos[]"]`, (i) => i.value.length);
ok(tam > 1000 && tam < 900000, `instalador: foto comprimida no celular (${Math.round(tam / 1024)} KB em base64)`);
ok((await inst.$$(`${fInst} [data-foto-preview] img`)).length === 1, 'instalador: miniatura da foto');

await inst.$eval(`${fInst} input[name="modelo"]`, (i) => { i.value = 'DS-2CD1043G2-I'; });
await inst.$eval(`${fInst} input[name="serie"]`, (i) => { i.value = 'AB:CD:EF:00:11:22'; });
await inst.select(`${fInst} select[name="repassar_para"]`, String(env.CONF_ID));
await inst.$eval(`${fInst} textarea[name="instrucao"]`, (t) => { t.value = 'Canal 5, lente 2.8 mm'; });
await enviar(inst, fInst);
let t = await texto(inst);
ok(/Instalado/.test(t) && /repassado para/i.test(t), 'instalador: "Instalado · repassado para ..."');
await inst.screenshot({ path: (env.FOTOS || '.') + '/repasse-instalador.png', fullPage: true }).catch(() => null);

// ---------- 2. Configurador ----------
const conf = await sessao(env.EMAIL_CONF, env.SENHA_TEC, 390);
t = await texto(conf);
ok(/Minha fila/i.test(t), 'configurador: cartao "Minha fila" no inicio');
await conf.goto(BASE + 'index.php/tecnico/fila', { waitUntil: 'networkidle2' });
t = await texto(conf);
ok(t.includes('Canal 5, lente 2.8 mm') && t.includes('AB:CD:EF:00:11:22'), 'configurador: fila com instrucao e serie do instalador');
ok(await fotoCarregou(conf, 'img[src*="fotoPonto"]'), 'configurador: foto do instalador aparece');
await conf.screenshot({ path: (env.FOTOS || '.') + '/repasse-fila.png', fullPage: true }).catch(() => null);

const fConf = await conf.evaluateHandle((pid) => {
    const f = [...document.querySelectorAll('form')].find((x) => x.querySelector(`input[name="ponto_id"][value="${pid}"]`) && x.querySelector('input[name="ip"]'));
    return f || null;
}, P1);
ok(!!(await fConf.evaluate((f) => !!f)), 'configurador: form "Configurei" com IP/canal');
await fConf.evaluate((f) => {
    f.querySelector('input[name="ip"]').value = '192.168.1.105';
    const c = f.querySelector('input[name="canal"]'); if (c) c.value = '5';
});
await Promise.all([conf.waitForNavigation({ waitUntil: 'networkidle2' }), fConf.evaluate((f) => f.requestSubmit())]);
t = await texto(conf);
ok(/Configurado/.test(t), 'configurador: "Configurei" gravado');
ok(/Testei/.test(t), 'configurador: ponto continua com ele, aguardando teste');

// ---------- 3. Gestor repassa outro ponto para a equipe ----------
const ges = await sessao(env.EMAIL, env.SENHA, 1366);
await ges.goto(BASE + `index.php/obras/visualizar/${OBRA}#pontos`, { waitUntil: 'networkidle2' });
const marcou = await ges.$eval(`input[name="ponto_ids[]"][value="${P2}"]`, (c) => { c.checked = true; return true; }).catch(() => false);
ok(marcou, 'gestor: caixa de selecao do ponto');
await ges.select('#form-repasse-lote select[name="destinatario"]', '');
await enviar(ges, '#form-repasse-lote');
t = await texto(ges);
ok(/repassado/i.test(t), 'gestor: repasse em lote para a equipe');
ok(await fotoCarregou(ges, '#tab-pontos img[src*="fotoPonto"]'), 'gestor: miniatura da foto na tabela');
await ges.screenshot({ path: (env.FOTOS || '.') + '/repasse-gestao.png', fullPage: true }).catch(() => null);

// ---------- 4. Configurador assume o da equipe ----------
await conf.goto(BASE + 'index.php/tecnico/fila', { waitUntil: 'networkidle2' });
const fAssumir = await conf.evaluateHandle((pid) => [...document.querySelectorAll('form[action*="assumir_ponto"]')].find((x) => x.querySelector(`input[name="ponto_id"][value="${pid}"]`)) || null, P2);
ok(!!(await fAssumir.evaluate((f) => !!f)), 'configurador: ponto aparece em "Da equipe"');
await Promise.all([conf.waitForNavigation({ waitUntil: 'networkidle2' }), fAssumir.evaluate((f) => f.requestSubmit())]);
const aindaNaEquipe = await conf.evaluate((pid) => !![...document.querySelectorAll('form[action*="assumir_ponto"]')].find((x) => x.querySelector(`input[name="ponto_id"][value="${pid}"]`)), P2);
ok(!aindaNaEquipe, 'configurador: assumiu o ponto');

// ---------- 5. Painel, relatorio e as-built ----------
await ges.goto(BASE + 'index.php/obras/painel', { waitUntil: 'networkidle2' });
t = await texto(ges);
ok(t.includes('Fila de repasses') && /Configurador|Teste/.test(t), 'painel: fila de repasses por tecnico');
await ges.goto(BASE + 'index.php/relatorioprojetos?tipo=diario', { waitUntil: 'networkidle2' });
t = await texto(ges);
ok(/Repassado para/.test(t), 'relatorio: evento de repasse');
ok(/Tempo médio até configurar/i.test(t), 'relatorio: tempo medio ate configurar');
await ges.goto(BASE + `index.php/obras/asbuilt/${OBRA}?fotos=1`, { waitUntil: 'networkidle2' });
ok(await fotoCarregou(ges, 'img[src*="fotoPonto"]'), 'as-built com fotos');

ok(problemas.length === 0, 'sem erros de JS/CSP' + (problemas.length ? ': ' + problemas.join(' | ') : ''));
await navegador.close();
console.log(falhas ? `\n${falhas} falha(s)` : '\nTudo certo');
process.exit(falhas ? 1 : 0);
