<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CitaModel extends Model
{
    protected $table = 'citas';

    protected $fillable = [
        'codigo_reserva',
        'paciente_id',
        'medico_especialidad_id',
        'estado_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'motivo_consulta',
        'motivo_cancelacion',
        'fecha_registro',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_registro' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(PacienteModel::class, 'paciente_id');
    }

    public function medicoEspecialidad()
    {
        return $this->belongsTo(MedicoEspecialidadModel::class, 'medico_especialidad_id');
    }

    public function estado()
    {
        return $this->belongsTo(EstadoCitaModel::class, 'estado_id');
    }

    public function atencion()
    {
        return $this->hasOne(AtencionClinicaModel::class, 'cita_id');
    }

    public function pagos()
    {
        return $this->hasMany(PagoModel::class, 'cita_id');
    }

    public function confirmar(): void
    {
        $this->update([
            'estado_id' => EstadoCitaModel::CONFIRMADA,
        ]);
    }

    public function cancelar(string $motivo): void
    {
        $this->update([
            'estado_id' => EstadoCitaModel::CANCELADA,
            'motivo_cancelacion' => $motivo,
        ]);
    }
}
