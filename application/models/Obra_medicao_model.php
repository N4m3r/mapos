<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Faturamento por medicao (ADR-015 fase 5).
 *
 * OS do projeto com fatura_no_projeto=1 nao faturam sozinhas: finalizadas, ficam
 * aguardando medicao. Na medicao, OS 'adicional' soma seu valor ao medido; as demais
 * (origem, execucao, corretiva) entram so como referencia, pois ja estao no contrato
 * pelas etapas. Faturar a medicao gera um lancamento de receita e fatura as OS.
 *
 * As funcoes estaticas sao puras (sem banco). As consultas sao resilientes:
 * sem a migracao aplicada devolvem vazio.
 */
class Obra_medicao_model extends CI_Model
{
    /* ====================== Funcoes puras ====================== */

    /** OS deste tipo de vinculo soma seu valor a medicao? So a adicional. */
    public static function entraNoValor(string $tipoVinculo): bool
    {
        return $tipoVinculo === 'adicional';
    }

    /** Valor cobrado da OS: valor com desconto (os.valor_desconto) ou servicos + produtos. */
    public static function valorOsComDesconto($totalServico, $totalProdutos, $valorComDesconto): float
    {
        $total = (float) $totalServico + (float) $totalProdutos;
        return round((float) $valorComDesconto > 0 ? (float) $valorComDesconto : $total, 2);
    }

    /** Soma das OS que entram no valor (soma_valor, ou pelo tipo de vinculo). */
    public static function somaValorOs(array $os): float
    {
        $soma = 0.0;
        foreach ($os as $o) {
            $o = (array) $o;
            $entra = array_key_exists('soma_valor', $o)
                ? (bool) $o['soma_valor']
                : self::entraNoValor((string) ($o['tipo_vinculo'] ?? ''));
            if ($entra) {
                $soma += (float) ($o['valor'] ?? 0);
            }
        }
        return round($soma, 2);
    }

    /** Valor total da medicao: etapas (numero ou lista com 'valor') + OS que somam. */
    public static function valorTotal($etapas, $os): float
    {
        if (is_array($etapas)) {
            $valorEtapas = 0.0;
            foreach ($etapas as $e) {
                $valorEtapas += (float) (((array) $e)['valor'] ?? 0);
            }
        } else {
            $valorEtapas = (float) $etapas;
        }
        return round($valorEtapas + self::somaValorOs((array) $os), 2);
    }

    /**
     * Avanco das etapas: [etapa_id => percentual_atual]. Etapa sem percentual fica
     * como esta; percentual nao recua nem passa de 100. valor = previsto * avanco.
     */
    public static function calcularEtapas(array $etapas, array $percentuaisPorEtapa): array
    {
        $out = [];
        foreach ($etapas as $e) {
            $e = (array) $e;
            $id = (int) $e['idEtapa'];
            $anterior = (float) ($e['percentual_concluido'] ?? 0);
            $novo = array_key_exists($id, $percentuaisPorEtapa) && $percentuaisPorEtapa[$id] !== '' && $percentuaisPorEtapa[$id] !== null
                ? (float) $percentuaisPorEtapa[$id]
                : $anterior;
            $novo = min(100.0, max($anterior, $novo));
            $out[] = [
                'etapa_id' => $id,
                'nome' => (string) ($e['nome'] ?? ''),
                'peso' => (float) ($e['peso_percentual'] ?? 0),
                'percentual_anterior' => $anterior,
                'percentual_atual' => $novo,
                'valor' => round((float) ($e['valor_previsto'] ?? 0) * (($novo - $anterior) / 100), 2),
            ];
        }
        return $out;
    }

    /** Percentual ponderado pelo peso (media simples se nao houver peso). */
    public static function percentualPonderado(array $etapas, string $campo): float
    {
        if (! $etapas) {
            return 0.0;
        }
        $peso = 0.0;
        $acc = 0.0;
        $simples = 0.0;
        foreach ($etapas as $e) {
            $peso += (float) $e['peso'];
            $acc += (float) $e['peso'] * (float) $e[$campo];
            $simples += (float) $e[$campo];
        }
        return round($peso > 0 ? $acc / $peso : $simples / count($etapas), 2);
    }

