<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/tributacao_helper.php';

class TributacaoHelperTest extends TestCase
{
    public function testPerfisTemPresets(): void
    {
        $p = tributacao_perfis();
        $this->assertSame(['mei', 'simples', 'normal'], array_keys($p));
        $this->assertSame(2, $p['mei']['preset']['op_simp_nac']);
        $this->assertSame('102', $p['simples']['preset']['csosn_padrao']);
        $this->assertNull($p['normal']['preset']['csosn_padrao']);
        $this->assertSame('5102', $p['normal']['preset']['cfop_padrao']);
    }

    public function testPresetReforma2026(): void
    {
        $r = tributacao_preset_reforma(2026);
        $this->assertSame(0.9, $r['reforma_cbs']);
        $this->assertSame(0.1, $r['reforma_ibs_uf']);
        $this->assertSame('000001', $r['reforma_cclasstrib']);
        $this->assertFalse($r['estimado']);
    }

    public function testPresetReformaOutroAnoEhEstimado(): void
    {
        $r = tributacao_preset_reforma(2028);
        $this->assertTrue($r['estimado']);
        $this->assertSame(0.9, $r['reforma_cbs']);
    }

    public function testPerfilAtual(): void
    {
        $this->assertSame('mei', tributacao_perfil_atual((object) ['op_simp_nac' => 2]));
        $this->assertSame('simples', tributacao_perfil_atual(['op_simp_nac' => '3']));
        $this->assertSame('normal', tributacao_perfil_atual((object) ['op_simp_nac' => 1]));
        $this->assertSame('normal', tributacao_perfil_atual([]));
    }
}
