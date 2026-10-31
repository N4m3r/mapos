<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menu lateral da Area do Tecnico (tambem usado na Area do Colaborador e na tela de ponto).
 * Funcoes puras (menu_tecnico_montar, _ativo, _iniciais, _telefone) sao testadas em
 * tests/Unit/MenuTecnicoTest.php; menu_tecnico_dados() consulta sessao/banco uma vez por requisicao.
 */

if (! function_exists('menu_tecnico_ativo')) {
    /** Verdadeiro se a rota atual (uri_string) corresponde ao item (rota ou apelidos), sem diferenciar maiusculas. */
    function menu_tecnico_ativo($atual, $rota, array $apelidos = [])
    {
        $norm = function ($s) {
            $s = strtolower(trim((string) $s, '/'));

            return preg_replace('#^index\.php/?#', '', $s);
        };
        $atual = $norm($atual);
        foreach (array_merge([$rota], $apelidos) as $r) {
            $r = $norm($r);
            if ($r === '') {
                continue;
            }
            // 'tecnico' (inicio) so vale quando exato: as demais telas tem item proprio
            if ($r === 'tecnico') {
                if ($atual === 'tecnico') {
                    return true;
                }
                continue;
            }
            if ($atual === $r || strpos($atual, $r . '/') === 0) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('menu_tecnico_montar')) {
    /**
     * Monta as secoes do menu a partir de permissoes/vinculo.
     * $ctx: atual, tecnico, colaborador_area, vinculado, ponto, sistema, fila_qtd, offline.
     * Cada item: id, rotulo, icone, rota (ou null), ativo, badge, desabilitado, acao.
     */
    function menu_tecnico_montar(array $ctx)
    {
        $c = $ctx + [
            'atual' => '', 'tecnico' => false, 'colaborador_area' => false, 'vinculado' => false,
            'ponto' => false, 'sistema' => false, 'fila_qtd' => 0, 'offline' => false,
        ];
        $item = function ($id, $rotulo, $icone, $rota, array $apelidos = [], array $extra = []) use ($c) {
            return $extra + [
                'id' => $id, 'rotulo' => $rotulo, 'icone' => $icone, 'rota' => $rota,
                'ativo' => $rota !== null && menu_tecnico_ativo($c['atual'], $rota, $apelidos),
                'badge' => 0, 'desabilitado' => false, 'acao' => null,
            ];
        };
        $secoes = [];

        if ($c['tecnico']) {
            $secoes[] = ['id' => 'trabalho', 'titulo' => 'Trabalho', 'itens' => [
                $item('inicio', 'Início', 'bxs-dashboard', 'tecnico'),
                $item('os', 'Minhas OS', 'bx-list-ul', 'tecnico/os', ['tecnico/visualizar', 'tecnico/nova_atividade']),
                $item('projetos', 'Projetos', 'bx-network-chart', 'tecnico/projetos', ['tecnico/projeto']),
                $item('fila', 'Minha fila', 'bx-transfer-alt', 'tecnico/fila', [], ['badge' => max(0, (int) $c['fila_qtd'])]),
                $item('relatorio', 'Meu relatório', 'bx-bar-chart-alt-2', 'tecnico/meu_relatorio'),
            ]];
        }

        if ($c['colaborador_area']) {
            if ($c['vinculado']) {
                $itens = [];
                if ($c['ponto']) {
                    $itens[] = $item('ponto', 'Bater ponto', 'bx-fingerprint', 'ponto');
                }
                $itens[] = $item('espelho', 'Meu ponto (espelho)', 'bx-calendar-check', 'colaborador/espelho');
                $itens[] = $item('ocorrencias', 'Ocorrências e ajustes', 'bx-edit', 'colaborador/ocorrencias');
                $itens[] = $item('ausencias', 'Férias e ausências', 'bx-sun', 'colaborador/ausencias');
                $itens[] = $item('holerite', 'Holerites', 'bx-receipt', 'colaborador/holerite');
                $itens[] = $item('meusdados', 'Meus dados', 'bx-id-card', 'colaborador/meusDados');
            } else {
                $itens = [$item('rh_pendente', 'RH: peça ao administrador para vincular seu cadastro', 'bx-info-circle', null, [], ['desabilitado' => true])];
            }
            $secoes[] = ['id' => 'rh', 'titulo' => 'RH', 'itens' => $itens];
        }

        $conta = [];
        if ($c['tecnico']) {
            $conta[] = $item('perfil', 'Meu perfil', 'bx-user', 'tecnico/perfil');
        }
        if ($c['offline']) {
            $conta[] = $item('offline', 'Modo offline', 'bx-cloud-off', null, [], ['acao' => 'offline']);
            $conta[] = $item('instalar', 'Instalar app', 'bx-download', null, [], ['acao' => 'instalar']);
        }
        if ($c['sistema']) {
            $conta[] = $item('sistema', 'Painel do sistema', 'bx-home', null, [], ['acao' => 'raiz']);
        }
        $conta[] = $item('sair', 'Sair', 'bx-log-out-circle', 'login/sair');
        $secoes[] = ['id' => 'conta', 'titulo' => 'Conta', 'itens' => $conta];

        return $secoes;
    }
}

if (! function_exists('menu_tecnico_iniciais')) {
    /** Ate duas iniciais (primeira e ultima palavra) em maiusculas; '?' se vazio. */
    function menu_tecnico_iniciais($nome)
    {
        $partes = preg_split('/\s+/u', trim((string) $nome), -1, PREG_SPLIT_NO_EMPTY);
        if (! $partes) {
            return '?';
        }
        $ini = mb_strtoupper(mb_substr($partes[0], 0, 1, 'UTF-8'), 'UTF-8');
        if (count($partes) > 1) {
            $ini .= mb_strtoupper(mb_substr(end($partes), 0, 1, 'UTF-8'), 'UTF-8');
        }

        return $ini;
    }
}

if (! function_exists('menu_tecnico_telefone')) {
    /**
     * Normaliza telefone brasileiro: aceita mascara e +55; devolve ['ok'=>bool,'valor'=>string].
     * Vazio e valido (valor ''). Valido: 10 digitos (fixo) ou 11 (celular, 3o digito 9), DDD 11-99.
     */
    function menu_tecnico_telefone($bruto)
    {
        $d = preg_replace('/\D+/', '', (string) $bruto);
        if ($d === '') {
            return ['ok' => trim((string) $bruto) === '', 'valor' => ''];
        }
        if ((strlen($d) === 12 || strlen($d) === 13) && strpos($d, '55') === 0) {
            $d = substr($d, 2);
        }
        $n = strlen($d);
        if (($n !== 10 && $n !== 11) || (int) substr($d, 0, 2) < 11) {
            return ['ok' => false, 'valor' => ''];
        }
        if ($n === 11 && $d[2] !== '9') {
            return ['ok' => false, 'valor' => ''];
        }
        if ($n === 10 && $d[2] === '0') {
            return ['ok' => false, 'valor' => ''];
        }
        $corte = $n === 11 ? 7 : 6;

        return ['ok' => true, 'valor' => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, $corte - 2), substr($d, $corte))];
    }
}

