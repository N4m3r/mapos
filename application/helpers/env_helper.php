<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Leitura e gravacao do .env (application/.env) pela interface.
 *
 * A gravacao preserva todas as linhas e chaves nao informadas, para nao apagar
 * configuracoes alheias a tela que chamou env_salvar(). Valores com espaco ou
 * caractere especial sao gravados entre aspas. Antes de sobrescrever o arquivo,
 * uma copia .bak e mantida. Se o arquivo nao existir, ele e criado apenas com as
 * chaves informadas.
 */
if (! function_exists('env_arquivo')) {
    /**
     * Caminho do .env. Sem argumento, usa o da aplicacao (application/.env).
     */
    function env_arquivo($arquivo = null)
    {
        if ($arquivo !== null && trim((string) $arquivo) !== '') {
            return rtrim((string) $arquivo, '/\\');
        }

        return rtrim(APPPATH, '/\\') . DIRECTORY_SEPARATOR . '.env';
    }
}

if (! function_exists('env_valor')) {
    /**
     * Valor carregado nesta requisicao (ou o padrao quando a chave nao existe).
     */
    function env_valor($chave, $padrao = null)
    {
        return array_key_exists($chave, $_ENV) ? $_ENV[$chave] : $padrao;
    }
}

if (! function_exists('env_valor_linha')) {
    /**
     * Le a chave direto do arquivo, mesmo que ela ainda nao esteja em $_ENV
     * (util logo apos uma gravacao). Mantem o padrao quando ausente.
     */
    function env_valor_linha($chave, $padrao = null, $arquivo = null)
    {
        $caminho = env_arquivo($arquivo);
        if (! is_file($caminho)) {
            return $padrao;
        }

        $linhas = file($caminho, FILE_IGNORE_NEW_LINES);
        if ($linhas === false) {
            return $padrao;
        }

        $padraoBusca = '/^\s*' . preg_quote((string) $chave, '/') . '\s*=(.*)$/';
        foreach ($linhas as $linha) {
            if (preg_match($padraoBusca, $linha, $m)) {
                return env_normalizar_valor($m[1]);
            }
        }

        return $padrao;
    }
}

if (! function_exists('env_normalizar_valor')) {
    /**
     * Remove aspas simples/duplas ao redor do valor e espacos sobrando.
     */
    function env_normalizar_valor($valor)
    {
        $valor = trim((string) $valor);

        if (strlen($valor) >= 2) {
            $primeiro = $valor[0];
            $ultimo = $valor[strlen($valor) - 1];
            if (($primeiro === '"' && $ultimo === '"') || ($primeiro === "'" && $ultimo === "'")) {
                $interno = substr($valor, 1, -1);
                if ($primeiro === '"') {
                    // Desfaz o escape de env_formatar_valor: \" volta a " e \\ volta a \.
                    $interno = str_replace(['\\"', '\\\\'], ['"', '\\'], $interno);
                }

                return $interno;
            }
        }

        // Comentario solto ao final (ex.: "valor # comentario") nao faz parte do valor.
        if (preg_match('/^([^\s#]+)\s+#.*$/', $valor, $m)) {
            return $m[1];
        }

        return $valor;
    }
}

if (! function_exists('env_formatar_valor')) {
    /**
     * Formata o valor para uma linha do .env.
     */
    function env_formatar_valor($valor)
    {
        $valor = (string) $valor;

        // Vazio fica sem aspas ("CHAVE=").
        if ($valor === '') {
            return '';
        }

        // Entre aspas quando houver espaco, #, aspas ou caractere de controle.
        if (preg_match('/[\s#"\'\\\\]/', $valor)) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $valor) . '"';
        }

        return $valor;
    }
}

if (! function_exists('env_salvar')) {
    /**
     * Grava um mapa chave => valor no .env preservando o restante do arquivo.
     *
     * Linhas existentes da chave sao substituidas no lugar; chaves novas sao
     * acrescentadas ao final. Retorna false quando o arquivo nao pode ser lido
     * ou gravado.
     *
     * Nunca cria um .env novo por padrão: um arquivo parcial (só com as chaves
     * enviadas) apagaria as credenciais de banco e derrubaria a instalação. Para
     * criar do zero, o chamador precisa passar $criar = true.
     *
     * @param array       $valores chave => valor
     * @param string|null $arquivo caminho alternativo (testes)
     * @param bool        $criar   permite criar o arquivo quando ele não existe
     */
    function env_salvar(array $valores, $arquivo = null, $criar = false)
    {
        $caminho = env_arquivo($arquivo);

        if (! is_file($caminho) && ! $criar) {
            return false;
        }

        $linhas = [];
        if (is_file($caminho)) {
            $conteudo = file($caminho, FILE_IGNORE_NEW_LINES);
            if ($conteudo === false) {
                return false;
            }
            $linhas = $conteudo;
        }

        $pendentes = [];
        foreach ($valores as $chave => $valor) {
            $chave = trim((string) $chave);
            if ($chave === '' || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $chave)) {
                continue;
            }
            $pendentes[$chave] = env_formatar_valor($valor);
        }

        $encontradas = [];
        foreach ($linhas as $indice => $linha) {
            if (preg_match('/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=/', $linha, $m)) {
                $chave = $m[1];
                if (array_key_exists($chave, $pendentes)) {
                    if (! isset($encontradas[$chave])) {
                        $linhas[$indice] = $chave . '=' . $pendentes[$chave];
                        $encontradas[$chave] = true;
                    } else {
                        // Chave duplicada: remove a repeticao.
                        unset($linhas[$indice]);
                    }
                }
            }
        }

        foreach ($pendentes as $chave => $valorFormatado) {
            if (! isset($encontradas[$chave])) {
                $linhas[] = $chave . '=' . $valorFormatado;
            }
        }

        $novo = implode(PHP_EOL, array_values($linhas));
        if ($novo !== '' && substr($novo, -1) !== "\n") {
            $novo .= PHP_EOL;
        }

        // Copia de seguranca antes de gravar (mantem apenas a ultima).
        if (is_file($caminho)) {
            @copy($caminho, $caminho . '.bak');
        }

        return @file_put_contents($caminho, $novo, LOCK_EX) !== false;
    }
}
