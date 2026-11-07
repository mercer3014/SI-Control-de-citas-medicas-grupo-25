<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\UsuarioModel;
use App\Models\MedicoModel;
use App\Models\MedicoEspecialidadModel;
use App\Models\HorarioAtencionModel;
use App\Models\AusenciaMedicaModel;

class HorariosYAusenciasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_configurar_horario_de_atencion_sin_traslape(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medicoEsp = MedicoEspecialidadModel::first();
        HorarioAtencionModel::where('medico_especialidad_id', $medicoEsp->id)->where('dia_id', 1)->delete();

        // 1. Crear horario válido
        $response = $this->actingAs($admin)->post('/horarios', [
            'medico_especialidad_id' => $medicoEsp->id,
            'dia_id' => 1, // Lunes
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
            'intervalo_minutos' => 30,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('horarios_atencion', [
            'medico_especialidad_id' => $medicoEsp->id,
            'dia_id' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);
    }

    public function test_rechaza_horario_con_traslape_en_mismo_dia(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medicoEsp = MedicoEspecialidadModel::first();
        HorarioAtencionModel::where('medico_especialidad_id', $medicoEsp->id)->where('dia_id', 2)->delete();

        // Crear base
        HorarioAtencionModel::create([
            'medico_especialidad_id' => $medicoEsp->id,
            'dia_id' => 2, // Martes
            'hora_inicio' => '14:00',
            'hora_fin' => '18:00',
            'intervalo_minutos' => 30,
            'estado' => true,
        ]);

        // Intentar registrar uno que se traslape (ej. 15:00 a 19:00)
        $response = $this->actingAs($admin)->post('/horarios', [
            'medico_especialidad_id' => $medicoEsp->id,
            'dia_id' => 2,
            'hora_inicio' => '15:00',
            'hora_fin' => '19:00',
            'intervalo_minutos' => 30,
        ]);

        $response->assertSessionHasErrors(['hora_inicio']);
    }

    public function test_registrar_y_eliminar_ausencia_medica(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medico = MedicoModel::first();

        $response = $this->actingAs($admin)->post('/ausencias', [
            'medico_id' => $medico->id,
            'fecha_inicio' => now()->addDays(5)->format('Y-m-d'),
            'fecha_fin' => now()->addDays(10)->format('Y-m-d'),
            'motivo' => 'Vacaciones de invierno programadas',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ausencias_medicas', [
            'medico_id' => $medico->id,
            'motivo' => 'Vacaciones de invierno programadas',
        ]);

        $ausencia = AusenciaMedicaModel::where('medico_id', $medico->id)->latest()->first();
        $deleteResp = $this->actingAs($admin)->delete("/ausencias/{$ausencia->id}");
        $deleteResp->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('ausencias_medicas', [
            'id' => $ausencia->id,
        ]);
    }

    public function test_valida_fechas_de_ausencia(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medico = MedicoModel::first();

        // Fecha fin antes de fecha inicio debe fallar
        $response = $this->actingAs($admin)->post('/ausencias', [
            'medico_id' => $medico->id,
            'fecha_inicio' => now()->addDays(10)->format('Y-m-d'),
            'fecha_fin' => now()->addDays(5)->format('Y-m-d'),
            'motivo' => 'Error de fechas',
        ]);

        $response->assertSessionHasErrors(['fecha_fin']);
    }
}
