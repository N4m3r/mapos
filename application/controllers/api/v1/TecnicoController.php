<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API v1 — Área do Técnico: painel, minhas OS, detalhe, projetos, fila e perfil.
 *
 * Reusa os mesmos models e o helper TecnicoInicio da web, então agrupamento de
 * agenda, próxima visita e endereço saem idênticos à tela PHP. A sessão aqui é
 * JWT (Bearer), não cookie — o app não depende de WebView nem de CSRF.
 *
 * Toda consulta é restrita ao usuário do token: um técnico nunca lê OS de outro.
 * Ações de campo (check-in, fotos, não realizado) ficam em TecnicoAtendimentoController.
 */
require_once __DIR__ . '/AreaApiController.php';

class TecnicoController extends AreaApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('tecnico_model');
        $this->load->model('checkin_model');
        $this->load->model('naorealizada_model');
        $this->load->model('os_observacoes_tecnico_model');
        $this->load->model('fotosatendimento_model');
        $this->load->model('obras_model');
        $this->load->model('obra_pontos_model');
        $this->load->model('mapos_model');
        $this->load->model('os_model');
        $this->load->helper('tecnico_inicio');
        // Regras puras do atendimento (status que aceita check-in).
        require_once APPPATH . 'helpers/atendimento_regras_helper.php';
    }

    // ------------------------------------------------------------------
    // Painel
    // ------------------------------------------------------------------

    /** GET /api/v1/tecnico — painel inicial do técnico. */
    public function index_get()
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $hoje = date('Y-m-d');

        $agenda = $this->tecnico_model->getAgendaTecnico($uid);
        $grupos = TecnicoInicio::agrupar($agenda, $hoje);
        $proxima = TecnicoInicio::proximaVisita($agenda, date('Y-m-d H:i:s'));

        $concluidasHoje = 0;
        foreach ($grupos['concluidas'] as $os) {
            if (substr((string) TecnicoInicio::dataEfetiva($os), 0, 10) === $hoje) {
                $concluidasHoje++;
            }
        }
        $atrasadas = 0;
        foreach ($grupos['atrasadas'] as $os) {
            if ($os->status !== 'Não Realizado') {
                $atrasadas++;
            }
        }

        $this->ok([
            'perfil' => $this->perfil($user),
            'hoje' => $hoje,
            'proxima' => $proxima
                ? $this->osResumo($proxima['os']) + ['continuar' => (bool) $proxima['continuar']]
                : null,
            'agenda' => array_map([$this, 'osResumo'], $agenda),
            'doDia' => array_map([$this, 'osResumo'], TecnicoInicio::doDia($agenda, $hoje)),
            'grupos' => [
                'hoje' => count($grupos['hoje']),
                'proximos' => count($grupos['proximos']),
                'atrasadas' => $atrasadas,
                'concluidas' => $concluidasHoje,
            ],
            'naoRealizadas' => array_slice($this->naoRealizadas($uid), 0, 3),
            'qtdNaoRealizadas' => (int) $this->naorealizada_model->contarPendentes($uid),
            'filaQtd' => (int) $this->obra_pontos_model->contarFila($uid),
            'podeCriarAtividade' => $this->permission->checkPermission($user->level, 'aTecnicoAtividade'),
        ], 'Painel do técnico');
    }

    // ------------------------------------------------------------------
    // Minhas OS
    // ------------------------------------------------------------------

    /** GET /api/v1/tecnico/os?aba=hoje|proximos|atrasadas|concluidas|todas */
    public function os_get()
    {
        $user = $this->exigir('vTecnicoOS');
        $uid = (int) $user->usuario->idUsuarios;
        $hoje = date('Y-m-d');

        $grupos = TecnicoInicio::agrupar($this->tecnico_model->getAgendaTecnico($uid), $hoje);
        $contagens = [
            'hoje' => count($grupos['hoje']),
            'proximos' => count($grupos['proximos']),
            'atrasadas' => count($grupos['atrasadas']),
            'concluidas' => count($grupos['concluidas']),
        ];

        $aba = (string) $this->get('aba');
        if ($aba === 'todas') {
            $ordens = array_merge($grupos['hoje'], $grupos['atrasadas'], $grupos['proximos'], $grupos['concluidas']);
        } elseif (isset($grupos[$aba])) {
            $ordens = $grupos[$aba];
        } else {
            $aba = 'hoje';
            $ordens = $grupos['hoje'];
        }

        $this->ok([
            'aba' => $aba,
            'hoje' => $hoje,
            'contagens' => $contagens,
            'ordens' => array_map([$this, 'osResumo'], $ordens),
        ], 'Minhas OS');
    }

    /** GET /api/v1/tecnico/os/{id} — detalhe para o atendimento. */
    public function os_detalhe_get($id = null)
    {
        $user = $this->exigir('vTecnicoOS');
        $uid = (int) $user->usuario->idUsuarios;
        $osId = (int) ($id !== null ? $id : $this->get('id'));

        $os = $this->osPermitida($osId, $uid);
        if (! $os) {
            $this->naoEncontrada('OS não encontrada ou não está designada a você.');
        }

        $cliente = $this->db->where('idClientes', $os->clientes_id)->get('clientes')->row();
        $checkin = $this->checkin_model->getCheckinAtivo($osId);

        $this->ok([
            'os' => [
                'idOs' => (int) $os->idOs,
                'status' => $os->status,
                'dataInicial' => $os->dataInicial ?? null,
                'dataFinal' => $os->dataFinal ?? null,
                'data_agendamento' => $os->data_agendamento ?? null,
                'descricaoProduto' => $os->descricaoProduto ?? null,
                'defeito' => $os->defeito ?? null,
                'observacoes' => $os->observacoes ?? null,
                'laudoTecnico' => $os->laudoTecnico ?? null,
                'tipo_servico' => $os->tipo_servico ?? null,
                'prioridade' => $os->prioridade ?? null,
                'nao_programada' => $os->nao_programada ?? null,
                'chamado_id' => $os->chamado_id ?? null,
                'latitude' => $os->latitude ?? null,
                'longitude' => $os->longitude ?? null,
                'garantia' => $os->garantia ?? null,
            ],
            'cliente' => $cliente ? $this->cliente($cliente) : null,
            'endereco' => TecnicoInicio::enderecoCurto($os),
            'urlRota' => TecnicoInicio::urlRota($os),
            'urlWhats' => TecnicoInicio::foneWhats($cliente->celular ?? ($cliente->telefone ?? '')),
            'checkinAtivo' => $checkin ? [
                'idCheckin' => (int) $checkin->idCheckin,
                'data_entrada' => $checkin->data_entrada,
                'status' => $checkin->status,
            ] : null,
            'observacoes' => $this->observacoes($osId),
            'fotos' => $this->fotos($osId),
            'servicosPrevistos' => $this->servicosPrevistos($osId),
            'produtosPrevistos' => $this->produtosPrevistos($osId),
            'historicoCliente' => $this->historicoCliente((int) $os->clientes_id, $osId),
            // Status terminal nao aceita check-in: o endpoint recusa e o app
            // precisa esconder o botao, senao oferece uma acao que falha.
            'podeCheckin' => ! $checkin
                && ! AtendimentoRegras::statusBloqueiaAtendimento($os->status ?? null)
                && $this->permission->checkPermission($user->level, 'eTecnicoCheckin'),
            'podeCheckout' => (bool) $checkin && $this->permission->checkPermission($user->level, 'eTecnicoCheckout'),
            'podeFoto' => $this->permission->checkPermission($user->level, 'eTecnicoFotos'),
            'podeNaoRealizado' => $this->permission->checkPermission($user->level, 'eTecnicoNaoRealizado'),
            'motivosNaoRealizado' => $this->motivos(),
        ], 'OS #' . $osId);
    }

    /** GET /api/v1/tecnico/nao-realizadas — serviços que faltaram, aguardando decisão. */
    public function nao_realizadas_get()
    {
        $user = $this->exigir('vTecnicoOS');
        $this->ok($this->naoRealizadas((int) $user->usuario->idUsuarios), 'Serviços não realizados');
    }

    /** GET /api/v1/tecnico/motivos — motivos padronizados de serviço não realizado. */
    public function motivos_get()
    {
        $this->exigir('vTecnicoOS');
        $this->ok($this->motivos(), 'Motivos');
    }

    // ------------------------------------------------------------------
    // Cadastros da atividade avulsa (sem qualquer valor financeiro)
    // ------------------------------------------------------------------

    /** GET /api/v1/tecnico/clientes?q= — autocomplete de clientes. */
    public function clientes_get()
    {
        $this->exigir('aTecnicoAtividade');
        $q = trim((string) $this->get('q'));

        $this->ok(array_map(function ($c) {
            return [
                'id' => (int) $c->idClientes,
                'nome' => $c->nomeCliente,
                'documento' => $this->documento($c->documento ?? ''),
                'telefone' => $c->celular ?: ($c->telefone ?? null),
            ];
        }, $this->tecnico_model->buscarClientes($q)), 'Clientes');
    }

    /** GET /api/v1/tecnico/servicos?q= — autocomplete de serviços, sem preço. */
    public function servicos_get()
    {
        $this->exigir('aTecnicoAtividade');
        $q = trim((string) $this->get('q'));

        $this->ok(array_map(static function ($s) {
            return ['id' => (int) $s->idServicos, 'nome' => $s->nome];
        }, $this->tecnico_model->buscarServicos($q)), 'Serviços');
    }

    /** GET /api/v1/tecnico/produtos?q= — autocomplete de produtos, sem preço. */
    public function produtos_get()
    {
        $this->exigir('aTecnicoAtividade');
        $q = trim((string) $this->get('q'));

        $this->ok(array_map(static function ($p) {
            return [
                'id' => (int) $p->idProdutos,
                'nome' => $p->descricao,
                'unidade' => $p->unidade ?? null,
                'estoque' => $p->estoque ?? null,
            ];
        }, $this->tecnico_model->buscarProdutos($q)), 'Produtos');
    }

    // ------------------------------------------------------------------
    // Projetos (obras) que o técnico executa
    // ------------------------------------------------------------------

    /** GET /api/v1/tecnico/projetos — projetos do técnico com progresso. */
    public function projetos_get()
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;

        $this->ok(array_map(function ($o) use ($uid) {
            return $this->projetoResumo($o, $uid);
        }, $this->obras_model->getProjetosDoUsuario($uid)), 'Meus projetos');
    }

    /** GET /api/v1/tecnico/projetos/{id} — detalhe do projeto: pontos, OS, material e RDO. */
    public function projeto_get($id = null)
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $obraId = (int) ($id !== null ? $id : $this->get('id'));

        $obra = $obraId ? $this->obras_model->getObra($obraId) : null;
        if (! $obra || ! $this->obras_model->usuarioTemAcesso($obraId, $uid)) {
            $this->naoEncontrada('Projeto não encontrado ou sem acesso.');
        }

        $pontos = $this->obra_pontos_model->getPontos($obraId);
        $statusLista = Obra_pontos_model::statusLista();

        $this->ok([
            'obra' => $this->projetoResumo($obra, $uid),
            'osVinculadas' => array_map(function ($o) {
                return [
                    'idOs' => (int) $o->idOs,
                    'status' => $o->status,
                    'dataInicial' => $o->dataInicial ?? null,
                    'defeito' => $o->defeito ?? null,
                ];
            }, (array) $this->obras_model->getOsVinculadas($obraId)),
            'consumo' => array_map(function ($c) {
                return [
                    'nome' => $c->descricao ?? ($c->nome ?? ''),
                    'quantidade' => $c->quantidade ?? null,
                    'tipo' => $c->tipo ?? null,
                ];
            }, (array) $this->obras_model->getConsumo($obraId)),
            'rdos' => array_map(function ($r) {
                return [
                    'id' => (int) $r->idRdo,
                    'numero' => $r->numero ?? null,
                    'data' => $r->data ?? null,
                    'atividades' => $r->atividades ?? null,
                ];
            }, (array) $this->obras_model->getRdos($obraId)),
            'pontos' => array_map(function ($p) use ($uid, $statusLista) {
                return $this->pontoResumo($p, $uid, $statusLista);
            }, $pontos),
            'statusLista' => $statusLista,
            'executores' => $this->obra_pontos_model->executores($obraId),
        ], $obra->nome);
    }

    /** GET /api/v1/tecnico/fila — pontos repassados para mim e os livres da equipe. */
    public function fila_get()
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $statusLista = Obra_pontos_model::statusLista();

        $this->ok([
            'pontos' => array_map(function ($p) use ($uid, $statusLista) {
                $out = $this->pontoResumo($p, $uid, $statusLista);
                $out['obra_id'] = (int) $p->obra_id;
                $out['obra_nome'] = $p->obra_nome ?? null;
                $out['cliente'] = $p->cliente ?? null;
                $out['minha'] = (bool) ($p->minha ?? false);
                $out['tem_foto'] = ! empty($p->foto_id);

                return $out;
            }, $this->obra_pontos_model->getFila($uid)),
            'statusLista' => $statusLista,
        ], 'Minha fila');
    }

    // ------------------------------------------------------------------
    // Perfil
    // ------------------------------------------------------------------

    /** GET /api/v1/tecnico/perfil — conta, vínculo de RH, equipes e números do mês. */
    public function perfil_get()
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;

        $this->ok($this->perfil($user) + [
            'equipes' => $this->equipes($uid),
            'numeros' => $this->numerosMes($uid),
        ], 'Meu perfil');
    }

    /** PUT /api/v1/tecnico/perfil — só telefone/celular do próprio usuário. */
    public function perfil_put()
    {
        $user = $this->exigir('vTecnicoDashboard');
        $uid = (int) $user->usuario->idUsuarios;
        $this->load->helper('menu_tecnico');

        $corpo = $this->corpo();
        $tel = menu_tecnico_telefone($corpo['telefone'] ?? '');
        $cel = menu_tecnico_telefone($corpo['celular'] ?? '');
        if (! $tel['ok'] || ! $cel['ok']) {
            $campo = ! $tel['ok'] ? 'Telefone' : 'Celular';
            $this->invalido($campo . ' inválido. Use DDD + número, ex.: (11) 91234-5678.');
        }

        $this->mapos_model->edit('usuarios', ['telefone' => $tel['valor'], 'celular' => $cel['valor']], 'idUsuarios', $uid);

        $this->ok($this->perfil($user), 'Contato atualizado.');
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    /** Abre a OS só se estiver designada ao técnico (ou vinculada a projeto dele). */
    protected function osPermitida($osId, $uid)
    {
        $os = $osId ? $this->db->where('idOs', $osId)->get('os')->row() : null;
        if (! $os) {
            return null;
        }
        if ((int) $os->tecnico_responsavel === (int) $uid) {
            return $os;
        }
        // Leitura liberada quando a OS está num projeto que o técnico executa.
        if ($this->obras_model->osEmProjetoDoUsuario($osId, $uid)) {
            return $os;
        }

        return null;
    }

    /** CPF (11 dígitos) ou CNPJ (14) formatado; devolve como veio quando não bate. */
    protected function documento($doc)
    {
        $num = preg_replace('/\D/', '', (string) $doc);
        if (strlen($num) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $num);
        }
        if (strlen($num) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $num);
        }

        return (string) $doc;
    }

    protected function perfil($user)
    {
        $this->load->helper('menu_tecnico');
        $u = $user->usuario;
        $nome = trim((string) ($u->nome ?? ''));
        $colab = menu_tecnico_colaborador((int) $u->idUsuarios);

        return [
            'id' => (int) $u->idUsuarios,
            'nome' => $nome,
            'primeiroNome' => $nome !== '' ? explode(' ', $nome)[0] : '',
            'email' => $u->email ?? null,
            'telefone' => $u->telefone ?? null,
            'celular' => $u->celular ?? null,
            'fotoUrl' => $this->temFotoPerfil($u) ? 'tecnico/perfil/foto' : null,
            'cargo' => $colab->cargo ?? null,
            'colaboradorVinculado' => (bool) $colab,
            'iniciais' => menu_tecnico_iniciais($nome),
        ];
    }

    /** GET /api/v1/tecnico/foto — foto do perfil (do usuário ou do colaborador). */
    public function foto_get()
    {
        $user = $this->exigir('vTecnicoDashboard');
        $u = $user->usuario;

        $img = basename((string) ($u->url_image_user ?? ''));
        if ($img !== '' && is_file(FCPATH . 'assets/userImage/' . $img)) {
            $caminho = FCPATH . 'assets/userImage/' . $img;
            header('Content-Type: ' . (mime_content_type($caminho) ?: 'image/jpeg'));
            header('Content-Length: ' . filesize($caminho));
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, max-age=3600');
            readfile($caminho);
            exit;
        }

        $colab = $this->colaboradorLogado();
        if ($colab && ! empty($colab->foto_base64)) {
            $this->servirBase64($colab->foto_base64, $colab->foto_mime ?: 'image/jpeg', true);
        }

        $this->naoEncontrada('Sem foto de perfil.');
    }

    protected function temFotoPerfil($u)
    {
        $img = basename((string) ($u->url_image_user ?? ''));
        if ($img !== '' && is_file(FCPATH . 'assets/userImage/' . $img)) {
            return true;
        }

        return $this->colaboradorLogado() !== null
            && ! empty($this->colaboradorLogado()->foto_base64);
    }

    protected function equipes($uid)
    {
        $this->load->model('obra_equipe_model');
        if (! $this->obra_equipe_model->disponivel()) {
            return [];
        }

        return array_map(static function ($e) {
            return [
                'id' => (int) ($e->idEquipe ?? 0),
                'nome' => $e->nome ?? null,
                'encarregado' => (bool) ($e->encarregado ?? false),
            ];
        }, (array) $this->obra_equipe_model->getEquipesDoUsuario($uid));
    }

    protected function numerosMes($uid)
    {
        $ini = date('Y-m-01');
        $fim = date('Y-m-t');

        $osAtendidas = 0;
        if ($this->db->table_exists('os_checkin')) {
            $osAtendidas = (int) $this->db->select('COUNT(DISTINCT os_id) AS n', false)
                ->where('usuarios_id', $uid)
                ->where('data_entrada >=', $ini . ' 00:00:00')
                ->where('data_entrada <=', $fim . ' 23:59:59')
                ->get('os_checkin')->row()->n;
        }

        $this->load->model('projeto_acompanhamento_model');
        $rel = $this->projeto_acompanhamento_model->relatorio($ini, $fim, ['usuario_id' => $uid]);

        return [
            'mes' => date('m/Y'),
            'osAtendidas' => $osAtendidas,
            'pontosAvancados' => (int) ($rel['totais']['avancos'] ?? 0),
        ];
    }

    protected function projetoResumo($obra, $uid)
    {
        $pontos = $this->obra_pontos_model->disponivel()
            ? $this->db->where('obra_id', (int) $obra->idObra)->count_all_results('obra_pontos')
            : 0;

        return [
            'idObra' => (int) $obra->idObra,
            'codigo' => $obra->codigo ?? null,
            'nome' => $obra->nome ?? null,
            'nomeCliente' => $obra->nomeCliente ?? null,
            'status' => $obra->status ?? null,
            'percentual_concluido' => $obra->percentual_concluido ?? null,
            'endereco' => trim(implode(', ', array_filter([
                trim((string) ($obra->logradouro ?? '')),
                trim((string) ($obra->numero ?? '')),
                trim((string) ($obra->bairro ?? '')),
            ]))),
            'cidade' => $obra->cidade ?? null,
            'uf' => $obra->uf ?? null,
            'latitude' => $obra->latitude ?? null,
            'longitude' => $obra->longitude ?? null,
            'data_inicio' => $obra->data_inicio_prevista ?? null,
            'data_previsao' => $obra->data_fim_prevista ?? null,
            'qtdPontos' => (int) $pontos,
            'minha' => (int) ($obra->responsavel_id ?? 0) === (int) $uid,
        ];
    }

    protected function pontoResumo($p, $uid, array $statusLista)
    {
        $status = (string) ($p->status ?? '');
        $rotulo = isset($statusLista[$status]) ? $statusLista[$status]['rotulo'] : $status;

        return [
            'idPonto' => (int) $p->idPonto,
            'identificacao' => $p->identificacao ?? null,
            'tipo' => $p->tipo ?? null,
            'local' => $p->local ?? null,
            'status' => $status,
            'status_rotulo' => $rotulo,
            'aguardando' => $p->aguardando ?? null,
            'responsavel' => $p->responsavel_nome ?? null,
            'qtd_fotos' => (int) ($p->qtd_fotos ?? 0),
            'minha' => (int) ($p->responsavel_id ?? 0) === (int) $uid,
        ];
    }
}
