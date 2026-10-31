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

    /**
     * Alerta de ponto parado (ADR-014): php index.php lembretes/pontos_parados [simular]
     * Precisa ALERTA_REPASSE_ATIVO=true no .env; sem WhatsApp configurado so registra no log.
     */
    public function pontos_parados($modo = null)
    {
        $simular = $modo === 'simular';
        $this->load->library('alerta_repasse');

        if (! $this->alerta_repasse->disponivel()) {
            fwrite(STDERR, 'Coluna obra_pontos.alertado_em nao existe. Rode: php index.php tools migrate' . PHP_EOL);
            exit(1);
        }
        if (! $simular && ! $this->alerta_repasse->ativo()) {
            echo 'Alerta de ponto parado desligado (ALERTA_REPASSE_ATIVO=false no .env). Use "simular" para ver.' . PHP_EOL;

            return;
        }

        $resultado = $this->alerta_repasse->executar($simular);
        foreach ($resultado as $r) {
            echo ($simular ? '[simulacao] ' : '') . sprintf('%s %s (%d pontos): %s', $r['tipo'], $r['nome'], $r['pontos'], $r['enviado'] ? 'enviado' : ($r['erro'] ?: 'nao enviado')) . PHP_EOL;
            if ($simular) {
                echo '    ' . str_replace("\n", "\n    ", $r['mensagem']) . PHP_EOL;
            }
        }
        echo 'Alertas ' . ($simular ? 'previstos: ' . count($resultado) : 'enviados: ' . count(array_filter($resultado, static fn ($r) => $r['enviado']))) . PHP_EOL;
    }
}
