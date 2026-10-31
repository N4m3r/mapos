<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/menu_tecnico_helper.php';

/**
 * Montagem do menu lateral do tecnico e normalizacao de telefone do perfil.
 */
class MenuTecnicoTest extends TestCase
{
    private function ids(array $secoes, string $sec): array
    {
        foreach ($secoes as $s) {
            if ($s['id'] === $sec) {
                return array_column($s['itens'], 'id');
            }
        }

        return [];
    }

    public function testTecnicoSemRhVeSoTrabalhoEConta(): void
    {
        $m = menu_tecnico_montar(['tecnico' => true, 'atual' => 'tecnico/os']);
        $this->assertSame(['inicio', 'os', 'projetos', 'fila', 'relatorio'], $this->ids($m, 'trabalho'));
        $this->assertSame([], $this->ids($m, 'rh'));
        $this->assertSame(['perfil', 'sair'], $this->ids($m, 'conta'));
    }

    public function testRhVinculadoComEsemPonto(): void
    {
        $com = menu_tecnico_montar(['colaborador_area' => true, 'vinculado' => true, 'ponto' => true]);
        $this->assertSame(['ponto', 'espelho', 'ocorrencias', 'ausencias', 'holerite', 'meusdados'], $this->ids($com, 'rh'));
        $sem = menu_tecnico_montar(['colaborador_area' => true, 'vinculado' => true, 'ponto' => false]);
        $this->assertNotContains('ponto', $this->ids($sem, 'rh'));
        $this->assertSame([], $this->ids($sem, 'trabalho'));
        $this->assertNotContains('perfil', $this->ids($sem, 'conta'));
    }

    public function testRhNaoVinculadoMostraItemDesabilitado(): void
    {
        $m = menu_tecnico_montar(['colaborador_area' => true, 'vinculado' => false, 'ponto' => true]);
        $rh = $m[0]['itens'];
        $this->assertCount(1, $rh);
        $this->assertTrue($rh[0]['desabilitado']);
        $this->assertNull($rh[0]['rota']);
    }

    public function testContaOfflineSistemaEFila(): void
    {
        $m = menu_tecnico_montar(['tecnico' => true, 'offline' => true, 'sistema' => true, 'fila_qtd' => 3]);
        $this->assertSame(['perfil', 'offline', 'instalar', 'sistema', 'sair'], $this->ids($m, 'conta'));
        $fila = array_values(array_filter($m[0]['itens'], fn ($i) => $i['id'] === 'fila'))[0];
        $this->assertSame(3, $fila['badge']);
        $m2 = menu_tecnico_montar(['tecnico' => true, 'fila_qtd' => -2]);
        $this->assertSame(0, $m2[0]['itens'][3]['badge']);
    }

    public function testItemAtivoEApelidos(): void
    {
        $this->assertTrue(menu_tecnico_ativo('tecnico', 'tecnico'));
        $this->assertFalse(menu_tecnico_ativo('tecnico/os', 'tecnico'));
        $this->assertTrue(menu_tecnico_ativo('tecnico/projeto/12', 'tecnico/projetos', ['tecnico/projeto']));
        $this->assertTrue(menu_tecnico_ativo('index.php/colaborador/meusdados', 'colaborador/meusDados'));
        $this->assertFalse(menu_tecnico_ativo('tecnico/projetos', 'tecnico/os'));
        $m = menu_tecnico_montar(['tecnico' => true, 'atual' => 'tecnico/visualizar/5']);
        $ativos = array_column(array_filter($m[0]['itens'], fn ($i) => $i['ativo']), 'id');
        $this->assertSame(['os'], array_values($ativos));
    }

    public function testIniciais(): void
    {
        $this->assertSame('MS', menu_tecnico_iniciais('maria da Silva'));
        $this->assertSame('J', menu_tecnico_iniciais(' João '));
        $this->assertSame('?', menu_tecnico_iniciais(''));
    }

    #[DataProvider('telefones')]
    public function testTelefone(string $entrada, bool $ok, string $valor): void
    {
        $this->assertSame(['ok' => $ok, 'valor' => $valor], menu_tecnico_telefone($entrada));
    }

    public static function telefones(): array
    {
        return [
            'vazio' => ['', true, ''],
            'celular' => ['11912345678', true, '(11) 91234-5678'],
            'fixo mascarado' => ['(11) 3456-7890', true, '(11) 3456-7890'],
            'com 55' => ['+55 11 91234-5678', true, '(11) 91234-5678'],
            'curto' => ['1234-5678', false, ''],
            'ddd invalido' => ['0191234567', false, ''],
            'celular sem 9' => ['11812345678', false, ''],
            'letras' => ['abc', false, ''],
        ];
    }
}
