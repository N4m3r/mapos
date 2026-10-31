<?php

use PHPUnit\Framework\TestCase;

require_once BASEPATH . 'core/Model.php';
require_once APPPATH . 'models/Fotosatendimento_model.php';

/**
 * Decodificacao das fotos de atendimento servidas por checkin/verFotoDB e mine/fotoAtendimento.
 */
class FotosAtendimentoTest extends TestCase
{
    public function testDataUrlUsaTipoDoPrefixo(): void
    {
        $r = Fotosatendimento_model::binarioDaFoto('data:image/png;base64,' . base64_encode('PNGDATA'), 'image/jpeg');
        $this->assertSame(['mime' => 'image/png', 'dados' => 'PNGDATA'], $r);
    }

    public function testBase64SemPrefixoUsaTipoGravado(): void
    {
        $r = Fotosatendimento_model::binarioDaFoto(base64_encode('WEBP'), 'image/webp');
        $this->assertSame(['mime' => 'image/webp', 'dados' => 'WEBP'], $r);
        $this->assertSame('image/jpeg', Fotosatendimento_model::binarioDaFoto(base64_encode('X'))['mime']);
    }

    public function testTipoQueNaoEImagemViraJpeg(): void
    {
        $this->assertSame('image/jpeg', Fotosatendimento_model::binarioDaFoto(base64_encode('<html>'), 'text/html')['mime']);
        $this->assertSame('image/jpeg', Fotosatendimento_model::binarioDaFoto('data:image/svg+xml;base64,' . base64_encode('<svg/>'))['mime']);
    }

    public function testInvalidoOuVazioDevolveNull(): void
    {
        $this->assertNull(Fotosatendimento_model::binarioDaFoto('***nao e base64***'));
        $this->assertNull(Fotosatendimento_model::binarioDaFoto(''));
    }
}
