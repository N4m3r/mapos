<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cálculos de descontos legais CLT (INSS, IRRF, VT, FGTS informativo).
 *
 * Valores e alíquotas vêm de `configuracoes` (editáveis em RH > Descontos CLT).
 * O resultado é gerencial — o holerite oficial da contabilidade prevalece.
 */
class Rh_clt
{
    protected $CI;
    protected $cfg = [];

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->carregarConfig();
    }

    public function carregarConfig()
    {
        $chaves = [
            'rh_clt_calcular_inss', 'rh_clt_calcular_irrf', 'rh_clt_mostrar_fgts', 'rh_clt_fgts_aliquota',
            'rh_clt_vt_ativo', 'rh_clt_vt_percentual', 'rh_clt_vt_valor_fixo', 'rh_clt_outras_deducoes',
            'rh_clt_dependente_deducao', 'rh_clt_inss_tabela', 'rh_clt_irrf_tabela',
            'rh_he_requer_aprovacao', 'rh_he_percentual_50', 'rh_he_percentual_100',
        ];
        $this->cfg = [];
        if (! $this->CI->db->table_exists('configuracoes')) {
            return;
        }
        $this->CI->db->where_in('config', $chaves);
        foreach ($this->CI->db->get('configuracoes')->result() as $row) {
            $this->cfg[$row->config] = $row->valor;
        }
    }

    public function get($chave, $default = null)
    {
        return array_key_exists($chave, $this->cfg) ? $this->cfg[$chave] : $default;
    }

    public function tabelaInss()
    {
        $raw = $this->get('rh_clt_inss_tabela', '[]');
        $t = json_decode($raw, true);
        return is_array($t) ? $t : [];
    }

    public function tabelaIrrf()
    {
        $raw = $this->get('rh_clt_irrf_tabela', '[]');
        $t = json_decode($raw, true);
        return is_array($t) ? $t : [];
    }

    /**
     * INSS progressivo (faixas) sobre a base de contribuição.
     * Usa o teto da última faixa como limite de contribuição.
     */
    public function calcularInss($base)
    {
        $base = max(0, (float) $base);
        $tabela = $this->tabelaInss();
        if (empty($tabela) || $base <= 0) {
            return 0.0;
        }
        $teto = (float) end($tabela)['ate'];
        $base = min($base, $teto);
        $total = 0.0;
        $anterior = 0.0;
        foreach ($tabela as $faixa) {
            $limite = (float) $faixa['ate'];
            $aliq = (float) $faixa['aliquota'] / 100;
            if ($base <= $anterior) {
                break;
            }
            $parcela = min($base, $limite) - $anterior;
            if ($parcela > 0) {
                $total += $parcela * $aliq;
            }
            $anterior = $limite;
        }
        return round($total, 2);
    }

    /**
     * IRRF mensal: base = remuneração − INSS − (dependentes × dedução).
     */
    public function calcularIrrf($baseBruta, $inss = 0, $dependentes = 0)
    {
        $dedDep = (float) $this->get('rh_clt_dependente_deducao', 189.59);
        $base = max(0, (float) $baseBruta - (float) $inss - ((int) $dependentes * $dedDep));
        $tabela = $this->tabelaIrrf();
        if (empty($tabela) || $base <= 0) {
            return 0.0;
        }
        foreach ($tabela as $faixa) {
            if ($base <= (float) $faixa['ate']) {
                $aliq = (float) $faixa['aliquota'] / 100;
                $ded = (float) ($faixa['deducao'] ?? 0);
                return max(0, round(($base * $aliq) - $ded, 2));
            }
        }
        return 0.0;
    }

    /** Vale-transporte: até 6% do salário (CLT art. 9 do Dec. 95.247/87). */
    public function calcularVt($salarioBase)
    {
        if ($this->get('rh_clt_vt_ativo', '0') !== '1') {
            return 0.0;
        }
        $fixo = (float) str_replace(',', '.', (string) $this->get('rh_clt_vt_valor_fixo', '0'));
        if ($fixo > 0) {
            return round($fixo, 2);
        }
        $pct = (float) $this->get('rh_clt_vt_percentual', '6');
        $pct = min(6.0, max(0, $pct)); // teto legal 6%
        return round(((float) $salarioBase) * $pct / 100, 2);
    }

    /** FGTS (informativo — encargo do empregador, não desconta do colaborador). */
    public function calcularFgts($base)
    {
        $aliq = (float) $this->get('rh_clt_fgts_aliquota', '8');
        return round(((float) $base) * $aliq / 100, 2);
    }

    /**
     * Monta os descontos legais para um colaborador na competência.
     *
     * @param object $colaborador
     * @param float  $proventosTotais salário + proventos de lançamentos
     * @param int    $dependentes
     * @return array{itens: array, total_descontos: float, fgts: float, base: float}
     */
    public function descontosLegais($colaborador, $proventosTotais, $dependentes = 0)
    {
        $base = (float) $proventosTotais;
        $salario = (float) ($colaborador->salario_base ?? 0);
        $itens = [];
        $total = 0.0;

        $inss = 0.0;
        if ($this->get('rh_clt_calcular_inss', '1') === '1') {
            $inss = $this->calcularInss($base);
            if ($inss > 0) {
                $itens[] = (object) [
                    'tipo' => 'inss',
                    'natureza' => 'desconto',
                    'descricao' => 'INSS (contribuição previdenciária)',
                    'valor' => $inss,
                    'legal' => true,
                ];
                $total += $inss;
            }
        }

        if ($this->get('rh_clt_calcular_irrf', '1') === '1') {
            $irrf = $this->calcularIrrf($base, $inss, $dependentes);
            if ($irrf > 0) {
                $itens[] = (object) [
                    'tipo' => 'irrf',
                    'natureza' => 'desconto',
                    'descricao' => 'IRRF (imposto de renda retido)',
                    'valor' => $irrf,
                    'legal' => true,
                ];
                $total += $irrf;
            }
        }

        $vt = $this->calcularVt($salario > 0 ? $salario : $base);
        if ($vt > 0) {
            $itens[] = (object) [
                'tipo' => 'vale_transporte',
                'natureza' => 'desconto',
                'descricao' => 'Vale-transporte (máx. 6% — CLT/Dec. 95.247)',
                'valor' => $vt,
                'legal' => true,
            ];
            $total += $vt;
        }

        $outras = (float) str_replace(',', '.', (string) $this->get('rh_clt_outras_deducoes', '0'));
        if ($outras > 0) {
            $itens[] = (object) [
                'tipo' => 'outro_legal',
                'natureza' => 'desconto',
                'descricao' => 'Outras deduções configuradas',
                'valor' => $outras,
                'legal' => true,
            ];
            $total += $outras;
        }

        $fgts = 0.0;
        if ($this->get('rh_clt_mostrar_fgts', '1') === '1') {
            $fgts = $this->calcularFgts($base);
        }

        return [
            'itens' => $itens,
            'total_descontos' => round($total, 2),
            'fgts' => $fgts,
            'base' => $base,
            'inss' => $inss,
        ];
    }

    public function heRequerAprovacao()
    {
        return $this->get('rh_he_requer_aprovacao', '1') !== '0';
    }

    public function fatorExtra50()
    {
        return 1 + ((float) $this->get('rh_he_percentual_50', '50') / 100);
    }

    public function fatorExtra100()
    {
        return 1 + ((float) $this->get('rh_he_percentual_100', '100') / 100);
    }
}
