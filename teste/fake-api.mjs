/**
 * Fake da API v1 do MAPOS para testes.
 *
 * Replica o contrato observado em application/controllers/api/v1:
 *  - envelope { status, message, result }
 *  - login devolve { access_token, permissions: [ { ...flags } ] }
 *  - conta devolve o token decodificado + usuario + level
 *  - datas de OS entram em dd/mm/aaaa e saem em YYYY-MM-DD
 *  - PUT /os/{id} exige dataInicial, dataFinal, status, clientes_id e usuarios_id
 *  - status=false com HTTP 200 em listas vazias
 */

import { createServer } from 'node:http';
import { readFileSync, statSync } from 'node:fs';
import { extname, join } from 'node:path';

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.mjs': 'text/javascript; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.webmanifest': 'application/manifest+json; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.ico': 'image/x-icon',
};

const TOKEN = 'fake.jwt.token';
const PERMISSIONS = {
  vOs: '1',
  aOs: '1',
  eOs: '1',
  dOs: '1',
  vCliente: '1',
  aCliente: '1',
  eCliente: '1',
  vProduto: '1',
  vServico: '1',
  cUsuario: '1',
};

/** Grupo só do técnico: sem vOs (é o que faz Login::concluirLogin mandar para /tecnico). */
const PERMISSIONS_TECNICO = {
  vTecnicoDashboard: '1',
  vTecnicoOS: '1',
  eTecnicoCheckin: '1',
  eTecnicoCheckout: '1',
  eTecnicoFotos: '1',
  eTecnicoNaoRealizado: '1',
  aTecnicoAtividade: '1',
};

/** Grupo do colaborador: RH self-service + ponto, sem área administrativa. */
const PERMISSIONS_COLABORADOR = {
  vAreaColaborador: '1',
  baterPonto: '1',
};

const PERMISSOES_POR_AREA = {
  admin: PERMISSIONS,
  tecnico: PERMISSIONS_TECNICO,
  colaborador: PERMISSIONS_COLABORADOR,
};

function isoDate(value) {
  // "dd/mm/aaaa" -> "aaaa-mm-dd" (mantém o comportamento do controller)
  if (typeof value !== 'string') return null;
  const [d, m, y] = value.split('/');
  if (!d || !m || !y) return null;
  return `${y}-${m}-${d}`;
}

function brDate(iso) {
  const [y, m, d] = String(iso).slice(0, 10).split('-');
  return `${d}/${m}/${y}`;
}

