<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Helper de exibição do DANFE (NF-e) e DANFSe (NFS-e).
 *
 * Contém apenas o parsing (somente leitura) do XML autorizado para o array de
 * dados consumido pelas views nfe/danfe_print e nfe/danfse_print. Extraído dos
 * métodos privados de Nfe.php para ser reutilizado no portal do cliente
 * (Mine.php) sem duplicar código. NÃO toca em emissão/assinatura.
 */

if (! function_exists('danfe_barcode_svg')) {
    /**
     * Gera o SVG do código de barras Code128 da chave de acesso (44 díg.).
     */
    function danfe_barcode_svg($chave)
    {
        $chave = preg_replace('/\D/', '', (string) $chave);
        if (strlen($chave) !== 44 || ! class_exists(\Mpdf\Barcode\Code128::class)) {
            return '';
        }
        try {
            $bc = new \Mpdf\Barcode\Code128($chave, 'C');
            $bd = $bc->getData();
            $altura = 50;
            $x = 0.0;
            $rects = '';
            foreach ($bd['bcode'] as $bar) {
                $w = (float) $bar['w'];
                if (! empty($bar['t'])) {
                    $rects .= '<rect x="' . round($x, 3) . '" y="0" width="' . round($w, 3) . '" height="' . $altura . '"/>';
                }
                $x += $w;
            }
            if ($rects === '' || $x <= 0) {
                return '';
            }

            return '<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="' . $altura . '" '
                . 'viewBox="0 0 ' . round($x, 3) . ' ' . $altura . '" preserveAspectRatio="none" fill="#000">'
                . $rects . '</svg>';
        } catch (\Throwable $e) {
            return '';
        }
    }
}

