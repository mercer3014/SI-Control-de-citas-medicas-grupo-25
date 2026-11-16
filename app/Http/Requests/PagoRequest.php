<?php

namespace App\Http\Requests;

use App\Models\UsuarioModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof UsuarioModel && $user->hasRole('Paciente');
    }

    public function rules(): array
    {
        return [
            'cita_id' => ['required', 'integer', 'exists:citas,id'],
            'metodo_pago_id' => [
                'required',
                'integer',
                Rule::exists('metodos_pago', 'id')->where(fn ($q) => $q->where('estado', true)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cita_id.required' => 'Debe seleccionar una cita.',
            'cita_id.exists' => 'La cita indicada no existe.',
            'metodo_pago_id.required' => 'Debe elegir un método de pago.',
            'metodo_pago_id.exists' => 'El método de pago no está habilitado.',
        ];
    }
}
