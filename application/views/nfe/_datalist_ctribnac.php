<?php
// Sugestões de Código de Tributação Nacional (cTribNac) da NFS-e Nacional.
// Baseado na lista de serviços da LC 116 (código = item+subitem+variante, 6 dígitos).
// Foco em serviços de informática/tecnologia e serviços técnicos comuns.
// Confirme sempre no portal oficial: https://www.gov.br/nfse
$sugestoesCtribnac = [
    '010101' => '1.01 - Análise e desenvolvimento de sistemas',
    '010201' => '1.02 - Programação',
    '010301' => '1.03 - Processamento, armazenamento ou hospedagem de dados e congêneres',
    '010401' => '1.04 - Elaboração de programas de computadores, inclusive jogos eletrônicos',
    '010501' => '1.05 - Licenciamento ou cessão de direito de uso de programas de computação',
    '010601' => '1.06 - Assessoria e consultoria em informática',
    '010701' => '1.07 - Suporte técnico em informática (instalação, configuração e manutenção de programas e bancos de dados)',
    '010801' => '1.08 - Planejamento, confecção, manutenção e atualização de páginas eletrônicas',
    '010901' => '1.09 - Disponibilização de conteúdos de áudio, vídeo, imagem e texto pela internet',
    '140101' => '14.01 - Manutenção e conservação de máquinas, equipamentos, aparelhos e congêneres',
    '140201' => '14.02 - Assistência técnica',
    '140601' => '14.06 - Instalação e montagem de aparelhos, máquinas e equipamentos',
    '170101' => '17.01 - Assessoria ou consultoria de qualquer natureza',
];
?>
<datalist id="listaCtribnac">
    <?php foreach ($sugestoesCtribnac as $codigo => $descricao) { ?>
        <option value="<?= $codigo ?>"><?= html_escape($descricao) ?></option>
    <?php } ?>
</datalist>
