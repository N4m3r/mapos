<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Formulários de Atendimento personalizados.
 *
 * Gerencia os formulários, seus campos e as respostas coletadas durante o
 * fluxo de atendimento (iniciar / durante / finalizar) de uma OS.
 */
class Formularios_atendimento_model extends CI_Model
{
    protected $table = 'formularios_atendimento';
    protected $tableCampos = 'formularios_atendimento_campos';
    protected $tableRespostas = 'formularios_atendimento_respostas';
    protected $tableItens = 'formularios_atendimento_respostas_itens';

    /* ------------------------------------------------------------------ */
    /* Catálogos                                                           */
    /* ------------------------------------------------------------------ */

    /** Etapas do fluxo de atendimento. */
    public static function etapasDisponiveis()
    {
        return [
            'iniciar' => 'Ao iniciar o atendimento',
            'durante' => 'Durante o atendimento',
            'finalizar' => 'Ao finalizar o atendimento',
        ];
    }

    /** Tipos de campo suportados pelo construtor. */
    public static function tiposCampo()
    {
        return [
            'texto' => 'Texto curto',
            'textarea' => 'Área de texto',
            'select' => 'Seleção suspensa',
            'radio' => 'Escolha única (opções)',
            'checkbox' => 'Múltipla escolha (caixas)',
            'number' => 'Número',
            'date' => 'Data',
            'time' => 'Hora',
            'email' => 'E-mail',
            'tel' => 'Telefone',
            'sim_nao' => 'Sim / Não',
        ];
    }

    /** Tipos que exigem lista de opções. */
    public static function tiposComOpcoes()
    {
        return ['select', 'radio', 'checkbox'];
    }

    /* ------------------------------------------------------------------ */
    /* Formulários                                                         */
    /* ------------------------------------------------------------------ */

    public function getAll()
    {
        if (! $this->db->table_exists($this->table)) {
            return [];
        }

        return $this->db->order_by('etapa', 'ASC')
            ->order_by('ordem', 'ASC')
            ->order_by('idFormulario', 'ASC')
            ->get($this->table)
            ->result();
    }

    public function getById($id)
    {
        if (! $this->db->table_exists($this->table)) {
            return null;
        }

        return $this->db->where('idFormulario', $id)->limit(1)->get($this->table)->row();
    }

    /** Formulários ativos de uma etapa, já com os campos carregados. */
    public function getByEtapa($etapa)
    {
        if (! $this->db->table_exists($this->table)) {
            return [];
        }

        $formularios = $this->db->where('etapa', $etapa)
            ->where('ativo', 1)
            ->order_by('ordem', 'ASC')
            ->order_by('idFormulario', 'ASC')
            ->get($this->table)
            ->result();

        foreach ($formularios as $f) {
            $f->campos = $this->getCampos($f->idFormulario);
        }

        return $formularios;
    }

    public function create(array $data)
    {
        if (! $this->db->table_exists($this->table)) {
            return false;
        }

        $data['data_cadastro'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);

        return $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        if (! $this->db->table_exists($this->table)) {
            return false;
        }

        $this->db->where('idFormulario', $id)->update($this->table, $data);

        return $this->db->affected_rows() >= 0;
    }

    public function delete($id)
    {
        if (! $this->db->table_exists($this->table)) {
            return false;
        }

        $this->db->where('formulario_id', $id)->delete($this->tableCampos);
        $this->db->where('idFormulario', $id)->delete($this->table);

        return $this->db->affected_rows() >= 0;
    }

    /* ------------------------------------------------------------------ */
    /* Campos                                                              */
    /* ------------------------------------------------------------------ */

    public function getCampos($formularioId)
    {
        if (! $this->db->table_exists($this->tableCampos)) {
            return [];
        }

        return $this->db->where('formulario_id', $formularioId)
            ->order_by('ordem', 'ASC')
            ->order_by('idCampo', 'ASC')
            ->get($this->tableCampos)
            ->result();
    }

