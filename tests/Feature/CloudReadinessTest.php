<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Listing;
use App\Models\Nft;
use App\Models\NftCollection;
use App\Models\User;
use App\Services\ChainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CloudReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        config(['monoverse.demo' => false]);
    }

    public function test_public_pages_work_without_a_user_and_private_routes_require_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Ingresar para comprar');
        $this->get('/cadena')->assertOk()->assertSee('Cadena íntegra');
        $this->get('/ingresar')->assertOk();
        $this->get('/registro')->assertOk();
        $this->get('/perfil')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
        $this->withSession(['demo_user_id' => User::where('is_admin', true)->value('id')])
            ->post('/demo/usuario', ['user_id' => 1])->assertNotFound();
    }

    public function test_register_login_and_logout_do_not_allow_role_or_balance_injection(): void
    {
        $this->post('/registro', [
            'name' => 'Test Collector', 'handle' => 'collector', 'email' => 'collector@example.test',
            'password' => 'Collector-pass-123', 'password_confirmation' => 'Collector-pass-123',
            'is_admin' => true, 'balance' => 999999,
        ])->assertRedirect(route('profile'));
        $user = User::where('handle', 'collector')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->is_admin);
        $this->assertEquals(25, $user->balance);
        $this->post('/salir')->assertRedirect('/');
        $this->assertGuest();
        $this->post('/ingresar', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/ingresar', ['email' => $user->email, 'password' => 'Collector-pass-123'])->assertRedirect(route('profile'));
        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertForbidden();
    }

    public function test_favorites_are_private_idempotent_and_removable(): void
    {
        $first = User::where('handle', 'will')->firstOrFail();
        $second = User::where('handle', 'joseluis')->firstOrFail();
        $nft = Nft::firstOrFail();
        $this->actingAs($first)->post(route('favorites.nft', $nft), ['saved' => 1])->assertRedirect();
        $this->post(route('favorites.nft', $nft), ['saved' => 1])->assertRedirect();
        $this->post(route('favorites.collection', $nft->collection), ['saved' => 1])->assertRedirect();
        $this->assertDatabaseCount('nft_favorites', 1);
        $this->get('/favoritos')->assertOk()->assertViewHas('nfts', fn ($items) => $items->count() === 1);
        $this->actingAs($second)->get('/favoritos')->assertViewHas('nfts', fn ($items) => $items->isEmpty());
        $this->actingAs($first)->post(route('favorites.nft', $nft), ['saved' => 0])->assertRedirect();
        $this->assertDatabaseCount('nft_favorites', 0);
    }

    public function test_chain_detects_tampered_event_link_and_missing_middle_block(): void
    {
        $chain = app(ChainService::class);
        $this->assertTrue($chain->verify()['valid']);
        $chain->appendBlock('An appended test event');
        $this->assertTrue($chain->verify()['valid']);
        $block = Block::where('position', 2)->firstOrFail();
        $original = $block->event;
        $block->update(['event' => 'Altered event']);
        $this->assertContains(2, $chain->verify()['errors']);
        $block->update(['event' => $original, 'previous_hash' => str_repeat('f', 64)]);
        $this->assertFalse($chain->verify()['valid']);
        $block->delete();
        $this->assertFalse($chain->verify()['valid']);
    }

    public function test_hidden_collection_cannot_be_relisted_and_suspension_blocks_minting(): void
    {
        $user = User::where('handle', 'joseluis')->firstOrFail();
        $nft = Nft::where('owner_id', $user->id)->firstOrFail();
        $nft->collection->update(['status' => 'hidden']);
        $this->actingAs($user)->post(route('nfts.sell', $nft), ['price' => 3])->assertStatus(422);
        $user->update(['suspended_at' => now()]);
        $this->post('/acunar', [])->assertForbidden();
    }

    public function test_purchase_rejects_duplicate_self_and_insufficient_balance_without_losing_funds(): void
    {
        $listing = Listing::where('type', 'fixed')->firstOrFail();
        $buyer = User::where('handle', 'joseluis')->firstOrFail();
        $this->actingAs($listing->seller)->post(route('listings.buy', $listing))->assertStatus(422);
        $buyer->update(['balance' => 0]);
        $this->actingAs($buyer)->post(route('listings.buy', $listing))->assertStatus(422);
        $this->assertSame('active', $listing->fresh()->status);
        $buyer->update(['balance' => 25]);
        $this->post(route('listings.buy', $listing))->assertRedirect();
        $remaining = $buyer->fresh()->balance;
        $this->post(route('listings.buy', $listing))->assertStatus(409);
        $this->assertSame($remaining, $buyer->fresh()->balance);
        $this->assertTrue(app(ChainService::class)->verify()['valid']);
    }

    public function test_mint_uploads_image_and_creates_numbered_copies_with_valid_chain(): void
    {
        $user = User::where('handle', 'joseluis')->firstOrFail();
        $image = UploadedFile::fake()->createWithContent('pixel.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jF9sAAAAASUVORK5CYII='));
        $this->actingAs($user)->post('/acunar', [
            'name' => 'Cloud Art', 'description' => 'Persistent uploaded art.', 'total_supply' => 5, 'quantity' => 3,
            'base_price' => '1.25', 'palette_from' => '#112233', 'palette_to' => '#445566', 'image' => $image,
        ])->assertRedirect(route('profile'));
        $collection = NftCollection::where('name', 'Cloud Art')->firstOrFail();
        $this->assertSame([1, 2, 3], $collection->nfts()->orderBy('token_number')->pluck('token_number')->all());
        $this->get(route('collections.image', $collection))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertTrue(app(ChainService::class)->verify()['valid']);
    }
}
