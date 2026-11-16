<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoModel extends Model
{
    public const PENDIENTE = 'Pendiente';

    public const APROBADO = 'Aprobado';

    public const RECHAZADO = 'Rechazado';

    public const REEMBOLSADO = 'Reembolsado';

    protected $table = 'pagos';

    protected $fillable = [
        'cita_id',
        'metodo_pago_id',
        'monto',
        'moneda',
        'estado',
        'referencia_transaccion',
        'fecha_pago',
        'motivo_reembolso',
        'fecha_reembolso',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'datetime',
        'fecha_reembolso' => 'datetime',
    ];

    public function cita()
    {
        return $this->belongsTo(CitaModel::class, 'cita_id');
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPagoModel::class, 'metodo_pago_id');
    }

    public function aprobar(string $referencia): void
    {
        $this->update([
            'estado' => self::APROBADO,
            'referencia_transaccion' => $referencia !== '' ? $referencia : $this->referencia_transaccion,
            'fecha_pago' => now(),
        ]);
    }

    public function rechazar(): void
    {
        $this->update([
            'estado' => self::RECHAZADO,
        ]);
    }

    public function reembolsar(string $motivo): void
    {
        $this->update([
            'estado' => self::REEMBOLSADO,
            'motivo_reembolso' => $motivo,
            'fecha_reembolso' => now(),
        ]);
    }

    public function estaAprobado(): bool
    {
        return $this->estado === self::APROBADO;
    }
}
