<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Atividades não programadas com pendência: sem assinatura do solicitante ou
 * sem o ID do chamado nos clientes que o exigem (clientes.exige_chamado).
 *
 * Alimenta a aba "Pendências" da Central de Notificações. A lista é calculada
 * na hora a partir das OS, então some sozinha quando a pendência é resolvida
 * (assinatura coletada ou chamado preenchido na OS).
 */
class Atividade_pendencias_model extends CI_Model
{
    /** Tipos de assinatura que contam como "o solicitante assinou". */
    public const TIPOS_ASSINATURA = ['solicitante', 'cliente_saida', 'cliente_aceite', 'cliente'];

    /** Janela da lista: atividades abertas nos últimos N dias. */
    public const DIAS = 90;

    /**
     * Clientes que exigem chamado, pelo nome. Usado para ligar a flag na
     * migration e como regra enquanto a migration não rodou. Ignora acento
     * e caixa: "Nova Era - Mercantil", "Pátio Gourmet", "TG Foods".
     */
    public static function nomeExigeChamado($nome)
    {
        $n = self::normalizar($nome);
        if ($n === '') {
            return false;
        }
        if (strpos($n, 'nova era') !== false && strpos($n, 'merc') !== false) {
            return true;
        }
        if (strpos($n, 'patio') !== false && strpos($n, 'gourmet') !== false) {
            return true;
        }

        return strpos(str_replace(' ', '', $n), 'tgfood') !== false;
    }

    /** Minúsculas, sem acento e com espaços simples. */
    public static function normalizar($texto)
    {
        $t = function_exists('mb_strtolower') ? mb_strtolower((string) $texto, 'UTF-8') : strtolower((string) $texto);
        $t = strtr($t, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
            'í' => 'i', 'î' => 'i', 'ì' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ò' => 'o', 'ö' => 'o',
            'ú' => 'u', 'û' => 'u', 'ù' => 'u', 'ü' => 'u', 'ç' => 'c',
        ]);

        return trim(preg_replace('/\s+/', ' ', $t));
    }

    /**
     * Falta assinatura só depois que o serviço andou: OS finalizada ou aberta
     * antes de hoje. Na hora em que a atividade é aberta ainda não é pendência.
     */
    public static function faltaAssinatura($temAssinatura, $status, $dataInicial, $hoje = null)
    {
        if ($temAssinatura || $status === 'Cancelado') {
            return false;
        }
        $hoje = $hoje ?: date('Y-m-d');

        return in_array($status, ['Finalizado', 'Faturado'], true)
            || ($dataInicial && substr((string) $dataInicial, 0, 10) < $hoje);
    }

    /** O cliente exige o ID do chamado? */
    public function exigeChamado($clienteId)
    {
        $clienteId = (int) $clienteId;
        if (! $clienteId || ! $this->db->table_exists('clientes')) {
            return false;
        }
        $temFlag = $this->db->field_exists('exige_chamado', 'clientes');
        $c = $this->db->select('nomeCliente' . ($temFlag ? ', exige_chamado' : ''))
            ->where('idClientes', $clienteId)->get('clientes')->row();
        if (! $c) {
            return false;
        }

        return $temFlag ? ! empty($c->exige_chamado) : self::nomeExigeChamado($c->nomeCliente);
    }

    /**
     * Atividades não programadas com pendência, mais recentes primeiro.
     *
     * @return array<int, array{idOs:int, cliente:string, tecnico:?string, data:?string, status:string, chamado:?string, falta_assinatura:bool, falta_chamado:bool}>
     */
    public function listar($limite = 200)
    {
        if (! $this->db->table_exists('os') || ! $this->db->field_exists('nao_programada', 'os')) {
            return [];
        }
        $temChamado = $this->db->field_exists('chamado_id', 'os');
        $temFlag = $this->db->field_exists('exige_chamado', 'clientes');
        $temAceite = $this->db->field_exists('aceite_status', 'os');

        $this->db->select('os.idOs, os.status, os.dataInicial, clientes.nomeCliente, usuarios.nome AS tecnico'
            . ($temChamado ? ', os.chamado_id' : ', NULL AS chamado_id')
            . ($temFlag ? ', clientes.exige_chamado' : ', NULL AS exige_chamado')
            . ($temAceite ? ', os.aceite_status' : ', NULL AS aceite_status'), false);
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id', 'left');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os.tecnico_responsavel', 'left');
        $this->db->where('os.nao_programada', 1);
        $this->db->where('os.status !=', 'Cancelado');
        $this->db->where('os.dataInicial >=', date('Y-m-d', strtotime('-' . self::DIAS . ' days')));
        $this->db->order_by('os.idOs', 'DESC');
        $this->db->limit(1000);
        $rows = $this->db->get()->result();
        if (! $rows) {
            return [];
        }

        $assinadas = [];
        if ($this->db->table_exists('os_assinaturas')) {
            $ids = array_map(static function ($r) {
                return (int) $r->idOs;
            }, $rows);
            foreach ($this->db->select('DISTINCT os_id', false)->where_in('os_id', $ids)
                ->where_in('tipo', self::TIPOS_ASSINATURA)->get('os_assinaturas')->result() as $a) {
                $assinadas[(int) $a->os_id] = true;
            }
        }

        $out = [];
        foreach ($rows as $r) {
            $exige = $temFlag ? ! empty($r->exige_chamado) : self::nomeExigeChamado($r->nomeCliente);
            $temAssinatura = isset($assinadas[(int) $r->idOs]) || $r->aceite_status === 'aceito';
            $faltaAssinatura = self::faltaAssinatura($temAssinatura, $r->status, $r->dataInicial);
            $faltaChamado = $exige && $temChamado && trim((string) $r->chamado_id) === '';
            if (! $faltaAssinatura && ! $faltaChamado) {
                continue;
            }
            $out[] = [
                'idOs' => (int) $r->idOs,
                'cliente' => (string) ($r->nomeCliente ?? ''),
                'tecnico' => $r->tecnico ?: null,
                'data' => $r->dataInicial ?: null,
                'status' => (string) $r->status,
                'chamado' => $r->chamado_id ?: null,
                'falta_assinatura' => $faltaAssinatura,
                'falta_chamado' => $faltaChamado,
            ];
            if (count($out) >= $limite) {
                break;
            }
        }

        return $out;
    }
}
