<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\UsuarioModel;

class ReportesYExportacionExcelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_modulo_reportes_calcula_kpis_administrativos(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/reportes');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Reportes/Index')
                ->has('metricas.total_citas')
                ->has('metricas.total_atendidas')
                ->has('metricas.total_canceladas')
                ->has('metricas.ingresos_totales')
                ->has('rendimientoMedicos')
                ->has('demandaEspecialidades')
        );
    }

    public function test_exportar_citas_a_excel_genera_archivo_xlsx_valido(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/reportes/export-citas');

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_exportar_pacientes_a_excel_genera_archivo_xlsx_valido(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/reportes/export-pacientes');

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }
}
