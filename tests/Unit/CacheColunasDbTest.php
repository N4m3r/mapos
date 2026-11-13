<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'core/Cache_colunas_db.php';

/** Banco falso: conta quantas vezes as colunas foram lidas de verdade. */
class BancoFalsoColunas
{
    public $data_cache = [];
    public $leituras = 0;
    public $consultas = [];
    public $colunas = ['os' => ['idOs', 'status']];

    public function list_fields($table)
    {
        $this->leituras++;

        return $this->colunas[$table] ?? false;
    }

    public function field_exists($campo, $tabela)
    {
        return in_array($campo, (array) $this->list_fields($tabela), true);
    }

    public function query($sql, $binds = false, $return_object = null)
    {
        $this->consultas[] = $sql;

        return true;
    }
}

class BancoComCacheColunas extends BancoFalsoColunas
{
    use Cache_colunas_db;
}

class CacheColunasDbTest extends TestCase
{
    public function testLeColunasUmaVezPorTabela(): void
    {
        $db = new BancoComCacheColunas();
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($db->field_exists('status', 'os'));
            $this->assertFalse($db->field_exists('chamado_id', 'os'));
        }
        $this->assertSame(1, $db->leituras);
    }

    public function testAlterTableLimpaOCache(): void
    {
        $db = new BancoComCacheColunas();
        $this->assertFalse($db->field_exists('chamado_id', 'os'));
        $db->data_cache['table_names'] = ['os'];

        $db->colunas['os'][] = 'chamado_id';
        $db->query('ALTER TABLE `os` ADD COLUMN `chamado_id` VARCHAR(60) NULL');

        $this->assertTrue($db->field_exists('chamado_id', 'os'));
        $this->assertSame(2, $db->leituras);
        $this->assertArrayNotHasKey('table_names', $db->data_cache);
    }

    public function testConsultaComumNaoLimpa(): void
    {
        $db = new BancoComCacheColunas();
        $db->field_exists('status', 'os');
        $db->query("SELECT * FROM os WHERE status = 'ALTER'");
        $db->field_exists('status', 'os');
        $this->assertSame(1, $db->leituras);
    }

    public function testTabelaInexistenteNaoFicaEmCache(): void
    {
        $db = new BancoComCacheColunas();
        $this->assertFalse($db->field_exists('x', 'nao_existe'));
        $db->colunas['nao_existe'] = ['x'];
        $this->assertTrue($db->field_exists('x', 'nao_existe'));
    }

    public function testComandosQueMudamOEsquema(): void
    {
        foreach (['ALTER TABLE os', "  create table x (id int)", 'DROP TABLE x', 'RENAME TABLE a TO b'] as $sql) {
            $this->assertTrue(BancoComCacheColunas::mudaEsquema($sql), $sql);
        }
        foreach (['SELECT 1', 'UPDATE os SET status = 1', 'INSERT INTO os VALUES (1)', 'TRUNCATE os', null] as $sql) {
            $this->assertFalse(BancoComCacheColunas::mudaEsquema($sql), (string) $sql);
        }
    }

    /**
     * O CI_DB do sistema precisa ficar entre o query builder e o driver real,
     * senão o cache não vale para o mysqli.
     */
    #[PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function testDriverMysqliHerdaOCache(): void
    {
        require_once BASEPATH . 'database/DB_driver.php';
        require_once BASEPATH . 'database/DB_query_builder.php';
        require_once APPPATH . 'core/CI_DB_com_cache.php';
        require_once BASEPATH . 'database/drivers/mysqli/mysqli_driver.php';

        $this->assertTrue(is_subclass_of('CI_DB_mysqli_driver', 'CI_DB'));
        $this->assertSame('CI_DB', (new ReflectionMethod('CI_DB_mysqli_driver', 'list_fields'))->getDeclaringClass()->getName());
        $this->assertSame('CI_DB', (new ReflectionMethod('CI_DB_mysqli_driver', 'query'))->getDeclaringClass()->getName());
    }
}
