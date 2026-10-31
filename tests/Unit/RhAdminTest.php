<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Rh_admin.php';

class RhAdminTest extends TestCase
{
    public function testHorasParaMinutos(): void
    {
        $this->assertSame(528, Rh_admin::horasParaMinutos('08:48'));
        $this->assertSame(528, Rh_admin::horasParaMinutos('8:48'));
        $this->assertSame(528, Rh_admin::horasParaMinutos('8h48'));
        $this->assertSame(480, Rh_admin::horasParaMinutos('480'));   // minutos puros (compatibilidade)
        $this->assertSame(480, Rh_admin::horasParaMinutos(' 08:00 '));
        $this->assertNull(Rh_admin::horasParaMinutos(''));
        $this->assertNull(Rh_admin::horasParaMinutos('abc'));
        $this->assertNull(Rh_admin::horasParaMinutos('08:75'));
        $this->assertNull(Rh_admin::horasParaMinutos('0'));
        $this->assertNull(Rh_admin::horasParaMinutos('25:00'));
        $this->assertNull(Rh_admin::horasParaMinutos('-60'));
    }

    public function testMinutosParaHoras(): void
    {
        $this->assertSame('08:48', Rh_admin::minutosParaHoras(528));
        $this->assertSame('08:00', Rh_admin::minutosParaHoras('480'));
        $this->assertSame('00:00', Rh_admin::minutosParaHoras(-5));
        $this->assertSame(528, Rh_admin::horasParaMinutos(Rh_admin::minutosParaHoras(528)));
    }

    public function testVoltarSeguroAceitaRotasPermitidas(): void
    {
        foreach (['rh/folha', 'rh/folha/2026-10', 'rh/ficha/12', 'rh/espelho/3/2026-10', 'rh/holerite/3', 'rh/colaboradores', 'rh/editarColaborador/9', '/rh/ocorrencias'] as $r) {
            $this->assertSame(ltrim($r, '/'), Rh_admin::voltarSeguro($r, 'rh/x'), $r);
        }
    }

    public function testVoltarSeguroRejeitaOutros(): void
    {
        foreach (['http://evil.com', '//evil.com', 'https://x/rh/folha', '../admin', 'rh/../admin', 'rh/ficha/abc', 'rh/excluirColaborador', 'usuarios', 'rh/ficha/1?x=2', 'javascript:alert(1)', '', null] as $r) {
            $this->assertSame('rh/padrao', Rh_admin::voltarSeguro($r, 'rh/padrao'), (string) $r);
        }
    }

    public function testSecaoERotuloVoltar(): void
    {
        $this->assertSame(['folha', 'Folha', 'rh/folha'], Rh_admin::secaoDe('rh/folha/2026-10'));
        $this->assertSame('colaboradores', Rh_admin::secaoDe('rh/ficha/4')[0]);
        $this->assertSame('Voltar à ficha', Rh_admin::rotuloVoltar('rh/ficha/4'));
        $this->assertSame('Voltar para folha', Rh_admin::rotuloVoltar('rh/folha'));
    }

    public function testRotuloContagem(): void
    {
        $this->assertSame('', Rh_admin::rotuloContagem(0));
        $this->assertSame('7', Rh_admin::rotuloContagem(7));
        $this->assertSame('99+', Rh_admin::rotuloContagem(150));
    }

    public function testSituacaoHolerite(): void
    {
        $this->assertSame('nenhum', Rh_admin::situacaoHolerite(null));
        $this->assertSame('nenhum', Rh_admin::situacaoHolerite((object) ['arquivo_base64' => '']));
        $this->assertSame('anexado', Rh_admin::situacaoHolerite((object) ['arquivo_base64' => 'x', 'liberado_colaborador' => 0]));
        $this->assertSame('liberado', Rh_admin::situacaoHolerite((object) ['arquivo_base64' => 'x', 'liberado_colaborador' => 1]));
    }
}
