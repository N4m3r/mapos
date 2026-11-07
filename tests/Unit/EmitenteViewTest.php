<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/emitente_helper.php';

if (! function_exists('base_url')) {
    function base_url($uri = '')
    {
        return 'http://localhost/' . ltrim((string) $uri, '/');
    }
}

if (! function_exists('site_url')) {
    function site_url($uri = '')
    {
        return 'http://localhost/index.php/' . ltrim((string) $uri, '/');
    }
}

if (! function_exists('html_escape')) {
    function html_escape($var, $double = true)
    {
        return empty($var) ? $var : htmlspecialchars((string) $var, ENT_QUOTES, 'UTF-8', $double);
    }
}

if (! function_exists('csp_nonce')) {
    function csp_nonce()
    {
        return 'nonce-teste';
    }
}

// convertUrlToUploadsPath vem do general_helper.php, que o bootstrap ja carrega:
// o helper real monta FCPATH . assets/uploads/<basename>. Um stub aqui seria
// codigo morto (a guarda function_exists impede redefinir).

/**
 * Render da tela do emitente.
 *
 * O `$v()` desta view devolve o valor CRU (nao passa por set_value, ao
 * contrario do formulario de usuario), entao o escape tem de estar na saida.
 * Um nome com aspas fecharia o atributo value e injetaria HTML — foi o que o
 * teste de XSS abaixo pegou.
 */
class EmitenteViewTest extends TestCase
{
    /** Nome do arquivo de logo criado no setUp (a view confere com is_file). */
    private string $logoNome;

    protected function setUp(): void
    {
        // A view decide "tem logo" com is_file(convertUrlToUploadsPath($url)), e
        // o helper real resolve para FCPATH . assets/uploads/<basename>. Sem um
        // arquivo de verdade, o caminho "com logo" nunca seria exercitado.
        $this->logoNome = 'logo_teste_' . getmypid() . '_' . uniqid() . '.png';

        $pasta = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'uploads';
        if (! is_dir($pasta)) {
            mkdir($pasta, 0777, true);
        }
        file_put_contents($pasta . DIRECTORY_SEPARATOR . $this->logoNome, 'png');
    }

    protected function tearDown(): void
    {
        $pasta = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'uploads';
        $arquivo = $pasta . DIRECTORY_SEPARATOR . $this->logoNome;

        if (is_file($arquivo)) {
            unlink($arquivo);
        }

        // A pasta nao existe no repositorio (esta no .gitignore). Se o teste foi
        // quem criou e ninguem mais esta usando, ela some de novo.
        if (is_dir($pasta) && count((array) scandir($pasta)) === 2) {
            rmdir($pasta);
        }
    }

    /** @param array<string, mixed> $extra */
    private function emitente(array $extra = []): object
    {
        return (object) array_merge([
            'id' => 1,
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
            'url_logo' => 'http://localhost/assets/uploads/' . $this->logoNome,
        ], $extra);
    }

    /** @return array<string, mixed> */
    private function vars($dados, array $extra = []): array
    {
        $checklist = emitente_checklist_fiscal($dados, null, false);

        return array_merge([
            'dados' => $dados,
            'checklist' => $checklist,
            'resumoChecklist' => emitente_checklist_resumo($checklist),
            'configFiscalExiste' => false,
            'tokenName' => 'MAPOS_TOKEN',
            'tokenHash' => 'hash-teste',
        ], $extra);
    }

    /** @param array<string, mixed> $vars */
    private function render(array $vars): string
    {
        extract($vars);

        ob_start();
        include APPPATH . 'views/mapos/emitente.php';

        return ob_get_clean();
    }

    public function testEdicaoTrazOsDadosCadastrados(): void
    {
        $html = $this->render($this->vars($this->emitente()));

        $this->assertStringContainsString('Dados do emitente', $html);
        $this->assertStringContainsString('11.222.333/0001-44', $html);
        $this->assertStringContainsString('123456789', $html);
        $this->assertStringContainsString('9988', $html);
        $this->assertStringContainsString('Manaus', $html);
        $this->assertStringContainsString('Sala 2', $html);

        $this->assertStringContainsString('mapos/editarEmitente', $html);
        $this->assertStringNotContainsString('mapos/cadastrarEmitente', $html);
        $this->assertStringContainsString('Salvar alterações', $html);
    }

    public function testSemEmitenteMostraFormularioDeCadastro(): void
    {
        $html = $this->render($this->vars(null));

        $this->assertStringContainsString('Dados do emitente', $html);
        $this->assertStringContainsString('mapos/cadastrarEmitente', $html);
        $this->assertStringContainsString('Cadastrar emitente', $html);
        $this->assertStringContainsString('Nenhum dado cadastrado', $html);
        $this->assertStringContainsString('name="userfile"', $html, 'cadastro aceita logo');
    }

