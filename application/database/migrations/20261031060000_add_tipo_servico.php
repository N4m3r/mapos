<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tipo de serviço (segurança eletrônica): os.tipo_servico e
 * formularios_atendimento.tipo_servico (NULL = vale para todos os tipos),
 * com formulários-modelo da etapa "durante" (passo "Checklist do serviço" do assistente do técnico) para cada tipo.
 *
 * Idempotente: colunas e formulários só são criados se ainda não existirem.
 */
class Migration_add_tipo_servico extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('os') && ! $this->db->field_exists('tipo_servico', 'os')) {
            $this->db->query('ALTER TABLE `os` ADD COLUMN `tipo_servico` VARCHAR(30) NULL DEFAULT NULL');
            $this->db->query('ALTER TABLE `os` ADD INDEX `idx_os_tipo_servico` (`tipo_servico`)');
        }
        if (! $this->db->table_exists('formularios_atendimento')) {
            return;
        }
        if (! $this->db->field_exists('tipo_servico', 'formularios_atendimento')) {
            $this->db->query('ALTER TABLE `formularios_atendimento` ADD COLUMN `tipo_servico` VARCHAR(30) NULL DEFAULT NULL');
        }
        if (! $this->db->table_exists('formularios_atendimento_campos')) {
            return;
        }

        $sn = 'sim_nao';
        $modelos = [
            'cftv' => ['Checklist - CFTV', [
                ['Todas as câmeras com imagem', $sn], ['Gravação no NVR/DVR funcionando', $sn],
                ['Dias de gravação', 'number'], ['Acesso pelo app configurado para o cliente', $sn],
                ['Data/hora do gravador correta', $sn], ['Cliente orientado', $sn],
            ]],
            'alarme' => ['Checklist - Alarme', [
                ['Sensores testados um a um', $sn], ['Sirene testada', $sn],
                ['Comunicação com a central/monitoramento ok', $sn], ['Bateria testada', $sn],
                ['Senhas de usuário cadastradas pelo cliente (nunca anotar senhas)', $sn], ['Cliente orientado', $sn],
            ]],
            'controle_acesso' => ['Checklist - Controle de acesso', [
                ['Fechadura/eletroímã ok', $sn], ['Botoeira de saída ok', $sn],
                ['Usuários/biometrias cadastrados (quantidade)', 'number'], ['Fonte/nobreak ok', $sn],
                ['Teste de abertura feito', $sn],
            ]],
            'interfonia' => ['Checklist - Interfonia', [
                ['Áudio ok', $sn], ['Vídeo ok', $sn], ['Abertura do portão/fechadura pelo interfone', $sn],
                ['Ramais/apartamentos testados', $sn],
            ]],
            'cerca_eletrica' => ['Checklist - Cerca elétrica', [
                ['Tensão medida (kV)', 'number'], ['Aterramento ok', $sn],
                ['Placas de advertência instaladas (obrigatório por lei)', $sn],
                ['Alarme da central de choque testado', $sn],
            ]],
            'portao' => ['Checklist - Portão automático', [
                ['Abre e fecha completo', $sn], ['Fim de curso ajustado', $sn],
                ['Sensor antiesmagamento/fotocélula ok', $sn], ['Controles cadastrados (quantidade)', 'number'],
            ]],
            'rede' => ['Checklist - Rede e Wi-Fi', [
                ['Internet ok', $sn], ['Wi-Fi com senha do cliente', $sn],
                ['Equipamentos com IP fixo anotados', $sn], ['Teste de velocidade (Mbps)', 'number'],
            ]],
            'rastreamento' => ['Checklist - Rastreamento', [
                ['Equipamento instalado e ligado', $sn], ['Posição aparecendo na plataforma', $sn],
                ['Bloqueio testado (se houver)', $sn],
            ]],
        ];

        $ordem = 100;
        foreach ($modelos as $tipo => [$nome, $campos]) {
            $existe = $this->db->where('nome', $nome)->where('tipo_servico', $tipo)->count_all_results('formularios_atendimento');
            if ($existe > 0) {
                continue;
            }
            $this->db->insert('formularios_atendimento', [
                'nome' => $nome,
                'descricao' => 'Modelo padrão - ajuste conforme sua rotina.',
                'etapa' => 'durante',
                'obrigatorio' => 0,
                'ativo' => 1,
                'ordem' => $ordem++,
                'tipo_servico' => $tipo,
                'data_cadastro' => date('Y-m-d H:i:s'),
            ]);
            $fid = $this->db->insert_id();
            foreach ($campos as $i => [$label, $tipoCampo]) {
                $this->db->insert('formularios_atendimento_campos', [
                    'formulario_id' => $fid,
                    'label' => $label,
                    'tipo' => $tipoCampo,
                    'obrigatorio' => 0,
                    'ordem' => $i,
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->table_exists('formularios_atendimento') && $this->db->field_exists('tipo_servico', 'formularios_atendimento')) {
            $this->dbforge->drop_column('formularios_atendimento', 'tipo_servico');
        }
        if ($this->db->table_exists('os') && $this->db->field_exists('tipo_servico', 'os')) {
            $this->dbforge->drop_column('os', 'tipo_servico');
        }
    }
}
