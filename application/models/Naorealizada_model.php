<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Serviço não realizado + motivos gerenciáveis (Área do Técnico).
 *
 * Registra as ocorrências de "não foi possível realizar o serviço", mantém a
 * lista de motivos padronizados e cuida da resolução (reagendar / reabrir).
 * Todos os métodos são resilientes: se as tabelas ainda não existem (migration
 * não aplicada), devolvem vazio em vez de quebrar a Área do Técnico.
 */
class Naorealizada_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /** As tabelas da funcionalidade existem? */
    public function disponivel()
    {
        return $this->db->table_exists('os_nao_realizada')
            && $this->db->table_exists('motivos_nao_realizada');
    }

    /* ============================================================= *
     *  Motivos
     * ============================================================= */

    public function getMotivos($somenteAtivos = true)
    {
        if (! $this->db->table_exists('motivos_nao_realizada')) {
            return [];
        }
        if ($somenteAtivos) {
            $this->db->where('ativo', 1);
        }
        $this->db->order_by('ordem', 'ASC');
        $this->db->order_by('nome', 'ASC');
        $query = $this->db->get('motivos_nao_realizada');

        return $query ? $query->result() : [];
    }

    public function getMotivo($id)
    {
        if (! $this->db->table_exists('motivos_nao_realizada')) {
            return null;
        }
        $this->db->where('idMotivo', (int) $id);
        $query = $this->db->get('motivos_nao_realizada');

        return $query ? $query->row() : null;
    }

    public function addMotivo($nome)
    {
        $nome = trim((string) $nome);
        if ($nome === '' || ! $this->db->table_exists('motivos_nao_realizada')) {
            return false;
        }

        // Evita duplicados (case-insensitive).
        $this->db->where('LOWER(nome)', mb_strtolower($nome, 'UTF-8'));
        if ($this->db->get('motivos_nao_realizada')->num_rows() > 0) {
            return false;
        }

        $ordem = (int) $this->db->select_max('ordem')->get('motivos_nao_realizada')->row()->ordem;
        $this->db->insert('motivos_nao_realizada', [
            'nome' => $nome,
            'ativo' => 1,
            'ordem' => $ordem + 1,
            'data_cadastro' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() >= 1;
    }

    /**
     * Remove um motivo. Se ele já foi usado em alguma ocorrência, apenas o
     * desativa (soft delete) para preservar o histórico; caso contrário,
     * apaga de fato.
     */
    public function removerMotivo($id)
    {
        $id = (int) $id;
        if (! $id || ! $this->db->table_exists('motivos_nao_realizada')) {
            return false;
        }

        $emUso = false;
        if ($this->db->table_exists('os_nao_realizada')) {
            $this->db->where('motivo_id', $id);
            $emUso = $this->db->count_all_results('os_nao_realizada') > 0;
        }

        if ($emUso) {
            $this->db->where('idMotivo', $id);
            $this->db->update('motivos_nao_realizada', ['ativo' => 0]);
        } else {
            $this->db->where('idMotivo', $id);
            $this->db->delete('motivos_nao_realizada');
        }

        return true;
    }

    public function reativarMotivo($id)
    {
        if (! $this->db->table_exists('motivos_nao_realizada')) {
            return false;
        }
        $this->db->where('idMotivo', (int) $id);
        $this->db->update('motivos_nao_realizada', ['ativo' => 1]);

        return true;
    }

    /* ============================================================= *
     *  Ocorrências (serviço não realizado)
     * ============================================================= */

    /**
     * Registra que o serviço não pôde ser realizado e coloca a OS em espera
     * (status "Não Realizado"). Guarda o status anterior para poder reabrir.
     *
     * @return int|false id da ocorrência
     */
    public function registrar($os_id, $usuarios_id, $motivo_id, $observacao, $status_anterior)
    {
        if (! $this->db->table_exists('os_nao_realizada')) {
            return false;
        }

        $motivo_id = (int) $motivo_id ?: null;
        $motivo_texto = null;
        if ($motivo_id) {
            $m = $this->getMotivo($motivo_id);
            $motivo_texto = $m ? $m->nome : null;
        }

        $this->db->insert('os_nao_realizada', [
            'os_id' => (int) $os_id,
            'usuarios_id' => (int) $usuarios_id ?: null,
            'motivo_id' => $motivo_id,
            'motivo_texto' => $motivo_texto,
            'observacao' => trim((string) $observacao) ?: null,
            'status_anterior' => $status_anterior ?: null,
            'data_registro' => date('Y-m-d H:i:s'),
            'resolvido' => 0,
        ]);

        $idOcorrencia = $this->db->insert_id();

        // Coloca a OS em espera.
        $this->db->where('idOs', (int) $os_id);
        $this->db->update('os', ['status' => 'Não Realizado']);

        return $idOcorrencia ?: false;
    }

    /**
     * Ocorrência pendente (não resolvida) de uma OS, se houver.
     */
    public function getPendentePorOs($os_id)
    {
        if (! $this->db->table_exists('os_nao_realizada')) {
            return null;
        }
        $this->db->where('os_id', (int) $os_id);
        $this->db->where('resolvido', 0);
        $this->db->order_by('idOcorrencia', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get('os_nao_realizada');

        return $query ? $query->row() : null;
    }

    public function getOcorrencia($id)
    {
        if (! $this->db->table_exists('os_nao_realizada')) {
            return null;
        }
        $this->db->where('idOcorrencia', (int) $id);
        $query = $this->db->get('os_nao_realizada');

        return $query ? $query->row() : null;
    }

    /**
     * Painel de espera: OS marcadas como "Não Realizado" (ocorrência ainda não
     * resolvida). Se $tecnico_id for informado, restringe às OS dele.
     */
    public function getPendentes($tecnico_id = null, $limite = 100)
    {
        if (! $this->db->table_exists('os_nao_realizada')) {
            return [];
        }

        $this->db->select('nr.*, os.idOs, os.status, os.dataInicial, os.descricaoProduto, os.tecnico_responsavel, clientes.nomeCliente, clientes.telefone, clientes.celular, usuarios.nome as nome_tecnico');
        $this->db->from('os_nao_realizada nr');
        $this->db->join('os', 'os.idOs = nr.os_id');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id', 'left');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os.tecnico_responsavel', 'left');
        $this->db->where('nr.resolvido', 0);
        $this->db->where('os.status', 'Não Realizado');
        if ($tecnico_id !== null) {
            $this->db->where('os.tecnico_responsavel', (int) $tecnico_id);
        }
        $this->db->order_by('nr.data_registro', 'DESC');
        $this->db->limit($limite);
        $query = $this->db->get();

        return $query ? $query->result() : [];
    }

    public function contarPendentes($tecnico_id = null)
    {
        if (! $this->db->table_exists('os_nao_realizada')) {
            return 0;
        }
        $this->db->from('os_nao_realizada nr');
        $this->db->join('os', 'os.idOs = nr.os_id');
        $this->db->where('nr.resolvido', 0);
        $this->db->where('os.status', 'Não Realizado');
        if ($tecnico_id !== null) {
            $this->db->where('os.tecnico_responsavel', (int) $tecnico_id);
        }
        $r = $this->db->count_all_results();

        return ($r !== false) ? $r : 0;
    }

    /**
     * Reagenda a OS: define nova data e devolve à agenda (status "Aberto").
     */
    public function reagendar($idOcorrencia, $nova_data, $resolvido_por)
    {
        $oc = $this->getOcorrencia($idOcorrencia);
        if (! $oc || $oc->resolvido) {
            return false;
        }

        $data = date('Y-m-d', strtotime($nova_data));
        if (! $data) {
            return false;
        }

        $this->db->where('idOcorrencia', (int) $idOcorrencia);
        $this->db->update('os_nao_realizada', [
            'resolvido' => 1,
            'resolucao' => 'reagendado',
            'nova_data' => $data,
            'resolvido_por' => (int) $resolvido_por ?: null,
            'data_resolucao' => date('Y-m-d H:i:s'),
        ]);

        // Devolve a OS à agenda na nova data.
        $this->db->where('idOs', (int) $oc->os_id);
        $this->db->update('os', [
            'status' => 'Aberto',
            'dataInicial' => $data,
        ]);

        return true;
    }

    /**
     * Reabre a OS para refazer, sem alterar a data. Restaura o status que a OS
     * tinha antes (ou "Aberto" se não houver registro).
     */
    public function reabrir($idOcorrencia, $resolvido_por)
    {
        $oc = $this->getOcorrencia($idOcorrencia);
        if (! $oc || $oc->resolvido) {
            return false;
        }

        $statusRestaurar = $oc->status_anterior ?: 'Aberto';
        // Não faz sentido restaurar para um estado terminal.
        if (in_array($statusRestaurar, ['Não Realizado', 'Finalizado', 'Faturado', 'Cancelado'], true)) {
            $statusRestaurar = 'Aberto';
        }

        $this->db->where('idOcorrencia', (int) $idOcorrencia);
        $this->db->update('os_nao_realizada', [
            'resolvido' => 1,
            'resolucao' => 'reaberto',
            'resolvido_por' => (int) $resolvido_por ?: null,
            'data_resolucao' => date('Y-m-d H:i:s'),
        ]);

        $this->db->where('idOs', (int) $oc->os_id);
        $this->db->update('os', ['status' => $statusRestaurar]);

        return true;
    }

    /**
     * Histórico completo de ocorrências de uma OS (para exibir na OS).
     */
    public function getHistoricoPorOs($os_id)
    {
        if (! $this->db->table_exists('os_nao_realizada')) {
            return [];
        }
        $this->db->select('nr.*, usuarios.nome as nome_tecnico');
        $this->db->from('os_nao_realizada nr');
        $this->db->join('usuarios', 'usuarios.idUsuarios = nr.usuarios_id', 'left');
        $this->db->where('nr.os_id', (int) $os_id);
        $this->db->order_by('nr.data_registro', 'DESC');
        $query = $this->db->get();

        return $query ? $query->result() : [];
    }
}
