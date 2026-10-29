<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pontos do projeto de seguranca eletronica (ADR-013): cameras, leitores,
 * pontos de rede etc., com status de instalacao e historico.
 *
 * As funcoes estaticas sao puras (sem banco). As consultas sao resilientes:
 * sem a migracao aplicada devolvem vazio.
 */
class Obra_pontos_model extends CI_Model
{
    /* ====================== Funcoes puras ====================== */

    public static function tipos()
    {
        return [
            'camera' => ['rotulo' => 'Câmera', 'prefixo' => 'CAM', 'icone' => 'bx-cctv'],
            'ponto_rede' => ['rotulo' => 'Ponto de rede', 'prefixo' => 'PR', 'icone' => 'bx-network-chart'],
            'leitor_acesso' => ['rotulo' => 'Leitor / controle de acesso', 'prefixo' => 'LEI', 'icone' => 'bx-id-card'],
            'fechadura' => ['rotulo' => 'Fechadura / eletroímã', 'prefixo' => 'FEC', 'icone' => 'bx-lock-alt'],
            'botoeira' => ['rotulo' => 'Botoeira', 'prefixo' => 'BOT', 'icone' => 'bx-radio-circle-marked'],
            'sensor' => ['rotulo' => 'Sensor de alarme', 'prefixo' => 'SEN', 'icone' => 'bx-bell'],
            'interfone' => ['rotulo' => 'Interfone / vídeo porteiro', 'prefixo' => 'INT', 'icone' => 'bx-phone-call'],
            'ap_wifi' => ['rotulo' => 'Access point', 'prefixo' => 'AP', 'icone' => 'bx-wifi'],
            'rack' => ['rotulo' => 'Rack', 'prefixo' => 'RACK', 'icone' => 'bx-server'],
            'switch' => ['rotulo' => 'Switch', 'prefixo' => 'SW', 'icone' => 'bx-sitemap'],
            'nvr' => ['rotulo' => 'NVR / DVR', 'prefixo' => 'NVR', 'icone' => 'bx-hdd'],
            'outro' => ['rotulo' => 'Outro', 'prefixo' => 'PT', 'icone' => 'bx-package'],
        ];
    }

    public static function statusLista()
    {
        return [
            'previsto' => ['rotulo' => 'Previsto', 'peso' => 0, 'acao' => '—', 'classe' => 'neutro'],
            'infra' => ['rotulo' => 'Infraestrutura pronta', 'peso' => 20, 'acao' => 'Infra pronta', 'classe' => 'info'],
            'cabeado' => ['rotulo' => 'Cabo passado', 'peso' => 40, 'acao' => 'Cabo passado', 'classe' => 'info'],
            'instalado' => ['rotulo' => 'Instalado', 'peso' => 70, 'acao' => 'Instalei', 'classe' => 'alerta'],
            'configurado' => ['rotulo' => 'Configurado', 'peso' => 90, 'acao' => 'Configurei', 'classe' => 'alerta'],
            'testado' => ['rotulo' => 'Testado / certificado', 'peso' => 100, 'acao' => 'Testei', 'classe' => 'sucesso'],
        ];
    }

    /** Proximo status na ordem; null no ultimo ou se desconhecido. */
    public static function proximoStatus($atual)
    {
        $chaves = array_keys(self::statusLista());
        $i = array_search((string) $atual, $chaves, true);
        if ($i === false || ! isset($chaves[$i + 1])) {
            return null;
        }
        return $chaves[$i + 1];
    }

    /** Le campo de array ou objeto. */
    private static function campo($item, $nome, $padrao = null)
    {
        if (is_array($item)) {
            return $item[$nome] ?? $padrao;
        }
        if (is_object($item)) {
            return $item->$nome ?? $padrao;
        }
        return $padrao;
    }

    /** Media dos pesos dos status (1 casa); null se nao ha pontos. */
    public static function progresso(array $pontos)
    {
        if (! $pontos) {
            return null;
        }
        $lista = self::statusLista();
        $soma = 0;
        foreach ($pontos as $p) {
            $st = (string) self::campo($p, 'status', 'previsto');
            $soma += isset($lista[$st]) ? $lista[$st]['peso'] : 0;
        }
        return round($soma / count($pontos), 1);
    }

