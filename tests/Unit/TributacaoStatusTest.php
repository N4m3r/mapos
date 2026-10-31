<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/tributacao_tabelas_helper.php';

class TributacaoStatusTest extends TestCase
{
    private const HOJE = '2026-10-31';

    /** Todas as tabelas oficiais importadas na data informada. */
    private function tabelasImportadas(string $em): array
    {
        $out = [];
        foreach (array_keys(trib_tabelas_definicao()) as $k) {
            $out[$k] = ['versao' => 'IT 2026.001', 'importado_em' => $em . ' 10:00:00'];
        }

        return $out;
    }

    private function situacao(array $itens, string $prefixo): ?string
    {
        foreach ($itens as $i) {
            if (str_starts_with($i['item'], $prefixo)) {
                return $i['situacao'];
            }
        }

        return null;
    }

    public function testTabelaNuncaImportadaEDesatualizada(): void
    {
        $versoes = $this->tabelasImportadas('2026-10-20');
        $versoes['cst'] = null;
        unset($versoes['credpres']);

        $itens = trib_status_atualizacao($versoes, [], self::HOJE);

        $this->assertSame('desatualizado', $this->situacao($itens, 'Tabela: CST'));
        $this->assertSame('desatualizado', $this->situacao($itens, 'Tabela: Crédito'));
        $this->assertSame('ok', $this->situacao($itens, 'Tabela: cClassTrib'));
    }

    public function testTabelaImportadaHaMaisDe90DiasEAtencao(): void
    {
        $versoes = $this->tabelasImportadas('2026-10-20');
        $versoes['cclasstrib'] = ['versao' => null, 'importado_em' => '2026-06-30 08:00:00']; // 123 dias

        $itens = trib_status_atualizacao($versoes, [], self::HOJE);

        $this->assertSame('atencao', $this->situacao($itens, 'Tabela: cClassTrib'));
    }

    public function testTabelaImportadaHa90DiasAindaEOk(): void
    {
        $versoes = $this->tabelasImportadas('2026-08-02'); // exatamente 90 dias

        $itens = trib_status_atualizacao($versoes, [], self::HOJE);

        $this->assertSame('ok', $this->situacao($itens, 'Tabela: CST'));
    }

    public function testLibComVersaoAntigaEAtencao(): void
    {
        $libs = [['pacote' => 'nfephp-org/sped-nfe', 'instalada' => '5.2.9', 'ultima' => 'v5.3.0']];

        $itens = trib_status_atualizacao($this->tabelasImportadas('2026-10-20'), $libs, self::HOJE);

        $this->assertSame('atencao', $this->situacao($itens, 'Biblioteca: nfephp-org/sped-nfe'));
        $this->assertStringContainsString('5.3.0', $itens[array_key_last($itens)]['mensagem']);
    }

    public function testLibAtualizadaEOk(): void
    {
        $libs = [['pacote' => 'hadder/nfse-nacional', 'instalada' => '1.0.22', 'ultima' => '1.0.22']];

        $itens = trib_status_atualizacao($this->tabelasImportadas('2026-10-20'), $libs, self::HOJE);

        $this->assertSame('ok', $this->situacao($itens, 'Biblioteca: hadder/nfse-nacional'));
    }

    public function testLibSemUltimaVersaoConhecidaEAtencao(): void
    {
        $libs = [['pacote' => 'hadder/nfse-nacional', 'instalada' => '1.0.22', 'ultima' => null]];

        $itens = trib_status_atualizacao($this->tabelasImportadas('2026-10-20'), $libs, self::HOJE);

        $this->assertSame('atencao', $this->situacao($itens, 'Biblioteca: hadder/nfse-nacional'));
    }

    public function testListaTemUmItemPorTabelaMaisUmPorLib(): void
    {
        $libs = [['pacote' => 'a/b', 'instalada' => '1.0.0', 'ultima' => '1.0.0']];

        $itens = trib_status_atualizacao($this->tabelasImportadas('2026-10-20'), $libs, self::HOJE);

        $this->assertCount(count(trib_tabelas_definicao()) + 1, $itens);
        foreach ($itens as $i) {
            $this->assertArrayHasKey('mensagem', $i);
            $this->assertContains($i['situacao'], ['ok', 'atencao', 'desatualizado']);
        }
    }
}
