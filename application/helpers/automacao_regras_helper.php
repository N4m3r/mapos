<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Regras puras da automação de faturamento (sem banco/sessão), testáveis
 * isoladamente.
 *
 * A biblioteca Autoaprovacao e o controller Automacao chamam estas funções em
 * vez de repetir o cálculo. Assim o dia de faturamento, a decisão de agendar e
 * o resumo da fila têm um lugar só, e o teste cobre sem subir o CodeIgniter.
 */
class AutomacaoRegras
{
    /** Dia padrão de faturamento quando a configuração está vazia ou inválida. */
    const DIA_PADRAO = 1;

    /**
     * Dia do mês em que a fila é liberada, sempre entre 1 e 28.
     *
     * 29 a 31 ficam de fora de propósito: fevereiro não tem esses dias, e um
     * agendamento para o dia 31 nunca venceria em fevereiro.
     */
    public static function diaFaturamento($valor): int
    {
        $dia = (int) $valor;
        if ($dia < 1 || $dia > 28) {
            return self::DIA_PADRAO;
        }

        return $dia;
    }

    /**
     * Próxima ocorrência do dia de faturamento, no formato Y-m-d.
     *
     * Se o dia configurado ainda está à frente no mês corrente, usa o mês
     * corrente; senão, o mês seguinte. A data de referência entra como
     * parâmetro para o teste não depender do relógio.
     */
    public static function proximaDataFaturamento(int $dia, string $hoje): string
    {
        $dia = self::diaFaturamento($dia);
        $ref = \DateTimeImmutable::createFromFormat('Y-m-d', substr($hoje, 0, 10));
        if ($ref === false) {
            $ref = new \DateTimeImmutable('today');
        }

        $base = ((int) $ref->format('j') < $dia)
            ? $ref->modify('first day of this month')
            : $ref->modify('first day of next month');

        return $base->setDate(
            (int) $base->format('Y'),
            (int) $base->format('n'),
            $dia
        )->format('Y-m-d');
    }

    /**
     * A emissão desta OS deve ser segurada para o dia de faturamento?
     *
     * Só segura quando o cliente está marcado com "faturamento agendado" e hoje
     * ainda não é o dia configurado — no próprio dia faz sentido emitir na hora.
     */
    public static function deveAgendar($faturamentoAgendado, int $diaHoje, $diaFaturamento): bool
    {
        if (empty($faturamentoAgendado)) {
            return false;
        }

        return (int) $diaHoje !== self::diaFaturamento($diaFaturamento);
    }

    /**
     * Situação de um item da fila para exibição e ordenação.
     *
     * - 'erro'      item que esgotou as tentativas e precisa de decisão humana
     * - 'vencido'   aguardando com data já alcançada (emite no próximo pulso)
     * - 'futuro'    aguardando para uma data à frente
     * - 'resolvido' já processado ou cancelado
     */
    public static function situacaoItem($status, $dataAgendada, string $hoje): string
    {
        $status = (string) $status;

        if ($status === 'erro') {
            return 'erro';
        }
        if ($status !== 'aguardando') {
            return 'resolvido';
        }

        $data = substr((string) $dataAgendada, 0, 10);

        return ($data !== '' && $data <= $hoje) ? 'vencido' : 'futuro';
    }

    /** O item ainda aceita cancelamento manual? */
    public static function podeCancelar($status): bool
    {
        return in_array((string) $status, ['aguardando', 'erro'], true);
    }

    /** O item pode voltar para a fila (nova tentativa)? */
    public static function podeRetentar($status): bool
    {
        return (string) $status === 'erro';
    }

    /** Quantas tentativas restam antes de o item parar em erro. */
    public static function tentativasRestantes($tentativas, int $limite = 5): int
    {
        return max(0, $limite - (int) $tentativas);
    }

    /**
     * Contagens para os indicadores da tela.
     *
     * @param iterable $itens objetos ou arrays com status e data_agendada
     *
     * @return array{total:int,aguardando:int,vencidos:int,futuros:int,erro:int}
     */
    public static function resumoFila(iterable $itens, string $hoje): array
    {
        $r = ['total' => 0, 'aguardando' => 0, 'vencidos' => 0, 'futuros' => 0, 'erro' => 0];

        foreach ($itens as $item) {
            $status = is_array($item) ? ($item['status'] ?? '') : ($item->status ?? '');
            $data = is_array($item) ? ($item['data_agendada'] ?? '') : ($item->data_agendada ?? '');
            $situacao = self::situacaoItem($status, $data, $hoje);

            $r['total']++;
            if ($situacao === 'erro') {
                $r['erro']++;
                continue;
            }
            if ($situacao === 'resolvido') {
                continue;
            }

            $r['aguardando']++;
            if ($situacao === 'vencido') {
                $r['vencidos']++;
            } else {
                $r['futuros']++;
            }
        }

        return $r;
    }
}
