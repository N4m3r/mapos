<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Agenda da equipe (ADR-005, F6): OS com data de atendimento agendada, por tecnico.
 * Ver: vOs. Arrastar para reagendar: eOs.
 */
class Agenda extends MY_Controller
{
    /** Cores fixas por tecnico (indice = id do tecnico % quantidade). */
    private const CORES = ['#2980b9', '#27ae60', '#8e44ad', '#d35400', '#16a085', '#c0392b', '#2c3e50', '#f39c12', '#7f8c8d', '#e84393'];

    public function __construct()
    {
        parent::__construct();
        $this->data['menuOs'] = 'OS';
    }

    public function index()
    {
        $this->precisa('vOs', 'Você não tem permissão para visualizar O.S.');

        $this->load->model('tecnico_model');
        $tecnicos = $this->tecnico_model->getTecnicosPorPermissao();
        foreach ($tecnicos as $t) {
            $t->cor = self::corDoTecnico($t->idUsuarios);
        }
        $this->data['tecnicos'] = $tecnicos;
        $this->data['podeEditar'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs');
        $this->data['disponivel'] = $this->db->field_exists('data_agendamento', 'os');
        $this->data['semData'] = $this->data['disponivel'] ? $this->osSemData() : [];
        $this->data['view'] = 'agenda/agenda';

        return $this->layout();
    }

    /**
     * Eventos do FullCalendar (?start=&end=&tecnico=).
     */
    public function eventos()
    {
        $this->precisa('vOs', 'Você não tem permissão para visualizar O.S.');

        $eventos = [];
        if ($this->db->field_exists('data_agendamento', 'os')) {
            $inicio = date('Y-m-d H:i:s', strtotime((string) $this->input->get('start')) ?: strtotime('monday this week'));
            $fim = date('Y-m-d H:i:s', strtotime((string) $this->input->get('end')) ?: strtotime('+7 days'));
            $this->db->select('o.idOs, o.status, o.data_agendamento, o.tecnico_responsavel, c.nomeCliente, c.bairro, c.cidade, u.nome AS tecnico')
                ->from('os o')
                ->join('clientes c', 'c.idClientes = o.clientes_id', 'left')
                ->join('usuarios u', 'u.idUsuarios = o.tecnico_responsavel', 'left')
                ->where('o.data_agendamento >=', $inicio)
                ->where('o.data_agendamento <', $fim)
                ->where('o.status !=', 'Cancelado');
            if ($tecnico = (int) $this->input->get('tecnico')) {
                $this->db->where('o.tecnico_responsavel', $tecnico);
            }
            foreach ($this->db->get()->result() as $os) {
                $inicioTs = strtotime($os->data_agendamento);
                $concluida = in_array($os->status, ['Finalizado', 'Faturado'], true);
                $eventos[] = [
                    'id' => (int) $os->idOs,
                    'title' => '#' . $os->idOs . ' ' . $os->nomeCliente,
                    'start' => date('Y-m-d\TH:i:s', $inicioTs),
                    'end' => date('Y-m-d\TH:i:s', $inicioTs + 3600),
                    'url' => site_url('os/visualizar/' . (int) $os->idOs),
                    'color' => $os->tecnico_responsavel ? self::corDoTecnico($os->tecnico_responsavel) : '#95a5a6',
                    'classNames' => $concluida ? ['agenda-concluida'] : [],
                    'extendedProps' => [
                        'tecnico' => $os->tecnico ?: 'Sem técnico',
                        'status' => $os->status,
                        'local' => trim(($os->bairro ?? '') . ' - ' . ($os->cidade ?? ''), ' -'),
                    ],
                ];
            }
        }

        $this->output->set_content_type('application/json')->set_output(json_encode($eventos));
    }

    /**
     * Reagenda uma OS (arrastar no calendario). POST os_id, inicio (AAAA-MM-DD HH:MM).
     */
    public function mover()
    {
        $resposta = function ($ok, $mensagem, $status = 200) {
            $this->output->set_status_header($status)->set_content_type('application/json')
                ->set_output(json_encode(['result' => $ok, 'mensagem' => $mensagem]));
        };

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) {
            return $resposta(false, 'Você não tem permissão para reagendar O.S.', 403);
        }
        $osId = (int) $this->input->post('os_id');
        $ts = strtotime((string) $this->input->post('inicio'));
        $os = $osId ? $this->db->select('idOs, status, data_agendamento')->where('idOs', $osId)->get('os')->row() : null;
        if (! $os || ! $ts) {
            return $resposta(false, 'OS ou data inválida.', 422);
        }
        if (in_array($os->status, ['Finalizado', 'Faturado', 'Cancelado'], true)) {
            return $resposta(false, 'OS ' . $os->status . ' não pode ser reagendada.', 422);
        }

        $novo = date('Y-m-d H:i:s', $ts);
        $this->db->where('idOs', $osId)->update('os', ['data_agendamento' => $novo]);
        log_info('Reagendou a OS #' . $osId . ' de ' . ($os->data_agendamento ?: 'sem data') . ' para ' . $novo);

        return $resposta(true, 'OS #' . $osId . ' reagendada para ' . date('d/m/Y H:i', $ts) . '.');
    }

    public static function corDoTecnico($id)
    {
        return self::CORES[((int) $id) % count(self::CORES)];
    }

    /** OS abertas com tecnico e sem data agendada (para lembrar de agendar). */
    private function osSemData()
    {
        return $this->db->select('o.idOs, o.status, c.nomeCliente, u.nome AS tecnico')
            ->from('os o')
            ->join('clientes c', 'c.idClientes = o.clientes_id', 'left')
            ->join('usuarios u', 'u.idUsuarios = o.tecnico_responsavel', 'left')
            ->where('o.data_agendamento IS NULL', null, false)
            ->where('o.tecnico_responsavel IS NOT NULL', null, false)
            ->where_not_in('o.status', ['Finalizado', 'Faturado', 'Cancelado'])
            ->order_by('o.idOs', 'DESC')->limit(20)
            ->get()->result();
    }
}
