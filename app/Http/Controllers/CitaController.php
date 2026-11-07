<?php

namespace App\Http\Controllers;

use App\Models\CitaModel;
use App\Models\EstadoCitaModel;
use App\Models\MedicoModel;
use App\Models\EspecialidadModel;
use App\Models\PacienteModel;
use App\Models\MedicoEspecialidadModel;
use App\Services\BitacoraService;
use App\Services\DisponibilidadCitaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CitaController extends Controller
{
    public function index(Request $request)
    {
        $query = CitaModel::with([
            'paciente.persona',
            'medicoEspecialidad.medico.persona',
            'medicoEspecialidad.especialidad',
            'estado',
            'atencion',
        ])->latest('fecha')->latest('hora_inicio');

        if ($request->filled('buscar')) {
            $term = '%' . trim($request->buscar) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('codigo_reserva', 'ilike', $term)
                  ->orWhere('motivo_consulta', 'ilike', $term)
                  ->orWhereHas('paciente.persona', function ($qp) use ($term) {
                      $qp->where('nombres', 'ilike', $term)
                         ->orWhere('apellidos', 'ilike', $term)
                         ->orWhere('ci', 'ilike', $term);
                  });
            });
        }

        if ($request->filled('medico_id')) {
            $query->whereHas('medicoEspecialidad', function ($q) use ($request) {
                $q->where('medico_id', $request->medico_id);
            });
        }

        if ($request->filled('especialidad_id')) {
            $query->whereHas('medicoEspecialidad', function ($q) use ($request) {
                $q->where('especialidad_id', $request->especialidad_id);
            });
        }

        if ($request->filled('estado_id')) {
            $query->where('estado_id', $request->estado_id);
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        $citas = $query->paginate(15)->withQueryString();

        $medicos = MedicoModel::with(['persona', 'medicoEspecialidades.especialidad'])->where('estado', true)->get();
        $especialidades = EspecialidadModel::where('estado', true)->get();
        $estados = EstadoCitaModel::orderBy('id')->get();
        $pacientes = PacienteModel::with('persona')->where('estado', true)->get();

        return Inertia::render('Citas/Index', [
            'citas' => $citas,
            'medicos' => $medicos,
            'especialidades' => $especialidades,
            'estados' => $estados,
            'pacientes' => $pacientes,
            'filters' => $request->only(['buscar', 'medico_id', 'especialidad_id', 'estado_id', 'fecha']),
        ]);
    }

    public function slots(Request $request, DisponibilidadCitaService $service)
    {
        $request->validate([
            'medico_especialidad_id' => 'required|exists:medico_especialidad,id',
            'fecha' => 'required|date',
            'excluir_cita_id' => 'nullable|integer',
        ]);

        $slots = $service->obtenerSlotsDisponibles(
            (int)$request->medico_especialidad_id,
            $request->fecha,
            $request->excluir_cita_id ? (int)$request->excluir_cita_id : null
        );

        return response()->json($slots);
    }

    public function store(Request $request)
    {
        $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'medico_especialidad_id' => 'required|exists:medico_especialidad,id',
            'fecha' => 'required|date',
            'hora_inicio' => 'required',
            'hora_fin' => 'required',
            'motivo_consulta' => 'required|string|max:500',
        ], [
            'motivo_consulta.required' => 'El motivo de la consulta es obligatorio.',
        ]);

        // Generar código único de reserva (CIT-XXXX)
        do {
            $codigo = 'CIT-' . strtoupper(Str::random(6));
        } while (CitaModel::where('codigo_reserva', $codigo)->exists());

        $horaInicio = substr($request->hora_inicio, 0, 5);
        $horaFin = substr($request->hora_fin, 0, 5);

        $cita = CitaModel::create([
            'codigo_reserva' => $codigo,
            'paciente_id' => $request->paciente_id,
            'medico_especialidad_id' => $request->medico_especialidad_id,
            'estado_id' => EstadoCitaModel::PROGRAMADA,
            'fecha' => $request->fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'motivo_consulta' => trim($request->motivo_consulta),
            'fecha_registro' => now(),
        ]);

        $paciente = PacienteModel::with('persona')->find($request->paciente_id);
        $medEsp = MedicoEspecialidadModel::with(['medico.persona', 'especialidad'])->find($request->medico_especialidad_id);

        BitacoraService::registrar(
            'PROGRAMACION_CITA',
            'Citas',
            "Cita agendada ({$codigo}) para {$paciente?->persona?->nombre_completo} con Dr(a). {$medEsp?->medico?->persona?->nombre_completo} el {$request->fecha} a las {$horaInicio}"
        );

        return back()->with('success', "Cita programada exitosamente. Código: {$codigo}");
    }

    public function reprogramar(Request $request, CitaModel $cita)
    {
        $request->validate([
            'fecha' => 'required|date',
            'hora_inicio' => 'required',
            'hora_fin' => 'required',
        ]);

        $horaInicio = substr($request->hora_inicio, 0, 5);
        $horaFin = substr($request->hora_fin, 0, 5);

        $cita->update([
            'fecha' => $request->fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
        ]);

        BitacoraService::registrar(
            'REPROGRAMACION_CITA',
            'Citas',
            "Cita {$cita->codigo_reserva} reprogramada para el {$request->fecha} a las {$horaInicio}"
        );

        return back()->with('success', "Cita {$cita->codigo_reserva} reprogramada exitosamente.");
    }

    public function cancelar(Request $request, CitaModel $cita)
    {
        $request->validate([
            'motivo_cancelacion' => 'required|string|min:5|max:255',
        ], [
            'motivo_cancelacion.required' => 'Debes especificar el motivo de la cancelación de la cita.',
            'motivo_cancelacion.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $cita->update([
            'estado_id' => EstadoCitaModel::CANCELADA,
            'motivo_cancelacion' => trim($request->motivo_cancelacion),
        ]);

        BitacoraService::registrar(
            'CANCELACION_CITA',
            'Citas',
            "Cita {$cita->codigo_reserva} cancelada. Motivo: {$request->motivo_cancelacion}"
        );

        return back()->with('success', "Cita {$cita->codigo_reserva} cancelada exitosamente.");
    }
}