    public static function statusMedicaoRotulo(string $status): string
    {
        $r = ['aberta' => 'Em aberto', 'aprovada' => 'Aprovada', 'faturada' => 'Faturada'];
        return $r[$status] ?? ucfirst($status);
    }

    /* ========================= Consultas ========================= */

    private function disponivel(): bool
    {
        return $this->db->table_exists('obra_medicao_os')
            && $this->db->table_exists('obra_os')
            && $this->db->field_exists('fatura_no_projeto', 'obra_os');
    }

    /**
     * Valor das OS com desconto, como Os::faturar calcula (mesma conta de
     * Os_model::valorTotalOS), em lote: 2 queries para todas as OS.
     *
     * @param array<int,float|string|null> $descontos desconto por idOs
     * @return array<int,float> valor por idOs
     */
    private function valoresDasOs(array $descontos): array
    {
        $ids = array_keys($descontos);
        if (! $ids) {
            return [];
        }
        $servico = array_fill_keys($ids, 0);
        $produtos = array_fill_keys($ids, 0);

        $this->db->select('servicos_os.os_id, servicos_os.preco, servicos_os.quantidade, servicos.preco as precoVenda');
        $this->db->from('servicos_os');
        $this->db->join('servicos', 'servicos.idServicos = servicos_os.servicos_id');
        $this->db->where_in('servicos_os.os_id', $ids);
        foreach ($this->db->get()->result() as $s) {
            $preco = $s->preco ?: $s->precoVenda;
            $servico[(int) $s->os_id] += ($preco * ($s->quantidade ?: 1));
        }

        $this->db->select('produtos_os.os_id, produtos_os.subTotal');
        $this->db->from('produtos_os');
        $this->db->join('produtos', 'produtos.idProdutos = produtos_os.produtos_id');
        $this->db->where_in('produtos_os.os_id', $ids);
        foreach ($this->db->get()->result() as $p) {
            $produtos[(int) $p->os_id] += $p->subTotal;
        }

        $out = [];
        foreach ($ids as $id) {
            $out[$id] = self::valorOsComDesconto($servico[$id], $produtos[$id], $descontos[$id]);
        }
        return $out;
    }

    /** OS do projeto finalizadas, que faturam no projeto e ainda nao entraram em medicao. */
    public function osAguardandoMedicao(int $obraId): array
    {
        if (! $this->disponivel()) {
            return [];
        }
        $this->db->select('os.idOs, os.status, os.valor_desconto, obra_os.tipo_vinculo, etapa.nome as etapa_nome');
        $this->db->from('obra_os');
        $this->db->join('os', 'os.idOs = obra_os.os_id');
        $this->db->join('obra_etapas etapa', 'etapa.idEtapa = obra_os.etapa_id', 'left');
        $this->db->join('obra_medicao_os mo', 'mo.os_id = obra_os.os_id', 'left');
        $this->db->where('obra_os.obra_id', $obraId);
        $this->db->where('obra_os.fatura_no_projeto', 1);
        $this->db->where('os.status', 'Finalizado');
        $this->db->where('os.faturado !=', 1);
        $this->db->where('mo.idMedicaoOs IS NULL', null, false);
        $this->db->order_by('os.idOs', 'ASC');
        $lista = $this->db->get()->result();

        $descontos = [];
        foreach ($lista as $o) {
            $descontos[(int) $o->idOs] = $o->valor_desconto;
        }
        $valores = $this->valoresDasOs($descontos);

        foreach ($lista as $o) {
            $o->idOs = (int) $o->idOs;
            $o->tipo_vinculo = (string) ($o->tipo_vinculo ?: 'execucao');
            $o->valor = $valores[$o->idOs];
            $o->soma_valor = self::entraNoValor($o->tipo_vinculo);
            unset($o->valor_desconto); // so serviu para o valor; a previa (JSON) segue igual
        }
        return $lista;
    }

