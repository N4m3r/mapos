<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Módulo de RH — permissões e configurações.
 *
 *  1. Injeta as novas flags de RH em TODOS os grupos de permissão existentes
 *     (sem apagar o que já existe). Grupos administradores (cSistema=1)
 *     recebem acesso total ao RH; os demais recebem 0 (negado por padrão).
 *  2. Cria o grupo "Colaborador" (autoatendimento: bate ponto e acessa a
 *     própria área), análogo ao grupo "Tecnico".
 *  3. Semeia as configurações do ponto (geofence/face) em `configuracoes`.
 *
 * Flags novas:
 *  - vRh              ver o módulo de RH (admin)
 *  - eRh              gerenciar colaboradores/unidades/jornadas
 *  - aprovarRh        aprovar ocorrências/ausências/lançamentos
 *  - vRhFinanceiro    ver salários e lançamentos financeiros
 *  - fecharFolha      fechar competência / gerar resumo de folha
 *  - vAreaColaborador acessar a Área do Colaborador (autoatendimento)
 *  - baterPonto       registrar o próprio ponto
 */
class Migration_add_rh_permissoes_e_config extends CI_Migration
{
    /** Flags exclusivas do administrador de RH. */
    private $flagsAdmin = ['vRh', 'eRh', 'aprovarRh', 'vRhFinanceiro', 'fecharFolha'];

    /** Flags do colaborador (autoatendimento). */
    private $flagsColaborador = ['vAreaColaborador', 'baterPonto'];

    public function up()
    {
        $todasFlags = array_merge($this->flagsAdmin, $this->flagsColaborador);

        // 1. Mescla as flags novas em cada grupo existente ------------------
        $grupos = $this->db->get('permissoes')->result();
        foreach ($grupos as $grupo) {
            $permissoes = @unserialize($grupo->permissoes);
            if (! is_array($permissoes)) {
                continue; // grupo com blob corrompido: não mexe
            }

            $ehAdmin = ! empty($permissoes['cSistema']) && $permissoes['cSistema'] == 1;

            foreach ($todasFlags as $flag) {
                if (array_key_exists($flag, $permissoes)) {
                    continue; // já tem a flag: preserva o valor atual
                }
                if (in_array($flag, $this->flagsAdmin, true)) {
                    $permissoes[$flag] = $ehAdmin ? 1 : 0;
                } else {
                    $permissoes[$flag] = 0;
                }
            }

            $this->db->where('idPermissao', $grupo->idPermissao)
                     ->update('permissoes', ['permissoes' => serialize($permissoes)]);
        }

        // 2. Cria o grupo "Colaborador" ------------------------------------
        if ($this->db->where('nome', 'Colaborador')->count_all_results('permissoes') == 0) {
            $permissoesColaborador = [
                // acessos administrativos: todos negados
                'aCliente' => 0, 'eCliente' => 0, 'dCliente' => 0, 'vCliente' => 0,
                'aProduto' => 0, 'eProduto' => 0, 'dProduto' => 0, 'vProduto' => 0,
                'aServico' => 0, 'eServico' => 0, 'dServico' => 0, 'vServico' => 0,
                'aOs' => 0, 'eOs' => 0, 'dOs' => 0, 'vOs' => 0,
                'aVenda' => 0, 'eVenda' => 0, 'dVenda' => 0, 'vVenda' => 0,
                'aGarantia' => 0, 'eGarantia' => 0, 'dGarantia' => 0, 'vGarantia' => 0,
                'aArquivo' => 0, 'eArquivo' => 0, 'dArquivo' => 0, 'vArquivo' => 0,
                'aPagamento' => 0, 'ePagamento' => 0, 'dPagamento' => 0, 'vPagamento' => 0,
                'aLancamento' => 0, 'eLancamento' => 0, 'dLancamento' => 0, 'vLancamento' => 0,
                'aCobranca' => 0, 'eCobranca' => 0, 'dCobranca' => 0, 'vCobranca' => 0,
                'cUsuario' => 0, 'cEmitente' => 0, 'cPermissao' => 0, 'cBackup' => 0,
                'cAuditoria' => 0, 'cEmail' => 0, 'cSistema' => 0,
                'rCliente' => 0, 'rProduto' => 0, 'rServico' => 0, 'rOs' => 0,
                'rVenda' => 0, 'rFinanceiro' => 0,
                // RH: só autoatendimento
                'vRh' => 0, 'eRh' => 0, 'aprovarRh' => 0, 'vRhFinanceiro' => 0, 'fecharFolha' => 0,
                'vAreaColaborador' => 1, 'baterPonto' => 1,
            ];

            $this->db->insert('permissoes', [
                'nome' => 'Colaborador',
                'data' => date('Y-m-d'),
                'permissoes' => serialize($permissoesColaborador),
                'situacao' => 1,
            ]);
            log_message('info', 'Grupo de permissao Colaborador criado com sucesso');
        }

        // 3. Configurações do ponto ----------------------------------------
        // Garante que config/valor comportam as chaves do RH (defensivo: em
        // bases sem a widen_configuracoes, config é VARCHAR(20) e truncaria
        // 'rh_geofence_obrigatorio'/'rh_tolerancia_padrao_min'). Ver [[configuracoes-varchar20]].
        $colConfig = $this->db->field_data('configuracoes');
        foreach ($colConfig as $c) {
            if ($c->name === 'config' && (int) $c->max_length < 60) {
                $this->db->query('ALTER TABLE `configuracoes` MODIFY `config` VARCHAR(60) NOT NULL');
            }
        }

        $configs = [
            'rh_geofence_obrigatorio' => '0',  // 1 = bloqueia batida fora do raio
            'rh_face_obrigatorio' => '0',      // 1 = exige reconhecimento facial
            'rh_face_score_minimo' => '0.55',  // limiar de similaridade aceito
            'rh_tolerancia_padrao_min' => '10',
        ];
        foreach ($configs as $chave => $valor) {
            if ($this->db->where('config', $chave)->count_all_results('configuracoes') == 0) {
                $this->db->insert('configuracoes', ['config' => $chave, 'valor' => $valor]);
            }
        }
    }

    public function down()
    {
        // Remove o grupo Colaborador
        $this->db->where('nome', 'Colaborador')->delete('permissoes');

        // Remove as flags de RH dos demais grupos
        $todasFlags = array_merge($this->flagsAdmin, $this->flagsColaborador);
        $grupos = $this->db->get('permissoes')->result();
        foreach ($grupos as $grupo) {
            $permissoes = @unserialize($grupo->permissoes);
            if (! is_array($permissoes)) {
                continue;
            }
            foreach ($todasFlags as $flag) {
                unset($permissoes[$flag]);
            }
            $this->db->where('idPermissao', $grupo->idPermissao)
                     ->update('permissoes', ['permissoes' => serialize($permissoes)]);
        }

        // Remove as configs
        foreach (['rh_geofence_obrigatorio', 'rh_face_obrigatorio', 'rh_face_score_minimo', 'rh_tolerancia_padrao_min'] as $chave) {
            $this->db->where('config', $chave)->delete('configuracoes');
        }
    }
}
