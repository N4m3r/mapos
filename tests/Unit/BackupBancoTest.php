<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Backup_banco.php';

/**
 * Backup automatico: nome de arquivo aceito no download e retencao.
 * O dump em si (dbutil) e testado no smoke/manual.
 */
class BackupBancoTest extends TestCase
{
    private string $pasta;

    private Backup_banco $backup;

    protected function setUp(): void
    {
        $GLOBALS['CI'] = new class {
            public $load;

            public function __construct()
            {
                $this->load = new class {
                    public function dbutil()
                    {
                    }
                };
            }
        };

        $this->pasta = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'backup_teste_' . uniqid() . DIRECTORY_SEPARATOR;
        mkdir($this->pasta);
        $_ENV['BACKUP_DIR'] = $this->pasta;
        $_ENV['BACKUP_RETENCAO_DIAS'] = '7';
        $this->backup = new Backup_banco();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->pasta . '*') ?: [] as $arquivo) {
            unlink($arquivo);
        }
        rmdir($this->pasta);
        unset($_ENV['BACKUP_DIR'], $_ENV['BACKUP_RETENCAO_DIAS']);
    }

    public function testCaminhoSoAceitaNomeDeBackupExistente(): void
    {
        touch($this->pasta . 'backup_2026-10-24_030000.zip');

        $this->assertSame($this->pasta . 'backup_2026-10-24_030000.zip', $this->backup->caminho('backup_2026-10-24_030000.zip'));
        $this->assertNull($this->backup->caminho('backup_2026-10-25_030000.zip'), 'nao existe');
        $this->assertNull($this->backup->caminho('../../.env'));
        $this->assertNull($this->backup->caminho('backup_2026-10-24_030000.zip/../x'));
        $this->assertNull($this->backup->caminho('outro.zip'));
    }

    public function testRetencaoApagaAntigosMasMantemOMaisRecente(): void
    {
        $this->criar('backup_2026-10-01_030000.zip', 23);
        $this->criar('backup_2026-10-20_030000.zip', 4);

        $metodo = new ReflectionMethod(Backup_banco::class, 'aplicarRetencao');
        $removidos = $metodo->invoke($this->backup);

        $this->assertSame(['backup_2026-10-01_030000.zip'], $removidos);
        $this->assertFileExists($this->pasta . 'backup_2026-10-20_030000.zip');
    }

    public function testRetencaoNuncaApagaOUnicoBackupMesmoVelho(): void
    {
        $this->criar('backup_2026-01-01_030000.zip', 300);

        $metodo = new ReflectionMethod(Backup_banco::class, 'aplicarRetencao');

        $this->assertSame([], $metodo->invoke($this->backup));
        $this->assertCount(1, $this->backup->listar());
    }

    public function testRetencaoInvalidaUsaSeteDias(): void
    {
        $_ENV['BACKUP_RETENCAO_DIAS'] = '0';
        $this->assertSame(7, $this->backup->retencaoDias());
        $_ENV['BACKUP_RETENCAO_DIAS'] = '30';
        $this->assertSame(30, $this->backup->retencaoDias());
    }

    private function criar(string $nome, int $diasAtras): void
    {
        file_put_contents($this->pasta . $nome, 'zip');
        touch($this->pasta . $nome, time() - $diasAtras * 86400);
    }
}