    /**
     * Previa da medicao (sem gravar). $osIds fora da lista de aguardando sao ignoradas.
     * Retorna etapas, os, valor_etapas, valor_os, valor_total e percentuais.
     */
    public function previaMedicao(int $obraId, array $percentuaisPorEtapa, array $osIds): array
    {
        $this->load->model('obras_model');
        $etapas = self::calcularEtapas($this->obras_model->getEtapas($obraId), $this->normalizarPercentuais($percentuaisPorEtapa));

        $osIds = array_map('intval', $osIds);
        $os = [];
        foreach ($this->osAguardandoMedicao($obraId) as $o) {
            if (in_array($o->idOs, $osIds, true)) {
                $os[] = $o;
            }
        }

        $valorEtapas = 0.0;
        foreach ($etapas as $e) {
            $valorEtapas += $e['valor'];
        }
        $anterior = self::percentualPonderado($etapas, 'percentual_anterior');
        $acumulado = self::percentualPonderado($etapas, 'percentual_atual');

        return [
            'etapas' => $etapas,
            'os' => $os,
            'valor_etapas' => round($valorEtapas, 2),
            'valor_os' => self::somaValorOs($os),
            'valor_total' => self::valorTotal($etapas, $os),
            'percentual_periodo' => round($acumulado - $anterior, 2),
            'percentual_acumulado' => $acumulado,
        ];
    }

    /**
     * Grava a medicao (aberta): itens das etapas, avanco das etapas e OS incluidas.
     * Retorna o id ou false (nada a medir, erro).
     */
    public function criarMedicao(int $obraId, array $dados, array $percentuaisPorEtapa, array $osIds, int $usuarioId)
    {
        $this->load->model('obras_model');
        if (! $this->obras_model->getObra($obraId)) {
            return false;
        }

        // Trava o projeto: duas medicoes simultaneas nao leem o mesmo "anterior" nem o mesmo numero
        $this->db->trans_start();
        $this->db->query('SELECT idObra FROM ' . $this->db->dbprefix('obras') . ' WHERE idObra = ? FOR UPDATE', [$obraId]);
        $p = $this->previaMedicao($obraId, $percentuaisPorEtapa, $osIds);

        $avancou = false;
        foreach ($p['etapas'] as $e) {
            if ($e['percentual_atual'] > $e['percentual_anterior']) {
                $avancou = true;
            }
        }
        if (! $avancou && ! $p['os']) {
            $this->db->trans_complete();

            return false;
        }

        $cab = [
            'obra_id' => $obraId,
            'numero' => $this->obras_model->proximoNumeroMedicao($obraId),
            'periodo_inicio' => $dados['periodo_inicio'] ?? null,
            'periodo_fim' => $dados['periodo_fim'] ?? null,
            'percentual_periodo' => $p['percentual_periodo'],
            'percentual_acumulado' => $p['percentual_acumulado'],
            'valor_medido' => $p['valor_etapas'],
            'status' => 'aberta',
            'data_cadastro' => date('Y-m-d H:i:s'),
        ];
        if ($this->db->field_exists('valor_total', 'obra_medicao')) {
            $cab['valor_os'] = $p['valor_os'];
            $cab['valor_total'] = $p['valor_total'];
        }

        $itens = [];
        foreach ($p['etapas'] as $e) {
            $itens[] = [
                'etapa_id' => $e['etapa_id'],
                'percentual_anterior' => $e['percentual_anterior'],
                'percentual_atual' => $e['percentual_atual'],
                'valor' => $e['valor'],
            ];
        }

        $id = (int) $this->obras_model->addMedicao($cab, $itens);
        foreach ($p['etapas'] as $e) {
            if ($e['percentual_atual'] > $e['percentual_anterior']) {
                $this->obras_model->editEtapa($e['etapa_id'], ['percentual_concluido' => $e['percentual_atual']], $obraId);
            }
        }
        foreach ($p['os'] as $o) {
            $this->db->insert('obra_medicao_os', [
                'medicao_id' => $id,
                'os_id' => $o->idOs,
                'valor' => $o->valor,
                'tipo_vinculo' => $o->tipo_vinculo,
            ]);
        }
        $this->obras_model->recalcularProgresso($obraId);
        $this->db->trans_complete();

        if ($this->db->trans_status() === false || ! $id) {
            return false;
        }
        log_info('Medicao ' . $cab['numero'] . ' do projeto ' . $obraId . ' registrada (usuario ' . $usuarioId . ', ' . count($p['os']) . ' OS)');
        return $id;
    }

