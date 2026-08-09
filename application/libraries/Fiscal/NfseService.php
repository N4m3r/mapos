<?php

namespace Libraries\Fiscal;

use Exception;
use Hadder\NfseNacional\Dps;
use Hadder\NfseNacional\Tools;
use stdClass;

/**
 * Emissão de NFS-e pelo Padrão Nacional (nfse.gov.br / Sefin Nacional),
 * a partir das Ordens de Serviço do Mapos, usando hadder/nfse-nacional.
 */
class NfseService
{
    private object $config;   // linha de configuracoes_nfe
    private object $emitente; // linha da tabela emitente
    private NfseTools $tools;

    private const VER_APLIC = 'MapOS_Fiscal_1.0';

    public function __construct(object $config, object $emitente)
    {
        $this->config = $config;
        $this->emitente = $emitente;

        if (empty($config->codigo_municipio)) {
            throw new Exception('Código IBGE do município não configurado. Acesse Notas Fiscais > Configurações.');
        }

        $certificado = CertificadoHelper::carregar($config->certificado_path, $config->senha_certificado);

        $toolsConfig = new stdClass();
        $toolsConfig->tpamb = (int) $config->ambiente;
        $toolsConfig->prefeitura = (string) $config->codigo_municipio;

        // NfseTools = Tools do hadder com o sign() sobrescrito pelo assinador
        // próprio (RSA-SHA1 via openssl_private_encrypt), contornando o bloqueio
        // de SHA1 do servidor. O material do certificado vem do próprio .pfx.
        $this->tools = new NfseTools(json_encode($toolsConfig), $certificado);
        $material = CertificadoHelper::lerChaveECert($config->certificado_path, $config->senha_certificado);
        $this->tools->definirMaterial($material['pkey'], $material['cert']);
    }

    /**
     * Código de Tributação Nacional padrão (6 dígitos) para quando nem o serviço
     * nem o wizard informam um. Configurável em configuracoes_nfe.ctribnac_padrao;
     * default 010701 (suporte em informática).
     */
    private function tribNacPadrao(): string
    {
        $c = isset($this->config->ctribnac_padrao) ? preg_replace('/\D/', '', (string) $this->config->ctribnac_padrao) : '';

        return strlen($c) === 6 ? $c : '010701';
    }

    /**
     * Código de Tributação Municipal padrão (default 100 — validado por NFS-e
     * autorizada de Manaus com cTribNac 010701 + cTribMun 100). Configurável em
     * configuracoes_nfe.ctribmun_padrao.
     */
    private function tribMunPadrao(): string
    {
        $c = isset($this->config->ctribmun_padrao) ? preg_replace('/\D/', '', (string) $this->config->ctribmun_padrao) : '';

        return $c !== '' ? $c : '100';
    }

