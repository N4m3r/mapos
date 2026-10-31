<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/simulador_ibscbs_helper.php';

class SimuladorIbsCbsTest extends TestCase
{
    private function base(array $o = []): array
    {
        return $o + ['faturamento_mensal' => 10000, 'perc_clientes_empresa' => 50, 'aliquota_das_efetiva' => 10,
            'parcela_ibscbs_no_das' => 30, 'aliquota_cbs' => 0.9, 'aliquota_ibs' => 0.1, 'creditos_compras_mensais' => 0];
    }

    public function testCenarioDas(): void
    {
        $r = simulador_ibscbs($this->base());
        $this->assertSame(1000.0, $r['das']['imposto_total']);
        $this->assertSame(150.0, $r['das']['credito_cliente']);
        $this->assertSame(850.0, $r['das']['custo_efetivo']);
    }

    public function testCenarioRegularComCreditos(): void
    {
        $r = simulador_ibscbs($this->base(['aliquota_cbs' => 8.8, 'aliquota_ibs' => 17.7, 'creditos_compras_mensais' => 500]));
        $this->assertSame(2150.0, $r['regular']['ibscbs_liquido']);
        $this->assertSame(2850.0, $r['regular']['imposto_total']);
        $this->assertSame(1325.0, $r['regular']['credito_cliente']);
        $this->assertSame('das', $r['melhor']);
        $this->assertStringContainsString('contador', $r['recomendacao']);
    }

    public function testRegularVantajosoComMuitoCredito(): void
    {
        $r = simulador_ibscbs($this->base(['creditos_compras_mensais' => 0, 'perc_clientes_empresa' => 100]));
        $this->assertSame('empate', $r['melhor']);
        $r = simulador_ibscbs($this->base(['creditos_compras_mensais' => 400, 'perc_clientes_empresa' => 100]));
        $this->assertSame('regular', $r['melhor']);
    }

    public function testEntradasInvalidasNaoQuebram(): void
    {
        $r = simulador_ibscbs(['faturamento_mensal' => 0, 'perc_clientes_empresa' => 500]);
        $this->assertSame(0.0, $r['das']['imposto_total']);
        $this->assertSame(0.0, $r['regular']['perc_faturamento']);
    }
}
