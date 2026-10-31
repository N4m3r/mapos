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
