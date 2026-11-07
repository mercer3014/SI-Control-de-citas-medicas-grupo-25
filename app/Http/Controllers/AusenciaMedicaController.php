<?php

namespace App\Http\Controllers;

use App\Models\AusenciaMedicaModel;
use App\Models\MedicoModel;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AusenciaMedicaController extends Controller
{
    public function index(Request $request)
    {
        $query = AusenciaMedicaModel::with(['medico.persona', 'medico.especialidades'])->latest('fecha_inicio');

        if ($request->filled('medico_id')) {
            $query->where('medico_id', $request->medico_id);
        }

        if ($request->filled('vigente')) {
            if ($request->boolean('vigente')) {
                $query->where('fecha_fin', '>=', now()->toDateString());
            } else {
                $query->where('fecha_fin', '<', now()->toDateString());
            }
        }

        $ausencias = $query->paginate(15)->withQueryString();
        $medicos = MedicoModel::with('persona')->where('estado', true)->get();

        return Inertia::render('Ausencias/Index', [
            'ausencias' => $ausencias,
            'medicos' => $medicos,
            'filters' => $request->only(['medico_id', 'vigente']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'medico_id' => 'required|exists:medicos,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'motivo' => 'required|string|max:255',
        ], [
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
            'motivo.required' => 'El motivo de la ausencia es obligatorio.',
        ]);

        $ausencia = AusenciaMedicaModel::create([
            'medico_id' => $request->medico_id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'motivo' => trim($request->motivo),
        ]);

        $medico = MedicoModel::with('persona')->find($request->medico_id);

        BitacoraService::registrar(
            'REGISTRO_AUSENCIA',
            'Ausencias',
            "Ausencia registrada para Dr(a). {$medico?->persona?->nombre_completo}: del {$request->fecha_inicio} al {$request->fecha_fin}. Motivo: {$request->motivo}"
        );

        return back()->with('success', 'Ausencia médica registrada exitosamente.');
    }

    public function destroy(AusenciaMedicaModel $ausencia)
    {
        $medicoNombre = $ausencia->medico?->persona?->nombre_completo ?? 'Médico';
        $periodo = "del {$ausencia->fecha_inicio?->format('Y-m-d')} al {$ausencia->fecha_fin?->format('Y-m-d')}";
        
        $ausencia->delete();

        BitacoraService::registrar(
            'CANCELACION_AUSENCIA',
            'Ausencias',
            "Ausencia de {$medicoNombre} ({$periodo}) cancelada y eliminada."
        );

        return back()->with('success', 'Ausencia médica cancelada exitosamente.');
    }
}
