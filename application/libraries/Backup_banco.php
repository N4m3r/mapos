<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Backup do banco de dados (ADR-005, F2).
 *
 * Usado pelo backup manual (Mapos::backup) e pelo automatico
 * (php index.php backup/executar, agendado no cron).
 *
 * .env:
 *   BACKUP_DIR            pasta dos backups automaticos (padrao: application/backups)
 *   BACKUP_RETENCAO_DIAS  apaga os automaticos mais antigos que isso (padrao: 7; o mais recente fica sempre)
 */
class Backup_banco
{
    private const PREFIXO = 'backup_';

    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->dbutil();
    }

    /**
     * Gera o dump SQL compactado (conteudo do .zip).
     */
    public function gerarZip()
    {
        $sql = $this->ci->dbutil->backup([
            'tables' => $this->tabelasLegiveis(),
            'format' => 'txt',
            'foreign_key_checks' => false,
        ]);

        // Sem o SET NAMES, restaurar pelo cliente mysql (que costuma usar latin1) corrompe os acentos
        $charset = $this->ci->db->char_set ?: 'utf8mb4';
        $this->ci->load->library('zip');
        $this->ci->zip->clear_data();
        $this->ci->zip->add_data('backup' . date('d-m-Y') . '.sql', "SET NAMES {$charset};

" . $sql);

        return $this->ci->zip->get_zip();
    }

    /**
     * Gera um backup e grava na pasta dos automaticos; depois aplica a retencao.
     *
     * @return array{arquivo: string, tamanho: int, removidos: string[]}
     */
    public function salvarAutomatico()
    {
        $pasta = $this->pasta();
        $this->protegerPasta($pasta);

        $zip = $this->gerarZip();
        if (! is_string($zip) || $zip === '') {
            throw new RuntimeException('O backup gerado veio vazio.');
        }

        $arquivo = $pasta . self::PREFIXO . date('Y-m-d_His') . '.zip';
        if (file_put_contents($arquivo, $zip) === false) {
            throw new RuntimeException('Nao foi possivel gravar ' . $arquivo . '. Verifique a permissao de escrita da pasta.');
        }

        return [
            'arquivo' => $arquivo,
            'tamanho' => strlen($zip),
            'removidos' => $this->aplicarRetencao(),
        ];
    }

    /**
     * Backups automaticos existentes, do mais recente para o mais antigo.
     *
     * @return array<int, array{nome: string, tamanho: int, data: int}>
     */
    public function listar()
    {
        $lista = [];
        foreach (glob($this->pasta() . self::PREFIXO . '*.zip') ?: [] as $arquivo) {
            $lista[] = ['nome' => basename($arquivo), 'tamanho' => filesize($arquivo), 'data' => filemtime($arquivo)];
        }
        usort($lista, static fn ($a, $b) => $b['data'] <=> $a['data']);

        return $lista;
    }

    /**
     * Caminho completo de um backup automatico pelo nome, ou null se o nome nao for valido/nao existir.
     */
    public function caminho($nome)
    {
        $nome = (string) $nome;
        if (! preg_match('/^' . self::PREFIXO . '\d{4}-\d{2}-\d{2}_\d{6}\.zip$/', $nome)) {
            return null;
        }
        $arquivo = $this->pasta() . $nome;

        return is_file($arquivo) ? $arquivo : null;
    }

    /**
     * Dias de retencao dos automaticos (BACKUP_RETENCAO_DIAS; invalido ou menor que 1 vira 7).
     */
    public function retencaoDias()
    {
        $dias = (int) ($_ENV['BACKUP_RETENCAO_DIAS'] ?? 7);

        return $dias >= 1 ? $dias : 7;
    }

    public function pasta()
    {
        $pasta = trim((string) ($_ENV['BACKUP_DIR'] ?? ''));
        if ($pasta === '') {
            $pasta = APPPATH . 'backups';
        }

        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $pasta), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Apaga os backups automaticos mais antigos que a retencao, mantendo sempre o mais recente.
     *
     * @return string[] nomes removidos
     */
    private function aplicarRetencao()
    {
        $limite = time() - $this->retencaoDias() * 86400;

        $removidos = [];
        foreach (array_slice($this->listar(), 1) as $backup) {
            if ($backup['data'] < $limite) {
                unlink($this->pasta() . $backup['nome']);
                $removidos[] = $backup['nome'];
            }
        }

        return $removidos;
    }

    /**
     * Tabelas que podem ser lidas. Sem esse filtro, uma tabela ilegivel (view
     * quebrada) faz o utilitario de backup do CI chamar num_rows() em false.
     */
    private function tabelasLegiveis()
    {
        $db = $this->ci->db;
        $dbDebugOriginal = $db->db_debug;
        $db->db_debug = false;
        $tabelas = [];
        foreach ($db->list_tables() as $tabela) {
            if ($db->query('SELECT 1 FROM ' . $db->protect_identifiers($tabela) . ' LIMIT 1') !== false) {
                $tabelas[] = $tabela;
            } else {
                log_message('error', 'Backup: ignorando tabela ilegível: ' . $tabela);
            }
        }
        $db->db_debug = $dbDebugOriginal;

        return $tabelas;
    }

    /**
     * Cria a pasta com .htaccess e index.html para nunca ser servida pelo servidor web.
     */
    private function protegerPasta($pasta)
    {
        if (! is_dir($pasta) && ! mkdir($pasta, 0750, true) && ! is_dir($pasta)) {
            throw new RuntimeException('Nao foi possivel criar a pasta de backups ' . $pasta);
        }
        if (! is_file($pasta . '.htaccess')) {
            file_put_contents($pasta . '.htaccess', "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n");
        }
        if (! is_file($pasta . 'index.html')) {
            file_put_contents($pasta . 'index.html', '<!DOCTYPE html><title>403 Forbidden</title><p>Acesso negado.</p>');
        }
    }
}
