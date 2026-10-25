/**
 * Testes de tela do Map-OS no Chrome headless (ADR-006).
 *
 * Variaveis: BASE_URL (ex.: http://localhost:8099/), EMAIL, SENHA, CHROME (executavel)
 *   cd tests/navegador && npm install && BASE_URL=... node telas.mjs
 *
 * Falha em: erro de JavaScript, violacao da CSP (exceto imagem de outro dominio, que e
 * dado cadastrado), tela que nao abre e acoes data-* ou PDV que nao funcionam.
 * Espera a instalacao limpa do smoke (tests/smoke/instalar.sh) com o produto 1 cadastrado.
 */
import puppeteer from 'puppeteer-core';

const BASE = (process.env.BASE_URL || 'http://localhost:8099/').replace(/\/?$/, '/');
const EMAIL = process.env.EMAIL || 'admin@mapos.test';
const SENHA = process.env.SENHA || 'admin123';
const CHROME = process.env.CHROME || '/usr/bin/google-chrome';

const TELAS = [
    'mapos', 'clientes', 'clientes/adicionar', 'os', 'os/adicionar', 'vendas', 'vendas/adicionar',
    'produtos', 'produtos/adicionar', 'produtos/movimentacoes', 'inventarios', 'servicos', 'garantias',
    'arquivos', 'financeiro/lancamentos', 'cobrancas/cobrancas', 'contratos', 'obras', 'usuarios',
    'permissoes', 'mapos/emitente', 'mapos/configurar', 'mapos/minhaConta', 'mapos/backups',
    'mapos/emailsLog', 'relatorios/clientes', 'relatorios/os', 'agenda', 'sla', 'caixa', 'pdv',
    'relatorios/nps', 'painelgerencial', 'relatoriocobranca', 'margem', 'comprasugerida', 'conciliacao', 'notificacoes', 'automacao', 'emailtemplates', 'whatsapptemplates', 'nfe/gerenciar', 'nfe/configuracoes',
    'os/atribuir', 'tecnico',
];

let falhas = 0;
const ok = (cond, msg) => {
    console.log((cond ? 'OK    ' : 'FALHA ') + msg);
    if (!cond) falhas++;
};
const espera = (ms) => new Promise((r) => setTimeout(r, ms));

const navegador = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
const pagina = await navegador.newPage();
await pagina.setViewport({ width: 1366, height: 900 });

let problemas = [];
pagina.on('pageerror', (e) => problemas.push('JS: ' + e.message.slice(0, 160)));
pagina.on('console', (m) => {
    const t = m.text();
    if (!/Content Security Policy/i.test(t)) return;
    // Imagem de outro dominio (ex.: logo cadastrado com URL externa) e dado, nao codigo
    if (/Loading the image/i.test(t)) return;
    problemas.push('CSP: ' + t.replace(/\s+/g, ' ').slice(0, 200));
});
const dialogos = [];
let respostaDialogo = null;
pagina.on('dialog', async (d) => {
    dialogos.push(d.message());
    if (respostaDialogo === null) await d.dismiss(); else await d.accept(respostaDialogo);
});
const ir = (rota) => pagina.goto(BASE + 'index.php/' + rota, { waitUntil: 'networkidle2', timeout: 30000 });

// --- Login pela tela -----------------------------------------------------
await pagina.goto(BASE + 'index.php/login', { waitUntil: 'networkidle2' });
const cabecalhoCsp = await pagina.evaluate(async () => (await fetch(location.href)).headers.get('content-security-policy'));
ok(!!cabecalhoCsp, 'CSP em modo bloqueio no login');
await pagina.type('#email', EMAIL);
await pagina.type('#senha', SENHA);
await Promise.all([pagina.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => null), pagina.click('#btn-acessar')]);
ok(pagina.url().includes('index.php/mapos'), 'login pela tela (' + pagina.url().split('index.php/')[1] + ')');
ok(problemas.length === 0, 'login sem erro de JS/CSP ' + JSON.stringify(problemas));

// --- Telas com a CSP bloqueando ------------------------------------------
for (const tela of TELAS) {
    problemas = [];
    let status = 0;
    try {
        const resp = await ir(tela);
        status = resp ? resp.status() : 0;
        await espera(300);
    } catch (e) {
        problemas.push('NAV: ' + e.message.slice(0, 100));
    }
    const unicos = [...new Set(problemas)];
    ok(status === 200 && unicos.length === 0, `${tela} [${status}]` + (unicos.length ? ' ' + JSON.stringify(unicos.slice(0, 3)) : ''));
}

// --- Acoes data-* ----------------------------------------------------------
problemas = [];
await ir('notificacoes');
const temConfirmar = await pagina.$('[data-confirmar]');
if (temConfirmar) {
    const url = pagina.url();
    const antes = dialogos.length;
    await temConfirmar.click();
    await espera(600);
    ok(dialogos.length === antes + 1 && pagina.url() === url, 'data-confirmar recusado pergunta e nao segue');
} else {
    console.log('PULADO data-confirmar (nenhum gatilho cadastrado)');
}

await ir('financeiro/lancamentos');
ok(await pagina.evaluate(() => {
    let chamou = false;
    const original = window.mostrarValores;
    window.mostrarValores = function () { chamou = true; return original && original.apply(this, arguments); };
    const b = document.querySelector('[data-chamar="mostrarValores"]');
    if (!b) return false;
    b.click();
    return chamou;
}), 'data-chamar chama a funcao da pagina');

// --- PDV: abrir caixa (se preciso), vender com troco, fechar ---------------
await ir('pdv');
if (await pagina.$('input[name="valor_abertura"]')) {
    await pagina.$eval('input[name="valor_abertura"]', (e) => { e.value = '50,00'; });
    await Promise.all([pagina.waitForNavigation({ waitUntil: 'networkidle2' }), pagina.click('form[action$="pdv/abrirCaixa"] button[type="submit"]')]);
}
ok(!!(await pagina.$('#pdvBusca')), 'PDV aberto');
problemas = [];
await pagina.type('#pdvBusca', 'Produto smoke');
await espera(900);
await pagina.keyboard.press('Enter');
await espera(500);
const total = await pagina.$eval('#pdvTotal', (e) => e.textContent.trim());
ok(/25,00/.test(total), 'produto no carrinho pela busca (' + total + ')');
respostaDialogo = '30,00';
await pagina.click('button[data-forma="dinheiro"]');
respostaDialogo = null;
await espera(300);
ok((await pagina.$eval('#pdvTroco', (e) => e.textContent)).includes('5,00'), 'troco calculado (R$ 5,00)');
await pagina.keyboard.press('F4');
await espera(1500);
const final = await pagina.evaluate(() => { const e = document.getElementById('pdvFinal'); return e && e.style.display === 'flex' ? e.innerText : ''; });
ok(/registrada/.test(final), 'venda finalizada pelo F4 (' + final.replace(/\s+/g, ' ').slice(0, 40) + ')');
ok(problemas.length === 0, 'PDV sem erro de JS/CSP ' + JSON.stringify(problemas));

await navegador.close();
console.log(falhas ? `\n${falhas} verificacao(oes) falharam` : '\nTodas as telas OK');
process.exit(falhas ? 1 : 0);
