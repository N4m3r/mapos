<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Perfis tributarios e valores da Reforma Tributaria (funcoes puras, sem CI).
 */

if (!function_exists('tributacao_perfis')) {
    function tributacao_perfis(): array
    {
        return [
            'mei' => [
                'rotulo' => 'MEI',
                'descricao' => 'Microempreendedor Individual. Paga um valor fixo mensal (DAS-MEI).',
                'preset' => ['op_simp_nac' => 2, 'csosn_padrao' => '102', 'cfop_padrao' => '5102'],
            ],
            'simples' => [
                'rotulo' => 'Simples Nacional',
                'descricao' => 'ME ou EPP optante pelo Simples. Paga um percentual do faturamento no DAS.',
                'preset' => ['op_simp_nac' => 3, 'csosn_padrao' => '102', 'cfop_padrao' => '5102'],
            ],
            'normal' => [
                'rotulo' => 'Lucro Presumido / Real',
                'descricao' => 'Fora do Simples. Os impostos são apurados separadamente; confirme com o contador.',
                'preset' => ['op_simp_nac' => 1, 'csosn_padrao' => null, 'cfop_padrao' => '5102'],
            ],
        ];
    }
}

if (!function_exists('tributacao_preset_reforma')) {
    function tributacao_preset_reforma(int $ano = 2026): array
    {
        $base = [
            'reforma_cbs' => 0.9,
            'reforma_ibs_uf' => 0.1,
            'reforma_ibs_mun' => 0.0,
            'reforma_cst' => '000',
            'reforma_cclasstrib' => '000001',
        ];

        return $base + ['ano' => 2026, 'estimado' => $ano !== 2026];
    }
}

if (!function_exists('tributacao_perfil_atual')) {
    /** @param object|array $config */
    function tributacao_perfil_atual($config): string
    {
        $op = is_array($config) ? ($config['op_simp_nac'] ?? null) : ($config->op_simp_nac ?? null);

        switch ((int) $op) {
            case 2:
                return 'mei';
            case 3:
                return 'simples';
            default:
                return 'normal';
        }
    }
}

if (!function_exists('tributacao_validar_ibscbs')) {
    /**
     * Pre-validacao local do CST + cClassTrib do IBS/CBS (NT 2025.002), antes de
     * transmitir. Evita as rejeicoes 1023/1024/1025. Retorna mensagem ou null (ok).
     * Na tabela oficial os 3 primeiros digitos do cClassTrib sao o CST (ex.: 000001 -> 000).
     */
    function tributacao_validar_ibscbs(string $cst, string $cClassTrib, int $modelo): ?string
    {
        if (!in_array($modelo, [55, 65], true)) {
            return 'Modelo de documento invalido para o IBS/CBS (use 55 ou 65).';
        }
        if (!preg_match('/^\d{3}$/', $cst)) {
            return 'CST do IBS/CBS deve ter 3 digitos (ex.: 000).';
        }
        if (!preg_match('/^\d{6}$/', $cClassTrib)) {
            return 'cClassTrib deve ter 6 digitos (ex.: 000001).';
        }
        if (substr($cClassTrib, 0, 3) !== $cst) {
            return "cClassTrib {$cClassTrib} nao e compativel com o CST {$cst}: os 3 primeiros digitos do cClassTrib devem ser {$cst}.";
        }

        return null;
    }
}

