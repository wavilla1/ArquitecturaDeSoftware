<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Listing extends Model
{
    public const TYPE_FIXED = 'fixed';

    public const TYPE_AUCTION = 'auction';

    public const MIN_BID_INCREMENT = 0.05;

    protected $fillable = ['nft_id', 'seller_id', 'price', 'status', 'type', 'closes_at', 'winning_bid_id'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'closes_at' => 'datetime',
        ];
    }

    public function nft(): BelongsTo
    {
        return $this->belongsTo(Nft::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class)->latest();
    }

    /**
     * The current best (escrowed) bid for this auction, when one exists.
     */
    public function leadingBid(): HasOne
    {
        return $this->hasOne(Bid::class)->where('status', Bid::STATUS_ACTIVE)->latestOfMany('amount');
    }

    public function winningBid(): BelongsTo
    {
        return $this->belongsTo(Bid::class, 'winning_bid_id');
    }

    public function isAuction(): bool
    {
        return $this->type === self::TYPE_AUCTION;
    }

    public function isOpenForBids(): bool
    {
        return $this->isAuction()
            && $this->status === 'active'
            && $this->closes_at !== null
            && now()->lessThan($this->closes_at);
    }

    public function hasClosed(): bool
    {
        return $this->isAuction() && $this->closes_at !== null && now()->greaterThanOrEqualTo($this->closes_at);
    }

    /**
     * Minimum amount a new bid must reach: the starting price if there are no
     * bids yet, or the current leading bid plus the minimum increment.
     */
    public function minimumNextBid(): float
    {
        $leading = $this->relationLoaded('leadingBid') ? $this->leadingBid : $this->leadingBid()->first();

        return $leading
            ? round((float) $leading->amount + self::MIN_BID_INCREMENT, 2)
            : (float) $this->price;
    }
}
