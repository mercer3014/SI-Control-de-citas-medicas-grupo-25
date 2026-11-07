<?php

namespace App\Http\Controllers;

use App\Models\AtencionClinicaModel;
use App\Models\CitaModel;
use App\Models\EstadoCitaModel;
use App\Models\MedicoModel;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AtencionClinicaController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = CitaModel::with([
            'paciente.persona',
            'paciente.antecedentes',
            'medicoEspecialidad.medico.persona',
            'medicoEspecialidad.especialidad',
            'estado',
            'atencion',
        ])->latest('fecha')->latest('hora_inicio');

        // Si el usuario es médico, filtrar únicamente sus citas
        if ($user->persona_id && $user->rol && strtolower($user->rol->nombre) === 'médico') {
            $medico = MedicoModel::where('persona_id', $user->persona_id)->first();
            if ($medico) {
                $query->whereHas('medicoEspecialidad', function ($q) use ($medico) {
                    $q->where('medico_id', $medico->id);
                });
            }
        } elseif ($request->filled('medico_id')) {
            $query->whereHas('medicoEspecialidad', function ($q) use ($request) {
                $q->where('medico_id', $request->medico_id);
            });
        }

        // Filtro por fecha (por defecto hoy si no se especifica, o todas)
        $fecha = $request->input('fecha', now()->toDateString());
        if ($fecha !== 'todas') {
            $query->whereDate('fecha', $fecha);
        }

        if ($request->filled('estado_id')) {
            $query->where('estado_id', $request->estado_id);
        }

        $citas = $query->paginate(15)->withQueryString();
        $medicos = MedicoModel::with('persona')->where('estado', true)->get();

        return Inertia::render('Atenciones/Index', [
            'citas' => $citas,
            'medicos' => $medicos,
            'filters' => [
                'fecha' => $fecha,
                'medico_id' => $request->medico_id,
                'estado_id' => $request->estado_id,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cita_id' => 'required|exists:citas,id',
            'diagnostico' => 'required|string|min:5',
            'tratamiento' => 'nullable|string',
            'notas_adicionales' => 'nullable|string',
        ], [
            'diagnostico.required' => 'El diagnóstico médico es obligatorio.',
            'diagnostico.min' => 'El diagnóstico debe tener al menos 5 caracteres.',
        ]);

        $cita = CitaModel::with(['paciente.persona', 'medicoEspecialidad.medico.persona'])->findOrFail($request->cita_id);

        DB::transaction(function () use ($request, $cita) {
            AtencionClinicaModel::create([
                'cita_id' => $cita->id,
                'diagnostico' => trim($request->diagnostico),
                'tratamiento' => $request->tratamiento ? trim($request->tratamiento) : null,
                'notas_adicionales' => $request->notas_adicionales ? trim($request->notas_adicionales) : null,
                'fecha_atencion' => now(),
            ]);

            $cita->update([
                'estado_id' => EstadoCitaModel::ATENDIDA,
            ]);

            BitacoraService::registrar(
                'REGISTRO_ATENCION',
                'Atenciones',
                "Atención registrada para paciente {$cita->paciente?->persona?->nombre_completo} (Cita: {$cita->codigo_reserva})"
            );
        });

        return back()->with('success', 'Atención clínica y diagnóstico registrados exitosamente.');
    }
}
