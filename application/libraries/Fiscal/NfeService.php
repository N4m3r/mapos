<?php

namespace Libraries\Fiscal;

use Exception;
use NFePHP\Common\Certificate;
use NFePHP\DA\NFe\Danfce;
use NFePHP\DA\NFe\Danfe;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use stdClass;

require_once APPPATH . 'helpers/tributacao_helper.php';
require_once APPPATH . 'helpers/classificacao_fiscal_helper.php';
require_once APPPATH . 'helpers/cliente_fiscal_helper.php';
require_once APPPATH . 'helpers/regime_normal_helper.php';
require_once APPPATH . 'helpers/cobranca_helper.php';

/**
 * Emissão de NF-e (modelo 55) e NFC-e (modelo 65, via comoNfce()) para empresas
 * do Simples Nacional (CRT=1), a partir das Vendas do Mapos, usando nfephp-org/sped-nfe.
 */
class NfeService
{
    private object $config;      // linha de configuracoes_nfe
    private object $emitente;    // linha da tabela emitente
    private ?Certificate $certificado = null;
    private ?Tools $tools = null; // carregado sob demanda (assinar/transmitir)
    private int $modelo = 55;

    /** Formas de pagamento do PDV -> tPag da NF-e/NFC-e. */
    public const TPAG = ['dinheiro' => '01', 'credito' => '03', 'debito' => '04', 'pix' => '17', 'outros' => '99'];

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

    /** Mesmo servico emitindo NFC-e (modelo 65): consumidor final no balcao, QR Code e DANFCE. */
    public function comoNfce(): self
    {
        $nfce = clone $this;
        $nfce->modelo = 65;
        $nfce->tools = null;

        return $nfce;
    }

    private function ehNfce(): bool
    {
        return $this->modelo === 65;
    }

