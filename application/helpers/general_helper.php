<?php

use Piggly\Pix\Parser;

if (! function_exists('emails_cobranca')) {
    /**
     * Destinatários de e-mail de cobrança/boleto de uma cobrança.
     *
     * Retorna o e-mail principal do cliente somado ao e-mail secundário
     * (financeiro), quando cadastrado, sem duplicar e apenas os válidos.
     *
     * @param object $cobranca Registro de cobrança já com os dados do cliente
     *                         (campos `email` e `email_secundario`), como o
     *                         retornado por Cobrancas_model::getById().
     * @return array Lista de e-mails válidos e únicos.
     */
    function emails_cobranca($cobranca)
    {
        $destinatarios = [];
        foreach (['email', 'email_secundario'] as $campo) {
            $valor = isset($cobranca->$campo) ? trim((string) $cobranca->$campo) : '';
            if ($valor !== '' && filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                $destinatarios[] = $valor;
            }
        }

        return array_values(array_unique($destinatarios));
    }
}

if (! function_exists('convertUrlToUploadsPath')) {
    function convertUrlToUploadsPath($url)
    {
        if (! $url) {
            return;
        }

        return FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . basename($url);
    }
}

if (! function_exists('limitarTexto')) {
    function limitarTexto($texto, $limite)
    {
        $contador = strlen($texto);

        if ($contador >= $limite) {
            $texto = substr($texto, 0, strrpos(substr($texto, 0, $limite), ' ')) . '...';

            return $texto;
        } else {
            return $texto;
        }
    }
}

if (! function_exists('getMoneyAsCents')) {
    function getMoneyAsCents($value)
    {
        // make sure we are dealing with a proper number now, no +.4393 or 3...304 or 76.5895,94
        if (! is_numeric($value)) {
            throw new \InvalidArgumentException('A entrada deve ser numérica!');
        }

        return intval(round(floatval($value), 2) * 100);
    }
}

if (! function_exists('boletoValoresNota')) {
    /**
     * Valores do boleto de uma nota fiscal, espelhando a lógica do gateway Cora:
     * NFS-e com ISS retido pelo tomador (configuracoes_nfe.tp_ret_issqn = 2) sai
     * líquido do ISS; NF-e (produtos) não tem retenção. Retorna
     * ['bruto', 'iss', 'liquido'] em reais.
     */
    function boletoValoresNota($nota, $configNfe)
    {
        $bruto = (float) ($nota->valor_total ?? 0);
        $iss = 0.0;
        if (isset($nota->tipo) && $nota->tipo === 'nfse'
            && $configNfe && (int) ($configNfe->tp_ret_issqn ?? 0) === 2) {
            $iss = round($bruto * ((float) ($configNfe->aliquota_iss ?? 0)) / 100, 2);
        }

        return [
            'bruto' => $bruto,
            'iss' => $iss,
            'liquido' => round($bruto - $iss, 2),
        ];
    }
}

if (! function_exists('getCobrancaTransactionStatus')) {
    function getCobrancaTransactionStatus($paymentGatewaysConfig, $paymentGateway, $status)
    {
        return $paymentGatewaysConfig[$paymentGateway]['transaction_status'][$status];
    }
}

if (! function_exists('getPixKeyType')) {
    function getPixKeyType($value)
    {
        if (Parser::validateDocument($value)) {
            return Parser::KEY_TYPE_DOCUMENT;
        }

        if (Parser::validateEmail($value)) {
            return Parser::KEY_TYPE_EMAIL;
        }

        if (Parser::validatePhone($value)) {
            return Parser::KEY_TYPE_PHONE;
        }

        if (Parser::validateRandom($value)) {
            return Parser::KEY_TYPE_RANDOM;
        }

        return null;
    }
}

if (! function_exists('getAmount')) {
    function getAmount($money)
    {
        $cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
        $onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);

        $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;

        $stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
        $removedThousandSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '', $stringWithCommaOrDot);

        return floatval(str_replace(',', '.', $removedThousandSeparator));
    }
}

