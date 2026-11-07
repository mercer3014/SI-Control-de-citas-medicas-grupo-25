<?php

namespace App\Http\Controllers;

use App\Models\CitaModel;
use App\Models\EstadoCitaModel;
use App\Models\PacienteModel;
use App\Models\MedicoModel;
use App\Models\EspecialidadModel;
use App\Services\ExportExcelService;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin = $request->input('fecha_fin', now()->endOfMonth()->toDateString());

        // Base query para el rango de fechas
        $citasQuery = CitaModel::with(['medicoEspecialidad.medico.persona', 'medicoEspecialidad.especialidad'])
            ->whereDate('fecha', '>=', $fechaInicio)
            ->whereDate('fecha', '<=', $fechaFin);

        $totalCitas = (clone $citasQuery)->count();
        $totalAtendidas = (clone $citasQuery)->where('estado_id', EstadoCitaModel::ATENDIDA)->count();
        $totalCanceladas = (clone $citasQuery)->where('estado_id', EstadoCitaModel::CANCELADA)->count();
        $tasaCumplimiento = $totalCitas > 0 ? round(($totalAtendidas / $totalCitas) * 100, 1) : 0;

        // Calcular recaudación sumando los aranceles de las citas atendidas
        $citasAtendidas = (clone $citasQuery)->where('estado_id', EstadoCitaModel::ATENDIDA)->get();
        $ingresosTotales = $citasAtendidas->sum(function ($c) {
            return (float)($c->medicoEspecialidad?->precio_consulta ?? 0);
        });

        // Rendimiento por médico
        $medicos = MedicoModel::with('persona')->where('estado', true)->get();
        $rendimientoMedicos = [];

        foreach ($medicos as $m) {
            $citasMedico = (clone $citasQuery)->whereHas('medicoEspecialidad', function ($q) use ($m) {
                $q->where('medico_id', $m->id);
            })->get();

            $atendidas = $citasMedico->where('estado_id', EstadoCitaModel::ATENDIDA)->count();
            $recaudado = $citasMedico->where('estado_id', EstadoCitaModel::ATENDIDA)->sum(function ($c) {
                return (float)($c->medicoEspecialidad?->precio_consulta ?? 0);
            });

            if ($citasMedico->count() > 0 || $m->id <= 5) {
                $rendimientoMedicos[] = [
                    'medico_id' => $m->id,
                    'nombre' => 'Dr(a). ' . ($m->persona?->nombre_completo ?? 'N/A'),
                    'matricula' => $m->matricula_profesional,
                    'total_citas' => $citasMedico->count(),
                    'atendidas' => $atendidas,
                    'ingresos' => $recaudado,
                ];
            }
        }

        // Demanda por especialidad
        $especialidades = EspecialidadModel::where('estado', true)->get();
        $demandaEspecialidades = [];

        foreach ($especialidades as $esp) {
            $count = (clone $citasQuery)->whereHas('medicoEspecialidad', function ($q) use ($esp) {
                $q->where('especialidad_id', $esp->id);
            })->count();

            $porcentaje = $totalCitas > 0 ? round(($count / $totalCitas) * 100, 1) : 0;

            $demandaEspecialidades[] = [
                'especialidad_id' => $esp->id,
                'nombre' => $esp->nombre,
                'total_citas' => $count,
                'porcentaje' => $porcentaje,
            ];
        }

        return Inertia::render('Reportes/Index', [
            'metricas' => [
                'total_citas' => $totalCitas,
                'total_atendidas' => $totalAtendidas,
                'total_canceladas' => $totalCanceladas,
                'tasa_cumplimiento' => $tasaCumplimiento,
                'ingresos_totales' => $ingresosTotales,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ],
            'rendimientoMedicos' => $rendimientoMedicos,
            'demandaEspecialidades' => $demandaEspecialidades,
            'filters' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ],
        ]);
    }

    public function exportCitas(Request $request, ExportExcelService $excel)
    {
        $citas = CitaModel::with([
            'paciente.persona',
            'medicoEspecialidad.medico.persona',
            'medicoEspecialidad.especialidad',
            'estado',
            'atencion',
        ])->latest('fecha')->get();

        BitacoraService::registrar('EXPORTACION_EXCEL', 'Reportes', 'Exportación de citas a Excel (.xlsx)');

        return $excel->exportarCitas($citas);
    }

    public function exportPacientes(Request $request, ExportExcelService $excel)
    {
        $pacientes = PacienteModel::with('persona')->latest()->get();

        BitacoraService::registrar('EXPORTACION_EXCEL', 'Reportes', 'Exportación del padrón de pacientes a Excel (.xlsx)');

        return $excel->exportarPacientes($pacientes);
    }

    public function exportGeneral(Request $request, ExportExcelService $excel)
    {
        $fechaInicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin = $request->input('fecha_fin', now()->endOfMonth()->toDateString());

        $citasQuery = CitaModel::with(['medicoEspecialidad.medico.persona', 'medicoEspecialidad.especialidad'])
            ->whereDate('fecha', '>=', $fechaInicio)
            ->whereDate('fecha', '<=', $fechaFin);

        $totalCitas = (clone $citasQuery)->count();
        $totalAtendidas = (clone $citasQuery)->where('estado_id', EstadoCitaModel::ATENDIDA)->count();
        $totalCanceladas = (clone $citasQuery)->where('estado_id', EstadoCitaModel::CANCELADA)->count();
        $tasaCumplimiento = $totalCitas > 0 ? round(($totalAtendidas / $totalCitas) * 100, 1) : 0;

        $citasAtendidas = (clone $citasQuery)->where('estado_id', EstadoCitaModel::ATENDIDA)->get();
        $ingresosTotales = $citasAtendidas->sum(function ($c) {
            return (float)($c->medicoEspecialidad?->precio_consulta ?? 0);
        });

        $metricas = [
            'total_citas' => $totalCitas,
            'total_atendidas' => $totalAtendidas,
            'total_canceladas' => $totalCanceladas,
            'tasa_cumplimiento' => $tasaCumplimiento,
            'ingresos_totales' => $ingresosTotales,
        ];

        BitacoraService::registrar('EXPORTACION_EXCEL', 'Reportes', 'Exportación de balance y KPIs a Excel (.xlsx)');

        return $excel->exportarReporteGeneral($metricas, [], []);
    }
}
