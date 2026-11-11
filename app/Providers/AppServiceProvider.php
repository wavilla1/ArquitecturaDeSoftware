<?php

namespace App\Providers;

use App\Contracts\CryptoMarket;
use App\Services\CryptoMarket\CoinGeckoMarket;
use App\Services\CryptoMarket\NullCryptoMarket;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Inversión de dependencias: quien necesite cotizaciones pide el
        // contrato CryptoMarket y aquí se decide qué proveedor lo cumple.
        $this->app->singleton(CryptoMarket::class, fn () => match (config('services.coingecko.driver')) {
            'coingecko' => new CoinGeckoMarket,
            default => new NullCryptoMarket,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
