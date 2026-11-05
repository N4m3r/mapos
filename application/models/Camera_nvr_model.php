<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Configuração de câmeras e NVR Hikvision pelo app do técnico.
 *
 * O app descobre os equipamentos na rede local (SADP/ISAPI) e grava aqui o
 * resultado: IP, MAC, canal do NVR e modelo. O cadastro prévio do projeto
 * (tipo=camera / tipo=nvr em obra_pontos) é a base: nenhuma câmera nova é
 * inventada aqui — só se atualiza o ponto que o projeto já previu.
 *
 * Sem a migração aplicada (colunas mac/nvr_id), as consultas degradam para
 * vazio em vez de quebrar.
 */
class Camera_nvr_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('obra_pontos_model');
    }

    /** Comum a projeto e ponto: colunas do ponto, com as da migração quando existem. */
    private function selectPonto()
    {
        $sel = 'idPonto, obra_id, tipo, identificacao, local, status, ip, canal, porta,'
            . ' modelo, serie, observacao, metragem_cabo';
        foreach (['mac', 'nvr_id', 'sadp_id', 'fabricante', 'firmware', 'ativada', 'configurada_em'] as $coluna) {
            $sel .= $this->db->field_exists($coluna, 'obra_pontos')
                ? ', ' . $coluna
                : ', NULL as ' . $coluna;
        }

        return $sel;
    }

    public function disponivel()
    {
        return $this->db->table_exists('obra_pontos');
    }

    /** Projeto pronto para configurar: NVRs previstos, câmeras e o que falta. */
    public function plano($obra_id)
    {
        if (! $this->disponivel()) {
            return ['nvr' => [], 'cameras' => [], 'resumo' => ['total' => 0, 'configuradas' => 0, 'pendentes' => 0]];
        }

        $linhas = $this->db->select($this->selectPonto(), false)
            ->where('obra_id', (int) $obra_id)
            ->where_in('tipo', ['nvr', 'camera'])
            ->order_by('tipo', 'desc')
            ->order_by('identificacao', 'asc')
            ->get('obra_pontos')->result();

        $nvr = [];
        $cameras = [];
        foreach ($linhas as $p) {
            $item = [
                'idPonto' => (int) $p->idPonto,
                'identificacao' => $p->identificacao,
                'tipo' => $p->tipo,
                'local' => $p->local,
                'status' => $p->status,
                'ip' => $p->ip,
                'canal' => $p->canal,
                'porta' => $p->porta,
                'modelo' => $p->modelo,
                'serie' => $p->serie,
                'mac' => $p->mac,
                'nvr_id' => $p->nvr_id !== null ? (int) $p->nvr_id : null,
                'fabricante' => $p->fabricante,
                'firmware' => $p->firmware,
                'ativada' => $p->ativada !== null ? (int) $p->ativada : null,
                'configurada_em' => $p->configurada_em,
                'observacao' => $p->observacao,
                'configurada' => $p->status === 'configurado' || $p->status === 'testado',
            ];
            if ($p->tipo === 'nvr') {
                $nvr[] = $item;
            } else {
                $cameras[] = $item;
            }
        }

        $configuradas = 0;
        foreach ($cameras as $c) {
            if ($c['configurada']) {
                $configuradas++;
            }
        }

        return [
            'nvr' => $nvr,
            'cameras' => $cameras,
            'resumo' => [
                'total' => count($cameras),
                'configuradas' => $configuradas,
                'pendentes' => count($cameras) - $configuradas,
            ],
        ];
    }

    /** Ponto do projeto (ou null se não existe / é de outra obra). */
    public function ponto($obra_id, $ponto_id)
    {
        if (! $this->disponivel()) {
            return null;
        }

        return $this->db->select($this->selectPonto(), false)
            ->where('idPonto', (int) $ponto_id)
            ->where('obra_id', (int) $obra_id)
            ->get('obra_pontos')->row() ?: null;
    }

    /** Canal já usado por outra câmera do mesmo NVR (null se livre). */
    public function canalOcupado($obra_id, $nvr_id, $canal, $exceto_id = 0)
    {
        if (! $this->disponivel() || ! $this->db->field_exists('nvr_id', 'obra_pontos')) {
            return null;
        }
        $this->db->select('idPonto, identificacao')
            ->where('obra_id', (int) $obra_id)
            ->where('nvr_id', (int) $nvr_id)
            ->where('canal', trim((string) $canal));
        if ($exceto_id) {
            $this->db->where('idPonto !=', (int) $exceto_id);
        }

        return $this->db->limit(1)->get('obra_pontos')->row() ?: null;
    }

    /** IP já usado por outro ponto do projeto (null se livre). */
    public function ipOcupado($obra_id, $ip, $exceto_id = 0)
    {
        return $this->obra_pontos_model->outroPontoCom((int) $obra_id, 'ip', trim((string) $ip), (int) $exceto_id);
    }

    /**
     * Grava o resultado da configuração no ponto.
     *
     * $dados: ip, canal, nvr_id, mac, modelo, serie, sadp_id, firmware, ativada,
     *         observacao, status (padrão "configurado").
     *
     * @return array ['ok' => bool, 'erro' => string|null, 'ponto' => object|null]
     */
    public function salvar($obra_id, $ponto_id, array $dados, $usuario_id = null)
    {
        $ponto = $this->ponto($obra_id, $ponto_id);
        if (! $ponto) {
            return ['ok' => false, 'erro' => 'Ponto não encontrado neste projeto.', 'ponto' => null];
        }
        if (! in_array($ponto->tipo, ['camera', 'nvr'], true)) {
            return ['ok' => false, 'erro' => 'Este ponto não é câmera nem NVR.', 'ponto' => null];
        }

        $ip = trim((string) ($dados['ip'] ?? ''));
        $canal = trim((string) ($dados['canal'] ?? ''));
        $porta = trim((string) ($dados['porta'] ?? ''));
        $erroRede = Obra_pontos_model::validarRede($ip, $canal, $porta);
        if ($erroRede !== null) {
            return ['ok' => false, 'erro' => $erroRede, 'ponto' => null];
        }

        if ($ip !== '') {
            $ipRepetido = $this->ipOcupado($obra_id, $ip, (int) $ponto->idPonto);
            if ($ipRepetido) {
                return ['ok' => false, 'erro' => 'O IP ' . $ip . ' já está no ponto ' . $ipRepetido->identificacao . ' deste projeto.', 'ponto' => null];
            }
        }

        // O NVR informado tem de ser um ponto nvr do mesmo projeto.
        $temNvr = isset($dados['nvr_id']) && $dados['nvr_id'] !== '' && $dados['nvr_id'] !== null;
        $nvrId = $temNvr ? (int) $dados['nvr_id'] : null;
        if ($nvrId !== null) {
            $nvr = $this->ponto($obra_id, $nvrId);
            if (! $nvr || $nvr->tipo !== 'nvr' || (int) $nvr->idPonto === (int) $ponto->idPonto) {
                return ['ok' => false, 'erro' => 'NVR inválido para este projeto.', 'ponto' => null];
            }
        }

        if ($ponto->tipo === 'camera' && $nvrId !== null && $canal !== '') {
            $ocupado = $this->canalOcupado($obra_id, $nvrId, $canal, (int) $ponto->idPonto);
            if ($ocupado) {
                return ['ok' => false, 'erro' => 'O canal ' . $canal . ' já é da câmera ' . $ocupado->identificacao . '.', 'ponto' => null];
            }
        }

        $statusLista = Obra_pontos_model::statusLista();
        $status = (string) ($dados['status'] ?? 'configurado');
        if (! isset($statusLista[$status])) {
            $status = 'configurado';
        }

        $extras = [];
        foreach (['ip', 'canal', 'porta', 'modelo', 'serie', 'observacao'] as $campo) {
            if (isset($dados[$campo]) && trim((string) $dados[$campo]) !== '') {
                $extras[$campo] = trim((string) $dados[$campo]);
            }
        }

        $ok = $this->obra_pontos_model->mudarStatus(
            (int) $ponto->idPonto,
            (int) $obra_id,
            $status,
            $usuario_id !== null ? (int) $usuario_id : null,
            $extras
        );
        if (! $ok) {
            return ['ok' => false, 'erro' => 'Não foi possível atualizar o ponto.', 'ponto' => null];
        }

        // Dados que o histórico de status não guarda (colunas da migração).
        $tecnicos = [
            'mac' => $this->cortar($dados['mac'] ?? null, 20),
            'sadp_id' => $this->cortar($dados['sadp_id'] ?? null, 60),
            'fabricante' => $this->cortar($dados['fabricante'] ?? null, 30),
            'firmware' => $this->cortar($dados['firmware'] ?? null, 80),
        ];
        if ($ponto->tipo === 'camera') {
            $tecnicos['nvr_id'] = $nvrId;
        }
        if (array_key_exists('ativada', $dados) && $dados['ativada'] !== null && $dados['ativada'] !== '') {
            $tecnicos['ativada'] = $dados['ativada'] ? 1 : 0;
        }
        if ($status === 'configurado' || $status === 'testado') {
            $tecnicos['configurada_em'] = date('Y-m-d H:i:s');
        }
        if ($this->db->field_exists('mac', 'obra_pontos')) {
            $tecnicos = array_filter($tecnicos, static function ($v) {
                return $v !== null;
            });
            if ($tecnicos) {
                $this->obra_pontos_model->editPonto((int) $ponto->idPonto, (int) $obra_id, $tecnicos);
            }
        }

        log_info('API: configuração de ' . $ponto->tipo . ' salva no ponto ' . $ponto->idPonto . ' do projeto ' . $obra_id);

        return ['ok' => true, 'erro' => null, 'ponto' => $this->ponto($obra_id, $ponto->idPonto)];
    }

    /** Próxima identificação livre do tipo no projeto (CAM-07 quando CAM-01..06 existem). */
    public function proximaIdentificacao($obra_id, $tipo)
    {
        $tipos = Obra_pontos_model::tipos();
        if (! isset($tipos[$tipo])) {
            $tipo = 'camera';
        }
        $prefixo = $tipos[$tipo]['prefixo'];

        $existentes = $this->db->select('identificacao')
            ->where('obra_id', (int) $obra_id)
            ->like('identificacao', $prefixo . '-', 'after')
            ->get('obra_pontos')->result();

        $maior = 0;
        foreach ($existentes as $e) {
            if (preg_match('/-(\d+)$/', (string) $e->identificacao, $m)) {
                $maior = max($maior, (int) $m[1]);
            }
        }

        $ident = '';
        foreach (Obra_pontos_model::gerarIdentificacoes($prefixo, $maior + 1, 1) as $gerada) {
            $ident = $gerada;
        }

        return $ident;
    }

    /**
     * Cria um ponto novo a partir de uma câmera descoberta na rede (quando o
     * projeto não previu todos os pontos). A câmera nasce "configurada".
     *
     * @return array ['ok' => bool, 'erro' => string|null, 'ponto' => object|null, 'idPonto' => int|null]
     */
    public function criarDoDescoberto($obra_id, array $dados, $usuario_id = null)
    {
        $tipo = (string) ($dados['tipo'] ?? 'camera');
        if (! in_array($tipo, ['camera', 'nvr'], true)) {
            $tipo = 'camera';
        }

        $ident = trim((string) ($dados['identificacao'] ?? ''));
        if ($ident === '') {
            $ident = $this->proximaIdentificacao($obra_id, $tipo);
        }
        $ident = function_exists('mb_substr') ? mb_substr($ident, 0, 30) : substr($ident, 0, 30);

        $jaExiste = $this->db->select('idPonto')->where('obra_id', (int) $obra_id)
            ->where('identificacao', $ident)->get('obra_pontos')->row();
        if ($jaExiste) {
            return ['ok' => false, 'erro' => 'Já existe um ponto ' . $ident . ' neste projeto.', 'ponto' => null, 'idPonto' => null];
        }

        $ip = trim((string) ($dados['ip'] ?? ''));
        $erroRede = Obra_pontos_model::validarRede($ip, '', '');
        if ($erroRede !== null) {
            return ['ok' => false, 'erro' => $erroRede, 'ponto' => null, 'idPonto' => null];
        }
        if ($ip !== '' && $this->ipOcupado($obra_id, $ip)) {
            return ['ok' => false, 'erro' => 'O IP ' . $ip . ' já está em outro ponto deste projeto.', 'ponto' => null, 'idPonto' => null];
        }

        $agora = date('Y-m-d H:i:s');
        $idPonto = (int) $this->obra_pontos_model->addPonto([
            'obra_id' => (int) $obra_id,
            'tipo' => $tipo,
            'identificacao' => $ident,
            'local' => $this->cortar($dados['local'] ?? null, 150),
            'ip' => $ip !== '' ? $ip : null,
            'canal' => $this->cortar($dados['canal'] ?? null, 10),
            'modelo' => $this->cortar($dados['modelo'] ?? null, 80),
            'serie' => $this->cortar($dados['serie'] ?? null, 60),
            'status' => 'configurado',
            'metragem_cabo' => 0,
            'atualizado_por' => $usuario_id !== null ? (int) $usuario_id : null,
            'atualizado_em' => $agora,
            'data_cadastro' => $agora,
        ]);
        if (! $idPonto) {
            return ['ok' => false, 'erro' => 'Não foi possível criar o ponto.', 'ponto' => null, 'idPonto' => null];
        }

        if ($this->db->field_exists('mac', 'obra_pontos')) {
            $tecnicos = [
                'mac' => $this->cortar($dados['mac'] ?? null, 20),
                'sadp_id' => $this->cortar($dados['sadp_id'] ?? null, 60),
                'fabricante' => $this->cortar($dados['fabricante'] ?? null, 30),
                'firmware' => $this->cortar($dados['firmware'] ?? null, 80),
                'ativada' => ! empty($dados['ativada']) ? 1 : 0,
                'configurada_em' => $agora,
            ];
            if ($tipo === 'camera' && ! empty($dados['nvr_id'])) {
                $tecnicos['nvr_id'] = (int) $dados['nvr_id'];
            }
            $tecnicos = array_filter($tecnicos, static function ($v) {
                return $v !== null;
            });
            $this->obra_pontos_model->editPonto($idPonto, (int) $obra_id, $tecnicos);
        }

        log_info('API: ponto ' . $ident . ' criado da descoberta no projeto ' . $obra_id);

        return ['ok' => true, 'erro' => null, 'ponto' => $this->ponto($obra_id, $idPonto), 'idPonto' => $idPonto];
    }

    private function cortar($texto, $max)
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return null;
        }

        return function_exists('mb_substr') ? mb_substr($texto, 0, (int) $max) : substr($texto, 0, (int) $max);
    }
}
