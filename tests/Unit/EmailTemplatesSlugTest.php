<?php

use PHPUnit\Framework\TestCase;

require_once BASEPATH . 'core/Model.php';
require_once APPPATH . 'models/Email_templates_model.php';

class EmailTemplatesSlugTest extends TestCase
{
    public function testSlugSemAcentoEComSublinhado(): void
    {
        $this->assertSame('aceite_do_servico_clientes', Email_templates_model::slugBase('Aceite do Serviço - Clientes'));
        $this->assertSame('ordem_de_servico', Email_templates_model::slugBase('ORDEM DE SERVIÇO'));
    }

    public function testNomeSemLetrasViraModelo(): void
    {
        $this->assertSame('modelo', Email_templates_model::slugBase('  --  '));
    }
}