if (! function_exists('formatar_garantia')) {
    /**
     * Garantia da OS e texto livre: "90" vira "90 dias"; "30 dias" ou "1 ano" ficam
     * como estao (antes saia "30 dias dias"); vazio devolve $vazio.
     */
    function formatar_garantia($garantia, $vazio = '')
    {
        $garantia = trim((string) $garantia);
        if ($garantia === '') {
            return $vazio;
        }

        return ctype_digit($garantia) ? $garantia . ' dias' : $garantia;
    }
}

if (! function_exists('asset_versao')) {
    /**
     * Versao de um arquivo de assets para usar no ?v= (data de modificacao),
     * para o navegador buscar de novo quando o arquivo mudar.
     *
     * @param string $caminho Caminho relativo a raiz do site (ex.: 'assets/js/csrf.js')
     * @param string $padrao  Valor usado se o arquivo nao existir
     */
    function asset_versao($caminho, $padrao = '1')
    {
        $arquivo = FCPATH . $caminho;

        return is_file($arquivo) ? (string) filemtime($arquivo) : $padrao;
    }
}

if (! function_exists('arquivo_e_imagem')) {
    /**
     * Indica se o caminho aponta para um arquivo de imagem existente (pelo conteudo, nao pela extensao).
     */
    function arquivo_e_imagem($caminho)
    {
        if (empty($caminho) || ! is_file($caminho)) {
            return false;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($caminho);

        return is_string($mime) && strpos($mime, 'image/') === 0;
    }
}

if (! function_exists('cpf_valido')) {
    /**
     * Valida os digitos de um CPF (aceita com ou sem mascara).
     */
    function cpf_valido($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', (string) $cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1+$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += $cpf[$i] * (($t + 1) - $i);
            }
            $resto = $soma % 11;
            if ((int) $cpf[$t] !== ($resto < 2 ? 0 : 11 - $resto)) {
                return false;
            }
        }

        return true;
    }
}

if (! function_exists('cnpj_valido')) {
    /**
     * Valida os digitos de um CNPJ (aceita com ou sem mascara).
     */
    function cnpj_valido($cnpj)
    {
        $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1+$/', $cnpj)) {
            return false;
        }
        foreach ([12 => 5, 13 => 6] as $tamanho => $peso) {
            $soma = 0;
            for ($i = 0; $i < $tamanho; $i++) {
                $soma += $cnpj[$i] * $peso;
                $peso = $peso === 2 ? 9 : $peso - 1;
            }
            $resto = $soma % 11;
            if ((int) $cnpj[$tamanho] !== ($resto < 2 ? 0 : 11 - $resto)) {
                return false;
            }
        }

        return true;
    }
}

if (! function_exists('formatar_chave_pix')) {
    /**
     * Chave PIX para exibicao: CPF e CNPJ com mascara, celular (11 digitos) como (00) 00000-0000;
     * e-mail e chave aleatoria ficam como estao.
     */
    function formatar_chave_pix($chave)
    {
        $chave = (string) $chave;
        $digitos = preg_replace('/[^0-9]/', '', $chave);
        if (cpf_valido($chave)) {
            return substr($digitos, 0, 3) . '.' . substr($digitos, 3, 3) . '.' . substr($digitos, 6, 3) . '-' . substr($digitos, 9);
        }
        if (cnpj_valido($chave)) {
            return substr($digitos, 0, 2) . '.' . substr($digitos, 2, 3) . '.' . substr($digitos, 5, 3) . '/' . substr($digitos, 8, 4) . '-' . substr($digitos, 12);
        }
        if (strlen($chave) === 11) {
            return '(' . substr($chave, 0, 2) . ') ' . substr($chave, 2, 5) . '-' . substr($chave, 7);
        }

        return $chave;
    }
}
