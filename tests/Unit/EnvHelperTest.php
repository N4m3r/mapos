<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
require_once APPPATH . 'helpers/env_helper.php';

/**
 * Gravacao do .env preservando o restante do arquivo (usada pela Central de
 * Notificacoes para gravar SMTP/WhatsApp sem apagar chaves alheias).
 */
class EnvHelperTest extends TestCase
{
    private $arquivo;

    protected function setUp(): void
    {
        $this->arquivo = tempnam(sys_get_temp_dir(), 'mapos_env_');
        file_put_contents($this->arquivo, implode(PHP_EOL, [
            'APP_ENVIRONMENT=production',
            'EMAIL_SMTP_HOST=antigo.smtp.com',
            'EMAIL_SMTP_PASS="senha com espaco"',
            '# comentario preservado',
            'WHATSAPP_EVOLUTION_ENABLED=false',
        ]) . PHP_EOL);
    }

    protected function tearDown(): void
    {
        foreach ([$this->arquivo, $this->arquivo . '.bak'] as $f) {
            if (is_file($f)) {
                unlink($f);
            }
        }
    }

    public function testSubstituiNoLugarEPreservaORestante(): void
    {
        $this->assertTrue(env_salvar([
            'EMAIL_SMTP_HOST' => 'smtp.novo.com',
            'WHATSAPP_EVOLUTION_URL' => 'https://evo.exemplo.com',
        ], $this->arquivo));

        $conteudo = file_get_contents($this->arquivo);

        $this->assertStringContainsString('APP_ENVIRONMENT=production', $conteudo);
        $this->assertStringContainsString('# comentario preservado', $conteudo);
        $this->assertStringContainsString('EMAIL_SMTP_HOST=smtp.novo.com', $conteudo);
        $this->assertStringContainsString('EMAIL_SMTP_PASS="senha com espaco"', $conteudo);
        $this->assertStringContainsString('WHATSAPP_EVOLUTION_URL=https://evo.exemplo.com', $conteudo);
        $this->assertSame('smtp.novo.com', env_valor_linha('EMAIL_SMTP_HOST', null, $this->arquivo));
        $this->assertSame('senha com espaco', env_valor_linha('EMAIL_SMTP_PASS', null, $this->arquivo));
    }

    public function testEscapaValorComCaracteresEspeciais(): void
    {
        env_salvar(['EMAIL_SMTP_PASS' => 'p@ss#1 "aspas"'], $this->arquivo);

        $this->assertSame('p@ss#1 "aspas"', env_valor_linha('EMAIL_SMTP_PASS', null, $this->arquivo));
        $this->assertStringContainsString('EMAIL_SMTP_PASS="p@ss#1 \\"aspas\\""', file_get_contents($this->arquivo));
    }

    public function testValorVazioFicaSemAspas(): void
    {
        env_salvar(['WHATSAPP_EVOLUTION_APIKEY' => ''], $this->arquivo);

        $this->assertStringContainsString('WHATSAPP_EVOLUTION_APIKEY=' . PHP_EOL, file_get_contents($this->arquivo));
        $this->assertSame('', env_valor_linha('WHATSAPP_EVOLUTION_APIKEY', 'x', $this->arquivo));
    }

    public function testNaoCriaArquivoInexistenteSemPermissaoExplicita(): void
    {
        $novo = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mapos_env_inexistente_' . getmypid() . '.env';

        $this->assertFalse(env_salvar(['A' => '1'], $novo));
        $this->assertFileDoesNotExist($novo);

        $this->assertTrue(env_salvar(['A' => '1'], $novo, true));
        $this->assertFileExists($novo);
        $this->assertSame('1', env_valor_linha('A', null, $novo));
        unlink($novo);
    }

    public function testGuardaBackupAntesDeSobrescrever(): void
    {
        env_salvar(['EMAIL_SMTP_HOST' => 'outro.smtp.com'], $this->arquivo);

        $this->assertFileExists($this->arquivo . '.bak');
        $this->assertStringContainsString('EMAIL_SMTP_HOST=antigo.smtp.com', file_get_contents($this->arquivo . '.bak'));
    }

    public function testChaveNovaEhAcrescentada(): void
    {
        env_salvar(['EMAIL_PROTOCOL' => 'smtp'], $this->arquivo);

        $this->assertSame('smtp', env_valor_linha('EMAIL_PROTOCOL', null, $this->arquivo));
    }

    public function testChaveDuplicadaNaoSeRepete(): void
    {
        file_put_contents($this->arquivo, "A=1\nA=2\nB=3\n");
        env_salvar(['A' => '9'], $this->arquivo);

        $conteudo = file_get_contents($this->arquivo);
        $this->assertSame(1, substr_count($conteudo, 'A='));
        $this->assertStringContainsString('A=9', $conteudo);
    }

    public function testIgnoraChaveInvalida(): void
    {
        env_salvar(['CHAVE INVALIDA' => 'x', 'OK' => 'y'], $this->arquivo);

        $this->assertStringNotContainsString('CHAVE INVALIDA', file_get_contents($this->arquivo));
        $this->assertSame('y', env_valor_linha('OK', null, $this->arquivo));
    }
}
