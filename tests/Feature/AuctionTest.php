<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Listing;
use App\Models\Nft;
use App\Models\NftCollection;
use App\Models\User;
use App\Services\AuctionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuctionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function openAuction(array $overrides = []): Listing
    {
        $seller = User::where('handle', 'juanjose')->firstOrFail();
        $collection = NftCollection::where('creator_id', $seller->id)->firstOrFail();
        $nft = Nft::create([
            'collection_id' => $collection->id,
            'owner_id' => $seller->id,
            'token_number' => 99,
            'token_hash' => hash('sha256', 'auction-test-'.uniqid()),
            'in_sale' => true,
        ]);

        return Listing::create(array_merge([
            'nft_id' => $nft->id,
            'seller_id' => $seller->id,
            'price' => 5.00,
            'status' => 'active',
            'type' => Listing::TYPE_AUCTION,
            'closes_at' => now()->addHours(6),
        ], $overrides));
    }

    public function test_bid_reserves_balance_and_becomes_leading(): void
    {
        $listing = $this->openAuction();
        $bidder = User::where('handle', 'will')->firstOrFail();
        $balanceBefore = (float) $bidder->balance;

        $this->withSession(['demo_user_id' => $bidder->id])
            ->post(route('listings.bid', $listing), ['amount' => 5.00])
            ->assertRedirect();

        $this->assertEquals($balanceBefore - 5.00, (float) $bidder->fresh()->balance);
        $this->assertDatabaseHas('bids', [
            'listing_id' => $listing->id,
            'bidder_id' => $bidder->id,
            'status' => Bid::STATUS_ACTIVE,
        ]);
    }

    public function test_higher_bid_refunds_the_previous_leader(): void
    {
        $listing = $this->openAuction();
        $first = User::where('handle', 'will')->firstOrFail();
        $second = User::where('handle', 'joseluis')->firstOrFail();
        $firstBalanceBefore = (float) $first->balance;

        $this->withSession(['demo_user_id' => $first->id])
            ->post(route('listings.bid', $listing), ['amount' => 5.00])
            ->assertRedirect();

        $this->withSession(['demo_user_id' => $second->id])
            ->post(route('listings.bid', $listing), ['amount' => 5.10])
            ->assertRedirect();

        $this->assertEquals($firstBalanceBefore, (float) $first->fresh()->balance);
        $this->assertDatabaseHas('bids', ['bidder_id' => $first->id, 'status' => Bid::STATUS_OUTBID]);
        $this->assertDatabaseHas('bids', ['bidder_id' => $second->id, 'status' => Bid::STATUS_ACTIVE]);
    }

    public function test_bid_below_the_minimum_is_rejected(): void
    {
        $listing = $this->openAuction();
        $bidder = User::where('handle', 'will')->firstOrFail();

        $this->withSession(['demo_user_id' => $bidder->id])
            ->post(route('listings.bid', $listing), ['amount' => 4.99])
            ->assertStatus(422);

        $this->assertDatabaseMissing('bids', ['listing_id' => $listing->id]);
    }

    public function test_seller_cannot_bid_on_their_own_auction(): void
    {
        $listing = $this->openAuction();

        $this->withSession(['demo_user_id' => $listing->seller_id])
            ->post(route('listings.bid', $listing), ['amount' => 5.00])
            ->assertStatus(422);
    }

    public function test_closing_awards_the_nft_and_funds_to_the_leading_bidder(): void
    {
        $listing = $this->openAuction(['closes_at' => now()->addMinute()]);
        $seller = $listing->seller;
        $bidder = User::where('handle', 'will')->firstOrFail();
        $sellerBalanceBefore = (float) $seller->balance;

        $this->withSession(['demo_user_id' => $bidder->id])
            ->post(route('listings.bid', $listing), ['amount' => 5.00])
            ->assertRedirect();

        $listing->update(['closes_at' => now()->subMinute()]);
        app(AuctionService::class)->closeExpired();

        $listing->refresh();
        $this->assertSame('sold', $listing->status);
        $this->assertSame($bidder->id, $listing->nft->fresh()->owner_id);
        $this->assertFalse((bool) $listing->nft->fresh()->in_sale);
        $this->assertEquals($sellerBalanceBefore + 5.00, (float) $seller->fresh()->balance);
        $this->assertDatabaseHas('bids', ['listing_id' => $listing->id, 'bidder_id' => $bidder->id, 'status' => Bid::STATUS_WON]);
        $this->assertDatabaseHas('transactions', ['nft_id' => $listing->nft_id, 'type' => 'sale', 'amount' => 5.00]);
    }

    public function test_closing_without_bids_expires_the_listing(): void
    {
        $listing = $this->openAuction(['closes_at' => now()->subMinute()]);

        app(AuctionService::class)->closeExpired();

        $listing->refresh();
        $this->assertSame('expired', $listing->status);
        $this->assertFalse((bool) $listing->nft->fresh()->in_sale);
        $this->assertSame($listing->seller_id, $listing->nft->fresh()->owner_id);
    }
}
