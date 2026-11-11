<?php

namespace App\Contracts;

/**
 * Fuente de cotizaciones de criptomonedas reales.
 *
 * Los controladores dependen de esta abstracción y no de un proveedor
 * concreto; la implementación se elige en AppServiceProvider.
 */
interface CryptoMarket
{
    /**
     * Cotizaciones vigentes, o una lista vacía si la fuente no está disponible.
     *
     * @return list<array{name: string, symbol: string, price_usd: float, change_24h: float|null}>
     */
    public function quotes(): array;
}
