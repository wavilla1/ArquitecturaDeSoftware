<?php

namespace App\Services\CryptoMarket;

use App\Contracts\CryptoMarket;

/**
 * Implementación sin red: se usa en pruebas o para desactivar la integración.
 */
class NullCryptoMarket implements CryptoMarket
{
    public function quotes(): array
    {
        return [];
    }
}
