<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/emitente_helper.php';

/**
 * Helper do emitente: cabecalho dos documentos e checklist fiscal.
 *
 * O checklist e o que a tela do emitente mostra para dizer o que falta antes
 * de emitir. Ele precisa dar o mesmo veredito que a emissao: CNPJ, IE, endereco
 * e codigo IBGE vindos do emitente/configuracao, e o certificado como item
 * separado.
 */
class EmitenteHelperTest extends TestCase
{
    /** @param array<string, mixed> $extra */
    private function emitente(array $extra = []): object
    {
        return (object) ($extra + [
            'nome' => 'Acme Ltda',
            'nome_fantasia' => 'Acme',
            'cnpj' => '11.222.333/0001-44',
            'ie' => '123456789',
            'inscricao_municipal' => '9988',
            'rua' => 'Rua A',
            'numero' => '10',
            'complemento' => 'Sala 2',
            'bairro' => 'Centro',
            'cidade' => 'Manaus',
            'uf' => 'AM',
            'cep' => '69000-000',
            'telefone' => '(92) 99999-9999',
            'email' => 'contato@acme.com.br',
            'url_logo' => 'http://localhost/assets/uploads/logo.png',
        ]);
    }

    public function testNomeExibidoPrefereFantasia(): void
    {
        $this->assertSame('Acme', emitente_nome_exibicao($this->emitente()));

        $semFantasia = $this->emitente(['nome_fantasia' => '']);
        $this->assertSame('Acme Ltda', emitente_nome_exibicao($semFantasia));
    }

    public function testNomeExibidoToleraEmitenteNulo(): void
    {
        // Sem emitente cadastrado nao ha nome a exibir: a tela de impressao e
        // que cuida do aviso "configure os dados do emitente".
        $this->assertSame('', emitente_nome_exibicao(null));
        $this->assertSame('', emitente_nome_exibicao('texto solto'));
    }

    public function testCabecalhoTrazIdentificacaoEEndereco(): void
    {
        $linhas = emitente_cabecalho($this->emitente());

        $this->assertNotEmpty($linhas);

        $texto = implode("\n", $linhas);
        // Nos documentos o CNPJ sai como foi cadastrado (mascarado); quem
        // manda so digitos e o XML fiscal.
        $this->assertStringContainsString('CNPJ: 11.222.333/0001-44', $texto);
        $this->assertStringContainsString('IE: 123456789', $texto);
        $this->assertStringContainsString('IM: 9988', $texto);
        $this->assertStringContainsString('Manaus', $texto);
        $this->assertStringContainsString('Sala 2', $texto, 'complemento entra no endereco');
    }

    public function testCabecalhoNaoMostraCnpjZerado(): void
    {
        $linhas = emitente_cabecalho($this->emitente(['cnpj' => '00.000.000/0000-00']));

        $this->assertStringNotContainsString('00000000000000', implode("\n", $linhas));
    }

    public function testEnderecoCondensaLogradouroECidade(): void
    {
        $endereco = emitente_endereco($this->emitente());

        $this->assertStringContainsString('Rua A', $endereco);
        $this->assertStringContainsString('Manaus', $endereco);
        $this->assertSame('', emitente_endereco(null));
    }

    public function testChecklistReprovaEmitenteAusente(): void
    {
        $itens = emitente_checklist_fiscal(null);
        $resumo = emitente_checklist_resumo($itens);

        $this->assertNotEmpty($itens);
        $this->assertSame(0, $resumo['prontos']);
        $this->assertSame(count($itens), $resumo['total']);

        foreach ($itens as $item) {
            $this->assertArrayHasKey('titulo', $item);
            $this->assertArrayHasKey('detalhe', $item);
            $this->assertFalse((bool) $item['ok'], 'Nada pode passar sem emitente: ' . $item['titulo']);
        }
    }

    public function testChecklistAprovaEmitenteCompletoComConfiguracao(): void
    {
        $config = (object) ['codigo_municipio' => '1302603'];

        $itens = emitente_checklist_fiscal($this->emitente(), $config, true);
        $resumo = emitente_checklist_resumo($itens);

        $this->assertSame($resumo['total'], $resumo['prontos'], 'Emitente completo deveria passar em tudo');

        foreach ($itens as $item) {
            $this->assertTrue((bool) $item['ok'], 'Item reprovado: ' . $item['titulo'] . ' — ' . $item['detalhe']);
        }
    }

    public function testChecklistApontaOndeResolverCodigoIbgeECertificado(): void
    {
        $itens = emitente_checklist_fiscal($this->emitente(), null, false);
        $porTitulo = [];

        foreach ($itens as $item) {
            $porTitulo[$item['titulo']] = $item;
        }

        $this->assertFalse($porTitulo['Código IBGE do município']['ok']);
        $this->assertSame('nfe/configuracoes', $porTitulo['Código IBGE do município']['acao']);

        $this->assertFalse($porTitulo['Certificado digital A1']['ok']);
        $this->assertSame('nfe/configuracoes', $porTitulo['Certificado digital A1']['acao']);
    }

    public function testChecklistAceitaIeIsento(): void
    {
        $itens = emitente_checklist_fiscal($this->emitente(['ie' => 'ISENTO']), (object) ['codigo_municipio' => '1302603'], true);

        foreach ($itens as $item) {
            if ($item['titulo'] === 'Inscrição estadual') {
                $this->assertTrue((bool) $item['ok'], 'ISENTO vale como IE informada');

                return;
            }
        }

        $this->fail('Checklist sem o item de inscricao estadual');
    }

    public function testResumoDeListaVazia(): void
    {
        $this->assertSame(['prontos' => 0, 'total' => 0], emitente_checklist_resumo([]));
    }
}