if (! function_exists('menu_tecnico_colaborador')) {
    /** Colaborador ativo do usuario, sem carregar a foto (base64, pesada). Inclui tem_foto, nome_unidade, nome_jornada. */
    function menu_tecnico_colaborador($usuarioId)
    {
        static $memo = [];
        if (array_key_exists($usuarioId, $memo)) {
            return $memo[$usuarioId];
        }
        $CI = &get_instance();
        $memo[$usuarioId] = null;
        if (! $CI->db->table_exists('rh_colaboradores')) {
            return null;
        }
        $CI->db->select("c.id, c.nome, c.cargo, c.departamento, c.unidade_id, c.jornada_id, (c.foto_base64 IS NOT NULL AND c.foto_base64 <> '') AS tem_foto", false);
        $CI->db->from('rh_colaboradores c');
        if ($CI->db->table_exists('rh_unidades')) {
            $CI->db->select('un.nome AS nome_unidade')->join('rh_unidades un', 'un.id = c.unidade_id', 'left');
        }
        if ($CI->db->table_exists('rh_jornadas')) {
            $CI->db->select('j.nome AS nome_jornada')->join('rh_jornadas j', 'j.id = c.jornada_id', 'left');
        }
        $CI->db->where(['c.usuarios_id' => (int) $usuarioId, 'c.situacao' => 1]);
        $CI->db->limit(1);

        return $memo[$usuarioId] = $CI->db->get()->row() ?: null;
    }
}

if (! function_exists('menu_tecnico_dados')) {
    /**
     * Dados do menu para a requisicao atual (memoizado). Retorna:
     * secoes, nome, cargo, foto_url, iniciais, perfil_url, vinculado.
     * $comOffline: mostra "Modo offline"/"Instalar app" (so onde pwa-tecnico.js esta carregado).
     */
    function menu_tecnico_dados($comOffline = false)
    {
        static $cache = [];
        $chave = $comOffline ? 'off' : 'on';
        if (isset($cache[$chave])) {
            return $cache[$chave];
        }
        $CI = &get_instance();
        $CI->load->library('permission');
        $perm = $CI->session->userdata('permissao');
        $uid = (int) $CI->session->userdata('id_admin');
        $pode = function ($p) use ($CI, $perm) {
            return (bool) $CI->permission->checkPermission($perm, $p);
        };

        $tecnico = $pode('vTecnicoDashboard');
        $areaColab = $pode('vAreaColaborador');
        $ponto = $pode('baterPonto');

        $colab = ($areaColab || $ponto) && $uid ? menu_tecnico_colaborador($uid) : null;

        $fila = 0;
        if ($tecnico && $uid) {
            try {
                $CI->load->model('obra_pontos_model');
                $fila = (int) $CI->obra_pontos_model->contarFila($uid);
            } catch (Throwable $e) {
                $fila = 0;
            }
        }

        $nome = (string) $CI->session->userdata('nome_admin');
        $foto = null;
        $img = basename((string) $CI->session->userdata('url_image_user_admin'));
        if ($img !== '' && is_file(FCPATH . 'assets/userImage/' . $img)) {
            $foto = base_url('assets/userImage/' . $img);
        } elseif ($colab && ! empty($colab->tem_foto)) {
            $foto = site_url('colaborador/foto');
        }

        $secoes = menu_tecnico_montar([
            'atual' => $CI->uri->uri_string(),
            'tecnico' => $tecnico,
            'colaborador_area' => $areaColab || $ponto,
            'vinculado' => (bool) $colab,
            'ponto' => $ponto,
            'sistema' => $pode('vOs'),
            'fila_qtd' => $fila,
            'offline' => (bool) $comOffline,
        ]);
        $perfilUrl = $tecnico ? site_url('tecnico/perfil') : ($colab ? site_url('colaborador/meusDados') : null);

        return $cache[$chave] = [
            'secoes' => $secoes,
            'nome' => $nome,
            'cargo' => $colab && ! empty($colab->cargo) ? (string) $colab->cargo : '',
            'foto_url' => $foto,
            'iniciais' => menu_tecnico_iniciais($nome),
            'perfil_url' => $perfilUrl,
            'vinculado' => (bool) $colab,
        ];
    }
}