    public static function resumo(array $pontos)
    {
        $porStatusVazio = array_fill_keys(array_keys(self::statusLista()), 0);
        $tipos = self::tipos();
        $tmp = [];
        $porStatus = $porStatusVazio;
        $cabo = 0.0;

        foreach ($pontos as $p) {
            $st = (string) self::campo($p, 'status', 'previsto');
            $tp = (string) self::campo($p, 'tipo', 'outro');
            if (! isset($tipos[$tp])) {
                $tp = 'outro';
            }
            if (isset($porStatus[$st])) {
                $porStatus[$st]++;
            }
            if (! isset($tmp[$tp])) {
                $tmp[$tp] = ['rotulo' => $tipos[$tp]['rotulo'], 'total' => 0, 'testados' => 0, 'por_status' => $porStatusVazio];
            }
            $tmp[$tp]['total']++;
            if ($st === 'testado') {
                $tmp[$tp]['testados']++;
            }
            if (isset($tmp[$tp]['por_status'][$st])) {
                $tmp[$tp]['por_status'][$st]++;
            }
            $cabo += (float) self::campo($p, 'metragem_cabo', 0);
        }

        // Somente tipos presentes, na ordem de tipos()
        $porTipo = [];
        foreach ($tipos as $chave => $_) {
            if (isset($tmp[$chave])) {
                $porTipo[$chave] = $tmp[$chave];
            }
        }

        return [
            'total' => count($pontos),
            'por_status' => $porStatus,
            'por_tipo' => $porTipo,
            'progresso' => self::progresso($pontos),
            'cabo_m' => round($cabo, 2),
        ];
    }

    /** ['CAM-01', 'CAM-02', ...]; 2 digitos, 3 se o ultimo passar de 99. */
    public static function gerarIdentificacoes($prefixo, $inicio, $qtd)
    {
        $qtd = max(1, min(500, (int) $qtd));
        $inicio = max(1, (int) $inicio);
        $largura = ($inicio + $qtd - 1) > 99 ? 3 : 2;
        $prefixo = trim((string) $prefixo);
        $lista = [];
        for ($n = $inicio; $n < $inicio + $qtd; $n++) {
            $lista[] = $prefixo . '-' . str_pad((string) $n, $largura, '0', STR_PAD_LEFT);
        }
        return $lista;
    }

