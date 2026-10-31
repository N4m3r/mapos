<?php

/**
 * Funcoes puras (sem CodeIgniter) do perfil fiscal do cliente: indicador de IE
 * do destinatario, validacao de SUFRAMA e rotulo leigo para a tela.
 */

if (! function_exists('cliente_ind_ie_dest')) {
    /**
     * indIEDest da NF-e: 1 = contribuinte ICMS, 2 = isento de IE, 9 = nao contribuinte.
     * Usa clientes.ind_ie_dest quando preenchido; senao deduz da IE
     * (preenchida = 1, 'ISENTO' = 2, vazia = 9).
     */
    function cliente_ind_ie_dest($cliente): int
    {
        $c = (array) $cliente;
        $ind = $c['ind_ie_dest'] ?? null;
        if ($ind !== null && $ind !== '' && in_array((int) $ind, [1, 2, 9], true)) {
            return (int) $ind;
        }

        $ie = strtoupper(trim((string) ($c['ie'] ?? '')));
        if ($ie === '') {
            return 9;
        }

        return $ie === 'ISENTO' ? 2 : 1;
    }
}

if (! function_exists('cliente_suframa_valida')) {
    /** Inscricao SUFRAMA: exatamente 9 digitos numericos. */
    function cliente_suframa_valida(?string $s): bool
    {
        return $s !== null && preg_match('/^\d{9}$/', trim($s)) === 1;
    }
}

if (! function_exists('cliente_perfil_rotulo')) {
    /** Rotulo leigo do perfil fiscal, ex.: "Empresa do Simples · contribuinte de ICMS". */
    function cliente_perfil_rotulo($cliente): string
    {
        $c = (array) $cliente;
        $partes = [];

        $regimes = ['mei' => 'MEI', 'simples' => 'Empresa do Simples', 'normal' => 'Empresa do regime normal'];
        $regime = $c['regime_tributario'] ?? null;
        if ($regime !== null && isset($regimes[$regime])) {
            $partes[] = $regimes[$regime];
        }

        if (! empty($c['orgao_publico'])) {
            $partes[] = 'órgão público';
        }

        $ind = cliente_ind_ie_dest($c);
        $ies = [1 => 'contribuinte de ICMS', 2 => 'isento de IE', 9 => 'não contribuinte de ICMS'];
        $partes[] = $ies[$ind];

        return implode(' · ', $partes);
    }
}
