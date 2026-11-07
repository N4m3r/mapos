<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Dados do emitente: cabecalho dos impressos e conferencia fiscal.
 *
 * O mesmo bloco de razao social, CNPJ, endereco e contato estava copiado em
 * seis views de impressao (OS, orcamento, venda, area do cliente e checkin),
 * cada uma com uma variacao: uma pulava o CNPJ mascarado, outra nao mostrava o
 * responsavel. Dados novos (nome fantasia, IE, IM) teriam de ser acrescentados
 * em todas. Aqui ha uma funcao so, e as views so escolhem o complemento final
 * ("Responsavel: Fulano", "Vendedor: Fulano").
 *
 * Funcoes puras, testadas em tests/Unit/EmitenteHelperTest.php.
 */
if (! function_exists('emitente_cabecalho')) {
    /**
     * Linhas de identificacao da empresa para o cabecalho do documento.
     *
     * Cada linha e texto puro (sem HTML) para a view decidir como quebrar. O
     * CNPJ mascarado "00.000.000/0000-00" e o recurso antigo para quem nao quer
     * exibir o documento: continua sendo omitido, como antes.
     *
     * @param object|array|null $emitente linha da tabela emitente
     *
     * @return array<int, string>
     */
    function emitente_cabecalho($emitente)
    {
        $v = static function ($campo) use ($emitente) {
            if (is_object($emitente)) {
                return isset($emitente->$campo) ? (string) $emitente->$campo : '';
            }
            if (is_array($emitente)) {
                return (string) ($emitente[$campo] ?? '');
            }

            return '';
        };

        $linhas = [];
        if ($v('cnpj') !== '' && $v('cnpj') !== '00.000.000/0000-00') {
            $linhas[] = 'CNPJ: ' . $v('cnpj');
        }
        if ($v('ie') !== '') {
            $linhas[] = 'IE: ' . $v('ie');
        }
        if ($v('inscricao_municipal') !== '') {
            $linhas[] = 'IM: ' . $v('inscricao_municipal');
        }

        $endereco = emitente_endereco($emitente);
        if ($endereco !== '') {
            $linhas[] = $endereco;
        }

        return $linhas;
    }
}

if (! function_exists('emitente_endereco')) {
    /**
     * Endereco em uma linha: "Rua X, 123, Bairro - Cidade/UF - CEP 00000-000".
     * Complemento entra depois do numero quando existe.
     *
     * @param object|array|null $emitente
     */
    function emitente_endereco($emitente)
    {
        $v = static function ($campo) use ($emitente) {
            if (is_object($emitente)) {
                return isset($emitente->$campo) ? trim((string) $emitente->$campo) : '';
            }
            if (is_array($emitente)) {
                return trim((string) ($emitente[$campo] ?? ''));
            }

            return '';
        };

        $rua = $v('rua');
        $numero = $v('numero');
        $complemento = $v('complemento');
        $bairro = $v('bairro');
        $cidade = $v('cidade');
        $uf = $v('uf');
        $cep = $v('cep');

        $logradouro = $rua;
        if ($numero !== '') {
            $logradouro = trim($logradouro . ', ' . $numero);
        }
        if ($complemento !== '') {
            $logradouro = trim($logradouro . ' - ' . $complemento);
        }
        if ($bairro !== '') {
            $logradouro = trim($logradouro . ', ' . $bairro, ', ');
        }

        $localidade = $cidade;
        if ($uf !== '') {
            $localidade = trim($localidade . '/' . $uf, '/');
        }
        if ($cep !== '') {
            $localidade = trim($localidade . ' - CEP ' . $cep, ' -');
        }

        $partes = array_filter([$logradouro, $localidade], static function ($p) {
            return $p !== '';
        });

        return implode(' - ', $partes);
    }
}

if (! function_exists('emitente_nome_exibicao')) {
    /**
     * Nome fantasia quando existir (o cliente reconhece pelo fantasia), senao a
     * razao social.
     *
     * @param object|array|null $emitente
     */
    function emitente_nome_exibicao($emitente)
    {
        if (is_object($emitente)) {
            $fantasia = trim((string) ($emitente->nome_fantasia ?? ''));
            $nome = trim((string) ($emitente->nome ?? ''));
        } elseif (is_array($emitente)) {
            $fantasia = trim((string) ($emitente['nome_fantasia'] ?? ''));
            $nome = trim((string) ($emitente['nome'] ?? ''));
        } else {
            return '';
        }

        return $fantasia !== '' ? $fantasia : $nome;
    }
}

