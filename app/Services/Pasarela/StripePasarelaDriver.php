<?php

namespace App\Services\Pasarela;

use App\Models\PagoModel;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Driver Stripe Checkout (HTTPS). No persiste datos de tarjeta.
 * BOB no es una moneda de Stripe: use STRIPE_CURRENCY si la clínica cobra en otra.
 */
class StripePasarelaDriver
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $webhookSecret,
        private readonly string $currency,
        private readonly float $tasaCambio = 0.0,
    ) {}

    public function crearTransaccion(string $monto, string $referencia): string
    {
        if ($this->secretKey === '') {
            throw new RuntimeException('STRIPE_SECRET no está configurado.');
        }

        $moneda = strtolower($this->currency !== '' ? $this->currency : 'bob');
        $importe = (float) $monto;
        if ($moneda !== 'bob' && $this->tasaCambio > 0) {
            $importe = $importe / $this->tasaCambio;
        }

        $response = Http::withToken($this->secretKey)
            ->asForm()
            ->timeout(15)
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => url('/mis-citas?comprobante='.$referencia),
                'cancel_url' => url('/mis-citas'),
                'client_reference_id' => $referencia,
                'metadata[referencia]' => $referencia,
                'line_items[0][quantity]' => 1,
                'line_items[0][price_data][currency]' => $moneda,
                'line_items[0][price_data][unit_amount]' => (int) round($importe * 100),
                'line_items[0][price_data][product_data][name]' => 'Consulta médica Clínica Vida Salud',
            ]);

        if (! $response->successful() || ! $response->json('url')) {
            throw new RuntimeException('Stripe no devolvió una URL de checkout.');
        }

        return (string) $response->json('url');
    }

    public function verificarFirma(array $payload, string $firma): bool
    {
        if ($this->webhookSecret === '' || $firma === '' || ! isset($payload['_raw'])) {
            return false;
        }

        $partes = [];
        foreach (explode(',', $firma) as $trozo) {
            [$clave, $valor] = array_pad(explode('=', trim($trozo), 2), 2, null);
            if ($clave !== null) {
                $partes[$clave] = $valor;
            }
        }

        $timestamp = $partes['t'] ?? null;
        $v1 = $partes['v1'] ?? null;
        if (! $timestamp || ! $v1) {
            return false;
        }

        $esperada = hash_hmac('sha256', $timestamp.'.'.$payload['_raw'], $this->webhookSecret);

        return hash_equals($esperada, $v1);
    }

    public function consultarEstado(string $referencia): string
    {
        if ($this->secretKey === '') {
            return PagoModel::PENDIENTE;
        }

        $response = Http::withToken($this->secretKey)
            ->timeout(15)
            ->get('https://api.stripe.com/v1/checkout/sessions/search', [
                'query' => 'client_reference_id:"'.$referencia.'"',
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            return PagoModel::PENDIENTE;
        }

        $sesion = $response->json('data.0');
        if (! is_array($sesion)) {
            return PagoModel::PENDIENTE;
        }

        return ($sesion['payment_status'] ?? '') === 'paid'
            ? PagoModel::APROBADO
            : PagoModel::PENDIENTE;
    }

    public function reembolsar(string $referencia, string $monto): bool
    {
        if ($this->secretKey === '') {
            return false;
        }

        $busqueda = Http::withToken($this->secretKey)
            ->timeout(15)
            ->get('https://api.stripe.com/v1/checkout/sessions/search', [
                'query' => 'client_reference_id:"'.$referencia.'"',
                'limit' => 1,
            ]);

        $paymentIntent = $busqueda->json('data.0.payment_intent');
        if (! $busqueda->successful() || ! $paymentIntent) {
            return false;
        }

        $refund = Http::withToken($this->secretKey)
            ->asForm()
            ->timeout(15)
            ->post('https://api.stripe.com/v1/refunds', [
                'payment_intent' => $paymentIntent,
                'amount' => (int) round(((float) $monto) * 100),
            ]);

        return $refund->successful();
    }

    public function traducirNotificacion(array $payload): array
    {
        $tipo = $payload['type'] ?? '';
        $objeto = $payload['data']['object'] ?? [];
        $referencia = $objeto['client_reference_id']
            ?? ($objeto['metadata']['referencia'] ?? null);

        if ($tipo === 'checkout.session.completed' && ($objeto['payment_status'] ?? '') === 'paid') {
            $monto = isset($objeto['amount_total'])
                ? number_format(((int) $objeto['amount_total']) / 100, 2, '.', '')
                : null;

            return [
                'referencia' => $referencia,
                'estado' => PagoModel::APROBADO,
                'monto' => $monto,
                'moneda' => strtoupper((string) ($objeto['currency'] ?? 'BOB')),
            ];
        }

        if (in_array($tipo, ['checkout.session.async_payment_failed', 'payment_intent.payment_failed'], true)) {
            return [
                'referencia' => $referencia,
                'estado' => PagoModel::RECHAZADO,
                'monto' => null,
                'moneda' => 'BOB',
            ];
        }

        if ($tipo === 'charge.refunded') {
            return [
                'referencia' => $referencia,
                'estado' => PagoModel::REEMBOLSADO,
                'monto' => null,
                'moneda' => 'BOB',
            ];
        }

        return [
            'referencia' => null,
            'estado' => null,
            'monto' => null,
            'moneda' => 'BOB',
        ];
    }
}
