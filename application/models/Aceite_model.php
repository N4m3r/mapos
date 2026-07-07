<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Model do aceite do serviço REALIZADO (pós-execução) via link temporário.
 *
 * Espelha o Aprovacao_model, mas é independente: o estado fica em colunas
 * `aceite_*` da própria tabela `os` (criadas por updates/update_os_aceite.sql)
 * e NÃO altera o status/orçamento da OS — apenas registra o aceite do cliente,
 * com assinatura digital.
 */
class Aceite_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Só funciona se a migration (updates/update_os_aceite.sql) já foi aplicada.
     */
    public function suportado()
    {
        return $this->db->field_exists('aceite_token', 'os');
    }

    /**
     * Gera (ou regenera) o link de aceite de uma OS, deixando-o pendente.
     *
     * @return array|false ['token' => ..., 'expira' => 'Y-m-d H:i:s'] ou false
     */
    public function gerarLink($osId, $diasValidade = 7)
    {
        if (! $this->suportado()) {
            return false;
        }

        $diasValidade = (int) $diasValidade > 0 ? (int) $diasValidade : 7;
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime("+{$diasValidade} days"));

        $this->db->where('idOs', $osId);
        $this->db->update('os', [
            'aceite_token' => $token,
            'aceite_status' => 'pendente',
            'aceite_expira' => $expira,
            'aceite_data' => null,
            'aceite_nome' => null,
            'aceite_ip' => null,
            'aceite_obs' => null,
            'aceite_assinatura_id' => null,
        ]);

        if ($this->db->affected_rows() < 0) {
            return false;
        }

        return ['token' => $token, 'expira' => $expira];
    }

    /**
     * Revoga o link de aceite ativo de uma OS.
     */
    public function revogarLink($osId)
    {
        if (! $this->suportado()) {
            return false;
        }

        $this->db->where('idOs', $osId);
        $this->db->update('os', [
            'aceite_token' => null,
            'aceite_status' => null,
            'aceite_expira' => null,
        ]);

        return $this->db->affected_rows() >= 0;
    }

    /**
     * Carrega a OS pelo token público, com dados do cliente e do responsável.
     */
    public function getByToken($token)
    {
        if (! $this->suportado() || empty($token)) {
            return null;
        }

        $this->db->select('os.*, clientes.nomeCliente, clientes.documento, clientes.email as email_cliente, clientes.telefone as telefone_cliente, clientes.celular as celular_cliente, usuarios.nome as nome_responsavel');
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id');
        $this->db->join('usuarios', 'usuarios.idUsuarios = os.usuarios_id', 'left');
        $this->db->where('os.aceite_token', $token);
        $this->db->limit(1);

        return $this->db->get()->row();
    }

    /**
     * Situação do link: 'invalido' | 'expirado' | 'pendente' | 'aceito' | 'recusado'.
     */
    public function situacao($os)
    {
        if (! $os || empty($os->aceite_status)) {
            return 'invalido';
        }

        if ($os->aceite_status === 'pendente') {
            if (! empty($os->aceite_expira) && strtotime($os->aceite_expira) < time()) {
                return 'expirado';
            }

            return 'pendente';
        }

        return $os->aceite_status; // aceito | recusado
    }

    /**
     * Registra a decisão do cliente (aceito/recusado) sobre o serviço realizado.
     *
     * @param string   $decisao       'aceito' ou 'recusado'
     * @param int|null $assinaturaId  id em os_assinaturas (quando aceito)
     */
    public function registrarDecisao($osId, $decisao, $nome, $obs, $ip, $assinaturaId = null)
    {
        if (! $this->suportado()) {
            return false;
        }

        $decisao = $decisao === 'aceito' ? 'aceito' : 'recusado';

        $this->db->where('idOs', $osId);
        $this->db->update('os', [
            'aceite_status' => $decisao,
            'aceite_data' => date('Y-m-d H:i:s'),
            'aceite_nome' => mb_substr(trim((string) $nome), 0, 150),
            'aceite_ip' => $ip,
            'aceite_obs' => $obs !== '' ? $obs : null,
            'aceite_assinatura_id' => $assinaturaId ?: null,
        ]);

        return $this->db->affected_rows() >= 0;
    }
}
