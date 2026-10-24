<?php

use MercadoPago\Exceptions\MPBadRequestException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Net\MPHttpClient;
use MercadoPago\Net\MPRequest;
use MercadoPago\Net\MPResponse;
use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Gateways/MercadoPago.php';

/**
 * Gateway MercadoPago (SDK v3) com o HTTP do SDK e o CodeIgniter simulados.
 */
class MercadoPagoGatewayTest extends TestCase
{
    private HttpFalso $http;

    private CiFalso $ci;

    private MercadoPago $gateway;

    protected function setUp(): void
    {
        $this->ci = new CiFalso();
        $GLOBALS['CI'] = $this->ci;

        // O PaymentClient captura o cliente HTTP no construtor: definir antes do gateway.
        $this->http = new HttpFalso();
        MercadoPagoConfig::setHttpClient($this->http);

        $this->gateway = new MercadoPago();
    }

    public function testGeraBoletoDeVenda(): void
    {
        $this->http->respostas[] = [201, [
            'id' => 555,
            'status' => 'pending',
            'date_of_expiration' => '2026-10-27T10:00:00.000-04:00',
            'transaction_details' => [
                'external_resource_url' => 'https://mp.test/boleto/555',
                'barcode' => ['content' => '23790000'],
            ],
        ]];

        $dados = $this->gateway->gerarCobranca(5, 'venda', 'boleto');

        [$metodo, $uri, $corpo, $cabecalhos] = $this->http->chamadas[0];
        $this->assertSame('POST', $metodo);
        $this->assertStringEndsWith('/v1/payments', $uri);
        $this->assertSame('Venda #5', $corpo['description']);
        $this->assertArrayNotHasKey('notification_url', $corpo);
        $this->assertEquals(100.0, $corpo['transaction_amount']);
        $this->assertSame('CPF', $corpo['payer']['identification']['type']);
        $this->assertNotEmpty(preg_grep('/^X-Idempotency-Key: /i', $cabecalhos));
        $this->assertNotEmpty(preg_grep('/Bearer TEST-token/', $cabecalhos));

        $this->assertSame('23790000', $dados['barcode']);
        $this->assertSame('https://mp.test/boleto/555', $dados['link']);
        $this->assertSame(555, $dados['charge_id']);
        $this->assertSame(10000, $dados['total']);
        $this->assertSame(5, $dados['vendas_id']);
        $this->assertSame(99, $dados['idCobranca']);
    }

    public function testBoletoDeOsUsaLinhaDigitavelQuandoNaoHaCodigoDeBarras(): void
    {
        // Resposta sem date_of_expiration nem barcode: nao pode derrubar com propriedade nao inicializada.
        $this->http->respostas[] = [201, ['id' => 556, 'status' => 'pending', 'transaction_details' => ['digitable_line' => '2379 1111']]];

        $dados = $this->gateway->gerarCobranca(8, 'os', 'boleto');

        $this->assertSame('OS #8', $this->http->chamadas[0][2]['description']);
        $this->assertSame('2379 1111', $dados['barcode']);
        $this->assertNull($dados['expire_at']);
        $this->assertSame(8, $dados['os_id']);
    }

    public function testAtualizarDadosGravaStatusDoMercadoPago(): void
    {
        $this->http->respostas[] = [200, ['id' => 123456789, 'status' => 'approved']];

        $this->gateway->atualizarDados(1);

        $this->assertSame('GET', $this->http->chamadas[0][0]);
        $this->assertStringEndsWith('/v1/payments/123456789', $this->http->chamadas[0][1]);
        $this->assertSame(['status' => 'approved'], end($this->ci->cobrancas_model->editados));
    }

    public function testCancelar(): void
    {
        $this->http->respostas[] = [200, ['id' => 123456789, 'status' => 'cancelled']];
        $this->http->respostas[] = [200, ['id' => 123456789, 'status' => 'cancelled']];

        $this->gateway->cancelar(1);

        $this->assertSame('PUT', $this->http->chamadas[0][0]);
        $this->assertSame('cancelled', $this->http->chamadas[0][2]['status']);
    }

    public function testErroDaApiViraExceptionComMensagemECausa(): void
    {
        $this->http->respostas[] = [400, ['message' => 'invalid payer', 'cause' => [['code' => 1, 'description' => 'payer.email invalido']]]];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Mercado Pago: invalid payer - payer.email invalido');

        $this->gateway->gerarCobranca(5, 'venda', 'boleto');
    }
}

class HttpFalso implements MPHttpClient
{
    public array $chamadas = [];

    public array $respostas = [];

    public function send(MPRequest $request): MPResponse
    {
        $this->chamadas[] = [$request->getMethod(), $request->getUri(), json_decode((string) $request->getPayload(), true), $request->getHeaders()];
        [$status, $corpo] = array_shift($this->respostas);
        $resposta = new MPResponse($status, $corpo);
        if ($status >= 300) {
            // Mesmo comportamento do MPDefaultHttpClient
            throw new MPBadRequestException('Api error', $resposta);
        }

        return $resposta;
    }
}

#[AllowDynamicProperties]
class CiFalso
{
    public $load;

    public $session;

    public $config;

    public $Os_model;

    public $vendas_model;

    public $cobrancas_model;

    public function __construct()
    {
        $this->load = new NadaFalso();
        $this->session = new NadaFalso();
        $this->config = new ConfigFalso();
        $this->Os_model = new ModelFalso();
        $this->vendas_model = new ModelFalso();
        $this->cobrancas_model = new ModelFalso();
    }
}

class NadaFalso
{
    public function __call($metodo, $argumentos)
    {
        return null;
    }
}

class ConfigFalso
{
    public function item($chave)
    {
        return ['MercadoPago' => [
            'credentials' => [
                'access_token' => 'TEST-token',
                'public_key' => '',
                'client_secret' => '',
                'client_id' => '',
                'integrator_id' => '',
                'platform_id' => null,
                'corporation_id' => '',
            ],
            'boleto_expiration' => 'P3D',
        ]];
    }
}

class ModelFalso
{
    public array $editados = [];

    public function getById($id)
    {
        return (object) [
            'idCobranca' => $id, 'charge_id' => '123456789', 'idClientes' => 7,
            'nomeCliente' => 'Maria da Silva', 'documento' => '123.456.789-09', 'email' => 'maria@exemplo.com',
            'telefone' => '11999999999', 'cep' => '01001-000', 'rua' => 'Rua A', 'numero' => '10',
            'bairro' => 'Centro', 'cidade' => 'Sao Paulo', 'estado' => 'SP', 'desconto' => 0, 'tipo_desconto' => null,
        ];
    }

    public function getProdutos($id)
    {
        return [(object) ['preco' => '50.00', 'quantidade' => 2]];
    }

    public function getServicos($id)
    {
        return [];
    }

    public function add($tabela, $dados, $retornarId = false)
    {
        return 99;
    }

    public function edit($tabela, $dados, $campo, $id)
    {
        $this->editados[] = $dados;

        return true;
    }
}
