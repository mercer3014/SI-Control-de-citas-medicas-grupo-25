<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportExcelService
{
    /**
     * Exporta el listado detallado de citas a un archivo .xlsx estilizado.
     */
    public function exportarCitas($citas): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Citas Médicas');

        // 1. Título Corporativo
        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'CLÍNICA VIDA SALUD — REPORTE DE CITAS MÉDICAS');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('0F172A');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // Subtítulo con fecha
        $sheet->mergeCells('A2:J2');
        $sheet->setCellValue('A2', 'Generado el: ' . now()->format('d/m/Y H:i:s') . ' | Sistema Integral de Gestión');
        $sheet->getStyle('A2')->getFont()->setSize(10)->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // 2. Encabezados de Columna
        $headers = [
            'A4' => 'Código',
            'B4' => 'Fecha',
            'C4' => 'Horario',
            'D4' => 'Paciente',
            'E4' => 'CI Paciente',
            'F4' => 'Médico',
            'G4' => 'Especialidad',
            'H4' => 'Arancel (Bs.)',
            'I4' => 'Estado',
            'J4' => 'Diagnóstico / Motivo',
        ];

        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col, $text);
        }

        $sheet->getStyle('A4:J4')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A4:J4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('1E293B');
        $sheet->getStyle('A4:J4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(24);

        // 3. Filas de Datos
        $row = 5;
        foreach ($citas as $c) {
            $arancel = $c->medicoEspecialidad?->precio_consulta ?? 0;
            $motivoOdiag = $c->atencion ? $c->atencion->diagnostico : ($c->motivo_cancelacion ? ('Cancelada: ' . $c->motivo_cancelacion) : $c->motivo_consulta);

            $sheet->setCellValue("A{$row}", $c->codigo_reserva);
            $sheet->setCellValue("B{$row}", $c->fecha ? $c->fecha->format('d/m/Y') : '-');
            $sheet->setCellValue("C{$row}", substr($c->hora_inicio, 0, 5) . ' - ' . substr($c->hora_fin, 0, 5));
            $sheet->setCellValue("D{$row}", $c->paciente?->persona?->nombre_completo ?? 'N/A');
            $sheet->setCellValue("E{$row}", $c->paciente?->persona?->ci ?? '-');
            $sheet->setCellValue("F{$row}", 'Dr(a). ' . ($c->medicoEspecialidad?->medico?->persona?->nombre_completo ?? 'N/A'));
            $sheet->setCellValue("G{$row}", $c->medicoEspecialidad?->especialidad?->nombre ?? '-');
            $sheet->setCellValue("H{$row}", (float)$arancel);
            $sheet->setCellValue("I{$row}", $c->estado?->nombre ?? 'N/A');
            $sheet->setCellValue("J{$row}", $motivoOdiag);

            // Formato moneda
            $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

            // Bordes suaves
            $sheet->getStyle("A{$row}:J{$row}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setARGB('E2E8F0');

            $row++;
        }

        // Autoajustar ancho de columnas
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->generarDescargaResponse($spreadsheet, 'Reporte_Citas_Clinica_Vida_Salud_' . date('Ymd_His') . '.xlsx');
    }

    /**
     * Exporta el padrón completo de pacientes a .xlsx.
     */
    public function exportarPacientes($pacientes): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Padrón de Pacientes');

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'CLÍNICA VIDA SALUD — PADRÓN GENERAL DE PACIENTES');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('0F172A');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $sheet->mergeCells('A2:I2');
        $sheet->setCellValue('A2', 'Generado el: ' . now()->format('d/m/Y H:i:s'));
        $sheet->getStyle('A2')->getFont()->setSize(10)->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = [
            'A4' => 'CI / Documento',
            'B4' => 'Nombre Completo',
            'C4' => 'Género',
            'D4' => 'Fecha Nacimiento',
            'E4' => 'Teléfono',
            'F4' => 'Grupo Sanguíneo',
            'G4' => 'Contacto de Emergencia',
            'H4' => 'Tel. Emergencia',
            'I4' => 'Estado',
        ];

        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col, $text);
        }

        $sheet->getStyle('A4:I4')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A4:I4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('1E293B');
        $sheet->getRowDimension(4)->setRowHeight(24);

        $row = 5;
        foreach ($pacientes as $p) {
            $sheet->setCellValue("A{$row}", $p->persona?->ci ?? '-');
            $sheet->setCellValue("B{$row}", $p->persona?->nombre_completo ?? 'N/A');
            $sheet->setCellValue("C{$row}", $p->persona?->genero === 'M' ? 'Masculino' : ($p->persona?->genero === 'F' ? 'Femenino' : 'Otro'));
            $sheet->setCellValue("D{$row}", $p->persona?->fecha_nacimiento ? $p->persona->fecha_nacimiento->format('d/m/Y') : '-');
            $sheet->setCellValue("E{$row}", $p->persona?->telefono ?? 'Sin teléfono');
            $sheet->setCellValue("F{$row}", $p->tipo_sangre ?? 'No reg.');
            $sheet->setCellValue("G{$row}", $p->contacto_emergencia_nombre ?? '-');
            $sheet->setCellValue("H{$row}", $p->contacto_emergencia_telefono ?? '-');
            $sheet->setCellValue("I{$row}", $p->estado ? 'Activo' : 'Inactivo');

            $sheet->getStyle("A{$row}:I{$row}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setARGB('E2E8F0');
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->generarDescargaResponse($spreadsheet, 'Padron_Pacientes_Clinica_Vida_Salud_' . date('Ymd_His') . '.xlsx');
    }

    /**
     * Exporta el resumen ejecutivo y financiero a .xlsx.
     */
    public function exportarReporteGeneral(array $metricas, $rendimientoMedicos, $demandaEspecialidades): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen Ejecutivo');

        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'CLÍNICA VIDA SALUD — BALANCE Y MÉTRICAS DE GESTIÓN');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('0F172A');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // Tabla de KPIs
        $sheet->setCellValue('A4', 'INDICADOR CLAVE (KPI)');
        $sheet->setCellValue('B4', 'VALOR');
        $sheet->getStyle('A4:B4')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A4:B4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('1E293B');

        $sheet->setCellValue('A5', 'Total de Citas Registradas');
        $sheet->setCellValue('B5', $metricas['total_citas'] ?? 0);

        $sheet->setCellValue('A6', 'Citas Atendidas Satisfactoriamente');
        $sheet->setCellValue('B6', $metricas['total_atendidas'] ?? 0);

        $sheet->setCellValue('A7', 'Citas Canceladas');
        $sheet->setCellValue('B7', $metricas['total_canceladas'] ?? 0);

        $sheet->setCellValue('A8', 'Tasa de Cumplimiento de Atención');
        $sheet->setCellValue('B8', ($metricas['tasa_cumplimiento'] ?? 0) . ' %');

        $sheet->setCellValue('A9', 'Ingresos Estimados por Consultas Atendidas');
        $sheet->setCellValue('B9', (float)($metricas['ingresos_totales'] ?? 0));
        $sheet->getStyle('B9')->getNumberFormat()->setFormatCode('"Bs. "#,##0.00');

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->generarDescargaResponse($spreadsheet, 'Resumen_Ejecutivo_Clinica_Vida_Salud_' . date('Ymd_His') . '.xlsx');
    }

    private function generarDescargaResponse(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate',
            'Pragma' => 'public',
        ]);
    }
}
