<?php

return [
    /*
    | Driver intercambiable: fake (sandbox local, sin credenciales) o stripe.
    | QR Simple usa el mismo contrato firmado; sin API publicada en la
    | documentación se demuestra el flujo con el driver fake.
    */
    'driver' => env('PASARELA_DRIVER', 'fake'),

    'api_key' => env('PASARELA_API_KEY', ''),
    'webhook_secret' => env('PASARELA_WEBHOOK_SECRET', ''),
    'url_base' => env('PASARELA_URL', ''),

    'fake_acepta_reembolso' => filter_var(env('PASARELA_FAKE_ACEPTA_REEMBOLSO', true), FILTER_VALIDATE_BOOLEAN),

    'stripe_secret' => env('STRIPE_SECRET', ''),
    'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    /*
    | Stripe no admite BOB. Vacío usa la moneda del pago (BOB) y la API
    | rechazará el cobro; defina una moneda soportada (p. ej. usd) solo si
    | la clínica decide cobrar en esa moneda.
    */
    'stripe_currency' => env('STRIPE_CURRENCY', ''),

    /*
    | Bolivianos por 1 unidad de la moneda de Stripe (p. ej. 6.96 para usd).
    | 0 = sin conversion (el monto en BOB se envia tal cual).
    */
    'stripe_tasa_cambio' => (float) env('STRIPE_TASA_CAMBIO', 0),
];