if (! function_exists('danfe_nfe_data')) {
    /**
     * Monta o array de dados do DANFE (NF-e / produtos) a partir do XML salvo.
     *
     * @throws Exception se o XML não existir ou for inválido
     */
    function danfe_nfe_data($nota)
    {
        if (empty($nota->xml_path) || ! is_file($nota->xml_path)) {
            throw new Exception('XML da NF-e não encontrado para gerar o DANFE.');
        }
        $dom = new \DOMDocument();
        if (! $dom->loadXML(file_get_contents($nota->xml_path))) {
            throw new Exception('XML da NF-e inválido.');
        }
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('n', 'http://www.portalfiscal.inf.br/nfe');
        $g = function ($path) use ($xp) {
            $node = $xp->query($path)->item(0);

            return $node ? trim($node->nodeValue) : '';
        };

        // Itens (produtos) com dados fiscais
        $itens = [];
        foreach ($xp->query('//n:infNFe/n:det') as $det) {
            $gi = function ($rel) use ($xp, $det) {
                $node = $xp->query($rel, $det)->item(0);

                return $node ? trim($node->nodeValue) : '';
            };
            // CST/CSOSN: no Simples é o CSOSN; senão o CST do ICMS
            $cst = $gi('.//n:imposto//n:CSOSN') ?: $gi('.//n:imposto//n:CST');
            $itens[] = [
                'codigo' => $gi('.//n:prod/n:cProd'),
                'descricao' => $gi('.//n:prod/n:xProd'),
                'ncm' => $gi('.//n:prod/n:NCM'),
                'cst' => $cst,
                'cfop' => $gi('.//n:prod/n:CFOP'),
                'unidade' => $gi('.//n:prod/n:uCom'),
                'quantidade' => $gi('.//n:prod/n:qCom'),
                'vUnit' => $gi('.//n:prod/n:vUnCom'),
                'vTotal' => $gi('.//n:prod/n:vProd'),
                'vBcIcms' => $gi('.//n:imposto//n:ICMS//n:vBC'),
                'vIcms' => $gi('.//n:imposto//n:ICMS//n:vICMS'),
                'pIcms' => $gi('.//n:imposto//n:ICMS//n:pICMS'),
                'vIpi' => $gi('.//n:imposto//n:IPI//n:vIPI'),
                'pIpi' => $gi('.//n:imposto//n:IPI//n:pIPI'),
            ];
        }

        $end = function ($base) use ($g) {
            return [
                'lgr' => $g($base . '/n:xLgr'),
                'nro' => $g($base . '/n:nro'),
                'cpl' => $g($base . '/n:xCpl'),
                'bairro' => $g($base . '/n:xBairro'),
                'mun' => $g($base . '/n:xMun'),
                'uf' => $g($base . '/n:UF'),
                'cep' => $g($base . '/n:CEP'),
                'fone' => $g($base . '/n:fone'),
            ];
        };

        $tot = function ($campo) use ($g) {
            return $g('//n:total/n:ICMSTot/n:' . $campo);
        };

        $d = [
            'chave' => preg_replace('/^NFe/', '', $g('//n:infNFe/@Id')),
            'numero' => $g('//n:ide/n:nNF') ?: (string) $nota->numero,
            'serie' => $g('//n:ide/n:serie'),
            'dhEmi' => $g('//n:ide/n:dhEmi'),
            'dhSaiEnt' => $g('//n:ide/n:dhSaiEnt'),
            'natOp' => $g('//n:ide/n:natOp'),
            'tpNF' => $g('//n:ide/n:tpNF'),
            'ambiente' => $g('//n:ide/n:tpAmb') ?: (string) $nota->ambiente,
            // Emitente
            'emitNome' => $g('//n:emit/n:xNome'),
            'emitCnpj' => $g('//n:emit/n:CNPJ'),
            'emitIE' => $g('//n:emit/n:IE'),
            'emitFone' => $g('//n:emit/n:enderEmit/n:fone'),
            'emitEnd' => $end('//n:emit/n:enderEmit'),
            // Destinatário
            'destNome' => $g('//n:dest/n:xNome'),
            'destDoc' => $g('//n:dest/n:CNPJ') ?: $g('//n:dest/n:CPF'),
            'destIE' => $g('//n:dest/n:IE'),
            'destEnd' => $end('//n:dest/n:enderDest'),
            // Totais
            'vBC' => $tot('vBC'),
            'vICMS' => $tot('vICMS'),
            'vBCST' => $tot('vBCST'),
            'vST' => $tot('vST'),
            'vProd' => $tot('vProd'),
            'vFrete' => $tot('vFrete'),
            'vSeg' => $tot('vSeg'),
            'vDesc' => $tot('vDesc'),
            'vIPI' => $tot('vIPI'),
            'vPIS' => $tot('vPIS'),
            'vCOFINS' => $tot('vCOFINS'),
            'vOutro' => $tot('vOutro'),
            'vNF' => $tot('vNF'),
            'vTotTrib' => $tot('vTotTrib'),
            // Transporte
            'modFrete' => $g('//n:transp/n:modFrete'),
            'transpNome' => $g('//n:transp/n:transporta/n:xNome'),
            'transpDoc' => $g('//n:transp/n:transporta/n:CNPJ') ?: $g('//n:transp/n:transporta/n:CPF'),
            'transpIE' => $g('//n:transp/n:transporta/n:IE'),
            'transpEnd' => $g('//n:transp/n:transporta/n:xEnder'),
            'transpMun' => $g('//n:transp/n:transporta/n:xMun'),
            'transpUF' => $g('//n:transp/n:transporta/n:UF'),
            'veicPlaca' => $g('//n:transp/n:veicTransp/n:placa'),
            'veicUF' => $g('//n:transp/n:veicTransp/n:UF'),
            'veicAntt' => $g('//n:transp/n:veicTransp/n:RNTC'),
            'volQtd' => $g('//n:transp/n:vol/n:qVol'),
            'volEsp' => $g('//n:transp/n:vol/n:esp'),
            'volMarca' => $g('//n:transp/n:vol/n:marca'),
            'volNum' => $g('//n:transp/n:vol/n:nVol'),
            'volPesoB' => $g('//n:transp/n:vol/n:pesoB'),
            'volPesoL' => $g('//n:transp/n:vol/n:pesoL'),
            // ISSQN (total)
            'issInscMun' => $g('//n:emit/n:IM'),
            'issVServ' => $g('//n:total/n:ISSQNtot/n:vServ'),
            'issVBC' => $g('//n:total/n:ISSQNtot/n:vBC'),
            'issVISS' => $g('//n:total/n:ISSQNtot/n:vISS'),
            // Adicionais / protocolo
            'infCpl' => $g('//n:infAdic/n:infCpl'),
            'protocolo' => $g('//n:protNFe/n:infProt/n:nProt'),
            'dhProt' => $g('//n:protNFe/n:infProt/n:dhRecbto'),
            'xMotivo' => $g('//n:protNFe/n:infProt/n:xMotivo'),
            'itens' => $itens,
        ];

        $d['barcodeSvg'] = danfe_barcode_svg($d['chave']);

        return $d;
    }
}

