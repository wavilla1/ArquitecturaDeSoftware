<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NftCollection extends Model
{
    protected $table = 'collections';

    protected $fillable = [
        'creator_id', 'name', 'slug', 'description', 'total_supply',
        'minted_count', 'base_price', 'palette_from', 'palette_to',
    ];

    protected function casts(): array
    {
        return ['base_price' => 'decimal:2'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function nfts(): HasMany
    {
        return $this->hasMany(Nft::class, 'collection_id');
    }

    public function suggestedPrice(): float
    {
        $sales = MarketplaceTransaction::query()
            ->where('type', 'sale')
            ->whereHas('nft', fn ($query) => $query->where('collection_id', $this->id))
            ->count();
        $scarcity = 1 + (($this->minted_count / max(1, $this->total_supply)) * 0.35);
        $demand = 1 + ($sales * 0.08);

        return round(((float) $this->base_price) * $scarcity * $demand, 2);
    }
}
