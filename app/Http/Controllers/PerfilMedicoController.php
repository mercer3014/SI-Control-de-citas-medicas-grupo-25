<?php

namespace App\Http\Controllers;

use App\Models\MedicoModel;
use App\Models\EspecialidadModel;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PerfilMedicoController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $medico = null;

        if ($user->persona_id) {
            $medico = MedicoModel::with(['persona', 'especialidades', 'medicoEspecialidades.horarios.dia'])
                ->where('persona_id', $user->persona_id)
                ->first();
        }

        if (!$medico) {
            $medico = MedicoModel::with(['persona', 'especialidades', 'medicoEspecialidades.horarios.dia'])->first();
        }

        if (!$medico) {
            return redirect()->route('medicos.index')->with('error', 'No se encontró un perfil médico asociado.');
        }

        return Inertia::render('Medicos/Perfil', [
            'medico' => $medico,
            'esMiPerfil' => true,
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $medico = null;

        if ($user->persona_id) {
            $medico = MedicoModel::where('persona_id', $user->persona_id)->first();
        }

        if (!$medico && $user->rol && strtolower($user->rol->nombre) === 'administrador') {
            $medico = MedicoModel::first();
        }

        if (!$medico) {
            return back()->with('error', 'Perfil médico no encontrado.');
        }

        $persona = $medico->persona;

        $request->validate([
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:200',
            'especialidades' => 'nullable|array',
            'especialidades.*.id' => 'required_with:especialidades|exists:especialidades,id',
            'especialidades.*.precio' => 'required_with:especialidades|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $medico, $persona) {
            if ($persona) {
                $persona->update([
                    'telefono' => $request->telefono ? trim($request->telefono) : null,
                    'direccion' => $request->direccion ? trim($request->direccion) : null,
                ]);
            }

            if ($request->has('especialidades')) {
                $syncData = [];
                foreach ($request->especialidades as $esp) {
                    $syncData[$esp['id']] = ['precio_consulta' => $esp['precio'] ?? 0];
                }
                $medico->especialidades()->sync($syncData);
            }

            BitacoraService::registrar(
                'ACTUALIZACION_PERFIL_MEDICO',
                'Médicos',
                "Perfil médico actualizado: Dr(a). " . ($persona?->nombre_completo ?? $medico->matricula_profesional)
            );
        });

        return back()->with('success', 'Perfil médico actualizado correctamente.');
    }
}
