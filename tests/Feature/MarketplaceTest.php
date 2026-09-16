<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Listing;
use App\Models\NftCollection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_marketplace_renders_seeded_listings(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Arte único')
            ->assertSee('Nebula Echoes');
    }

    public function test_demo_user_can_be_switched(): void
    {
        $juan = User::where('handle', 'juanjose')->firstOrFail();

        $this->post(route('demo.user'), ['user_id' => $juan->id])
            ->assertRedirect()
            ->assertSessionHas('demo_user_id', $juan->id);
    }

    public function test_purchase_transfers_balance_and_ownership(): void
    {
        $buyer = User::where('handle', 'joseluis')->firstOrFail();
        $listing = Listing::where('status', 'active')->where('seller_id', '!=', $buyer->id)->firstOrFail();
        $seller = $listing->seller;
        $buyerBalance = (float) $buyer->balance;
        $sellerBalance = (float) $seller->balance;
        $price = (float) $listing->price;

        $this->withSession(['demo_user_id' => $buyer->id])
            ->post(route('listings.buy', $listing))
            ->assertRedirect();

        $this->assertSame('sold', $listing->fresh()->status);
        $this->assertSame($buyer->id, $listing->nft->fresh()->owner_id);
        $this->assertEquals($buyerBalance - $price, (float) $buyer->fresh()->balance);
        $this->assertEquals($sellerBalance + $price, (float) $seller->fresh()->balance);
    }

    public function test_mint_creates_collection_and_appends_block(): void
    {
        $user = User::where('handle', 'joseluis')->firstOrFail();
        $blocksBefore = Block::count();

        $this->withSession(['demo_user_id' => $user->id])
            ->post(route('mint.store'), [
                'name' => 'Signal Bloom',
                'description' => 'Una colección creada desde la prueba del MVP.',
                'total_supply' => 12,
                'base_price' => 2.75,
                'palette_from' => '#7c3aed',
                'palette_to' => '#06b6d4',
                'list_now' => 1,
            ])
            ->assertRedirect(route('profile'));

        $this->assertDatabaseHas('collections', ['name' => 'Signal Bloom', 'creator_id' => $user->id]);
        $this->assertDatabaseCount('blocks', $blocksBefore + 1);
        $this->assertTrue(NftCollection::where('name', 'Signal Bloom')->firstOrFail()->nfts()->firstOrFail()->in_sale);
    }
}
