<?php

namespace Libraries\Fiscal;

use Exception;
use NFePHP\Common\Certificate;
use NFePHP\DA\NFe\Danfe;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use stdClass;

/**
 * Emissão de NF-e (modelo 55) para empresas do Simples Nacional (CRT=1),
 * a partir das Vendas do Mapos, usando nfephp-org/sped-nfe.
 */
class NfeService
{
    private object $config;      // linha de configuracoes_nfe
    private object $emitente;    // linha da tabela emitente
    private ?Certificate $certificado = null;
    private ?Tools $tools = null; // carregado sob demanda (assinar/transmitir)

    private const CODIGOS_UF = [
        'AC' => 12, 'AL' => 27, 'AP' => 16, 'AM' => 13, 'BA' => 29, 'CE' => 23,
        'DF' => 53, 'ES' => 32, 'GO' => 52, 'MA' => 21, 'MT' => 51, 'MS' => 50,
        'MG' => 31, 'PA' => 15, 'PB' => 25, 'PR' => 41, 'PE' => 26, 'PI' => 22,
        'RJ' => 33, 'RN' => 24, 'RS' => 43, 'RO' => 11, 'RR' => 14, 'SC' => 42,
        'SP' => 35, 'SE' => 28, 'TO' => 17,
    ];

    public function __construct(object $config, object $emitente)
    {
        $this->config = $config;
        $this->emitente = $emitente;
    }

    /**
     * Inicializa (uma vez) o Tools da NFePHP com o certificado.
     * Só é necessário para assinar/transmitir — a montagem do XML (rascunho) não usa.
     */
    private function tools(): Tools
    {
        if ($this->tools === null) {
            $this->certificado = CertificadoHelper::carregar($this->config->certificado_path, $this->config->senha_certificado);

            $toolsConfig = json_encode([
                'atualizacao' => date('Y-m-d H:i:s'),
                'tpAmb' => (int) $this->config->ambiente,
                'razaosocial' => $this->emitente->nome,
                'cnpj' => $this->somenteNumeros($this->emitente->cnpj),
                'siglaUF' => $this->siglaUf(),
                'schemes' => 'PL_009_V4',
                'versao' => '4.00',
            ]);

            $this->tools = new Tools($toolsConfig, $this->certificado);
            $this->tools->model('55');
        }

        return $this->tools;
    }

    /**
     * Monta o objeto Make da NF-e (sem assinar/transmitir).
     * Reutilizado pela emissão e pela prévia (rascunho). Lança exceção se os dados forem inválidos.
     * $itens: itens_de_vendas + produtos (result do Vendas_model::getProdutos)
     */
    private function construirMake(object $venda, array $itens, int $numero, array $opcoes = []): Make
    {
        $this->validarEmitente();
        if (empty($itens)) {
            throw new Exception('A venda não possui produtos para emissão da NF-e.');
        }

        $make = new Make();

        $std = new stdClass();
        $std->versao = '4.00';
        $make->taginfNFe($std);

        $ufEmit = $this->siglaUf();
        $ufDest = strtoupper(trim($venda->estado ?? ''));
        $interestadual = ($ufDest !== '' && $ufDest !== $ufEmit);

        // ide
        $std = new stdClass();
        $std->cUF = self::CODIGOS_UF[$ufEmit];
        $std->cNF = str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT);
        $std->natOp = 'VENDA DE MERCADORIA';
        $std->mod = 55;
        $std->serie = (int) $this->config->serie_nfe;
        $std->nNF = $numero;
        $std->dhEmi = date('Y-m-d\TH:i:sP');
        $std->tpNF = 1;                                   // saída
        $std->idDest = $interestadual ? 2 : 1;
        $std->cMunFG = (int) $this->config->codigo_municipio;
        $std->tpImp = 1;                                  // DANFE retrato
        $std->tpEmis = 1;                                 // emissão normal
        $std->cDV = 0;                                    // recalculado pela lib
        $std->tpAmb = (int) $this->config->ambiente;
        $std->finNFe = 1;                                 // normal
        $std->indFinal = 1;                               // consumidor final
        $std->indPres = 1;                                // presencial
        $std->procEmi = 0;
        $std->verProc = 'MapOS_Fiscal_1.0';
        $make->tagide($std);

        // emitente (CRT=1: Simples Nacional)
        $std = new stdClass();
        $std->xNome = $this->emitente->nome;
        $std->CNPJ = $this->somenteNumeros($this->emitente->cnpj);
        $std->IE = $this->somenteNumeros($this->emitente->ie);
        $std->CRT = 1;
        $make->tagemit($std);

