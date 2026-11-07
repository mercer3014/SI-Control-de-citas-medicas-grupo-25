<?php

namespace App\Services;

use App\Models\MedicoEspecialidadModel;
use App\Models\HorarioAtencionModel;
use App\Models\AusenciaMedicaModel;
use App\Models\CitaModel;
use App\Models\EstadoCitaModel;
use Carbon\Carbon;

class DisponibilidadCitaService
{
    /**
     * Calcula los slots horarios disponibles para una fecha y médico-especialidad.
     */
    public function obtenerSlotsDisponibles(int $medicoEspecialidadId, string $fecha, ?int $excluirCitaId = null): array
    {
        $fechaCarbon = Carbon::parse($fecha);
        $diaSemanaId = $fechaCarbon->dayOfWeekIso; // 1 = Lunes, 7 = Domingo

        $medEsp = MedicoEspecialidadModel::find($medicoEspecialidadId);
        if (!$medEsp) {
            return [];
        }

        // 1. Verificar si el médico tiene una ausencia médica en esta fecha
        $enAusencia = AusenciaMedicaModel::where('medico_id', $medEsp->medico_id)
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->whereDate('fecha_fin', '>=', $fecha)
            ->exists();

        if ($enAusencia) {
            return [];
        }

        // 2. Obtener franjas horarias configuradas para este día de la semana
        $horarios = HorarioAtencionModel::where('medico_especialidad_id', $medicoEspecialidadId)
            ->where('dia_id', $diaSemanaId)
            ->where('estado', true)
            ->get();

        if ($horarios->isEmpty()) {
            return [];
        }

        // 3. Obtener citas ocupadas en esa fecha (Programadas o Confirmadas)
        $citasOcupadas = CitaModel::where('medico_especialidad_id', $medicoEspecialidadId)
            ->whereDate('fecha', $fecha)
            ->whereIn('estado_id', [EstadoCitaModel::PROGRAMADA, EstadoCitaModel::CONFIRMADA])
            ->when($excluirCitaId, function ($query, $id) {
                return $query->where('id', '!=', $id);
            })
            ->get(['hora_inicio', 'hora_fin']);

        $slotsDisponibles = [];

        foreach ($horarios as $h) {
            $intervalo = $h->intervalo_minutos ?: 30;
            $inicio = Carbon::parse($fecha . ' ' . substr($h->hora_inicio, 0, 5));
            $fin = Carbon::parse($fecha . ' ' . substr($h->hora_fin, 0, 5));

            $actual = $inicio->copy();
            while ($actual->copy()->addMinutes($intervalo)->lte($fin)) {
                $slotInicio = $actual->format('H:i');
                $actual->addMinutes($intervalo);
                $slotFin = $actual->format('H:i');

                // Comprobar si choca con alguna cita ocupada
                $ocupado = false;
                foreach ($citasOcupadas as $c) {
                    $cIni = substr($c->hora_inicio, 0, 5);
                    $cFin = substr($c->hora_fin, 0, 5);

                    if ($slotInicio < $cFin && $slotFin > $cIni) {
                        $ocupado = true;
                        break;
                    }
                }

                if (!$ocupado) {
                    $slotsDisponibles[] = [
                        'inicio' => $slotInicio,
                        'fin' => $slotFin,
                        'label' => "{$slotInicio} - {$slotFin}",
                    ];
                }
            }
        }

        return $slotsDisponibles;
    }
}