if (!function_exists('tributacao_parametros_do_ano')) {
    /**
     * Aliquotas da reforma para um ano, a partir das linhas da tabela tributos_parametros.
     * Ano exato: valores da linha (estimado = false). Ano ausente: maior ano anterior (estimado = true).
     * Sem nenhum ano anterior: preset interno de 2026 (estimado = true).
     *
     * @param array $linhas arrays com ano, cbs, ibs_uf, ibs_mun, confirmado, fonte
     */
    function tributacao_parametros_do_ano(int $ano, array $linhas): array
    {
        $porAno = [];
        foreach ($linhas as $l) {
            if (isset($l['ano'])) {
                $porAno[(int) $l['ano']] = $l;
            }
        }

        $formatar = static function (array $l, int $anoRef, bool $estimado): array {
            return [
                'ano' => $anoRef,
                'cbs' => (float) ($l['cbs'] ?? 0),
                'ibs_uf' => (float) ($l['ibs_uf'] ?? 0),
                'ibs_mun' => (float) ($l['ibs_mun'] ?? 0),
                'confirmado' => (bool) ($l['confirmado'] ?? false),
                'estimado' => $estimado,
                'fonte' => (string) ($l['fonte'] ?? ''),
            ];
        };

        if (isset($porAno[$ano])) {
            return $formatar($porAno[$ano], $ano, false);
        }

        $anteriores = array_filter(array_keys($porAno), static fn ($a) => $a < $ano);
        if ($anteriores) {
            return $formatar($porAno[max($anteriores)], $ano, true);
        }

        $preset = tributacao_preset_reforma(2026);

        return [
            'ano' => 2026,
            'cbs' => (float) $preset['reforma_cbs'],
            'ibs_uf' => (float) $preset['reforma_ibs_uf'],
            'ibs_mun' => (float) $preset['reforma_ibs_mun'],
            'confirmado' => false,
            'estimado' => true,
            'fonte' => 'Preset interno (LC 214/2025 - ano de teste)',
        ];
    }
}

/* ---- IVA-T9: opcao semestral do Simples (IBS/CBS no DAS ou regime regular) ---- */

if (! function_exists('tributacao_semestre')) {
    /** @return array{0:int,1:int} [ano, semestre 1|2] */
    function tributacao_semestre(string $data): array
    {
        $t = strtotime($data) ?: time();

        return [(int) date('Y', $t), (int) date('n', $t) <= 6 ? 1 : 2];
    }
}

if (! function_exists('tributacao_regime_ibscbs')) {
    /** Regime de recolhimento de IBS/CBS na data: 'das' ou 'regular'. $opcoes: linhas com ano, semestre, regime. */
    function tributacao_regime_ibscbs(array $opcoes, string $data, int $opSimpNac): string
    {
        if ($opSimpNac === 1) {
            return 'regular';
        }
        [$ano, $sem] = tributacao_semestre($data);
        foreach ($opcoes as $o) {
            if ((int) ($o['ano'] ?? 0) === $ano && (int) ($o['semestre'] ?? 0) === $sem) {
                return ($o['regime'] ?? '') === 'regular' ? 'regular' : 'das';
            }
        }

        return 'das';
    }
}

if (! function_exists('tributacao_texto_credito_cliente')) {
    function tributacao_texto_credito_cliente(string $regime): string
    {
        if ($regime === 'regular') {
            return 'Você recolhe IBS/CBS por fora do DAS: seu cliente empresa aproveita o crédito integral destacado na nota.';
        }

        return 'Por ser do Simples (IBS/CBS no DAS), seu cliente empresa só aproveita como crédito o que você recolheu no DAS.';
    }
}

if (! function_exists('tributacao_janela_opcao')) {
    /**
     * Proxima janela legal (aberta ou futura) para escolher/cancelar o regime de IBS/CBS.
     * Fonte: Res. CGSN 194/2026. Datas futuras dependem de resolucao do CGSN: revisar se mudarem.
     *
     * @return array{rotulo:string,inicio:string,fim:string,aberta:bool,dias_para_abrir:int,dias_restantes:int}|null
     */
    function tributacao_janela_opcao(string $hoje): ?array
    {
        $janelas = [
            // Cancelamento da opcao feita para o 1o semestre de 2027 (opcao encerrou em 30/10/2026).
            ['rotulo' => 'Cancelamento da opção do 1º semestre de 2027', 'inicio' => '2026-11-03', 'fim' => '2026-12-20'],
            // Opcao para o 2o semestre de 2027.
            ['rotulo' => 'Opção pelo regime do 2º semestre de 2027', 'inicio' => '2027-03-01', 'fim' => '2027-03-31'],
        ];
        $h = strtotime($hoje) ?: time();
        $h = strtotime(date('Y-m-d', $h));
        foreach ($janelas as $j) {
            $ini = strtotime($j['inicio']);
            $fim = strtotime($j['fim']);
            if ($h > $fim) {
                continue;
            }

            return $j + [
                'aberta' => $h >= $ini,
                'dias_para_abrir' => $h >= $ini ? 0 : (int) round(($ini - $h) / 86400),
                'dias_restantes' => (int) round(($fim - $h) / 86400),
            ];
        }

        return null;
    }
}
