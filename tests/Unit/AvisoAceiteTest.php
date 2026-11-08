<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Aviso_aceite.php';

class AvisoAceiteTest extends TestCase
{
    public function testTagsComChamado(): void
    {
        $os = (object) ['idOs' => '812', 'nomeCliente' => 'Pátio Gourmet', 'chamado_id' => ' CH-77 '];
        $this->assertSame([
            '{CLIENTE_NOME}' => 'Pátio Gourmet',
            '{NUMERO_OS}' => 812,
            '{LINK}' => 'https://x/a',
            '{CHAMADO}' => 'CH-77',
        ], Aviso_aceite::tags($os, 'https://x/a'));
    }

    public function testChamadoVazioQuandoAOsNaoTem(): void
    {
        $tags = Aviso_aceite::tags((object) ['idOs' => 5, 'nomeCliente' => 'Ana'], 'l');
        $this->assertSame('', $tags['{CHAMADO}']);
    }

    public function testFallbackUsaAsTagsBasicas(): void
    {
        foreach (['{CLIENTE_NOME}', '{NUMERO_OS}', '{LINK}'] as $tag) {
            $this->assertStringContainsString($tag, Aviso_aceite::FALLBACK);
        }
    }
}
