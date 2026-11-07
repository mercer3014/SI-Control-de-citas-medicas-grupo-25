<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PerfilMedicoController;
use App\Http\Controllers\HorarioAtencionController;
use App\Http\Controllers\AusenciaMedicaController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\AtencionClinicaController;
use App\Http\Controllers\ReporteController;

// Rutas Públicas / Autenticación
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');

    Route::get('/reset-password', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

// Rutas Protegidas
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // MÓDULO 1: Seguridad, Acceso y Auditoría
    Route::middleware('role:Administrador')->group(function () {
        Route::patch('usuarios/{usuario}/toggle', [UsuarioController::class, 'toggle'])->name('usuarios.toggle');
        Route::resource('usuarios', UsuarioController::class)->except(['create', 'edit', 'show']);
        Route::get('roles', [RolController::class, 'index'])->name('roles.index');
        Route::put('roles/{rol}', [RolController::class, 'update'])->name('roles.update');
        Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
        Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
        Route::get('backups/download', [BackupController::class, 'download'])->name('backups.download');
    });

    // MÓDULO 2: Catálogos Clínicos y Personal
    // Especialidades (Admin)
    Route::middleware('role:Administrador')->group(function () {
        Route::resource('especialidades', EspecialidadController::class)->except(['create', 'edit', 'show']);
    });

    // Médicos (Admin y Recepción)
    Route::middleware('role:Administrador,Recepcionista')->group(function () {
        Route::resource('medicos', MedicoController::class)->except(['create', 'edit']);
    });

    // Pacientes (Recepcionista y Admin)
    Route::middleware('role:Administrador,Recepcionista')->group(function () {
        Route::resource('pacientes', PacienteController::class)->except(['create', 'edit']);
    });

    // Perfil Médico (CU-07)
    Route::middleware('role:Administrador,Médico')->group(function () {
        Route::get('/mi-perfil-medico', [PerfilMedicoController::class, 'index'])->name('perfil-medico.index');
        Route::put('/mi-perfil-medico', [PerfilMedicoController::class, 'update'])->name('perfil-medico.update');
    });

    // MÓDULO 3: Disponibilidad y Turnos Médicos (CU-09 y CU-10)
    Route::middleware('role:Administrador,Recepcionista')->group(function () {
        Route::get('/horarios', [HorarioAtencionController::class, 'index'])->name('horarios.index');
        Route::post('/horarios', [HorarioAtencionController::class, 'store'])->name('horarios.store');
        Route::put('/horarios/{horario}', [HorarioAtencionController::class, 'update'])->name('horarios.update');
        Route::patch('/horarios/{horario}/toggle', [HorarioAtencionController::class, 'toggle'])->name('horarios.toggle');
        Route::delete('/horarios/{horario}', [HorarioAtencionController::class, 'destroy'])->name('horarios.destroy');
    });

    Route::middleware('role:Administrador,Recepcionista,Médico')->group(function () {
        Route::get('/ausencias', [AusenciaMedicaController::class, 'index'])->name('ausencias.index');
        Route::post('/ausencias', [AusenciaMedicaController::class, 'store'])->name('ausencias.store');
        Route::delete('/ausencias/{ausencia}', [AusenciaMedicaController::class, 'destroy'])->name('ausencias.destroy');

        // MÓDULO 4: Agenda y Citas Médicas (CU-11, CU-12, CU-13, CU-15)
        Route::get('/citas', [CitaController::class, 'index'])->name('citas.index');
        Route::get('/citas/slots-disponibles', [CitaController::class, 'slots'])->name('citas.slots');
        Route::post('/citas', [CitaController::class, 'store'])->name('citas.store');
        Route::post('/citas/{cita}/reprogramar', [CitaController::class, 'reprogramar'])->name('citas.reprogramar');
        Route::post('/citas/{cita}/cancelar', [CitaController::class, 'cancelar'])->name('citas.cancelar');

        // MÓDULO 5: Atención Médica y Registro Clínico (CU-14)
        Route::get('/atenciones', [AtencionClinicaController::class, 'index'])->name('atenciones.index');
        Route::post('/atenciones', [AtencionClinicaController::class, 'store'])->name('atenciones.store');
    });

    // MÓDULO 6: Reportes Administrativos y Exportación Excel (CU-16 y CU-17)
    Route::middleware('role:Administrador,Recepcionista')->group(function () {
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/export-citas', [ReporteController::class, 'exportCitas'])->name('reportes.export-citas');
        Route::get('/reportes/export-pacientes', [ReporteController::class, 'exportPacientes'])->name('reportes.export-pacientes');
        Route::get('/reportes/export-general', [ReporteController::class, 'exportGeneral'])->name('reportes.export-general');
    });
});
