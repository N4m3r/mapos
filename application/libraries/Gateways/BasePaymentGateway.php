<?php

namespace Libraries\Gateways;

use Libraries\Gateways\Contracts\PaymentGateway;

abstract class BasePaymentGateway implements PaymentGateway
{
    /** @var \CI_Controller Instancia do CodeIgniter (atribuida no construtor de cada gateway) */
    protected $ci;

    /** Itens escolhidos ('p:<id>' / 's:<id>'); null = documento inteiro. */
    protected $selecao = null;

    /** Fração do desconto em R$ que cabe aos itens escolhidos (1 = tudo). */
    protected $fatorDescontoReal = 1.0;

    /** Complemento da descrição da cobrança (ex.: " - Serviços"). */
    protected $rotuloSelecao = '';

    /**
     * Restringe a próxima cobrança a alguns itens do documento. Sem chamar
     * (ou com null), cobra o documento inteiro como sempre.
     */
    public function definirSelecao(?array $chaves, string $rotulo = '')
    {
        $this->selecao = $chaves;
        $this->rotuloSelecao = $rotulo;
        $this->fatorDescontoReal = 1.0;
    }

    /**
     * Filtra produtos/serviços pela seleção e calcula a parte do desconto em
     * R$ proporcional ao valor escolhido. Desconto em % já se aplica sozinho
     * sobre o total filtrado.
     */
    protected function aplicarSelecao(array $produtos, array $servicos): array
    {
        if ($this->selecao === null) {
            return [$produtos, $servicos];
        }

        $bruto = static function (array $itens) {
            return array_reduce($itens, static function ($t, $i) {
                return $t + floatval($i->preco) * intval($i->quantidade);
            }, 0);
        };
        $totalGeral = $bruto($produtos) + $bruto($servicos);

        $sel = array_flip($this->selecao);
        $produtos = array_values(array_filter($produtos, static function ($p) use ($sel) {
            return isset($sel['p:' . (int) ($p->idProdutos_os ?? $p->idItens ?? 0)]);
        }));
        $servicos = array_values(array_filter($servicos, static function ($s) use ($sel) {
            return isset($sel['s:' . (int) ($s->idServicos_os ?? 0)]);
        }));

        if (! $produtos && ! $servicos) {
            throw new \Exception('Nenhum item escolhido para cobrar.');
        }

        $totalSel = $bruto($produtos) + $bruto($servicos);
        $this->fatorDescontoReal = $totalGeral > 0 ? $totalSel / $totalGeral : 1.0;

        return [$produtos, $servicos];
    }

    public function gerarCobranca($id, $tipo, $metodoPagamento, $data = [])
    {
        switch ($metodoPagamento) {
            case PaymentGateway::PAYMENT_METHOD_BILLET:
                return $this->gerarCobrancaBoleto($id, $tipo);
                break;
            case PaymentGateway::PAYMENT_METHOD_LINK:
                return $this->gerarCobrancaLink($id, $tipo);
                break;
            default:
                throw new \Exception('Método de pagamento inválido!');
        }
    }

    public function findEntity($id, $tipo)
    {
        switch ($tipo) {
            case PaymentGateway::PAYMENT_TYPE_OS:
                return $this->ci->Os_model->getById($id);
                break;
            case PaymentGateway::PAYMENT_TYPE_VENDAS:
                return $this->ci->vendas_model->getById($id);
                break;
            default:
                throw new \Exception('Tipo de entidade a ser gerado a cobrança inválido!');
                break;
        }
    }

    public function errosCadastro($entity = null)
    {
        if ($entity == null) {
            return null;
        }
        $error_list = "Por favor preencher os seguintes dados do(a) seu(ua) cliente!\n\n";
        $check = false;
        $list = ['rua', 'numero', 'bairro', 'cep', 'cidade', 'estado', 'documento', 'telefone', 'nomeCliente', 'email'];
        foreach ($entity as $key => $value) {
            if (in_array($key, $list)) {
                if ((empty($value) || strlen($value) < 2) && ! is_numeric($value)) {
                    $error_list .= '-' . $key . "\n";
                    $check = true;
                }
            }
        }
        if (! $check) {
            return null;
        }

        return $error_list;
    }

    public function enviarPorEmail($id)
    {
        throw new \Exception('Não implementado');
    }

    public function atualizarDados($id)
    {
        throw new \Exception('Não implementado');
    }

    public function cancelar($id)
    {
        throw new \Exception('Não implementado');
    }

    public function confirmarPagamento($id)
    {
        throw new \Exception('Não implementado');
    }

    abstract protected function gerarCobrancaBoleto($id, $tipo);

    abstract protected function gerarCobrancaLink($id, $tipo);
}
