<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Tributacao extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Tributacao_tabelas_model', 'tabelas_model');
        if (! (PHP_SAPI === 'cli') && ! $this->session->userdata('id_admin')) {
            redirect('login');
        }
    }

    /** Tabelas oficiais da reforma tributaria: versoes, contagens e importacao de CSV. */
    public function tabelas()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cNfe')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar o módulo fiscal.');
            redirect(base_url());
        }
        if ($this->input->post('tabela')) {
            $arq = $_FILES['arquivo'] ?? null;
            if (! $arq || $arq['error'] !== UPLOAD_ERR_OK || $arq['size'] > 10 * 1024 * 1024) {
                $this->session->set_flashdata('error', 'Escolha um arquivo CSV de até 10 MB.');
            } else {
                $r = $this->tabelas_model->importar(
                    (string) $this->input->post('tabela'),
                    file_get_contents($arq['tmp_name']),
                    substr(trim((string) $this->input->post('versao')), 0, 40),
                    substr(trim((string) $this->input->post('fonte_url')), 0, 255),
                    $this->input->post('vigencia_inicio'),
                    $this->session->userdata('id_admin')
                );
                if ($r['ok']) {
                    $this->session->set_flashdata('success', 'Tabela atualizada: ' . $r['qtd'] . ' linhas importadas.');
                } else {
                    $this->session->set_flashdata('error', 'Nada foi alterado. ' . implode(' ', array_slice($r['erros'], 0, 5)));
                }
            }
            redirect('tributacao/tabelas');
        }
        $this->data['resumo'] = $this->tabelas_model->resumo();
        $this->data['view'] = 'tributacao/tabelas';

        return $this->layout();
    }

    /** Sugestao de NBS/cIndOp/cClassTrib pelo item da LC 116 (JSON). Usado no cadastro de servico. */
    public function sugerir($itemLc116 = '')
    {
        $this->output->set_content_type('application/json');
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eServico')) {
            return $this->output->set_status_header(403)->set_output(json_encode(['ok' => false, 'mensagem' => 'Sem permissão.']));
        }
        $item = preg_replace('/[^0-9.]/', '', urldecode((string) $itemLc116));
        $linhas = $item === '' ? [] : $this->tabelas_model->sugerirPorItemLc116($item);
        $sug = [];
        foreach ($linhas as $l) {
            $sug[] = ['nbs' => $l->nbs, 'cindop' => $l->cindop, 'cclasstrib' => $l->cclasstrib, 'descricao' => $l->descricao];
        }

        return $this->output->set_output(json_encode(['ok' => true, 'sugestoes' => $sug, 'mensagem' => $sug ? '' : 'Nenhuma sugestão para este código. As tabelas oficiais já foram importadas?']));
    }

    /** Central da Reforma Tributaria: se o sistema esta atualizado, prazos e links oficiais. */
    public function central()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cNfe')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar o módulo fiscal.');
            redirect(base_url());
        }
        $this->load->helper('tributacao');

        $verificacao = $this->lerVerificacao();
        $instaladas = $this->versoesInstaladas();
        $libs = [];
        foreach ($instaladas as $pacote => $inst) {
            $libs[] = ['pacote' => $pacote, 'instalada' => $inst, 'ultima' => $verificacao['versoes'][$pacote]['ultima'] ?? null];
        }

        $hoje = date('Y-m-d');
        $this->data['itens'] = trib_status_atualizacao($this->versoesTabelas(), $libs, $hoje);
        $this->data['verificacao'] = $verificacao;
        $this->data['libs'] = $libs;
        $this->data['janela'] = tributacao_janela_opcao($hoje);
        $this->data['view'] = 'tributacao/central';

        return $this->layout();
    }

    /** Verificacao mensal (cron): php index.php tributacao verificar. So roda por linha de comando. */
    public function verificar()
    {
        if (! (PHP_SAPI === 'cli')) {
            show_404();
        }

        $instaladas = $this->versoesInstaladas();
        $versoes = [];
        $avisos = [];
        foreach ($instaladas as $pacote => $inst) {
            $ultima = $this->ultimaVersaoPackagist($pacote);
            $versoes[$pacote] = ['instalada' => $inst, 'ultima' => $ultima];
            if ($ultima === null) {
                $avisos[] = "Não foi possível consultar {$pacote} no Packagist (sem conexão?).";
            } elseif ($inst !== null && version_compare($inst, $ultima, '<')) {
                $avisos[] = "{$pacote}: instalada {$inst}, há versão nova {$ultima}. Atualize com cuidado e teste as notas.";
            }
        }

        $resultado = ['data' => date('Y-m-d H:i:s'), 'versoes' => $versoes, 'avisos' => $avisos];
        $arquivo = APPPATH . 'cache/tributacao_verificacao.json';
        $ok = file_put_contents($arquivo, json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false;

        echo 'Verificação da Reforma Tributária em ' . $resultado['data'] . PHP_EOL;
        foreach ($versoes as $pacote => $v) {
            echo "  {$pacote}: instalada " . ($v['instalada'] ?? '-') . ', última ' . ($v['ultima'] ?? 'não verificada') . PHP_EOL;
        }
        foreach ($avisos as $a) {
            echo '  AVISO: ' . $a . PHP_EOL;
        }
        echo $ok ? 'Resultado salvo em application/cache/tributacao_verificacao.json' . PHP_EOL : 'ERRO: não foi possível gravar application/cache/tributacao_verificacao.json' . PHP_EOL;
    }

    /** Versoes instaladas (composer installed.json): [pacote => versao] so das libs fiscais. */
    private function versoesInstaladas(): array
    {
        $alvo = ['nfephp-org/sped-nfe', 'hadder/nfse-nacional'];
        $out = array_fill_keys($alvo, null);
        $arq = APPPATH . 'vendor/composer/installed.json';
        $d = is_file($arq) ? json_decode((string) file_get_contents($arq), true) : null;
        $pacotes = is_array($d) ? ($d['packages'] ?? $d) : [];
        foreach ($pacotes as $p) {
            if (is_array($p) && isset($p['name']) && array_key_exists($p['name'], $out)) {
                $out[$p['name']] = ltrim((string) ($p['version'] ?? ''), 'vV') ?: null;
            }
        }

        return $out;
    }

    /** Ultima versao estavel publicada no Packagist (p2), ou null se nao consultou. */
    private function ultimaVersaoPackagist(string $pacote): ?string
    {
        $url = 'https://repo.packagist.org/p2/' . $pacote . '.json';
        $ctx = stream_context_create(['http' => ['timeout' => 10, 'header' => "User-Agent: MapOS-Tributacao\r\n"]]);
        try {
            $txt = @file_get_contents($url, false, $ctx);
            $json = $txt === false ? null : json_decode($txt, true);
        } catch (Throwable $e) {
            return null;
        }
        if (! is_array($json) || ! isset($json['packages'][$pacote]) || ! is_array($json['packages'][$pacote])) {
            return null;
        }
        $melhor = null;
        foreach ($json['packages'][$pacote] as $v) {
            $n = ltrim((string) ($v['version'] ?? ''), 'vV');
            if (! preg_match('/^\d+(\.\d+)*$/', $n)) {
                continue; // ignora dev, alpha, beta, rc
            }
            if ($melhor === null || version_compare($n, $melhor, '>')) {
                $melhor = $n;
            }
        }

        return $melhor;
    }

    /** Le o resultado salvo pelo comando verificar (ou null). */
    private function lerVerificacao(): ?array
    {
        $arq = APPPATH . 'cache/tributacao_verificacao.json';
        $d = is_file($arq) ? json_decode((string) file_get_contents($arq), true) : null;

        return is_array($d) ? $d : null;
    }

    /** Tabelas oficiais: [tabela => ['versao','importado_em'] | null] a partir do resumo do model. */
    private function versoesTabelas(): array
    {
        $out = [];
        foreach ($this->tabelas_model->resumo() as $k => $t) {
            $out[$k] = $t['versao'] ? ['versao' => $t['versao']->versao, 'importado_em' => $t['versao']->importado_em] : null;
        }

        return $out;
    }

    /** Simulador DAS x regime regular de IBS/CBS (decisão semestral do Simples). */
    public function simulador()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cNfe')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar o módulo fiscal.');
            redirect(base_url());
        }
        $this->load->helper('simulador_ibscbs');
        $this->load->model('Tributos_parametros_model');
        $this->load->model('Entradas_model');

        $desde = date('Y-m-01', strtotime('-6 months'));
        $ate = date('Y-m-01');
        $notas = $this->db->select('tipo, valor_total, xml')->where('status', 'autorizada')
            ->where('data_emissao >=', $desde)->where('data_emissao <', $ate)->limit(2000)->get('notas_fiscais')->result();
        $soma = 0.0;
        $comCnpj = 0;
        foreach ($notas as $n) {
            $soma += (float) $n->valor_total;
            if ($n->xml && preg_match('#<(?:dest|toma)\b[^>]*>.*?<CNPJ>\d{14}</CNPJ>#s', (string) $n->xml)) {
                $comCnpj++;
            }
        }
        $cfg = $this->db->field_exists('simples_aliquota_efetiva', 'configuracoes_nfe')
            ? $this->db->get_where('configuracoes_nfe', ['id' => 1])->row() : null;
        $par = $this->Tributos_parametros_model->doAno(2027);

        $creditos = 0.0;
        for ($i = 1; $i <= 6; $i++) {
            $creditos += (float) $this->Entradas_model->creditoDoPeriodo(date('Y-m', strtotime("-$i months")))['total'];
        }
        $this->data['padrao'] = [
            'faturamento_mensal' => round($soma / 6, 2),
            'perc_clientes_empresa' => $notas ? round($comCnpj / count($notas) * 100, 1) : 0,
            'aliquota_das_efetiva' => (float) ($cfg->simples_aliquota_efetiva ?? 0),
            'parcela_ibscbs_no_das' => SIMULADOR_PARCELA_IBSCBS_DAS_PADRAO,
            'aliquota_cbs' => $par['cbs'],
            'aliquota_ibs' => $par['ibs_uf'] + $par['ibs_mun'],
            'creditos_compras_mensais' => round($creditos / 6, 2),
        ];
        $this->data['parametros_estimado'] = ! empty($par['estimado']);
        $this->data['view'] = 'tributacao/simulador';

        return $this->layout();
    }
}
