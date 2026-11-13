<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cache, por requisição, da lista de colunas de cada tabela.
 *
 * No CodeIgniter 3, field_exists() chama list_fields(), que roda um
 * SHOW COLUMNS a cada chamada, sem guardar nada. O sistema usa field_exists()
 * em centenas de pontos para conviver com bancos em versões diferentes, então
 * uma tela de obra/projeto fazia dezenas de consultas repetidas só para isso.
 *
 * Aqui a lista de colunas é guardada na primeira consulta e reaproveitada até
 * o fim da requisição. Qualquer comando que mude o esquema (ALTER, CREATE,
 * DROP, RENAME), inclusive os do dbforge e das migrations, limpa o cache, e a
 * próxima consulta volta a ler do banco.
 */
trait Cache_colunas_db
{
    /** @var array<string, string[]> tabela => colunas */
    protected $colunasCache = [];

    public function list_fields($table)
    {
        $chave = (string) $table;
        if (isset($this->colunasCache[$chave])) {
            return $this->colunasCache[$chave];
        }

        $campos = parent::list_fields($table);
        if (is_array($campos)) {
            $this->colunasCache[$chave] = $campos;
        }

        return $campos;
    }

    public function query($sql, $binds = false, $return_object = null)
    {
        if (self::mudaEsquema($sql)) {
            $this->limparCacheColunas();
        }

        return parent::query($sql, $binds, $return_object);
    }

    /** Esquece colunas e tabelas lidas nesta requisição. */
    public function limparCacheColunas()
    {
        $this->colunasCache = [];
        unset($this->data_cache['table_names']);
    }

    /** O comando SQL altera a estrutura do banco? */
    public static function mudaEsquema($sql)
    {
        return is_string($sql) && preg_match('/^\s*(ALTER|CREATE|DROP|RENAME)\b/i', $sql) === 1;
    }
}
