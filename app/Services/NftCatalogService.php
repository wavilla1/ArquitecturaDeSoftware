<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\Nft;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class NftCatalogService
{
    public function paginate(int $page): LengthAwarePaginator
    {
        return $this->available()->orderBy('nfts.id')->paginate(20, ['nfts.*'], 'page', $page);
    }

    public function findAvailable(int $id): Nft
    {
        return $this->available()->findOrFail($id);
    }

    private function available(): Builder
    {
        return Nft::query()
            ->where('in_sale', true)
            ->whereHas('collection', fn (Builder $query) => $query->visible())
            ->whereHas('owner', fn (Builder $query) => $query->whereNull('suspended_at'))
            ->whereHas('activeListing', fn (Builder $query) => $query
                ->where('type', Listing::TYPE_FIXED)
                ->whereColumn('listings.seller_id', 'nfts.owner_id'))
            ->with([
                'owner:id,handle,accent',
                'activeListing',
                'collection' => fn ($query) => $query
                    ->select('id', 'name', 'description', 'total_supply', 'palette_from', 'palette_to')
                    ->selectRaw('image_data IS NOT NULL AS has_image'),
            ]);
    }
}
