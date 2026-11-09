<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Listing;
use App\Models\MarketplaceTransaction;
use App\Models\Nft;
use App\Models\NftCollection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NftCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        config(['monoverse.demo' => false]);
    }

    public function test_public_api_matches_the_contract_without_authentication_or_session(): void
    {
        $response = $this->get('/api/v1/nfts')->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta', ['current_page' => 1, 'per_page' => 20, 'total' => 3, 'last_page' => 1]);
        $this->assertSame(['data', 'meta'], array_keys($response->json()));
        $this->assertSame([
            'id' => 1,
            'name' => 'Nebula Echoes #1',
            'description' => 'Fragmentos de color nacidos en una nebulosa digital.',
            'collection' => 'Nebula Echoes',
            'price' => '3.20',
            'currency' => 'MONO',
            'seller' => 'juanjose',
            'image_url' => null,
            'detail_url' => route('nfts.show', 1),
        ], $response->json('data.0'));
        $this->assertEmpty($response->headers->getCookies());
        foreach ($response->json('data') as $item) {
            $this->assertSame(array_keys($response->json('data.0')), array_keys($item));
            $this->get($item['detail_url'])->assertOk()->assertSee($item['collection'])->assertSee('Ingresar para comprar');
        }
    }

    public function test_catalog_excludes_unavailable_or_moderated_nfts(): void
    {
        $first = Listing::where('type', 'fixed')->orderBy('id')->firstOrFail();
        $first->update(['status' => 'sold']);
        $this->getJson('/api/v1/nfts')->assertJsonCount(2, 'data');
        $remaining = Listing::where('type', 'fixed')->where('status', 'active')->get();
        $remaining[0]->nft->collection->update(['status' => 'hidden']);
        $remaining[1]->seller->update(['suspended_at' => now()]);
        $this->getJson('/api/v1/nfts')->assertExactJson([
            'data' => [],
            'meta' => ['current_page' => 1, 'per_page' => 20, 'total' => 0, 'last_page' => 1],
        ]);
    }

    public function test_catalog_rejects_inconsistent_owner_or_unlisted_inventory(): void
    {
        $listing = Listing::where('type', 'fixed')->firstOrFail();
        $listing->nft->update(['owner_id' => User::where('id', '!=', $listing->seller_id)->firstOrFail()->id]);
        $this->getJson('/api/v1/nfts')->assertJsonCount(2, 'data');
        Nft::query()->update(['in_sale' => false]);
        $this->getJson('/api/v1/nfts')->assertJsonCount(0, 'data');
    }

    public function test_pagination_is_stable_and_bounded_to_twenty_items(): void
    {
        $collection = NftCollection::firstOrFail();
        $owner = User::firstOrFail();
        for ($number = 3; $number <= 24; $number++) {
            $nft = Nft::create([
                'collection_id' => $collection->id, 'owner_id' => $owner->id,
                'token_number' => $number, 'token_hash' => hash('sha256', 'pagination-'.$number), 'in_sale' => true,
            ]);
            Listing::create(['nft_id' => $nft->id, 'seller_id' => $owner->id, 'price' => '1.50', 'type' => 'fixed', 'status' => 'active']);
        }
        $first = $this->getJson('/api/v1/nfts?page=1&per_page=10000')->assertJsonCount(20, 'data')->assertJsonPath('meta.total', 25);
        $second = $this->getJson('/api/v1/nfts?page=2')->assertJsonCount(5, 'data')->assertJsonPath('meta.last_page', 2);
        $this->assertEmpty(array_intersect(array_column($first->json('data'), 'id'), array_column($second->json('data'), 'id')));
        $this->getJson('/api/v1/nfts?page=3')->assertJsonCount(0, 'data')->assertJsonPath('meta.current_page', 3);
    }

    public function test_invalid_page_and_unsupported_method_return_json_without_accept_header(): void
    {
        foreach (['0', '-1', 'abc', '1.5', '1000001', ''] as $page) {
            $this->get('/api/v1/nfts?page='.$page)->assertStatus(422)
                ->assertHeader('Content-Type', 'application/json')->assertJsonValidationErrors('page');
        }
        $this->post('/api/v1/nfts')->assertStatus(405)->assertHeader('Content-Type', 'application/json');
        $this->get('/api/v1/missing')->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    public function test_cross_origin_reading_is_allowed_without_credentials(): void
    {
        $this->withHeader('Origin', 'https://partner.example.test')->get('/api/v1/nfts')
            ->assertOk()->assertHeader('Access-Control-Allow-Origin', '*')->assertHeaderMissing('Access-Control-Allow-Credentials');
        $this->withHeaders([
            'Origin' => 'https://partner.example.test',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'Accept',
        ])->options('/api/v1/nfts')->assertNoContent()->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function test_query_has_no_market_side_effects_and_excludes_expired_auctions(): void
    {
        $auction = Listing::where('type', 'auction')->firstOrFail();
        $auction->update(['closes_at' => now()->subMinute()]);
        $blocks = Block::count();
        $transactions = MarketplaceTransaction::count();
        $balances = User::orderBy('id')->pluck('balance')->all();
        $this->getJson('/api/v1/nfts')->assertOk()->assertJsonCount(3, 'data');
        $this->assertSame($blocks, Block::count());
        $this->assertSame($transactions, MarketplaceTransaction::count());
        $this->assertSame($balances, User::orderBy('id')->pluck('balance')->all());
        $this->assertSame('active', $auction->fresh()->status);
    }

    public function test_image_urls_work_and_hidden_details_are_not_exposed(): void
    {
        $collection = NftCollection::firstOrFail();
        $collection->update([
            'image_data' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jF9sAAAAASUVORK5CYII=',
            'image_mime' => 'image/png',
        ]);
        $response = $this->getJson('/api/v1/nfts')->assertJsonPath('data.0.image_url', route('collections.image', $collection));
        $this->get($response->json('data.0.image_url'))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/nfts/1')->assertOk()->assertSee($response->json('data.0.image_url'));
        $collection->update(['status' => 'hidden']);
        $this->get('/nfts/1')->assertNotFound()->assertDontSee('Nebula Echoes');
        $this->get('/nfts/999999')->assertNotFound();
        $this->get('/nfts/abc')->assertNotFound();
    }

    public function test_purchase_from_details_returns_to_market_and_removes_catalog_item(): void
    {
        $listing = Listing::where('type', 'fixed')->firstOrFail();
        $buyer = User::where('handle', 'joseluis')->firstOrFail();
        $this->actingAs($buyer)->get(route('nfts.show', $listing->nft))->assertOk()->assertSee('Comprar ahora');
        $this->post(route('listings.buy', $listing), ['return_to_market' => '1'])->assertRedirect(route('marketplace'));
        $this->get(route('nfts.show', $listing->nft))->assertNotFound();
        $response = $this->getJson('/api/v1/nfts')->assertJsonCount(2, 'data');
        $this->assertNotContains($listing->nft_id, array_column($response->json('data'), 'id'));
    }

    public function test_api_rate_limit_returns_json_and_retry_after(): void
    {
        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->get('/api/v1/nfts')->assertOk();
        }
        $this->get('/api/v1/nfts')->assertStatus(429)
            ->assertHeader('Content-Type', 'application/json')->assertHeader('Retry-After');
    }
}