    /**
     * Reforma Tributária na NFS-e — DETECÇÃO DE SUPORTE.
     *
     * A biblioteca hadder/nfse-nacional 1.0.22 ainda NÃO gera o grupo IBS/CBS na
     * DPS (o bloco está comentado no Dps.php) e os schemas embarcados vão só até
     * DPS_v1.01, que não têm o grupo. Enquanto isso não muda, anexar os campos
     * geraria rejeição por schema no SEFIN Nacional.
     *
     * Esta função detecta, de forma segura, se a versão instalada já traz o
     * layout: procura o termo "IBSCBS" em algum schema DPS embarcado. Só quando
     * um schema com IBS/CBS existir é que os campos passam a ser anexados —
     * até lá a emissão sai idêntica à de hoje. Assim, ao atualizar o hadder para
     * uma versão com a reforma, a NFS-e passa a levar IBS/CBS automaticamente.
     */
    private function nfseSuportaIbsCbs(): bool
    {
        $dir = APPPATH . 'vendor/hadder/nfse-nacional/storage/schemes';
        foreach (glob($dir . '/DPS_*.xsd') ?: [] as $xsd) {
            $conteudo = @file_get_contents($xsd);
            if ($conteudo !== false && stripos($conteudo, 'IBSCBS') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reforma Tributária na NFS-e — MONTAGEM DO GRUPO IBS/CBS (provisório).
     *
     * Preenche em $std->infDPS->valores->trib->ibscbs os valores de IBS/CBS a
     * partir das alíquotas configuráveis (as mesmas da NF-e: reforma_ibs_uf,
     * reforma_ibs_mun, reforma_cbs), espelhando a estrutura do grupo da NF-e.
     *
     * IMPORTANTE: os nomes exatos dos nós da DPS de IBS/CBS devem ser conferidos
     * contra a versão do hadder que liberar o layout (NT 004 do padrão nacional).
     * Como o método só é chamado quando nfseSuportaIbsCbs() é verdadeiro (schema
     * com IBS/CBS presente), hoje ele NÃO executa — fica de scaffold pronto.
     */
    private function anexarIbsCbsDps(stdClass $std, float $total): void
    {
        $pIBSUF = (float) ($this->config->reforma_ibs_uf ?? 0);
        $pIBSMun = (float) ($this->config->reforma_ibs_mun ?? 0);
        $pCBS = (float) ($this->config->reforma_cbs ?? 0);
        $vIBSUF = round($total * $pIBSUF / 100, 2);
        $vIBSMun = round($total * $pIBSMun / 100, 2);
        $vCBS = round($total * $pCBS / 100, 2);

        $ibscbs = new stdClass();
        $ibscbs->CST = $this->config->reforma_cst ?? '000';
        $ibscbs->cClassTrib = $this->config->reforma_cclasstrib ?? '000001';

        $ibscbs->gIBSCBS = new stdClass();
        $ibscbs->gIBSCBS->vBC = number_format($total, 2, '.', '');

        $ibscbs->gIBSCBS->gIBS = new stdClass();
        $ibscbs->gIBSCBS->gIBS->gIBSUF = new stdClass();
        $ibscbs->gIBSCBS->gIBS->gIBSUF->pIBSUF = number_format($pIBSUF, 4, '.', '');
        $ibscbs->gIBSCBS->gIBS->gIBSUF->vIBSUF = number_format($vIBSUF, 2, '.', '');
        $ibscbs->gIBSCBS->gIBS->gIBSMun = new stdClass();
        $ibscbs->gIBSCBS->gIBS->gIBSMun->pIBSMun = number_format($pIBSMun, 4, '.', '');
        $ibscbs->gIBSCBS->gIBS->gIBSMun->vIBSMun = number_format($vIBSMun, 2, '.', '');
        $ibscbs->gIBSCBS->gIBS->vIBS = number_format($vIBSUF + $vIBSMun, 2, '.', '');

        $ibscbs->gIBSCBS->gCBS = new stdClass();
        $ibscbs->gIBSCBS->gCBS->pCBS = number_format($pCBS, 4, '.', '');
        $ibscbs->gIBSCBS->gCBS->vCBS = number_format($vCBS, 2, '.', '');

        // Anexa ao grupo de tributos da DPS (estrutura a confirmar na release).
        if (!isset($std->infDPS->valores->trib)) {
            $std->infDPS->valores->trib = new stdClass();
        }
        $std->infDPS->valores->trib->ibscbs = $ibscbs;
    }

    /**
     * Normaliza um texto livre para ir num campo da DPS (ex.: xDescServ).
     * Remove marcação HTML, decodifica entidades, tira caracteres de controle e
     * colapsa espaços/quebras. Evita conteúdo que possa ser reserializado de
     * forma diferente no transporte e invalidar a assinatura (E0714).
     */
    private function limparTexto(string $texto): string
    {
        $t = strip_tags($texto);
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Remove caracteres de controle (inclui CR/LF/TAB, que viram espaço abaixo).
        $t = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $t);
        // Colapsa qualquer sequência de espaços em um único espaço.
        $t = preg_replace('/\s+/u', ' ', (string) $t);

        return trim((string) $t);
    }

    /**
     * Escapa caracteres reservados de XML (& < > " ') para um campo da DPS.
     * A biblioteca monta o XML com DOMDocument::createElement($tag, $valor), que
     * interpreta o valor como marcação — um "&" cru (ex.: razão social
     * "F&F Distribuidora...") vira início de entidade e dispara
     * "unterminated entity reference". Pré-escapar produz o valor correto, pois o
     * createElement decodifica a entidade de volta no caractere original.
     */
    private function escaparXml(string $texto): string
    {
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Prepara um campo de texto curto (nome, endereço) para a DPS: limpa,
     * trunca no limite do schema e então escapa para XML. Trunca antes de
     * escapar para não cortar no meio de uma entidade (ex.: "&amp;").
     */
    private function limparCampo(string $texto, int $max): string
    {
        return $this->escaparXml(mb_substr($this->limparTexto($texto), 0, $max));
    }

    /**
     * Formata a resposta de rejeição do Sefin Nacional em:
     *   [0] mensagem legível (lista "Codigo - Descricao [ - Complemento]")
     *   [1] detalhe técnico completo (JSON pretty do retorno inteiro)
     * Assim a tela mostra o essencial e, expandido, o retorno bruto sem perder nada.
     */
    private function formatarErros($resposta): array
    {
        $detalhe = is_string($resposta)
            ? $resposta
            : json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (is_string($resposta)) {
            return [$resposta, $detalhe];
        }

        $erros = $resposta['erros'] ?? $resposta['erro'] ?? null;
        if ($erros === null) {
            // Sem chave conhecida: usa o retorno inteiro como mensagem.
            return [json_encode($resposta, JSON_UNESCAPED_UNICODE), $detalhe];
        }
        if (is_string($erros)) {
            return [$erros, $detalhe];
        }

        $linhas = [];
        foreach ((array) $erros as $e) {
            if (is_string($e)) {
                $linhas[] = $e;
                continue;
            }
            $e = (array) $e;
            $cod = (string) ($e['Codigo'] ?? $e['codigo'] ?? $e['code'] ?? '');
            $desc = (string) ($e['Descricao'] ?? $e['descricao'] ?? $e['Mensagem'] ?? $e['mensagem'] ?? $e['message'] ?? '');
            $compl = (string) ($e['Complemento'] ?? $e['complemento'] ?? $e['Correcao'] ?? $e['correcao'] ?? $e['Sugestao'] ?? '');
            $linha = trim(($cod !== '' ? '[' . $cod . '] ' : '') . $desc);
            if ($compl !== '') {
                $linha .= ' — ' . $compl;
            }
            if ($linha === '') {
                $linha = json_encode($e, JSON_UNESCAPED_UNICODE);
            }
            $linhas[] = $linha;
        }

        $mensagem = $linhas === [] ? $detalhe : implode(' | ', $linhas);

        return [$mensagem, $detalhe];
    }

    /**
     * Monta e envia a DPS de uma OS finalizada.
     * $servicos: itens de servicos_os + servicos (result do Os_model::getServicos)
     * Retorna: ['sucesso', 'chave', 'numero_dps', 'motivo', 'xml']
     */
    public function emitir(object $os, array $servicos, int $numeroDps, array $opcoes = []): array
    {
        if (empty($servicos)) {
            throw new Exception('A OS não possui serviços lançados para emissão da NFS-e.');
        }

        $documento = preg_replace('/\D/', '', (string) $os->documento);
        if ($documento === '') {
            throw new Exception('O cliente da OS não possui CPF/CNPJ cadastrado.');
        }

        // códigos de tributação: pega o do primeiro serviço com código cadastrado
        $cTribNac = null;
        $cTribMunCad = '';
        $descricoes = [];
        $total = 0.0;
        foreach ($servicos as $servico) {
            $codigo = preg_replace('/\D/', '', (string) ($servico->codigo_servico_municipio ?? ''));
            if ($cTribNac === null && strlen($codigo) === 6) {
                $cTribNac = $codigo;
                // usa o código municipal do MESMO serviço que forneceu o nacional
                $cTribMunCad = preg_replace('/\D/', '', (string) ($servico->codigo_tributacao_municipal ?? ''));
            }
            $quantidade = (float) ($servico->quantidade ?? 1) ?: 1;
            $preco = (float) ($servico->preco ?? $servico->precoVenda);
            $total += $quantidade * $preco;
            $descricoes[] = trim($servico->nome . (empty($servico->descricao) ? '' : ' - ' . $servico->descricao))
                . ' (' . number_format($quantidade, 0) . 'x)';
        }
        // Override do código de tributação vindo do wizard de emissão
        $cTribNacOpcao = preg_replace('/\D/', '', (string) ($opcoes['ctribnac'] ?? ''));
        if (strlen($cTribNacOpcao) === 6) {
            $cTribNac = $cTribNacOpcao;
        }
        if ($cTribNac === null) {
            // Sem código no serviço nem no wizard (ex.: emissão automática com
            // boleto): usa o padrão configurável (default 010701 = suporte em TI).
            $cTribNac = $this->tribNacPadrao();
        }
        $total = round($total, 2);

        $std = new stdClass();
        $std->infDPS = new stdClass();
        $std->infDPS->tpAmb = (int) $this->config->ambiente;
        $std->infDPS->dhEmi = date('Y-m-d\TH:i:sP');
        $std->infDPS->verAplic = self::VER_APLIC;
        $std->infDPS->serie = (string) $this->config->serie_dps;
        $std->infDPS->nDPS = (string) $numeroDps;
        $std->infDPS->dCompet = date('Y-m-d');
        $std->infDPS->tpEmit = 1; // emitida pelo prestador
        $std->infDPS->cLocEmi = (string) $this->config->codigo_municipio;

        // Substituição de NFS-e: quando informado, esta DPS substitui a nota
        // cuja chave está em chSubstda (grupo subst). cMotivo/xMotivo obrigatórios.
        $subst = $opcoes['subst'] ?? null;
        if (is_array($subst) && !empty($subst['chSubstda']) && !empty($subst['cMotivo'])) {
            $std->infDPS->subst = new stdClass();
            $std->infDPS->subst->chSubstda = preg_replace('/\D/', '', (string) $subst['chSubstda']);
            $std->infDPS->subst->cMotivo = str_pad((string) $subst['cMotivo'], 2, '0', STR_PAD_LEFT);
            if (!empty($subst['xMotivo'])) {
                $std->infDPS->subst->xMotivo = $this->limparCampo((string) $subst['xMotivo'], 255);
            }
        }

        // prestador
        $std->infDPS->prest = new stdClass();
        $std->infDPS->prest->CNPJ = preg_replace('/\D/', '', (string) $this->emitente->cnpj);
        $fone = preg_replace('/\D/', '', (string) $this->emitente->telefone);
        if ($fone !== '') {
            $std->infDPS->prest->fone = $fone;
        }
        if (!empty($this->config->inscricao_municipal)) {
            $std->infDPS->prest->IM = preg_replace('/\D/', '', (string) $this->config->inscricao_municipal);
        }
        $std->infDPS->prest->regTrib = new stdClass();
        $std->infDPS->prest->regTrib->opSimpNac = (int) $this->config->op_simp_nac;
        // Regime de apuração do Simples Nacional — exigido quando optante (MEI/ME/EPP).
        // 1 = regra geral (mesmo valor da NFS-e autorizada da JJ em Manaus).
        if (in_array((int) $this->config->op_simp_nac, [2, 3], true)) {
            $std->infDPS->prest->regTrib->regApTribSN = 1;
        }
        $std->infDPS->prest->regTrib->regEspTrib = (int) $this->config->reg_esp_trib;

        // tomador
        $std->infDPS->toma = new stdClass();
        if (strlen($documento) === 14) {
            $std->infDPS->toma->CNPJ = $documento;
        } else {
            $std->infDPS->toma->CPF = $documento;
        }
        $std->infDPS->toma->xNome = $this->limparCampo((string) $os->nomeCliente, 150);

        // Endereço do tomador — obrigatório quando o ISS é retido (E0237) e
        // recomendado sempre. O cadastro de cliente não guarda o código IBGE,
        // então assume-se o município do emitente (caso comum: tomador local).
        $tomaRua = trim((string) ($os->rua ?? ''));
        $tomaCep = preg_replace('/\D/', '', (string) ($os->cep ?? ''));
        if ($tomaRua !== '' && strlen($tomaCep) === 8) {
            $std->infDPS->toma->end = new stdClass();
            $std->infDPS->toma->end->xLgr = $this->limparCampo($tomaRua, 255);
            $std->infDPS->toma->end->nro = $this->limparCampo(trim((string) ($os->numero ?? '')) ?: 'S/N', 60);
            if (!empty($os->bairro)) {
                $std->infDPS->toma->end->xBairro = $this->limparCampo((string) $os->bairro, 60);
            }
            $std->infDPS->toma->end->endNac = new stdClass();
            $std->infDPS->toma->end->endNac->cMun = (string) $this->config->codigo_municipio;
            $std->infDPS->toma->end->endNac->CEP = $tomaCep;
        }

        // serviço
        $std->infDPS->serv = new stdClass();
        $std->infDPS->serv->locPrest = new stdClass();
        $std->infDPS->serv->locPrest->cLocPrestacao = (string) $this->config->codigo_municipio;
        $std->infDPS->serv->cServ = new stdClass();
        $std->infDPS->serv->cServ->cTribNac = $cTribNac;
        // Código de Tributação Municipal (3 dígitos), exigido por alguns
        // municípios (ex.: Manaus) junto do nacional — resolve E0312.
        // Prioriza o informado no wizard; senão usa o cadastrado no serviço.
        $cTribMun = preg_replace('/\D/', '', (string) ($opcoes['ctribmun'] ?? ''));
        if ($cTribMun === '') {
            $cTribMun = $cTribMunCad;
        }
        if ($cTribMun === '') {
            // Padrão configurável (default 100) — usado na emissão automática.
            $cTribMun = $this->tribMunPadrao();
        }
        if ($cTribMun !== '') {
            $std->infDPS->serv->cServ->cTribMun = str_pad($cTribMun, 3, '0', STR_PAD_LEFT);
        }

        // Descrição do serviço: usa a informada no wizard, senão a montada a partir da OS.
        $descOpcao = trim((string) ($opcoes['desc_servico'] ?? ''));
        $descServico = $descOpcao !== ''
            ? $descOpcao
            : 'OS nr. ' . $os->idOs . ': ' . implode('; ', $descricoes);
        // Informações complementares anexadas à descrição (a DPS nacional não tem campo próprio).
        $infComplementar = trim((string) ($opcoes['info_complementar'] ?? ''));
        if ($infComplementar !== '') {
            $descServico .= ' | Obs.: ' . $infComplementar;
        }
        // Higieniza: na emissão automática o texto vem de campos livres da OS
        // (laudo/observações) que podem trazer HTML, quebras CRLF e caracteres de
        // controle. Isso pode ser reserializado de forma diferente no envio e
        // quebrar o digest da assinatura (E0714). Deixa só texto limpo.
        $descServico = $this->limparTexto($descServico);
        if ($descServico === '') {
            $descServico = 'OS nr. ' . $os->idOs;
        }
        $std->infDPS->serv->cServ->xDescServ = $this->escaparXml(mb_substr($descServico, 0, 2000));

        // valores
        $tpRet = isset($opcoes['tp_ret_issqn']) && $opcoes['tp_ret_issqn'] !== ''
            ? (int) $opcoes['tp_ret_issqn']
            : (int) $this->config->tp_ret_issqn;
        $aliquota = isset($opcoes['aliquota_iss']) && $opcoes['aliquota_iss'] !== ''
            ? (float) str_replace(',', '.', (string) $opcoes['aliquota_iss'])
            : (float) $this->config->aliquota_iss;

        $std->infDPS->valores = new stdClass();
        $std->infDPS->valores->vServPrest = new stdClass();
        $std->infDPS->valores->vServPrest->vServ = number_format($total, 2, '.', '');
        $std->infDPS->valores->trib = new stdClass();
        $std->infDPS->valores->trib->tribMun = new stdClass();
        $std->infDPS->valores->trib->tribMun->tribISSQN = 1; // operação tributável
        $std->infDPS->valores->trib->tribMun->tpRetISSQN = $tpRet;
        // Alíquota (pAliq) só é permitida quando o ISS é RETIDO (tpRetISSQN=2).
        // No Simples Nacional sem retenção, informar alíquota gera a rejeição E0625.
        if ($aliquota > 0 && $tpRet === 2) {
            $std->infDPS->valores->trib->tribMun->pAliq = number_format($aliquota, 2, '.', '');
        }

        // Total de tributos: ME/EPP/MEI (optante) usa o percentual do Simples
        // (pTotTribSN) e NÃO pode enviar indTotTrib (rejeição E0712).
        $std->infDPS->valores->trib->totTrib = new stdClass();
        if (in_array((int) $this->config->op_simp_nac, [2, 3], true)) {
            $std->infDPS->valores->trib->totTrib->pTotTribSN = number_format($aliquota > 0 ? $aliquota : 0, 2, '.', '');
        } else {
            $std->infDPS->valores->trib->totTrib->indTotTrib = 0;
        }

        // Reforma Tributária: anexa o grupo IBS/CBS à DPS SOMENTE quando o
        // checkbox estiver ligado E a lib/schema do hadder já suportarem o layout
        // (senão a DPS seria rejeitada por schema). Hoje nfseSuportaIbsCbs() é
        // falso → nada muda; ao atualizar o hadder, passa a valer automaticamente.
        if (!empty($this->config->reforma_ativa) && $this->nfseSuportaIbsCbs()) {
            $this->anexarIbsCbsDps($std, $total);
        }

        $dps = new Dps($std);
        $resposta = $this->tools->enviaDps($dps->render());

        if (!is_array($resposta)) {
            throw new Exception('Retorno inesperado do Sefin Nacional: ' . json_encode($resposta));
        }

        // resposta de sucesso contém a chave de acesso e o XML da NFS-e gzip+base64
        $chave = $resposta['chaveAcesso'] ?? $resposta['idDps'] ?? null;
        $xmlGzip = $resposta['nfseXmlGZipB64'] ?? null;

        if ($xmlGzip === null) {
            [$mensagem, $detalhe] = $this->formatarErros($resposta);

            return [
                'sucesso' => false,
                'chave' => $chave,
                'numero_dps' => $numeroDps,
                'motivo' => $mensagem,
                'motivo_detalhe' => $detalhe,
                'xml' => null,
            ];
        }

        $xmlNfse = gzdecode(base64_decode($xmlGzip));
        if ($chave === null && $xmlNfse !== false) {
            // extrai a chave do atributo Id da infNFSe quando não vem no corpo da resposta
            if (preg_match('/infNFSe[^>]*Id="NFS(\d+)"/i', $xmlNfse, $m)) {
                $chave = $m[1];
            }
        }

        return [
            'sucesso' => true,
            'chave' => $chave,
            'numero_dps' => $numeroDps,
            'motivo' => 'NFS-e gerada pelo Sefin Nacional',
            'descricao_servico' => $descServico,
            'xml' => $xmlNfse !== false ? $xmlNfse : null,
        ];
    }

    /**
     * Cancela uma NFS-e pelo evento e101101.
     */
    public function cancelar(string $chave, string $motivo): array
    {
        $std = new stdClass();
        $std->infPedReg = new stdClass();
        $std->infPedReg->chNFSe = $chave;
        $std->infPedReg->CNPJAutor = preg_replace('/\D/', '', (string) $this->emitente->cnpj);
        $std->infPedReg->dhEvento = date('Y-m-d\TH:i:sP');
        $std->infPedReg->tpAmb = (int) $this->config->ambiente;
        $std->infPedReg->verAplic = self::VER_APLIC;
        $std->infPedReg->e101101 = new stdClass();
        $std->infPedReg->e101101->xDesc = 'Cancelamento de NFS-e';
        $std->infPedReg->e101101->cMotivo = 1; // erro na emissão
        $std->infPedReg->e101101->xMotivo = $this->limparCampo($motivo, 255);

        $resposta = $this->tools->cancelaNfse($std);

        if (is_array($resposta) && (isset($resposta['erro']) || isset($resposta['erros']))) {
            [$mensagem, $detalhe] = $this->formatarErros($resposta);

            return [
                'sucesso' => false,
                'motivo' => $mensagem,
                'motivo_detalhe' => $detalhe,
            ];
        }

        return ['sucesso' => true, 'motivo' => 'Evento de cancelamento registrado no Sefin Nacional'];
    }

    /**
     * Baixa o PDF do DANFSe direto do Sefin Nacional.
     * A biblioteca devolve o conteúdo "cru" — aqui garantimos que é um PDF
     * (decodificando base64/gzip se preciso) ou lançamos um erro legível.
     */
    public function danfse(string $chave): string
    {
        $retorno = $this->tools->consultarDanfse($chave);

        if (is_array($retorno)) {
            $erro = $retorno['erro'] ?? $retorno['erros'] ?? $retorno;
            throw new Exception('Sefin Nacional não retornou o DANFSe: ' . (is_string($erro) ? $erro : json_encode($erro, JSON_UNESCAPED_UNICODE)));
        }
        if (!is_string($retorno) || $retorno === '') {
            throw new Exception('Sefin Nacional retornou vazio para o DANFSe (chave ' . $chave . ').');
        }

        // Já é um PDF?
        if (strncmp($retorno, '%PDF', 4) === 0) {
            return $retorno;
        }
        // Veio em base64?
        $b64 = base64_decode(trim($retorno), true);
        if ($b64 !== false && strncmp($b64, '%PDF', 4) === 0) {
            return $b64;
        }
        // Veio comprimido (gzip)?
        $gz = @gzdecode($retorno);
        if ($gz !== false && strncmp($gz, '%PDF', 4) === 0) {
            return $gz;
        }

        // Não é PDF — provavelmente uma mensagem de erro do Sefin.
        throw new Exception('O Sefin não retornou um PDF válido do DANFSe. Resposta: ' . mb_substr(trim($retorno), 0, 300));
    }
}
