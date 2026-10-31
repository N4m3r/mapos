<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/tipo_servico_helper.php';

class TipoServicoTest extends TestCase
{
    public function testListaFixa(): void
    {
        $t = tipos_servico();
        $this->assertCount(9, $t);
        $this->assertSame('CFTV / câmeras', tipo_servico_rotulo('cftv'));
        $this->assertSame('bx-bolt-circle', tipo_servico_icone('cerca_eletrica'));
    }

    public function testValido(): void
    {
        $this->assertTrue(tipo_servico_valido('rede'));
        $this->assertFalse(tipo_servico_valido('xyz'));
        $this->assertFalse(tipo_servico_valido(''));
        $this->assertFalse(tipo_servico_valido(null));
        $this->assertSame('', tipo_servico_rotulo('xyz'));
        $this->assertNull(tipo_servico_normalizar('xyz'));
        $this->assertSame('rede', tipo_servico_normalizar(' rede '));
    }

    public function testFiltroGeraisMaisTipo(): void
    {
        $fs = [
            (object) ['id' => 1, 'tipo_servico' => null],
            (object) ['id' => 2, 'tipo_servico' => 'cftv'],
            (object) ['id' => 3, 'tipo_servico' => 'alarme'],
            (object) ['id' => 4, 'tipo_servico' => ''],
        ];
        $ids = fn ($r) => array_map(fn ($f) => $f->id, $r);
        $this->assertSame([1, 2, 4], $ids(tipo_servico_filtrar($fs, 'cftv')));
        $this->assertSame([1, 4], $ids(tipo_servico_filtrar($fs, null)));
    }

    public function testSelo(): void
    {
        $this->assertSame('', tipo_servico_selo(''));
        $this->assertStringContainsString('bx-wifi', tipo_servico_selo('rede'));
    }
}
