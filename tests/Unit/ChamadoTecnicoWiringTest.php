<?php

use PHPUnit\Framework\TestCase;

/**
 * Ligacao do numero do chamado nos fluxos de atividade nao programada.
 *
 * O campo nasce na tela do tecnico (web e app), passa pelo controller (web e
 * API) e volta nos payloads que alimentam o app. Nenhum teste de banco cobre
 * isso, e um rename de campo quebraria a ligacao em silencio: a OS salvaria
 * sem chamado e o app mostraria vazio, sem erro.
 *
 * Varredura da fonte, como MenuViewTest faz com a barra lateral.
 */
class ChamadoTecnicoWiringTest extends TestCase
{
    private static function fonte($caminho): string
    {
        $completo = (strpos($caminho, 'mobile/') === 0 ? FCPATH : APPPATH) . $caminho;
        $conteudo = @file_get_contents($completo);
        self::assertNotFalse($conteudo, 'arquivo nao encontrado: ' . $caminho);

        return (string) $conteudo;
    }

    public function testFormularioWebEnviaOCampo(): void
    {
        $html = self::fonte('views/tecnico/nova_atividade.php');

        $this->assertStringContainsString('name="chamado_id"', $html, 'o formulario nao envia o chamado');
        $this->assertStringContainsString('maxlength="60"', $html, 'o limite precisa caber na coluna (60)');
    }

    public function testControllerWebGravaComGuardDeColuna(): void
    {
        $php = self::fonte('controllers/Tecnico.php');

        $this->assertStringContainsString("field_exists('chamado_id', 'os')", $php, 'falta a guarda para ambiente sem migration');
        $this->assertStringContainsString("helper('chamado_cobranca')", $php, 'o helper precisa estar carregado antes de normalizar');
        $this->assertStringContainsString("chamado_normalizar(\$this->input->post('chamado_id'))", $php);
    }

    public function testApiGravaComGuardaDeColuna(): void
    {
        $php = self::fonte('controllers/api/v1/TecnicoOcorrenciaController.php');

        $this->assertStringContainsString("'chamado_id' =>", $php);
        $this->assertStringContainsString("field_exists('chamado_id', 'os')", $php, 'falta a guarda para ambiente sem migration');
    }

    public function testModelDescartaAColunaQuandoNaoExiste(): void
    {
        $php = self::fonte('models/Tecnico_model.php');

        $this->assertStringContainsString("unset(\$data['chamado_id'])", $php, 'o INSERT quebraria em ambiente sem a migration');
    }

    public function testPayloadsDaApiExpoemOChamado(): void
    {
        foreach (['controllers/api/v1/AreaApiController.php', 'controllers/api/v1/TecnicoController.php'] as $arquivo) {
            $this->assertStringContainsString(
                "'chamado_id' => \$os->chamado_id ?? null,",
                self::fonte($arquivo),
                'payload sem o chamado: ' . $arquivo
            );
        }
    }

    public function testTelaWebDoTecnicoMostraOChamado(): void
    {
        $html = self::fonte('views/tecnico/visualizar_os.php');

        $this->assertStringContainsString('info-label">Chamado</span>', $html);
        $this->assertStringContainsString('html_escape($os->chamado_id)', $html, 'o valor precisa passar pelo escape');
    }

    public function testAppEnviaMostraEDeclaraOCampo(): void
    {
        $tela = self::fonte('mobile/src/screens/tecnico/TecnicoAtividadeScreen.tsx');
        $this->assertStringContainsString('const [chamado, setChamado]', $tela);
        $this->assertStringContainsString('value={chamado}', $tela, 'o input precisa estar ligado ao estado');
        $this->assertStringContainsString('chamado_id: chamado.trim() || undefined,', $tela, 'o payload nao envia o chamado');
        $this->assertStringContainsString('maxLength={60}', $tela);

        $detalhe = self::fonte('mobile/src/screens/tecnico/TecnicoAtendimentoScreen.tsx');
        $this->assertStringContainsString('<Kv k="Chamado" v={os.chamado_id} />', $detalhe);

        $payload = self::fonte('mobile/src/lib/api-tecnico.ts');
        $this->assertStringContainsString('chamado_id?: string;', $payload, 'o payload tipado precisa do campo');

        $tipos = self::fonte('mobile/src/lib/types-tecnico.ts');
        $this->assertSame(
            2,
            substr_count($tipos, 'chamado_id?: string | null;'),
            'os dois blocos de OS (resumo e detalhe) precisam declarar o campo'
        );
    }
}
