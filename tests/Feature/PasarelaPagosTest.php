<?php

namespace Tests\Feature;

use App\Models\BitacoraModel;
use App\Models\CitaModel;
use App\Models\EstadoCitaModel;
use App\Models\MedicoEspecialidadModel;
use App\Models\MetodoPagoModel;
use App\Models\PacienteModel;
use App\Models\PagoModel;
use App\Models\UsuarioModel;
use App\Services\PasarelaPagoService;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PN11 a PN18: pasarela de pagos (CU-18, CU-19 y recaudación de CU-16).
 */
class PasarelaPagosTest extends TestCase
{
    private UsuarioModel $pacienteUser;

    private PacienteModel $paciente;

    private UsuarioModel $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pasarela.driver' => 'fake',
            'pasarela.webhook_secret' => 'test-secret-pn',
            'pasarela.fake_acepta_reembolso' => true,
        ]);

        $this->artisan('db:seed');
        PagoModel::query()->delete();

        $this->pacienteUser = UsuarioModel::where('username', 'paciente.lucia')->firstOrFail();
        $this->paciente = PacienteModel::where('persona_id', $this->pacienteUser->persona_id)->firstOrFail();
        $this->admin = UsuarioModel::where('username', 'admin')->firstOrFail();
    }

    public function test_pn11_pago_aprobado_confirma_la_cita_y_registra_bitacora(): void
    {
        $cita = $this->citaProgramada('PN11');
        $pago = $this->iniciarPago($cita);

        $this->assertSame(
            number_format((float) $cita->medicoEspecialidad->precio_consulta, 2, '.', ''),
            number_format((float) $pago->monto, 2, '.', '')
        );

        $respuesta = $this->actingAs($this->pacienteUser)
            ->post('/pagos/sandbox/'.$pago->referencia_transaccion, ['decision' => 'aprobado']);

        $respuesta->assertRedirect(route('mis-citas', ['comprobante' => $pago->referencia_transaccion]));
        $pago->refresh();
        $cita->refresh();

        $this->assertSame(PagoModel::APROBADO, $pago->estado);
        $this->assertNotNull($pago->fecha_pago);
        $this->assertSame(EstadoCitaModel::CONFIRMADA, (int) $cita->estado_id);
        $this->assertSame(1, $this->bitacorasDe('PAGO_CONSULTA', $pago->referencia_transaccion));
        $this->assertSame(1, PagoModel::where('referencia_transaccion', $pago->referencia_transaccion)->count());

        $this->actingAs($this->pacienteUser)
            ->get('/mis-citas?comprobante='.$pago->referencia_transaccion)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Citas/MisCitas')->where('comprobante.estado', PagoModel::APROBADO));
    }

    public function test_pn12_pago_rechazado_mantiene_la_cita_programada(): void
    {
        $cita = $this->citaProgramada('PN12');
        $pago = $this->iniciarPago($cita);

        $respuesta = $this->actingAs($this->pacienteUser)
            ->post('/pagos/sandbox/'.$pago->referencia_transaccion, ['decision' => 'rechazado']);

        $respuesta->assertRedirect();
        $respuesta->assertSessionHas('error', 'Pago rechazado, intente nuevamente');

        $this->assertSame(PagoModel::RECHAZADO, $pago->fresh()->estado);
        $this->assertSame(EstadoCitaModel::PROGRAMADA, (int) $cita->fresh()->estado_id);
    }

    public function test_pn13_webhook_duplicado_no_repite_pago_ni_bitacora(): void
    {
        $cita = $this->citaProgramada('PN13');
        $pago = $this->iniciarPago($cita);
        $payload = $this->payload($pago, PagoModel::APROBADO);
        $firma = $this->firmar($payload);

        $this->postJson('/pagos/webhook', $payload, ['X-Pasarela-Firma' => $firma])->assertOk();
        $this->postJson('/pagos/webhook', $payload, ['X-Pasarela-Firma' => $firma])
            ->assertOk()
            ->assertJsonPath('idempotente', true);

        $this->assertSame(1, PagoModel::where('referencia_transaccion', $pago->referencia_transaccion)->count());
        $this->assertSame(1, $this->bitacorasDe('PAGO_CONSULTA', $pago->referencia_transaccion));
        $this->assertSame(EstadoCitaModel::CONFIRMADA, (int) $cita->fresh()->estado_id);
        $this->assertSame(PagoModel::APROBADO, $pago->fresh()->estado);
    }

    public function test_pn14_firma_invalida_no_cambia_pago_ni_cita(): void
    {
        $cita = $this->citaProgramada('PN14');
        $pago = $this->iniciarPago($cita);

        $this->postJson('/pagos/webhook', $this->payload($pago, PagoModel::APROBADO), [
            'X-Pasarela-Firma' => 'firma-distinta-al-secreto',
        ])->assertStatus(400)->assertJsonPath('message', 'Firma inválida');

        $this->assertSame(PagoModel::PENDIENTE, $pago->fresh()->estado);
        $this->assertSame(EstadoCitaModel::PROGRAMADA, (int) $cita->fresh()->estado_id);
        $this->assertSame(0, $this->bitacorasDe('PAGO_CONSULTA', $pago->referencia_transaccion));
    }

    public function test_pn15_reembolso_de_cita_no_atendida_la_cancela_y_sale_de_recaudacion(): void
    {
        $cita = $this->citaProgramada('PN15');
        $pago = $this->iniciarPago($cita);
        $this->actingAs($this->pacienteUser)
            ->post('/pagos/sandbox/'.$pago->referencia_transaccion, ['decision' => 'aprobado'])
            ->assertRedirect();

        $motivo = 'Paciente no podrá asistir a la consulta';
        $respuesta = $this->actingAs($this->admin)->post('/pagos/'.$pago->id.'/reembolsar', [
            'motivo_reembolso' => $motivo,
        ]);

        $respuesta->assertSessionHas('success');
        $pago->refresh();
        $cita->refresh();

        $this->assertSame(PagoModel::REEMBOLSADO, $pago->estado);
        $this->assertSame($motivo, $pago->motivo_reembolso);
        $this->assertNotNull($pago->fecha_reembolso);
        $this->assertSame(EstadoCitaModel::CANCELADA, (int) $cita->estado_id);
        $this->assertSame(1, $this->bitacorasDe('REEMBOLSO_PAGO', $pago->referencia_transaccion));

        $dia = $pago->fecha_reembolso->toDateString();
        $this->actingAs($this->admin)
            ->get('/reportes?fecha_inicio='.$dia.'&fecha_fin='.$dia)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('metricas.ingresos_totales', fn ($valor) => abs((float) $valor) < 0.001)
                ->where('metricas.reembolsos_periodo', fn ($valor) => abs((float) $valor - (float) $pago->monto) < 0.001)
            );
    }

    public function test_pn16_rechaza_reembolso_si_el_pago_no_esta_aprobado(): void
    {
        $citaRechazada = $this->citaProgramada('PN16A');
        $rechazado = $this->iniciarPago($citaRechazada);
        $this->actingAs($this->pacienteUser)
            ->post('/pagos/sandbox/'.$rechazado->referencia_transaccion, ['decision' => 'rechazado']);

        $citaDevuelta = $this->citaProgramada('PN16B');
        $devuelto = $this->iniciarPago($citaDevuelta);
        $this->actingAs($this->pacienteUser)
            ->post('/pagos/sandbox/'.$devuelto->referencia_transaccion, ['decision' => 'aprobado']);
        $this->actingAs($this->admin)->post('/pagos/'.$devuelto->id.'/reembolsar', [
            'motivo_reembolso' => 'Devolución previa de la prueba',
        ]);

        foreach ([$rechazado, $devuelto] as $pago) {
            $estadoAntes = $pago->fresh()->estado;
            $citaEstado = (int) $pago->cita()->first()->estado_id;

            $respuesta = $this->actingAs($this->admin)->from('/pagos')->post('/pagos/'.$pago->id.'/reembolsar', [
                'motivo_reembolso' => 'Reembolso no permitido',
            ]);

            $respuesta->assertRedirect('/pagos');
            $respuesta->assertSessionHas('error', 'Solo se pueden reembolsar pagos aprobados');
            $this->assertSame($estadoAntes, $pago->fresh()->estado);
            $this->assertSame($citaEstado, (int) $pago->cita()->first()->estado_id);
        }
    }

    public function test_pn17_reembolso_de_cita_atendida_no_la_cancela(): void
    {
        $cita = $this->citaProgramada('PN17');
        $pago = $this->iniciarPago($cita);
        $this->actingAs($this->pacienteUser)
            ->post('/pagos/sandbox/'.$pago->referencia_transaccion, ['decision' => 'aprobado']);

        $cita->refresh();
        $cita->update(['estado_id' => EstadoCitaModel::ATENDIDA]);

        $this->actingAs($this->admin)->post('/pagos/'.$pago->id.'/reembolsar', [
            'motivo_reembolso' => 'Devolución autorizada por la administración',
        ])->assertSessionHas('success');

        $this->assertSame(PagoModel::REEMBOLSADO, $pago->fresh()->estado);
        $this->assertSame('Devolución autorizada por la administración', $pago->fresh()->motivo_reembolso);
        $this->assertSame(EstadoCitaModel::ATENDIDA, (int) $cita->fresh()->estado_id);
        $this->assertSame(1, $this->bitacorasDe('REEMBOLSO_PAGO', $pago->referencia_transaccion));
    }

    public function test_pn18_recaudacion_solo_suma_pagos_aprobados_y_respeta_filtros(): void
    {
        $meA = MedicoEspecialidadModel::query()->firstOrFail();
        $meB = MedicoEspecialidadModel::where('medico_id', '!=', $meA->medico_id)->firstOrFail();
        $qr = MetodoPagoModel::where('nombre', 'QR Simple')->firstOrFail();
        $debito = MetodoPagoModel::where('nombre', 'Tarjeta de Débito')->firstOrFail();

        $this->pagoDirecto($meA, $qr->id, 150, PagoModel::APROBADO, '2026-11-05 09:00:00', 'TRX-PN18-150');
        $this->pagoDirecto($meB, $debito->id, 180, PagoModel::APROBADO, '2026-11-10 09:00:00', 'TRX-PN18-180');
        $this->pagoDirecto($meA, $qr->id, 150, PagoModel::RECHAZADO, '2026-11-11 09:00:00', 'TRX-PN18-RECH');
        $this->pagoDirecto($meA, $qr->id, 200, PagoModel::PENDIENTE, '2026-11-12 09:00:00', 'TRX-PN18-PEND', null);
        $this->pagoDirecto($meB, $debito->id, 160, PagoModel::REEMBOLSADO, '2026-11-08 09:00:00', 'TRX-PN18-REEM', '2026-11-12 10:00:00');

        $this->actingAs($this->admin)
            ->get('/reportes?fecha_inicio=2026-11-01&fecha_fin=2026-11-30')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reportes/Index')
                ->where('metricas.ingresos_totales', fn ($valor) => abs((float) $valor - 330) < 0.001)
                ->where('metricas.reembolsos_periodo', fn ($valor) => abs((float) $valor - 160) < 0.001)
            );

        $this->actingAs($this->admin)
            ->get('/reportes?fecha_inicio=2026-11-01&fecha_fin=2026-11-30&medico_id='.$meA->medico_id)
            ->assertInertia(fn ($page) => $page
                ->where('metricas.ingresos_totales', fn ($valor) => abs((float) $valor - 150) < 0.001)
            );

        $this->actingAs($this->admin)
            ->get('/reportes?fecha_inicio=2026-11-01&fecha_fin=2026-11-30&metodo_pago_id='.$debito->id)
            ->assertInertia(fn ($page) => $page
                ->where('metricas.ingresos_totales', fn ($valor) => abs((float) $valor - 180) < 0.001)
            );

        $this->actingAs($this->admin)
            ->get('/reportes/export-general?fecha_inicio=2026-11-01&fecha_fin=2026-11-30')
            ->assertOk();
    }

    public function test_driver_stripe_verifica_la_firma_del_webhook(): void
    {
        config([
            'pasarela.driver' => 'stripe',
            'pasarela.stripe_webhook_secret' => 'whsec_test',
        ]);

        $raw = '{"id":"evt_test"}';
        $timestamp = '1600000000';
        $firma = hash_hmac('sha256', $timestamp.'.'.$raw, 'whsec_test');
        $servicio = new PasarelaPagoService;

        $this->assertTrue($servicio->verificarFirma(['_raw' => $raw], 't='.$timestamp.',v1='.$firma));
        $this->assertFalse($servicio->verificarFirma(['_raw' => $raw], 't='.$timestamp.',v1=firma-incorrecta'));
    }

    private function citaProgramada(string $marca): CitaModel
    {
        $me = MedicoEspecialidadModel::where('precio_consulta', '>', 0)->firstOrFail();

        return CitaModel::create([
            'codigo_reserva' => substr('CIT'.$marca.strtoupper(Str::random(6)), 0, 20),
            'paciente_id' => $this->paciente->id,
            'medico_especialidad_id' => $me->id,
            'estado_id' => EstadoCitaModel::PROGRAMADA,
            'fecha' => now()->addDays(40 + random_int(1, 80))->toDateString(),
            'hora_inicio' => '07:00',
            'hora_fin' => '07:30',
            'motivo_consulta' => 'Consulta de prueba '.$marca,
            'fecha_registro' => now(),
        ])->load('medicoEspecialidad');
    }

    private function iniciarPago(CitaModel $cita): PagoModel
    {
        $metodo = MetodoPagoModel::query()->activos()->firstOrFail();

        $this->actingAs($this->pacienteUser)
            ->getJson('/mis-citas/'.$cita->id.'/pagar')
            ->assertOk()
            ->assertJsonPath('admite_pago', true);

        $this->actingAs($this->pacienteUser)
            ->post('/pagos', [
                'cita_id' => $cita->id,
                'metodo_pago_id' => $metodo->id,
            ])
            ->assertRedirect();

        return PagoModel::where('cita_id', $cita->id)->latest('id')->firstOrFail();
    }

    private function payload(PagoModel $pago, string $estado): array
    {
        return [
            'referencia' => $pago->referencia_transaccion,
            'estado' => $estado,
            'monto' => number_format((float) $pago->monto, 2, '.', ''),
            'moneda' => $pago->moneda ?: 'BOB',
        ];
    }

    private function firmar(array $payload): string
    {
        return (new PasarelaPagoService)->firmar($payload);
    }

    private function bitacorasDe(string $accion, string $referencia): int
    {
        return BitacoraModel::where('accion', $accion)
            ->where('detalle', 'like', '%'.$referencia.'%')
            ->count();
    }

    private function pagoDirecto(
        MedicoEspecialidadModel $me,
        int $metodoId,
        float $monto,
        string $estado,
        string $fechaPago,
        string $referencia,
        ?string $fechaReembolso = null,
    ): PagoModel {
        $cita = CitaModel::create([
            'codigo_reserva' => substr('CIT'.strtoupper(Str::random(10)), 0, 20),
            'paciente_id' => $this->paciente->id,
            'medico_especialidad_id' => $me->id,
            'estado_id' => EstadoCitaModel::PROGRAMADA,
            'fecha' => '2026-11-15',
            'hora_inicio' => '15:00',
            'hora_fin' => '15:30',
            'motivo_consulta' => 'Reporte '.$referencia,
            'fecha_registro' => now(),
        ]);

        return PagoModel::create([
            'cita_id' => $cita->id,
            'metodo_pago_id' => $metodoId,
            'monto' => $monto,
            'moneda' => 'BOB',
            'estado' => $estado,
            'referencia_transaccion' => $referencia,
            'fecha_pago' => $estado === PagoModel::PENDIENTE ? null : $fechaPago,
            'motivo_reembolso' => $estado === PagoModel::REEMBOLSADO ? 'Devolución de prueba' : null,
            'fecha_reembolso' => $fechaReembolso,
        ]);
    }
}
