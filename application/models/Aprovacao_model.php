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
     * A verificação por código (token) só funciona após a migration
     * 20260712000002 (coluna aprovacao_exige_token na OS).
     */
    public function suportaVerificacao()
    {
        return $this->db->field_exists('aprovacao_exige_token', 'os');
    }

    /** Máximo de tentativas de código antes de bloquear. */
    const MAX_TENTATIVAS_CODIGO = 5;

    /** Validade do código de verificação, em minutos. */
    const MINUTOS_CODIGO = 15;

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

        $dados = [
            'aprovacao_token' => $token,
            'aprovacao_status' => 'pendente',
            'aprovacao_expira' => $expira,
            'aprovacao_data' => null,
            'aprovacao_nome' => null,
            'aprovacao_ip' => null,
            'aprovacao_obs' => null,
        ];

        // Um novo link zera qualquer verificação anterior — o cliente terá de
        // validar o código novamente.
        if ($this->suportaVerificacao()) {
            $dados['aprovacao_codigo'] = null;
            $dados['aprovacao_codigo_expira'] = null;
            $dados['aprovacao_codigo_validado'] = 0;
            $dados['aprovacao_codigo_tentativas'] = 0;
            $dados['aprovacao_codigo_canal'] = null;
        }

        $this->db->where('idOs', $osId);
        $this->db->update('os', $dados);

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

        $select = 'os.*, clientes.nomeCliente, clientes.documento, clientes.email as email_cliente, clientes.telefone as telefone_cliente, clientes.celular as celular_cliente, usuarios.nome as nome_responsavel';
        // Flag de exigência de código no cadastro do cliente (pode não existir
        // ainda em ambientes sem a migration de verificação).
        if ($this->db->field_exists('aprovacao_exige_token', 'clientes')) {
            $select .= ', clientes.aprovacao_exige_token as cliente_exige_token';
        }
        // Números extras (WhatsApp) que também recebem o código.
        if ($this->db->field_exists('aprovacao_token_numeros', 'clientes')) {
            $select .= ', clientes.aprovacao_token_numeros as cliente_token_numeros';
        }
        $this->db->select($select);
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

    /* ================================================================== */
    /*  Verificação por código (token) — opcional por cliente e/ou por OS  */
    /* ================================================================== */

    /**
     * A OS exige código de verificação? Verdadeiro se a flag estiver ligada
     * na própria OS OU no cadastro do cliente.
     */
    public function exigeToken($os)
    {
        if (! $os || ! $this->suportaVerificacao()) {
            return false;
        }

        $naOs = (int) ($os->aprovacao_exige_token ?? 0) === 1;
        $noCliente = (int) ($os->cliente_exige_token ?? 0) === 1;

        return $naOs || $noCliente;
    }

    /** O cliente já validou o código desta OS? */
    public function codigoValidado($os)
    {
        return $os && (int) ($os->aprovacao_codigo_validado ?? 0) === 1;
    }

    /**
     * Liga/desliga a exigência de código diretamente na OS.
     */
    public function setExigeTokenOs($osId, $exige)
    {
        if (! $this->suportaVerificacao()) {
            return false;
        }

        $this->db->where('idOs', $osId);
        $this->db->update('os', ['aprovacao_exige_token' => $exige ? 1 : 0]);

        return $this->db->affected_rows() >= 0;
    }

    /**
     * Salva os números extras (WhatsApp) desta OS (texto livre, um por linha).
     */
    public function setTokenNumerosOs($osId, $texto)
    {
        if (! $this->db->field_exists('aprovacao_token_numeros', 'os')) {
            return false;
        }

        $texto = trim((string) $texto);
        $this->db->where('idOs', $osId);
        $this->db->update('os', ['aprovacao_token_numeros' => $texto !== '' ? $texto : null]);

        return $this->db->affected_rows() >= 0;
    }

    /**
     * Extrai números de telefone de um texto livre (linhas, vírgulas ou ponto
     * e vírgula). Mantém só os dígitos e descarta entradas curtas demais
     * (menos que DDD + número).
     *
     * @return string[] lista de números só com dígitos
     */
    public static function parseNumeros($texto)
    {
        $numeros = [];
        foreach (preg_split('/[\r\n,;]+/', (string) $texto) as $parte) {
            $digitos = preg_replace('/\D/', '', $parte);
            if (strlen($digitos) >= 10) {
                $numeros[] = $digitos;
            }
        }

        return $numeros;
    }

    /**
     * Todos os destinos de WhatsApp que devem receber o código: celular do
     * cliente + números extras do cliente + números extras da OS. Sem repetição.
     *
     * @return string[] números só com dígitos
     */
    public function numerosDestino($os)
    {
        if (! $os) {
            return [];
        }

        $lista = [];
        $celular = preg_replace('/\D/', '', (string) ($os->celular_cliente ?? ''));
        if (strlen($celular) >= 10) {
            $lista[] = $celular;
        }
        $lista = array_merge($lista, self::parseNumeros($os->cliente_token_numeros ?? ''));
        $lista = array_merge($lista, self::parseNumeros($os->aprovacao_token_numeros ?? ''));

        return array_values(array_unique($lista));
    }

    /**
     * Gera um novo código de 6 dígitos, guarda o hash e a validade e zera o
     * estado de validação. Retorna o código em texto puro (para envio).
     */
    public function gerarCodigo($osId)
    {
        if (! $this->suportaVerificacao()) {
            return false;
        }

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->db->where('idOs', $osId);
        $this->db->update('os', [
            'aprovacao_codigo' => hash('sha256', $codigo),
            'aprovacao_codigo_expira' => date('Y-m-d H:i:s', strtotime('+' . self::MINUTOS_CODIGO . ' minutes')),
            'aprovacao_codigo_validado' => 0,
            'aprovacao_codigo_tentativas' => 0,
            'aprovacao_codigo_canal' => null,
        ]);

        return $codigo;
    }

    /**
     * Registra por qual canal o código foi enviado (whatsapp/email), só para
     * exibição ao cliente.
     */
    public function registrarCanalCodigo($osId, $canal)
    {
        if (! $this->suportaVerificacao()) {
            return;
        }
        $this->db->where('idOs', $osId);
        $this->db->update('os', ['aprovacao_codigo_canal' => mb_substr((string) $canal, 0, 20)]);
    }

    /**
     * Valida o código informado pelo cliente.
     *
     * @return string 'ok' | 'invalido' | 'expirado' | 'bloqueado' | 'sem_codigo'
     */
    public function validarCodigo($osId, $codigo)
    {
        if (! $this->suportaVerificacao()) {
            return 'sem_codigo';
        }

        $this->db->select('aprovacao_codigo, aprovacao_codigo_expira, aprovacao_codigo_tentativas');
        $this->db->from('os');
        $this->db->where('idOs', $osId);
        $os = $this->db->get()->row();

        if (! $os || empty($os->aprovacao_codigo)) {
            return 'sem_codigo';
        }

        if ((int) $os->aprovacao_codigo_tentativas >= self::MAX_TENTATIVAS_CODIGO) {
            return 'bloqueado';
        }

        if (empty($os->aprovacao_codigo_expira) || strtotime($os->aprovacao_codigo_expira) < time()) {
            return 'expirado';
        }

        // Conta a tentativa antes de comparar.
        $this->db->set('aprovacao_codigo_tentativas', 'aprovacao_codigo_tentativas + 1', false);
        $this->db->where('idOs', $osId);
        $this->db->update('os');

        $informado = hash('sha256', preg_replace('/\D/', '', (string) $codigo));
        if (! hash_equals($os->aprovacao_codigo, $informado)) {
            return 'invalido';
        }

        $this->db->where('idOs', $osId);
        $this->db->update('os', ['aprovacao_codigo_validado' => 1]);

        return 'ok';
    }
}
