<?php

namespace App\Services;

use App\Models\Bid;
use App\Models\Block;
use App\Models\Listing;
use App\Models\MarketplaceTransaction;
use App\Models\Nft;
use App\Models\NftCollection;
use App\Models\User;
use Illuminate\Support\Collection;

class AdminReportService
{
    /**
     * Headline numbers of the marketplace, all derived from the tables the
     * MVP already writes.
     *
     * @return array<string, int|float>
     */
    public function metrics(): array
    {
        $sales = MarketplaceTransaction::query()->where('type', 'sale');
        $salesCount = (clone $sales)->count();
        $volume = (float) (clone $sales)->sum('amount');

        return [
            'users' => User::query()->count(),
            'suspended' => User::query()->whereNotNull('suspended_at')->count(),
            'collections' => NftCollection::query()->count(),
            'hidden_collections' => NftCollection::query()->where('status', NftCollection::STATUS_HIDDEN)->count(),
            'nfts' => Nft::query()->count(),
            'active_listings' => Listing::query()->where('status', 'active')->count(),
            'open_auctions' => Listing::query()->where('status', 'active')->where('type', Listing::TYPE_AUCTION)->count(),
            'sales' => $salesCount,
            'volume' => round($volume, 2),
            'average_ticket' => $salesCount > 0 ? round($volume / $salesCount, 2) : 0.0,
            'blocks' => Block::query()->count(),
        ];
    }

    /**
     * Total MONO accounted for by the platform: free balance plus the amount
     * escrowed by the leading bids. Useful to check that auctions never
     * create or destroy funds.
     *
     * @return array<string, float>
     */
    public function circulatingBalance(): array
    {
        $free = round((float) User::query()->sum('balance'), 2);
        $escrowed = round((float) Bid::query()->where('status', Bid::STATUS_ACTIVE)->sum('amount'), 2);

        return [
            'free' => $free,
            'escrowed' => $escrowed,
            'total' => round($free + $escrowed, 2),
        ];
    }

    /**
     * Collections ranked by the volume their NFTs have traded.
     *
     * @return Collection<int, NftCollection>
     */
    public function topCollections(int $limit = 3): Collection
    {
        return NftCollection::query()
            ->with('creator')
            ->withCount('nfts')
            ->get()
            ->map(function (NftCollection $collection): NftCollection {
                $collection->setAttribute('traded_volume', round((float) MarketplaceTransaction::query()
                    ->where('type', 'sale')
                    ->whereHas('nft', fn ($query) => $query->where('collection_id', $collection->id))
                    ->sum('amount'), 2));

                return $collection;
            })
            ->sortByDesc('traded_volume')
            ->take($limit)
            ->values();
    }

    /**
     * Every collection with the data the moderation table needs.
     *
     * @return Collection<int, NftCollection>
     */
    public function collectionsForModeration(): Collection
    {
        return NftCollection::query()
            ->with('creator')
            ->withCount([
                'nfts',
                'nfts as listed_nfts_count' => fn ($query) => $query->where('in_sale', true),
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Every user with the counters the moderation table needs.
     *
     * @return Collection<int, User>
     */
    public function usersForModeration(): Collection
    {
        return User::query()
            ->withCount([
                'nfts',
                'collections',
                'listings as active_listings_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->orderBy('name')
            ->get();
    }
}
