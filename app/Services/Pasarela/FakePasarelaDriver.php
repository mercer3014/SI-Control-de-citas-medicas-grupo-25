<?php

namespace App\Services\Pasarela;

use App\Models\PagoModel;

/**
 * Sandbox local. No llama a ninguna red ni pide datos de tarjeta.
 * La firma es HMAC-SHA256 del cuerpo canónico referencia|estado|monto|moneda.
 */
class FakePasarelaDriver
{
    public function __construct(private readonly string $secret) {}

    public function crearTransaccion(string $monto, string $referencia): string
    {
        return url('/pagos/sandbox/'.$referencia);
    }

    public function verificarFirma(array $payload, string $firma): bool
    {
        if ($firma === '' || $this->secret === '') {
            return false;
        }

        return hash_equals($this->firmar($payload), $firma);
    }

    public function firmar(array $payload): string
    {
        $canon = implode('|', [
            (string) ($payload['referencia'] ?? ''),
            (string) ($payload['estado'] ?? ''),
            $this->normalizarMonto($payload['monto'] ?? ''),
            (string) ($payload['moneda'] ?? 'BOB'),
        ]);

        return hash_hmac('sha256', $canon, $this->secret);
    }

    public function consultarEstado(string $referencia): string
    {
        return PagoModel::where('referencia_transaccion', $referencia)->value('estado')
            ?? PagoModel::PENDIENTE;
    }

    public function reembolsar(string $referencia, string $monto): bool
    {
        return (bool) config('pasarela.fake_acepta_reembolso', true);
    }

    public function traducirNotificacion(array $payload): array
    {
        return [
            'referencia' => $payload['referencia'] ?? null,
            'estado' => $payload['estado'] ?? null,
            'monto' => $payload['monto'] ?? null,
            'moneda' => $payload['moneda'] ?? 'BOB',
        ];
    }

    private function normalizarMonto(mixed $monto): string
    {
        if ($monto === null || $monto === '') {
            return '';
        }

        return number_format((float) $monto, 2, '.', '');
    }
}