    /**
     * Inicializa (uma vez) o Tools da NFePHP com o certificado.
     * Só é necessário para assinar/transmitir — a montagem do XML (rascunho) não usa.
     */
    private function tools(): Tools
    {
        if ($this->tools === null) {
            $this->certificado = CertificadoHelper::carregar($this->config->certificado_path, $this->config->senha_certificado);

            $configTools = [
                'atualizacao' => date('Y-m-d H:i:s'),
                'tpAmb' => (int) $this->config->ambiente,
                'razaosocial' => $this->emitente->nome,
                'cnpj' => $this->somenteNumeros($this->emitente->cnpj),
                'siglaUF' => $this->siglaUf(),
                'schemes' => 'PL_009_V4',
                'versao' => '4.00',
            ];
            if ($this->ehNfce()) {
                // CSC/ID do token: cadastrados na SEFAZ da UF, usados no hash do QR Code
                $configTools['CSC'] = trim((string) ($this->config->csc ?? ''));
                $configTools['CSCid'] = trim((string) ($this->config->csc_id ?? ''));
            }

            $this->tools = new NfceTools(json_encode($configTools), $this->certificado);
            $this->tools->model((string) $this->modelo);
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
        $nfce = $this->ehNfce();

        // Reforma Tributária (Fase 2): quando ligada, o Make é montado no layout
        // PL_010 (schema > 9), que habilita o grupo IBS/CBS por item e o
        // totalizador. Desligada, mantém o PL_009 (comportamento atual, sem risco).
        // Regime normal (op_simp_nac = 1, CRT 3): o grupo IBS/CBS e obrigatorio na
        // NT 2025.002 (v1.40+); sem ele a SEFAZ rejeita (1115). Por isso vai SEMPRE,
        // independente de reforma_ativa. Simples (3) e MEI (2) seguem opcionais em
        // 2026 e dependem do interruptor reforma_ativa.
        $reforma = !empty($this->config->reforma_ativa) || (int) ($this->config->op_simp_nac ?? 0) === 1;
        if ($reforma) {
            // pre-validacao local por item (classificacao propria ou padrao): pega 1023/1024/1025 sem gastar numero
            $errosIbs = fiscal_prevalidar_itens($itens, $this->config, $this->modelo, 'produto');
            if ($errosIbs) {
                throw new Exception(implode(' ', $errosIbs));
            }
        }
        $make = $reforma ? new Make('PL_010') : new Make();

        $std = new stdClass();
        $std->versao = '4.00';
        $make->taginfNFe($std);

        $ufEmit = $this->siglaUf();
        $ufDest = strtoupper(trim($venda->estado ?? ''));
        $interestadual = ! $nfce && ($ufDest !== '' && $ufDest !== $ufEmit); // NFC-e e sempre operacao interna

        // ide
        $std = new stdClass();
        $std->cUF = self::CODIGOS_UF[$ufEmit];
        $std->cNF = str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT);
        $std->natOp = 'VENDA DE MERCADORIA';
        $std->mod = $this->modelo;
        $std->serie = (int) ($nfce ? ($this->config->serie_nfce ?? 1) : $this->config->serie_nfe);
        $std->nNF = $numero;
        $std->dhEmi = date('Y-m-d\TH:i:sP');
        $std->tpNF = 1;                                   // saída
        $std->idDest = $interestadual ? 2 : 1;
        $std->cMunFG = (int) $this->config->codigo_municipio;
        $std->tpImp = $nfce ? 4 : 1;                      // 4 = DANFCE, 1 = DANFE retrato
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
        // CRT: 1 Simples, 3 regime normal. rn_crt devolve 4 para MEI, mas o leiaute da
        // NF-e so aceita CRT 1, 2 ou 3; MEI emite como Simples (CRT 1).
        $crt = rn_crt((int) ($this->config->op_simp_nac ?? 3));
        if ($crt === 4) {
            $crt = 1;
        }
        $normal = $crt === 3;
        $std->CRT = $crt;
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

        // destinatário (na NFC-e e opcional: so vai se o consumidor informou CPF/CNPJ)
        $documento = $nfce
            ? $this->somenteNumeros($opcoes['cpf'] ?? '')
            : $this->somenteNumeros($venda->documento);
        if ($documento === '' && ! $nfce) {
            throw new Exception('O cliente da venda não possui CPF/CNPJ cadastrado.');
        }
        if ($documento !== '') {
            $std = new stdClass();
            // Em homologação a SEFAZ exige esta razão social fixa no destinatário
            $std->xNome = ((int) $this->config->ambiente === 2)
                ? 'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL'
                : $venda->nomeCliente;
            if (strlen($documento) === 14) {
                $std->CNPJ = $documento;
                $std->indIEDest = cliente_ind_ie_dest($venda);
                if ($std->indIEDest === 1 && !empty($venda->ie)) {
                    $std->IE = $this->somenteNumeros($venda->ie);
                }
            } else {
                $std->CPF = $documento;
                $std->indIEDest = 9; // não contribuinte
            }
            if ($nfce) {
                $std->indIEDest = 9;
                unset($std->IE);
            }
            // Zona Franca de Manaus: inscricao SUFRAMA valida vai na tag ISUF (so informativa)
            if (! $nfce && ! empty($venda->inscricao_suframa) && cliente_suframa_valida((string) $venda->inscricao_suframa)) {
                $std->ISUF = trim((string) $venda->inscricao_suframa);
            }
            $make->tagdest($std);
        }

        // O cadastro de clientes não guarda o código IBGE do município; o endereço
        // do destinatário só é incluído quando o cliente é da mesma cidade do
        // emitente (aí o cMun da configuração vale para os dois). enderDest é
        // opcional no schema da NF-e.
        $mesmaCidade = !empty($venda->cidade)
            && mb_strtoupper(trim($venda->cidade)) === mb_strtoupper(trim($this->emitente->cidade ?? ''));
        if (! $nfce && !empty($venda->rua) && $mesmaCidade) {
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

        // Regime normal: DIFAL (destinatario nao contribuinte em outra UF) ainda nao suportado
        if ($normal && $interestadual && $documento !== '' && (strlen($documento) === 11 || cliente_ind_ie_dest($venda) === 9)) {
            throw new Exception('DIFAL ainda não suportado; consulte o contador.');
        }

        // desconto total rateado entre os itens, proporcional ao subtotal e fechando no centavo
        $subtotais = [];
        foreach ($itens as $it) {
            $subtotais[] = round((float) $it->quantidade * (float) $it->preco, 2);
        }
        $descontoTotal = max(0.0, min((float) ($venda->desconto ?? 0), array_sum($subtotais)));
        $descontosItem = cobranca_ratear_centavos($subtotais, (int) round($descontoTotal * 100));

        // itens
        $nItem = 0;
        $totDesc = 0.0;
        $totalProdutos = 0.0;
        $totBcIcms = 0.0;
        $totIcms = 0.0;
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
            $vDescItem = ($descontosItem[$nItem - 1] ?? 0) / 100;
            $baseItem = round($subTotal - $vDescItem, 2);
            $totDesc += $vDescItem;

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
            if ($vDescItem > 0) {
                $std->vDesc = number_format($vDescItem, 2, '.', '');
            }
            $std->indTot = 1;
            $make->tagprod($std);

            $std = new stdClass();
            $std->item = $nItem;
            $std->vTotTrib = 0.00;
            $make->tagimposto($std);

            if ($normal) {
                // Regime normal: ICMS normal + PIS/COFINS com aliquota (a lib soma os totais)
                $icms = rn_icms_item([
                    'icms_cst' => $item->icms_cst ?? null,
                    'icms_aliquota' => $item->icms_aliquota ?? null,
                    'vProd' => $subTotal,
                    'vDesc' => $vDescItem,
                ], $this->config, $interestadual);
                $std = new stdClass();
                $std->item = $nItem;
                $std->orig = classificacao_efetiva($item, $this->config, 'produto')['origem'];
                $std->CST = $icms['cst'];
                if ($icms['cst'] === '00') {
                    $std->modBC = 3;
                    $std->vBC = number_format($icms['vBC'], 2, '.', '');
                    $std->pICMS = number_format($icms['pICMS'], 4, '.', '');
                    $std->vICMS = number_format($icms['vICMS'], 2, '.', '');
                }
                $totBcIcms += $icms['vBC'];
                $totIcms += $icms['vICMS'];
                $make->tagICMS($std);

                $pc = rn_pis_cofins_item($baseItem, $this->config);
                $semValor = in_array($pc['cst'], ['04', '05', '06', '07', '08', '09'], true);
                $std = new stdClass();
                $std->item = $nItem;
                $std->CST = $pc['cst'];
                if (! $semValor) {
                    $std->vBC = number_format($pc['vBC'], 2, '.', '');
                    $std->pPIS = number_format($pc['pPIS'], 4, '.', '');
                    $std->vPIS = number_format($pc['vPIS'], 2, '.', '');
                }
                $make->tagPIS($std);

                $std = new stdClass();
                $std->item = $nItem;
                $std->CST = $pc['cst'];
                if (! $semValor) {
                    $std->vBC = number_format($pc['vBC'], 2, '.', '');
                    $std->pCOFINS = number_format($pc['pCOFINS'], 4, '.', '');
                    $std->vCOFINS = number_format($pc['vCOFINS'], 2, '.', '');
                }
                $make->tagCOFINS($std);
            } else {
                // ICMS Simples Nacional
                $std = new stdClass();
                $std->item = $nItem;
                $std->orig = classificacao_efetiva($item, $this->config, 'produto')['origem'];
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

            // Grupo IBS/CBS (reforma tributária) — só quando ativado nas
            // configurações. Alíquotas/CST/cClassTrib são parametrizáveis; a
            // base é o valor do item. O totalizador é montado pela lib.
            if ($reforma) {
                $cls = classificacao_efetiva($item, $this->config, 'produto');
                $pRed = (float) ($item->p_red_ibscbs ?? 0);
                $fator = 1 - max(0.0, min(100.0, $pRed)) / 100;
                $pIBSUFBase = (float) ($this->config->reforma_ibs_uf ?? 0);
                $pIBSMunBase = (float) ($this->config->reforma_ibs_mun ?? 0);
                $pCBSBase = (float) ($this->config->reforma_cbs ?? 0);
                $pIBSUF = round($pIBSUFBase * $fator, 4);
                $pIBSMun = round($pIBSMunBase * $fator, 4);
                $pCBS = round($pCBSBase * $fator, 4);
                $vIBSUF = round($baseItem * $pIBSUF / 100, 2);
                $vIBSMun = round($baseItem * $pIBSMun / 100, 2);
                $vCBS = round($baseItem * $pCBS / 100, 2);

                $std = new stdClass();
                $std->item = $nItem;
                $std->CST = $cls['cst'] ?: '000';
                $std->cClassTrib = $cls['cclasstrib'] ?: '000001';
                $std->vBC = number_format($baseItem, 2, '.', '');
                $std->gIBSUF_pIBSUF = $pIBSUF;
                $std->gIBSUF_vIBSUF = number_format($vIBSUF, 2, '.', '');
                $std->gIBSMun_pIBSMun = $pIBSMun;
                $std->gIBSMun_vIBSMun = number_format($vIBSMun, 2, '.', '');
                $std->vIBS = number_format($vIBSUF + $vIBSMun, 2, '.', '');
                $std->gCBS_pCBS = $pCBS;
                $std->gCBS_vCBS = number_format($vCBS, 2, '.', '');
                if ($pRed > 0) {
                    $std->gIBSUF_pRedAliq = $pRed;
                    $std->gIBSUF_pAliqEfet = $pIBSUF;
                    $std->gIBSMun_pRedAliq = $pRed;
                    $std->gIBSMun_pAliqEfet = $pIBSMun;
                    $std->gCBS_pRedAliq = $pRed;
                    $std->gCBS_pAliqEfet = $pCBS;
                }
                $make->tagIBSCBS($std);
            }
        }

        // desconto ja rateado nos itens (vDesc em det/prod); total = soma dos itens
        $desconto = round($totDesc, 2);

        // totais - a lib soma os itens automaticamente quando os campos vêm zerados
        $std = new stdClass();
        // a lib NAO soma vBC/vICMS dos itens: no regime normal vao acumulados aqui (PIS/COFINS ela soma)
        $std->vBC = number_format($totBcIcms, 2, '.', '');
        $std->vICMS = number_format($totIcms, 2, '.', '');
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

        $pagamentos = $opcoes['pagamentos'] ?? [];
        if ($nfce && $pagamentos) {
            // Formas reais do PDV; o troco vai em vTroco
            foreach ($pagamentos as $p) {
                $std = new stdClass();
                $std->tPag = self::TPAG[$p['forma']] ?? '99';
                if ($std->tPag === '99') {
                    $std->xPag = 'Outros';
                }
                $std->vPag = number_format((float) $p['valor'], 2, '.', '');
                if (in_array($std->tPag, ['03', '04', '17'], true)) {
                    $std->tpIntegra = 2; // pagamento nao integrado ao sistema (maquininha/PIX a parte)
                }
                $make->tagdetPag($std);
            }
            $troco = (float) ($opcoes['troco'] ?? 0);
            if ($troco > 0) {
                $std = new stdClass();
                $std->vTroco = number_format($troco, 2, '.', '');
                $make->tagpag($std);
            }
        } else {
            $std = new stdClass();
            $std->tPag = '01'; // dinheiro/à vista (Simples: sem detalhamento de cobrança)
            $std->vPag = number_format($totalProdutos - $desconto, 2, '.', '');
            $make->tagdetPag($std);
        }

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
        $std->infCpl = ($normal ? '' : 'DOCUMENTO EMITIDO POR ME OU EPP OPTANTE PELO SIMPLES NACIONAL. '
            . 'NAO GERA DIREITO A CREDITO FISCAL DE IPI.') . ($refDoc !== '' ? ' ' . $refDoc . '.' : '')
            . ($infComplementar !== '' ? ' ' . $infComplementar : '');
        $make->taginfAdic($std);

        // Responsável técnico — obrigatório em várias SEFAZ (ex.: AM, rejeição 972).
        // Usa os dados do próprio emitente como responsável pelo sistema emissor.
        $std = new stdClass();
        $std->CNPJ = $this->somenteNumeros($this->emitente->cnpj);
        $std->xContato = mb_substr(trim((string) $this->emitente->nome), 0, 60) ?: 'Responsavel Tecnico';
        $std->email = trim((string) $this->emitente->email) ?: 'contato@exemplo.com.br';
        $std->fone = $this->somenteNumeros($this->emitente->telefone);
        $make->taginfRespTec($std);

        // O método de montagem mudou de nome entre versões do sped-nfe
        // (monta() nas mais novas, montaNFe() nas anteriores).
        $montou = method_exists($make, 'monta') ? $make->monta() : $make->montaNFe();
        if (!$montou) {
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

        // Assinatura RSA-SHA1 pelo assinador próprio (Signer), que contorna
        // servidores que bloqueiam SHA1 em openssl_sign. Gera assinatura padrão.
        $material = CertificadoHelper::lerChaveECert($this->config->certificado_path, $this->config->senha_certificado);
        $xmlAssinado = Signer::sign($xml, 'infNFe', $material['pkey'], $material['cert']);
        if ($this->ehNfce()) {
            $xmlAssinado = $this->tools()->inserirQRCode($xmlAssinado);
        }

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
        $danfe = $this->ehNfce() ? new Danfce($xmlProtocolado) : new Danfe($xmlProtocolado);
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