        $std = new stdClass();
        $std->xLgr = $this->emitente->rua;
        $std->nro = $this->emitente->numero ?: 'S/N';
        $std->xBairro = $this->emitente->bairro;
        $std->cMun = (int) $this->config->codigo_municipio;
        $std->xMun = $this->emitente->cidade;
        $std->UF = $ufEmit;
        $std->CEP = $this->somenteNumeros($this->emitente->cep);
        $std->cPais = 1058;
        $std->xPais = 'BRASIL';
        $make->tagenderEmit($std);

        // destinatário
        $documento = $this->somenteNumeros($venda->documento);
        if ($documento === '') {
            throw new Exception('O cliente da venda não possui CPF/CNPJ cadastrado.');
        }
        $std = new stdClass();
        // Em homologação a SEFAZ exige esta razão social fixa no destinatário
        $std->xNome = ((int) $this->config->ambiente === 2)
            ? 'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL'
            : $venda->nomeCliente;
        if (strlen($documento) === 14) {
            $std->CNPJ = $documento;
            $std->indIEDest = !empty($venda->ie) ? 1 : 9;
            if (!empty($venda->ie)) {
                $std->IE = $this->somenteNumeros($venda->ie);
            }
        } else {
            $std->CPF = $documento;
            $std->indIEDest = 9; // não contribuinte
        }
        $make->tagdest($std);

        // O cadastro de clientes não guarda o código IBGE do município; o endereço
        // do destinatário só é incluído quando o cliente é da mesma cidade do
        // emitente (aí o cMun da configuração vale para os dois). enderDest é
        // opcional no schema da NF-e.
        $mesmaCidade = !empty($venda->cidade)
            && mb_strtoupper(trim($venda->cidade)) === mb_strtoupper(trim($this->emitente->cidade ?? ''));
        if (!empty($venda->rua) && $mesmaCidade) {
            $std = new stdClass();
            $std->xLgr = $venda->rua;
            $std->nro = $venda->numero ?: 'S/N';
            $std->xBairro = $venda->bairro ?: 'Centro';
            $std->cMun = (int) $this->config->codigo_municipio;
            $std->xMun = $venda->cidade;
            $std->UF = $ufDest ?: $ufEmit;
            $std->CEP = $this->somenteNumeros($venda->cep);
            $std->cPais = 1058;
            $std->xPais = 'BRASIL';
            $make->tagenderDest($std);
        }

        // itens
        $nItem = 0;
        $totalProdutos = 0.0;
        foreach ($itens as $item) {
            $nItem++;
            $ncm = $this->somenteNumeros($item->ncm ?? '');
            if (strlen($ncm) !== 8) {
                throw new Exception("O produto \"{$item->descricao}\" não possui NCM válido (8 dígitos). Cadastre o NCM em Produtos antes de emitir.");
            }
            $cfop = $this->somenteNumeros($item->cfop ?? '') ?: $this->config->cfop_padrao;
            if ($interestadual && $cfop[0] === '5') {
                $cfop = '6' . substr($cfop, 1); // converte CFOP interno para interestadual
            }

            $quantidade = (float) $item->quantidade;
            $valorUnitario = (float) $item->preco;
            $subTotal = round($quantidade * $valorUnitario, 2);
            $totalProdutos += $subTotal;

            $std = new stdClass();
            $std->item = $nItem;
            $std->cProd = (string) $item->produtos_id;
            $std->cEAN = 'SEM GTIN';
            $std->xProd = ((int) $this->config->ambiente === 2 && $nItem === 1)
                ? 'NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL'
                : mb_substr($item->descricao, 0, 120);
            $std->NCM = $ncm;
            if (!empty($item->cest)) {
                $std->CEST = $this->somenteNumeros($item->cest);
            }
            $std->CFOP = $cfop;
            $std->uCom = $item->unidade ?: 'UN';
            $std->qCom = number_format($quantidade, 4, '.', '');
            $std->vUnCom = number_format($valorUnitario, 10, '.', '');
            $std->vProd = number_format($subTotal, 2, '.', '');
            $std->cEANTrib = 'SEM GTIN';
            $std->uTrib = $item->unidade ?: 'UN';
            $std->qTrib = number_format($quantidade, 4, '.', '');
            $std->vUnTrib = number_format($valorUnitario, 10, '.', '');
            $std->indTot = 1;
            $make->tagprod($std);

            $std = new stdClass();
            $std->item = $nItem;
            $std->vTotTrib = 0.00;
            $make->tagimposto($std);

            // ICMS Simples Nacional
            $std = new stdClass();
            $std->item = $nItem;
            $std->orig = 0;
            $std->CSOSN = $this->config->csosn_padrao;
            $make->tagICMSSN($std);

            // PIS/COFINS - outras operações (sem destaque no Simples Nacional)
            $std = new stdClass();
            $std->item = $nItem;
            $std->CST = '49';
            $std->vBC = 0.00;
            $std->pPIS = 0.0000;
            $std->vPIS = 0.00;
            $make->tagPIS($std);

            $std = new stdClass();
            $std->item = $nItem;
            $std->CST = '49';
            $std->vBC = 0.00;
            $std->pCOFINS = 0.0000;
            $std->vCOFINS = 0.00;
            $make->tagCOFINS($std);
        }

