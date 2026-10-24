<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Lembretes automaticos ao cliente (ADR-005, F6). So pela linha de comando:
 *
 *   php index.php lembretes/executar          envia (precisa LEMBRETES_ATIVO=true)
 *   php index.php lembretes/executar simular  so mostra o que seria enviado
 */
class Lembretes extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (! $this->input->is_cli_request()) {
            show_404();
        }
        $this->load->library('lembretes_cliente');
    }

    public function executar($modo = null)
    {
        $simular = $modo === 'simular';

        if (! $this->lembretes_cliente->disponivel()) {
            fwrite(STDERR, 'Tabela lembretes_enviados nao existe. Rode: php index.php tools migrate' . PHP_EOL);
            exit(1);
        }
        if (! $simular && ! $this->lembretes_cliente->ativo()) {
            echo 'Lembretes desligados (LEMBRETES_ATIVO=false no .env). Use "simular" para ver o que seria enviado.' . PHP_EOL;

            return;
        }

        $resultado = $this->lembretes_cliente->executar($simular);
        foreach ($resultado as $r) {
            $linha = sprintf(
                '%s OS #%d (%s): %s',
                $r['tipo'],
                $r['os'],
                $r['cliente'],
                $r['canais'] ? implode(', ', $r['canais']) : 'nao enviado'
            );
            if ($r['erro']) {
                $linha .= ' - ' . $r['erro'];
            }
            echo ($simular ? '[simulacao] ' : '') . $linha . PHP_EOL;
            if ($simular) {
                echo '    ' . str_replace("\n", "\n    ", $r['mensagem']) . PHP_EOL;
            } else {
                log_message('info', 'Lembrete ao cliente: ' . $linha);
            }
        }

        $enviados = count(array_filter($resultado, static fn ($r) => (bool) $r['canais']));
        echo ($simular ? 'Seriam enviados: ' : 'Enviados: ') . ($simular ? count($resultado) : $enviados) . PHP_EOL;
    }
}
