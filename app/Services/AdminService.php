<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\Nft;
use App\Models\NftCollection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminService
{
    public function __construct(private readonly ChainService $chain)
    {
    }

    /**
     * Hide a collection from the public marketplace, or restore it. Hiding
     * withdraws its direct-sale listings; auctions that already hold an
     * escrowed bid are left to close on their own so nobody loses funds.
     */
    public function toggleCollectionVisibility(NftCollection $collection, User $admin): NftCollection
    {
        return DB::transaction(function () use ($collection, $admin): NftCollection {
            $locked = NftCollection::query()->whereKey($collection->id)->lockForUpdate()->firstOrFail();
            $hiding = ! $locked->isHidden();

            $locked->update([
                'status' => $hiding ? NftCollection::STATUS_HIDDEN : NftCollection::STATUS_VISIBLE,
            ]);

            $withdrawn = $hiding
                ? $this->cancelDirectListings(
                    Listing::query()->whereIn('nft_id', Nft::query()->where('collection_id', $locked->id)->select('id'))
                )
                : 0;

            $action = $hiding ? 'oculta' : 'restaurada';
            $detail = $withdrawn > 0 ? " ({$withdrawn} publicación(es) retirada(s))" : '';
            $this->chain->appendBlock("Moderación: colección {$locked->name} {$action} por @{$admin->handle}{$detail}");

            return $locked;
        });
    }

    /**
     * Suspend an account or bring it back. A suspended user keeps its NFTs
     * and balance but cannot operate, so its open direct listings are
     * withdrawn from the marketplace.
     */
    public function toggleUserSuspension(User $user, User $admin): User
    {
        return DB::transaction(function () use ($user, $admin): User {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            abort_if($locked->id === $admin->id, 422, 'No puedes suspender tu propia cuenta de administrador.');
            abort_if($locked->is_admin, 422, 'No se puede suspender a otro administrador.');

            $suspending = ! $locked->isSuspended();
            $locked->update(['suspended_at' => $suspending ? now() : null]);

            $withdrawn = $suspending
                ? $this->cancelDirectListings(Listing::query()->where('seller_id', $locked->id))
                : 0;

            $action = $suspending ? 'suspendido' : 'reactivado';
            $detail = $withdrawn > 0 ? " ({$withdrawn} publicación(es) retirada(s))" : '';
            $this->chain->appendBlock("Moderación: usuario @{$locked->handle} {$action} por @{$admin->handle}{$detail}");

            return $locked;
        });
    }

    /**
     * Withdraw every active fixed-price listing matched by the query and
     * return its NFT to the owner's inventory. Auctions are skipped on
     * purpose: their bids hold reserved balance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Listing>  $query
     */
    private function cancelDirectListings($query): int
    {
        $listings = $query->where('status', 'active')
            ->where('type', Listing::TYPE_FIXED)
            ->lockForUpdate()
            ->get();

        foreach ($listings as $listing) {
            $listing->update(['status' => 'cancelled']);
            Nft::query()->whereKey($listing->nft_id)->update(['in_sale' => false]);
        }

        return $listings->count();
    }
}
