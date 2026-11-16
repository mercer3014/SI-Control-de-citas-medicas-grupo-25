<?php

namespace App\Services;

use App\Services\Pasarela\FakePasarelaDriver;
use App\Services\Pasarela\StripePasarelaDriver;

/**
 * Implementación de InterfazPasarela. El driver (fake|stripe) sale de .env
 * para cambiar de proveedor sin tocar PagoController.
 */
class PasarelaPagoService implements InterfazPasarela
{
    private string $driverName;

    private string $apiKey;

    private string $webhookSecret;

    private string $urlBase;

    private FakePasarelaDriver $fake;

    private StripePasarelaDriver $stripe;

    public function __construct()
    {
        $this->driverName = (string) config('pasarela.driver', 'fake');
        $this->apiKey = (string) config('pasarela.api_key', '');
        $this->webhookSecret = (string) config('pasarela.webhook_secret', '');
        $this->urlBase = (string) config('pasarela.url_base', '');

        if ($this->webhookSecret === '' && $this->driverName !== 'stripe') {
            $this->webhookSecret = 'fake-local-sandbox';
        }

        $this->fake = new FakePasarelaDriver($this->webhookSecret);
        $this->stripe = new StripePasarelaDriver(
            (string) config('pasarela.stripe_secret', $this->apiKey),
            (string) config('pasarela.stripe_webhook_secret', $this->webhookSecret),
            (string) config('pasarela.stripe_currency', ''),
        );
    }

    public function crearTransaccion(string $monto, string $referencia): string
    {
        return $this->usaStripe()
            ? $this->stripe->crearTransaccion($monto, $referencia)
            : $this->fake->crearTransaccion($monto, $referencia);
    }

    public function verificarFirma(array $payload, string $firma): bool
    {
        return $this->usaStripe()
            ? $this->stripe->verificarFirma($payload, $firma)
            : $this->fake->verificarFirma($payload, $firma);
    }

    public function consultarEstado(string $referencia): string
    {
        return $this->usaStripe()
            ? $this->stripe->consultarEstado($referencia)
            : $this->fake->consultarEstado($referencia);
    }

    public function reembolsar(string $referencia, string $monto): bool
    {
        return $this->usaStripe()
            ? $this->stripe->reembolsar($referencia, $monto)
            : $this->fake->reembolsar($referencia, $monto);
    }

    public function firmar(array $payload): string
    {
        return $this->fake->firmar($payload);
    }

    public function traducirNotificacion(array $payload): array
    {
        return $this->usaStripe()
            ? $this->stripe->traducirNotificacion($payload)
            : $this->fake->traducirNotificacion($payload);
    }

    public function driver(): string
    {
        return $this->driverName;
    }

    private function usaStripe(): bool
    {
        return $this->driverName === 'stripe';
    }
}
