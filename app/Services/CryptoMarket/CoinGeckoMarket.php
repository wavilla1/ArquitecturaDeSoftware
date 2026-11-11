<?php

namespace App\Services\CryptoMarket;

use App\Contracts\CryptoMarket;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cotizaciones tomadas de la API pública de CoinGecko (sin API key).
 */
class CoinGeckoMarket implements CryptoMarket
{
    private const CACHE_KEY = 'crypto-market.coingecko.quotes';

    public function quotes(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $quotes = $this->fetch();

        // Un fallo se recuerda poco tiempo para no frenar cada carga del mercado.
        Cache::put(self::CACHE_KEY, $quotes, $quotes === [] ? 60 : (int) config('services.coingecko.cache_seconds'));

        return $quotes;
    }

    private function fetch(): array
    {
        try {
            $response = Http::baseUrl(config('services.coingecko.base_url'))
                ->acceptJson()
                ->timeout(3)
                ->get('/coins/markets', [
                    'vs_currency' => 'usd',
                    'ids' => implode(',', config('services.coingecko.coins')),
                ]);

            if (! $response->successful() || ! is_array($response->json())) {
                Log::warning('CoinGecko respondió sin cotizaciones.', ['status' => $response->status()]);

                return [];
            }

            return collect($response->json())
                ->filter(fn ($coin) => is_array($coin) && isset($coin['name'], $coin['symbol']) && is_numeric($coin['current_price'] ?? null))
                ->map(fn (array $coin) => [
                    'name' => (string) $coin['name'],
                    'symbol' => mb_strtoupper((string) $coin['symbol']),
                    'price_usd' => (float) $coin['current_price'],
                    'change_24h' => is_numeric($coin['price_change_percentage_24h'] ?? null)
                        ? round((float) $coin['price_change_percentage_24h'], 2)
                        : null,
                ])
                ->values()
                ->all();
        } catch (Throwable $exception) {
            Log::warning('No se pudo consultar CoinGecko.', ['error' => $exception->getMessage()]);

            return [];
        }
    }
}
