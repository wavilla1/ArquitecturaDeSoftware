<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceTransaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = ['buyer_id', 'seller_id', 'nft_id', 'amount', 'type'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function nft(): BelongsTo
    {
        return $this->belongsTo(Nft::class);
    }
}