        // desconto aplicado sobre o total (rateado pela lib no ICMSTot)
        $desconto = (float) ($venda->desconto ?? 0);

        // totais - a lib soma os itens automaticamente quando os campos vêm zerados
        $std = new stdClass();
        $std->vBC = 0.00;
        $std->vICMS = 0.00;
        $std->vICMSDeson = 0.00;
        $std->vBCST = 0.00;
        $std->vST = 0.00;
        $std->vProd = number_format($totalProdutos, 2, '.', '');
        $std->vFrete = 0.00;
        $std->vSeg = 0.00;
        $std->vDesc = number_format($desconto, 2, '.', '');
        $std->vII = 0.00;
        $std->vIPI = 0.00;
        $std->vIPIDevol = 0.00;
        $std->vPIS = 0.00;
        $std->vCOFINS = 0.00;
        $std->vOutro = 0.00;
        $std->vNF = number_format($totalProdutos - $desconto, 2, '.', '');
        $std->vTotTrib = 0.00;
        $make->tagICMSTot($std);

        // transporte - sem frete
        $std = new stdClass();
        $std->modFrete = 9;
        $make->tagtransp($std);

        // pagamento
        $std = new stdClass();
        $make->tagpag($std);

        $std = new stdClass();
        $std->tPag = '01'; // dinheiro/à vista (Simples: sem detalhamento de cobrança)
        $std->vPag = number_format($totalProdutos - $desconto, 2, '.', '');
        $make->tagdetPag($std);

        // informações adicionais exigidas do Simples Nacional.
        // A origem pode ser uma Venda (idVendas) ou uma Ordem de Serviço (idOs).
        if (isset($venda->idVendas)) {
            $refDoc = 'Venda nr. ' . $venda->idVendas;
        } elseif (isset($venda->idOs)) {
            $refDoc = 'Ordem de Servico nr. ' . $venda->idOs;
        } else {
            $refDoc = '';
        }
        $infComplementar = trim((string) ($opcoes['info_complementar'] ?? ''));
        $std = new stdClass();
        $std->infCpl = 'DOCUMENTO EMITIDO POR ME OU EPP OPTANTE PELO SIMPLES NACIONAL. '
            . 'NAO GERA DIREITO A CREDITO FISCAL DE IPI.' . ($refDoc !== '' ? ' ' . $refDoc . '.' : '')
            . ($infComplementar !== '' ? ' ' . $infComplementar : '');
        $make->taginfAdic($std);

        if (!$make->monta()) {
            $erros = implode('; ', array_map(fn ($e) => is_string($e) ? $e : json_encode($e), $make->getErrors()));
            throw new Exception('Erros na montagem do XML da NF-e: ' . $erros);
        }

