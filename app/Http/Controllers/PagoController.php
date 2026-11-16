<?php

namespace App\Http\Controllers;

use App\Http\Requests\PagoRequest;
use App\Models\CitaModel;
use App\Models\EstadoCitaModel;
use App\Models\MetodoPagoModel;
use App\Models\PacienteModel;
use App\Models\PagoModel;
use App\Services\BitacoraService;
use App\Services\PasarelaPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PagoController extends Controller
{
    public function __construct(private readonly PasarelaPagoService $pasarela) {}

    /**
     * CU-19 paso 1-4: lista de Gestión de Pagos.
     */
    public function index(Request $request): Response
    {
        $query = PagoModel::with([
            'cita.paciente.persona',
            'cita.estado',
            'cita.medicoEspecialidad.medico.persona',
            'cita.medicoEspecialidad.especialidad',
            'metodoPago',
        ])->latest('id');

        if ($request->filled('buscar')) {
            $term = '%'.trim((string) $request->buscar).'%';
            $query->where(function ($q) use ($term) {
                $q->where('referencia_transaccion', 'ilike', $term)
                    ->orWhereHas('cita', function ($qc) use ($term) {
                        $qc->where('codigo_reserva', 'ilike', $term)
                            ->orWhereHas('paciente.persona', function ($qp) use ($term) {
                                $qp->where('nombres', 'ilike', $term)
                                    ->orWhere('apellidos', 'ilike', $term)
                                    ->orWhere('ci', 'ilike', $term);
                            });
                    });
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('metodo_pago_id')) {
            $query->where('metodo_pago_id', $request->metodo_pago_id);
        }

        $recaudadoPeriodo = (float) PagoModel::query()
            ->where('estado', PagoModel::APROBADO)
            ->whereDate('fecha_pago', '>=', now()->startOfMonth()->toDateString())
            ->whereDate('fecha_pago', '<=', now()->endOfMonth()->toDateString())
            ->sum('monto');

        return Inertia::render('Pagos/GestionPagos', [
            'pagos' => $query->paginate(15)->withQueryString(),
            'metodos' => MetodoPagoModel::query()->orderBy('nombre')->get(['id', 'nombre', 'estado']),
            'estados' => [PagoModel::PENDIENTE, PagoModel::APROBADO, PagoModel::RECHAZADO, PagoModel::REEMBOLSADO],
            'recaudadoPeriodo' => round($recaudadoPeriodo, 2),
            'filters' => $request->only(['buscar', 'estado', 'metodo_pago_id']),
        ]);
    }

    /**
     * CU-18 mensajes 2-5: consultar la cita, calcular el monto y mostrar el resumen.
     */
    public function iniciarPago(Request $request, CitaModel $cita): JsonResponse
    {
        $this->autorizarCitaDelPaciente($request, $cita);

        if (! $this->citaAdmitePago($cita)) {
            return response()->json([
                'admite_pago' => false,
                'mensaje' => 'La cita no admite pago en línea',
            ], 422);
        }

        $cita->load(['medicoEspecialidad.medico.persona', 'medicoEspecialidad.especialidad', 'estado', 'paciente.persona']);

        return response()->json([
            'admite_pago' => true,
            'monto' => $this->calcularMonto($cita),
            'moneda' => 'BOB',
            'cita' => [
                'id' => $cita->id,
                'codigo_reserva' => $cita->codigo_reserva,
                'fecha' => optional($cita->fecha)->toDateString(),
                'hora_inicio' => substr((string) $cita->hora_inicio, 0, 5),
                'hora_fin' => substr((string) $cita->hora_fin, 0, 5),
                'estado' => $cita->estado?->nombre,
                'paciente' => $cita->paciente?->persona?->nombre_completo,
                'medico' => $cita->medicoEspecialidad?->medico?->persona?->nombre_completo,
                'especialidad' => $cita->medicoEspecialidad?->especialidad?->nombre,
            ],
            'metodos' => MetodoPagoModel::query()->activos()->orderBy('nombre')->get(['id', 'nombre', 'descripcion']),
        ]);
    }

    /**
     * CU-18 mensajes 7-10: pago Pendiente y redirección HTTPS al checkout.
     */
    public function store(PagoRequest $request): RedirectResponse
    {
        $cita = CitaModel::with('medicoEspecialidad')->findOrFail($request->integer('cita_id'));
        $this->autorizarCitaDelPaciente($request, $cita);

        if (! $this->citaAdmitePago($cita)) {
            return back()->with('error', 'La cita no admite pago en línea');
        }

        try {
            $monto = $this->calcularMonto($cita);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pago = $cita->pagos()->where('estado', PagoModel::PENDIENTE)->latest('id')->first();

        if ($pago) {
            $pago->update([
                'metodo_pago_id' => $request->integer('metodo_pago_id'),
                'monto' => $monto,
                'moneda' => 'BOB',
            ]);
            if (! $pago->referencia_transaccion) {
                $pago->update(['referencia_transaccion' => $this->generarReferencia()]);
            }
        } else {
            $pago = PagoModel::create([
                'cita_id' => $cita->id,
                'metodo_pago_id' => $request->integer('metodo_pago_id'),
                'monto' => $monto,
                'moneda' => 'BOB',
                'estado' => PagoModel::PENDIENTE,
                'referencia_transaccion' => $this->generarReferencia(),
            ]);
        }

        try {
            $url = $this->pasarela->crearTransaccion((string) $pago->monto, (string) $pago->referencia_transaccion);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No se recibió respuesta de la pasarela');
        }

        if ($url === '') {
            return back()->with('error', 'No se recibió respuesta de la pasarela');
        }

        return redirect()->to($url);
    }

    /**
     * CU-18 mensajes 11-19: firma, idempotencia, aprobación y comprobante.
     */
    public function webhook(Request $request): JsonResponse
    {
        $firma = (string) ($request->header('X-Pasarela-Firma') ?: $request->header('Stripe-Signature') ?: '');
        $payload = $request->all();
        $payload['_raw'] = $request->getContent();

        if (! $this->pasarela->verificarFirma($payload, $firma)) {
            return response()->json(['message' => 'Firma inválida'], 400);
        }

        return $this->confirmarPago($this->pasarela->traducirNotificacion($payload));
    }

    /**
     * CU-19 mensajes 5-16: validar, reembolsar en la pasarela y cancelar si no fue atendida.
     */
    public function reembolsar(Request $request, PagoModel $pago): RedirectResponse
    {
        $request->validate([
            'motivo_reembolso' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'motivo_reembolso.required' => 'Debe indicar el motivo del reembolso.',
            'motivo_reembolso.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        if (! $this->validarEstadoPago($pago)) {
            return back()->with('error', 'Solo se pueden reembolsar pagos aprobados');
        }

        $motivo = trim((string) $request->motivo_reembolso);
        $aceptado = $this->pasarela->reembolsar((string) $pago->referencia_transaccion, (string) $pago->monto);

        if (! $aceptado) {
            BitacoraService::registrar(
                'REEMBOLSO_RECHAZADO',
                'Pagos',
                "La pasarela no pudo reembolsar el pago {$pago->referencia_transaccion}. Motivo solicitado: {$motivo}"
            );

            return back()->with('error', 'La pasarela no pudo procesar el reembolso');
        }

        DB::transaction(function () use ($pago, $motivo) {
            $pago->reembolsar($motivo);
            $cita = $pago->cita()->lockForUpdate()->first();

            if ($cita && (int) $cita->estado_id !== EstadoCitaModel::ATENDIDA) {
                $cita->cancelar('Reembolso del pago: '.$motivo);
            }

            BitacoraService::registrar(
                'REEMBOLSO_PAGO',
                'Pagos',
                "Pago {$pago->referencia_transaccion} reembolsado. Motivo: {$motivo}. Cita {$cita?->codigo_reserva}."
            );
        });

        return back()->with('success', 'El reembolso fue confirmado.');
    }

    public function sandbox(Request $request, string $referencia): Response
    {
        abort_unless($this->pasarela->driver() === 'fake', 404);

        $pago = $this->pagoSandbox($request, $referencia);
        $pago->load(['cita.paciente.persona', 'cita.medicoEspecialidad.medico.persona', 'cita.medicoEspecialidad.especialidad', 'metodoPago']);

        return Inertia::render('Pagos/Sandbox', [
            'pago' => $pago,
        ]);
    }

    public function sandboxConfirmar(Request $request, string $referencia): RedirectResponse
    {
        abort_unless($this->pasarela->driver() === 'fake', 404);

        $request->validate([
            'decision' => ['required', 'in:aprobado,rechazado'],
        ]);

        $pago = $this->pagoSandbox($request, $referencia);
        $estado = $request->decision === 'aprobado' ? PagoModel::APROBADO : PagoModel::RECHAZADO;

        $payload = [
            'referencia' => $pago->referencia_transaccion,
            'estado' => $estado,
            'monto' => number_format((float) $pago->monto, 2, '.', ''),
            'moneda' => $pago->moneda ?: 'BOB',
        ];

        $interno = Request::create('/pagos/webhook', 'POST', $payload);
        $interno->headers->set('X-Pasarela-Firma', $this->pasarela->firmar($payload));
        $interno->headers->set('Accept', 'application/json');

        $respuesta = $this->webhook($interno);
        if ($respuesta->getStatusCode() >= 400) {
            return back()->with('error', 'No se recibió respuesta de la pasarela');
        }

        $pago->refresh();

        if ($pago->estado === PagoModel::APROBADO) {
            return redirect()
                ->route('mis-citas', ['comprobante' => $pago->referencia_transaccion])
                ->with('success', 'Pago aprobado. La cita quedó confirmada.');
        }

        return redirect()
            ->route('mis-citas', ['comprobante' => $pago->referencia_transaccion])
            ->with('error', 'Pago rechazado, intente nuevamente');
    }

    private function confirmarPago(array $datos): JsonResponse
    {
        $referencia = $datos['referencia'] ?? null;
        $estado = $datos['estado'] ?? null;

        if (! $referencia || ! in_array($estado, [PagoModel::APROBADO, PagoModel::RECHAZADO, PagoModel::REEMBOLSADO], true)) {
            return response()->json(['message' => 'Notificación incompleta'], 422);
        }

        return DB::transaction(function () use ($datos, $referencia, $estado) {
            $pago = PagoModel::where('referencia_transaccion', $referencia)->lockForUpdate()->first();

            if (! $pago) {
                return response()->json(['message' => 'Pago no encontrado'], 404);
            }

            $moneda = strtoupper((string) ($datos['moneda'] ?? $pago->moneda));
            if ($datos['monto'] !== null && $datos['monto'] !== '' && $moneda === strtoupper((string) $pago->moneda)) {
                if (abs((float) $datos['monto'] - (float) $pago->monto) > 0.009) {
                    return response()->json(['message' => 'El monto no coincide con el pago'], 422);
                }
            }

            if ($this->verificarDuplicado($pago, $estado)) {
                return response()->json([
                    'message' => 'Notificación ya procesada',
                    'idempotente' => true,
                    'estado' => $pago->estado,
                ]);
            }

            $cita = $pago->cita()->lockForUpdate()->first();

            if ($estado === PagoModel::APROBADO) {
                $pago->aprobar($referencia);
                if ($cita && (int) $cita->estado_id === EstadoCitaModel::PROGRAMADA) {
                    $cita->confirmar();
                }
                BitacoraService::registrar(
                    'PAGO_CONSULTA',
                    'Pagos',
                    "Pago {$referencia} aprobado por {$pago->monto} {$pago->moneda}. Cita {$cita?->codigo_reserva} confirmada."
                );
            } elseif ($estado === PagoModel::RECHAZADO) {
                $pago->rechazar();
                BitacoraService::registrar(
                    'PAGO_RECHAZADO',
                    'Pagos',
                    "Pago {$referencia} rechazado. Cita {$cita?->codigo_reserva} permanece programada."
                );
            } elseif ($estado === PagoModel::REEMBOLSADO && $pago->estaAprobado()) {
                $motivo = (string) ($datos['motivo'] ?? 'Reembolso confirmado por la pasarela');
                $pago->reembolsar($motivo);
                if ($cita && (int) $cita->estado_id !== EstadoCitaModel::ATENDIDA) {
                    $cita->cancelar('Reembolso del pago: '.$motivo);
                }
                BitacoraService::registrar(
                    'REEMBOLSO_PAGO',
                    'Pagos',
                    "Pago {$referencia} reembolsado por notificación de la pasarela. Cita {$cita?->codigo_reserva}."
                );
            }

            return response()->json([
                'message' => 'Notificación procesada',
                'estado' => $pago->fresh()->estado,
            ]);
        });
    }

    private function verificarDuplicado(PagoModel $pago, string $estado): bool
    {
        return $pago->estado !== PagoModel::PENDIENTE && $estado !== PagoModel::PENDIENTE;
    }

    private function validarEstadoPago(PagoModel $pago): bool
    {
        return $pago->estaAprobado();
    }

    private function calcularMonto(CitaModel $cita): string
    {
        $cita->loadMissing('medicoEspecialidad');
        $precio = $cita->medicoEspecialidad?->precio_consulta;

        if ($precio === null || (float) $precio <= 0) {
            throw new \RuntimeException('La especialidad del médico no tiene un arancel de consulta.');
        }

        return number_format((float) $precio, 2, '.', '');
    }

    private function citaAdmitePago(CitaModel $cita): bool
    {
        if ((int) $cita->estado_id !== EstadoCitaModel::PROGRAMADA) {
            return false;
        }

        return ! $cita->pagos()->where('estado', PagoModel::APROBADO)->exists();
    }

    private function autorizarCitaDelPaciente(Request $request, CitaModel $cita): void
    {
        $paciente = PacienteModel::where('persona_id', $request->user()?->persona_id)->first();
        abort_unless($paciente && (int) $cita->paciente_id === (int) $paciente->id, HttpResponse::HTTP_FORBIDDEN);
    }

    private function pagoSandbox(Request $request, string $referencia): PagoModel
    {
        $pago = PagoModel::where('referencia_transaccion', $referencia)->firstOrFail();
        $this->autorizarCitaDelPaciente($request, $pago->cita);

        return $pago;
    }

    private function generarReferencia(): string
    {
        do {
            $referencia = 'TRX-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (PagoModel::where('referencia_transaccion', $referencia)->exists());

        return $referencia;
    }
}
