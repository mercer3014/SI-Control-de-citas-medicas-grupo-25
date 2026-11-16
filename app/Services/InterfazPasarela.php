<?php

namespace App\Services;

/**
 * Contrato de la pasarela externa (CU-18 y CU-19).
 * El monto viaja como cadena decimal ("150.00"): PHP no tiene un tipo decimal.
 */
interface InterfazPasarela
{
    public function crearTransaccion(string $monto, string $referencia): string;

    public function verificarFirma(array $payload, string $firma): bool;

    public function consultarEstado(string $referencia): string;

    public function reembolsar(string $referencia, string $monto): bool;
}