        return $make;
    }

    /**
     * Monta o XML de rascunho (sem assinar/transmitir) — usado na prévia do DANFE.
     */
    public function montarXmlRascunho(object $venda, array $itens, int $numero, array $opcoes = []): string
    {
        return $this->construirMake($venda, $itens, $numero, $opcoes)->getXML();
    }

    /**
     * Monta, assina e transmite a NF-e.
     * Retorna: ['sucesso', 'chave', 'protocolo', 'cstat', 'motivo', 'xml']
     */
    public function emitir(object $venda, array $itens, int $numero, array $opcoes = []): array
    {
        $make = $this->construirMake($venda, $itens, $numero, $opcoes);

        $xml = $make->getXML();
        $chave = $make->getChave();

        $xmlAssinado = $this->tools()->signNFe($xml);

        // envio síncrono
        $resposta = $this->tools()->sefazEnviaLote([$xmlAssinado], (string) time(), 1);

        $st = new Standardize();
        $retorno = $st->toStd($resposta);

        // resposta síncrona traz protNFe direto
        $prot = $retorno->protNFe ?? null;
        if ($prot === null && isset($retorno->infRec)) {
            throw new Exception('SEFAZ retornou processamento assíncrono inesperado (cStat ' . $retorno->cStat . ' - ' . $retorno->xMotivo . ')');
        }
        if ($prot === null) {
            throw new Exception('Retorno da SEFAZ sem protocolo: cStat ' . ($retorno->cStat ?? '?') . ' - ' . ($retorno->xMotivo ?? 'desconhecido'));
        }

        $cStat = (string) $prot->infProt->cStat;
        $xMotivo = (string) $prot->infProt->xMotivo;

        if ($cStat !== '100') {
            return [
                'sucesso' => false,
                'chave' => $chave,
                'protocolo' => null,
                'cstat' => $cStat,
                'motivo' => $xMotivo,
                'xml' => $xmlAssinado,
            ];
        }

        $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $resposta);

        return [
            'sucesso' => true,
            'chave' => $chave,
            'protocolo' => (string) $prot->infProt->nProt,
            'cstat' => $cStat,
            'motivo' => $xMotivo,
            'xml' => $xmlProtocolado,
        ];
    }

    /**
     * Consulta o status do serviço da SEFAZ autorizadora (UF do emitente).
     * É o teste real de ponta a ponta: assina, faz TLS com o certificado e
     * recebe resposta da SEFAZ. Retorna ['cstat' => ..., 'motivo' => ...].
     */
    public function statusSefaz(): array
    {
        $resposta = $this->tools()->sefazStatus($this->siglaUf(), (int) $this->config->ambiente);
        $st = new Standardize();
        $r = $st->toStd($resposta);

        return [
            'cstat' => (string) ($r->cStat ?? ''),
            'motivo' => (string) ($r->xMotivo ?? ''),
        ];
    }

    /**
     * Dados do titular do certificado carregado (para exibir no teste).
     */
    public function dadosCertificado(): array
    {
        $cert = CertificadoHelper::carregar($this->config->certificado_path, $this->config->senha_certificado);

        return [
            'titular' => method_exists($cert, 'getCompanyName') ? (string) $cert->getCompanyName() : '',
            'validade' => $cert->getValidTo()->format('d/m/Y'),
            'valido_de' => $cert->getValidFrom()->format('d/m/Y'),
        ];
    }

    /**
     * Cancela uma NF-e autorizada (prazo legal de 24h).
     */
    public function cancelar(string $chave, string $protocolo, string $justificativa): array
    {
        if (mb_strlen($justificativa) < 15) {
            throw new Exception('A justificativa do cancelamento deve ter no mínimo 15 caracteres.');
        }
        $resposta = $this->tools()->sefazCancela($chave, $justificativa, $protocolo);

        $st = new Standardize();
        $retorno = $st->toStd($resposta);

        $infEvento = $retorno->retEvento->infEvento ?? null;
        $cStat = (string) ($infEvento->cStat ?? $retorno->cStat ?? '');
        $xMotivo = (string) ($infEvento->xMotivo ?? $retorno->xMotivo ?? '');

        if (!in_array($cStat, ['101', '135', '155'])) {
            return ['sucesso' => false, 'cstat' => $cStat, 'motivo' => $xMotivo, 'xml' => null];
        }

        $xmlEvento = Complements::toAuthorize($this->tools()->lastRequest, $resposta);

        return ['sucesso' => true, 'cstat' => $cStat, 'motivo' => $xMotivo, 'xml' => $xmlEvento];
    }

    /**
     * Gera o PDF do DANFE a partir do XML protocolado.
     */
    public function danfe(string $xmlProtocolado, ?string $logoPath = null): string
    {
        $danfe = new Danfe($xmlProtocolado);
        if ($logoPath !== null && file_exists($logoPath)) {
            $logo = 'data://text/plain;base64,' . base64_encode(file_get_contents($logoPath));
            return $danfe->render($logo);
        }

        return $danfe->render();
    }

    private function validarEmitente(): void
    {
        $faltando = [];
        if (empty($this->somenteNumeros($this->emitente->cnpj))) {
            $faltando[] = 'CNPJ';
        }
        if (empty($this->somenteNumeros($this->emitente->ie))) {
            $faltando[] = 'Inscrição Estadual';
        }
        if (empty($this->config->codigo_municipio)) {
            $faltando[] = 'Código IBGE do município (Configurações Fiscais)';
        }
        if (empty($this->emitente->rua) || empty($this->emitente->cidade) || empty($this->emitente->cep)) {
            $faltando[] = 'Endereço completo do emitente';
        }
        if (!empty($faltando)) {
            throw new Exception('Dados do emitente incompletos para emissão: ' . implode(', ', $faltando));
        }
    }

    private function siglaUf(): string
    {
        $uf = strtoupper(trim($this->emitente->uf ?? ''));
        if (!isset(self::CODIGOS_UF[$uf])) {
            throw new Exception("UF do emitente inválida: \"{$uf}\". Corrija em Configurações > Emitente.");
        }

        return $uf;
    }

    private function somenteNumeros(?string $valor): string
    {
        return preg_replace('/\D/', '', (string) $valor);
    }
}
