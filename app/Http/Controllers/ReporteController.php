<?php

namespace App\Http\Controllers;

use App\Models\CitaModel;
use App\Models\EspecialidadModel;
use App\Models\EstadoCitaModel;
use App\Models\MedicoModel;
use App\Models\MetodoPagoModel;
use App\Models\PacienteModel;
use App\Models\PagoModel;
use App\Services\BitacoraService;
use App\Services\ExportExcelService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $reporte = $this->armarReporte($request);

        return Inertia::render('Reportes/Index', $reporte);
    }

    public function exportCitas(Request $request, ExportExcelService $excel)
    {
        $query = CitaModel::with([
            'paciente.persona',
            'medicoEspecialidad.medico.persona',
            'medicoEspecialidad.especialidad',
            'estado',
            'atencion',
        ]);

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha', '<=', $request->fecha_fin);
        }
        if ($request->filled('especialidad_id')) {
            $query->whereHas('medicoEspecialidad', fn ($q) => $q->where('especialidad_id', $request->especialidad_id));
        }
        if ($request->filled('medico_id')) {
            $query->whereHas('medicoEspecialidad', fn ($q) => $q->where('medico_id', $request->medico_id));
        }

        $citas = $query->latest('fecha')->get();

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
        $reporte = $this->armarReporte($request);

        BitacoraService::registrar('EXPORTACION_EXCEL', 'Reportes', 'Exportación de balance y KPIs a Excel (.xlsx)');

        return $excel->exportarReporteGeneral(
            $reporte['metricas'],
            $reporte['rendimientoMedicos'],
            $reporte['demandaEspecialidades'],
            $reporte['recaudacionMetodos'],
        );
    }

    /**
     * CU-16: citas del rango y recaudación real desde pagos aprobados.
     * Mensajes: contar citas por estado, sumar pagos aprobados, sumar reembolsos,
     * calcular recaudación y recaudación por médico y método de pago.
     */
    private function armarReporte(Request $request): array
    {
        $fechaInicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin = $request->input('fecha_fin', now()->endOfMonth()->toDateString());
        $especialidadId = $request->filled('especialidad_id') ? (int) $request->especialidad_id : null;
        $medicoId = $request->filled('medico_id') ? (int) $request->medico_id : null;
        $metodoPagoId = $request->filled('metodo_pago_id') ? (int) $request->metodo_pago_id : null;

        $citasQuery = CitaModel::with(['medicoEspecialidad.medico.persona', 'medicoEspecialidad.especialidad'])
            ->whereDate('fecha', '>=', $fechaInicio)
            ->whereDate('fecha', '<=', $fechaFin);
        $this->filtrarCitasPorProfesional($citasQuery, $especialidadId, $medicoId);

        $conteo = $this->contarCitasPorEstado($citasQuery);
        $totalCitas = $conteo['total'];
        $totalAtendidas = $conteo['atendidas'];
        $totalCanceladas = $conteo['canceladas'];
        $tasaCumplimiento = $totalCitas > 0 ? round(($totalAtendidas / $totalCitas) * 100, 1) : 0;

        $ingresosTotales = $this->sumarPagosAprobados($fechaInicio, $fechaFin, $especialidadId, $medicoId, $metodoPagoId);
        $reembolsos = $this->sumarReembolsos($fechaInicio, $fechaFin, $especialidadId, $medicoId, $metodoPagoId);
        $recaudacion = $this->calcularRecaudacion($ingresosTotales);

        $medicos = MedicoModel::with('persona')->where('estado', true)->orderBy('id')->get();
        $rendimientoMedicos = [];

        foreach ($medicos as $m) {
            if ($medicoId && (int) $m->id !== $medicoId) {
                continue;
            }

            $citasMedico = (clone $citasQuery)->whereHas('medicoEspecialidad', function ($q) use ($m) {
                $q->where('medico_id', $m->id);
            })->get();

            $atendidas = $citasMedico->where('estado_id', EstadoCitaModel::ATENDIDA)->count();
            $recaudado = round((float) $this->consultaPagos(PagoModel::APROBADO, 'fecha_pago', $fechaInicio, $fechaFin, $especialidadId, (int) $m->id, $metodoPagoId)->sum('monto'), 2);
            $pagosAprobados = $this->consultaPagos(PagoModel::APROBADO, 'fecha_pago', $fechaInicio, $fechaFin, $especialidadId, (int) $m->id, $metodoPagoId)->count();

            if ($citasMedico->count() > 0 || $m->id <= 5 || $pagosAprobados > 0) {
                $rendimientoMedicos[] = [
                    'medico_id' => $m->id,
                    'nombre' => 'Dr(a). '.($m->persona?->nombre_completo ?? 'N/A'),
                    'matricula' => $m->matricula_profesional,
                    'total_citas' => $citasMedico->count(),
                    'atendidas' => $atendidas,
                    'pagos_aprobados' => $pagosAprobados,
                    'ingresos' => $recaudado,
                ];
            }
        }

        $especialidades = EspecialidadModel::where('estado', true)->orderBy('nombre')->get();
        $demandaEspecialidades = [];

        foreach ($especialidades as $esp) {
            if ($especialidadId && (int) $esp->id !== $especialidadId) {
                continue;
            }

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

        $recaudacionMetodos = $this->recaudacionPorMetodo($fechaInicio, $fechaFin, $especialidadId, $medicoId, $metodoPagoId, $recaudacion);

        $sinInformacion = $totalCitas === 0 && $recaudacion == 0.0 && $reembolsos == 0.0;

        return [
            'metricas' => [
                'total_citas' => $totalCitas,
                'total_atendidas' => $totalAtendidas,
                'total_canceladas' => $totalCanceladas,
                'tasa_cumplimiento' => $tasaCumplimiento,
                'ingresos_totales' => $recaudacion,
                'reembolsos_periodo' => $reembolsos,
                'sin_informacion' => $sinInformacion,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ],
            'rendimientoMedicos' => $rendimientoMedicos,
            'demandaEspecialidades' => $demandaEspecialidades,
            'recaudacionMetodos' => $recaudacionMetodos,
            'catalogos' => [
                'especialidades' => $especialidades->map(fn ($e) => ['id' => $e->id, 'nombre' => $e->nombre])->values(),
                'medicos' => $medicos->map(fn ($m) => [
                    'id' => $m->id,
                    'nombre' => $m->persona?->nombre_completo ?? 'N/A',
                ])->values(),
                'metodos' => MetodoPagoModel::query()->activos()->orderBy('nombre')->get(['id', 'nombre']),
            ],
            'filters' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'especialidad_id' => $especialidadId ? (string) $especialidadId : '',
                'medico_id' => $medicoId ? (string) $medicoId : '',
                'metodo_pago_id' => $metodoPagoId ? (string) $metodoPagoId : '',
            ],
        ];
    }

    private function contarCitasPorEstado(Builder $citasQuery): array
    {
        return [
            'total' => (clone $citasQuery)->count(),
            'atendidas' => (clone $citasQuery)->where('estado_id', EstadoCitaModel::ATENDIDA)->count(),
            'canceladas' => (clone $citasQuery)->where('estado_id', EstadoCitaModel::CANCELADA)->count(),
        ];
    }

    private function sumarPagosAprobados(string $inicio, string $fin, ?int $especialidadId, ?int $medicoId, ?int $metodoPagoId): float
    {
        return round((float) $this->consultaPagos(PagoModel::APROBADO, 'fecha_pago', $inicio, $fin, $especialidadId, $medicoId, $metodoPagoId)->sum('monto'), 2);
    }

    private function sumarReembolsos(string $inicio, string $fin, ?int $especialidadId, ?int $medicoId, ?int $metodoPagoId): float
    {
        return round((float) $this->consultaPagos(PagoModel::REEMBOLSADO, 'fecha_reembolso', $inicio, $fin, $especialidadId, $medicoId, $metodoPagoId)->sum('monto'), 2);
    }

    private function calcularRecaudacion(float $pagosAprobados): float
    {
        return round($pagosAprobados, 2);
    }

    private function recaudacionPorMetodo(string $inicio, string $fin, ?int $especialidadId, ?int $medicoId, ?int $metodoPagoId, float $total): array
    {
        $filas = $this->consultaPagos(PagoModel::APROBADO, 'fecha_pago', $inicio, $fin, $especialidadId, $medicoId, $metodoPagoId)
            ->selectRaw('metodo_pago_id, SUM(monto) as total, COUNT(*) as cantidad')
            ->groupBy('metodo_pago_id')
            ->get()
            ->keyBy('metodo_pago_id');

        return MetodoPagoModel::query()
            ->activos()
            ->when($metodoPagoId, fn ($q) => $q->where('id', $metodoPagoId))
            ->orderBy('nombre')
            ->get()
            ->map(function ($metodo) use ($filas, $total) {
                $totalMetodo = round((float) ($filas[$metodo->id]->total ?? 0), 2);

                return [
                    'metodo_pago_id' => $metodo->id,
                    'nombre' => $metodo->nombre,
                    'total' => $totalMetodo,
                    'cantidad' => (int) ($filas[$metodo->id]->cantidad ?? 0),
                    'porcentaje' => $total > 0 ? round(($totalMetodo / $total) * 100, 1) : 0,
                ];
            })
            ->values()
            ->all();
    }

    private function consultaPagos(string $estado, string $campoFecha, string $inicio, string $fin, ?int $especialidadId, ?int $medicoId, ?int $metodoPagoId): Builder
    {
        return PagoModel::query()
            ->where('estado', $estado)
            ->whereDate($campoFecha, '>=', $inicio)
            ->whereDate($campoFecha, '<=', $fin)
            ->when($metodoPagoId, fn ($q) => $q->where('metodo_pago_id', $metodoPagoId))
            ->when($especialidadId || $medicoId, function ($q) use ($especialidadId, $medicoId) {
                $q->whereHas('cita.medicoEspecialidad', function ($qe) use ($especialidadId, $medicoId) {
                    if ($medicoId) {
                        $qe->where('medico_id', $medicoId);
                    }
                    if ($especialidadId) {
                        $qe->where('especialidad_id', $especialidadId);
                    }
                });
            });
    }

    private function filtrarCitasPorProfesional(Builder $citasQuery, ?int $especialidadId, ?int $medicoId): void
    {
        if ($especialidadId) {
            $citasQuery->whereHas('medicoEspecialidad', fn ($q) => $q->where('especialidad_id', $especialidadId));
        }
        if ($medicoId) {
            $citasQuery->whereHas('medicoEspecialidad', fn ($q) => $q->where('medico_id', $medicoId));
        }
    }
}
