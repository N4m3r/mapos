<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Relatório de cobrança do mês por cliente (ADR-011).
 *
 * itens()      levanta OS, vendas, mensalidades de contrato e lançamentos avulsos
 *              da competência, com poucas queries (lotes + mapas em PHP).
 * classificar() função pura (sem banco) que decide a situação do item.
 * resumo()     agrupa por situação e por cliente.
 */
class Relatorio_cobranca_model extends CI_Model
{
    const SITUACOES = ['faturado', 'pendente', 'nao_realizado', 'nao_cobrado', 'a_realizar'];

    const STATUS_COBRANCA_PAGA = ['PAID', 'RECEIVED', 'CONFIRMED', 'DUNNING_RECEIVED', 'paid', 'settled'];
    const STATUS_COBRANCA_IGNORADA = ['CANCELLED', 'canceled', 'EXPIRED'];

    const OS_NAO_REALIZADO = ['Não Realizado', 'Cancelado'];
    const OS_CONCLUIDA = ['Finalizado', 'Faturado'];

    /* ===================================================================
     * Classificação (pura)
     * =================================================================== */

    public static function classificar(array $f, string $hoje): array
    {
        $origem = (string) ($f['origem'] ?? '');
        $status = (string) ($f['status'] ?? '');
        $temLanc = ! empty($f['tem_lancamento']);
        $lancPago = ! empty($f['lancamento_pago']);
        $dataPgto = $f['data_pagamento'] ?? null;
        $cobAberta = ! empty($f['tem_cobranca_aberta']);
        $cobPaga = ! empty($f['cobranca_paga']);
        $venc = $f['vencimento'] ?? null;
        $dataRef = (string) ($f['data_ref'] ?? '');

        // 1) Faturado e pago
        if ($lancPago || $cobPaga) {
            return [
                'situacao' => 'faturado',
                'detalhe' => self::validaData($dataPgto) ? 'Pago em ' . self::dm($dataPgto) : 'Pago',
            ];
        }

        // 2) Pendente de pagamento
        if ($temLanc || $cobAberta) {
            if (self::validaData($venc)) {
                if ($venc < $hoje) {
                    $dias = self::diasEntre($venc, $hoje);
                    $detalhe = 'Vencido há ' . $dias . ($dias === 1 ? ' dia' : ' dias');
                } else {
                    $detalhe = 'Vence ' . self::dm($venc);
                }
            } else {
                $detalhe = $cobAberta && ! $temLanc ? 'Boleto em aberto' : 'Aguardando pagamento';
            }

            return ['situacao' => 'pendente', 'detalhe' => $detalhe];
        }

        // 3) Não realizado
        if (($origem === 'os' && in_array($status, self::OS_NAO_REALIZADO, true))
            || ($origem === 'venda' && $status === 'Cancelado')) {
            return ['situacao' => 'nao_realizado', 'detalhe' => $status];
        }

        // 4) Não cobrado
        if ($origem === 'os' && in_array($status, self::OS_CONCLUIDA, true)) {
            return ['situacao' => 'nao_cobrado', 'detalhe' => 'Concluída sem cobrança'];
        }
        if ($origem === 'venda') {
            return ['situacao' => 'nao_cobrado', 'detalhe' => 'Venda sem cobrança'];
        }
        if ($origem === 'contrato') {
            if ($dataRef !== '' && $dataRef <= $hoje) {
                return ['situacao' => 'nao_cobrado', 'detalhe' => 'Mensalidade sem lançamento'];
            }

            // 5) Mensalidade futura
            return [
                'situacao' => 'a_realizar',
                'detalhe' => self::validaData($dataRef) ? 'Vence ' . self::dm($dataRef) : 'Mensalidade futura',
            ];
        }
        if ($origem === 'lancamento') {
            return ['situacao' => 'nao_cobrado', 'detalhe' => 'Sem cobrança'];
        }

        // 5) Será realizado (OS em aberto)
        $agenda = $f['data_agendamento'] ?? null; // fato opcional extra
        if (self::validaData($agenda)) {
            return ['situacao' => 'a_realizar', 'detalhe' => 'Agendada ' . self::dm($agenda)];
        }

        return ['situacao' => 'a_realizar', 'detalhe' => $status !== '' ? $status : 'Em aberto'];
    }

