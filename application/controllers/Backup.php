<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Backup automatico do banco (ADR-005, F2). So pela linha de comando:
 *
 *   php index.php backup/executar
 *
 * Agende uma vez por dia (cron no Linux/docker, Agendador de Tarefas no Windows).
 */
class Backup extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (! $this->input->is_cli_request()) {
            show_404();
        }
    }

    public function executar()
    {
        $this->load->library('backup_banco');

        try {
            $resultado = $this->backup_banco->salvarAutomatico();
        } catch (Throwable $e) {
            log_message('error', 'Backup automatico falhou: ' . $e->getMessage());
            fwrite(STDERR, 'ERRO: ' . $e->getMessage() . PHP_EOL);
            exit(1);
        }

        $linha = sprintf(
            'Backup salvo: %s (%s KB)',
            $resultado['arquivo'],
            number_format($resultado['tamanho'] / 1024, 1, ',', '.')
        );
        if ($resultado['removidos']) {
            $linha .= '; removidos pela retencao: ' . implode(', ', $resultado['removidos']);
        }
        log_message('info', $linha);
        echo $linha . PHP_EOL;
    }
}
