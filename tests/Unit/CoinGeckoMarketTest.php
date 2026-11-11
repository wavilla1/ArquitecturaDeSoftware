<?php

namespace Tests\Unit;

use App\Services\CryptoMarket\CoinGeckoMarket;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CoinGeckoMarketTest extends TestCase
{
    public function test_it_maps_the_coingecko_response_and_caches_it(): void
    {
        Http::fake([
            'api.coingecko.com/*' => Http::response([
                ['id' => 'bitcoin', 'name' => 'Bitcoin', 'symbol' => 'btc', 'current_price' => 101250.5, 'price_change_percentage_24h' => 1.23456],
                ['id' => 'ethereum', 'name' => 'Ethereum', 'symbol' => 'eth', 'current_price' => 3400, 'price_change_percentage_24h' => null],
                ['id' => 'roto', 'name' => 'Sin precio', 'symbol' => 'xxx', 'current_price' => null],
            ]),
        ]);
        $market = new CoinGeckoMarket;

        $quotes = $market->quotes();

        $this->assertSame([
            ['name' => 'Bitcoin', 'symbol' => 'BTC', 'price_usd' => 101250.5, 'change_24h' => 1.23],
            ['name' => 'Ethereum', 'symbol' => 'ETH', 'price_usd' => 3400.0, 'change_24h' => null],
        ], $quotes);
        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://api.coingecko.com/api/v3/coins/markets')
            && $request['vs_currency'] === 'usd'
            && $request['ids'] === 'bitcoin,ethereum,solana');

        // La segunda lectura sale de la caché: no hay otra llamada al tercero.
        $this->assertSame($quotes, $market->quotes());
        Http::assertSentCount(1);
    }

    public function test_it_returns_no_quotes_when_coingecko_fails(): void
    {
        Http::fake(['api.coingecko.com/*' => Http::response(['error' => 'rate limited'], 429)]);

        $this->assertSame([], (new CoinGeckoMarket)->quotes());
    }
}
