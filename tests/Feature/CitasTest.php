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

        $cita = CitaModel::create([
            'codigo_reserva' => 'CIT-' . strtoupper(Str::random(8)),
            'paciente_id' => $paciente->id,
            'medico_especialidad_id' => $medicoEsp->id,
            'estado_id' => EstadoCitaModel::PROGRAMADA,
            'fecha' => now()->addDays(2)->format('Y-m-d'),
            'hora_inicio' => '10:00',
            'hora_fin' => '10:30',
            'motivo_consulta' => 'Dolor de cabeza',
            'fecha_registro' => now(),
        ]);

        $nuevaFecha = now()->addDays(4)->format('Y-m-d');

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
}
