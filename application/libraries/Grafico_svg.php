<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Graficos SVG acessiveis (PHP puro, sem banco): colunas, barras horizontais e barra empilhada 100%.
 * Cada marca e um <g class="gsvg-marca" tabindex="0" data-dica="..."> lido por assets/js/grafico-svg.js.
 * Cores/tintas: skill dataviz. Texto nunca usa a cor da serie.
 */
class Grafico_svg
{
    public const AZUL = '#2a78d6';
    public const TINTA = '#0b0b0b';
    public const TINTA_2 = '#52514e';
    public const EIXO = '#898781';
    public const GRADE = '#e1e0d9';
    public const BASE = '#c3c2b7';
    public const SUPERFICIE = '#fcfcfb';
    public const FONTE = 'var(--ui-fonte, system-ui, sans-serif)';

    private static $seq = 0;

    /** Numero em pt-BR (virgula decimal, ponto de milhar). */
    public static function num($v, int $dec = 0): string
    {
        return number_format((float) $v, $dec, ',', '.');
    }

    public static function esc($s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function f($n): string
    {
        $s = rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');

        return ($s === '' || $s === '-0') ? '0' : $s;
    }

    private static function abrir(int $w, int $h, array $opc, string &$id): string
    {
        $id = 'gsvg' . (++self::$seq);
        $titulo = (string) ($opc['titulo'] ?? 'Gráfico');
        $desc = (string) ($opc['descricao'] ?? '');

        return '<svg class="gsvg" xmlns="http://www.w3.org/2000/svg" role="img" width="100%" viewBox="0 0 ' . $w . ' ' . $h . '"'
            . ' aria-labelledby="' . $id . 't' . ($desc !== '' ? ' ' . $id . 'd' : '') . '" font-family="' . self::esc(self::FONTE) . '">'
            . '<title id="' . $id . 't">' . self::esc($titulo) . '</title>'
            . ($desc !== '' ? '<desc id="' . $id . 'd">' . self::esc($desc) . '</desc>' : '');
    }

    private static function texto($x, $y, $s, $anchor = 'start', $cor = self::EIXO, $size = 11, $peso = 400): string
    {
        return '<text x="' . self::f($x) . '" y="' . self::f($y) . '" text-anchor="' . $anchor . '" fill="' . $cor
            . '" font-size="' . $size . '" font-weight="' . $peso . '">' . self::esc($s) . '</text>';
    }

    private static function marca(string $dica): string
    {
        return '<g class="gsvg-marca" tabindex="0" role="img" aria-label="' . self::esc(str_replace("\n", ': ', $dica))
            . '" data-dica="' . self::esc($dica) . '">';
    }

    /** Teto "redondo" e passo para o eixo (no maximo 4 intervalos). */
    public static function escala(float $max, bool $inteiro = true): array
    {
        if ($max <= 0) {
            return [1.0, 1.0];
        }
        $mag = 10 ** floor(log10($max / 4));
        $passo = $mag;
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            $passo = $m * $mag;
            if ($inteiro && $passo < 1) {
                $passo = 1;
            }
            if (ceil($max / $passo) <= 4) {
                break;
            }
        }

        return [(float) ($passo * ceil($max / $passo)), (float) $passo];
    }

    /** Marca fina com ponta arredondada (4px) e base reta. */
    private static function colunaPath($x, $base, $w, $h, $r = 4): string
    {
        $r = min($r, $w / 2, $h);
        $y = $base - $h;

        return 'M' . self::f($x) . ' ' . self::f($base) . 'V' . self::f($y + $r)
            . 'Q' . self::f($x) . ' ' . self::f($y) . ' ' . self::f($x + $r) . ' ' . self::f($y)
            . 'H' . self::f($x + $w - $r)
            . 'Q' . self::f($x + $w) . ' ' . self::f($y) . ' ' . self::f($x + $w) . ' ' . self::f($y + $r)
            . 'V' . self::f($base) . 'Z';
    }

