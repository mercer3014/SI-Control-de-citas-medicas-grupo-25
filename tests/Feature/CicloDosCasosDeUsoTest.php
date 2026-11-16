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
use App\Models\AtencionClinicaModel;
use Illuminate\Support\Str;

class CicloDosCasosDeUsoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * CU-09: Configuración de Horarios de Atención sin traslapes
     */
    public function test_cu09_configurar_horarios_sin_traslape(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medEsp = MedicoEspecialidadModel::first();
        HorarioAtencionModel::where('medico_especialidad_id', $medEsp->id)->where('dia_id', 4)->delete();

        $response = $this->actingAs($admin)->post('/horarios', [
            'medico_especialidad_id' => $medEsp->id,
            'dia_id' => 4, // Jueves
            'hora_inicio' => '14:00',
            'hora_fin' => '18:00',
            'intervalo_minutos' => 30,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('horarios_atencion', [
            'medico_especialidad_id' => $medEsp->id,
            'dia_id' => 4,
            'hora_inicio' => '14:00',
        ]);
    }

    /**
     * CU-10: Registro y consulta de ausencias médicas
     */
    public function test_cu10_gestion_ausencias_medicas(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medico = MedicoModel::first();

        $response = $this->actingAs($admin)->post('/ausencias', [
            'medico_id' => $medico->id,
            'fecha_inicio' => now()->addDays(10)->format('Y-m-d'),
            'fecha_fin' => now()->addDays(12)->format('Y-m-d'),
            'motivo' => 'Seminario internacional de actualización médica',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ausencias_medicas', [
            'medico_id' => $medico->id,
            'motivo' => 'Seminario internacional de actualización médica',
        ]);
    }

    /**
     * CU-11: Programación de cita con código único
     */
    public function test_cu11_programar_cita_genera_codigo_unico(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medicoEsp = MedicoEspecialidadModel::first();
        $paciente = PacienteModel::first();
        $fechaCita = now()->addDays(7)->format('Y-m-d');

        // Limpiar cualquier cita previa de prueba en esa fecha
        CitaModel::where('medico_especialidad_id', $medicoEsp->id)->whereDate('fecha', $fechaCita)->delete();

        $response = $this->actingAs($admin)->post('/citas', [
            'paciente_id' => $paciente->id,
            'medico_especialidad_id' => $medicoEsp->id,
            'fecha' => $fechaCita,
            'hora_inicio' => '08:00',
            'hora_fin' => '08:30',
            'motivo_consulta' => 'Evaluación de rutina anual',
        ]);

        $response->assertSessionHasNoErrors();
        $cita = CitaModel::where('paciente_id', $paciente->id)->latest()->first();
        $this->assertNotNull($cita);
        $this->assertStringStartsWith('CIT-', $cita->codigo_reserva);
    }

    /**
     * CU-12: Reprogramación de cita médica
     */
    public function test_cu12_reprogramar_cita(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medicoEsp = MedicoEspecialidadModel::first();
        $paciente = PacienteModel::first();

        $cita = CitaModel::create([
            'codigo_reserva' => 'CIT-' . strtoupper(Str::random(8)),
            'paciente_id' => $paciente->id,
            'medico_especialidad_id' => $medicoEsp->id,
            'estado_id' => EstadoCitaModel::PROGRAMADA,
            'fecha' => now()->addDays(5)->format('Y-m-d'),
            'hora_inicio' => '10:00',
            'hora_fin' => '10:30',
            'motivo_consulta' => 'Control de peso y nutrición',
            'fecha_registro' => now(),
        ]);

        $nuevaFecha = now()->addDays(6)->format('Y-m-d');

        // Limpiar cualquier cita en la fecha destino de reprogramación
        CitaModel::where('medico_especialidad_id', $medicoEsp->id)->whereDate('fecha', $nuevaFecha)->delete();

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

    /**
     * CU-13: Cancelación de cita médica con motivo obligatorio
     */
    public function test_cu13_cancelar_cita_con_motivo(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $medicoEsp = MedicoEspecialidadModel::first();
        $paciente = PacienteModel::first();

        $cita = CitaModel::create([
            'codigo_reserva' => 'CIT-' . strtoupper(Str::random(8)),
            'paciente_id' => $paciente->id,
            'medico_especialidad_id' => $medicoEsp->id,
            'estado_id' => EstadoCitaModel::PROGRAMADA,
            'fecha' => now()->addDays(4)->format('Y-m-d'),
            'hora_inicio' => '15:00',
            'hora_fin' => '15:30',
            'motivo_consulta' => 'Molestia abdominal',
            'fecha_registro' => now(),
        ]);

        $response = $this->actingAs($admin)->post("/citas/{$cita->id}/cancelar", [
            'motivo_cancelacion' => 'Paciente suspendió consulta por viaje imprevisto',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado_id' => EstadoCitaModel::CANCELADA,
            'motivo_cancelacion' => 'Paciente suspendió consulta por viaje imprevisto',
        ]);
    }

    /**
     * CU-14: Registro de atención clínica
     */
    public function test_cu14_registrar_atencion_clinica(): void
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
            'hora_inicio' => '09:00',
            'hora_fin' => '09:30',
            'motivo_consulta' => 'Dolor de garganta e inflamación',
            'fecha_registro' => now(),
        ]);

        $response = $this->actingAs($admin)->post('/atenciones', [
            'cita_id' => $cita->id,
            'diagnostico' => 'Faringitis aguda congestiva',
            'tratamiento' => 'Ibuprofeno 400mg cada 8h por 3 días. Gárgaras con solución salina.',
            'notas_adicionales' => 'Reposo de voz por 48 horas.',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('atenciones_clinicas', [
            'cita_id' => $cita->id,
            'diagnostico' => 'Faringitis aguda congestiva',
        ]);
        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado_id' => EstadoCitaModel::ATENDIDA,
        ]);
    }

    /**
     * CU-15: Consulta e historial de citas
     */
    public function test_cu15_historial_de_citas_con_filtros(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/citas?estado_id=' . EstadoCitaModel::ATENDIDA);
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Citas/Index')
                ->has('citas.data')
        );
    }

    /**
     * CU-16: Generación de reportes administrativos y KPIs
     */
    public function test_cu16_reportes_administrativos(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/reportes');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Reportes/Index')
                ->has('metricas.total_citas')
                ->has('metricas.total_atendidas')
        );
    }

    /**
     * CU-17: Exportación nativa a formato Excel .xlsx
     */
    public function test_cu17_exportar_a_excel(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/reportes/export-citas');
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }
}
