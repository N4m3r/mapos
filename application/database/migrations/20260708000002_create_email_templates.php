<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_create_email_templates extends CI_Migration
{
    public function up()
    {
        // Tabela de modelos de e-mail (um registro por tipo de e-mail enviado).
        if (! $this->db->table_exists('email_templates')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'slug' => [
                    'type' => 'VARCHAR',
                    'constraint' => 60,
                ],
                'nome' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                ],
                'descricao' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'assunto' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                ],
                'corpo' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'tags' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'ativo' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'data_criacao' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'data_atualizacao' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('slug');
            $this->dbforge->create_table('email_templates');
        }

        // Layout global (HTML/CSS) que envolve o corpo de todos os e-mails.
        $this->seedConfig('email_layout', $this->defaultLayout());
        $this->seedConfig('email_css', $this->defaultCss());

        // Modelos padrão.
        $agora = date('Y-m-d H:i:s');

        $this->seedTemplate([
            'slug' => 'os',
            'nome' => 'Ordem de Serviço',
            'descricao' => 'Enviado ao cliente ao compartilhar/notificar uma Ordem de Serviço.',
            'assunto' => 'Ordem de Serviço #{{os_numero}} - {{empresa_nome}}',
            'corpo' => $this->defaultCorpoOs(),
            'tags' => 'cliente_nome, cliente_email, empresa_nome, os_numero, os_status, os_data_inicial, os_data_final, os_garantia, os_detalhes_html, os_itens_html, os_valor_total, data_atual',
            'data_criacao' => $agora,
            'data_atualizacao' => $agora,
        ]);

        $this->seedTemplate([
            'slug' => 'cobranca',
            'nome' => 'Cobrança / Boleto da NF',
            'descricao' => 'Enviado ao cliente com o boleto/PIX gerado a partir da nota fiscal ou da cobrança.',
            'assunto' => 'Cobrança #{{cobranca_numero}} - {{empresa_nome}}',
            'corpo' => $this->defaultCorpoCobranca(),
            'tags' => 'cliente_nome, cliente_email, empresa_nome, cobranca_numero, cobranca_valor, cobranca_vencimento, cobranca_descricao, cobranca_pagamento_html, cobranca_link, cobranca_pdf, cobranca_barcode, cobranca_pix, data_atual',
            'data_criacao' => $agora,
            'data_atualizacao' => $agora,
        ]);
    }

    public function down()
    {
        if ($this->db->table_exists('email_templates')) {
            $this->dbforge->drop_table('email_templates');
        }
        $this->db->where_in('config', ['email_layout', 'email_css'])->delete('configuracoes');
    }

    private function seedConfig($config, $valor)
    {
        $existe = $this->db->where('config', $config)->count_all_results('configuracoes');
        if ($existe == 0) {
            $this->db->insert('configuracoes', ['config' => $config, 'valor' => $valor]);
        }
    }

    private function seedTemplate($data)
    {
        $existe = $this->db->where('slug', $data['slug'])->count_all_results('email_templates');
        if ($existe == 0) {
            $this->db->insert('email_templates', $data);
        }
    }

    private function defaultLayout()
    {
        return <<<'HTML'
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>{{css}}</style>
</head>
<body>
    <div class="email-bg">
        <table class="email-wrapper" role="presentation" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td class="email-header">
                    {{empresa_logo_img}}
                    <div class="email-header-name">{{empresa_nome}}</div>
                </td>
            </tr>
            <tr>
                <td class="email-body">
                    {{conteudo}}
                </td>
            </tr>
            <tr>
                <td class="email-footer">
                    <strong>{{empresa_nome}}</strong><br>
                    {{empresa_endereco}}<br>
                    {{empresa_telefone}} &middot; {{empresa_email}}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
HTML;
    }

    private function defaultCss()
    {
        return <<<'CSS'
body { margin: 0; padding: 0; background: #eef1f6; }
.email-bg { background: #eef1f6; padding: 24px 12px; font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #3b4256; }
.email-wrapper { max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 6px 24px rgba(45, 51, 91, 0.10); }
.email-header { background: #2d335b; padding: 28px 32px; text-align: center; }
.email-header img { max-height: 56px; max-width: 200px; display: inline-block; }
.email-header-name { color: #ffffff; font-size: 20px; font-weight: 600; margin-top: 8px; letter-spacing: .3px; }
.email-body { padding: 32px; font-size: 15px; line-height: 1.6; }
.email-body p { margin: 0 0 14px; }
.email-body h2 { font-size: 18px; color: #2d335b; margin: 24px 0 10px; }
.email-body table.dados { width: 100%; border-collapse: collapse; margin: 8px 0 18px; }
.email-body table.dados td { padding: 8px 10px; border-bottom: 1px solid #eef1f6; font-size: 14px; }
.email-body table.dados td.rotulo { color: #8a90a6; width: 40%; }
.email-body table.itens { width: 100%; border-collapse: collapse; margin: 10px 0 18px; font-size: 14px; }
.email-body table.itens th { background: #f4f6fb; text-align: left; padding: 10px; color: #6b7191; font-weight: 600; }
.email-body table.itens td { padding: 10px; border-bottom: 1px solid #eef1f6; }
.email-body .total { font-size: 17px; color: #2d335b; text-align: right; margin-top: 6px; }
.btn-pagar { display: inline-block; background: #2ecc71; color: #ffffff !important; text-decoration: none; padding: 13px 26px; border-radius: 8px; font-weight: 600; margin: 6px 6px 6px 0; }
.btn-link { display: inline-block; background: #2d335b; color: #ffffff !important; text-decoration: none; padding: 13px 26px; border-radius: 8px; font-weight: 600; margin: 6px 6px 6px 0; }
.box-pagamento { background: #f4f6fb; border-radius: 10px; padding: 18px; margin: 14px 0; }
.box-pagamento .rotulo { color: #8a90a6; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
.box-pagamento code { display: block; word-break: break-all; background: #ffffff; border: 1px solid #e2e6f0; border-radius: 6px; padding: 10px; font-size: 12px; color: #3b4256; }
.email-footer { background: #f4f6fb; padding: 22px 32px; text-align: center; font-size: 12px; color: #8a90a6; line-height: 1.6; }
CSS;
    }

    private function defaultCorpoOs()
    {
        return <<<'HTML'
<p>Olá, <strong>{{cliente_nome}}</strong>!</p>
<p>Segue o resumo da sua Ordem de Serviço <strong>#{{os_numero}}</strong>.</p>
<table class="dados" role="presentation" cellpadding="0" cellspacing="0">
    <tr><td class="rotulo">Status</td><td>{{os_status}}</td></tr>
    <tr><td class="rotulo">Abertura</td><td>{{os_data_inicial}}</td></tr>
    <tr><td class="rotulo">Encerramento</td><td>{{os_data_final}}</td></tr>
    <tr><td class="rotulo">Garantia</td><td>{{os_garantia}}</td></tr>
</table>
{{os_detalhes_html}}
{{os_itens_html}}
<p class="total">Total: <strong>{{os_valor_total}}</strong></p>
<p>Qualquer dúvida, estamos à disposição.</p>
HTML;
    }

    private function defaultCorpoCobranca()
    {
        return <<<'HTML'
<p>Olá, <strong>{{cliente_nome}}</strong>!</p>
<p>Você tem uma cobrança no valor de <strong>{{cobranca_valor}}</strong> com vencimento em <strong>{{cobranca_vencimento}}</strong>.</p>
{{cobranca_pagamento_html}}
<p style="color:#8a90a6; font-size:13px;">{{cobranca_descricao}}</p>
<p>Assim que o pagamento for identificado, você receberá a confirmação. Obrigado!</p>
HTML;
    }
}
