<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\UsuarioModel;
use App\Models\MedicoModel;
use App\Models\MedicoEspecialidadModel;
use App\Models\PacienteModel;
use App\Models\CitaModel;
use App\Models\EstadoCitaModel;
use App\Models\AtencionClinicaModel;
use Illuminate\Support\Str;

class AtencionClinicaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_registrar_atencion_clinica_y_marcar_cita_atendida(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medicoEsp = MedicoEspecialidadModel::first();
        $paciente = PacienteModel::first();

        $cita = CitaModel::create([
            'codigo_reserva' => 'CIT-' . strtoupper(Str::random(8)),
            'paciente_id' => $paciente->id,
            'medico_especialidad_id' => $medicoEsp->id,
            'estado_id' => EstadoCitaModel::PROGRAMADA,
            'fecha' => now()->format('Y-m-d'),
            'hora_inicio' => '10:00',
            'hora_fin' => '10:30',
            'motivo_consulta' => 'Fiebre persistente y tos seca',
            'fecha_registro' => now(),
        ]);

        $response = $this->actingAs($admin)->post('/atenciones', [
            'cita_id' => $cita->id,
            'diagnostico' => 'Faringoamigdalitis bacteriana aguda',
            'tratamiento' => 'Amoxicilina 500mg cada 8 horas por 7 días. Paracetamol 500mg si hay fiebre.',
            'notas_adicionales' => 'Paciente tolera bien medicación oral. Se recomienda reposo de 48 horas.',
        ]);

        $response->assertSessionHasNoErrors();

        // 1. Verificar registro en atenciones_clinicas
        $this->assertDatabaseHas('atenciones_clinicas', [
            'cita_id' => $cita->id,
            'diagnostico' => 'Faringoamigdalitis bacteriana aguda',
        ]);

        // 2. Verificar que el estado de la cita cambió a ATENDIDA (3)
        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado_id' => EstadoCitaModel::ATENDIDA,
        ]);

        // 3. Verificar auditoría en bitácora
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'REGISTRO_ATENCION',
            'modulo' => 'Atenciones',
        ]);
    }

    public function test_diagnostico_es_obligatorio_al_registrar_atencion(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medicoEsp = MedicoEspecialidadModel::first();
        $paciente = PacienteModel::first();

        $cita = CitaModel::create([
            'codigo_reserva' => 'CIT-' . strtoupper(Str::random(8)),
            'paciente_id' => $paciente->id,
            'medico_especialidad_id' => $medicoEsp->id,
            'estado_id' => EstadoCitaModel::PROGRAMADA,
            'fecha' => now()->format('Y-m-d'),
            'hora_inicio' => '11:00',
            'hora_fin' => '11:30',
            'motivo_consulta' => 'Control',
            'fecha_registro' => now(),
        ]);

        $response = $this->actingAs($admin)->post('/atenciones', [
            'cita_id' => $cita->id,
            'diagnostico' => '',
            'tratamiento' => 'Tratamiento de prueba',
        ]);

        $response->assertSessionHasErrors(['diagnostico']);
    }
}
