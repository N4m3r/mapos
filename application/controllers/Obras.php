<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Obras (Equipe de Obra) — gestão de projetos de construção.
 *
 * Contêiner de longa duração com cronograma físico (etapas/EAP), diário de
 * obra (RDO), medições e custos. A gestão de material fica em Obramaterial.
 * Ver docs/PROJETO-OBRAS.md.
 */
class Obras extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->model('obras_model');
        $this->data['menuObras'] = 'Obras';
    }

    public function index()
    {
        $this->painel();
    }

    /**
     * Painel de gestão de obras: KPIs + pendências que cruzam todas as obras
     * (medições a aprovar, obras em execução, custódia de material do cliente).
     */
    public function painel()
    {
        $this->precisa('vObras', 'Você não tem permissão para visualizar Projetos.');

        $this->data['kpis'] = $this->obras_model->kpis();
        $this->data['obras_execucao'] = $this->obras_model->getObrasPorStatus('em_execucao', 8);
        $this->data['medicoes_abertas'] = $this->obras_model->getMedicoesAbertas(10);
        $this->data['rdos_recentes'] = $this->obras_model->getRdosRecentes(8);
        $this->load->model('projeto_acompanhamento_model');
        $this->data['acomp'] = $this->projeto_acompanhamento_model->painel(date('Y-m-d'));
        $this->data['obra_menu'] = 'painel';
        $this->data['view'] = 'obras/painel';
        return $this->layout();
    }

    public function gerenciar()
    {
        $this->precisa('vObras', 'Você não tem permissão para visualizar Projetos.');
        $this->load->library('pagination');
        $this->data['obra_menu'] = 'obras';

        $filtros = [
            'pesquisa' => $this->input->get('pesquisa'),
            'status' => $this->input->get('status'),
        ];

        $this->data['configuration']['base_url'] = site_url('obras/gerenciar/');
        $this->data['configuration']['total_rows'] = $this->obras_model->count();
        $this->pagination->initialize($this->data['configuration']);

        $this->data['results'] = $this->obras_model->getObras(
            $this->data['configuration']['per_page'],
            $this->uri->segment(3),
            $filtros
        );
        $this->load->model('projeto_acompanhamento_model');
        $this->data['resumo'] = $this->projeto_acompanhamento_model->resumoLista(array_map(function ($r) {
            return (int) $r->idObra;
        }, $this->data['results'] ?: []));
        $this->data['contagem_status'] = $this->projeto_acompanhamento_model->contagemStatus();
        $this->data['view'] = 'obras/gerenciar';
        return $this->layout();
    }

    public function adicionar()
    {
        $this->precisa('cObras', 'Você não tem permissão para adicionar Projetos.');

        if ($this->input->post()) {
            $nome = trim((string) $this->input->post('nome'));
            if ($nome === '') {
                $this->session->set_flashdata('error', 'Informe o nome do projeto.');
            } else {
                $dadosNovo = $this->montarDados();
                $dadosNovo['status'] = 'planejamento';
                $id = $this->obras_model->add($dadosNovo);
                if ($id) {
                    log_info('Adicionou um projeto. ID: ' . $id);
                    $msg = 'Projeto cadastrado com sucesso.';
                    $etapasIn = (array) $this->input->post('etapas');
                    $pontosIn = (array) $this->input->post('pontos');
                    $tecnicosIn = (array) $this->input->post('tecnicos');
                    if ($etapasIn || $pontosIn || $tecnicosIn) {
                        $this->load->model('obra_planejamento_model');
                        $r = $this->obra_planejamento_model->montarProjetoInicial(
                            $id,
                            $etapasIn,
                            $pontosIn,
                            $tecnicosIn,
                            (int) $this->session->userdata('id_admin')
                        );
                        $msg = 'Projeto criado com ' . (int) $r['etapas'] . ' etapas e ' . (int) $r['pontos'] . ' pontos.';
                    }
                    $this->session->set_flashdata('success', $msg);
                    redirect('obras/visualizar/' . $id);
                }
                $this->session->set_flashdata('error', 'Ocorreu um erro ao salvar.');
            }
        }

        $this->load->model('obra_pontos_model');
        $this->carregarSelects();
        $this->data['view'] = 'obras/adicionar';
        return $this->layout();
    }

    /** Etapas padrao do tipo de projeto (assistente "Novo projeto"). GET ?tipo= */
    public function etapasPadraoTipo()
    {
        $this->precisa('cObras', 'Você não tem permissão para adicionar Projetos.');
        $this->load->model('obra_pontos_model');
        $tipo = (string) $this->input->get('tipo');
        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['etapas' => Obra_pontos_model::etapasPadrao($tipo)], JSON_UNESCAPED_UNICODE));
    }

    public function editar()
    {
        $id = (int) $this->uri->segment(3);
        $this->precisa('eObras', 'Você não tem permissão para editar Projetos.');
        if (! $id || ! ($obra = $this->obras_model->getObra($id))) {
            $this->session->set_flashdata('error', 'Projeto não encontrado.');
            redirect('obras');
        }

        if ($this->input->post()) {
            $dados = $this->montarDados(false);
            $this->obras_model->edit($id, $dados);
            log_info('Editou o projeto. ID: ' . $id);

            // Fecha a conversa de e-mail do RDO (se houver uma em andamento) quando
            // o projeto é marcado como concluído/entregue. Best-effort.
            if (in_array($dados['status'], ['concluida', 'entregue'], true)) {
                try {
                    $this->load->library('notificador');
                    $this->notificador->emailProjetoFinalizado($id);
                } catch (\Throwable $e) {
                    log_info('Falha ao disparar e-mail de finalização do projeto ' . $id . ': ' . $e->getMessage());
                }
            }

            $this->session->set_flashdata('success', 'Projeto atualizado com sucesso.');
            redirect('obras/visualizar/' . $id);
        }

        $this->carregarSelects();
        $this->data['result'] = $obra;
        $this->data['view'] = 'obras/editar';
        return $this->layout();
    }

    public function visualizar()
    {
        $id = (int) $this->uri->segment(3);
        $this->precisa('vObras', 'Você não tem permissão para visualizar Projetos.');
        if (! $id || ! ($obra = $this->obras_model->getObra($id))) {
            $this->session->set_flashdata('error', 'Projeto não encontrado.');
            redirect('obras');
        }

        $this->load->model('obra_material_model');
        $this->data['obra'] = $obra;
        $this->data['etapas'] = $this->obras_model->getEtapas($id);
        $this->data['rdos'] = $this->obras_model->getRdos($id);
        $this->data['medicoes'] = $this->obras_model->getMedicoes($id);
        $this->data['equipes'] = $this->obras_model->getEquipesAlocadas($id);
        $this->data['saldos'] = $this->obra_material_model->getSaldos($id);
        $this->data['recebimentos'] = $this->obra_material_model->getRecebimentos($id);
        $this->data['entregas'] = $this->obra_material_model->getEntregas($id);
        $this->data['compras'] = $this->obra_material_model->getResumoCompras($id);
        $this->data['custo'] = $this->obras_model->getResumoCusto($id);
        $this->data['apontamentos'] = $this->obras_model->getApontamentos($id);
        $this->data['apontamento_total'] = $this->obras_model->getTotalApontamento($id);
        $this->data['os_vinculadas'] = $this->obras_model->getOsVinculadas($id);
        $this->data['os_disponiveis'] = $this->obras_model->getOsDisponiveis();
        $this->data['os_servicos'] = $this->obras_model->getServicosDasOs($id);
        $this->data['os_produtos'] = $this->obras_model->getProdutosDasOs($id);

        // Atividade vinda de OS vinculada (check-in/checkout, mão de obra e consumo com
        // os_id) e consumo do projeto com a origem (RDO ou OS) - ADR-012/ADR-015 fase 4.
        $this->load->model('projeto_acompanhamento_model');
        $this->data['atividades_os'] = $this->projeto_acompanhamento_model->atividadesProjeto($id);
        $this->data['consumo_projeto'] = $this->projeto_acompanhamento_model->consumoProjeto($id);

        // Pontos do projeto (câmeras, pontos de rede, leitores...)
        $this->load->model('obra_pontos_model');
        $ptTipos = Obra_pontos_model::tipos();
        $ptStatus = Obra_pontos_model::statusLista();
        $filtros = [];
        $ft = (string) $this->input->get('pt_tipo');
        $fs = (string) $this->input->get('pt_status');
        if ($ft !== '' && isset($ptTipos[$ft])) {
            $filtros['tipo'] = $ft;
        }
        if ($fs !== '' && isset($ptStatus[$fs])) {
            $filtros['status'] = $fs;
        }
        $fa = (string) $this->input->get('pt_aguardando');
        if ($fa !== '' && isset($ptStatus[$fa])) {
            $filtros['aguardando'] = $fa;
        }
        $fr = (string) $this->input->get('pt_resp');
        if ($fr === 'equipe') {
            $filtros['responsavel_id'] = 'equipe';
        } elseif ($fr !== '' && ctype_digit($fr) && (int) $fr > 0) {
            $filtros['responsavel_id'] = (int) $fr;
        }
        $this->data['pontos'] = $this->obra_pontos_model->getPontos($id, $filtros);
        $this->data['pontos_todos'] = $this->obra_pontos_model->getPontos($id);
        $this->data['pontos_resumo'] = Obra_pontos_model::resumo($this->data['pontos_todos']);
        $this->data['pontos_tipos'] = $ptTipos;
        $this->data['pontos_status'] = $ptStatus;

        // "Ver pontos pendentes" da sugestão de conclusão de etapa (ADR-015 fase 4):
        // filtra, sem precisar de nada no Obra_pontos_model, os pontos ligados às OS da etapa.
        $feEtapa = (int) $this->input->get('pt_etapa');
        $etapaFiltroNome = '';
        if ($feEtapa > 0) {
            foreach ($this->data['etapas'] as $et) {
                if ((int) $et->idEtapa === $feEtapa) {
                    $etapaFiltroNome = (string) $et->nome;
                }
            }
        }
        if ($feEtapa > 0 && $etapaFiltroNome !== '' && $this->db->table_exists('obra_os') && $this->db->table_exists('obra_ponto_os')) {
            $this->db->select('obra_ponto_os.ponto_id');
            $this->db->from('obra_ponto_os');
            $this->db->join('obra_os', 'obra_os.os_id = obra_ponto_os.os_id');
            $this->db->where('obra_os.etapa_id', $feEtapa);
            $idsEtapa = [];
            foreach ($this->db->get()->result() as $r) {
                $idsEtapa[(int) $r->ponto_id] = true;
            }
            $this->data['pontos'] = array_values(array_filter($this->data['pontos'], function ($p) use ($idsEtapa) {
                return isset($idsEtapa[(int) $p->idPonto]) && $p->status !== 'testado';
            }));
        } elseif ($feEtapa > 0) {
            $etapaFiltroNome = '';
        }
        $this->data['pontos_filtro_etapa'] = $etapaFiltroNome !== '' ? ['id' => $feEtapa, 'nome' => $etapaFiltroNome] : null;
        $this->data['pontos_filtro'] = [
            'tipo' => $filtros['tipo'] ?? '',
            'status' => $filtros['status'] ?? '',
            'aguardando' => $filtros['aguardando'] ?? '',
            'resp' => isset($filtros['responsavel_id']) ? (string) $filtros['responsavel_id'] : '',
        ];
        $this->data['executores'] = $this->obra_pontos_model->executores($id);
        $this->data['fotos_pontos'] = $this->obra_pontos_model->getFotosObra($id);

        // Executores do projeto + controle de acesso à execução
        $this->data['usuarios_projeto'] = $this->obras_model->getUsuariosProjeto($id);
        $idsVinculados = array_map(function ($u) {
            return (int) $u->usuario_id;
        }, $this->data['usuarios_projeto']);
        $this->db->select('idUsuarios, nome')->where('situacao', 1);
        if ($idsVinculados) {
            $this->db->where_not_in('idUsuarios', $idsVinculados);
        }
        $this->data['usuarios_disponiveis'] = $this->db->order_by('nome', 'ASC')->get('usuarios')->result();
        $uid = $this->session->userdata('id_admin');
        $this->data['pode_executar'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'cObraRdo')
            || $this->obras_model->usuarioTemAcesso($id, $uid);

        // Planejamento por etapa (itens previstos e OS geradas) - ADR-015 fase 2.
        $this->data['plan_ativo'] = false;
        $this->data['itensPorEtapa'] = [];
        $this->data['totaisPrevistos'] = [];
        $this->data['osPorEtapa'] = [];
        $this->data['pontosSemOs'] = [];
        $this->data['tecnicos_os'] = [];
        $this->data['equipes_os'] = [];
        if ($this->planejamentoDisponivel()) {
            $this->load->model('obra_planejamento_model');
            $this->data['plan_ativo'] = true;
            $this->data['itensPorEtapa'] = $this->obra_planejamento_model->getItensObra($id);
            $this->data['totaisPrevistos'] = $this->obra_planejamento_model->totaisPrevistosPorEtapa($id);
            $this->data['osPorEtapa'] = $this->obra_planejamento_model->getOsPorEtapa($id);
            $semOs = [];
            foreach ((array) $this->obra_planejamento_model->pontosSemOs($id) as $p) {
                $semOs[(int) (is_object($p) ? $p->idPonto : $p['idPonto'])] = true;
            }
            $this->data['pontosSemOs'] = $semOs;
            $this->data['tecnicos_os'] = $this->db->select('idUsuarios, nome')->where('situacao', 1)->order_by('nome', 'ASC')->get('usuarios')->result();
            if ($this->db->table_exists('equipes')) {
                $this->load->model('obra_equipe_model');
                $this->data['equipes_os'] = $this->obra_equipe_model->getEquipesSelect();
            }
        }

        $this->data['etapasSugestoesConcluir'] = $this->etapasSugeridasParaConcluir($id);

        $this->montarGuiaProjeto($obra);

        $this->data['view'] = 'obras/visualizar';
        return $this->layout();
    }

    /**
     * Etapas com todas as OS finalizadas mas pontos pendentes, por idEtapa (ADR-015 fase 4).
     * Erro na consulta nao derruba a pagina do projeto.
     */
    private function etapasSugeridasParaConcluir(int $obraId): array
    {
        if (! $obraId) {
            return [];
        }
        try {
            $this->load->library('projeto_sync');
            $lista = $this->projeto_sync->etapasSugeridasParaConcluir($obraId);
        } catch (\Throwable $e) {
            log_message('error', 'etapasSugeridasParaConcluir falhou: ' . $e->getMessage());

            return [];
        }
        $out = [];
        foreach ((array) $lista as $s) {
            $s = (array) $s;
            if (! isset($s['etapa_id'])) {
                continue;
            }
            $out[(int) $s['etapa_id']] = [
                'etapa_id' => (int) $s['etapa_id'],
                'etapa_nome' => (string) ($s['etapa_nome'] ?? ''),
                'os_finalizadas' => (int) ($s['os_finalizadas'] ?? 0),
                'pontos_pendentes' => (int) ($s['pontos_pendentes'] ?? 0),
            ];
        }

        return $out;
    }

    /** Estado do projeto -> trilho, proximo passo e prazo (ADR-016). Usa so dados ja carregados em visualizar. */
    private function montarGuiaProjeto($obra)
    {
        require_once APPPATH . 'libraries/Projeto_guia.php';
        $d = $this->data;
        $semOs = 0;
        foreach ((array) $d['etapas'] as $e) {
            if (empty($d['osPorEtapa'][$e->idEtapa])) {
                $semOs++;
            }
        }
        $statusOs = array_map(function ($o) {
            return $o->status;
        }, (array) $d['os_vinculadas']);
        $resumo = $d['pontos_resumo'];
        $encerrada = in_array($obra->status, ['concluida', 'entregue', 'cancelada'], true);
        $estado = [
            'status' => (string) $obra->status,
            'etapas' => count((array) $d['etapas']),
            'etapas_sem_os' => $semOs,
            'plan_ativo' => ! empty($d['plan_ativo']),
            'exige_pontos' => Projeto_guia::tipoExigePontos($obra->tipo_obra) && $this->db->table_exists('obra_pontos'),
            'pontos' => (int) $resumo['total'],
            'pontos_testados' => (int) ($resumo['por_status']['testado'] ?? 0),
            'tem_equipe' => ! empty($d['usuarios_projeto']) || ! empty($d['equipes']) || ! empty($obra->responsavel_id),
            'os_abertas' => Projeto_guia::contarOsAbertas($statusOs),
            'pode_executar' => ! empty($d['pode_executar']),
            'pode_editar' => $this->permission->checkPermission($this->session->userdata('permissao'), 'eObras'),
        ];
        $this->data['guia'] = Projeto_guia::proximoPasso($estado);
        $this->data['guia_estado'] = $estado;
        $this->data['prazo'] = Projeto_guia::prazo($obra->data_fim_prevista, date('Y-m-d'), $encerrada);
    }

    /* ==================== Usuários do projeto ==================== */

    public function vincularUsuario()
    {
        $this->precisa('eObras', 'Você não tem permissão para gerenciar o acesso ao projeto.');
        $obra_id = (int) $this->input->post('obra_id');
        $usuario_id = (int) $this->input->post('usuario_id');
        if (! $obra_id || ! $usuario_id) {
            $this->session->set_flashdata('error', 'Selecione um usuário.');
            redirect('obras/visualizar/' . $obra_id . '#equipe');
        }
        if ($this->obras_model->vincularUsuario($obra_id, $usuario_id)) {
            log_info('Vinculou o usuário ' . $usuario_id . ' ao projeto ' . $obra_id);
            $this->session->set_flashdata('success', 'Usuário vinculado ao projeto.');
        } else {
            $this->session->set_flashdata('error', 'Usuário já vinculado ou inválido.');
        }
        redirect('obras/visualizar/' . $obra_id . '#equipe');
    }

    public function desvincularUsuario()
    {
        $this->precisa('eObras', 'Você não tem permissão para gerenciar o acesso ao projeto.');
        $obra_id = (int) $this->input->post('obra_id');
        $this->obras_model->desvincularUsuario((int) $this->input->post('idObraUsuario'), $obra_id);
        log_info('Desvinculou um usuário do projeto ' . $obra_id);
        $this->session->set_flashdata('success', 'Acesso removido.');
        redirect('obras/visualizar/' . $obra_id . '#equipe');
    }

    /**
     * Registro rápido (one-click) do que foi executado no projeto.
     * Reaproveita o RDO: grava só "o que foi feito" + fotos, com data e
     * responsável automáticos. Só executores com acesso podem registrar.
     */
    public function registrarExecucao()
    {
        $obra_id = (int) $this->input->post('obra_id');
        $obra = $this->obras_model->getObra($obra_id);
        if (! $obra) {
            $this->session->set_flashdata('error', 'Projeto não encontrado.');
            redirect('obras');
        }
        $uid = $this->session->userdata('id_admin');
        $ehGestor = $this->permission->checkPermission($this->session->userdata('permissao'), 'cObraRdo');
        if (! $ehGestor && ! $this->obras_model->usuarioTemAcesso($obra_id, $uid)) {
            $this->session->set_flashdata('error', 'Você não tem acesso para registrar execução neste projeto.');
            redirect('obras/visualizar/' . $obra_id);
        }

        $atividades = trim((string) $this->input->post('atividades'));
        if ($atividades === '') {
            $this->session->set_flashdata('error', 'Descreva o que foi realizado.');
            redirect('obras/visualizar/' . $obra_id . '#rdo');
        }

        $rdoId = $this->obras_model->addRdo([
            'obra_id' => $obra_id,
            'numero' => $this->obras_model->proximoNumeroRdo($obra_id),
            'data' => date('Y-m-d'),
            'condicao' => 'praticavel',
            'responsavel_id' => $uid,
            'atividades' => $atividades,
            'status' => 'finalizado',
            'data_registro' => date('Y-m-d H:i:s'),
        ]);

        foreach ((array) $this->input->post('fotos', false) as $b64) {
            $bin = $this->base64ParaBlob($b64);
            if ($bin !== null) {
                $this->obras_model->addRdoFoto($rdoId, $bin);
            }
        }

        log_info('Registro rápido de execução na obra ' . $obra_id);

        // Envia o RDO ao(s) grupo(s)/cliente/e-mail do gatilho "rdo_registrado". Best-effort.
        try {
            $this->load->library('notificador');
            $this->notificador->whatsappRdo($rdoId);
            $this->notificador->emailRdo($rdoId);
        } catch (\Throwable $e) {
            log_info('Falha ao disparar notificação de RDO (RDO #' . $rdoId . '): ' . $e->getMessage());
        }

        $this->session->set_flashdata('success', 'Execução registrada. Obrigado!');
        redirect('obras/visualizar/' . $obra_id . '#rdo');
    }

    /* ========================= OS vinculadas ===================== */

    /** Vincula uma OS existente ao projeto (representa o que será executado). */
    public function vincularOs()
    {
        $this->precisa('eObras', 'Você não tem permissão para vincular OS ao projeto.');
        $obra_id = (int) $this->input->post('obra_id');
        $os_id = (int) $this->input->post('os_id');
        if (! $obra_id || ! $os_id) {
            $this->session->set_flashdata('error', 'Selecione uma Ordem de Serviço.');
            redirect('obras/visualizar/' . $obra_id . '#os');
        }
        if ($this->obras_model->vincularOs($obra_id, $os_id, $this->input->post('etapa_id'))) {
            log_info('Vinculou a OS ' . $os_id . ' ao projeto ' . $obra_id);
            $this->session->set_flashdata('success', 'OS vinculada ao projeto.');
        } else {
            $this->session->set_flashdata('error', 'Não foi possível vincular (a OS já está em um projeto ou a etapa não é deste projeto).');
        }
        redirect('obras/visualizar/' . $obra_id . '#os');
    }

    public function desvincularOs()
    {
        $this->precisa('eObras', 'Você não tem permissão para desvincular OS.');
        $obra_id = (int) $this->input->post('obra_id');
        $this->obras_model->desvincularOs((int) $this->input->post('idVinculo'), $obra_id);
        log_info('Desvinculou uma OS do projeto ' . $obra_id);
        $this->session->set_flashdata('success', 'OS desvinculada do projeto.');
        redirect('obras/visualizar/' . $obra_id . '#os');
    }

    /* ======================= Apontamento / efetivo ======================= */

    /** Consolida o efetivo do dia a partir do ponto facial/GPS. */
    public function consolidarPonto()
    {
        $this->precisa('cObraRdo', 'Você não tem permissão para registrar efetivo.');
        $obra_id = (int) $this->input->post('obra_id');
        $data = $this->dataOuNull($this->input->post('data')) ?: date('Y-m-d');
        $n = $this->obras_model->consolidarPonto($obra_id, $data);
        if ($n > 0) {
            $this->session->set_flashdata('success', $n . ' colaborador(es) consolidado(s) do ponto em ' . date('d/m/Y', strtotime($data)) . '.');
        } else {
            $this->session->set_flashdata('error', 'Nenhuma batida de ponto vinculada a esta obra nesse dia.');
        }
        redirect('obras/visualizar/' . $obra_id . '#maodeobra');
    }

    /** Apontamento manual do encarregado (sem exigir ponto facial). */
    public function salvarApontamento()
    {
        $this->precisa('cObraRdo', 'Você não tem permissão para registrar efetivo.');
        $obra_id = (int) $this->input->post('obra_id');
        $nome = trim((string) $this->input->post('nome'));
        if ($nome === '') {
            $this->session->set_flashdata('error', 'Informe o nome do colaborador.');
            redirect('obras/visualizar/' . $obra_id . '#maodeobra');
        }
        $diaria = $this->input->post('diaria') ? 1 : 0;
        $this->obras_model->addApontamento([
            'obra_id' => $obra_id,
            'colaborador_id' => (int) $this->input->post('colaborador_id') ?: null,
            'nome' => $nome,
            'funcao' => $this->input->post('funcao'),
            'data' => $this->dataOuNull($this->input->post('data')) ?: date('Y-m-d'),
            'horas' => (float) $this->input->post('horas'),
            'diaria' => $diaria,
            'origem' => 'manual',
            'valor' => (float) $this->input->post('valor'),
        ]);
        $this->session->set_flashdata('success', 'Apontamento registrado.');
        redirect('obras/visualizar/' . $obra_id . '#maodeobra');
    }

    public function excluirApontamento()
    {
        $this->precisa('cObraRdo', 'Sem permissão.');
        $obra_id = (int) $this->input->post('obra_id');
        if ($this->obras_model->deleteApontamento((int) $this->input->post('idApontamento'), $obra_id)) {
            $this->session->set_flashdata('success', 'Apontamento removido.');
        } else {
            $this->session->set_flashdata('error', 'Apontamento não encontrado neste projeto.');
        }
        redirect('obras/visualizar/' . $obra_id . '#maodeobra');
    }

    public function excluir()
    {
        $this->precisa('dObras', 'Você não tem permissão para excluir Projetos.');
        $id = (int) $this->input->post('idObra');
        if (! $id) {
            redirect('obras');
        }
        if ($this->obras_model->temMedicaoFechada($id)) {
            $this->session->set_flashdata('error', 'Projeto com medição aprovada ou faturada não pode ser excluído. Cancele ou conclua o projeto.');
            redirect('obras/visualizar/' . $id);
        }
        if ($this->obras_model->delete($id)) {
            log_info('Removeu o projeto. ID: ' . $id);
            $this->session->set_flashdata('success', 'Projeto excluído com sucesso.');
        } else {
            $this->session->set_flashdata('error', 'Não foi possível excluir o projeto.');
        }
        redirect('obras');
    }

    /** Limita um numero do POST ao intervalo [min, max]. */
    private function numeroEntre($valor, $min, $max = null)
    {
        $n = max($min, (float) $valor);
        return $max === null ? $n : min($max, $n);
    }

    /* ============================ Etapas ============================ */

    public function salvarEtapa()
    {
        $this->precisa('eObraCronograma', 'Você não tem permissão para editar o cronograma.');
        $obra_id = (int) $this->input->post('obra_id');
        $idEtapa = (int) $this->input->post('idEtapa');
        $dados = [
            'obra_id' => $obra_id,
            'codigo_eap' => $this->input->post('codigo_eap'),
            'nome' => trim((string) $this->input->post('nome')),
            'peso_percentual' => $this->numeroEntre($this->input->post('peso_percentual'), 0, 100),
            'valor_previsto' => $this->numeroEntre($this->input->post('valor_previsto'), 0),
            'percentual_concluido' => $this->numeroEntre($this->input->post('percentual_concluido'), 0, 100),
            'data_inicio_prevista' => $this->dataOuNull($this->input->post('data_inicio_prevista')),
            'data_fim_prevista' => $this->dataOuNull($this->input->post('data_fim_prevista')),
            'status' => $this->input->post('status') ?: 'pendente',
        ];
        if ($dados['nome'] === '') {
            $this->session->set_flashdata('error', 'Informe o nome da etapa.');
            redirect('obras/visualizar/' . $obra_id);
        }
        if (! $this->obras_model->getObra($obra_id)) {
            $this->session->set_flashdata('error', 'Projeto não encontrado.');
            redirect('obras');
        }
        if ($idEtapa) {
            if (! $this->etapaDaObra($obra_id, $idEtapa)) {
                $this->session->set_flashdata('error', 'Etapa não encontrada neste projeto.');
                redirect('obras/visualizar/' . $obra_id . '#cronograma');
            }
            $this->obras_model->editEtapa($idEtapa, $dados, $obra_id);
        } else {
            $dados['ordem'] = count($this->obras_model->getEtapas($obra_id));
            $this->obras_model->addEtapa($dados);
        }
        $this->obras_model->recalcularProgresso($obra_id);
        $this->session->set_flashdata('success', 'Etapa salva.');
        redirect('obras/visualizar/' . $obra_id . '#cronograma');
    }

    public function excluirEtapa()
    {
        $this->precisa('eObraCronograma', 'Sem permissão.');
        $obra_id = (int) $this->input->post('obra_id');
        if (! $this->obras_model->deleteEtapa((int) $this->input->post('idEtapa'), $obra_id)) {
            $this->session->set_flashdata('error', 'Etapa não encontrada neste projeto.');
            redirect('obras/visualizar/' . $obra_id . '#cronograma');
        }
        $this->obras_model->recalcularProgresso($obra_id);
        $this->session->set_flashdata('success', 'Etapa removida.');
        redirect('obras/visualizar/' . $obra_id . '#cronograma');
    }

    /* ============ Planejamento: itens previstos e geração de OS ============ */

    private function planejamentoDisponivel()
    {
        return file_exists(APPPATH . 'models/Obra_planejamento_model.php')
            && $this->db->table_exists('obra_etapa_itens');
    }

    /** Valida permissão, projeto e model; devolve o id do projeto (ou redireciona). */
    private function planejamentoInicio()
    {
        $this->precisa('eObras', 'Você não tem permissão para editar Projetos.');
        $obra_id = (int) $this->input->post('obra_id');
        if (! $obra_id || ! $this->obras_model->getObra($obra_id)) {
            $this->session->set_flashdata('error', 'Projeto não encontrado.');
            redirect('obras');
        }
        if (! $this->planejamentoDisponivel()) {
            $this->session->set_flashdata('error', 'O planejamento por etapa ainda não está disponível neste ambiente.');
            redirect('obras/visualizar/' . $obra_id . '#cronograma');
        }
        $this->load->model('obra_planejamento_model');
        return $obra_id;
    }

    private function etapaDaObra($obra_id, $etapa_id)
    {
        foreach ($this->obras_model->getEtapas($obra_id) as $e) {
            if ((int) $e->idEtapa === (int) $etapa_id) {
                return $e;
            }
        }
        return null;
    }

    /** Opções comuns de geração de OS a partir do POST. */
    private function opcoesGerarOs()
    {
        $tv = (string) $this->input->post('tipo_vinculo');
        $op = [
            'usuario_id' => (int) $this->session->userdata('id_admin'),
            'tipo_vinculo' => in_array($tv, ['execucao', 'complementar', 'garantia'], true) ? $tv : 'execucao',
            'fatura_no_projeto' => $this->input->post('fatura_no_projeto') ? 1 : 0,
        ];
        $tec = (int) $this->input->post('tecnico_id');
        if ($tec > 0) {
            $op['tecnico_id'] = $tec;
        }
        $eq = (int) $this->input->post('equipe_id');
        if ($eq > 0) {
            $op['equipe_id'] = $eq;
        }
        $dt = $this->dataOuNull($this->input->post('data_agendamento'));
        if ($dt) {
            $op['data_agendamento'] = $dt;
        }
        return $op;
    }

    /** O flash é injetado em JS (swal): sem aspas, barras nem tags. */
    private function textoFlash($t)
    {
        return preg_replace('/[<>"\'\\\\\r\n]/u', '', (string) $t);
    }

    private function linkOs($idOs)
    {
        return 'OS #' . (int) $idOs;
    }

    /** Autocomplete de produto/serviço para os itens previstos. */
    public function autoCompleteItem()
    {
        $this->precisa('vObras', 'Sem permissão.');
        $termo = trim((string) $this->input->get('term'));
        $tipo = $this->input->get('tipo') === 'servico' ? 'servico' : 'produto';
        $out = [];
        if ($termo !== '') {
            if ($tipo === 'servico') {
                $rows = $this->db->select('idServicos, nome, preco')->like('nome', $termo)->limit(25)->get('servicos')->result();
                foreach ($rows as $r) {
                    $out[] = ['label' => $r->nome . ' | R$ ' . $r->preco, 'id' => (int) $r->idServicos, 'preco' => $r->preco];
                }
            } else {
                $rows = $this->db->select('idProdutos, descricao, precoVenda')->group_start()->like('descricao', $termo)->or_like('codDeBarra', $termo)->group_end()->limit(25)->get('produtos')->result();
                foreach ($rows as $r) {
                    $out[] = ['label' => $r->descricao . ' | R$ ' . $r->precoVenda, 'id' => (int) $r->idProdutos, 'preco' => $r->precoVenda];
                }
            }
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode($out));
    }

    /** Previa da conversao de OS em projeto (JSON) para o modal da OS. */
    public function previaConversaoOs()
    {
        $this->precisa('cObras', 'Você não tem permissão para adicionar Projetos.');
        $this->load->model('obra_pontos_model');
        $this->load->model('obra_planejamento_model');
        $r = $this->obra_planejamento_model->previaConversao((int) $this->input->get('os_id'));
        if ($r['ok']) {
            $r['tipos'] = [];
            foreach (Obra_pontos_model::tipos() as $k => $t) {
                $r['tipos'][] = ['id' => $k, 'rotulo' => $t['rotulo']];
            }
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode($r));
    }

    /** Converte uma OS aprovada em projeto (ADR-015 fase 3). */
    public function converterOs()
    {
        $this->precisa('cObras', 'Você não tem permissão para adicionar Projetos.');
        $osId = (int) $this->input->post('os_id');
        $voltar = 'os/visualizar/' . $osId;
        if (! $osId || ! $this->input->post()) {
            $this->session->set_flashdata('error', 'OS não informada.');
            redirect('os');
        }
        $this->load->model('obra_pontos_model');
        $this->load->model('obra_planejamento_model');

        $op = [
            'usuario_id' => (int) $this->session->userdata('id_admin'),
            'nome' => $this->input->post('nome'),
            'tipo_obra' => $this->input->post('tipo_obra'),
            'data_inicio_prevista' => $this->dataOuNull($this->input->post('data_inicio_prevista')),
            'data_fim_prevista' => $this->dataOuNull($this->input->post('data_fim_prevista')),
            'etapas' => $this->input->post('etapas') === 'padrao' ? 'padrao' : 'servicos',
            'gerar_pontos' => (bool) $this->input->post('gerar_pontos'),
        ];
        $porProduto = [];
        foreach ((array) $this->input->post('pontos_por_produto') as $pid => $tipo) {
            if ((int) $pid > 0) {
                $porProduto[(int) $pid] = (string) $tipo;
            }
        }
        if ($porProduto) {
            $op['pontos_por_produto'] = $porProduto;
        }

        $r = $this->obra_planejamento_model->converterOsEmProjeto($osId, $op);
        if (empty($r['ok'])) {
            $this->session->set_flashdata('error', 'Não foi possível converter a OS: ' . $this->textoFlash($r['erro'] ?? 'erro desconhecido'));
            redirect($voltar);
        }
        $x = $r['resumo'];
        log_info('Converteu a OS ' . $osId . ' no projeto ' . (int) $r['idObra']);
        $this->session->set_flashdata('success', 'Projeto criado a partir da OS #' . $osId . ': ' . (int) $x['etapas'] . ' etapas, ' . (int) $x['itens'] . ' itens previstos e ' . (int) $x['pontos'] . ' pontos.');
        redirect('obras/visualizar/' . (int) $r['idObra']);
    }

    public function salvarItemEtapa()
    {
        $obra_id = $this->planejamentoInicio();
        $etapa_id = (int) $this->input->post('etapa_id');
        $tipo = $this->input->post('tipo') === 'servico' ? 'servico' : 'produto';
        $ref = (int) $this->input->post('referencia_id');
        $qtd = (float) str_replace(',', '.', (string) $this->input->post('quantidade'));
        $precoPost = trim((string) $this->input->post('preco'));
        $preco = $precoPost === '' ? null : (float) str_replace(',', '.', $precoPost);
        if (! $this->etapaDaObra($obra_id, $etapa_id)) {
            $this->session->set_flashdata('error', 'Etapa não encontrada neste projeto.');
        } elseif ($ref <= 0 || $qtd <= 0) {
            $this->session->set_flashdata('error', 'Escolha o item na busca e informe a quantidade.');
        } elseif ($this->obra_planejamento_model->addItem($obra_id, $etapa_id, $tipo, $ref, $qtd, $preco) === false) {
            $this->session->set_flashdata('error', 'Não foi possível adicionar o item.');
        } else {
            log_info('Adicionou item previsto na etapa ' . $etapa_id . ' da obra ' . $obra_id);
            $this->session->set_flashdata('success', 'Item previsto adicionado.');
        }
        redirect('obras/visualizar/' . $obra_id . '#cronograma');
    }

    public function excluirItemEtapa()
    {
        $obra_id = $this->planejamentoInicio();
        if ($this->obra_planejamento_model->deleteItem((int) $this->input->post('idItem'), $obra_id)) {
            $this->session->set_flashdata('success', 'Item removido.');
        } else {
            $this->session->set_flashdata('error', 'Item não encontrado neste projeto.');
        }
        redirect('obras/visualizar/' . $obra_id . '#cronograma');
    }

    public function gerarOsEtapa()
    {
        $obra_id = $this->planejamentoInicio();
        $etapa_id = (int) $this->input->post('etapa_id');
        if (! $this->etapaDaObra($obra_id, $etapa_id)) {
            $this->session->set_flashdata('error', 'Etapa não encontrada neste projeto.');
            redirect('obras/visualizar/' . $obra_id . '#cronograma');
        }
        $op = $this->opcoesGerarOs();
        $pontos = $this->pontosValidos($obra_id, (array) $this->input->post('pontos'));
        if ($pontos) {
            $op['pontos'] = $pontos;
        }
        $r = $this->obra_planejamento_model->gerarOsDaEtapa($etapa_id, $op);
        if (! empty($r['ok'])) {
            log_info('Gerou OS ' . (int) $r['idOs'] . ' da etapa ' . $etapa_id . ' (obra ' . $obra_id . ')');
            $this->session->set_flashdata('success', 'OS criada: ' . $this->linkOs($r['idOs']) . '.');
        } else {
            $this->session->set_flashdata('error', 'Não foi possível gerar a OS: ' . $this->textoFlash($r['erro'] ?? 'erro desconhecido'));
        }
        redirect('obras/visualizar/' . $obra_id . '#cronograma');
    }

    public function gerarOsLote()
    {
        $obra_id = $this->planejamentoInicio();
        $ids = [];
        // Carrega as etapas do projeto uma vez e indexa por id
        $etapasDaObra = [];
        foreach ($this->obras_model->getEtapas($obra_id) as $e) {
            $etapasDaObra[(int) $e->idEtapa] = true;
        }
        foreach ((array) $this->input->post('etapas') as $eid) {
            if ((int) $eid > 0 && isset($etapasDaObra[(int) $eid])) {
                $ids[] = (int) $eid;
            }
        }
        $op = $this->opcoesGerarOs();
        if ($this->input->post('forcar')) {
            $op['forcar'] = true;
        }
        $res = $this->obra_planejamento_model->gerarOsEmLote($obra_id, array_values(array_unique($ids)), $op);
        $criadas = [];
        $puladas = [];
        foreach ((array) $res as $r) {
            if (! empty($r['ok'])) {
                $criadas[] = $this->linkOs($r['idOs']);
            } else {
                $puladas[] = '#' . (int) $r['etapa_id'] . ': ' . $this->textoFlash($r['erro'] ?? 'motivo não informado');
            }
        }
        $msg = count($criadas) . ' OS criadas' . ($criadas ? ' (' . implode(', ', $criadas) . ')' : '')
            . ', ' . count($puladas) . ' etapas puladas' . ($puladas ? ' (' . implode('; ', $puladas) . ')' : '') . '.';
        log_info('Gerou OS em lote na obra ' . $obra_id . ': ' . count($criadas) . ' criadas');
        $this->session->set_flashdata($criadas ? 'success' : 'error', $msg);
        redirect('obras/visualizar/' . $obra_id . '#cronograma');
    }

    public function gerarOsPontos()
    {
        $obra_id = $this->planejamentoInicio();
        $etapa_id = (int) $this->input->post('etapa_id');
        $pontos = $this->pontosValidos($obra_id, (array) $this->input->post('pontos'));
        if (! $this->etapaDaObra($obra_id, $etapa_id)) {
            $this->session->set_flashdata('error', 'Escolha a etapa da OS.');
        } elseif (! $pontos) {
            $this->session->set_flashdata('error', 'Marque ao menos um ponto do projeto.');
        } else {
            $op = $this->opcoesGerarOs();
            $op['pontos'] = $pontos;
            $r = $this->obra_planejamento_model->gerarOsDaEtapa($etapa_id, $op);
            if (! empty($r['ok'])) {
                log_info('Gerou OS ' . (int) $r['idOs'] . ' para ' . count($pontos) . ' pontos (obra ' . $obra_id . ')');
                $this->session->set_flashdata('success', 'OS criada com ' . count($pontos) . ' ponto(s): ' . $this->linkOs($r['idOs']) . '.');
            } else {
                $this->session->set_flashdata('error', 'Não foi possível gerar a OS: ' . $this->textoFlash($r['erro'] ?? 'erro desconhecido'));
            }
        }
        $this->voltarPontos($obra_id);
    }

    /** Filtra os ids para os pontos que realmente pertencem ao projeto. */
    private function pontosValidos($obra_id, array $ids)
    {
        $ids = array_filter(array_map('intval', $ids), function ($v) {
            return $v > 0;
        });
        if (! $ids) {
            return [];
        }
        $this->load->model('obra_pontos_model');
        $ok = [];
        foreach ($this->obra_pontos_model->getPontos($obra_id) as $p) {
            $ok[(int) $p->idPonto] = true;
        }
        return array_values(array_unique(array_filter($ids, function ($v) use ($ok) {
            return isset($ok[$v]);
        })));
    }

    /* ============================ Pontos ============================ */

    /** Corta o texto no limite da coluna; vazio vira null. */
    private function textoPonto($campo, $max)
    {
        $v = trim((string) $this->input->post($campo));
        if ($v === '') {
            return null;
        }
        return mb_substr($v, 0, $max);
    }

    private function voltarPontos($obra_id)
    {
        redirect('obras/visualizar/' . (int) $obra_id . '#pontos');
    }

    public function salvarPonto()
    {
        $this->precisa('eObras', 'Você não tem permissão para editar Projetos.');
        $this->load->model('obra_pontos_model');
        $obra_id = (int) $this->input->post('obra_id');
        $idPonto = (int) $this->input->post('idPonto');
        if (! $obra_id || ! $this->obras_model->getObra($obra_id)) {
            $this->session->set_flashdata('error', 'Projeto não encontrado.');
            redirect('obras');
        }
        $tipo = (string) $this->input->post('tipo');
        $ident = mb_substr(trim((string) $this->input->post('identificacao')), 0, 30);
        if (! isset(Obra_pontos_model::tipos()[$tipo])) {
            $this->session->set_flashdata('error', 'Tipo de ponto inválido.');
            $this->voltarPontos($obra_id);
        }
        if ($ident === '') {
            $this->session->set_flashdata('error', 'Informe a identificação do ponto (ex.: CAM-07).');
            $this->voltarPontos($obra_id);
        }
        $dados = [
            'tipo' => $tipo,
            'identificacao' => $ident,
            'local' => $this->textoPonto('local', 150),
            'ip' => $this->textoPonto('ip', 45),
            'canal' => $this->textoPonto('canal', 10),
            'porta' => $this->textoPonto('porta', 30),
            'modelo' => $this->textoPonto('modelo', 80),
            'serie' => $this->textoPonto('serie', 60),
            'observacao' => $this->textoPonto('observacao', 255),
        ];
        if ($idPonto) {
            $ponto = $this->obra_pontos_model->getPonto($idPonto);
            if (! $ponto || (int) $ponto->obra_id !== $obra_id) {
                $this->session->set_flashdata('error', 'Ponto não encontrado neste projeto.');
                $this->voltarPontos($obra_id);
            }
            $dados['atualizado_por'] = (int) $this->session->userdata('id_admin');
            $dados['atualizado_em'] = date('Y-m-d H:i:s');
            $this->obra_pontos_model->editPonto($idPonto, $obra_id, $dados);
        } else {
            $dados['obra_id'] = $obra_id;
            $dados['status'] = 'previsto';
            $dados['data_cadastro'] = date('Y-m-d H:i:s');
            $idPonto = (int) $this->obra_pontos_model->addPonto($dados);
            $this->obra_pontos_model->recalcularProgressoObra($obra_id);
        }

        // Fotos (data URL, até 6) entram com a etapa = status atual do ponto
        $fotosSalvas = 0;
        $fotosRecusadas = 0;
        $pontoSalvo = $idPonto ? $this->obra_pontos_model->getPonto($idPonto) : null;
        if ($pontoSalvo) {
            $uid = (int) $this->session->userdata('id_admin');
            foreach (array_slice((array) $this->input->post('fotos', false), 0, 6) as $b64) {
                $bin = $this->base64ParaBlob($b64);
                if ($bin === null) {
                    $fotosRecusadas++;
                    continue;
                }
                if ($this->obra_pontos_model->addFoto($idPonto, $obra_id, $bin, $uid ?: null, (string) $pontoSalvo->status) > 0) {
                    $fotosSalvas++;
                } else {
                    $fotosRecusadas++;
                }
            }
        }

        log_info('Salvou ponto ' . $ident . ' na obra ' . $obra_id);
        $msg = 'Ponto salvo.';
        if ($fotosSalvas > 0) {
            $msg .= ' ' . $fotosSalvas . ($fotosSalvas === 1 ? ' foto adicionada.' : ' fotos adicionadas.');
        }
        if ($fotosRecusadas > 0) {
            $msg .= ' ' . $fotosRecusadas . ($fotosRecusadas === 1 ? ' foto não aceita (formato ou tamanho).' : ' fotos não aceitas (formato ou tamanho).');
        }
        $this->session->set_flashdata('success', $msg);
        $this->voltarPontos($obra_id);
    }

    /** Foto de um ponto (equipamento/etiqueta). Gestão ou acesso ao projeto. */
    public function fotoPonto()
    {
        $this->load->model('obra_pontos_model');
        $id = (int) $this->uri->segment(3);
        $row = $id ? $this->obra_pontos_model->getFotoBinaria($id) : null;
        $podeVer = $row && ($this->permission->checkPermission($this->session->userdata('permissao'), 'vObras')
            || $this->obras_model->usuarioTemAcesso($row->obra_id, $this->session->userdata('id_admin')));
        if (! $podeVer) {
            show_404();
        }
        $mime = Obra_pontos_model::fotoValida($row->foto);
        if ($mime === null) {
            show_404();
        }
        header('Content-Type: ' . $mime);
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        echo $row->foto;
        return;
    }

    /** Repassa os pontos marcados para um executor do projeto, ou para a equipe (destinatario vazio). */
    public function repassarPontos()
    {
        $this->precisa('eObras', 'Você não tem permissão para editar Projetos.');
        $this->load->model('obra_pontos_model');
        $obra_id = (int) $this->input->post('obra_id');
        if (! $obra_id || ! $this->obras_model->getObra($obra_id)) {
            $this->session->set_flashdata('error', 'Projeto não encontrado.');
            redirect('obras');
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('ponto_ids')), function ($v) {
            return $v > 0;
        })));
        if (! $ids) {
            $this->session->set_flashdata('error', 'Marque ao menos um ponto para repassar.');
            $this->voltarPontos($obra_id);
        }

        $executores = $this->obra_pontos_model->executores($obra_id);
        $destino = (string) $this->input->post('destinatario');
        $destinoId = null;
        $destinoNome = 'a equipe';
        if ($destino !== '') {
            $destinoId = (int) $destino;
            $nome = null;
            foreach ($executores as $e) {
                if ((int) $e['idUsuarios'] === $destinoId) {
                    $nome = $e['nome'];
                }
            }
            if (! $destinoId || $nome === null) {
                $this->session->set_flashdata('error', 'Escolha um executor do projeto ou a equipe.');
                $this->voltarPontos($obra_id);
            }
            // O flash é injetado em JS pela view: sem aspas e sem tags no nome
            $destinoNome = preg_replace('/[<>"\'\\\\]/u', '', (string) $nome);
        }

        $uid = (int) $this->session->userdata('id_admin');
        $instrucao = $this->textoPonto('instrucao', 255);
        $n = $this->obra_pontos_model->repassarLote($obra_id, $ids, $destinoId, $uid ?: null, $instrucao);
        log_info('Repassou ' . $n . ' pontos na obra ' . $obra_id);
        if ($n > 0) {
            $this->session->set_flashdata('success', $n . ($n === 1 ? ' ponto repassado' : ' pontos repassados') . ' para ' . $destinoNome . '.');
        } else {
            $this->session->set_flashdata('error', 'Nenhum ponto foi repassado (pontos testados não podem ser repassados).');
        }
        $this->voltarPontos($obra_id);
    }

    public function gerarPontos()
    {
        $this->precisa('eObras', 'Você não tem permissão para editar Projetos.');
        $this->load->model('obra_pontos_model');
        $obra_id = (int) $this->input->post('obra_id');
        if (! $obra_id || ! $this->obras_model->getObra($obra_id)) {
            $this->session->set_flashdata('error', 'Projeto não encontrado.');
            redirect('obras');
        }
        $tipos = Obra_pontos_model::tipos();
        $tipo = (string) $this->input->post('tipo');
        if (! isset($tipos[$tipo])) {
            $this->session->set_flashdata('error', 'Escolha o tipo de ponto.');
            $this->voltarPontos($obra_id);
        }
        $prefixo = mb_substr(trim((string) $this->input->post('prefixo')), 0, 20);
        if ($prefixo === '') {
            $prefixo = $tipos[$tipo]['prefixo'];
        }
        $inicio = max(1, (int) $this->input->post('inicio'));
        $qtd = max(1, min(500, (int) $this->input->post('quantidade')));
        $local = $this->textoPonto('local', 150);
        $n = $this->obra_pontos_model->gerarLote($obra_id, $tipo, $prefixo, $inicio, $qtd, $local);
        if ($n > 0) {
            $this->obra_pontos_model->recalcularProgressoObra($obra_id);
        }
        log_info('Gerou ' . $n . ' pontos (' . $tipo . ') na obra ' . $obra_id);
        $this->session->set_flashdata('success', $n . ($n === 1 ? ' ponto criado.' : ' pontos criados.') . ($n < $qtd ? ' Os demais já existiam.' : ''));
        $this->voltarPontos($obra_id);
    }

    public function excluirPonto()
    {
        $this->precisa('eObras', 'Você não tem permissão para editar Projetos.');
        $this->load->model('obra_pontos_model');
        $obra_id = (int) $this->input->post('obra_id');
        $this->obra_pontos_model->excluirPonto((int) $this->input->post('idPonto'), $obra_id);
        $this->obra_pontos_model->recalcularProgressoObra($obra_id);
        log_info('Excluiu ponto ' . (int) $this->input->post('idPonto') . ' da obra ' . $obra_id);
        $this->session->set_flashdata('success', 'Ponto removido.');
        $this->voltarPontos($obra_id);
    }

    public function statusPonto()
    {
        $this->load->model('obra_pontos_model');
        $obra_id = (int) $this->input->post('obra_id');
        $uid = (int) $this->session->userdata('id_admin');
        $pode = $this->permission->checkPermission($this->session->userdata('permissao'), 'eObras')
            || $this->obras_model->usuarioTemAcesso($obra_id, $uid);
        if (! $pode) {
            $this->session->set_flashdata('error', 'Você não tem permissão para atualizar este projeto.');
            redirect('obras');
        }
        $ponto = $this->obra_pontos_model->getPonto((int) $this->input->post('ponto_id'));
        if (! $ponto || (int) $ponto->obra_id !== $obra_id) {
            $this->session->set_flashdata('error', 'Ponto não encontrado neste projeto.');
            $this->voltarPontos($obra_id);
        }
        $status = (string) $this->input->post('status');
        $lista = Obra_pontos_model::statusLista();
        if (! isset($lista[$status])) {
            $this->session->set_flashdata('error', 'Status inválido.');
            $this->voltarPontos($obra_id);
        }
        $metros = max(0, (float) str_replace(',', '.', (string) $this->input->post('metragem_cabo')));
        $extras = ['metragem_cabo' => min($metros, 999999.99)];
        $obs = $this->textoPonto('observacao', 255);
        if ($obs !== null) {
            $extras['observacao'] = $obs;
        }
        $this->obra_pontos_model->mudarStatus((int) $ponto->idPonto, $obra_id, $status, $uid ?: null, $extras);
        log_info('Status do ponto ' . $ponto->identificacao . ' para ' . $status . ' na obra ' . $obra_id);
        $this->session->set_flashdata('success', $ponto->identificacao . ': ' . $lista[$status]['rotulo']);
        $this->voltarPontos($obra_id);
    }

    public function aplicarEtapasPadrao()
    {
        $this->precisa('eObras', 'Você não tem permissão para editar Projetos.');
        $this->load->model('obra_pontos_model');
        $obra_id = (int) $this->input->post('obra_id');
        $obra = $obra_id ? $this->obras_model->getObra($obra_id) : null;
        if (! $obra) {
            $this->session->set_flashdata('error', 'Projeto não encontrado.');
            redirect('obras');
        }
        if (trim((string) $obra->tipo_obra) === '') {
            $this->session->set_flashdata('error', 'Defina o tipo do projeto para criar as etapas padrão.');
            redirect('obras/visualizar/' . $obra_id . '#cronograma');
        }
        $n = $this->obra_pontos_model->aplicarEtapasPadrao($obra_id, (string) $obra->tipo_obra);
        if (! $this->obra_pontos_model->getPontos($obra_id)) {
            $this->obras_model->recalcularProgresso($obra_id);
        }
        log_info('Aplicou etapas padrão (' . $n . ') na obra ' . $obra_id);
        $this->session->set_flashdata('success', $n > 0 ? $n . ($n === 1 ? ' etapa criada.' : ' etapas criadas.') : 'Todas as etapas padrão já existem.');
        redirect('obras/visualizar/' . $obra_id . '#cronograma');
    }

    /** As-built: lista dos pontos instalados, imprimível, para entregar ao cliente. */
    public function asbuilt()
    {
        $this->precisa('vObras', 'Você não tem permissão para visualizar Projetos.');
        $id = (int) $this->uri->segment(3);
        $obra = $id ? $this->obras_model->getObra($id) : null;
        if (! $obra) {
            $this->session->set_flashdata('error', 'Projeto não encontrado.');
            redirect('obras');
        }
        $this->load->model('mapos_model');
        $this->load->model('obra_pontos_model');
        $pontos = $this->obra_pontos_model->getPontos($id);
        $this->data['obra'] = $obra;
        $this->data['pontos'] = $pontos;
        $this->data['resumo'] = Obra_pontos_model::resumo($pontos);
        $this->data['tipos'] = Obra_pontos_model::tipos();
        $this->data['status_lista'] = Obra_pontos_model::statusLista();
        $this->data['emitente'] = $this->mapos_model->getEmitente();
        $this->data['com_fotos'] = (string) $this->input->get('fotos') === '1';
        $this->data['fotos_pontos'] = $this->data['com_fotos'] ? $this->obra_pontos_model->getFotosObra($id) : [];
        $this->load->view('obras/asbuilt', $this->data);
    }

    /* ============================= RDO ============================= */

    public function salvarRdo()
    {
        $this->precisa('cObraRdo', 'Você não tem permissão para registrar RDO.');
        $obra_id = (int) $this->input->post('obra_id');
        $dados = [
            'obra_id' => $obra_id,
            'numero' => $this->obras_model->proximoNumeroRdo($obra_id),
            'data' => $this->dataOuNull($this->input->post('data')) ?: date('Y-m-d'),
            'clima_manha' => $this->input->post('clima_manha'),
            'clima_tarde' => $this->input->post('clima_tarde'),
            'clima_noite' => $this->input->post('clima_noite'),
            'condicao' => $this->input->post('condicao') ?: 'praticavel',
            'responsavel_id' => $this->session->userdata('id_admin'),
            'efetivo_total' => (int) $this->input->post('efetivo_total'),
            'atividades' => $this->input->post('atividades'),
            'ocorrencias' => $this->input->post('ocorrencias'),
            'observacoes' => $this->input->post('observacoes'),
            'status' => 'finalizado',
            'assinatura' => dataurl_imagem($this->input->post('assinatura', false)),
            'data_registro' => date('Y-m-d H:i:s'),
        ];
        $rdoId = $this->obras_model->addRdo($dados);

        // Fotos enviadas como base64 (campo repetível fotos[])
        foreach ((array) $this->input->post('fotos', false) as $b64) {
            $bin = $this->base64ParaBlob($b64);
            if ($bin !== null) {
                $this->obras_model->addRdoFoto($rdoId, $bin);
            }
        }

        log_info('Registrou RDO na obra ' . $obra_id);

        // Envia o RDO ao(s) grupo(s)/cliente/e-mail do gatilho "rdo_registrado". Best-effort.
        try {
            $this->load->library('notificador');
            $this->notificador->whatsappRdo($rdoId);
            $this->notificador->emailRdo($rdoId);
        } catch (\Throwable $e) {
            log_info('Falha ao disparar notificação de RDO (RDO #' . $rdoId . '): ' . $e->getMessage());
        }

        $this->session->set_flashdata('success', 'RDO registrado.');
        redirect('obras/visualizar/' . $obra_id . '#rdo');
    }

    public function fotoRdo()
    {
        $id = (int) $this->uri->segment(3);
        $row = $this->db->select('f.foto, r.obra_id')
            ->from('obra_rdo_foto f')
            ->join('obra_rdo r', 'r.idRdo = f.rdo_id')
            ->where('f.idFoto', $id)
            ->get()->row();

        // Gestão (vObras) ou executor com acesso ao projeto do RDO.
        $podeVer = $row && ($this->permission->checkPermission($this->session->userdata('permissao'), 'vObras')
            || $this->obras_model->usuarioTemAcesso($row->obra_id, $this->session->userdata('id_admin')));
        if (! $podeVer) {
            show_404();
        }
        if ($row->foto) {
            header('Content-Type: image/jpeg');
            echo $row->foto;
        }
    }

    /** Versão imprimível de um RDO (o que foi feito), com cabeçalho do emitente. */
    public function imprimirRdo()
    {
        $this->precisa('vObras', 'Você não tem permissão para visualizar Projetos.');
        $id = (int) $this->uri->segment(3);
        $rdo = $this->obras_model->getRdo($id);
        if (! $id || ! $rdo) {
            $this->session->set_flashdata('error', 'RDO não encontrado.');
            redirect('obras');
        }
        $this->load->model('mapos_model');
        $resp = $this->db->select('nome')->where('idUsuarios', (int) $rdo->responsavel_id)
            ->get('usuarios')->row();
        $this->data['rdo'] = $rdo;
        $this->data['obra'] = $this->obras_model->getObra($rdo->obra_id);
        $this->data['responsavel'] = $resp ? $resp->nome : '';
        $this->data['fotos'] = $this->obras_model->getRdoFotos($id);
        $this->data['emitente'] = $this->mapos_model->getEmitente();
        $this->load->view('obras/imprimir_rdo', $this->data);
    }

    /* =========================== Medição =========================== */

    public function salvarMedicao()
    {
        $this->precisa('cObraMedicao', 'Você não tem permissão para lançar medição.');
        $obra_id = (int) $this->input->post('obra_id');
        if (! $obra_id || ! $this->obras_model->getObra($obra_id)) {
            $this->session->set_flashdata('error', 'Projeto não encontrado.');
            redirect('obras');
        }
        $this->load->model('obra_medicao_model');

        // form antigo manda pct_<idEtapa>; o novo pode mandar p[idEtapa]
        $pct = (array) $this->input->post('p');
        foreach ($this->obras_model->getEtapas($obra_id) as $e) {
            $v = $this->input->post('pct_' . $e->idEtapa);
            if ($v !== null && $v !== '') {
                $pct[(int) $e->idEtapa] = $v;
            }
        }

        $osIds = $this->osIdsDaMedicao($obra_id, (array) $this->input->post('os_ids'), (bool) $this->input->post('os_selecao'));
        if ($osIds === false) {
            $this->session->set_flashdata('error', 'Há OS que não pertencem a este projeto ou não aguardam medição.');
            redirect('obras/visualizar/' . $obra_id . '#medicao');
        }

        $id = $this->obra_medicao_model->criarMedicao($obra_id, [
            'periodo_inicio' => $this->dataOuNull($this->input->post('periodo_inicio')),
            'periodo_fim' => $this->dataOuNull($this->input->post('periodo_fim')),
        ], $pct, $osIds, (int) $this->session->userdata('id_admin'));

        if (! $id) {
            $this->session->set_flashdata('error', 'Nada a medir: nenhuma etapa avançou e nenhuma OS foi incluída.');
        } else {
            $this->session->set_flashdata('success', 'Medição registrada' . ($osIds ? ' com ' . count($osIds) . ' OS.' : '.'));
        }
        redirect('obras/visualizar/' . $obra_id . '#medicao');
    }

    public function aprovarMedicao()
    {
        $this->precisa('aObraMedicao', 'Você não tem permissão para aprovar medição.');
        $obra_id = (int) $this->input->post('obra_id');
        $id = (int) $this->input->post('idMedicao');
        $m = $this->medicaoDaObra($obra_id, $id);
        if (! $m || $m->status !== 'aberta') {
            $this->session->set_flashdata('error', 'Medição não encontrada ou já aprovada.');
            redirect('obras/visualizar/' . $obra_id . '#medicao');
        }
        $assinatura = dataurl_imagem($this->input->post('assinatura', false));
        if (! $this->obras_model->aprovarMedicao($id, $this->session->userdata('id_admin'), $assinatura)) {
            $this->session->set_flashdata('error', 'Medição já aprovada por outra pessoa.');
            redirect('obras/visualizar/' . $obra_id . '#medicao');
        }
        $this->session->set_flashdata('success', 'Medição aprovada.');
        redirect('obras/visualizar/' . $obra_id . '#medicao');
    }

    /** Previa da medicao (JSON): GET obra_id, p[etapa]=percentual, os[]=idOs (sem os[] e sem os_selecao: todas as aguardando). */
    public function previaMedicaoAction()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cObraMedicao')) {
            return $this->jsonMedicao(['ok' => false, 'erro' => 'Você não tem permissão para lançar medição.'], 403);
        }
        $obra_id = (int) $this->input->get('obra_id');
        if (! $obra_id || ! $this->obras_model->getObra($obra_id)) {
            return $this->jsonMedicao(['ok' => false, 'erro' => 'Projeto não encontrado.'], 404);
        }
        $this->load->model('obra_medicao_model');
        $osIds = $this->osIdsDaMedicao($obra_id, (array) $this->input->get('os'), (bool) $this->input->get('os_selecao'));
        if ($osIds === false) {
            return $this->jsonMedicao(['ok' => false, 'erro' => 'Há OS que não pertencem a este projeto ou não aguardam medição.'], 400);
        }
        $r = $this->obra_medicao_model->previaMedicao($obra_id, (array) $this->input->get('p'), $osIds);
        return $this->jsonMedicao(['ok' => true] + $r);
    }

    /** Fatura a medicao aprovada: lancamento de receita e OS da medicao faturadas (ADR-015 fase 5). */
    public function faturarMedicao()
    {
        $perm = $this->session->userdata('permissao');
        $ajax = $this->input->is_ajax_request();
        $obra_id = (int) $this->input->post('obra_id');
        $voltar = 'obras/visualizar/' . $obra_id . '#medicao';
        $responder = function ($ok, $msg, $extra = []) use ($ajax, $voltar) {
            if ($ajax) {
                return $this->jsonMedicao(['result' => $ok, 'message' => $msg] + $extra, $ok ? 200 : 400);
            }
            $this->session->set_flashdata($ok ? 'success' : 'error', $this->textoFlash($msg));
            redirect($voltar);
        };

        if (! $this->permission->checkPermission($perm, 'aObraMedicao') || ! $this->permission->checkPermission($perm, 'aLancamento')) {
            return $responder(false, 'Você não tem permissão para faturar medição.');
        }
        $id = (int) $this->input->post('idMedicao');
        $m = $this->medicaoDaObra($obra_id, $id);
        if (! $m) {
            return $responder(false, 'Medição não encontrada neste projeto.');
        }
        $this->load->model('obra_medicao_model');
        $r = $this->obra_medicao_model->faturarMedicao($id, (int) $this->session->userdata('id_admin'), [
            'vencimento' => $this->dataOuNull($this->input->post('vencimento')) ?: date('Y-m-d'),
            'forma_pagamento' => (string) ($this->input->post('forma_pagamento') ?: $this->input->post('formaPgto')),
        ]);
        if (empty($r['ok'])) {
            return $responder(false, $r['erro'] ?: 'Não foi possível faturar a medição.');
        }
        return $responder(true, 'Medição ' . (int) $m->numero . ' faturada.', ['lancamento_id' => $r['lancamento_id']]);
    }

    /**
     * OS da medicao: sem lista e sem a flag os_selecao (form antigo), todas as aguardando.
     * Retorna false se alguma OS nao for do projeto ou nao aguardar medicao.
     */
    private function osIdsDaMedicao($obra_id, array $pedidas, $selecao)
    {
        $aguardando = array_map(function ($o) { return (int) $o->idOs; }, $this->obra_medicao_model->osAguardandoMedicao($obra_id));
        $pedidas = array_values(array_unique(array_filter(array_map('intval', $pedidas))));
        if (! $pedidas && ! $selecao) {
            return $aguardando;
        }
        if (array_diff($pedidas, $aguardando)) {
            return false;
        }
        return $pedidas;
    }

    /** Medicao se pertencer ao projeto, senao null. */
    private function medicaoDaObra($obra_id, $idMedicao)
    {
        $m = $this->obras_model->getMedicao($idMedicao);
        return ($m && $obra_id && (int) $m->obra_id === (int) $obra_id) ? $m : null;
    }

    private function jsonMedicao(array $dados, $status = 200)
    {
        return $this->output->set_status_header($status)->set_content_type('application/json')->set_output(json_encode($dados));
    }

    /* ============================ Helpers ============================ */

    private function montarDados($comCadastro = true)
    {
        $dados = [
            'codigo' => $this->input->post('codigo'),
            'nome' => trim((string) $this->input->post('nome')),
            'clientes_id' => (int) $this->input->post('clientes_id') ?: null,
            'responsavel_id' => (int) $this->input->post('responsavel_id') ?: null,
            'tipo_obra' => $this->input->post('tipo_obra'),
            'contrato_numero' => $this->input->post('contrato_numero'),
            'valor_contrato' => (float) $this->input->post('valor_contrato'),
            'bdi_percentual' => (float) $this->input->post('bdi_percentual'),
            'cep' => $this->input->post('cep'),
            'logradouro' => $this->input->post('logradouro'),
            'numero' => $this->input->post('numero'),
            'complemento' => $this->input->post('complemento'),
            'bairro' => $this->input->post('bairro'),
            'cidade' => $this->input->post('cidade'),
            'uf' => $this->input->post('uf'),
            'data_inicio_prevista' => $this->dataOuNull($this->input->post('data_inicio_prevista')),
            'data_fim_prevista' => $this->dataOuNull($this->input->post('data_fim_prevista')),
            'data_inicio_real' => $this->dataOuNull($this->input->post('data_inicio_real')),
            'data_fim_real' => $this->dataOuNull($this->input->post('data_fim_real')),
            'status' => $this->input->post('status') ?: 'planejamento',
            'observacoes' => $this->input->post('observacoes'),
        ];
        if ($comCadastro) {
            $dados['data_cadastro'] = date('Y-m-d H:i:s');
        }
        return $dados;
    }

    private function carregarSelects()
    {
        $this->data['clientes'] = $this->db->select('idClientes, nomeCliente, documento')
            ->order_by('nomeCliente', 'ASC')->get('clientes')->result();
        $this->data['usuarios'] = $this->db->select('idUsuarios, nome')
            ->where('situacao', 1)->order_by('nome', 'ASC')->get('usuarios')->result();
    }

    /** Fotos devem vir de $this->input->post('fotos', false): o filtro de XSS corrompe o data URL. */
    private function base64ParaBlob($b64)
    {
        if (! $b64 || strpos($b64, 'base64,') === false) {
            return null;
        }
        $bin = base64_decode(explode('base64,', $b64)[1], true);
        return $bin ?: null;
    }
}
