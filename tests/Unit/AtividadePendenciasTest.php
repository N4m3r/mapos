<?php

use PHPUnit\Framework\TestCase;

require_once BASEPATH . 'core/Model.php';
require_once APPPATH . 'models/Atividade_pendencias_model.php';

class AtividadePendenciasTest extends TestCase
{
    public function testClientesQueExigemChamadoPeloNome()
    {
        foreach (['Nova Era - Mercantil', 'NOVA ERA MERCANTIL LTDA', 'Supermercado Nova Era Mercatil', 'Pátio Gourmet', 'PATIO GOURMET TGFOODS', 'TG Foods Alimentos', 'tgfoods'] as $nome) {
            $this->assertTrue(Atividade_pendencias_model::nomeExigeChamado($nome), $nome);
        }
    }

    public function testOutrosClientesNaoExigem()
    {
        foreach (['Nova Era Academia', 'Mercado Bom Preço', 'Pátio Central', 'Gourmet Express', '', null] as $nome) {
            $this->assertFalse(Atividade_pendencias_model::nomeExigeChamado($nome), (string) $nome);
        }
    }

    public function testFaltaAssinaturaSoDepoisDoServico()
    {
        $hoje = '2026-11-08';
        $this->assertFalse(Atividade_pendencias_model::faltaAssinatura(false, 'Aberto', '2026-11-08', $hoje), 'aberta hoje ainda não é pendência');
        $this->assertTrue(Atividade_pendencias_model::faltaAssinatura(false, 'Aberto', '2026-11-07', $hoje), 'passou do dia');
        $this->assertTrue(Atividade_pendencias_model::faltaAssinatura(false, 'Finalizado', '2026-11-08', $hoje), 'finalizada sem assinatura');
        $this->assertFalse(Atividade_pendencias_model::faltaAssinatura(true, 'Finalizado', '2026-11-01', $hoje), 'assinada');
        $this->assertFalse(Atividade_pendencias_model::faltaAssinatura(false, 'Cancelado', '2026-11-01', $hoje), 'cancelada');
    }
}
