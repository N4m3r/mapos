<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Verificação por código (token) na aprovação da OS.
 *
 * Quando ativada, o cliente precisa informar um código de uso único — enviado
 * por WhatsApp/e-mail — antes de poder aprovar/reprovar pelo link público.
 *
 * A exigência pode ser ligada por cliente (clientes.aprovacao_exige_token) e/ou
 * direto na OS (os.aprovacao_exige_token). É exigido se qualquer uma estiver on.
 *
 * Idempotente: cada coluna só é criada se ainda faltar.
 */
class Migration_add_aprovacao_verificacao_token extends CI_Migration
{
    private $colunasOs = [
        'aprovacao_exige_token' => "`aprovacao_exige_token` TINYINT(1) NOT NULL DEFAULT 0",
        'aprovacao_codigo' => "`aprovacao_codigo` VARCHAR(64) NULL DEFAULT NULL",
        'aprovacao_codigo_expira' => "`aprovacao_codigo_expira` DATETIME NULL DEFAULT NULL",
        'aprovacao_codigo_validado' => "`aprovacao_codigo_validado` TINYINT(1) NOT NULL DEFAULT 0",
        'aprovacao_codigo_tentativas' => "`aprovacao_codigo_tentativas` INT NOT NULL DEFAULT 0",
        'aprovacao_codigo_canal' => "`aprovacao_codigo_canal` VARCHAR(20) NULL DEFAULT NULL",
    ];

    private $colunasClientes = [
        'aprovacao_exige_token' => "`aprovacao_exige_token` TINYINT(1) NOT NULL DEFAULT 0",
    ];

    public function up()
    {
        if ($this->db->table_exists('os')) {
            foreach ($this->colunasOs as $coluna => $definicao) {
                if (! $this->db->field_exists($coluna, 'os')) {
                    $this->db->query("ALTER TABLE `os` ADD COLUMN {$definicao}");
                }
            }
        }

        if ($this->db->table_exists('clientes')) {
            foreach ($this->colunasClientes as $coluna => $definicao) {
                if (! $this->db->field_exists($coluna, 'clientes')) {
                    $this->db->query("ALTER TABLE `clientes` ADD COLUMN {$definicao}");
                }
            }
        }
    }

    public function down()
    {
        foreach (array_keys($this->colunasOs) as $coluna) {
            if ($this->db->field_exists($coluna, 'os')) {
                $this->dbforge->drop_column('os', $coluna);
            }
        }
        foreach (array_keys($this->colunasClientes) as $coluna) {
            if ($this->db->field_exists($coluna, 'clientes')) {
                $this->dbforge->drop_column('clientes', $coluna);
            }
        }
    }
}
