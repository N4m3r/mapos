<?php

if (! function_exists('dateInterval')) {
    /**
     * Vencimento da garantia: soma os dias de garantia a data inicial.
     *
     * As views chamam esta funcao com dataFinal/dataVenda vindos do banco, que
     * em registros antigos aparecem zerados ('0000-00-00') ou nulos. Sem data
     * inicial valida nao existe vencimento a exibir e a funcao devolve ''.
     *
     * @param string|null     $startDate Data inicial em Y-m-d (hora e ignorada).
     * @param string|int|null $finalDate Dias de garantia.
     * @return string Vencimento em d/m/Y, ou '' quando nao ha data valida.
     */
    function dateInterval($startDate, $finalDate)
    {
        $dias = filter_var(is_scalar($finalDate) ? trim((string) $finalDate) : '', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0],
        ]);

        if ($dias === false) {
            return '';
        }

        $texto = is_scalar($startDate) ? trim((string) $startDate) : '';

        if (strlen($texto) < 10 || strpos($texto, '0000-00-00') === 0) {
            return '';
        }

        $obj_data = false;
        foreach (['!Y-m-d', '!Y/m/d'] as $formato) {
            $obj_data = DateTime::createFromFormat($formato, substr($texto, 0, 10));
            $erros = DateTime::getLastErrors();

            if ($obj_data !== false && ($erros === false || ($erros['warning_count'] === 0 && $erros['error_count'] === 0))) {
                break;
            }

            $obj_data = false;
        }

        if ($obj_data === false) {
            return '';
        }

        $obj_data->add(new DateInterval('P' . $dias . 'D'));

        return $obj_data->format('d/m/Y');
    }
}