    /**
     * Colunas verticais (serie unica). $pontos: [['rotulo','valor','dica'], ...]
     * $opc: titulo, descricao, cor, decimais, rotulos_x (max de rotulos no eixo X).
     */
    public static function colunas(array $pontos, array $opc = []): string
    {
        $W = 640;
        $H = 210;
        $ml = 34;
        $mr = 8;
        $mt = 20;
        $mb = 24;
        $cor = $opc['cor'] ?? self::AZUL;
        $dec = (int) ($opc['decimais'] ?? 0);
        $pontos = array_values($pontos);
        $n = count($pontos);
        $id = '';
        $s = self::abrir($W, $H, $opc, $id);

        $max = 0.0;
        foreach ($pontos as $p) {
            $max = max($max, (float) ($p['valor'] ?? 0));
        }
        [$teto, $passo] = self::escala($max, $dec === 0);
        $base = $H - $mb;
        $ph = $base - $mt;
        $pw = $W - $ml - $mr;

        for ($v = 0.0; $v <= $teto + 1e-9; $v += $passo) {
            $y = $base - ($v / $teto * $ph);
            if ($v > 0) {
                $s .= '<line x1="' . $ml . '" x2="' . ($W - $mr) . '" y1="' . self::f($y) . '" y2="' . self::f($y) . '" stroke="' . self::GRADE . '" stroke-width="1"/>';
            }
            $s .= self::texto($ml - 6, $y + 4, self::num($v, $dec), 'end');
        }
        $s .= '<line x1="' . $ml . '" x2="' . ($W - $mr) . '" y1="' . $base . '" y2="' . $base . '" stroke="' . self::BASE . '" stroke-width="1"/>';

        if ($n === 0) {
            return $s . '</svg>';
        }

        $banda = $pw / $n;
        $larg = min(24, max(2, $banda * 0.6));
        $cada = max(1, (int) ceil($n / max(1, (int) ($opc['rotulos_x'] ?? 8))));
        $iMax = 0;
        foreach ($pontos as $i => $p) {
            if ((float) ($p['valor'] ?? 0) > (float) $pontos[$iMax]['valor']) {
                $iMax = $i;
            }
        }

        foreach ($pontos as $i => $p) {
            $val = (float) ($p['valor'] ?? 0);
            $cx = $ml + $banda * ($i + 0.5);
            $h = $val / $teto * $ph;
            $dica = (string) ($p['dica'] ?? (($p['rotulo'] ?? '') . "\n" . self::num($val, $dec)));
            $s .= self::marca($dica)
                . '<rect class="gsvg-alvo" x="' . self::f($cx - $banda / 2) . '" y="' . $mt . '" width="' . self::f($banda) . '" height="' . self::f($ph) . '" fill="transparent"/>';
            if ($h > 0) {
                $s .= '<path class="gsvg-barra" d="' . self::colunaPath($cx - $larg / 2, $base, $larg, max($h, 1)) . '" fill="' . $cor . '"/>';
            }
            $s .= '</g>';
            $mostraX = ($i % $cada === 0) || ($i === $n - 1 && ($n - 1) % $cada >= $cada / 2);
            if ($mostraX) {
                $s .= self::texto($cx, $base + 15, (string) ($p['rotulo'] ?? ''), 'middle');
            }
            if ($val > 0 && ($i === $iMax || $i === $n - 1)) {
                $s .= self::texto($cx, $base - $h - 5, self::num($val, $dec), 'middle', self::TINTA, 11, 600);
            }
        }

        return $s . '</svg>';
    }

    /**
     * Barras horizontais (serie unica), valor na ponta. $pontos: [['rotulo','valor','dica'], ...]
     * $opc: titulo, descricao, cor, decimais, sufixo (ex.: " h").
     */
    public static function barrasHorizontais(array $pontos, array $opc = []): string
    {
        $W = 640;
        $linha = 28;
        $mt = 4;
        $ml = 150;
        $mr = 70;
        $pontos = array_values($pontos);
        $n = count($pontos);
        $H = max(40, $mt * 2 + $n * $linha);
        $cor = $opc['cor'] ?? self::AZUL;
        $dec = (int) ($opc['decimais'] ?? 0);
        $suf = (string) ($opc['sufixo'] ?? '');
        $id = '';
        $s = self::abrir($W, $H, $opc, $id);

        $max = 0.0;
        foreach ($pontos as $p) {
            $max = max($max, (float) ($p['valor'] ?? 0));
        }
        $pw = $W - $ml - $mr;
        $s .= '<line x1="' . $ml . '" x2="' . $ml . '" y1="' . $mt . '" y2="' . ($H - $mt) . '" stroke="' . self::BASE . '" stroke-width="1"/>';

        foreach ($pontos as $i => $p) {
            $val = (float) ($p['valor'] ?? 0);
            $y = $mt + $i * $linha;
            $w = $max > 0 ? $val / $max * $pw : 0;
            $rot = (string) ($p['rotulo'] ?? '');
            $rotCurto = mb_strlen($rot) > 22 ? mb_substr($rot, 0, 21) . '…' : $rot;
            $dica = (string) ($p['dica'] ?? ($rot . "\n" . self::num($val, $dec) . $suf));
            $s .= self::marca($dica)
                . '<rect class="gsvg-alvo" x="0" y="' . $y . '" width="' . $W . '" height="' . $linha . '" fill="transparent"/>'
                . self::texto($ml - 8, $y + $linha / 2 + 4, $rotCurto, 'end', self::TINTA_2, 12);
            if ($w > 0) {
                $w = max($w, 6);
                $x2 = $ml + $w;
                $s .= '<path class="gsvg-barra" d="M' . $ml . ' ' . ($y + 6) . 'H' . self::f($x2 - 4)
                    . 'Q' . self::f($x2) . ' ' . ($y + 6) . ' ' . self::f($x2) . ' ' . ($y + 10)
                    . 'V' . ($y + $linha - 10) . 'Q' . self::f($x2) . ' ' . ($y + $linha - 6) . ' ' . self::f($x2 - 4) . ' ' . ($y + $linha - 6)
                    . 'H' . $ml . 'Z" fill="' . $cor . '"/>';
            }
            $s .= self::texto($ml + $w + 6, $y + $linha / 2 + 4, self::num($val, $dec) . $suf, 'start', self::TINTA, 12, 600)
                . '</g>';
        }

        return $s . '</svg>';
    }

