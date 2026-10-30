/**
 * Acompanhamento de projetos (ADR-012) no Chrome headless.
 *
 * Variaveis: BASE_URL, EMAIL, SENHA (gestor com vObras), EMAIL_TEC, SENHA_TEC (tecnico),
 * CHROME, FOTOS (pasta para screenshots; opcional), OBRA_ID e IGNORAR_CSP.
 * Testa: painel com saude dos projetos, relatorio semanal/mensal com filtros e impressao,
 * e o "Meu relatorio" do tecnico no celular. Falha em erro de JS ou de CSP.
 */
import puppeteer from 'puppeteer-core';

const BASE = (process.env.BASE_URL || 'http://localhost:8099/').replace(/\/?$/, '/');
const EMAIL = process.env.EMAIL || 'admin@mapos.test';
const SENHA = process.env.SENHA || 'admin123';
const EMAIL_TEC = process.env.EMAIL_TEC || '';
const SENHA_TEC = process.env.SENHA_TEC || '';
const CHROME = process.env.CHROME || '/usr/bin/google-chrome';
const FOTOS = process.env.FOTOS || '';
const OBRA_ID = process.env.OBRA_ID || '';
const IGNORAR = process.env.IGNORAR_CSP || ''; // trecho de URL de violacao conhecida do ambiente (ex.: logo de outra instalacao) // projeto com pontos (ADR-013); o tecnico precisa ter acesso

let falhas = 0;
const ok = (cond, msg) => {
    console.log((cond ? 'OK    ' : 'FALHA ') + msg);
    if (!cond) falhas++;
};

const navegador = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
const problemas = [];

async function novaPagina(largura) {
    const ctx = await navegador.createBrowserContext();
    const page = await ctx.newPage();
    await page.setViewport({ width: largura, height: 900 });
    page.on('pageerror', (e) => problemas.push('JS: ' + e.message.slice(0, 160)));
    page.on('console', (m) => {
        const t = m.text();
        if (/Content Security Policy/i.test(t) && !(IGNORAR && t.includes(IGNORAR))) problemas.push('CSP: ' + t.slice(0, 160));
    });
    return page;
}

async function entrar(page, email, senha) {
    await page.goto(BASE + 'index.php/login', { waitUntil: 'networkidle2' });
    await page.type('#email', email);
    await page.type('#senha', senha);
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => null), page.click('#btn-acessar')]);
}

const foto = async (page, nome) => FOTOS && page.screenshot({ path: `${FOTOS}/${nome}.png`, fullPage: true });

// ---------- Gestao ----------
const page = await novaPagina(1366);
await entrar(page, EMAIL, SENHA);

let resp = await page.goto(BASE + 'index.php/obras/painel', { waitUntil: 'networkidle2' });
ok(resp.status() === 200, 'painel abre (' + resp.status() + ')');
let texto = await page.evaluate(() => document.body.innerText);
ok(texto.includes('Situação de cada projeto'), 'painel: bloco "Situação de cada projeto"');
ok(texto.includes('Pontos por tipo'), 'painel: pontos por tipo');
ok(texto.includes('Atividade dos últimos 7 dias'), 'painel: atividade dos ultimos 7 dias');
ok(texto.includes('Técnicos hoje'), 'painel: tecnicos hoje');
ok(!!(await page.$('a[href*="relatorioprojetos"]')), 'painel: atalho para o relatorio');
await foto(page, 'painel');

resp = await page.goto(BASE + 'index.php/relatorioprojetos', { waitUntil: 'networkidle2' });
ok(resp.status() === 200, 'relatorio abre (' + resp.status() + ')');
texto = await page.evaluate(() => document.body.innerText);
ok(/Semana de \d{2}\/\d{2} a \d{2}\/\d{2}\/\d{4}/.test(texto), 'relatorio: periodo semanal por padrao');
ok(!!(await page.$('[data-acao="imprimir"]')), 'relatorio: botao imprimir');
ok(!!(await page.$('.projeto-nav a.ativo[href*="relatorioprojetos"]')), 'relatorio: aba ativa na navegacao de projetos');
await foto(page, 'relatorio-semanal');

// Seta "anterior" troca o periodo
const antes = texto.match(/Semana de [^\n]+/)?.[0];
const seta = await page.$('a[href*="relatorioprojetos"][title="Período anterior"]');
if (seta) {
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), seta.click()]);
    const depois = (await page.evaluate(() => document.body.innerText)).match(/Semana de [^\n]+/)?.[0];
    ok(antes && depois && antes !== depois, `relatorio: navegar entre semanas (${depois})`);
} else {
    ok(false, 'relatorio: link de navegacao de periodo');
}

resp = await page.goto(BASE + 'index.php/relatorioprojetos?tipo=mensal', { waitUntil: 'networkidle2' });
texto = await page.evaluate(() => document.body.innerText);
ok(/(Janeiro|Fevereiro|Março|Abril|Maio|Junho|Julho|Agosto|Setembro|Outubro|Novembro|Dezembro)\/\d{4}/.test(texto), 'relatorio: periodo mensal');

