<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Link publico de acompanhamento da OS e avaliacao NPS do cliente (ADR-007).
 *
 * O link nao expira (o cliente acompanha a OS do inicio ao fim) e mostra so
 * dados de andamento, sem valores nem dados pessoais.
 */
class Acompanhamento_model extends CI_Model
{
    /** Status em que o cliente pode avaliar o atendimento. */
    public const STATUS_AVALIAVEIS = ['Finalizado', 'Faturado'];

    public function disponivel()
    {
        return $this->db->field_exists('acompanhar_token', 'os') && $this->db->table_exists('os_avaliacoes');
    }

    /** Token da OS; gera na primeira chamada. Retorna null se a OS nao existir. */
    public function tokenDaOs($osId)
    {
        if (! $this->disponivel()) {
            return null;
        }
        $os = $this->db->select('idOs, acompanhar_token')->where('idOs', (int) $osId)->get('os', 1)->row();
        if (! $os) {
            return null;
        }
        if (! empty($os->acompanhar_token)) {
            return $os->acompanhar_token;
        }
        $token = bin2hex(random_bytes(32));
        $this->db->where('idOs', $os->idOs)->update('os', ['acompanhar_token' => $token]);

        return $token;
    }

    /** URL publica (encurtada quando possivel) de acompanhamento da OS. */
    public function link($osId)
    {
        $token = $this->tokenDaOs($osId);
        if (! $token) {
            return '';
        }
        $this->load->library('encurtador');

        return $this->encurtador->encurtar(site_url('acompanhar/' . $token));
    }

    public function getByToken($token)
    {
        if (! is_string($token) || ! preg_match('/^[a-f0-9]{64}$/', $token) || ! $this->disponivel()) {
            return null;
        }

        return $this->db->select('os.idOs, os.status, os.dataInicial, os.dataFinal, os.descricaoProduto, os.clientes_id, clientes.nomeCliente, usuarios.nome AS tecnico')
            ->from('os')
            ->join('clientes', 'clientes.idClientes = os.clientes_id', 'left')
            ->join('usuarios', 'usuarios.idUsuarios = os.usuarios_id', 'left')
            ->where('os.acompanhar_token', $token)
            ->limit(1)->get()->row();
    }

    public function avaliacao($osId)
    {
        return $this->db->where('os_id', (int) $osId)->get('os_avaliacoes', 1)->row();
    }

    /** Grava a avaliacao (uma por OS). Retorna false se ja existir ou a nota for invalida. */
    public function avaliar($osId, $nota, $comentario, $ip)
    {
        if (! is_numeric($nota) || (int) $nota < 0 || (int) $nota > 10 || $this->avaliacao($osId)) {
            return false;
        }

        return $this->db->insert('os_avaliacoes', [
            'os_id' => (int) $osId,
            'nota' => (int) $nota,
            'comentario' => mb_substr(trim((string) $comentario), 0, 1000) ?: null,
            'ip' => $ip,
            'criado_em' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Resumo NPS do periodo: total, promotores (9-10), neutros (7-8),
     * detratores (0-6), nps (-100 a 100), por tecnico e a lista de avaliacoes.
     */
    public function resumo($dataInicial, $dataFinal)
    {
        $vazio = ['total' => 0, 'promotores' => 0, 'neutros' => 0, 'detratores' => 0, 'nps' => null, 'porTecnico' => [], 'avaliacoes' => []];
        if (! $this->disponivel()) {
            return $vazio;
        }

        $avaliacoes = $this->db->select('a.*, os.idOs, clientes.nomeCliente, usuarios.nome AS tecnico')
            ->from('os_avaliacoes a')
            ->join('os', 'os.idOs = a.os_id')
            ->join('clientes', 'clientes.idClientes = os.clientes_id', 'left')
            ->join('usuarios', 'usuarios.idUsuarios = os.usuarios_id', 'left')
            ->where('a.criado_em >=', $dataInicial . ' 00:00:00')
            ->where('a.criado_em <=', $dataFinal . ' 23:59:59')
            ->order_by('a.criado_em', 'DESC')
            ->get()->result();

        $r = $vazio;
        $r['avaliacoes'] = $avaliacoes;
        $tecnicos = [];
        foreach ($avaliacoes as $a) {
            $faixa = $a->nota >= 9 ? 'promotores' : ($a->nota >= 7 ? 'neutros' : 'detratores');
            $r[$faixa]++;
            $r['total']++;
            $nome = $a->tecnico ?: '(sem técnico)';
            $tecnicos[$nome] = $tecnicos[$nome] ?? ['total' => 0, 'promotores' => 0, 'detratores' => 0, 'soma' => 0];
            $tecnicos[$nome]['total']++;
            $tecnicos[$nome]['soma'] += $a->nota;
            if ($faixa !== 'neutros') {
                $tecnicos[$nome][$faixa]++;
            }
        }
        if ($r['total']) {
            $r['nps'] = (int) round(($r['promotores'] - $r['detratores']) * 100 / $r['total']);
        }
        foreach ($tecnicos as $nome => $t) {
            $r['porTecnico'][] = [
                'tecnico' => $nome,
                'total' => $t['total'],
                'media' => round($t['soma'] / $t['total'], 1),
                'nps' => (int) round(($t['promotores'] - $t['detratores']) * 100 / $t['total']),
            ];
        }
        usort($r['porTecnico'], fn ($a, $b) => $b['nps'] <=> $a['nps']);

        return $r;
    }
}