    /** OS incluidas na medicao: idOs, status atual, tipo_vinculo, etapa_nome, valor, soma_valor. */
    public function getOsDaMedicao(int $medicaoId): array
    {
        if (! $this->db->table_exists('obra_medicao_os')) {
            return [];
        }
        $this->db->select('mo.os_id as idOs, mo.valor, mo.tipo_vinculo, os.status, etapa.nome as etapa_nome');
        $this->db->from('obra_medicao_os mo');
        $this->db->join('os', 'os.idOs = mo.os_id', 'left');
        $this->db->join('obra_os', 'obra_os.os_id = mo.os_id', 'left');
        $this->db->join('obra_etapas etapa', 'etapa.idEtapa = obra_os.etapa_id', 'left');
        $this->db->where('mo.medicao_id', $medicaoId);
        $this->db->order_by('mo.os_id', 'ASC');
        $lista = $this->db->get()->result();
        foreach ($lista as $o) {
            $o->idOs = (int) $o->idOs;
            $o->valor = (float) $o->valor;
            $o->tipo_vinculo = (string) $o->tipo_vinculo;
            $o->soma_valor = self::entraNoValor($o->tipo_vinculo);
        }
        return $lista;
    }

    /**
     * Fatura a medicao aprovada: um lancamento de receita (como Os::faturar) e as OS
     * da medicao viram Faturado. Tudo em transacao.
     * $opcoes: vencimento (Y-m-d), forma_pagamento.
     */
    public function faturarMedicao(int $medicaoId, int $usuarioId, array $opcoes): array
    {
        $erro = function ($msg) {
            return ['ok' => false, 'lancamento_id' => null, 'erro' => $msg];
        };
        if (! $this->db->field_exists('lancamento_id', 'obra_medicao')) {
            return $erro('Faturamento por medição indisponível: rode as migrações.');
        }
        $this->load->model('obras_model');
        $this->load->model('os_model');

        $m = $this->obras_model->getMedicao($medicaoId);
        if (! $m) {
            return $erro('Medição não encontrada.');
        }
        if ($m->status !== 'aprovada' || ! empty($m->lancamento_id)) {
            return $erro('Só é possível faturar medição aprovada e ainda não faturada.');
        }
        $obra = $this->obras_model->getObra((int) $m->obra_id);
        if (! $obra || empty($obra->clientes_id)) {
            return $erro('O projeto não tem cliente para o lançamento.');
        }
        $valor = round((float) $m->valor_total > 0 ? (float) $m->valor_total : (float) $m->valor_medido + (float) $m->valor_os, 2);
        if ($valor <= 0) {
            return $erro('Medição sem valor a faturar.');
        }
        $venc = (string) ($opcoes['vencimento'] ?? '');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $venc)) {
            $venc = date('Y-m-d');
        }

        $os = $this->getOsDaMedicao($medicaoId);
        $ids = array_map(function ($o) { return '#' . $o->idOs; }, $os);

        // mesmos campos de Os::faturar (sem desconto: valor_desconto = valor cheio)
        $lanc = [
            'descricao' => 'Medição ' . (int) $m->numero . ' - ' . $obra->nome,
            'valor' => $valor,
            'tipo_desconto' => 'real',
            'desconto' => 0,
            'valor_desconto' => $valor,
            'clientes_id' => (int) $obra->clientes_id,
            'data_vencimento' => $venc,
            'data_pagamento' => null,
            'baixado' => 0,
            'cliente_fornecedor' => (string) $obra->nomeCliente,
            'forma_pgto' => (string) ($opcoes['forma_pagamento'] ?? ''),
            'tipo' => 'receita',
            'observacoes' => 'Projeto #' . (int) $obra->idObra . ($ids ? ' - OS ' . implode(', ', $ids) : ''),
            'usuarios_id' => $usuarioId,
        ];

        $this->db->trans_start();
        $this->db->insert('lancamentos', $lanc);
        $lancId = (int) $this->db->insert_id();

        $this->db->where('idMedicao', $medicaoId)->where('status', 'aprovada')
            ->update('obra_medicao', ['status' => 'faturada', 'lancamento_id' => $lancId]);
        $atualizou = $this->db->affected_rows() === 1;

        $temLancOs = $this->db->field_exists('lancamento', 'os');
        foreach ($os as $o) {
            if (in_array($o->status, ['Faturado', 'Cancelado'], true)) {
                continue;
            }
            $t = $this->os_model->valorTotalOS($o->idOs);
            $extra = ['faturado' => 1, 'valorTotal' => (float) $t['totalServico'] + (float) $t['totalProdutos']];
            if ($temLancOs) {
                // a OS aponta para o lancamento da medicao
                $extra['lancamento'] = $lancId;
            }
            $this->os_model->alterarStatus($o->idOs, 'Faturado', $extra);
        }

        if (! $lancId || ! $atualizou) {
            $this->db->trans_rollback();
            return $erro('Não foi possível faturar a medição.');
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return $erro('Não foi possível faturar a medição.');
        }

        // NFS-e e cobranca (Cora) da medicao ficam fora desta fase: emitir pelo financeiro.
        log_info('Faturou a medicao ' . (int) $m->numero . ' do projeto ' . (int) $obra->idObra . '. Lancamento: ' . $lancId);
        return ['ok' => true, 'lancamento_id' => $lancId, 'erro' => null];
    }

    /** Numeros financeiros do projeto para o painel. */
    public function resumoFinanceiro(int $obraId): array
    {
        $out = [
            'contrato' => 0.0, 'medido' => 0.0, 'aprovado' => 0.0, 'faturado' => 0.0,
            'saldo_a_medir' => 0.0, 'os_aguardando' => 0, 'medicoes_aprovadas_nao_faturadas' => 0,
        ];
        $this->load->model('obras_model');
        $obra = $this->obras_model->getObra($obraId);
        if (! $obra) {
            return $out;
        }
        $contrato = (float) ($obra->valor_contrato ?? 0);
        if ($contrato <= 0) {
            foreach ($this->obras_model->getEtapas($obraId) as $e) {
                $contrato += (float) $e->valor_previsto;
            }
        }
        $out['contrato'] = round($contrato, 2);

        $temTotal = $this->db->field_exists('valor_total', 'obra_medicao');
        $etapasMedidas = 0.0;
        foreach ($this->obras_model->getMedicoes($obraId) as $m) {
            $v = (float) $m->valor_medido;
            if ($temTotal && (float) $m->valor_total > 0) {
                $v = (float) $m->valor_total;
            }
            $etapasMedidas += (float) $m->valor_medido;
            $out['medido'] += $v;
            if (in_array($m->status, ['aprovada', 'faturada'], true)) {
                $out['aprovado'] += $v;
            }
            if ($m->status === 'faturada') {
                $out['faturado'] += $v;
            }
            if ($m->status === 'aprovada') {
                $out['medicoes_aprovadas_nao_faturadas']++;
            }
        }
        foreach (['medido', 'aprovado', 'faturado'] as $k) {
            $out[$k] = round($out[$k], 2);
        }
        // adicionais ficam fora do contrato: o saldo considera so as etapas
        $out['saldo_a_medir'] = round(max(0.0, $contrato - $etapasMedidas), 2);
        $out['os_aguardando'] = count($this->osAguardandoMedicao($obraId));
        return $out;
    }

    /** [etapa_id => percentual] so com chaves validas. */
    private function normalizarPercentuais(array $p): array
    {
        $out = [];
        foreach ($p as $k => $v) {
            if ((int) $k > 0 && $v !== '' && $v !== null) {
                $out[(int) $k] = (float) str_replace(',', '.', (string) $v);
            }
        }
        return $out;
    }
}
