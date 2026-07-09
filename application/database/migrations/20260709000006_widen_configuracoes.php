<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_widen_configuracoes extends CI_Migration
{
    public function up()
    {
        // A tabela configuracoes foi criada com config/valor em VARCHAR(20),
        // pequeno demais para as chaves e valores novos (automacao_*,
        // notif_intervalo_disparo, layout/CSS de e-mail). Sem alargar, o INSERT
        // dessas configs falha/trunca e nada persiste.
        $this->db->query('ALTER TABLE `configuracoes` MODIFY `config` VARCHAR(60) NOT NULL');
        $this->db->query('ALTER TABLE `configuracoes` MODIFY `valor` TEXT NULL');
    }

    public function down()
    {
        // Não reverte: voltar para VARCHAR(20) truncaria dados existentes.
    }
}
