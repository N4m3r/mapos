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

        // código de tributação nacional: exige ao menos um serviço com código
        $cTribNac = null;
        $descricoes = [];
        $total = 0.0;
        foreach ($servicos as $servico) {
            $codigo = preg_replace('/\D/', '', (string) ($servico->codigo_servico_municipio ?? ''));
            if ($cTribNac === null && strlen($codigo) === 6) {
                $cTribNac = $codigo;
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
        $std->infDPS->prest->regTrib->regEspTrib = (int) $this->config->reg_esp_trib;

        // tomador
        $std->infDPS->toma = new stdClass();
        if (strlen($documento) === 14) {
            $std->infDPS->toma->CNPJ = $documento;
        } else {
            $std->infDPS->toma->CPF = $documento;
        }
        $std->infDPS->toma->xNome = mb_substr($os->nomeCliente, 0, 150);

        // serviço
        $std->infDPS->serv = new stdClass();
        $std->infDPS->serv->locPrest = new stdClass();
        $std->infDPS->serv->locPrest->cLocPrestacao = (string) $this->config->codigo_municipio;
        $std->infDPS->serv->cServ = new stdClass();
        $std->infDPS->serv->cServ->cTribNac = $cTribNac;

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
        if ($aliquota > 0) {
            // Alíquota aplicada do ISSQN (layout nacional). No Simples Nacional
            // costuma ser dispensável (ISS apurado no DAS) — validar em homologação.
            $std->infDPS->valores->trib->tribMun->pAliqAplic = number_format($aliquota, 2, '.', '');
        }
        $std->infDPS->valores->trib->tribMun->tpRetISSQN = $tpRet;
        $std->infDPS->valores->trib->totTrib = new stdClass();
        $std->infDPS->valores->trib->totTrib->indTotTrib = 0;

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
     */
    public function danfse(string $chave): string
    {
        $pdf = $this->tools->consultarDanfse($chave);
        if (!is_string($pdf) || $pdf === '') {
            throw new Exception('Não foi possível obter o DANFSe no Sefin Nacional: ' . json_encode($pdf));
        }

        return $pdf;
    }
}
