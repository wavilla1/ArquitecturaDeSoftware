<?php

namespace App\Services;

use App\Models\Bid;
use App\Models\Listing;
use App\Models\MarketplaceTransaction;
use App\Models\Nft;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuctionService
{
    public function __construct(private readonly ChainService $chain)
    {
    }

    /**
     * Place a bid on an auction listing. Reserves the bidder's balance for
     * the duration of the auction and automatically refunds whoever was
     * previously leading, so the platform never holds more than one active
     * (escrowed) bid per listing at a time.
     */
    public function placeBid(Listing $listing, int $bidderId, float $amount): Bid
    {
        return DB::transaction(function () use ($listing, $bidderId, $amount): Bid {
            $lockedListing = Listing::query()->whereKey($listing->id)->lockForUpdate()->firstOrFail();

            abort_unless($lockedListing->isAuction(), 422, 'Esta publicación no acepta pujas.');
            abort_if($lockedListing->status !== 'active', 409, 'Esta subasta ya no está disponible.');
            abort_if($lockedListing->hasClosed(), 409, 'La subasta ya cerró; espera la adjudicación.');
            abort_if($lockedListing->seller_id === $bidderId, 422, 'No puedes ofertar por tu propio NFT.');

            $leadingBid = Bid::query()
                ->where('listing_id', $lockedListing->id)
                ->where('status', Bid::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            $minimum = $leadingBid
                ? round((float) $leadingBid->amount + Listing::MIN_BID_INCREMENT, 2)
                : (float) $lockedListing->price;

            abort_if($amount < $minimum, 422, "La puja debe ser de al menos {$minimum} MONO.");
            abort_if($leadingBid && (int) $leadingBid->bidder_id === $bidderId, 422, 'Ya tienes la puja más alta de esta subasta.');

            $bidder = User::query()->whereKey($bidderId)->lockForUpdate()->firstOrFail();
            abort_if((float) $bidder->balance < $amount, 422, 'Saldo insuficiente para esta puja.');

            $bidder->balance = (float) $bidder->balance - $amount;
            $bidder->save();

            if ($leadingBid) {
                $previousBidder = User::query()->whereKey($leadingBid->bidder_id)->lockForUpdate()->firstOrFail();
                $previousBidder->balance = (float) $previousBidder->balance + (float) $leadingBid->amount;
                $previousBidder->save();
                $leadingBid->update(['status' => Bid::STATUS_OUTBID]);
            }

            $bid = Bid::create([
                'listing_id' => $lockedListing->id,
                'bidder_id' => $bidderId,
                'amount' => $amount,
                'status' => Bid::STATUS_ACTIVE,
            ]);

            $nft = Nft::query()->whereKey($lockedListing->nft_id)->with('collection')->firstOrFail();
            $this->chain->appendBlock("Puja de {$amount} MONO por {$nft->collection->name} #{$nft->token_number} de @{$bidder->handle}");

            return $bid;
        });
    }

    /**
     * Close every auction listing whose deadline has passed: the leading bid
     * (if any) wins and the NFT changes hands, otherwise the listing simply
     * expires and returns to the seller's inventory.
     */
    public function closeExpired(): int
    {
        $expiredIds = Listing::query()
            ->where('type', Listing::TYPE_AUCTION)
            ->where('status', 'active')
            ->whereNotNull('closes_at')
            ->where('closes_at', '<=', now())
            ->pluck('id');

        $closed = 0;
        foreach ($expiredIds as $listingId) {
            if ($this->closeListing($listingId)) {
                $closed++;
            }
        }

        return $closed;
    }

    private function closeListing(int $listingId): bool
    {
        return DB::transaction(function () use ($listingId): bool {
            $listing = Listing::query()->whereKey($listingId)->lockForUpdate()->first();
            if (! $listing || $listing->status !== 'active' || ! $listing->hasClosed()) {
                return false;
            }

            $nft = Nft::query()->whereKey($listing->nft_id)->with('collection')->lockForUpdate()->firstOrFail();
            $winningBid = Bid::query()
                ->where('listing_id', $listing->id)
                ->where('status', Bid::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if (! $winningBid) {
                $listing->update(['status' => 'expired']);
                $nft->update(['in_sale' => false]);
                $this->chain->appendBlock("Subasta cerrada sin pujas para {$nft->collection->name} #{$nft->token_number}");

                return true;
            }

            $seller = User::query()->whereKey($listing->seller_id)->lockForUpdate()->firstOrFail();
            $seller->balance = (float) $seller->balance + (float) $winningBid->amount;
            $seller->save();

            $winningBid->update(['status' => Bid::STATUS_WON]);
            $listing->update(['status' => 'sold', 'winning_bid_id' => $winningBid->id]);
            $nft->update(['owner_id' => $winningBid->bidder_id, 'in_sale' => false]);

            MarketplaceTransaction::create([
                'buyer_id' => $winningBid->bidder_id,
                'seller_id' => $seller->id,
                'nft_id' => $nft->id,
                'amount' => $winningBid->amount,
                'type' => 'sale',
            ]);

            $winner = User::query()->whereKey($winningBid->bidder_id)->firstOrFail();
            $this->chain->appendBlock("Subasta adjudicada de {$nft->collection->name} #{$nft->token_number}: @{$seller->handle} → @{$winner->handle} por {$winningBid->amount} MONO");

            return true;
        });
    }
}