// Impressao: filtros somem, cabecalho de impressao aparece
await page.emulateMediaType('print');
const imp = await page.evaluate(() => {
    const visivel = (el) => !!el && getComputedStyle(el).display !== 'none' && el.offsetParent !== null;
    return {
        filtros: visivel(document.querySelector('form[method="get"], form[method="GET"]')),
        botao: visivel(document.querySelector('[data-acao="imprimir"]')),
    };
});
ok(!imp.filtros && !imp.botao, 'impressao: filtros e botao ocultos');
const pdf = await page.pdf({ format: 'A4' });
ok(pdf.length > 5000, `impressao: PDF gerado (${pdf.length} bytes)`);
await page.emulateMediaType('screen');

// ---------- Pontos do projeto (ADR-013) ----------
if (OBRA_ID) {
    resp = await page.goto(BASE + 'index.php/obras/visualizar/' + OBRA_ID + '#pontos', { waitUntil: 'networkidle2' });
    const aba = await page.evaluate(() => {
        const p = document.querySelector('#tab-pontos');
        return { visivel: !!p && getComputedStyle(p).display !== 'none', linhas: document.querySelectorAll('#tab-pontos .pt-status-form').length };
    });
    ok(aba.visivel, 'pontos: #pontos abre a aba Pontos');
    ok(aba.linhas > 0, `pontos: tabela com pontos (${aba.linhas})`);
    texto = await page.evaluate(() => document.body.textContent);
    ok(texto.includes('Registros do dia') && !texto.includes('Diário de Obra'), 'pontos: vocabulario "Registros do dia"');
    await foto(page, 'pontos-gestao');

    // Gestor muda o status do primeiro ponto pela tabela
    const antesSt = await page.$eval('#tab-pontos .pt-status-form select[name="status"]', (s) => s.value);
    const novo = antesSt === 'testado' ? 'configurado' : 'testado';
    await page.$eval('#tab-pontos .pt-status-form select[name="status"]', (s, v) => { s.value = v; }, novo);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle2' }),
        page.$eval('#tab-pontos .pt-status-form', (f) => (f.requestSubmit ? f.requestSubmit() : f.submit())),
    ]);
    const depoisSt = await page.$eval('#tab-pontos .pt-status-form select[name="status"]', (s) => s.value).catch(() => null);
    ok(depoisSt === novo, `pontos: gestor muda status (${antesSt} -> ${depoisSt})`);

    resp = await page.goto(BASE + 'index.php/obras/asbuilt/' + OBRA_ID, { waitUntil: 'networkidle2' });
    texto = await page.evaluate(() => document.body.innerText);
    ok(resp.status() === 200 && texto.includes('As-built'), 'as-built abre');
    await page.emulateMediaType('print');
    const asb = await page.pdf({ format: 'A4' });
    ok(asb.length > 3000, `as-built: PDF (${asb.length} bytes)`);
    await page.emulateMediaType('screen');
    await foto(page, 'asbuilt');
}

// ---------- Tecnico (celular) ----------
if (EMAIL_TEC) {
    const tec = await novaPagina(390);
    await entrar(tec, EMAIL_TEC, SENHA_TEC);
    resp = await tec.goto(BASE + 'index.php/tecnico/projetos', { waitUntil: 'networkidle2' });
    ok(!!(await tec.$('a[href*="tecnico/meu_relatorio"]')), 'tecnico: atalho "Meu relatório" em projetos');
    resp = await tec.goto(BASE + 'index.php/tecnico/meu_relatorio?tipo=semanal', { waitUntil: 'networkidle2' });
    ok(resp.status() === 200, 'tecnico: meu relatorio abre (' + resp.status() + ')');
    texto = await tec.evaluate(() => document.body.innerText);
    ok(texto.includes('Meu relatório'), 'tecnico: titulo');
    const larg = await tec.evaluate(() => document.documentElement.scrollWidth);
    ok(larg <= 400, `tecnico: sem rolagem horizontal no celular (${larg}px)`);
    await foto(tec, 'tecnico-meu-relatorio');

    if (OBRA_ID) {
        await tec.goto(BASE + 'index.php/tecnico/projeto/' + OBRA_ID + '#pontos', { waitUntil: 'networkidle2' });
        await foto(tec, 'tecnico-pontos');
        const botao = await tec.$('form[action*="tecnico/ponto_status"] button.ponto-acao');
        ok(!!botao, 'tecnico: botao de avancar ponto');
        if (botao) {
            const rotulo = (await botao.evaluate((b) => b.textContent)).trim();
            await Promise.all([tec.waitForNavigation({ waitUntil: 'networkidle2' }), botao.click()]);
            texto = await tec.evaluate(() => document.body.innerText);
            ok(/[A-Z]+-\d+: /.test(texto), `tecnico: avancou ponto com um toque ("${rotulo}")`);
        }
    }
    resp = await tec.goto(BASE + 'index.php/relatorioprojetos', { waitUntil: 'networkidle2' });
    ok(!tec.url().includes('relatorioprojetos'), 'tecnico: sem acesso ao relatorio de gestao');
}

ok(problemas.length === 0, 'sem erros de JS/CSP' + (problemas.length ? ': ' + problemas.join(' | ') : ''));
await navegador.close();
console.log(falhas ? `\n${falhas} falha(s)` : '\nTudo certo');
process.exit(falhas ? 1 : 0);