if (! function_exists('emitente_checklist_fiscal')) {
    /**
     * O que falta para emitir nota, item a item, com o mesmo critério usado na
     * emissão (NfeService::validarEmitente e NfseService exigem CNPJ, IE e
     * endereço; o código IBGE vem da configuração fiscal).
     *
     * Devolve uma lista de itens `{ok, titulo, detalhe, acao}`; `acao` e a rota
     * para resolver, ou null. A tela do emitente desenha isso como conferencia.
     *
     * @param object|array|null $emitente             linha do emitente
     * @param object|array|null $configFiscal         linha de configuracoes_nfe (tomada nula sem tabela)
     * @param bool              $certificadoPresente  certificado A1 carregado?
     *
     * @return array<int, array{ok: bool, titulo: string, detalhe: string, acao: ?string}>
     */
    function emitente_checklist_fiscal($emitente, $configFiscal = null, $certificadoPresente = false)
    {
        $campo = static function ($fonte, $campo) {
            if (is_object($fonte)) {
                return trim((string) ($fonte->$campo ?? ''));
            }
            if (is_array($fonte)) {
                return trim((string) ($fonte[$campo] ?? ''));
            }

            return '';
        };
        $soDigitos = static function ($valor) {
            return preg_replace('/\D/', '', (string) $valor);
        };

        $itens = [];

        $cnpj = $soDigitos($campo($emitente, 'cnpj'));
        $itens[] = [
            'ok' => strlen($cnpj) === 14,
            'titulo' => 'CNPJ',
            'detalhe' => strlen($cnpj) === 14 ? 'Informado.' : 'Obrigatório para qualquer nota fiscal.',
            'acao' => null,
        ];

        $ie = $campo($emitente, 'ie');
        $itens[] = [
            'ok' => $soDigitos($ie) !== '' || strtoupper($ie) === 'ISENTO',
            'titulo' => 'Inscrição estadual',
            'detalhe' => $soDigitos($ie) !== '' ? 'Informada.' : 'Obrigatória na NF-e. Use ISENTO para quem não é contribuinte.',
            'acao' => null,
        ];

        $enderecoOk = $campo($emitente, 'rua') !== '' && $campo($emitente, 'cidade') !== '' && $soDigitos($campo($emitente, 'cep')) !== '';
        $itens[] = [
            'ok' => $enderecoOk,
            'titulo' => 'Endereço completo',
            'detalhe' => $enderecoOk ? 'Logradouro, cidade e CEP preenchidos.' : 'Faltam logradouro, cidade ou CEP. A SEFAZ rejeita a nota sem eles.',
            'acao' => null,
        ];

        $codigoMunicipio = $campo($configFiscal, 'codigo_municipio');
        $itens[] = [
            'ok' => strlen($soDigitos($codigoMunicipio)) === 7,
            'titulo' => 'Código IBGE do município',
            'detalhe' => strlen($soDigitos($codigoMunicipio)) === 7 ? 'Configurado.' : 'Fica em Notas Fiscais > Configurações, não aqui.',
            'acao' => 'nfe/configuracoes',
        ];

        $itens[] = [
            'ok' => (bool) $certificadoPresente,
            'titulo' => 'Certificado digital A1',
            'detalhe' => $certificadoPresente ? 'Carregado.' : 'Sem o certificado a nota não é transmitida. Envie o .pfx em Notas Fiscais > Configurações.',
            'acao' => 'nfe/configuracoes',
        ];

        $im = $campo($emitente, 'inscricao_municipal');
        $itens[] = [
            'ok' => (bool) $configFiscal && $im !== '' ? true : ($configFiscal !== null),
            'titulo' => 'Inscrição municipal (NFS-e)',
            'detalhe' => $im !== ''
                ? 'Informada aqui; passa a valer na próxima NFS-e emitida.'
                : 'Opcional na NFS-e, mas exigida por muitas prefeituras. Pode ser informada aqui ou em Configurações fiscais.',
            'acao' => null,
        ];

        return $itens;
    }
}

if (! function_exists('emitente_checklist_resumo')) {
    /**
     * "4 de 6 itens prontos" para o selo do topo da tela.
     *
     * @param array<int, array{ok: bool}> $itens
     */
    function emitente_checklist_resumo(array $itens)
    {
        $prontos = 0;
        foreach ($itens as $item) {
            if (! empty($item['ok'])) {
                $prontos++;
            }
        }

        return ['prontos' => $prontos, 'total' => count($itens)];
    }
}

if (! function_exists('emitente_logo_url')) {
    /**
     * URL da logo cadastrada no emitente, usada como logo principal do sistema.
     *
     * Devolve null quando nao ha logo utilizavel (campo vazio, tabela ausente ou
     * arquivo inexistente em assets/uploads); nesse caso a tela mantem a logo
     * padrao do Map-OS. A consulta roda uma vez por requisicao.
     *
     * @return string|null
     */
    function emitente_logo_url()
    {
        static $resolvido = false;
        static $url = null;

        if ($resolvido) {
            return $url;
        }
        $resolvido = true;

        try {
            $CI = function_exists('get_instance') ? get_instance() : null;
            if (! is_object($CI) || ! isset($CI->db) || ! $CI->db->table_exists('emitente')) {
                return null;
            }

            $consulta = $CI->db->select('url_logo')->limit(1)->get('emitente');
            if (! $consulta) {
                return null;
            }

            $logo = trim((string) ($consulta->row()->url_logo ?? ''));
            if ($logo === '' || ! is_file(convertUrlToUploadsPath($logo))) {
                return null;
            }

            $url = $logo;
        } catch (\Throwable $e) {
            $url = null;
        }

        return $url;
    }
}
