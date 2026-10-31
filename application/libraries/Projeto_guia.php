<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Guia do projeto (ADR-016): calcula o "proximo passo" e o texto do prazo.
 * Funcoes puras (sem banco) para serem testadas isoladamente.
 */
class Projeto_guia
{
    public const FASE_MONTAR = 1;
    public const FASE_PLANEJAR = 2;
    public const FASE_EXECUTAR = 3;
    public const FASE_ENTREGAR = 4;

    private const OS_ENCERRADAS = ['Finalizado', 'Faturado', 'Cancelado'];

    /** Tipos de projeto que trabalham com pontos (cameras, leitores, tomadas...). */
    public static function tipoExigePontos($tipo)
    {
        $t = strtolower(strtr((string) $tipo, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i',
            'ó' => 'o', 'õ' => 'o', 'ô' => 'o', 'ú' => 'u', 'ç' => 'c',
        ]));
        foreach (['cftv', 'camera', 'acesso', 'rede', 'fibra', 'alarme', 'seguranca', 'cerca', 'interfone'] as $p) {
            if ($t !== '' && strpos($t, $p) !== false) {
                return true;
            }
        }
        return false;
    }

    /** Conta OS ainda em aberto a partir da lista de status. */
    public static function contarOsAbertas(array $statusOs)
    {
        $n = 0;
        foreach ($statusOs as $s) {
            if (! in_array((string) $s, self::OS_ENCERRADAS, true)) {
                $n++;
            }
        }
        return $n;
    }

    /**
     * @param array $estado status, etapas, etapas_sem_os, plan_ativo, exige_pontos, pontos,
     *                      pontos_testados, tem_equipe, os_abertas, pode_executar, pode_editar;
     *                      medicao (ADR-015 fase 5): os_aguardando_medicao e saldo_a_medir (null = sem
     *                      dado, mantem o texto antigo), medicoes_a_faturar, pode_medir
     *
     * @return array ['fase'=>1..4 (0 = cancelado), 'texto', 'botao_rotulo', 'alvo']
     */
    public static function proximoPasso(array $estado)
    {
        $e = $estado + [
            'status' => 'planejamento', 'etapas' => 0, 'etapas_sem_os' => 0, 'plan_ativo' => false,
            'exige_pontos' => false, 'pontos' => 0, 'pontos_testados' => 0, 'tem_equipe' => false,
            'os_abertas' => 0, 'pode_executar' => false, 'pode_editar' => true,
            'os_aguardando_medicao' => null, 'saldo_a_medir' => null, 'medicoes_a_faturar' => 0,
            'pode_medir' => false,
        ];
        $passo = function ($fase, $texto, $rotulo = '', $alvo = '') {
            return ['fase' => $fase, 'texto' => $texto, 'botao_rotulo' => $rotulo, 'alvo' => $alvo];
        };

        if (in_array($e['status'], ['concluida', 'entregue'], true)) {
            return $passo(self::FASE_ENTREGAR, 'Projeto concluído. Nada pendente.');
        }
        if ($e['status'] === 'cancelada') {
            return $passo(0, 'Projeto cancelado.');
        }

        if ((int) $e['etapas'] === 0) {
            return $passo(self::FASE_MONTAR, 'Crie as etapas do projeto para organizar o trabalho.', 'Criar etapas padrão', '#aba-planejamento');
        }
        if ($e['exige_pontos'] && (int) $e['pontos'] === 0) {
            return $passo(self::FASE_MONTAR, 'Adicione os pontos (câmeras, leitores, tomadas) que serão instalados.', 'Adicionar pontos', $e['pode_editar'] ? '#modal-lote' : '#aba-pontos');
        }
        if (! $e['tem_equipe']) {
            return $passo(self::FASE_MONTAR, 'Vincule a equipe que vai executar o projeto.', 'Vincular equipe', '#aba-resumo');
        }
        if ($e['plan_ativo'] && (int) $e['etapas_sem_os'] > 0) {
            return $passo(
                self::FASE_PLANEJAR,
                (int) $e['etapas_sem_os'] === 1 ? 'Uma etapa ainda não tem OS. Gere a OS para o técnico executar.' : (int) $e['etapas_sem_os'] . ' etapas ainda não têm OS. Gere as OS para os técnicos executarem.',
                'Gerar OS das etapas',
                $e['pode_editar'] ? '#modal-os-lote' : '#aba-planejamento'
            );
        }
        $pontosPendentes = max(0, (int) $e['pontos'] - (int) $e['pontos_testados']);
        // Medicao aprovada e ainda sem cobranca no financeiro (ADR-015 fase 5): dinheiro parado.
        $aFaturar = (int) $e['medicoes_a_faturar'];
        if ($aFaturar > 0) {
            return $passo(
                ((int) $e['os_abertas'] > 0 || $pontosPendentes > 0) ? self::FASE_EXECUTAR : self::FASE_ENTREGAR,
                $aFaturar === 1 ? 'Uma medição foi aprovada e ainda não foi faturada. Fature para gerar a cobrança.' : $aFaturar . ' medições foram aprovadas e ainda não foram faturadas. Fature para gerar as cobranças.',
                'Faturar a medição',
                '#aba-financeiro'
            );
        }
        if ((int) $e['os_abertas'] > 0) {
            return $passo(
                self::FASE_EXECUTAR,
                'Há ' . (int) $e['os_abertas'] . ' OS em aberto. Registre o que foi feito e atualize os pontos.',
                'Registrar execução',
                $e['pode_executar'] ? '#modal-registro' : '#aba-execucao'
            );
        }
        if ($pontosPendentes > 0) {
            return $passo(
                self::FASE_EXECUTAR,
                $pontosPendentes === 1 ? 'Falta testar 1 ponto. Atualize o status conforme o trabalho avança.' : 'Faltam testar ' . $pontosPendentes . ' pontos. Atualize o status conforme o trabalho avança.',
                'Atualizar pontos',
                '#aba-pontos'
            );
        }
        if ($e['os_aguardando_medicao'] === null && $e['saldo_a_medir'] === null) {
            return $passo(
                self::FASE_ENTREGAR,
                'Tudo testado. Gere a documentação final (as-built) no menu Mais e faça a medição.',
                'Abrir medição',
                '#aba-financeiro'
            );
        }
        $aguardando = (int) $e['os_aguardando_medicao'];
        if ($aguardando > 0 || (float) $e['saldo_a_medir'] >= 0.01) {
            return $passo(
                self::FASE_ENTREGAR,
                'Tudo executado e testado. Faça a medição para cobrar o que falta'
                    . ($aguardando > 0 ? ($aguardando === 1 ? ' (1 OS aguardando medição).' : ' (' . $aguardando . ' OS aguardando medição).') : '.'),
                'Fazer a medição',
                $e['pode_medir'] ? '#modal-medicao' : '#aba-financeiro'
            );
        }
        return $passo(
            self::FASE_ENTREGAR,
            'Tudo testado e medido. Gere a documentação final (as-built) no menu Mais e conclua o projeto.',
            'Ver financeiro',
            '#aba-financeiro'
        );
    }

    /**
     * Texto do prazo. $fimPrevisto: Y-m-d ou vazio; $hoje: Y-m-d.
     *
     * @return array ['texto', 'classe' (sucesso|alerta|perigo|neutro), 'dias' (int|null)]
     */
    public static function prazo($fimPrevisto, $hoje, $encerrado = false)
    {
        if ($encerrado) {
            return ['texto' => 'Encerrado', 'classe' => 'neutro', 'dias' => null];
        }
        $fimPrevisto = (string) $fimPrevisto;
        if ($fimPrevisto === '' || $fimPrevisto === '0000-00-00') {
            return ['texto' => 'Sem prazo definido', 'classe' => 'neutro', 'dias' => null];
        }
        $dias = (int) round((strtotime($fimPrevisto) - strtotime($hoje)) / 86400);
        if ($dias < 0) {
            $n = -$dias;
            return ['texto' => 'Atrasado ' . $n . ($n === 1 ? ' dia' : ' dias'), 'classe' => 'perigo', 'dias' => $dias];
        }
        if ($dias === 0) {
            return ['texto' => 'Prazo termina hoje', 'classe' => 'alerta', 'dias' => 0];
        }
        return [
            'texto' => 'Faltam ' . $dias . ($dias === 1 ? ' dia' : ' dias'),
            'classe' => $dias <= 7 ? 'alerta' : 'sucesso',
            'dias' => $dias,
        ];
    }
}