if (! function_exists('danfe_nfse_data')) {
    /**
     * Monta o array de dados do DANFSe (NFS-e Nacional) a partir do XML salvo.
     *
     * @throws Exception se o XML não existir ou for inválido
     */
    function danfe_nfse_data($nota)
    {
        if (empty($nota->xml_path) || ! is_file($nota->xml_path)) {
            throw new Exception('XML da NFS-e não encontrado para gerar o DANFSe localmente.');
        }
        $dom = new \DOMDocument();
        if (! $dom->loadXML(file_get_contents($nota->xml_path))) {
            throw new Exception('XML da NFS-e inválido.');
        }
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('n', 'http://www.sped.fazenda.gov.br/nfse');
        $g = function ($path) use ($xp) {
            $node = $xp->query($path)->item(0);

            return $node ? trim($node->nodeValue) : '';
        };

        return [
            'chave' => preg_replace('/^NFS/', '', $g('//n:infNFSe/@Id')),
            'numero' => $g('//n:infNFSe/n:nNFSe') ?: (string) $nota->numero,
            'dhProc' => $g('//n:infNFSe/n:dhProc'),
            'competencia' => $g('//n:DPS//n:dCompet'),
            // tpAmb do XML é o campo correto de ambiente (1=Produção, 2=Homologação).
            // NÃO usar ambGer (1=Prefeitura, 2=Sist. Nacional) — não indica homologação.
            'ambiente' => $g('//n:DPS//n:tpAmb') ?: (string) $nota->ambiente,
            'prestNome' => $g('//n:infNFSe/n:emit/n:xNome'),
            'prestCnpj' => $g('//n:infNFSe/n:emit/n:CNPJ'),
            'prestIM' => $g('//n:infNFSe/n:emit/n:IM'),
            'prestEnd' => trim($g('//n:infNFSe/n:emit/n:enderNac/n:xLgr') . ', ' . $g('//n:infNFSe/n:emit/n:enderNac/n:nro') . ' - ' . $g('//n:infNFSe/n:emit/n:enderNac/n:xBairro')),
            'prestMun' => $g('//n:infNFSe/n:xLocEmi'),
            'tomaNome' => $g('//n:DPS//n:toma/n:xNome'),
            'tomaDoc' => $g('//n:DPS//n:toma/n:CNPJ') ?: $g('//n:DPS//n:toma/n:CPF'),
            'servDesc' => $g('//n:DPS//n:serv/n:cServ/n:xDescServ'),
            'cTribNac' => $g('//n:DPS//n:serv/n:cServ/n:cTribNac'),
            'cTribMun' => $g('//n:DPS//n:serv/n:cServ/n:cTribMun'),
            'xTribNac' => $g('//n:infNFSe/n:xTribNac'),
            'vServ' => $g('//n:DPS//n:vServPrest/n:vServ') ?: $g('//n:infNFSe/n:valores/n:vBC'),
            'vISS' => $g('//n:infNFSe/n:valores/n:vISSQN'),
            'vLiq' => $g('//n:infNFSe/n:valores/n:vLiq'),
            'pAliq' => $g('//n:infNFSe/n:valores/n:pAliqAplic'),
            // Informações adicionais presentes no XML
            'nDFSe' => $g('//n:infNFSe/n:nDFSe'),
            'nDPS' => $g('//n:DPS//n:nDPS'),
            'serieDps' => $g('//n:DPS//n:serie'),
            'dhEmi' => $g('//n:DPS//n:dhEmi'),
            'municipioIncid' => $g('//n:infNFSe/n:xLocIncid'),
            'cLocIncid' => $g('//n:infNFSe/n:cLocIncid'),
            'municipioPrest' => $g('//n:infNFSe/n:xLocPrestacao'),
            'opSimpNac' => $g('//n:DPS//n:prest/n:regTrib/n:opSimpNac'),
            'tpRet' => $g('//n:DPS//n:tribMun/n:tpRetISSQN'),
            'pTotTribSN' => $g('//n:DPS//n:totTrib/n:pTotTribSN'),
        ];
    }
}
