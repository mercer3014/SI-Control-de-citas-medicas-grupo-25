<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago')->onUpdate('cascade')->onDelete('restrict');
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 3)->default('BOB');
            $table->string('estado', 20)->default('Pendiente');
            $table->string('referencia_transaccion', 100)->nullable()->unique();
            $table->timestamp('fecha_pago')->nullable();
            $table->text('motivo_reembolso')->nullable();
            $table->timestamp('fecha_reembolso')->nullable();
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE pagos ADD CONSTRAINT chk_pagos_estado CHECK (estado IN ('Pendiente', 'Aprobado', 'Rechazado', 'Reembolsado'))");
            DB::statement('ALTER TABLE pagos ADD CONSTRAINT chk_pagos_monto CHECK (monto > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('metodos_pago');
    }
};
