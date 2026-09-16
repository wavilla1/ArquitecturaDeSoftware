<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Listing extends Model
{
    protected $fillable = ['nft_id', 'seller_id', 'price', 'status'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function nft(): BelongsTo
    {
        return $this->belongsTo(Nft::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