export function createFakeApi({ authedPermissions, area = 'admin', twoFactorUser = false, dist, noRewrite = false } = {}) {
  const permissoes = authedPermissions ?? PERMISSOES_POR_AREA[area] ?? PERMISSIONS;
  const state = {
    area,
    permissoes,
    /** Quando definido, /conta devolve estas permissões (perfil alterado depois do login). */
    permissoesAtuais: null,
    /** Liga o aviso do PHP antes do JSON em todas as respostas. */
    avisoPhp: false,
    loggedIn: false,
    twoFactorPending: false,
    nextOsId: 1002,
    nextClienteId: 21,
    nextAnotacaoId: 900,
    checkin: null,
    observacoes: [],
    motivosNaoRealizado: [{ id: 1, nome: 'Cliente ausente' }, { id: 2, nome: 'Local fechado' }],
    naoRealizadas: [],
    fila: [],
    batidas: [],
    ocorrencias: [],
    ausencias: [],
    fotoSeq: 0,
    ultimoCheckout: null,
    os: [
      {
        idOs: 1000,
        clientes_id: 1,
        usuarios_id: 1,
        nomeCliente: 'Ana Beatriz Souza',
        celular_cliente: '(11) 98888-7777',
        nome: 'Carlos Técnico',
        status: 'Em Andamento',
        dataInicial: '2026-10-28',
        dataFinal: '2026-11-05',
        descricaoProduto: 'Notebook Dell Inspiron 15',
        defeito: 'Não liga após queda de energia',
        observacoes: 'Cliente pediu urgência',
        laudoTecnico: '',
        faturado: '0',
        garantia: '3 meses',
        refGarantia: 'G-1',
        totalProdutos: '150.00',
        totalServicos: '200.00',
        calcTotal: 350,
        produtos: [{ produtos_id: 3, descricao: 'Fonte 65W', preco: '150.00', quantidade: '1' }],
        servicos: [{ servicos_id: 7, nome: 'Diagnóstico e reparo', preco: '200.00', quantidade: '1' }],
        anexos: [],
        anotacoes: [{ idAnotacoes: 1, os_id: 1000, anotacao: '[Carlos] Testes iniciais ok', data_hora: '2026-11-01 09:15:00' }],
        textoWhatsApp: 'OS 1000 - Ana Beatriz Souza',
      },
      {
        idOs: 1001,
        clientes_id: 2,
        usuarios_id: 1,
        nomeCliente: 'Mercado Bom Preço',
        celular_cliente: '(11) 97777-6666',
        nome: 'Carlos Técnico',
        status: 'Aberto',
        dataInicial: '2026-11-02',
        dataFinal: '2026-11-03',
        descricaoProduto: 'Impressora térmica',
        defeito: 'Não imprime cupom',
        observacoes: '',
        laudoTecnico: '',
        faturado: '0',
        totalProdutos: '0',
        totalServicos: '80.00',
        calcTotal: 80,
        produtos: [],
        servicos: [],
        anexos: [],
        anotacoes: [],
        textoWhatsApp: 'OS 1001',
      },
    ],
    clientes: [
      {
        idClientes: 1,
        nomeCliente: 'Ana Beatriz Souza',
        celular: '(11) 98888-7777',
        telefone: '(11) 3333-4444',
        documento: '123.456.789-00',
        email: 'ana@exemplo.com',
        rua: 'Rua das Flores',
        numero: '100',
        bairro: 'Centro',
        cidade: 'São Paulo',
        estado: 'SP',
        cep: '01000-000',
        dataCadastro: '2026-01-10',
        fornecedor: '0',
      },
      {
        idClientes: 2,
        nomeCliente: 'Mercado Bom Preço',
        celular: '(11) 97777-6666',
        telefone: '',
        documento: '12.345.678/0001-90',
        email: 'contato@bompreco.com',
        cidade: 'São Paulo',
        estado: 'SP',
        dataCadastro: '2026-02-20',
        fornecedor: '0',
      },
    ],
  };

  const server = createServer(async (req, res) => {
    const url = new URL(req.url ?? '/', 'http://localhost');
    const path = url.pathname;
    const method = (req.method ?? 'GET').toUpperCase();

    // CORS aberto (o app é servido de outra origem)
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    if (method === 'OPTIONS') {
      res.writeHead(204).end();
      return;
    }

    // Instalação sem mod_rewrite: só o caminho com index.php chega ao PHP.
    // (É o comportamento real de jj-ferreiras.com.br/mapos.)
    if (noRewrite) {
      if (path.startsWith('/index.php/api/')) {
        await handleApi(req, res, path.slice('/index.php'.length), method, url);
        return;
      }
      if (path.startsWith('/api/')) {
        res.writeHead(404, { 'Content-Type': 'text/html; charset=utf-8' }).end('<!doctype html><h1>404</h1>');
        return;
      }
    }

    if (path.startsWith('/api/')) {
      await handleApi(req, res, path, method, url);
      return;
    }
    // Com rewrite, o CodeIgniter também atende pelo index.php (o app tenta esse
    // caminho depois de um 404, e precisa receber o mesmo 404 em JSON).
    if (path.startsWith('/index.php/api/')) {
      await handleApi(req, res, path.slice('/index.php'.length), method, url);
      return;
    }

    // Simula o app publicado numa subpasta da instalação (ex.: /mobile-app/).
    if (path === '/mobile-app' || path.startsWith('/mobile-app/')) {
      serveStatic(res, path.slice('/mobile-app'.length) || '/', dist ?? process.env.MAPOS_DIST);
      return;
    }

    serveStatic(res, path, process.env.MAPOS_DIST);
  });

  function json(res, code, payload) {
    // Servidor com display_errors ligado: aviso do PHP impresso antes do JSON.
    const aviso = state.avisoPhp
      ? '<br />\n<b>Deprecated</b>:  Creation of dynamic property Os_model::$x is deprecated in <b>/home/app/Os_model.php</b> on line <b>12</b><br />\n'
      : '';
    const body = aviso + JSON.stringify(payload);
    res.writeHead(code, { 'Content-Type': 'application/json; charset=utf-8', 'Content-Length': Buffer.byteLength(body) });
    res.end(body);
  }

  function ok(res, result, message = 'OK', code = 200) {
    json(res, code, { status: true, message, result });
  }

  function fail(res, message, code = 200) {
    json(res, code, { status: false, message, result: null });
  }

  function auth(req) {
    const header = req.headers.authorization ?? '';
    return header === `Bearer ${TOKEN}`;
  }

  function requireAuth(req, res) {
    if (!auth(req)) {
      fail(res, 'Faça login para acessar a API.', 401);
      return false;
    }
    return true;
  }

  function readBody(req) {
    return new Promise((resolve) => {
      let raw = '';
      req.on('data', (chunk) => {
        raw += chunk;
      });
      req.on('end', () => {
        if (!raw) return resolve({});
        try {
          resolve(JSON.parse(raw));
        } catch {
          resolve({});
        }
      });
    });
  }

  async function handleApi(req, res, path, method, url) {
    // ---------- autenticação ----------
    if (path === '/api/v1/login' && method === 'POST') {
      const body = await readBody(req);
      if (!body.email || !body.password) {
        return json(res, 401, { status: false, message: 'Preencha e-mail e senha.', result: null });
      }
      if (body.email !== 'tecnico@empresa.com' || body.password !== 'senha123') {
        return json(res, 401, { status: false, message: 'Os dados de acesso estão incorretos!', result: null });
      }
      if (twoFactorUser && !body.codigo) {
        return json(res, 401, {
          status: false,
          message: 'Informe o código da verificação em duas etapas no campo "codigo".',
          dois_fatores: true,
          result: null,
        });
      }
      if (twoFactorUser && body.codigo !== '123456') {
        return json(res, 401, { status: false, message: 'Código inválido.', dois_fatores: true, result: null });
      }
      state.loggedIn = true;
      return ok(res, { access_token: TOKEN, permissions: [permissoes] }, 'Login realizado com sucesso!');
    }

    if (path === '/api/v1/reGenToken' && method === 'GET') {
      if (!requireAuth(req, res)) return;
      return ok(res, { access_token: TOKEN, permissions: [permissoes] });
    }

    if (path === '/api/v1/conta' && method === 'GET') {
      if (!requireAuth(req, res)) return;
      return ok(res, {
        status: true,
        data: { uid: 1, email: 'tecnico@empresa.com', permissao: 1 },
        usuario: {
          idUsuarios: 1,
          nome: 'Carlos Técnico',
          email: 'tecnico@empresa.com',
          url_image_user: null,
          permissoes_id: 1,
        },
        level: 1,
        ...(state.permissoesAtuais ? { permissions: [state.permissoesAtuais] } : {}),
      });
    }

    // Marca pública (sem token): nome e logo para a tela de login.
    if (path === '/api/v1/marca' && method === 'GET') {
      return ok(res, { appName: 'Assistência Tech', nome: 'Assistência Tech LTDA', url_logo: null });
    }

    if (path === '/api/v1/emitente' && method === 'GET') {
      if (!requireAuth(req, res)) return;
      return ok(res, {
        appName: 'Assistência Tech',
        emitente: { nome: 'Assistência Tech LTDA', telefone: '(11) 4002-8922', email: 'contato@tech.com', cidade: 'São Paulo', estado: 'SP' },
      });
    }

    // ---------- dashboard ----------
    if (path === '/api/v1' && method === 'GET') {
      if (!requireAuth(req, res)) return;
      return ok(res, {
        countOs: String(state.os.length),
        clientes: String(state.clientes.length),
        produtos: '12',
        servicos: '8',
        garantias: '2',
        vendas: '5',
        osAbertas: state.os.filter((o) => o.status === 'Aberto'),
        osAndamento: state.os.filter((o) => o.status === 'Em Andamento'),
        estoqueBaixo: [{ idProdutos: 9, descricao: 'Pasta térmica', estoque: '0', estoqueMinimo: '2' }],
      });
    }

    // ---------- OS ----------
    const osDetail = /^\/api\/v1\/os\/(\d+)$/.exec(path);
    const osAnotacoes = /^\/api\/v1\/os\/(\d+)\/anotacoes$/.exec(path);

    if (path === '/api/v1/os' && method === 'GET') {
      if (!requireAuth(req, res)) return;
      const search = (url.searchParams.get('search') ?? '').toLowerCase();
      const status = url.searchParams.get('status');
      const from = url.searchParams.get('from');
      const to = url.searchParams.get('to');
      let list = state.os;

      if (search) {
        list = list.filter(
          (o) => o.nomeCliente.toLowerCase().includes(search) || String(o.idOs).includes(search),
        );
      }
      if (status) list = list.filter((o) => o.status === status);
      if (from && to) {
        const fromIso = isoDate(from);
        const toIso = isoDate(to);
        list = list.filter((o) => o.dataFinal >= fromIso && o.dataFinal <= toIso);
      }
      return ok(res, list, 'Listando OSs');
    }

    if (path === '/api/v1/os' && method === 'POST') {
      if (!requireAuth(req, res)) return;
      const body = await readBody(req);
      if (!body.clientes_id || !body.usuarios_id || !body.status) {
        return json(res, 400, { status: false, message: 'Preencha todos os campos obrigatórios', result: null });
      }
      const dataInicial = isoDate(body.dataInicial);
      const dataFinal = isoDate(body.dataFinal) ?? dataInicial;
      if (!dataInicial) {
        return json(res, 400, { status: false, message: 'Data inicial inválida', result: null });
      }
      const createdAt = {
        idOs: state.nextOsId++,
        clientes_id: Number(body.clientes_id),
        usuarios_id: Number(body.usuarios_id),
        nomeCliente: state.clientes.find((c) => c.idClientes === Number(body.clientes_id))?.nomeCliente ?? 'Cliente',
        nome: 'Carlos Técnico',
        status: body.status,
        dataInicial,
        dataFinal,
        descricaoProduto: body.descricaoProduto ?? '',
        defeito: body.defeito ?? '',
        observacoes: body.observacoes ?? '',
        laudoTecnico: body.laudoTecnico ?? '',
        faturado: '0',
        totalProdutos: '0',
        totalServicos: '0',
        calcTotal: 0,
        produtos: [],
        servicos: [],
        anexos: [],
        anotacoes: [],
      };
      state.os.push(createdAt);
      return ok(res, createdAt, 'OS adicionada com sucesso!', 201);
    }

    if (osDetail && method === 'GET') {
      if (!requireAuth(req, res)) return;
      const id = Number(osDetail[1]);
      const found = state.os.find((o) => o.idOs === id);
      if (!found) return fail(res, 'OS não encontrada.');
      return ok(res, found, 'Detalhes da OS');
    }

    if (osDetail && method === 'PUT') {
      if (!requireAuth(req, res)) return;
      const id = Number(osDetail[1]);
      const found = state.os.find((o) => o.idOs === id);
      if (!found) return json(res, 400, { status: false, message: 'Essa OS não existe', result: null });
      const body = await readBody(req);
      // o controller exige exatamente estes campos
      for (const required of ['dataInicial', 'dataFinal', 'status', 'clientes_id', 'usuarios_id']) {
        if (body[required] === undefined || body[required] === null || body[required] === '') {
          return json(res, 400, { status: false, message: 'Preencha os campos obrigatórios', result: null });
        }
      }
      const dataInicial = isoDate(body.dataInicial);
      const dataFinal = isoDate(body.dataFinal);
      if (!dataInicial || !dataFinal) {
        return json(res, 400, { status: false, message: 'Data inválida (use dd/mm/aaaa)', result: null });
      }
      // `faturado` é preservado: o controller real não desfatura na edição.
      Object.assign(found, {
        clientes_id: Number(body.clientes_id),
        usuarios_id: Number(body.usuarios_id),
        status: body.status,
        dataInicial,
        dataFinal,
        descricaoProduto: body.descricaoProduto ?? found.descricaoProduto,
        defeito: body.defeito ?? found.defeito,
        observacoes: body.observacoes ?? found.observacoes,
        laudoTecnico: body.laudoTecnico ?? found.laudoTecnico,
      });
      if (body.garantias_id !== undefined) found.garantias_id = body.garantias_id;
      state.lastUpdate = { id, body };
      // O controller devolve o detalhe completo (produtos, serviços, anotações).
      return ok(res, found, 'OS editada com sucesso!');
    }

    if (osDetail && method === 'DELETE') {
      if (!requireAuth(req, res)) return;
      const id = Number(osDetail[1]);
      state.os = state.os.filter((o) => o.idOs !== id);
      return ok(res, null, 'OS excluída com sucesso!');
    }

    if (osAnotacoes) {
      if (!requireAuth(req, res)) return;
      const id = Number(osAnotacoes[1]);
      const found = state.os.find((o) => o.idOs === id);
      if (!found) return fail(res, 'OS não encontrada.');

      if (method === 'POST') {
        const body = await readBody(req);
        if (!body.anotacao) {
          return json(res, 400, { status: false, message: 'O campo Anotação é obrigatório', result: null });
        }
        const nota = {
          idAnotacoes: state.nextAnotacaoId++,
          os_id: id,
          // o controller prefixa o nome do usuário
          anotacao: `[Carlos Técnico] ${body.anotacao}`,
          data_hora: new Date().toISOString().slice(0, 19).replace('T', ' '),
        };
        found.anotacoes.unshift(nota);
        return ok(res, { idAnotacoes: nota.idAnotacoes, anotacao: body.anotacao }, 'Anotação adicinada com sucesso!', 201);
      }
    }

    // ---------- clientes ----------
    const clienteDetail = /^\/api\/v1\/clientes\/(\d+)$/.exec(path);

    if (path === '/api/v1/clientes' && method === 'GET') {
      if (!requireAuth(req, res)) return;
      const search = (url.searchParams.get('search') ?? '').toLowerCase();
      let list = state.clientes;
      if (search) {
        list = list.filter((c) =>
          [c.nomeCliente, c.documento, c.celular, c.telefone, c.email].some((v) => (v ?? '').toLowerCase().includes(search)),
        );
      }
      // Lista vazia é resposta válida, como no controller real.
      return ok(res, list, 'Lista de Clientes');
    }

    if (clienteDetail && method === 'GET') {
      if (!requireAuth(req, res)) return;
      const id = Number(clienteDetail[1]);
      const found = state.clientes.find((c) => c.idClientes === id);
      if (!found) return fail(res, 'Nenhum cliente localizado com esse ID.');
      return ok(
        res,
        { ...found, ordensServicos: state.os.filter((o) => o.clientes_id === id) },
        'Detalhes do Cliente',
      );
    }

    if (path === '/api/v1/clientes' && method === 'POST') {
      if (!requireAuth(req, res)) return;
      const body = await readBody(req);
      if (!body.nomeCliente) {
        return json(res, 400, { status: false, message: 'O campo Nome é obrigatório', result: null });
      }
      const created = {
        idClientes: state.nextClienteId++,
        fornecedor: '0',
        dataCadastro: new Date().toISOString().slice(0, 10),
        ...body,
      };
      state.clientes.push(created);
      return ok(res, created, 'Cliente adicionado com sucesso!', 201);
    }

    // ---------- catálogos ----------
    if (path === '/api/v1/usuarios' && method === 'GET') {
      if (!requireAuth(req, res)) return;
      return ok(res, [{ idUsuarios: 1, nome: 'Carlos Técnico', email: 'tecnico@empresa.com' }], 'Lista de Usuários');
    }

    if (path === '/api/v1/produtos' && method === 'GET') {
      if (!requireAuth(req, res)) return;
      return ok(res, [{ idProdutos: 3, descricao: 'Fonte 65W', precoVenda: '150.00', estoque: '10' }], 'Listando Produtos');
    }

    if (path === '/api/v1/servicos' && method === 'GET') {
      if (!requireAuth(req, res)) return;
      return ok(res, [{ idServicos: 7, nome: 'Diagnóstico e reparo', preco: '200.00' }], 'Listando Serviços');
    }

    // ---------- Área do técnico ----------
    if (path.startsWith('/api/v1/tecnico')) {
      if (!requireAuth(req, res)) return;
      return handleTecnico(req, res, path, method, url);
    }

    // ---------- Área do colaborador ----------
    if (path.startsWith('/api/v1/colaborador') || path.startsWith('/api/v1/ponto')) {
      if (!requireAuth(req, res)) return;
      return handleColaborador(req, res, path, method, url);
    }

    return fail(res, 'Rota não encontrada na API.', 404);
  }

  // ==================================================================
  // Área do técnico
  // ==================================================================

  function osResumo(o) {
    return {
      idOs: o.idOs,
      status: o.status,
      nomeCliente: o.nomeCliente,
      telefone: o.telefone_cliente ?? null,
      celular: o.celular_cliente ?? null,
      endereco: 'Rua das Flores, 100 - Centro',
      rua: 'Rua das Flores',
      numero: '100',
      bairro: 'Centro',
      cidade: 'São Paulo',
      estado: 'SP',
      dataInicial: o.dataInicial,
      data_agendamento: null,
      dataEfetiva: o.dataInicial,
      hora: '09:30',
      descricaoProduto: o.descricaoProduto,
      defeito: o.defeito,
      tipo_servico: 'manutencao',
      prioridade: null,
      nao_programada: 0,
      urlRota: 'https://www.google.com/maps/dir/?api=1&destination=Rua+das+Flores',
      urlWhats: '5511988887777',
      aba: o.status === 'Em Andamento' ? 'hoje' : 'proximos',
      rotuloStatus: o.status === 'Em Andamento' ? 'Em atendimento' : 'Agendada',
      statusClasse: o.status === 'Em Andamento' ? 'andamento' : 'agendada',
    };
  }

  async function handleTecnico(req, res, path, method, url) {
    const osD = /^\/api\/v1\/tecnico\/os\/(\d+)$/.exec(path);
    const osBy = state.os[0];

    if (path === '/api/v1/tecnico' && method === 'GET') {
      return ok(res, {
        perfil: {
          id: 1,
          nome: 'Carlos Técnico',
          primeiroNome: 'Carlos',
          email: 'tecnico@empresa.com',
          fotoUrl: null,
          cargo: 'Técnico de campo',
        },
        hoje: '2026-11-04',
        proxima: { ...osResumo(osBy), continuar: osBy.status === 'Em Andamento' },
        agenda: [osResumo(osBy)],
        doDia: [osResumo(osBy)],
        grupos: { hoje: 1, proximos: 1, atrasadas: 1, concluidas: 0 },
        naoRealizadas: state.naoRealizadas,
        qtdNaoRealizadas: state.naoRealizadas.length,
        filaQtd: state.fila.length,
        podeCriarAtividade: true,
      });
    }

    if (path === '/api/v1/tecnico/os' && method === 'GET') {
      const aba = url.searchParams.get('aba') ?? 'hoje';
      const ordens = aba === 'atrasadas' ? [] : [osResumo(osBy), osResumo(state.os[1] ?? osBy)];
      return ok(res, {
        aba,
        hoje: '2026-11-04',
        contagens: { hoje: 1, proximos: 1, atrasadas: 1, concluidas: 0 },
        ordens,
      });
    }

    if (osD && method === 'GET') {
      const id = Number(osD[1]);
      const os = state.os.find((o) => o.idOs === id);
      if (!os) return fail(res, 'OS não encontrada ou não está designada a você.', 404);
      return ok(res, {
        os: {
          idOs: os.idOs,
          status: os.status,
          dataInicial: os.dataInicial,
          dataFinal: os.dataFinal,
          data_agendamento: null,
          descricaoProduto: os.descricaoProduto,
          defeito: os.defeito,
          observacoes: os.observacoes,
          laudoTecnico: '',
          tipo_servico: 'manutencao',
          prioridade: null,
          nao_programada: state.naoProgramada ? 1 : 0,
          garantia: os.garantia,
        },
        cliente: {
          idClientes: 1,
          nomeCliente: os.nomeCliente,
          celular: os.celular_cliente,
          telefone: os.telefone_cliente,
          documento: '123.456.789-00',
          rua: 'Rua das Flores',
          numero: '100',
          bairro: 'Centro',
          cidade: 'São Paulo',
          estado: 'SP',
        },
        endereco: 'Rua das Flores, 100 - Centro',
        urlRota: 'https://www.google.com/maps/dir/?api=1&destination=Rua+das+Flores',
        urlWhats: '5511988887777',
        checkinAtivo: state.checkin && state.checkin.os_id === id ? state.checkin : null,
        observacoes: state.observacoes.filter((o) => o.os_id === id),
        fotos: [],
        servicosPrevistos: [{ nome: 'Diagnóstico e reparo', quantidade: 1 }],
        produtosPrevistos: [{ nome: 'Fonte 65W', quantidade: 1 }],
        historicoCliente: [],
        podeCheckin: !state.checkin,
        podeCheckout: Boolean(state.checkin),
        podeFoto: true,
        podeNaoRealizado: true,
        motivosNaoRealizado: state.motivosNaoRealizado,
      });
    }

    if (path === '/api/v1/tecnico/checkin' && method === 'POST') {
      const body = await readBody(req);
      state.checkin = { os_id: Number(body.os_id), idCheckin: 1, data_entrada: '2026-11-04 09:00:00', status: 'Em Andamento' };
      state.ultimoCheckin = body;
      return ok(res, { checkin_id: 1, data_entrada: '2026-11-04 09:00:00', status: 'Em Andamento' }, 'Atendimento iniciado.');
    }

    if (path === '/api/v1/tecnico/checkout' && method === 'POST') {
      const body = await readBody(req);
      state.ultimoCheckout = body;
      const confirmado = /^data:image\/(png|jpeg);base64,/.test(String(body.assinatura ?? ''));
      if (!confirmado) {
        return fail(res, 'Assinatura do técnico obrigatória para finalizar o atendimento.', 400);
      }
      state.checkin = null;
      return ok(res, { os_id: Number(body.os_id), status: 'Finalizado', data_saida: '2026-11-04 10:30:00' }, 'Atendimento finalizado.');
    }

    if (path === '/api/v1/tecnico/observacoes' && method === 'POST') {
      const body = await readBody(req);
      const nota = {
        id: state.observacoes.length + 1,
        os_id: Number(body.os_id),
        texto: body.texto,
        hora: '04/11/2026 10:00',
        autor: 'Carlos Técnico',
        registrado_em: body.registrado_em ?? null,
      };
      state.observacoes.unshift(nota);
      return ok(res, nota, 'Observação registrada.');
    }

    if (path === '/api/v1/tecnico/fotos' && method === 'POST') {
      const body = await readBody(req);
      state.fotoSeq += 1;
      return ok(res, { id: state.fotoSeq, etapa: body.etapa ?? 'durante', url: `tecnico/foto/${state.fotoSeq}` }, 'Foto enviada.');
    }

    // A API devolve "tecnico/foto/N" (sem barra); o app completa com /api/v1.
    if (/^\/api\/v1\/tecnico\/foto\/\d+$/.test(path) && method === 'GET') {
      const png = Buffer.from(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
        'base64',
      );
      res.writeHead(200, { 'Content-Type': 'image/png', 'Content-Length': png.length });
      res.end(png);
      return;
    }

    if (path === '/api/v1/tecnico/nao-realizado' && method === 'POST') {
      const body = await readBody(req);
      const reg = {
        id: state.naoRealizadas.length + 1,
        os_id: Number(body.os_id),
        idOs: Number(body.os_id),
        nomeCliente: osBy.nomeCliente,
        motivo: state.motivosNaoRealizado.find((m) => m.id === Number(body.motivo_id))?.nome ?? body.observacao,
        observacao: body.observacao ?? null,
        data_registro: '2026-11-04 11:00:00',
      };
      state.naoRealizadas.push(reg);
      return ok(res, reg, 'Registrado.');
    }

    if (path === '/api/v1/tecnico/nao-realizadas' && method === 'GET') {
      return ok(res, state.naoRealizadas, 'Serviços não realizados');
    }

    if (path === '/api/v1/tecnico/motivos' && method === 'GET') {
      return ok(res, state.motivosNaoRealizado, 'Motivos');
    }

    if (path === '/api/v1/tecnico/nao-realizadas/reagendar' && method === 'POST') {
      const body = await readBody(req);
      state.ultimoReagendamento = body;
      return ok(res, { ocorrencia_id: Number(body.ocorrencia_id), os_id: 1000, nova_data: body.nova_data }, 'OS reagendada.');
    }

    if (path === '/api/v1/tecnico/nao-realizadas/reabrir' && method === 'POST') {
      const body = await readBody(req);
      return ok(res, { ocorrencia_id: Number(body.ocorrencia_id), os_id: 1000 }, 'OS reaberta.');
    }

    if (path === '/api/v1/tecnico/clientes' && method === 'GET') {
      const q = (url.searchParams.get('q') ?? '').toLowerCase();
      return ok(
        res,
        state.clientes
          .filter((c) => !q || c.nomeCliente.toLowerCase().includes(q))
          .map((c) => ({
            id: c.idClientes,
            nome: c.nomeCliente,
            documento: c.documento,
            telefone: c.celular,
            exige_chamado: Boolean(c.exige_chamado),
          })),
      );
    }

    if (path === '/api/v1/tecnico/servicos' && method === 'GET') {
      return ok(res, [{ id: 7, nome: 'Diagnóstico e reparo' }]);
    }

    if (path === '/api/v1/tecnico/produtos' && method === 'GET') {
      return ok(res, [{ id: 3, nome: 'Fonte 65W', estoque: '10' }]);
    }

    if (path === '/api/v1/tecnico/atividade' && method === 'POST') {
      const body = await readBody(req);
      const id = state.nextOsId++;
      state.os.push({
        ...osBy,
        idOs: id,
        status: 'Aberto',
        descricaoProduto: body.descricaoProduto ?? '',
        defeito: body.defeito ?? '',
      });
      state.ultimaAtividade = body;
      return ok(res, { idOs: id, servicos: (body.servicos ?? []).length, produtos: (body.produtos ?? []).length, status: 'Aberto' }, 'Atividade aberta com sucesso!');
    }

    if (path === '/api/v1/tecnico/projetos' && method === 'GET') {
      return ok(res, [
        {
          idObra: 1,
          nome: 'Condomínio Vila Verde',
          nomeCliente: 'Mercado Bom Preço',
          status: 'em_execucao',
          percentual_concluido: 40,
          qtdPontos: 3,
          minha: true,
        },
      ]);
    }

    if (path === '/api/v1/tecnico/projetos/1' && method === 'GET') {
      return ok(res, {
        obra: { idObra: 1, nome: 'Condomínio Vila Verde', nomeCliente: 'Mercado Bom Preço', status: 'em_execucao', percentual_concluido: 40, qtdPontos: 3 },
        osVinculadas: [{ idOs: 1001, status: 'Aberto', dataInicial: '2026-11-02', defeito: 'Instalação de câmeras' }],
        consumo: [{ nome: 'Cabo UTP Cat6', quantidade: '120', tipo: 'material' }],
        rdos: [{ id: 1, numero: 1, data: '2026-11-03', atividades: 'Infraestrutura do bloco A' }],
        pontos: [
          { idPonto: 1, identificacao: 'CAM-01', tipo: 'camera', status: 'previsto', status_rotulo: 'Previsto', minha: true, local: 'Entrada', qtd_fotos: 0 },
          { idPonto: 2, identificacao: 'NVR-01', tipo: 'nvr', status: 'infra', status_rotulo: 'Infraestrutura pronta', minha: false, responsavel: 'Ana Silva', qtd_fotos: 1 },
        ],
        statusLista: {
          previsto: { rotulo: 'Previsto', classe: 'neutro', peso: 0 },
          infra: { rotulo: 'Infraestrutura pronta', classe: 'info', peso: 20 },
          cabeado: { rotulo: 'Cabo passado', classe: 'info', peso: 40 },
          instalado: { rotulo: 'Instalado', classe: 'alerta', peso: 70 },
          configurado: { rotulo: 'Configurado', classe: 'alerta', peso: 90 },
          testado: { rotulo: 'Testado / certificado', classe: 'sucesso', peso: 100 },
        },
        executores: { 1: [{ idUsuarios: 1, nome: 'Carlos Técnico' }, { idUsuarios: 2, nome: 'Ana Silva' }] },
      });
    }

    const pontoGet = /^\/api\/v1\/tecnico\/pontos\/(\d+)$/.exec(path);
    if (pontoGet && method === 'GET') {
      const idP = Number(pontoGet[1]);
      return ok(res, {
        ponto: { idPonto: idP, obra_id: 1, identificacao: `CAM-0${idP}`, tipo: 'camera', local: 'Entrada', status: 'previsto', status_rotulo: 'Previsto', minha: true, qtd_fotos: 0 },
        statusLista: {
          previsto: { rotulo: 'Previsto', classe: 'neutro', peso: 0 },
          infra: { rotulo: 'Infraestrutura pronta', classe: 'info', peso: 20 },
          instalado: { rotulo: 'Instalado', classe: 'alerta', peso: 70 },
          testado: { rotulo: 'Testado / certificado', classe: 'sucesso', peso: 100 },
        },
        proximoStatus: 'infra',
        testeItens: {
          imagem_ok: 'Imagem ok',
          gravando_nvr: 'Gravando no NVR',
          visao_noturna: 'Visão noturna (IR) ok',
          foco_enquadramento: 'Foco / enquadramento ok',
          data_hora: 'Data/hora correta',
        },
        exigeTeste: false,
        exigeEtiqueta: false,
        temFotoEtiqueta: false,
        historico: [],
        fotos: [],
        executores: [{ idUsuarios: 1, nome: 'Carlos Técnico' }],
      });
    }

    if (/^\/api\/v1\/tecnico\/pontos\/\d+\/status$/.test(path) && method === 'POST') {
      const body = await readBody(req);
      state.ultimoStatusPonto = body;
      return ok(res, {
        ponto: { idPonto: 1, obra_id: 1, identificacao: 'CAM-01', tipo: 'camera', status: body.status, status_rotulo: body.status, minha: true, qtd_fotos: 1 },
        fotos_salvas: (body.fotos ?? []).length,
        repassado: null,
      }, 'CAM-01 atualizado.');
    }

    if (/^\/api\/v1\/tecnico\/pontos\/\d+\/assumir$/.test(path) && method === 'POST') {
      return ok(res, { idPonto: 1 }, 'CAM-01: assumido por você.');
    }

    // ---------- configuração de câmeras / NVR ----------
    if (/^\/api\/v1\/tecnico\/projetos\/\d+\/camera-config$/.test(path) && method === 'GET') {
      return ok(res, {
        obra: { idObra: 1, nome: 'Condomínio Vila Verde', nomeCliente: 'Mercado Bom Preço', endereco: 'Rua das Flores, 100' },
        nvr: [
          {
            idPonto: 2, identificacao: 'NVR-01', tipo: 'nvr', status: 'previsto', status_rotulo: 'Previsto',
            ip: '192.168.1.10', modelo: 'DS-7608NI-K2', serie: 'DS-7608NI-K2-0001', mac: null, nvr_id: null,
            configurada: false,
          },
        ],
        cameras: [
          {
            idPonto: 1, identificacao: 'CAM-01', tipo: 'camera', local: 'Entrada', status: 'previsto',
            status_rotulo: 'Previsto', ip: null, canal: null, modelo: null, serie: null, mac: null,
            nvr_id: null, configurada: false,
          },
          {
            idPonto: 3, identificacao: 'CAM-02', tipo: 'camera', local: 'Estacionamento', status: 'configurado',
            status_rotulo: 'Configurado', ip: '192.168.1.31', canal: '2', modelo: 'DS-2CD2143G2', serie: 'SN-889',
            mac: '44:19:B6:00:00:01', nvr_id: 2, configurada: true,
          },
        ],
        resumo: { total: 2, configuradas: 1, pendentes: 1 },
        canaisOcupados: { 2: { 2: 'CAM-02' } },
      }, 'Plano de câmeras do projeto.');
    }

    if (/^\/api\/v1\/tecnico\/projetos\/\d+\/camera-config\/pontos\/\d+$/.test(path) && method === 'POST') {
      const body = await readBody(req);
      state.ultimaCamera = body;
      const id = Number(path.match(/pontos\/(\d+)$/)?.[1] ?? 1);
      return ok(res, {
        ponto: {
          idPonto: id, identificacao: 'CAM-01', tipo: 'camera', status: body.status ?? 'configurado',
          status_rotulo: 'Configurado', ip: body.ip ?? null, canal: body.canal ?? null,
          mac: body.mac ?? null, nvr_id: body.nvr_id ?? null, configurada: true,
        },
      }, 'CAM-01: configuração salva.');
    }

    if (/^\/api\/v1\/tecnico\/projetos\/\d+\/camera-config\/nova$/.test(path) && method === 'POST') {
      const body = await readBody(req);
      state.ultimaCameraNova = body;
      return ok(res, {
        idPonto: 9,
        ponto: {
          idPonto: 9, identificacao: 'CAM-09', tipo: 'camera', status: 'configurado',
          status_rotulo: 'Configurado', ip: body.ip ?? null, canal: body.canal ?? null,
          mac: body.mac ?? null, nvr_id: body.nvr_id ?? null, configurada: true,
        },
      }, 'CAM-09: ponto criado e configurado.');
    }

    if (/^\/api\/v1\/tecnico\/projetos\/\d+\/camera-config\/lote$/.test(path) && method === 'POST') {
      const body = await readBody(req);
      state.cameraLote = body;
      const total = (body.cameras ?? []).length;
      return ok(res, {
        salvas: total, criadas: 0, falhas: [],
        plano: { nvr: [], cameras: [], resumo: { total, configuradas: total, pendentes: 0 } },
      }, `${total} câmeras salvas no projeto.`);
    }

    if (path === '/api/v1/tecnico/fila' && method === 'GET') {
      return ok(res, {
        pontos: [
          { idPonto: 1, obra_id: 1, obra_nome: 'Condomínio Vila Verde', identificacao: 'CAM-01', status: 'infra', status_rotulo: 'Infraestrutura pronta', minha: true, local: 'Entrada' },
          { idPonto: 3, obra_id: 1, obra_nome: 'Condomínio Vila Verde', identificacao: 'CAM-03', status: 'previsto', status_rotulo: 'Previsto', minha: false, responsavel: 'Ana Silva', local: 'Garagem' },
        ],
        statusLista: { previsto: { rotulo: 'Previsto' }, infra: { rotulo: 'Infraestrutura pronta' } },
      });
    }

    if (path === '/api/v1/tecnico/perfil' && method === 'GET') {
      return ok(res, {
        id: 1,
        nome: 'Carlos Técnico',
        primeiroNome: 'Carlos',
        email: 'tecnico@empresa.com',
        telefone: '(11) 3333-4444',
        celular: '(11) 98888-7777',
        fotoUrl: null,
        cargo: 'Técnico de campo',
        colaboradorVinculado: false,
        iniciais: 'CT',
        equipes: [{ id: 1, nome: 'Equipe A', encarregado: true }],
        numeros: { mes: '11/2026', osAtendidas: 12, pontosAvancados: 7 },
      });
    }

    if (path === '/api/v1/tecnico/perfil' && method === 'PUT') {
      const body = await readBody(req);
      state.perfilAtualizado = body;
      return ok(res, { id: 1, nome: 'Carlos Técnico', primeiroNome: 'Carlos', telefone: body.telefone, celular: body.celular, fotoUrl: null }, 'Contato atualizado.');
    }

    if (path === '/api/v1/tecnico/relatorio' && method === 'GET') {
      const tipo = url.searchParams.get('tipo') || 'diario';
      const data = url.searchParams.get('data') || '2026-11-04';
      state.relatorioPedidos = [...(state.relatorioPedidos ?? []), { tipo, data }];
      return ok(res, {
        tipo,
        periodo: { inicio: data, fim: data, rotulo: `${tipo} de ${data}`, anterior: '2026-11-03', proximo: '2026-11-05' },
        totais: { registros: 2, projetos: 1, fotos: 4, ocorrencias: 1, avancos: 3, cabo_m: 35.5 },
        projetos: [
          {
            idObra: 1,
            nome: 'Condomínio Vila Verde',
            cliente: 'Construtora Alfa',
            percentual_concluido: 42.5,
            registros: [{ data, atividades: 'Passagem de cabos no bloco B', ocorrencias: 'Falta de escada' }],
            avancos: [{ data: `${data} 14:05:00`, evento: 'status', identificacao: 'CAM-07', local: 'Corredor B', para_rotulo: 'Cabo passado', metragem_cabo: 35 }],
          },
        ],
      });
    }

    if (path === '/api/v1/tecnico/a-caminho' && method === 'POST') {
      state.aCaminho = await readBody(req);
      return ok(res, { hora: '09:10', repetido: false }, 'Cliente avisado às 09:10');
    }

    if (path === '/api/v1/tecnico/posicao' && method === 'POST') {
      state.posicoes = [...(state.posicoes ?? []), await readBody(req)];
      return ok(res, { registrado: true, em_atendimento: false }, 'Posição recebida');
    }

    const solic = /^\/api\/v1\/tecnico\/os\/(\d+)\/(assinatura-solicitante|link-solicitante|link-solicitante\/email)$/.exec(path);
    if (solic) {
      state.solicitante ??= { situacao: 'invalido', link: null, assinado: false };
      const sol = state.solicitante;
      const acao = solic[2];
      if (acao === 'assinatura-solicitante' && method === 'GET') {
        return ok(res, {
          suportado: true,
          situacao: sol.situacao,
          link: sol.link,
          email_cliente: 'ana@cliente.com',
          assinado: sol.assinado,
          assinaturas: sol.assinado ? [{ tipo: 'solicitante', nome: sol.nome, data: '2026-11-04 10:00:00' }] : [],
        });
      }
      if (acao === 'assinatura-solicitante' && method === 'POST') {
        const body = await readBody(req);
        if (!String(body.assinatura ?? '').startsWith('data:image/png;base64,')) return fail(res, 'Assinatura em branco.', 400);
        Object.assign(sol, { assinado: true, nome: body.nome, documento: body.documento, imagem: body.assinatura });
        return ok(res, { assinado: true }, 'Assinatura registrada com sucesso!');
      }
      if (acao === 'link-solicitante' && method === 'POST') {
        Object.assign(sol, { situacao: 'pendente', link: `${url.origin}/aceite/abc123` });
        return ok(res, { link: sol.link, expira: '11/11/2026' }, 'Link gerado com sucesso!');
      }
      if (acao === 'link-solicitante/email' && method === 'POST') {
        const body = await readBody(req);
        sol.email = body.email;
        return ok(res, { link: sol.link ?? `${url.origin}/aceite/abc123` }, `E-mail enfileirado para ${body.email}.`);
      }
    }

    return fail(res, 'Rota do técnico não encontrada na API.', 404);
  }

  // ==================================================================
  // Área do colaborador
  // ==================================================================

  const PONTO_SEQ = ['entrada', 'inicio_intervalo', 'fim_intervalo', 'inicio_intervalo', 'saida'];

  function proximoTipo() {
    const ultima = state.batidas[state.batidas.length - 1];
    if (!ultima) return 'entrada';
    if (ultima.tipo === 'entrada') return 'inicio_intervalo';
    if (ultima.tipo === 'inicio_intervalo') return 'fim_intervalo';
    if (ultima.tipo === 'fim_intervalo') return 'inicio_intervalo';
    return 'entrada';
  }

  function batidaFormatada(b) {
    const labels = {
      entrada: 'Entrada',
      saida: 'Saída',
      inicio_intervalo: 'Início do intervalo',
      fim_intervalo: 'Fim do intervalo',
    };
    return {
      id: b.id,
      tipo: b.tipo,
      label: labels[b.tipo],
      hora: String(b.data_hora).slice(11, 16),
      data_hora: b.data_hora,
      latitude: b.latitude ?? null,
      longitude: b.longitude ?? null,
      os_id: b.os_id ?? null,
      obra_id: null,
      dentro_geofence: b.dentro_geofence ?? 1,
      distancia_metros: b.distancia_metros ?? 0,
    };
  }

  function totais() {
    const batidas = state.batidas.length;
    return {
      dias_trabalhados: batidas ? 1 : 0,
      minutos_trabalhados: batidas ? 180 : 0,
      minutos_previstos: 480,
      minutos_extras_50: batidas ? 30 : 0,
      minutos_extras_100: 0,
      minutos_faltas: batidas ? 0 : 480,
      saldo_banco_min: batidas ? 30 : -480,
    };
  }

  async function handleColaborador(req, res, path, method, url) {
    if (path === '/api/v1/colaborador' && method === 'GET') {
      return ok(res, {
        colaborador: { id: 1, nome: 'Maria Colaboradora', primeiroNome: 'Maria', cargo: 'Auxiliar administrativo', fotoUrl: null },
        batidasHoje: state.batidas.map(batidaFormatada),
        proximoTipo: proximoTipo(),
        totaisMes: totais(),
        totaisSemana: { minutos_trabalhados: 180, minutos_faltas: 0, inicio: '2026-11-02', fim: '2026-11-08' },
        horas: { normais_min: 180, extras_50_min: 30, extras_100_min: 0, saldo_min: 30 },
        pontoInicio: '2026-01-01',
        pendentes: state.ocorrencias.filter((o) => o.status === 'pendente').length,
        podeBaterPonto: true,
        hoje: '2026-11-04',
      });
    }

    if (path === '/api/v1/colaborador/espelho' && method === 'GET') {
      const competencia = url.searchParams.get('competencia') ?? '2026-11';
      return ok(res, {
        competencia,
        linhas: [
          { data: '2026-11-02', dia_semana: 1, eh_util: true, abonado: false, cobra_falta: true, batidas: [], calc: { trabalhado: 0, previsto: 0, extra50: 0, extra100: 0, falta: 480, saldo: -480 } },
          { data: '2026-11-03', dia_semana: 2, eh_util: true, abonado: false, cobra_falta: true, batidas: [{ id: 9, tipo: 'entrada', hora: '08:00', data_hora: '2026-11-03 08:00:00', dentro_geofence: 1 }], calc: { trabalhado: 480, previsto: 480, extra50: 30, extra100: 0, falta: 0, saldo: 30 } },
          { data: '2026-11-04', dia_semana: 3, eh_util: true, abonado: false, cobra_falta: true, batidas: state.batidas.map(batidaFormatada), calc: { trabalhado: 180, previsto: 480, extra50: 0, extra100: 0, falta: 0, saldo: -300 } },
        ],
        totais: totais(),
        totaisSemana: { minutos_trabalhados: 180, minutos_faltas: 0 },
        pontoInicio: '2026-01-01',
        horas: { normais_min: 180, extras_50_min: 30, extras_100_min: 0, saldo_min: 30 },
      });
    }

    if (path === '/api/v1/colaborador/batidas' && method === 'GET') {
      return ok(res, { data: url.searchParams.get('data') ?? '2026-11-04', batidas: state.batidas.map(batidaFormatada) });
    }

    if (path === '/api/v1/colaborador/holerite' && method === 'GET') {
      return ok(res, {
        competencia: url.searchParams.get('competencia') ?? '2026-11',
        resumo: {
          proventos: 3200,
          descontos: 480,
          liquido: 2720,
          fgts: 256,
          itens: [
            { tipo: 'salario', natureza: 'provento', descricao: 'Salário base', valor: 3000 },
            { tipo: 'extra', natureza: 'provento', descricao: 'Horas extras 50%', valor: 200 },
            { tipo: 'inss', natureza: 'desconto', descricao: 'INSS', valor: 320 },
            { tipo: 'irrf', natureza: 'desconto', descricao: 'IRRF', valor: 160 },
          ],
        },
        arquivo: { nome: 'holerite-2026-11.pdf', mime: 'application/pdf', url: 'colaborador/holerite/arquivo' },
      });
    }

    if (path === '/api/v1/colaborador/holerite/arquivo' && method === 'GET') {
      const pdf = '%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF';
      res.writeHead(200, { 'Content-Type': 'application/pdf', 'Content-Length': Buffer.byteLength(pdf) });
      res.end(pdf);
      return;
    }

    if (path === '/api/v1/colaborador/dados' && method === 'GET') {
      return ok(res, {
        id: 1,
        nome: 'Maria Colaboradora',
        primeiroNome: 'Maria',
        cargo: 'Auxiliar administrativo',
        email: 'maria@empresa.com',
        celular: '11977776666',
        pix_tipo: 'email',
        pix_chave: 'maria@empresa.com',
        fotoUrl: null,
        admissao: '2024-03-01',
        unidade: 'Matriz',
      });
    }

    if (path === '/api/v1/colaborador/dados' && method === 'PUT') {
      const body = await readBody(req);
      state.dadosAtualizados = body;
      return ok(res, { id: 1, nome: 'Maria Colaboradora', email: body.email, celular: body.celular, pix_tipo: body.pix_tipo, pix_chave: body.pix_chave, fotoUrl: null }, 'Dados atualizados.');
    }

    if (path === '/api/v1/colaborador/ocorrencias' && method === 'GET') {
      return ok(res, state.ocorrencias, 'Minhas solicitações');
    }

    if (path === '/api/v1/colaborador/ocorrencias' && method === 'POST') {
      const body = await readBody(req);
      const reg = {
        id: state.ocorrencias.length + 1,
        tipo: body.tipo,
        data_referencia: body.data_referencia ?? null,
        correcao_tipo: body.correcao_tipo ?? null,
        correcao_data_hora: body.correcao_data_hora ?? null,
        descricao: body.descricao,
        status: 'pendente',
        resposta: null,
        tem_anexo: Boolean(body.anexo),
        created_at: '2026-11-04 12:00:00',
      };
      state.ocorrencias.unshift(reg);
      return ok(res, reg, 'Solicitação enviada para o RH.');
    }

    if (path === '/api/v1/colaborador/ausencias' && method === 'GET') {
      return ok(res, state.ausencias, 'Minhas folgas e férias');
    }

    if (path === '/api/v1/colaborador/ausencias' && method === 'POST') {
      const body = await readBody(req);
      const dias = Math.max(
        1,
        Math.round((new Date(body.data_fim ?? body.data_inicio) - new Date(body.data_inicio)) / 86400000) + 1,
      );
      const reg = {
        id: state.ausencias.length + 1,
        tipo: body.tipo,
        data_inicio: body.data_inicio,
        data_fim: body.data_fim ?? body.data_inicio,
        dias,
        motivo: body.motivo ?? null,
        status: 'pendente',
        resposta: null,
        tem_anexo: Boolean(body.anexo),
        created_at: '2026-11-04 12:00:00',
      };
      state.ausencias.unshift(reg);
      return ok(res, reg, 'Solicitação enviada para o RH.');
    }

    if (path === '/api/v1/ponto' && method === 'GET') {
      return ok(res, {
        colaborador: { id: 1, nome: 'Maria Colaboradora' },
        hoje: '2026-11-04',
        batidasHoje: state.batidas.map(batidaFormatada),
        proximoTipo: proximoTipo(),
        unidades: [{ id: 1, nome: 'Matriz', latitude: -23.55, longitude: -46.63, raio_metros: 200 }],
        os: [],
        obras: [],
        temBiometria: false,
        config: { geofenceObrigatorio: false, faceObrigatorio: false, faceScoreMinimo: 0.55 },
      });
    }

    if (path === '/api/v1/ponto/registrar' && method === 'POST') {
      const body = await readBody(req);
      const tipo = body.tipo ?? 'entrada';
      state.batidas.push({
        id: state.batidas.length + 1,
        tipo,
        data_hora: `2026-11-04 ${String(9 + state.batidas.length).padStart(2, '0')}:00:00`,
        latitude: body.latitude ?? null,
        longitude: body.longitude ?? null,
        os_id: body.os_id ?? null,
        dentro_geofence: 1,
        distancia_metros: 12,
      });
      state.ultimaBatida = body;
      return ok(
        res,
        {
          registro_id: state.batidas.length,
          tipo,
          hora: '09:00',
          proximoTipo: proximoTipo(),
          fora_area: false,
          distancia_metros: 12,
          os_id: body.os_id ?? null,
          obra_id: null,
          batidasHoje: state.batidas.map(batidaFormatada),
        },
        'Batida registrada.',
      );
    }

    return fail(res, 'Rota do colaborador não encontrada na API.', 404);
  }

  function serveStatic(res, path, root = process.env.MAPOS_DIST) {
    if (!root) {
      res.writeHead(500).end('MAPOS_DIST não definido');
      return;
    }

    // path.normalize no Windows troca "/" por "\": trata separadores e evita
    // escapar do dist antes de juntar com a raiz.
    const safe = path.replace(/\\/g, '/').replace(/^\/+/, '').replace(/(\.\.\/)+/g, '');
    let file = join(root, safe);

    let isDirectory = false;
    try {
      isDirectory = statSync(file).isDirectory();
    } catch {
      isDirectory = true;
    }
    if (!safe || isDirectory) file = join(root, 'index.html'); // SPA fallback

    try {
      const body = readFileSync(file);
      res.writeHead(200, { 'Content-Type': MIME[extname(file)] ?? 'application/octet-stream' });
      res.end(body);
    } catch {
      res.writeHead(404).end('not found');
    }
  }

  return { server, state, TOKEN };
}
