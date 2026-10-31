<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/tributacao_helper.php';

class TributacaoParametrosTest extends TestCase
{
    private function linhas(): array
    {
        return [
            ['ano' => 2026, 'cbs' => '0.9000', 'ibs_uf' => '0.1000', 'ibs_mun' => '0.0000', 'confirmado' => 1, 'fonte' => 'LC 214/2025 - ano de teste'],
            ['ano' => 2027, 'cbs' => '0.9000', 'ibs_uf' => '0.0500', 'ibs_mun' => '0.0500', 'confirmado' => 0, 'fonte' => 'a confirmar'],
        ];
    }

    public function testAnoExatoNaoEhEstimado(): void
    {
        $r = tributacao_parametros_do_ano(2027, $this->linhas());
        $this->assertSame(2027, $r['ano']);
        $this->assertSame(0.9, $r['cbs']);
        $this->assertSame(0.05, $r['ibs_uf']);
        $this->assertSame(0.05, $r['ibs_mun']);
        $this->assertFalse($r['confirmado']);
        $this->assertFalse($r['estimado']);
        $this->assertSame('a confirmar', $r['fonte']);
    }

    public function testAnoConfirmadoRetornaConfirmado(): void
    {
        $r = tributacao_parametros_do_ano(2026, $this->linhas());
        $this->assertTrue($r['confirmado']);
        $this->assertFalse($r['estimado']);
        $this->assertSame(0.1, $r['ibs_uf']);
    }

    public function testAnoAusenteUsaMaiorAnteriorComoEstimado(): void
    {
        $r = tributacao_parametros_do_ano(2030, $this->linhas());
        $this->assertSame(2030, $r['ano']);
        $this->assertSame(0.05, $r['ibs_uf']);
        $this->assertTrue($r['estimado']);
    }

    public function testMaiorAnteriorEhEscolhidoEntreVarios(): void
    {
        $linhas = array_merge($this->linhas(), [
            ['ano' => 2028, 'cbs' => 8.8, 'ibs_uf' => 0, 'ibs_mun' => 0, 'confirmado' => 0, 'fonte' => 'x'],
        ]);
        $r = tributacao_parametros_do_ano(2029, $linhas);
        $this->assertSame(8.8, $r['cbs']);
        $this->assertTrue($r['estimado']);
    }

    public function testSemAnteriorCaiNoPresetDe2026(): void
    {
        $r = tributacao_parametros_do_ano(2025, $this->linhas());
        $this->assertSame(2026, $r['ano']);
        $this->assertSame(0.9, $r['cbs']);
        $this->assertSame(0.1, $r['ibs_uf']);
        $this->assertSame(0.0, $r['ibs_mun']);
        $this->assertTrue($r['estimado']);
    }

    public function testSemLinhasCaiNoPreset(): void
    {
        $r = tributacao_parametros_do_ano(2026, []);
        $this->assertSame(2026, $r['ano']);
        $this->assertSame(0.9, $r['cbs']);
        $this->assertTrue($r['estimado']);
        $this->assertSame(
            ['ano', 'cbs', 'ibs_uf', 'ibs_mun', 'confirmado', 'estimado', 'fonte'],
            array_keys($r)
        );
    }

    public function testPresetReformaContinuaCompativel(): void
    {
        $r = tributacao_preset_reforma(2026);
        $this->assertSame(0.9, $r['reforma_cbs']);
        $this->assertFalse($r['estimado']);
    }
}
