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
            throw new Exception('Nenhum serviço da OS possui o Código de Tributação Nacional (6 dígitos). Informe-o no momento da emissão ou cadastre-o em Serviços.');
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
        $std->infDPS->toma->xNome = mb_substr($os->nomeCliente, 0, 150);

        // Endereço do tomador — obrigatório quando o ISS é retido (E0237) e
        // recomendado sempre. O cadastro de cliente não guarda o código IBGE,
        // então assume-se o município do emitente (caso comum: tomador local).
        $tomaRua = trim((string) ($os->rua ?? ''));
        $tomaCep = preg_replace('/\D/', '', (string) ($os->cep ?? ''));
        if ($tomaRua !== '' && strlen($tomaCep) === 8) {
            $std->infDPS->toma->end = new stdClass();
            $std->infDPS->toma->end->xLgr = mb_substr($tomaRua, 0, 255);
            $std->infDPS->toma->end->nro = mb_substr(trim((string) ($os->numero ?? '')) ?: 'S/N', 0, 60);
            if (!empty($os->bairro)) {
                $std->infDPS->toma->end->xBairro = mb_substr((string) $os->bairro, 0, 60);
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
        $std->infDPS->serv->cServ->xDescServ = mb_substr($descServico, 0, 2000);

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

        $dps = new Dps($std);
        $resposta = $this->tools->enviaDps($dps->render());

        if (!is_array($resposta)) {
            throw new Exception('Retorno inesperado do Sefin Nacional: ' . json_encode($resposta));
        }

        // resposta de sucesso contém a chave de acesso e o XML da NFS-e gzip+base64
        $chave = $resposta['chaveAcesso'] ?? $resposta['idDps'] ?? null;
        $xmlGzip = $resposta['nfseXmlGZipB64'] ?? null;

        if ($xmlGzip === null) {
            $erros = $resposta['erros'] ?? $resposta['erro'] ?? $resposta;
            return [
                'sucesso' => false,
                'chave' => $chave,
                'numero_dps' => $numeroDps,
                'motivo' => is_string($erros) ? $erros : json_encode($erros, JSON_UNESCAPED_UNICODE),
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
        $std->infPedReg->e101101->xMotivo = mb_substr($motivo, 0, 255);

        $resposta = $this->tools->cancelaNfse($std);

        if (is_array($resposta) && (isset($resposta['erro']) || isset($resposta['erros']))) {
            $erros = $resposta['erros'] ?? $resposta['erro'];
            return [
                'sucesso' => false,
                'motivo' => is_string($erros) ? $erros : json_encode($erros, JSON_UNESCAPED_UNICODE),
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
