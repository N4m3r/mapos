<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Emailtemplate
 *
 * Renderiza os e-mails a partir dos modelos configuráveis (tabela
 * email_templates) e do layout global (HTML/CSS em configuracoes). Substitui
 * as tags {{tag}} do assunto e do corpo por valores derivados do contexto
 * (emitente, cliente, OS, cobrança) e envolve o corpo no layout global.
 *
 * Uso:
 *   $this->load->library('emailtemplate');
 *   $r = $this->emailtemplate->render('cobranca', ['emitente' => $e, 'cobranca' => $c]);
 *   if ($r && $r['ativo']) { // usar $r['assunto'] e $r['corpo'] }
 */
class Emailtemplate
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('email_templates_model');
    }

    /**
     * Renderiza um modelo pelo slug. Retorna null quando o modelo não existe
     * (permite fallback para as views antigas). Quando existe mas está inativo,
     * retorna ['ativo' => false] para que o chamador não envie o e-mail.
     */
    public function render($slug, array $context = [])
    {
        if (! $this->CI->db->table_exists('email_templates')) {
            return null;
        }

        $template = $this->CI->email_templates_model->getBySlug($slug);
        if (! $template) {
            return null;
        }

        if ((int) $template->ativo !== 1) {
            return ['ativo' => false, 'assunto' => '', 'corpo' => ''];
        }

        return $this->renderTemplate($template, $context);
    }

    /**
     * Renderiza a partir de um objeto de modelo (usado também no preview de
     * edições ainda não salvas).
     */
    public function renderTemplate($template, array $context = [])
    {
        $tags = $this->buildTags($context);

        $assunto = $this->applyTags($template->assunto, $tags);
        $corpo = $this->applyTags($template->corpo, $tags);

        $documento = str_replace(
            ['{{css}}', '{{conteudo}}'],
            [$this->getCss(), $corpo],
            $this->getLayout()
        );
        $documento = $this->applyTags($documento, $tags);

        return [
            'ativo' => (int) $template->ativo === 1,
            'assunto' => $assunto,
            'corpo' => $documento,
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Layout / CSS global                                                 */
    /* ------------------------------------------------------------------ */

    public function getLayout()
    {
        $valor = $this->getConfig('email_layout');

        return $valor !== null && trim($valor) !== '' ? $valor : '<html><head><style>{{css}}</style></head><body>{{conteudo}}</body></html>';
    }

    public function getCss()
    {
        $valor = $this->getConfig('email_css');

        return $valor !== null ? $valor : '';
    }

    protected function getConfig($chave)
    {
        $row = $this->CI->db->where('config', $chave)->limit(1)->get('configuracoes')->row();

        return $row ? $row->valor : null;
    }

    /* ------------------------------------------------------------------ */
    /* Tags                                                                */
    /* ------------------------------------------------------------------ */

    /**
     * Substitui {{tag}} (com espaços opcionais, sem diferenciar maiúsculas) no
     * texto pelos valores do mapa. Tags desconhecidas viram string vazia.
     */
    public function applyTags($texto, array $tags)
    {
        if ($texto === null || $texto === '') {
            return '';
        }

        return preg_replace_callback('/\{\{\s*([a-z0-9_\.]+)\s*\}\}/i', function ($m) use ($tags) {
            $chave = strtolower($m[1]);

            return array_key_exists($chave, $tags) ? (string) $tags[$chave] : '';
        }, $texto);
    }

    /**
     * Monta o mapa de tags a partir do contexto. Objetos aceitos:
     *   emitente, cliente, os, cobranca. Extras em $context['extra'] têm
     *   precedência (mapa associativo tag => valor).
     */
    public function buildTags(array $context)
    {
        $tags = [
            'data_atual' => date('d/m/Y'),
        ];

        $emitente = $context['emitente'] ?? null;
        $os = $context['os'] ?? null;
        $cobranca = $context['cobranca'] ?? null;
        $cliente = $context['cliente'] ?? $os ?? $cobranca ?? null;

        // Empresa / emitente.
        if ($emitente) {
            $tags['empresa_nome'] = $this->prop($emitente, 'nome');
            $tags['empresa_email'] = $this->prop($emitente, 'email');
            $tags['empresa_telefone'] = $this->prop($emitente, 'telefone');
            $tags['empresa_cnpj'] = $this->prop($emitente, 'cnpj');
            $endereco = trim(sprintf(
                '%s, %s - %s, %s/%s',
                $this->prop($emitente, 'rua'),
                $this->prop($emitente, 'numero'),
                $this->prop($emitente, 'bairro'),
                $this->prop($emitente, 'cidade'),
                $this->prop($emitente, 'uf')
            ), ', -/');
            $tags['empresa_endereco'] = $endereco;
            $logo = $this->prop($emitente, 'url_logo');
            $tags['empresa_logo'] = $logo;
            $tags['empresa_logo_img'] = $logo ? '<img src="' . htmlspecialchars($logo, ENT_QUOTES) . '" alt="' . htmlspecialchars($tags['empresa_nome'], ENT_QUOTES) . '">' : '';
        }

        // Cliente.
        if ($cliente) {
            $tags['cliente_nome'] = $this->prop($cliente, 'nomeCliente');
            $tags['cliente_email'] = $this->prop($cliente, 'email');
            $tags['cliente_telefone'] = $this->prop($cliente, 'celular') ?: ($this->prop($cliente, 'telefone') ?: $this->prop($cliente, 'celular_cliente'));
            $tags['cliente_endereco'] = trim(sprintf(
                '%s, %s - %s, %s/%s',
                $this->prop($cliente, 'rua'),
                $this->prop($cliente, 'numero'),
                $this->prop($cliente, 'bairro'),
                $this->prop($cliente, 'cidade'),
                $this->prop($cliente, 'estado')
            ), ', -/');
        }

        // Ordem de serviço.
        if ($os) {
            $tags['os_numero'] = $this->prop($os, 'idOs');
            $tags['os_status'] = $this->prop($os, 'status');
            $tags['os_data_inicial'] = $this->formatDate($this->prop($os, 'dataInicial'));
            $tags['os_data_final'] = $this->formatDate($this->prop($os, 'dataFinal'));
            $tags['os_garantia'] = $this->prop($os, 'garantia') ?: '—';

            $produtos = $context['produtos'] ?? [];
            $servicos = $context['servicos'] ?? [];
            [$itensHtml, $total] = $this->osItens($produtos, $servicos);
            $tags['os_itens_html'] = $itensHtml;

            $valorDesconto = (float) $this->prop($os, 'valor_desconto');
            $temDesconto = $this->prop($os, 'desconto') && $valorDesconto != 0;
            $tags['os_valor_total'] = 'R$ ' . number_format($temDesconto ? $valorDesconto : $total, 2, ',', '.');
            $tags['os_detalhes_html'] = $this->osDetalhes($os);
        }

        // Cobrança / boleto.
        if ($cobranca) {
            $tags['cobranca_numero'] = $this->prop($cobranca, 'idCobranca');
            $tags['cobranca_valor'] = 'R$ ' . number_format(((float) $this->prop($cobranca, 'total', 0)) / 100, 2, ',', '.');
            $venc = $this->prop($cobranca, 'expire_at');
            $tags['cobranca_vencimento'] = $venc ? date('d/m/Y', strtotime($venc)) : '—';
            $tags['cobranca_descricao'] = $this->prop($cobranca, 'message');
            $tags['cobranca_link'] = $this->prop($cobranca, 'link') ?: $this->prop($cobranca, 'pdf');
            $tags['cobranca_pdf'] = $this->prop($cobranca, 'pdf');
            $tags['cobranca_barcode'] = $this->prop($cobranca, 'barcode');
            $tags['cobranca_pix'] = $this->prop($cobranca, 'pix');
            $tags['cobranca_pagamento_html'] = $this->cobrancaPagamento($cobranca);
        }

        // Extras com precedência.
        if (! empty($context['extra']) && is_array($context['extra'])) {
            foreach ($context['extra'] as $k => $v) {
                $tags[strtolower($k)] = $v;
            }
        }

        return $tags;
    }

    /* ------------------------------------------------------------------ */
    /* Blocos HTML gerados                                                 */
    /* ------------------------------------------------------------------ */

    protected function osDetalhes($os)
    {
        $secoes = [
            'Descrição' => $this->prop($os, 'descricaoProduto'),
            'Defeito Apresentado' => $this->prop($os, 'defeito'),
            'Observações' => $this->prop($os, 'observacoes'),
            'Laudo Técnico' => $this->prop($os, 'laudoTecnico'),
        ];

        $html = '';
        foreach ($secoes as $titulo => $conteudo) {
            if ($conteudo !== null && trim((string) $conteudo) !== '') {
                $html .= '<h2>' . $titulo . '</h2><p>' . htmlspecialchars_decode((string) $conteudo) . '</p>';
            }
        }

        return $html;
    }

    protected function osItens($produtos, $servicos)
    {
        $total = 0.0;
        $html = '';

        if (! empty($produtos)) {
            $html .= '<h2>Produtos</h2><table class="itens" role="presentation" cellpadding="0" cellspacing="0"><tr><th>Produto</th><th>Qtd.</th><th>Preço</th><th>Subtotal</th></tr>';
            foreach ($produtos as $p) {
                $sub = (float) $this->prop($p, 'subTotal', 0);
                $total += $sub;
                $preco = $this->prop($p, 'preco') ?: $this->prop($p, 'precoVenda');
                $html .= '<tr><td>' . htmlspecialchars((string) $this->prop($p, 'descricao')) . '</td>'
                    . '<td>' . $this->prop($p, 'quantidade') . '</td>'
                    . '<td>R$ ' . number_format((float) $preco, 2, ',', '.') . '</td>'
                    . '<td>R$ ' . number_format($sub, 2, ',', '.') . '</td></tr>';
            }
            $html .= '</table>';
        }

        if (! empty($servicos)) {
            $html .= '<h2>Serviços</h2><table class="itens" role="presentation" cellpadding="0" cellspacing="0"><tr><th>Serviço</th><th>Qtd.</th><th>Preço</th><th>Subtotal</th></tr>';
            foreach ($servicos as $s) {
                $preco = (float) ($this->prop($s, 'preco') ?: $this->prop($s, 'precoVenda'));
                $qtd = (float) ($this->prop($s, 'quantidade') ?: 1);
                $sub = $preco * $qtd;
                $total += $sub;
                $html .= '<tr><td>' . htmlspecialchars((string) $this->prop($s, 'nome')) . '</td>'
                    . '<td>' . ($qtd ?: 1) . '</td>'
                    . '<td>R$ ' . number_format($preco, 2, ',', '.') . '</td>'
                    . '<td>R$ ' . number_format($sub, 2, ',', '.') . '</td></tr>';
            }
            $html .= '</table>';
        }

        return [$html, $total];
    }

    protected function cobrancaPagamento($cobranca)
    {
        $link = $this->prop($cobranca, 'link') ?: $this->prop($cobranca, 'pdf');
        $pix = $this->prop($cobranca, 'pix');
        $barcode = $this->prop($cobranca, 'barcode');

        $html = '';
        if ($link) {
            $html .= '<p><a class="btn-pagar" href="' . htmlspecialchars($link, ENT_QUOTES) . '" target="_blank">Pagar / abrir boleto</a></p>';
        }
        if ($pix) {
            $html .= '<div class="box-pagamento"><div class="rotulo">PIX copia e cola</div><code>' . htmlspecialchars($pix) . '</code></div>';
        }
        if ($barcode) {
            $html .= '<div class="box-pagamento"><div class="rotulo">Código de barras</div><code>' . htmlspecialchars($barcode) . '</code></div>';
        }

        return $html;
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    protected function prop($obj, $name, $default = '')
    {
        if (is_object($obj)) {
            return isset($obj->$name) ? $obj->$name : $default;
        }
        if (is_array($obj)) {
            return $obj[$name] ?? $default;
        }

        return $default;
    }

    protected function formatDate($value)
    {
        if (! $value) {
            return '';
        }
        $ts = strtotime($value);

        return $ts ? date('d/m/Y', $ts) : (string) $value;
    }
}