    /** Minusculas e sem acento, sem depender da extensao intl. */
    private static function normalizar($texto)
    {
        $mapa = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c',
            'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a',
            'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e',
            'Í' => 'i', 'Ì' => 'i', 'Î' => 'i', 'Ï' => 'i',
            'Ó' => 'o', 'Ò' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o',
            'Ú' => 'u', 'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u', 'Ç' => 'c',
        ];
        return strtolower(strtr((string) $texto, $mapa));
    }

    public static function etapasPadrao($tipoProjeto)
    {
        $t = self::normalizar($tipoProjeto);
        $contem = function (array $palavras) use ($t) {
            foreach ($palavras as $p) {
                if (strpos($t, $p) !== false) {
                    return true;
                }
            }
            return false;
        };

        $listas = [];
        if ($contem(['cftv', 'camera'])) {
            $listas[] = [
                'Infraestrutura (eletroduto/eletrocalha)', 'Passagem de cabos', 'Montagem do rack',
                'Instalação das câmeras', 'Configuração do NVR e acesso remoto',
                'Testes e ajuste de imagem', 'Entrega e treinamento',
            ];
        }
        if ($contem(['acesso'])) {
            $listas[] = [
                'Infraestrutura', 'Passagem de cabos', 'Instalação de leitores, fechaduras e botoeiras',
                'Instalação e configuração da controladora', 'Cadastro de usuários e regras',
                'Testes', 'Entrega e treinamento',
            ];
        }
        if ($contem(['rede', 'fibra'])) {
            $listas[] = [
                'Infraestrutura', 'Passagem de cabos', 'Montagem do rack e patch panel', 'Conectorização',
                'Certificação dos pontos', 'Identificação e documentação', 'Entrega',
            ];
        }
        if ($contem(['alarme'])) {
            $listas[] = [
                'Infraestrutura', 'Passagem de cabos', 'Instalação de sensores e central',
                'Programação e monitoramento', 'Testes', 'Entrega e treinamento',
            ];
        }
        if (! $listas) {
            $listas[] = ['Infraestrutura', 'Passagem de cabos', 'Instalação', 'Configuração', 'Testes', 'Entrega'];
        }

        // Junta sem repetir nomes, mantendo a primeira ocorrencia
        $nomes = [];
        foreach ($listas as $lista) {
            foreach ($lista as $nome) {
                if (! in_array($nome, $nomes, true)) {
                    $nomes[] = $nome;
                }
            }
        }
        return $nomes;
    }

    /** O que o ponto passa a aguardar depois de ir para $status (ADR-014). */
    public static function aguardandoApos($status)
    {
        return self::proximoStatus($status);
    }

    public static function rotuloAguardando($aguardando)
    {
        $mapa = [
            'infra' => 'Aguardando infraestrutura',
            'cabeado' => 'Aguardando passagem de cabo',
            'instalado' => 'Aguardando instalação',
            'configurado' => 'Aguardando configuração',
            'testado' => 'Aguardando teste',
        ];
        return $mapa[(string) $aguardando] ?? '';
    }

    /** Foto obrigatoria so para marcar "Instalado" no app do tecnico. */
    public static function exigeFoto($status)
    {
        return (string) $status === 'instalado';
    }

    /** MIME pelos bytes iniciais (jpeg, png, webp); null se nao for imagem ou passar de 4 MB. */
    public static function fotoValida($binario)
    {
        $binario = (string) $binario;
        $tam = strlen($binario);
        if ($tam < 12 || $tam > 4 * 1024 * 1024) {
            return null;
        }
        if (substr($binario, 0, 3) === "\xFF\xD8\xFF") {
            return 'image/jpeg';
        }
        if (substr($binario, 0, 8) === "\x89PNG\r\n\x1A\n") {
            return 'image/png';
        }
        if (substr($binario, 0, 4) === 'RIFF' && substr($binario, 8, 4) === 'WEBP') {
            return 'image/webp';
        }
        return null;
    }

    /* ====================== Consultas ====================== */

    public function disponivel()
    {
        return $this->db->table_exists('obra_pontos');
    }

    private function temRepasse()
    {
        return $this->db->table_exists('obra_pontos') && $this->db->field_exists('responsavel_id', 'obra_pontos');
    }

    private function temFotos()
    {
        return $this->db->table_exists('obra_ponto_foto');
    }

    private function cortar($texto, $max)
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return null;
        }
        return mb_substr($texto, 0, $max);
    }

    /** SELECT/JOIN comuns de ponto (alias p), com nomes e contagem de fotos sem trazer o blob. */
    private function baseSelectPontos($extra = '')
    {
        $sel = 'p.*';
        if ($this->temRepasse()) {
            $sel .= ', ur.nome as responsavel_nome, urp.nome as repassado_por_nome';
        } else {
            $sel .= ', NULL as responsavel_nome, NULL as repassado_por_nome';
        }
        $sel .= $this->temFotos()
            ? ', (SELECT COUNT(*) FROM obra_ponto_foto f WHERE f.ponto_id = p.idPonto) as qtd_fotos'
            : ', 0 as qtd_fotos';
        $this->db->select($sel . $extra, false);
        $this->db->from('obra_pontos p');
        if ($this->temRepasse()) {
            $this->db->join('usuarios ur', 'ur.idUsuarios = p.responsavel_id', 'left');
            $this->db->join('usuarios urp', 'urp.idUsuarios = p.repassado_por', 'left');
        }
    }

    public function getPontos($obra_id, $filtros = [])
    {
        if (! $this->db->table_exists('obra_pontos')) {
            return [];
        }
        $this->baseSelectPontos();
        $this->db->where('p.obra_id', (int) $obra_id);
        if (! empty($filtros['tipo'])) {
            $this->db->where('p.tipo', $filtros['tipo']);
        }
        if (! empty($filtros['status'])) {
            $this->db->where('p.status', $filtros['status']);
        }
        if ($this->temRepasse()) {
            if (! empty($filtros['aguardando'])) {
                $this->db->where('p.aguardando', $filtros['aguardando']);
            }
            if (isset($filtros['responsavel_id']) && $filtros['responsavel_id'] !== '') {
                if ($filtros['responsavel_id'] === 'equipe') {
                    $this->db->where('p.responsavel_id IS NULL', null, false);
                    $this->db->where('p.aguardando IS NOT NULL', null, false);
                } else {
                    $this->db->where('p.responsavel_id', (int) $filtros['responsavel_id']);
                }
            }
        }
        $chaves = array_keys(self::tipos());
        $ordem = 'CASE p.tipo';
        foreach ($chaves as $i => $k) {
            $ordem .= ' WHEN ' . $this->db->escape($k) . ' THEN ' . $i;
        }
        $ordem .= ' ELSE ' . count($chaves) . ' END';
        $this->db->order_by($ordem, '', false);
        $this->db->order_by('p.identificacao', 'asc');
        return $this->db->get()->result();
    }

    public function getPonto($id)
    {
        if (! $this->db->table_exists('obra_pontos')) {
            return null;
        }
        $this->baseSelectPontos();
        $row = $this->db->where('p.idPonto', (int) $id)->get()->row();
        return $row ?: null;
    }

    public function addPonto($d)
    {
        if (! $this->db->table_exists('obra_pontos')) {
            return 0;
        }
        if (empty($d['data_cadastro'])) {
            $d['data_cadastro'] = date('Y-m-d H:i:s');
        }
        $this->db->insert('obra_pontos', $d);
        $id = (int) $this->db->insert_id();
        if (! empty($d['obra_id'])) {
            $this->recalcularProgressoObra($d['obra_id']);
        }
        return $id;
    }

    public function editPonto($id, $obra_id, $d)
    {
        if (! $this->db->table_exists('obra_pontos')) {
            return false;
        }
        unset($d['idPonto'], $d['obra_id']);
        $ok = (bool) $this->db->where('idPonto', (int) $id)->where('obra_id', (int) $obra_id)->update('obra_pontos', $d);
        $this->recalcularProgressoObra($obra_id);
        return $ok;
    }

    public function excluirPonto($id, $obra_id)
    {
        if (! $this->db->table_exists('obra_pontos')) {
            return false;
        }
        $existe = $this->db->where('idPonto', (int) $id)->where('obra_id', (int) $obra_id)->count_all_results('obra_pontos');
        if (! $existe) {
            return false;
        }
        if ($this->db->table_exists('obra_ponto_historico')) {
            $this->db->where('ponto_id', (int) $id)->delete('obra_ponto_historico');
        }
        if ($this->temFotos()) {
            $this->db->where('ponto_id', (int) $id)->delete('obra_ponto_foto');
        }
        $this->db->where('idPonto', (int) $id)->where('obra_id', (int) $obra_id)->delete('obra_pontos');
        $this->recalcularProgressoObra($obra_id);
        return true;
    }

    /** Cria os pontos que ainda nao existem (mesma identificacao no projeto); devolve quantos criou. */
    public function gerarLote($obra_id, $tipo, $prefixo, $inicio, $qtd, $local = null)
    {
        if (! $this->db->table_exists('obra_pontos')) {
            return 0;
        }
        $obra_id = (int) $obra_id;
        $existentes = [];
        foreach ($this->db->select('identificacao')->where('obra_id', $obra_id)->get('obra_pontos')->result() as $r) {
            $existentes[$r->identificacao] = true;
        }
        $agora = date('Y-m-d H:i:s');
        $criados = 0;
        foreach (self::gerarIdentificacoes($prefixo, $inicio, $qtd) as $ident) {
            if (isset($existentes[$ident])) {
                continue;
            }
            $this->db->insert('obra_pontos', [
                'obra_id' => $obra_id,
                'tipo' => $tipo,
                'identificacao' => $ident,
                'local' => ($local !== null && $local !== '') ? $local : null,
                'status' => 'previsto',
                'metragem_cabo' => 0,
                'data_cadastro' => $agora,
            ]);
            $criados++;
        }
        if ($criados) {
            $this->recalcularProgressoObra($obra_id);
        }
        return $criados;
    }

    /** Muda o status do ponto, grava historico e recalcula o progresso do projeto. */
    public function mudarStatus($ponto_id, $obra_id, $status, $usuario_id, $extras = [])
    {
        if (! $this->db->table_exists('obra_pontos')) {
            return false;
        }
        if (! isset(self::statusLista()[$status])) {
            return false;
        }
        $ponto = $this->db->where('idPonto', (int) $ponto_id)->where('obra_id', (int) $obra_id)->get('obra_pontos')->row();
        if (! $ponto) {
            return false;
        }

        $agora = date('Y-m-d H:i:s');
        $upd = ['status' => $status, 'atualizado_por' => $usuario_id, 'atualizado_em' => $agora];
        $temDados = false;
        foreach (['ip', 'canal', 'porta', 'modelo', 'serie', 'local', 'observacao'] as $campo) {
            if (isset($extras[$campo]) && trim((string) $extras[$campo]) !== '') {
                $upd[$campo] = trim((string) $extras[$campo]);
                $temDados = true;
            }
        }
        $metros = isset($extras['metragem_cabo']) ? max(0, round((float) str_replace(',', '.', (string) $extras['metragem_cabo']), 2)) : 0;
        if ($metros > 0) {
            $upd['metragem_cabo'] = round((float) $ponto->metragem_cabo + $metros, 2);
        }

        // Sem mudanca, sem metros e sem dados: nada a gravar
        if ($ponto->status === $status && $metros <= 0 && ! $temDados) {
            $this->recalcularProgressoObra($obra_id);
            return true;
        }

        if ($this->temRepasse() && $ponto->status !== $status) {
            if ($status === 'testado') {
                $upd['aguardando'] = null;
                $upd['responsavel_id'] = null;
                $upd['instrucao'] = null;
            } else {
                $upd['aguardando'] = self::aguardandoApos($status);
                $upd['responsavel_id'] = $usuario_id !== null ? (int) $usuario_id : null;
            }
        }
        $this->db->where('idPonto', (int) $ponto_id)->update('obra_pontos', $upd);

        if ($this->db->table_exists('obra_ponto_historico')) {
            $hist = [
                'ponto_id' => (int) $ponto_id,
                'obra_id' => (int) $obra_id,
                'status_anterior' => $ponto->status,
                'status_novo' => $status,
                'metragem_cabo' => $metros,
                'usuario_id' => $usuario_id,
                'data' => $agora,
                'observacao' => $upd['observacao'] ?? null,
            ];
            if ($this->db->field_exists('evento', 'obra_ponto_historico')) {
                $hist['evento'] = 'status';
            }
            $this->db->insert('obra_ponto_historico', $hist);
        }

        $this->recalcularProgressoObra($obra_id);
        return true;
    }

    public function ultimoHistoricoId($ponto_id)
    {
        if (! $this->db->table_exists('obra_ponto_historico')) {
            return null;
        }
        $r = $this->db->select_max('idHistorico', 'm')->where('ponto_id', (int) $ponto_id)->get('obra_ponto_historico')->row();
        return ($r && $r->m !== null) ? (int) $r->m : null;
    }

    /** Usuarios ativos que podem executar pontos do projeto: vinculados + responsavel. */
    public function executores($obra_id)
    {
        if (! $this->db->table_exists('usuarios')) {
            return [];
        }
        $obra_id = (int) $obra_id;
        $sql = 'SELECT u.idUsuarios, u.nome FROM usuarios u WHERE u.situacao = 1 AND (u.idUsuarios = (SELECT o.responsavel_id FROM obras o WHERE o.idObra = ?)';
        $binds = [$obra_id];
        if ($this->db->table_exists('obra_usuario')) {
            $sql .= ' OR u.idUsuarios IN (SELECT ou.usuario_id FROM obra_usuario ou WHERE ou.obra_id = ?)';
            $binds[] = $obra_id;
        }
        $sql .= ') ORDER BY u.nome ASC, u.idUsuarios ASC';
        $lista = [];
        foreach ($this->db->query($sql, $binds)->result() as $r) {
            $lista[(int) $r->idUsuarios] = ['idUsuarios' => (int) $r->idUsuarios, 'nome' => (string) $r->nome];
        }
        return array_values($lista);
    }

    private function registrarEvento($ponto, $obra_id, $evento, $usuario_id, $destinatario_id, $observacao)
    {
        if (! $this->db->table_exists('obra_ponto_historico') || ! $this->db->field_exists('evento', 'obra_ponto_historico')) {
            return;
        }
        $this->db->insert('obra_ponto_historico', [
            'ponto_id' => (int) $ponto->idPonto,
            'obra_id' => (int) $obra_id,
            'status_anterior' => $ponto->status,
            'status_novo' => $ponto->status,
            'metragem_cabo' => 0,
            'usuario_id' => $usuario_id,
            'data' => date('Y-m-d H:i:s'),
            'observacao' => $observacao,
            'evento' => $evento,
            'destinatario_id' => $destinatario_id,
        ]);
    }

    /** Passa o ponto para um tecnico (ou para a equipe, se $destinatario_id for null). */
    public function repassar($ponto_id, $obra_id, $destinatario_id, $usuario_id, $instrucao = null)
    {
        if (! $this->temRepasse()) {
            return false;
        }
        $ponto = $this->db->where('idPonto', (int) $ponto_id)->where('obra_id', (int) $obra_id)->get('obra_pontos')->row();
        if (! $ponto || $ponto->status === 'testado') {
            return false;
        }
        $aguardando = self::aguardandoApos($ponto->status);
        if ($aguardando === null) {
            return false;
        }
        if ($destinatario_id !== null) {
            $destinatario_id = (int) $destinatario_id;
            $ok = false;
            foreach ($this->executores($obra_id) as $e) {
                if ($e['idUsuarios'] === $destinatario_id) {
                    $ok = true;
                    break;
                }
            }
            if (! $ok) {
                return false;
            }
        }
        $instrucao = $this->cortar($instrucao, 255);
        $this->db->where('idPonto', (int) $ponto_id)->update('obra_pontos', [
            'responsavel_id' => $destinatario_id,
            'aguardando' => $aguardando,
            'repassado_por' => $usuario_id !== null ? (int) $usuario_id : null,
            'repassado_em' => date('Y-m-d H:i:s'),
            'instrucao' => $instrucao,
        ]);
        $this->registrarEvento($ponto, $obra_id, 'repasse', $usuario_id, $destinatario_id, $instrucao);
        return true;
    }

    public function repassarLote($obra_id, array $ponto_ids, $destinatario_id, $usuario_id, $instrucao = null)
    {
        $n = 0;
        foreach (array_unique(array_map('intval', $ponto_ids)) as $id) {
            if ($id > 0 && $this->repassar($id, $obra_id, $destinatario_id, $usuario_id, $instrucao)) {
                $n++;
            }
        }
        return $n;
    }

    /** Assume um ponto da equipe (ou o proprio); $forcar tira de outro tecnico. */
    public function assumir($ponto_id, $obra_id, $usuario_id, $forcar = false)
    {
        if (! $this->temRepasse()) {
            return false;
        }
        $ponto = $this->db->where('idPonto', (int) $ponto_id)->where('obra_id', (int) $obra_id)->get('obra_pontos')->row();
        if (! $ponto || $ponto->status === 'testado' || $ponto->aguardando === null || $ponto->aguardando === '') {
            return false;
        }
        $usuario_id = (int) $usuario_id;
        $livre = $ponto->responsavel_id === null;
        $meu = $ponto->responsavel_id !== null && (int) $ponto->responsavel_id === $usuario_id;
        if (! $livre && ! $meu && ! $forcar) {
            return false;
        }
        $this->db->where('idPonto', (int) $ponto_id)->update('obra_pontos', ['responsavel_id' => $usuario_id]);
        $this->registrarEvento($ponto, $obra_id, 'assumiu', $usuario_id, $usuario_id, null);
        return true;
    }

    /** Guarda a foto do ponto; devolve o id ou 0 (imagem invalida, ponto inexistente ou limite de 20). */
    public function addFoto($ponto_id, $obra_id, $binario, $usuario_id, $etapa = null, $historico_id = null, $legenda = null)
    {
        if (! $this->temFotos() || self::fotoValida($binario) === null) {
            return 0;
        }
        $existe = $this->db->where('idPonto', (int) $ponto_id)->where('obra_id', (int) $obra_id)->count_all_results('obra_pontos');
        if (! $existe) {
            return 0;
        }
        $qtd = $this->db->where('ponto_id', (int) $ponto_id)->count_all_results('obra_ponto_foto');
        if ($qtd >= 20) {
            return 0;
        }
        $this->db->insert('obra_ponto_foto', [
            'ponto_id' => (int) $ponto_id,
            'obra_id' => (int) $obra_id,
            'historico_id' => $historico_id !== null ? (int) $historico_id : null,
            'etapa' => $this->cortar($etapa, 15),
            'foto' => $binario,
            'legenda' => $this->cortar($legenda, 150),
            'usuario_id' => $usuario_id !== null ? (int) $usuario_id : null,
            'data' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insert_id();
    }

    private function selectFotosSemBlob()
    {
        $this->db->select('f.idFoto, f.ponto_id, f.etapa, f.legenda, f.usuario_id, u.nome as usuario, f.data');
        $this->db->from('obra_ponto_foto f');
        $this->db->join('usuarios u', 'u.idUsuarios = f.usuario_id', 'left');
        $this->db->order_by('f.data', 'asc');
        $this->db->order_by('f.idFoto', 'asc');
    }

    public function getFotos($ponto_id)
    {
        if (! $this->temFotos()) {
            return [];
        }
        $this->selectFotosSemBlob();
        return $this->db->where('f.ponto_id', (int) $ponto_id)->get()->result();
    }

    public function getFotosObra($obra_id)
    {
        if (! $this->temFotos()) {
            return [];
        }
        $this->selectFotosSemBlob();
        $out = [];
        foreach ($this->db->where('f.obra_id', (int) $obra_id)->get()->result() as $f) {
            $out[(int) $f->ponto_id][] = $f;
        }
        return $out;
    }

    public function getFotoBinaria($idFoto)
    {
        if (! $this->temFotos()) {
            return null;
        }
        $r = $this->db->select('foto, obra_id, ponto_id')->where('idFoto', (int) $idFoto)->get('obra_ponto_foto')->row();
        return $r ?: null;
    }

    /** Pontos que esperam o usuario (dele, ou da equipe nos projetos a que tem acesso). */
    public function getFila($usuario_id)
    {
        if (! $this->temRepasse() || ! $this->db->table_exists('obras')) {
            return [];
        }
        $u = (int) $usuario_id;
        $acesso = 'o.responsavel_id = ' . $u;
        if ($this->db->table_exists('obra_usuario')) {
            $acesso .= ' OR EXISTS (SELECT 1 FROM obra_usuario ou WHERE ou.obra_id = p.obra_id AND ou.usuario_id = ' . $u . ')';
        }
        $extra = ', o.nome as obra_nome, c.nomeCliente as cliente, (p.responsavel_id = ' . $u . ') as minha';
        $extra .= $this->temFotos()
            ? ', (SELECT MIN(f2.idFoto) FROM obra_ponto_foto f2 WHERE f2.ponto_id = p.idPonto) as foto_id'
            : ', NULL as foto_id';
        $this->baseSelectPontos($extra);
        $this->db->join('obras o', 'o.idObra = p.obra_id');
        $this->db->join('clientes c', 'c.idClientes = o.clientes_id', 'left');
        $this->db->where('p.aguardando IS NOT NULL', null, false);
        $this->db->where('p.status <>', 'testado');
        $this->db->where_in('o.status', ['planejamento', 'em_execucao']);
        $this->db->where('(p.responsavel_id = ' . $u . ' OR (p.responsavel_id IS NULL AND (' . $acesso . ')))', null, false);
        $this->db->order_by('(p.responsavel_id = ' . $u . ') DESC', '', false);
        $this->db->order_by('COALESCE(p.repassado_em, p.atualizado_em)', 'asc', false);
        $this->db->order_by('p.idPonto', 'asc');
        $lista = $this->db->get()->result();
        foreach ($lista as $r) {
            $r->minha = (bool) (int) $r->minha;
            $r->foto_id = $r->foto_id !== null ? (int) $r->foto_id : null;
        }
        return $lista;
    }

    public function contarFila($usuario_id)
    {
        return count($this->getFila($usuario_id));
    }

    public function getHistorico($obra_id, $inicio = null, $fim = null, $usuario_id = null)
    {
        if (! $this->db->table_exists('obra_ponto_historico') || ! $this->db->table_exists('obra_pontos')) {
            return [];
        }
        $comEvento = $this->db->field_exists('evento', 'obra_ponto_historico');
        $this->db->select('h.idHistorico, h.ponto_id, p.identificacao, p.tipo, p.local, h.status_anterior, h.status_novo, '
            . 'h.metragem_cabo, h.usuario_id, u.nome as usuario, h.data, h.observacao'
            . ($comEvento ? ', h.evento, h.destinatario_id, ud.nome as destinatario'
                : ", 'status' as evento, NULL as destinatario_id, NULL as destinatario"), false);
        $this->db->from('obra_ponto_historico h');
        if ($comEvento) {
            $this->db->join('usuarios ud', 'ud.idUsuarios = h.destinatario_id', 'left');
        }
        $this->db->join('obra_pontos p', 'p.idPonto = h.ponto_id', 'left');
        $this->db->join('usuarios u', 'u.idUsuarios = h.usuario_id', 'left');
        $this->db->where('h.obra_id', (int) $obra_id);
        if ($inicio) {
            $this->db->where('h.data >=', $inicio . ' 00:00:00');
        }
        if ($fim) {
            $this->db->where('h.data <=', $fim . ' 23:59:59');
        }
        if ($usuario_id) {
            $this->db->where('h.usuario_id', (int) $usuario_id);
        }
        $this->db->order_by('h.data', 'desc');
        $this->db->order_by('h.idHistorico', 'desc');
        return $this->db->get()->result();
    }

    /** Com pontos, grava obras.percentual_concluido; sem pontos devolve null sem gravar. */
    public function recalcularProgressoObra($obra_id)
    {
        if (! $this->db->table_exists('obra_pontos') || ! $this->db->table_exists('obras')) {
            return null;
        }
        $pontos = $this->db->select('status')->where('obra_id', (int) $obra_id)->get('obra_pontos')->result();
        $prog = self::progresso($pontos);
        if ($prog === null) {
            return null;
        }
        $this->db->where('idObra', (int) $obra_id)->update('obras', ['percentual_concluido' => $prog]);
        return $prog;
    }

    /** Cria as etapas padrao que ainda nao existem (por nome); devolve quantas criou. */
    public function aplicarEtapasPadrao($obra_id, $tipoProjeto)
    {
        if (! $this->db->table_exists('obra_etapas')) {
            return 0;
        }
        $obra_id = (int) $obra_id;
        $nomes = [];
        foreach ($this->db->select('nome')->where('obra_id', $obra_id)->get('obra_etapas')->result() as $e) {
            $nomes[mb_strtolower(trim($e->nome))] = true;
        }
        $maior = $this->db->select_max('ordem', 'm')->where('obra_id', $obra_id)->get('obra_etapas')->row();
        $ordem = $maior && $maior->m !== null ? (int) $maior->m : 0;

        $criadas = 0;
        foreach (self::etapasPadrao($tipoProjeto) as $nome) {
            if (isset($nomes[mb_strtolower($nome)])) {
                continue;
            }
            $ordem++;
            $this->db->insert('obra_etapas', [
                'obra_id' => $obra_id,
                'nome' => $nome,
                'ordem' => $ordem,
                'status' => 'pendente',
            ]);
            $criadas++;
        }
        return $criadas;
    }
}
