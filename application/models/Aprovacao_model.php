<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Model do sistema de aprovação de OS via link temporário.
 *
 * Todo o estado da aprovação fica em colunas da própria tabela `os`
 * (aprovacao_token, aprovacao_status, aprovacao_expira, ...), criadas
 * pela migration updates/update_os_aprovacao.sql.
 */
class Aprovacao_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * O módulo só funciona se a migration já tiver sido aplicada.
     * Evita erro fatal em ambientes ainda não migrados (mesmo padrão do módulo fiscal).
     */
    public function suportado()
    {
        return $this->db->field_exists('aprovacao_token', 'os');
    }

    /**
     * Gera (ou regenera) o link de aprovação de uma OS, deixando-a pendente.
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
            'aprovacao_token' => $token,
            'aprovacao_status' => 'pendente',
            'aprovacao_expira' => $expira,
            'aprovacao_data' => null,
            'aprovacao_nome' => null,
            'aprovacao_ip' => null,
            'aprovacao_obs' => null,
        ]);

        if ($this->db->affected_rows() < 0) {
            return false;
        }

        return ['token' => $token, 'expira' => $expira];
    }

    /**
     * Revoga o link ativo de uma OS (o link para de funcionar).
     */
    public function revogarLink($osId)
    {
        if (! $this->suportado()) {
            return false;
        }

        $this->db->where('idOs', $osId);
        $this->db->update('os', [
            'aprovacao_token' => null,
            'aprovacao_status' => null,
            'aprovacao_expira' => null,
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
        $this->db->where('os.aprovacao_token', $token);
        $this->db->limit(1);

        return $this->db->get()->row();
    }

    /**
     * Situação do link: 'invalido' | 'expirado' | 'pendente' | 'aprovado' | 'reprovado'.
     */
    public function situacao($os)
    {
        if (! $os || empty($os->aprovacao_status)) {
            return 'invalido';
        }

        if ($os->aprovacao_status === 'pendente') {
            if (! empty($os->aprovacao_expira) && strtotime($os->aprovacao_expira) < time()) {
                return 'expirado';
            }

            return 'pendente';
        }

        return $os->aprovacao_status; // aprovado | reprovado
    }

    /**
     * Registra a decisão do cliente (aprovado/reprovado) e atualiza a OS.
     *
     * @param string $decisao 'aprovado' ou 'reprovado'
     */
    public function registrarDecisao($osId, $decisao, $nome, $obs, $ip)
    {
        if (! $this->suportado()) {
            return false;
        }

        $decisao = $decisao === 'aprovado' ? 'aprovado' : 'reprovado';

        $data = [
            'aprovacao_status' => $decisao,
            'aprovacao_data' => date('Y-m-d H:i:s'),
            'aprovacao_nome' => mb_substr(trim($nome), 0, 150),
            'aprovacao_ip' => $ip,
            'aprovacao_obs' => $obs !== '' ? $obs : null,
            // Reflete a decisão no status da OS.
            'status' => $decisao === 'aprovado' ? 'Aprovado' : 'Orçamento',
        ];

        $this->db->where('idOs', $osId);
        $this->db->update('os', $data);

        return $this->db->affected_rows() >= 0;
    }
}
