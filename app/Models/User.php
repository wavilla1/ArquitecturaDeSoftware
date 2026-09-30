<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'handle',
        'email',
        'password',
        'balance',
        'accent',
        'is_admin',
        'suspended_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
            'is_admin' => 'boolean',
            'suspended_at' => 'datetime',
        ];
    }

    public function collections()
    {
        return $this->hasMany(NftCollection::class, 'creator_id');
    }

    public function nfts()
    {
        return $this->hasMany(Nft::class, 'owner_id');
    }

    public function bids()
    {
        return $this->hasMany(Bid::class, 'bidder_id');
    }

    public function listings()
    {
        return $this->hasMany(Listing::class, 'seller_id');
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function favoriteNfts()
    {
        return $this->belongsToMany(Nft::class, 'nft_favorites')->withTimestamps();
    }

    public function favoriteCollections()
    {
        return $this->belongsToMany(NftCollection::class, 'collection_favorites', 'user_id', 'collection_id')->withTimestamps();
    }
}