    /**
     * Barras empilhadas 100%, uma por linha (ex.: pontos por status de cada projeto).
     * $linhas: [['rotulo','total','texto','segmentos'=>[['rotulo','valor','cor','dica']]]]
     * O que falta ate 'total' fica como trilho vazio (nao e cor de dado).
     * $opc: titulo, descricao, legenda => [['rotulo','cor']] (cor null = trilho)
     */
    public static function empilhada100(array $linhas, array $opc = []): string
    {
        $W = 640;
        $barW = 430;
        $altLeg = 22;
        $altLinha = 44;
        $linhas = array_values($linhas);
        $H = $altLeg + max(1, count($linhas)) * $altLinha + 4;
        $id = '';
        $s = self::abrir($W, $H, $opc, $id);

        $x = 0;
        foreach ($opc['legenda'] ?? [] as $l) {
            $rot = (string) ($l['rotulo'] ?? '');
            $s .= '<rect x="' . self::f($x) . '" y="3" width="12" height="12" rx="3" fill="' . self::esc(($l['cor'] ?? null) ?: self::GRADE) . '"/>'
                . self::texto($x + 17, 13, $rot, 'start', self::TINTA_2, 11);
            $x += 17 + 6.2 * mb_strlen($rot) + 14;
        }

        foreach ($linhas as $i => $L) {
            $y = $altLeg + $i * $altLinha;
            $total = max(0, (float) ($L['total'] ?? 0));
            $rot = (string) ($L['rotulo'] ?? '');
            $rotCurto = mb_strlen($rot) > 60 ? mb_substr($rot, 0, 59) . '…' : $rot;
            $s .= self::texto(0, $y + 12, $rotCurto, 'start', self::TINTA, 12, 600)
                . '<rect x="0" y="' . ($y + 20) . '" width="' . $barW . '" height="14" rx="4" fill="' . self::GRADE . '"/>';
            $cx = 0.0;
            foreach ($L['segmentos'] ?? [] as $seg) {
                $v = (float) ($seg['valor'] ?? 0);
                if ($v <= 0 || $total <= 0) {
                    continue;
                }
                $w = $v / $total * $barW;
                $dica = (string) ($seg['dica'] ?? ($rot . "\n" . ($seg['rotulo'] ?? '') . ': ' . self::num($v)));
                $s .= self::marca($dica)
                    . '<rect class="gsvg-alvo" x="' . self::f($cx) . '" y="' . ($y + 14) . '" width="' . self::f($w) . '" height="26" fill="transparent"/>'
                    . '<rect class="gsvg-barra" x="' . self::f($cx + 1) . '" y="' . ($y + 20) . '" width="' . self::f(max(0.5, $w - 2)) . '" height="14" rx="3" fill="' . self::esc($seg['cor'] ?? self::AZUL) . '"/>'
                    . '</g>';
                $cx += $w;
            }
            $s .= self::texto($barW + 14, $y + 31, (string) ($L['texto'] ?? ''), 'start', self::TINTA, 12, 500);
        }

        return $s . '</svg>';
    }
}