    private static function validaData($d): bool
    {
        return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}/', $d) === 1 && strpos($d, '0000') !== 0;
    }

    private static function dm(string $d): string
    {
        return substr($d, 8, 2) . '/' . substr($d, 5, 2);
    }

    private static function diasEntre(string $de, string $ate): int
    {
        $a = strtotime(substr($de, 0, 10));
        $b = strtotime(substr($ate, 0, 10));

        return max(0, (int) round(($b - $a) / 86400));
    }

    /* ===================================================================
     * Itens do mês
     * =================================================================== */

    public function itens(string $competencia, array $filtros = []): array
    {
        if (! preg_match('/^(\d{4})-(\d{2})$/', $competencia, $m) || (int) $m[2] < 1 || (int) $m[2] > 12) {
            $competencia = date('Y-m');
            preg_match('/^(\d{4})-(\d{2})$/', $competencia, $m);
        }
        $ini = $competencia . '-01';
        $fim = date('Y-m-t', strtotime($ini));
        $hoje = date('Y-m-d');

        $clienteId = ! empty($filtros['clientes_id']) ? (int) $filtros['clientes_id'] : null;
        $origemF = ! empty($filtros['origem']) ? (string) $filtros['origem'] : null;
        $situacaoF = $filtros['situacao'] ?? null;
        if (is_string($situacaoF)) {
            $situacaoF = $situacaoF === '' ? null : [$situacaoF];
        }
        if (is_array($situacaoF) && ! $situacaoF) {
            $situacaoF = null;
        }

        $quer = function ($o) use ($origemF) {
            return $origemF === null || $origemF === $o;
        };

        $itens = [];
        $lancUsados = [];

        // Avulsos dependem de saber o que foi usado: sempre levantamos os demais
        // quando o filtro de origem não os exclui; para 'lancamento' basta o resto
        // para calcular ids usados, então levantamos tudo e filtramos no fim.
        $itens = array_merge(
            $this->itensOs($ini, $fim, $hoje, $clienteId, $lancUsados),
            $this->itensVendas($ini, $fim, $hoje, $clienteId, $lancUsados),
            $this->itensContratos($competencia, $ini, $fim, $hoje, $clienteId, $lancUsados)
        );
        if ($quer('lancamento')) {
            $itens = array_merge($itens, $this->itensAvulsos($ini, $fim, $hoje, $clienteId, $lancUsados));
        }

        $saida = [];
        foreach ($itens as $it) {
            if ($origemF !== null && $it['origem'] !== $origemF) {
                continue;
            }
            if ($situacaoF !== null && ! in_array($it['situacao'], $situacaoF, true)) {
                continue;
            }
            $saida[] = $it;
        }

        return $saida;
    }

    /* -------------------------------- OS -------------------------------- */

    private function itensOs($ini, $fim, $hoje, $clienteId, array &$lancUsados): array
    {
        if (! $this->db->table_exists('os')) {
            return [];
        }
        $temAgenda = $this->db->field_exists('data_agendamento', 'os');
        $temLancCol = $this->db->field_exists('lancamento', 'os');
        $temDescProd = false;

        $cols = 'os.idOs, os.dataInicial, os.dataFinal, os.status, os.desconto, os.valor_desconto, os.faturado, os.clientes_id, clientes.nomeCliente';
        $cols .= $temAgenda ? ', os.data_agendamento' : '';
        $cols .= $temLancCol ? ', os.lancamento' : '';
        if ($this->db->field_exists('valorTotal', 'os')) {
            $cols .= ', os.valorTotal';
        }
        if ($this->db->field_exists('descricaoProduto', 'os')) {
            $cols .= ', os.descricaoProduto';
            $temDescProd = true;
        }

        $this->db->select($cols, false)->from('os')->join('clientes', 'clientes.idClientes = os.clientes_id', 'left');
        $this->db->group_start()
            ->group_start()->where('os.dataFinal >=', $ini)->where('os.dataFinal <=', $fim)->group_end()
            ->or_group_start()->where('os.dataInicial >=', $ini)->where('os.dataInicial <=', $fim)->group_end();
        if ($temAgenda) {
            $this->db->or_group_start()->where('os.data_agendamento >=', $ini)->where('os.data_agendamento <=', $fim . ' 23:59:59')->group_end();
        }
        $this->db->group_end();
        if ($clienteId) {
            $this->db->where('os.clientes_id', $clienteId);
        }
        $oss = $this->db->get()->result();
        if (! $oss) {
            return [];
        }

        $ids = array_map(function ($o) { return (int) $o->idOs; }, $oss);

        $totais = $this->totaisOs($ids);

        // Lançamentos: por os.lancamento e pela descrição padrão.
        $lancPorId = [];
        $idsLanc = [];
        if ($temLancCol) {
            foreach ($oss as $o) {
                if (! empty($o->lancamento)) {
                    $idsLanc[] = (int) $o->lancamento;
                }
            }
        }
        $lancPorId = $this->lancamentosPorId($idsLanc);
        $lancPorOs = $this->lancamentosDeOs($ids);

        // Cobranças
        $cobPorOs = $this->cobrancasPor('os_id', $ids);

        $itens = [];
        foreach ($oss as $o) {
            $id = (int) $o->idOs;
            $dataRef = null;
            $ag = $temAgenda && ! empty($o->data_agendamento) ? substr($o->data_agendamento, 0, 10) : null;
            foreach ([$o->dataFinal, $ag, $o->dataInicial] as $d) {
                if (self::validaData($d) && substr($d, 0, 10) >= $ini && substr($d, 0, 10) <= $fim) {
                    $dataRef = substr($d, 0, 10);
                    break;
                }
            }
            if ($dataRef === null) {
                continue;
            }

            $lancs = [];
            if ($temLancCol && ! empty($o->lancamento) && isset($lancPorId[(int) $o->lancamento])) {
                $lancs[] = $lancPorId[(int) $o->lancamento];
            } elseif (isset($lancPorOs[$id])) {
                $lancs = $lancPorOs[$id];
            }

            $valor = $this->valorOs($o, $totais[$id] ?? 0.0);
            $itens[] = $this->montaItem('os', $id, $o->clientes_id, $o->nomeCliente, $this->descOs($id, $o, $temDescProd), $dataRef,
                $valor, (string) $o->status, ! empty($o->faturado), $lancs, $cobPorOs[$id] ?? [], $hoje,
                'os/visualizar/' . $id, $ag, $lancUsados);
        }

        return $itens;
    }

    private function descOs($id, $o, $temDescProd): string
    {
        $d = 'OS #' . $id;
        if ($temDescProd && ! empty($o->descricaoProduto)) {
            $txt = trim(preg_replace('/\s+/', ' ', strip_tags($o->descricaoProduto)));
            if ($txt !== '') {
                $d .= ' - ' . (mb_strlen($txt) > 40 ? mb_substr($txt, 0, 40) . '...' : $txt);
            }
        }

        return $d;
    }

    private function valorOs($o, $total): float
    {
        if (! empty($o->desconto) && (float) $o->valor_desconto != 0) {
            return round((float) $o->valor_desconto, 2);
        }
        if ($total <= 0 && isset($o->valorTotal)) {
            return round((float) $o->valorTotal, 2);
        }

        return round((float) $total, 2);
    }

    /** Soma serviços + produtos por OS, em lote (regra de Os_model::valorTotalOS). */
    private function totaisOs(array $ids): array
    {
        $tot = array_fill_keys($ids, 0.0);
        if ($this->db->table_exists('servicos_os')) {
            $sel = 'servicos_os.os_id, servicos_os.preco, servicos_os.quantidade, servicos.preco AS precoVenda';
            $this->db->select($sel, false)->from('servicos_os')
                ->join('servicos', 'servicos.idServicos = servicos_os.servicos_id', 'left')
                ->where_in('servicos_os.os_id', $ids);
            foreach ($this->db->get()->result() as $s) {
                $preco = $s->preco ?: $s->precoVenda;
                $tot[(int) $s->os_id] += (float) $preco * ($s->quantidade ?: 1);
            }
        }
        if ($this->db->table_exists('produtos_os')) {
            $this->db->select('os_id, SUM(subTotal) AS t', false)->from('produtos_os')
                ->where_in('os_id', $ids)->group_by('os_id');
            foreach ($this->db->get()->result() as $p) {
                $tot[(int) $p->os_id] += (float) $p->t;
            }
        }

        return $tot;
    }

    /* ------------------------------ Vendas ------------------------------ */

    private function itensVendas($ini, $fim, $hoje, $clienteId, array &$lancUsados): array
    {
        if (! $this->db->table_exists('vendas')) {
            return [];
        }
        $cols = 'vendas.idVendas, vendas.dataVenda, vendas.valorTotal, vendas.desconto, vendas.valor_desconto, vendas.faturado, vendas.clientes_id, clientes.nomeCliente';
        $temStatus = $this->db->field_exists('status', 'vendas');
        $temLancCol = $this->db->field_exists('lancamentos_id', 'vendas');
        $cols .= $temStatus ? ', vendas.status' : '';
        $cols .= $temLancCol ? ', vendas.lancamentos_id' : '';

        $this->db->select($cols, false)->from('vendas')->join('clientes', 'clientes.idClientes = vendas.clientes_id', 'left')
            ->where('vendas.dataVenda >=', $ini)->where('vendas.dataVenda <=', $fim);
        if ($clienteId) {
            $this->db->where('vendas.clientes_id', $clienteId);
        }
        $vendas = $this->db->get()->result();
        if (! $vendas) {
            return [];
        }
        $ids = array_map(function ($v) { return (int) $v->idVendas; }, $vendas);

        $idsLanc = [];
        if ($temLancCol) {
            foreach ($vendas as $v) {
                if (! empty($v->lancamentos_id)) {
                    $idsLanc[] = (int) $v->lancamentos_id;
                }
            }
        }
        $lancPorId = $this->lancamentosPorId($idsLanc);
        $lancPorVenda = [];
        if ($this->db->table_exists('lancamentos') && $this->db->field_exists('vendas_id', 'lancamentos')) {
            $this->db->from('lancamentos')->where_in('vendas_id', $ids);
            foreach ($this->db->get()->result() as $l) {
                $lancPorVenda[(int) $l->vendas_id][(int) $l->idLancamentos] = $l;
            }
        }
        $cobPorVenda = $this->cobrancasPor('vendas_id', $ids);

        $itens = [];
        foreach ($vendas as $v) {
            $id = (int) $v->idVendas;
            $lancs = $lancPorVenda[$id] ?? [];
            if ($temLancCol && ! empty($v->lancamentos_id) && isset($lancPorId[(int) $v->lancamentos_id])) {
                $lancs[(int) $v->lancamentos_id] = $lancPorId[(int) $v->lancamentos_id];
            }
            $valor = (! empty($v->desconto) && (float) $v->valor_desconto != 0) ? (float) $v->valor_desconto : (float) $v->valorTotal;
            $itens[] = $this->montaItem('venda', $id, $v->clientes_id, $v->nomeCliente, 'Venda #' . $id, substr($v->dataVenda, 0, 10),
                round($valor, 2), $temStatus ? (string) $v->status : '', ! empty($v->faturado), array_values($lancs),
                $cobPorVenda[$id] ?? [], $hoje, 'vendas/visualizar/' . $id, null, $lancUsados);
        }

        return $itens;
    }

    /* ----------------------------- Contratos ---------------------------- */

    private function itensContratos($competencia, $ini, $fim, $hoje, $clienteId, array &$lancUsados): array
    {
        if (! $this->db->table_exists('contratos')) {
            return [];
        }
        $this->db->select('contratos.*, clientes.nomeCliente', false)->from('contratos')
            ->join('clientes', 'clientes.idClientes = contratos.clientes_id', 'left')
            ->where('contratos.status', 'ativo')
            ->where('contratos.data_inicio <=', $fim);
        $this->db->group_start()->where('contratos.data_fim IS NULL', null, false)
            ->or_where('contratos.data_fim >=', $ini)->group_end();
        if ($clienteId) {
            $this->db->where('contratos.clientes_id', $clienteId);
        }
        $contratos = $this->db->get()->result();
        if (! $contratos) {
            return [];
        }

        $this->load->model('contratos_model');
        [$ano, $mes] = array_map('intval', explode('-', $competencia));
        $mmaaaa = sprintf('%02d/%04d', $mes, $ano);

        $elegiveis = [];
        $descs = [];
        foreach ($contratos as $c) {
            if (! self::validaData($c->data_inicio) || $c->data_inicio > $fim) {
                continue;
            }
            if (self::validaData($c->data_fim) && $c->data_fim < $ini) {
                continue;
            }
            $passo = ['trimestral' => 3, 'semestral' => 6, 'anual' => 12][$c->tipo] ?? 1;
            $di = (int) substr($c->data_inicio, 0, 4) * 12 + (int) substr($c->data_inicio, 5, 2);
            $diff = ($ano * 12 + $mes) - $di;
            if ($diff < 0 || $diff % $passo !== 0) {
                continue;
            }
            $c->_desc = $this->contratos_model->descricaoFatura($c->idContrato, $mmaaaa);
            $descs[$c->_desc] = true;
            $elegiveis[] = $c;
        }
        if (! $elegiveis) {
            return [];
        }

        $lancPorDesc = [];
        if ($this->db->table_exists('lancamentos')) {
            $this->db->from('lancamentos')->where_in('descricao', array_keys($descs));
            foreach ($this->db->get()->result() as $l) {
                $lancPorDesc[$l->descricao][] = $l;
            }
        }

        $itens = [];
        foreach ($elegiveis as $c) {
            $dia = min(28, max(1, (int) $c->dia_vencimento));
            $venc = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
            $desc = 'Mensalidade Contrato #' . (int) $c->idContrato . ' - ' . $mmaaaa;
            $itens[] = $this->montaItem('contrato', (int) $c->idContrato, $c->clientes_id, $c->nomeCliente, $desc, $venc,
                round((float) $c->valor, 2), '', false, $lancPorDesc[$c->_desc] ?? [], [], $hoje,
                'contratos/visualizar/' . (int) $c->idContrato, null, $lancUsados);
        }

        return $itens;
    }

    /* ------------------------------ Avulsos ----------------------------- */

    private function itensAvulsos($ini, $fim, $hoje, $clienteId, array $lancUsados): array
    {
        if (! $this->db->table_exists('lancamentos')) {
            return [];
        }
        $temCli = $this->db->field_exists('clientes_id', 'lancamentos');
        $this->db->select('lancamentos.*' . ($temCli ? ', clientes.nomeCliente' : ''), false)->from('lancamentos');
        if ($temCli) {
            $this->db->join('clientes', 'clientes.idClientes = lancamentos.clientes_id', 'left');
        }
        $this->db->where('lancamentos.tipo', 'receita')
            ->where('lancamentos.data_vencimento >=', $ini)->where('lancamentos.data_vencimento <=', $fim);
        if ($clienteId) {
            if (! $temCli) {
                return [];
            }
            $this->db->where('lancamentos.clientes_id', $clienteId);
        }
        $itens = [];
        foreach ($this->db->get()->result() as $l) {
            if (isset($lancUsados[(int) $l->idLancamentos])) {
                continue;
            }
            $nome = $temCli ? $l->nomeCliente : null;
            if (! $nome && ! empty($l->cliente_fornecedor)) {
                $nome = $l->cliente_fornecedor;
            }
            $usados = [];
            $it = $this->montaItem('lancamento', (int) $l->idLancamentos, $temCli ? $l->clientes_id : null, $nome,
                (string) $l->descricao, substr($l->data_vencimento, 0, 10), $this->valorLanc($l), '', false, [$l], [], $hoje,
                'financeiro/lancamentos', null, $usados);
            $itens[] = $it;
        }

        return $itens;
    }

    /* --------------------------- Auxiliares DB -------------------------- */

    private function lancamentosPorId(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        $mapa = [];
        if (! $ids || ! $this->db->table_exists('lancamentos')) {
            return $mapa;
        }
        $this->db->from('lancamentos')->where_in('idLancamentos', $ids);
        foreach ($this->db->get()->result() as $l) {
            $mapa[(int) $l->idLancamentos] = $l;
        }

        return $mapa;
    }

    /** Lançamentos de OS pela descrição padrão ("Fatura de OS Nº: N" ou "Fatura de OS - #N"). */
    private function lancamentosDeOs(array $ids): array
    {
        $mapa = [];
        if (! $ids || ! $this->db->table_exists('lancamentos')) {
            return $mapa;
        }
        foreach (array_chunk($ids, 50) as $chunk) {
            $this->db->from('lancamentos')->group_start();
            foreach ($chunk as $id) {
                $this->db->or_like('descricao', 'Fatura de OS Nº: ' . $id, 'after')
                    ->or_like('descricao', 'Fatura de OS - #' . $id, 'after');
            }
            $this->db->group_end();
            foreach ($this->db->get()->result() as $l) {
                if (preg_match('/^Fatura de OS (?:Nº:\s*|- #)(\d+)\s*$/u', (string) $l->descricao, $mm)) {
                    $os = (int) $mm[1];
                    if (in_array($os, $chunk, true)) {
                        $mapa[$os][(int) $l->idLancamentos] = $l;
                    }
                }
            }
        }
        foreach ($mapa as $k => $v) {
            $mapa[$k] = array_values($v);
        }

        return $mapa;
    }

    private function cobrancasPor(string $campo, array $ids): array
    {
        $mapa = [];
        if (! $ids || ! $this->db->table_exists('cobrancas') || ! $this->db->field_exists($campo, 'cobrancas')) {
            return $mapa;
        }
        $this->db->from('cobrancas')->where_in($campo, $ids);
        foreach ($this->db->get()->result() as $c) {
            $mapa[(int) $c->$campo][] = $c;
        }

        return $mapa;
    }

    private function valorLanc($l): float
    {
        if (! empty($l->desconto) && (float) $l->valor_desconto != 0) {
            return round((float) $l->valor_desconto, 2);
        }

        return round((float) $l->valor, 2);
    }

    /**
     * Monta o item: deriva os fatos dos lançamentos/cobranças e chama classificar().
     *
     * @param array $lancs lançamentos relacionados (objetos)
     * @param array $cobs  cobranças relacionadas (objetos)
     */
    private function montaItem($origem, $refId, $clienteId, $nome, $descricao, $dataRef, $valor, $status, $faturado,
        array $lancs, array $cobs, $hoje, $link, $agendamento, array &$lancUsados): array
    {
        $temLanc = ! empty($lancs);
        $todosPagos = $temLanc;
        $venc = null;
        $dataPgto = null;
        $valorLanc = 0.0;
        foreach ($lancs as $l) {
            $lancUsados[(int) $l->idLancamentos] = true;
            $valorLanc += $this->valorLanc($l);
            $pago = ! empty($l->baixado);
            if (! $pago) {
                $todosPagos = false;
                if (self::validaData($l->data_vencimento) && ($venc === null || $l->data_vencimento < $venc)) {
                    $venc = substr($l->data_vencimento, 0, 10);
                }
            } elseif (self::validaData($l->data_pagamento) && ($dataPgto === null || $l->data_pagamento > $dataPgto)) {
                $dataPgto = substr($l->data_pagamento, 0, 10);
            }
        }
        if ($temLanc && $todosPagos && $venc === null) {
            // vencimento informativo do último lançamento
            $venc = self::validaData(end($lancs)->data_vencimento) ? substr(end($lancs)->data_vencimento, 0, 10) : null;
        }

        $cobAberta = false;
        $cobPaga = false;
        foreach ($cobs as $c) {
            $st = (string) $c->status;
            if (in_array($st, self::STATUS_COBRANCA_PAGA, true)) {
                $cobPaga = true;
            } elseif (! in_array($st, self::STATUS_COBRANCA_IGNORADA, true)) {
                $cobAberta = true;
                if (! $temLanc && self::validaData($c->expire_at ?? null)) {
                    $e = substr($c->expire_at, 0, 10);
                    if ($venc === null || $e < $venc) {
                        $venc = $e;
                    }
                }
            }
        }

        $fatos = [
            'origem' => $origem,
            'status' => $status,
            'faturado' => (bool) $faturado,
            'tem_lancamento' => $temLanc,
            'lancamento_pago' => $temLanc && $todosPagos,
            'data_pagamento' => $dataPgto,
            'tem_cobranca_aberta' => $cobAberta,
            'cobranca_paga' => $cobPaga,
            'vencimento' => $venc,
            'data_ref' => $dataRef,
            'data_agendamento' => $agendamento,
        ];
        $cls = self::classificar($fatos, $hoje);

        // Avulso: o valor é o do próprio lançamento.
        if ($origem === 'lancamento' && $temLanc) {
            $valor = round($valorLanc, 2);
        }

        return [
            'origem' => $origem,
            'referencia_id' => (int) $refId,
            'clientes_id' => $clienteId !== null && $clienteId !== '' ? (int) $clienteId : null,
            'cliente' => ($nome !== null && trim((string) $nome) !== '') ? (string) $nome : '(sem cliente)',
            'descricao' => (string) $descricao,
            'data_ref' => $dataRef,
            'vencimento' => $venc,
            'valor' => round((float) $valor, 2),
            'situacao' => $cls['situacao'],
            'detalhe' => $cls['detalhe'],
            'link' => $link,
        ];
    }

    /* ===================================================================
     * Resumo
     * =================================================================== */

    public function resumo(array $itens): array
    {
        $totais = [];
        foreach (self::SITUACOES as $s) {
            $totais[$s] = ['qtd' => 0, 'valor' => 0.0];
        }
        $clientes = [];

        foreach ($itens as $it) {
            $s = $it['situacao'];
            if (! isset($totais[$s])) {
                $totais[$s] = ['qtd' => 0, 'valor' => 0.0];
            }
            $totais[$s]['qtd']++;
            $totais[$s]['valor'] += (float) $it['valor'];

            $chave = $it['clientes_id'] === null ? 'sem' : (string) $it['clientes_id'];
            if (! isset($clientes[$chave])) {
                $clientes[$chave] = [
                    'clientes_id' => $it['clientes_id'],
                    'cliente' => $it['cliente'],
                    'itens' => [],
                    'totais' => array_fill_keys(self::SITUACOES, 0.0),
                    'total_a_cobrar' => 0.0,
                ];
            }
            $clientes[$chave]['itens'][] = $it;
            $clientes[$chave]['totais'][$s] = ($clientes[$chave]['totais'][$s] ?? 0.0) + (float) $it['valor'];
        }

        foreach ($totais as $s => $t) {
            $totais[$s]['valor'] = round($t['valor'], 2);
        }

        foreach ($clientes as &$c) {
            foreach ($c['totais'] as $s => $v) {
                $c['totais'][$s] = round($v, 2);
            }
            $c['total_a_cobrar'] = round(($c['totais']['nao_cobrado'] ?? 0) + ($c['totais']['pendente'] ?? 0), 2);
            usort($c['itens'], function ($a, $b) {
                return strcmp($a['data_ref'], $b['data_ref']) ?: ($a['referencia_id'] <=> $b['referencia_id']);
            });
        }
        unset($c);

        $lista = array_values($clientes);
        usort($lista, function ($a, $b) {
            return strcasecmp($a['cliente'], $b['cliente']);
        });

        return ['totais' => $totais, 'clientes' => $lista];
    }
}
