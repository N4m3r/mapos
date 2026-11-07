<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Regras puras (sem banco/sessao) do atendimento da OS, testaveis isoladamente.
 */
class AtendimentoRegras
{
    /**
     * Quem pode remover uma foto do atendimento.
     * Gestor (eOs) remove qualquer uma. Tecnico (eTecnicoFotos) so remove a que ele
     * mesmo enviou, e apenas em OS em que e responsavel ou tem check-in.
     */
    public static function podeRemoverFoto(bool $temEOs, bool $temTecnicoFotos, int $usuarioId, int $fotoUsuarioId, bool $vinculadoOs): bool
    {
        if ($temEOs) {
            return true;
        }

        return $temTecnicoFotos && $usuarioId > 0 && $fotoUsuarioId === $usuarioId && $vinculadoOs;
    }

    /**
     * checkin_id enviado com a foto: deve ser da mesma OS e, se nao for gestor, do proprio tecnico.
     * Sem checkin_id (vazio) e aceito.
     */
    public static function checkinValidoParaFoto($checkinOsId, $checkinUsuarioId, int $osId, int $usuarioId, bool $gestor): bool
    {
        if ($checkinOsId === null) {
            return false;
        }
        if ((int) $checkinOsId !== $osId) {
            return false;
        }

        return $gestor || (int) $checkinUsuarioId === $usuarioId;
    }

    /**
     * OS em estado terminal nao aceita novo atendimento. A tela esconde o botao,
     * mas o endpoint precisa recusar o POST direto.
     */
    public static function statusBloqueiaAtendimento(?string $status): bool
    {
        return in_array($status, ['Finalizado', 'Faturado', 'Cancelado'], true);
    }

    /**
     * Status em que o tecnico pode iniciar um atendimento.
     *
     * "Aprovado" entra aqui porque e o status que o administrativo aplica para
     * liberar um novo atendimento numa OS ja finalizada (reabertura controlada).
     */
    const STATUS_INICIA_ATENDIMENTO = ['Aberto', 'Aprovado', 'Em Andamento'];

    /** Status que o administrativo usa para reabrir uma OS finalizada. */
    const STATUS_REABRE_ATENDIMENTO = ['Aprovado'];

    /** O tecnico pode iniciar atendimento neste status? */
    public static function statusPermiteIniciarAtendimento(?string $status): bool
    {
        return in_array($status, self::STATUS_INICIA_ATENDIMENTO, true);
    }

    /** Este status veio de uma reabertura feita pelo administrativo? */
    public static function statusReabreAtendimento(?string $status): bool
    {
        return in_array($status, self::STATUS_REABRE_ATENDIMENTO, true);
    }

    /**
     * A assinatura ja coletada impede iniciar outro atendimento?
     *
     * Na OS comum impede: o servico ja foi assinado e o botao nao deve voltar por
     * engano. Na OS reaberta pelo administrativo nao impede: as assinaturas
     * antigas ficam no historico da OS e o novo atendimento coleta as suas.
     */
    public static function assinaturaBloqueiaInicio(?string $status, bool $temAssinatura): bool
    {
        return $temAssinatura && ! self::statusReabreAtendimento($status);
    }

    /**
     * Data/hora de agendamento ao reagendar: nova data + hora do agendamento anterior
     * (se houver), senao 08:00:00.
     */
    public static function dataAgendamentoReagendada(string $novaData, ?string $agendamentoAnterior): string
    {
        $dia = date('Y-m-d', strtotime($novaData));
        $hora = '08:00:00';
        if ($agendamentoAnterior && ($ts = strtotime($agendamentoAnterior)) !== false) {
            $h = date('H:i:s', $ts);
            if ($h !== '00:00:00') {
                $hora = $h;
            }
        }

        return $dia . ' ' . $hora;
    }
}
