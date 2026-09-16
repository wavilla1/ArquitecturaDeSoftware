<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Nft extends Model
{
    protected $fillable = ['collection_id', 'owner_id', 'token_number', 'token_hash', 'in_sale'];

    protected function casts(): array
    {
        return ['in_sale' => 'boolean'];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(NftCollection::class, 'collection_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function activeListing(): HasOne
    {
        return $this->hasOne(Listing::class)->where('status', 'active')->latestOfMany();
    }
}
