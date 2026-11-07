<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Emitente: dados fiscais e de contato que faltavam (nome fantasia, inscricao
 * municipal, complemento) e registro do ultimo acesso do usuario.
 *
 * O emitente alimenta o cabecalho de OS, orcamentos, vendas, recibos e notas.
 * So tinha razao social, CNPJ, IE e endereco; a NF-e exige o nome fantasia
 * quando existir (xFant) e a NFS-e exige a inscricao municipal (IM) do
 * prestador. A IM vivia so em configuracoes_nfe, longe do resto da empresa.
 *
 * usuarios.ultimo_acesso sustenta a coluna "Ultimo acesso" da lista de contas
 * e a conferencia de quem realmente usa o sistema.
 *
 * Idempotente: cada coluna so e criada se ainda nao existir.
 */
class Migration_add_emitente_fiscal_e_acesso extends CI_Migration
{
    /** @var array<string, string> coluna => definicao SQL */
    private $colunasEmitente = [
        'nome_fantasia' => '`nome_fantasia` VARCHAR(255) NULL DEFAULT NULL',
        'inscricao_municipal' => '`inscricao_municipal` VARCHAR(20) NULL DEFAULT NULL',
        'complemento' => '`complemento` VARCHAR(45) NULL DEFAULT NULL',
    ];

    /** @var array<string, string> */
    private $colunasUsuario = [
        'ultimo_acesso' => '`ultimo_acesso` DATETIME NULL DEFAULT NULL',
    ];

    public function up()
    {
        if ($this->db->table_exists('emitente')) {
            foreach ($this->colunasEmitente as $coluna => $definicao) {
                if (! $this->db->field_exists($coluna, 'emitente')) {
                    $this->db->query('ALTER TABLE `emitente` ADD COLUMN ' . $definicao);
                }
            }
        }

        if ($this->db->table_exists('usuarios')) {
            foreach ($this->colunasUsuario as $coluna => $definicao) {
                if (! $this->db->field_exists($coluna, 'usuarios')) {
                    $this->db->query('ALTER TABLE `usuarios` ADD COLUMN ' . $definicao);
                }
            }
        }

        $this->completarPerfisExistentes();

        log_message('info', 'Migration add_emitente_fiscal_e_acesso executada');
    }

    /**
     * Perfis salvos antes do catalogo unico nao tem as chaves novas
     * (cNfe, cAutomacao, cContrato...). Como checkPermission devolve false
     * para chave ausente, esses perfis perderiam acesso a telas que ja usavam.
     *
     * Aqui so se acrescenta: chave ausente recebe 1 quando o perfil ja tinha
     * a permissao "mae" equivalente (quem configura o sistema continua
     * configurando, quem emite nota continua emitindo). Nada e removido.
     */
    private function completarPerfisExistentes()
    {
        if (! $this->db->table_exists('permissoes')) {
            return;
        }

        // chave nova => permissao existente que autoriza herdar
        $heranca = [
            'cNfe' => 'cSistema',
            'eNfe' => 'eOs',
            'vNfe' => 'vOs',
            'dNfe' => 'dOs',
            'cAutomacao' => 'cSistema',
            'cFormularioAtendimento' => 'cSistema',
            'vEntrada' => 'vNfe',
            'cEntrada' => 'eNfe',
            'dEntrada' => 'dNfe',
            'vRh' => 'cUsuario',
            'eRh' => 'cUsuario',
            'vAreaColaborador' => 'vTecnicoDashboard',
            'baterPonto' => 'vTecnicoDashboard',
            'aprovarRh' => 'eRh',
            'vRhFinanceiro' => 'rFinanceiro',
            'fecharFolha' => 'vRhFinanceiro',
            'vRelatorioAtendimentos' => 'rOs',
            'cContrato' => 'aOs',
            'eContrato' => 'eOs',
            'dContrato' => 'dOs',
            'vContrato' => 'vOs',
            'fContrato' => 'vLancamento',
        ];

        foreach ($this->db->get('permissoes')->result() as $grupo) {
            set_error_handler(static function () {
                return true;
            });
            $perms = unserialize((string) $grupo->permissoes, ['allowed_classes' => false]);
            restore_error_handler();

            if (! is_array($perms)) {
                continue;
            }

            $mudou = false;
            foreach ($heranca as $chave => $mae) {
                if (array_key_exists($chave, $perms)) {
                    continue;
                }
                $perms[$chave] = ! empty($perms[$mae]) ? 1 : 0;
                $mudou = true;
            }

            if ($mudou) {
                $this->db->where('idPermissao', $grupo->idPermissao)
                    ->update('permissoes', ['permissoes' => serialize($perms)]);
            }
        }
    }

    public function down()
    {
        foreach (array_keys($this->colunasEmitente) as $coluna) {
            if ($this->db->table_exists('emitente') && $this->db->field_exists($coluna, 'emitente')) {
                $this->dbforge->drop_column('emitente', $coluna);
            }
        }
        foreach (array_keys($this->colunasUsuario) as $coluna) {
            if ($this->db->table_exists('usuarios') && $this->db->field_exists($coluna, 'usuarios')) {
                $this->dbforge->drop_column('usuarios', $coluna);
            }
        }
    }
}
