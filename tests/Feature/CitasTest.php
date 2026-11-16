<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\UsuarioModel;
use App\Models\MedicoModel;
use App\Models\MedicoEspecialidadModel;
use App\Models\PacienteModel;
use App\Models\CitaModel;
use App\Models\EstadoCitaModel;
use App\Models\HorarioAtencionModel;
use App\Models\AusenciaMedicaModel;

use Illuminate\Support\Str;

class CitasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_programar_cita_medica_genera_codigo_unico(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medicoEsp = MedicoEspecialidadModel::first();
        $paciente = PacienteModel::first();

        // Próximo lunes libre
        $proximoLunes = now()->addDays(100 + random_int(1, 50))->next(1)->format('Y-m-d');

        // Asegurar horario para el día lunes (dia 1)
        HorarioAtencionModel::where('medico_especialidad_id', $medicoEsp->id)->where('dia_id', 1)->delete();
        HorarioAtencionModel::create([
            'medico_especialidad_id' => $medicoEsp->id,
            'dia_id' => 1,
            'hora_inicio' => '09:00',
            'hora_fin' => '12:00',
            'intervalo_minutos' => 30,
            'estado' => true,
        ]);

        // Próximo lunes
        $proximoLunes = now()->next(1)->format('Y-m-d');
        CitaModel::where('medico_especialidad_id', $medicoEsp->id)->whereDate('fecha', $proximoLunes)->delete();

        $response = $this->actingAs($admin)->post('/citas', [
            'paciente_id' => $paciente->id,
            'medico_especialidad_id' => $medicoEsp->id,
            'fecha' => $proximoLunes,
            'hora_inicio' => '09:00',
            'hora_fin' => '09:30',
            'motivo_consulta' => 'Control rutinario de hipertensión',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('citas', [
            'paciente_id' => $paciente->id,
            'medico_especialidad_id' => $medicoEsp->id,
            'fecha' => $proximoLunes,
            'hora_inicio' => '09:00',
            'estado_id' => EstadoCitaModel::PROGRAMADA,
        ]);

        $cita = CitaModel::where('paciente_id', $paciente->id)->latest()->first();
        $this->assertStringStartsWith('CIT-', $cita->codigo_reserva);
    }

    public function test_reprogramar_cita_actualiza_fecha_y_hora(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medicoEsp = MedicoEspecialidadModel::first();
        $paciente = PacienteModel::first();

        $fechaBase = now()->addDays(160 + random_int(1, 50))->format('Y-m-d');
        $nuevaFecha = now()->addDays(220 + random_int(1, 50))->format('Y-m-d');

        $cita = CitaModel::create([
            'codigo_reserva' => 'CIT-' . strtoupper(Str::random(8)),
            'paciente_id' => $paciente->id,
            'medico_especialidad_id' => $medicoEsp->id,
            'estado_id' => EstadoCitaModel::PROGRAMADA,
            'fecha' => $fechaBase,
            'hora_inicio' => '10:00',
            'hora_fin' => '10:30',
            'motivo_consulta' => 'Dolor de cabeza',
            'fecha_registro' => now(),
        ]);

        $response = $this->actingAs($admin)->post("/citas/{$cita->id}/reprogramar", [
            'fecha' => $nuevaFecha,
            'hora_inicio' => '11:00',
            'hora_fin' => '11:30',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'fecha' => $nuevaFecha,
            'hora_inicio' => '11:00',
        ]);
    }

    public function test_cancelar_cita_requiere_motivo_y_cambia_estado(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medicoEsp = MedicoEspecialidadModel::first();
        $paciente = PacienteModel::first();

        $cita = CitaModel::create([
            'codigo_reserva' => 'CIT-' . strtoupper(Str::random(8)),
            'paciente_id' => $paciente->id,
            'medico_especialidad_id' => $medicoEsp->id,
            'estado_id' => EstadoCitaModel::PROGRAMADA,
            'fecha' => now()->addDays(3)->format('Y-m-d'),
            'hora_inicio' => '15:00',
            'hora_fin' => '15:30',
            'motivo_consulta' => 'Chequeo general',
            'fecha_registro' => now(),
        ]);

        // Cancelación sin motivo debe fallar
        $failResp = $this->actingAs($admin)->post("/citas/{$cita->id}/cancelar", [
            'motivo_cancelacion' => '',
        ]);
        $failResp->assertSessionHasErrors(['motivo_cancelacion']);

        // Cancelación con motivo válido
        $response = $this->actingAs($admin)->post("/citas/{$cita->id}/cancelar", [
            'motivo_cancelacion' => 'Paciente viajó de emergencia a otra ciudad',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado_id' => EstadoCitaModel::CANCELADA,
            'motivo_cancelacion' => 'Paciente viajó de emergencia a otra ciudad',
        ]);
    }

    public function test_rechaza_programacion_de_cita_con_traslape_horario_para_el_mismo_medico(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medicoEsp = MedicoEspecialidadModel::first();
        $pacientes = PacienteModel::take(2)->get();
        $paciente1 = $pacientes[0];
        $paciente2 = $pacientes[1];

        $fechaTest = now()->addDays(280 + random_int(1, 50))->next(2)->format('Y-m-d');
        CitaModel::where('medico_especialidad_id', $medicoEsp->id)->whereDate('fecha', $fechaTest)->delete();

        // 1. Programar primera cita legítima de 10:00 a 10:30
        $resp1 = $this->actingAs($admin)->post('/citas', [
            'paciente_id' => $paciente1->id,
            'medico_especialidad_id' => $medicoEsp->id,
            'fecha' => $fechaTest,
            'hora_inicio' => '10:00',
            'hora_fin' => '10:30',
            'motivo_consulta' => 'Primera consulta asignada',
        ]);
        $resp1->assertSessionHasNoErrors();

        // 2. Intentar programar una segunda cita para otro paciente con el mismo médico en el mismo horario
        $resp2 = $this->actingAs($admin)->post('/citas', [
            'paciente_id' => $paciente2->id,
            'medico_especialidad_id' => $medicoEsp->id,
            'fecha' => $fechaTest,
            'hora_inicio' => '10:00',
            'hora_fin' => '10:30',
            'motivo_consulta' => 'Intento de doble reserva en mismo horario',
        ]);

        // Debe ser rechazado por validación de traslape
        $resp2->assertSessionHasErrors(['hora_inicio']);

        // 3. Intentar programar con traslape parcial (ej. 10:15 a 10:45)
        $resp3 = $this->actingAs($admin)->post('/citas', [
            'paciente_id' => $paciente2->id,
            'medico_especialidad_id' => $medicoEsp->id,
            'fecha' => $fechaTest,
            'hora_inicio' => '10:15',
            'hora_fin' => '10:45',
            'motivo_consulta' => 'Intento de colisión parcial',
        ]);
        $resp3->assertSessionHasErrors(['hora_inicio']);
    }

    public function test_servicio_disponibilidad_descarta_slots_ocupados(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medicoEsp = MedicoEspecialidadModel::first();
        $paciente = PacienteModel::first();

        // Configurar horario martes 08:00 - 10:00 (4 slots de 30 min: 08:00, 08:30, 09:00, 09:30)
        HorarioAtencionModel::where('medico_especialidad_id', $medicoEsp->id)->where('dia_id', 2)->delete();
        HorarioAtencionModel::create([
            'medico_especialidad_id' => $medicoEsp->id,
            'dia_id' => 2,
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'intervalo_minutos' => 30,
            'estado' => true,
        ]);

        $fechaMartes = now()->addDays(340 + random_int(1, 50))->next(2)->format('Y-m-d');

        // Reservar 08:30 a 09:00
        CitaModel::create([
            'codigo_reserva' => 'CIT-SLT-' . strtoupper(Str::random(6)),
            'paciente_id' => $paciente->id,
            'medico_especialidad_id' => $medicoEsp->id,
            'estado_id' => EstadoCitaModel::PROGRAMADA,
            'fecha' => $fechaMartes,
            'hora_inicio' => '08:30',
            'hora_fin' => '09:00',
            'motivo_consulta' => 'Prueba slot',
            'fecha_registro' => now(),
        ]);

        // Consultar slots disponibles por API
        $response = $this->actingAs($admin)->getJson("/citas/slots-disponibles?medico_especialidad_id={$medicoEsp->id}&fecha={$fechaMartes}");
        $response->assertOk();

        $slots = $response->json();
        $inicios = array_column($slots, 'inicio');

        // Debe contener 08:00, 09:00, 09:30 pero NO 08:30
        $this->assertContains('08:00', $inicios);
        $this->assertContains('09:00', $inicios);
        $this->assertContains('09:30', $inicios);
        $this->assertNotContains('08:30', $inicios);
    }
}