    public function testEdicaoOfereceTrocarERemoverLogo(): void
    {
        $html = $this->render($this->vars($this->emitente()));

        $this->assertStringContainsString('mapos/editarLogo', $html);
        $this->assertStringContainsString('mapos/removerLogo', $html);
        $this->assertStringContainsString('Trocar logo', $html);
        $this->assertStringContainsString('Cadastrada', $html);
        $this->assertStringContainsString('data-confirmar', $html, 'remover logo pede confirmacao');
    }

    public function testSemLogoAvisaQueOsDocumentosSaemSoComDados(): void
    {
        $html = $this->render($this->vars($this->emitente(['url_logo' => ''])));

        $this->assertStringContainsString('Não enviada', $html);
        $this->assertStringContainsString('Enviar logo', $html);
        $this->assertStringNotContainsString('mapos/removerLogo', $html, 'sem logo nao ha o que remover');
    }

    public function testEscapaValoresDosCampos(): void
    {
        $html = $this->render($this->vars($this->emitente([
            'nome' => 'Acme & Cia "Matriz"',
            'rua' => 'Rua "A"',
        ])));

        // Um unico nivel de escape: nada de &amp;amp; (duplo).
        $this->assertStringNotContainsString('&amp;amp;', $html);
        $this->assertStringContainsString('Acme &amp; Cia &quot;Matriz&quot;', $html);
        $this->assertStringContainsString('Rua &quot;A&quot;', $html);

        // E o essencial: nenhuma aspa crua fecha o atributo value.
        $this->assertSame(0, preg_match('/value="[^"]*&(?![a-zA-Z]+;)/', $html), 'atributo com & cru');
        $this->assertStringNotContainsString('value="Acme & Cia "', $html);
    }

    public function testChecklistMostraOQueFaltaComAtalhoParaResolver(): void
    {
        $html = $this->render($this->vars($this->emitente(['cnpj' => '', 'ie' => ''])));

        $this->assertStringContainsString('Conferência para emissão', $html);
        $this->assertStringContainsString('emitente-check', $html);
        $this->assertStringContainsString('CNPJ', $html);
        $this->assertStringContainsString('nfe/configuracoes', $html, 'atalho para o codigo IBGE e o certificado');
    }

    public function testKpiDeConferenciaContaOsItensProntos(): void
    {
        $dados = $this->emitente();
        $checklist = emitente_checklist_fiscal($dados, (object) ['codigo_municipio' => '1302603'], true);
        $resumo = emitente_checklist_resumo($checklist);

        $html = $this->render($this->vars($dados, ['checklist' => $checklist, 'resumoChecklist' => $resumo]));

        $this->assertSame($resumo['total'], $resumo['prontos'], 'o fixture deveria passar em tudo');
        $this->assertStringContainsString('Todos os itens conferidos', $html);
        $this->assertStringContainsString('ui-kpi sucesso', $html);
    }

    public function testChecklistComPendenciaUsaOEstruturaDeAlerta(): void
    {
        $dados = $this->emitente(['cnpj' => '']);
        $checklist = emitente_checklist_fiscal($dados, null, false);
        $resumo = emitente_checklist_resumo($checklist);

        $html = $this->render($this->vars($dados, ['checklist' => $checklist, 'resumoChecklist' => $resumo]));

        $this->assertStringContainsString('ui-kpi alerta', $html);
        $this->assertStringContainsString('class="falta"', $html);
        $this->assertStringContainsString('Veja o que falta', $html);
    }

    public function testAtalhoDeConfiguracoesFiscaisSoApareceComOModuloInstalado(): void
    {
        $semFiscal = $this->render($this->vars($this->emitente(), ['configFiscalExiste' => false]));
        $comFiscal = $this->render($this->vars($this->emitente(), ['configFiscalExiste' => true]));

        $this->assertStringNotContainsString('Configurações fiscais', $semFiscal);
        $this->assertStringContainsString('Configurações fiscais', $comFiscal);
    }

    public function testFormularioCarregaMascaraEAlerta(): void
    {
        $html = $this->render($this->vars($this->emitente()));

        $this->assertStringContainsString('jquery.mask.min.js', $html);
        $this->assertStringContainsString('sweetalert2.all.min.js', $html, 'funcoes.js usa Swal.fire no CEP e no CNPJ');
        $this->assertStringContainsString('funcoes.js', $html);
        $this->assertStringContainsString('id="buscar_info_cnpj"', $html);
        $this->assertStringContainsString('id="cep"', $html);
    }
}
