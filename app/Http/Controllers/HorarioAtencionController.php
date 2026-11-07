<?php

namespace App\Http\Controllers;

use App\Models\HorarioAtencionModel;
use App\Models\MedicoModel;
use App\Models\MedicoEspecialidadModel;
use App\Models\DiaSemanaModel;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HorarioAtencionController extends Controller
{
    public function index(Request $request)
    {
        $medicos = MedicoModel::with([
            'persona',
            'medicoEspecialidades.especialidad',
            'medicoEspecialidades.horarios.dia',
        ])
        ->where('estado', true)
        ->get();

        $dias = DiaSemanaModel::orderBy('id')->get();

        $selectedMedicoId = $request->input('medico_id', $medicos->first()?->id);

        return Inertia::render('Horarios/Index', [
            'medicos' => $medicos,
            'dias' => $dias,
            'selectedMedicoId' => $selectedMedicoId ? (int)$selectedMedicoId : null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'medico_especialidad_id' => 'required|exists:medico_especialidad,id',
            'dia_id' => 'required|exists:dias_semana,id',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'intervalo_minutos' => 'required|integer|min:10|max:120',
        ], [
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        $horaInicio = $request->hora_inicio;
        $horaFin = $request->hora_fin;

        // Comprobar traslapes en el mismo día y médico-especialidad
        $traslape = HorarioAtencionModel::where('medico_especialidad_id', $request->medico_especialidad_id)
            ->where('dia_id', $request->dia_id)
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->where('hora_inicio', '<', $horaFin)
                  ->where('hora_fin', '>', $horaInicio);
            })
            ->exists();

        if ($traslape) {
            return back()->withErrors(['hora_inicio' => 'El horario ingresado se traslapa con un bloque existente para este día.']);
        }

        $horario = HorarioAtencionModel::create([
            'medico_especialidad_id' => $request->medico_especialidad_id,
            'dia_id' => $request->dia_id,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'intervalo_minutos' => $request->intervalo_minutos,
            'estado' => true,
        ]);

        $medEsp = MedicoEspecialidadModel::with(['medico.persona', 'especialidad'])->find($request->medico_especialidad_id);
        $dia = DiaSemanaModel::find($request->dia_id);

        BitacoraService::registrar(
            'REGISTRO_HORARIO',
            'Horarios',
            "Horario agregado para Dr(a). {$medEsp?->medico?->persona?->nombre_completo} ({$medEsp?->especialidad?->nombre}): {$dia?->nombre} {$horaInicio} - {$horaFin}"
        );

        return back()->with('success', 'Horario de atención configurado exitosamente.');
    }

    public function update(Request $request, HorarioAtencionModel $horario)
    {
        $request->validate([
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'intervalo_minutos' => 'required|integer|min:10|max:120',
        ], [
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        $horaInicio = $request->hora_inicio;
        $horaFin = $request->hora_fin;

        // Comprobar traslapes excluyendo el propio horario
        $traslape = HorarioAtencionModel::where('medico_especialidad_id', $horario->medico_especialidad_id)
            ->where('dia_id', $horario->dia_id)
            ->where('id', '!=', $horario->id)
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->where('hora_inicio', '<', $horaFin)
                  ->where('hora_fin', '>', $horaInicio);
            })
            ->exists();

        if ($traslape) {
            return back()->withErrors(['hora_inicio' => 'El horario ingresado se traslapa con otro bloque existente para este día.']);
        }

        $horario->update([
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'intervalo_minutos' => $request->intervalo_minutos,
        ]);

        BitacoraService::registrar(
            'MODIFICACION_HORARIO',
            'Horarios',
            "Horario modificado (ID: {$horario->id}): {$horaInicio} - {$horaFin}"
        );

        return back()->with('success', 'Horario actualizado exitosamente.');
    }

    public function toggle(HorarioAtencionModel $horario)
    {
        $nuevoEstado = !$horario->estado;
        $horario->update(['estado' => $nuevoEstado]);

        BitacoraService::registrar(
            $nuevoEstado ? 'ACTIVACION_HORARIO' : 'DESACTIVACION_HORARIO',
            'Horarios',
            "Horario ID {$horario->id} cambiado a " . ($nuevoEstado ? 'Activo' : 'Inactivo')
        );

        return back()->with('success', "Horario " . ($nuevoEstado ? 'activado' : 'desactivado') . " correctamente.");
    }

    public function destroy(HorarioAtencionModel $horario)
    {
        $id = $horario->id;
        $horario->delete();

        BitacoraService::registrar('ELIMINACION_HORARIO', 'Horarios', "Horario ID {$id} eliminado del sistema.");

        return back()->with('success', 'Horario de atención eliminado exitosamente.');
    }
}
