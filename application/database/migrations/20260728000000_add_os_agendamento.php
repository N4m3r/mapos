<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Agendamento do atendimento do técnico na OS.
 *
 * `os.data_agendamento` (DATETIME) guarda a data e a HORA marcadas para a
 * visita do técnico — definida na Central de Atendimento ao atribuir/trocar o
 * técnico. Diferente de `dataInicial/dataFinal` (que são só datas do ciclo da
 * OS), este campo carrega o horário do compromisso em campo.
 */
class Migration_add_os_agendamento extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('os') && ! $this->db->field_exists('data_agendamento', 'os')) {
            $this->dbforge->add_column('os', [
                'data_agendamento' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'comment' => 'Data/hora agendada do atendimento do técnico',
                ],
            ]);
        }

        log_message('info', 'Migration add_os_agendamento executada com sucesso');
    }

    public function down()
    {
        if ($this->db->field_exists('data_agendamento', 'os')) {
            $this->dbforge->drop_column('os', 'data_agendamento');
        }
    }
}