    /**
     * Substitui todos os campos do formulário pela lista informada.
     *
     * @param array $campos lista de arrays (label, tipo, opcoes, placeholder,
     *                      ajuda, obrigatorio, ordem)
     */
    public function saveCampos($formularioId, array $campos)
    {
        if (! $this->db->table_exists($this->tableCampos)) {
            return false;
        }

        $this->db->where('formulario_id', $formularioId)->delete($this->tableCampos);

        $ordem = 0;
        foreach ($campos as $campo) {
            $label = trim($campo['label'] ?? '');
            if ($label === '') {
                continue;
            }

            $this->db->insert($this->tableCampos, [
                'formulario_id' => $formularioId,
                'label' => $label,
                'tipo' => $campo['tipo'] ?? 'texto',
                'opcoes' => isset($campo['opcoes']) && $campo['opcoes'] !== '' ? $campo['opcoes'] : null,
                'placeholder' => $campo['placeholder'] ?? null,
                'ajuda' => $campo['ajuda'] ?? null,
                'obrigatorio' => ! empty($campo['obrigatorio']) ? 1 : 0,
                'ordem' => $ordem++,
            ]);
        }

        return true;
    }

    /* ------------------------------------------------------------------ */
    /* Respostas                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Salva (ou regrava) a resposta de um formulário para uma OS.
     *
     * @param array $valores mapa campo_id => valor (valor pode ser array p/ checkbox)
     */
    public function salvarResposta($formularioId, $osId, $checkinId, $usuarioId, $etapa, array $valores)
    {
        if (! $this->db->table_exists($this->tableRespostas)) {
            return false;
        }

        $campos = $this->getCampos($formularioId);
        if (empty($campos)) {
            return false;
        }

        // Remove resposta anterior do mesmo formulário/OS (regrava).
        $antigas = $this->db->select('idResposta')
            ->where('formulario_id', $formularioId)
            ->where('os_id', $osId)
            ->get($this->tableRespostas)
            ->result();
        foreach ($antigas as $r) {
            $this->db->where('resposta_id', $r->idResposta)->delete($this->tableItens);
        }
        $this->db->where('formulario_id', $formularioId)->where('os_id', $osId)->delete($this->tableRespostas);

        $this->db->insert($this->tableRespostas, [
            'formulario_id' => $formularioId,
            'os_id' => $osId,
            'checkin_id' => $checkinId ?: null,
            'usuarios_id' => $usuarioId ?: null,
            'etapa' => $etapa,
            'data_resposta' => date('Y-m-d H:i:s'),
        ]);
        $respostaId = $this->db->insert_id();

        foreach ($campos as $campo) {
            $valor = $valores[$campo->idCampo] ?? '';
            if (is_array($valor)) {
                $valor = implode(', ', array_map('strval', $valor));
            }

            $this->db->insert($this->tableItens, [
                'resposta_id' => $respostaId,
                'campo_id' => $campo->idCampo,
                'label' => $campo->label,
                'valor' => $valor === '' ? null : $valor,
            ]);
        }

        return $respostaId;
    }

    /** Respostas de uma OS (com itens), opcionalmente filtradas por etapa. */
    public function getRespostasByOs($osId, $etapa = null)
    {
        if (! $this->db->table_exists($this->tableRespostas)) {
            return [];
        }

        $this->db->where('os_id', $osId);
        if ($etapa !== null) {
            $this->db->where('etapa', $etapa);
        }
        $respostas = $this->db->order_by('data_resposta', 'ASC')->get($this->tableRespostas)->result();

        foreach ($respostas as $r) {
            $r->itens = $this->db->where('resposta_id', $r->idResposta)
                ->order_by('idItem', 'ASC')
                ->get($this->tableItens)
                ->result();
            $form = $this->getById($r->formulario_id);
            $r->formulario_nome = $form ? $form->nome : ('Formulário #' . $r->formulario_id);
        }

        return $respostas;
    }

    /** Mapa campo_id => valor já respondido, para pré-preencher o formulário. */
    public function getValoresRespondidos($formularioId, $osId)
    {
        if (! $this->db->table_exists($this->tableRespostas)) {
            return [];
        }

        $resposta = $this->db->where('formulario_id', $formularioId)
            ->where('os_id', $osId)
            ->order_by('idResposta', 'DESC')
            ->limit(1)
            ->get($this->tableRespostas)
            ->row();

        if (! $resposta) {
            return [];
        }

        $itens = $this->db->where('resposta_id', $resposta->idResposta)->get($this->tableItens)->result();
        $mapa = [];
        foreach ($itens as $item) {
            $mapa[$item->campo_id] = $item->valor;
        }

        return $mapa;
    }
}
