<?php

class Permissoes extends Seeder
{
    private $table = 'permissoes';

    public function run()
    {
        echo 'Running Permissoes Seeder';

        // seed records manually
        $data = [
            'idPermissao' => 1,
            'nome' => 'Administrador',
            'permissoes' => 'a:91:{s:8:"vCliente";s:1:"1";s:8:"aCliente";s:1:"1";s:8:"eCliente";s:1:"1";s:8:"dCliente";s:1:"1";s:8:"vProduto";s:1:"1";s:8:"aProduto";s:1:"1";s:8:"eProduto";s:1:"1";s:8:"dProduto";s:1:"1";s:8:"vServico";s:1:"1";s:8:"aServico";s:1:"1";s:8:"eServico";s:1:"1";s:8:"dServico";s:1:"1";s:3:"vOs";s:1:"1";s:3:"aOs";s:1:"1";s:3:"eOs";s:1:"1";s:3:"dOs";s:1:"1";s:15:"vBtnAtendimento";s:1:"1";s:3:"vRh";s:1:"1";s:3:"eRh";s:1:"1";s:9:"aprovarRh";s:1:"1";s:13:"vRhFinanceiro";s:1:"1";s:11:"fecharFolha";s:1:"1";s:6:"vObras";s:1:"1";s:6:"cObras";s:1:"1";s:6:"eObras";s:1:"1";s:6:"dObras";s:1:"1";s:15:"vObraCronograma";s:1:"1";s:15:"eObraCronograma";s:1:"1";s:8:"vObraRdo";s:1:"1";s:8:"cObraRdo";s:1:"1";s:12:"vObraMedicao";s:1:"1";s:12:"cObraMedicao";s:1:"1";s:12:"aObraMedicao";s:1:"1";s:10:"vObraCusto";s:1:"1";s:13:"vObraMaterial";s:1:"1";s:13:"cObraMaterial";s:1:"1";s:13:"rObraMaterial";s:1:"1";s:13:"sObraMaterial";s:1:"1";s:11:"cObraEquipe";s:1:"1";s:10:"vObraCampo";s:1:"1";s:9:"vContrato";s:1:"1";s:9:"cContrato";s:1:"1";s:9:"eContrato";s:1:"1";s:9:"dContrato";s:1:"1";s:9:"fContrato";s:1:"1";s:6:"vVenda";s:1:"1";s:6:"aVenda";s:1:"1";s:6:"eVenda";s:1:"1";s:6:"dVenda";s:1:"1";s:9:"vCobranca";s:1:"1";s:9:"aCobranca";s:1:"1";s:9:"eCobranca";s:1:"1";s:9:"dCobranca";s:1:"1";s:4:"vNfe";s:1:"1";s:4:"eNfe";s:1:"1";s:4:"dNfe";s:1:"1";s:4:"cNfe";s:1:"1";s:8:"vEntrada";s:1:"1";s:8:"cEntrada";s:1:"1";s:8:"dEntrada";s:1:"1";s:9:"vGarantia";s:1:"1";s:9:"aGarantia";s:1:"1";s:9:"eGarantia";s:1:"1";s:9:"dGarantia";s:1:"1";s:8:"vArquivo";s:1:"1";s:8:"aArquivo";s:1:"1";s:8:"eArquivo";s:1:"1";s:8:"dArquivo";s:1:"1";s:11:"vLancamento";s:1:"1";s:11:"aLancamento";s:1:"1";s:11:"eLancamento";s:1:"1";s:11:"dLancamento";s:1:"1";s:8:"rCliente";s:1:"1";s:8:"rProduto";s:1:"1";s:8:"rServico";s:1:"1";s:3:"rOs";s:1:"1";s:6:"rVenda";s:1:"1";s:11:"rFinanceiro";s:1:"1";s:22:"vRelatorioAtendimentos";s:1:"1";s:10:"vDashboard";s:1:"1";s:18:"vRelatorioCompleto";s:1:"1";s:14:"vExportarDados";s:1:"1";s:8:"cUsuario";s:1:"1";s:10:"cPermissao";s:1:"1";s:9:"cEmitente";s:1:"1";s:8:"cSistema";s:1:"1";s:6:"cEmail";s:1:"1";s:7:"cBackup";s:1:"1";s:10:"cAuditoria";s:1:"1";s:10:"cAutomacao";s:1:"1";s:22:"cFormularioAtendimento";s:1:"1";}',
            'situacao' => 1,
            'data' => '2020-12-30',
        ];
        $this->db->insert($this->table, $data);

        echo PHP_EOL;
    }
}
