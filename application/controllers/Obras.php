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

    private function precisa($perm, $msg)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), $perm)) {
            $this->session->set_flashdata('error', $msg);
            redirect(base_url());
        }
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
                $id = $this->obras_model->add($this->montarDados());
                if ($id) {
                    log_info('Adicionou um projeto. ID: ' . $id);
                    $this->session->set_flashdata('success', 'Projeto cadastrado com sucesso.');
                    redirect('obras/visualizar/' . $id);
                }
                $this->session->set_flashdata('error', 'Ocorreu um erro ao salvar.');
            }
        }

        $this->carregarSelects();
        $this->data['view'] = 'obras/adicionar';
        return $this->layout();
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
            $this->obras_model->edit($id, $this->montarDados(false));
            log_info('Editou o projeto. ID: ' . $id);
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

        $this->data['view'] = 'obras/visualizar';
        return $this->layout();
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

        foreach ((array) $this->input->post('fotos') as $b64) {
            $bin = $this->base64ParaBlob($b64);
            if ($bin !== null) {
                $this->obras_model->addRdoFoto($rdoId, $bin);
            }
        }

        log_info('Registro rápido de execução na obra ' . $obra_id);
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
            $this->session->set_flashdata('error', 'Não foi possível vincular (a OS já pode estar em um projeto).');
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
        $this->obras_model->deleteApontamento((int) $this->input->post('idApontamento'), $obra_id);
        $this->session->set_flashdata('success', 'Apontamento removido.');
        redirect('obras/visualizar/' . $obra_id . '#maodeobra');
    }

    public function excluir()
    {
        $this->precisa('dObras', 'Você não tem permissão para excluir Projetos.');
        $id = (int) $this->input->post('idObra');
        if ($id) {
            $this->obras_model->delete($id);
            log_info('Removeu o projeto. ID: ' . $id);
            $this->session->set_flashdata('success', 'Projeto excluído com sucesso.');
        }
        redirect('obras');
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
            'peso_percentual' => (float) $this->input->post('peso_percentual'),
            'valor_previsto' => (float) $this->input->post('valor_previsto'),
            'percentual_concluido' => (float) $this->input->post('percentual_concluido'),
            'data_inicio_prevista' => $this->dataOuNull($this->input->post('data_inicio_prevista')),
            'data_fim_prevista' => $this->dataOuNull($this->input->post('data_fim_prevista')),
            'status' => $this->input->post('status') ?: 'pendente',
        ];
        if ($dados['nome'] === '') {
            $this->session->set_flashdata('error', 'Informe o nome da etapa.');
            redirect('obras/visualizar/' . $obra_id);
        }
        if ($idEtapa) {
            $this->obras_model->editEtapa($idEtapa, $dados);
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
        $this->obras_model->deleteEtapa((int) $this->input->post('idEtapa'));
        $this->obras_model->recalcularProgresso($obra_id);
        $this->session->set_flashdata('success', 'Etapa removida.');
        redirect('obras/visualizar/' . $obra_id . '#cronograma');
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
            'assinatura' => $this->input->post('assinatura') ?: null,
            'data_registro' => date('Y-m-d H:i:s'),
        ];
        $rdoId = $this->obras_model->addRdo($dados);

        // Fotos enviadas como base64 (campo repetível fotos[])
        foreach ((array) $this->input->post('fotos') as $b64) {
            $bin = $this->base64ParaBlob($b64);
            if ($bin !== null) {
                $this->obras_model->addRdoFoto($rdoId, $bin);
            }
        }

        log_info('Registrou RDO na obra ' . $obra_id);
        $this->session->set_flashdata('success', 'RDO registrado.');
        redirect('obras/visualizar/' . $obra_id . '#rdo');
    }

    public function fotoRdo()
    {
        $foto = $this->obras_model->getRdoFotos((int) $this->uri->segment(3));
        // devolve a primeira imagem por id da foto
        $id = (int) $this->uri->segment(3);
        $this->db->where('idFoto', $id);
        $row = $this->db->get('obra_rdo_foto')->row();
        if ($row && $row->foto) {
            header('Content-Type: image/jpeg');
            echo $row->foto;
        }
    }

    /* =========================== Medição =========================== */

    public function salvarMedicao()
    {
        $this->precisa('cObraMedicao', 'Você não tem permissão para lançar medição.');
        $obra_id = (int) $this->input->post('obra_id');
        $etapas = $this->obras_model->getEtapas($obra_id);

        $itens = [];
        $valorTotal = 0;
        $pesoTotal = 0;
        $pctPonderado = 0;
        foreach ($etapas as $e) {
            $novo = (float) $this->input->post('pct_' . $e->idEtapa);
            $anterior = (float) $e->percentual_concluido;
            if ($novo < $anterior) {
                $novo = $anterior;
            }
            $valorItem = (float) $e->valor_previsto * (($novo - $anterior) / 100);
            $itens[] = [
                'etapa_id' => $e->idEtapa,
                'percentual_anterior' => $anterior,
                'percentual_atual' => $novo,
                'valor' => $valorItem,
            ];
            $valorTotal += $valorItem;
            $pesoTotal += (float) $e->peso_percentual;
            $pctPonderado += (float) $e->peso_percentual * $novo;
            // aplica avanço na etapa
            $this->obras_model->editEtapa($e->idEtapa, ['percentual_concluido' => $novo]);
        }
        $acumulado = $pesoTotal > 0 ? round($pctPonderado / $pesoTotal, 2) : 0;

        $cab = [
            'obra_id' => $obra_id,
            'numero' => $this->obras_model->proximoNumeroMedicao($obra_id),
            'periodo_inicio' => $this->dataOuNull($this->input->post('periodo_inicio')),
            'periodo_fim' => $this->dataOuNull($this->input->post('periodo_fim')),
            'percentual_acumulado' => $acumulado,
            'valor_medido' => $valorTotal,
            'status' => 'aberta',
            'data_cadastro' => date('Y-m-d H:i:s'),
        ];
        $this->obras_model->addMedicao($cab, $itens);
        $this->obras_model->recalcularProgresso($obra_id);
        $this->session->set_flashdata('success', 'Medição registrada.');
        redirect('obras/visualizar/' . $obra_id . '#medicao');
    }

    public function aprovarMedicao()
    {
        $this->precisa('aObraMedicao', 'Você não tem permissão para aprovar medição.');
        $obra_id = (int) $this->input->post('obra_id');
        $id = (int) $this->input->post('idMedicao');
        $this->obras_model->aprovarMedicao($id, $this->session->userdata('id_admin'), $this->input->post('assinatura'));
        $this->session->set_flashdata('success', 'Medição aprovada.');
        redirect('obras/visualizar/' . $obra_id . '#medicao');
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
        $this->data['clientes'] = $this->db->select('idClientes, nomeCliente')
            ->order_by('nomeCliente', 'ASC')->get('clientes')->result();
        $this->data['usuarios'] = $this->db->select('idUsuarios, nome')
            ->where('situacao', 1)->order_by('nome', 'ASC')->get('usuarios')->result();
    }

    private function dataOuNull($valor)
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }
        // aceita dd/mm/aaaa ou aaaa-mm-dd
        if (strpos($valor, '/') !== false) {
            $p = explode('/', $valor);
            if (count($p) === 3) {
                return $p[2] . '-' . $p[1] . '-' . $p[0];
            }
        }
        return $valor;
    }

    private function base64ParaBlob($b64)
    {
        if (! $b64 || strpos($b64, 'base64,') === false) {
            return null;
        }
        $bin = base64_decode(explode('base64,', $b64)[1], true);
        return $bin ?: null;
    }
}
