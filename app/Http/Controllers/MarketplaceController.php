<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Listing;
use App\Models\MarketplaceTransaction;
use App\Models\Nft;
use App\Models\NftCollection;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function index(Request $request): View
    {
        $activeUser = $this->activeUser($request);
        $listings = Listing::query()
            ->where('status', 'active')
            ->with(['seller', 'nft.collection.creator', 'nft.owner'])
            ->latest()
            ->get();

        return view('marketplace', [
            'activeUser' => $activeUser,
            'users' => User::orderBy('name')->get(),
            'listings' => $listings,
            'collectionCount' => NftCollection::count(),
            'nftCount' => Nft::count(),
            'volume' => MarketplaceTransaction::where('type', 'sale')->sum('amount'),
            'recentBlocks' => Block::orderByDesc('position')->limit(3)->get(),
        ]);
    }

    public function mint(Request $request): View
    {
        return view('mint', [
            'activeUser' => $this->activeUser($request),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function storeMint(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:500'],
            'total_supply' => ['required', 'integer', 'min:1', 'max:1000'],
            'base_price' => ['required', 'numeric', 'min:0.10', 'max:999999'],
            'palette_from' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'palette_to' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'list_now' => ['nullable', 'boolean'],
        ]);
        $user = $this->activeUser($request);

        DB::transaction(function () use ($validated, $user): void {
            $collection = NftCollection::create([
                'creator_id' => $user->id,
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(5)),
                'description' => $validated['description'],
                'total_supply' => $validated['total_supply'],
                'minted_count' => 1,
                'base_price' => $validated['base_price'],
                'palette_from' => $validated['palette_from'],
                'palette_to' => $validated['palette_to'],
            ]);

            $nft = Nft::create([
                'collection_id' => $collection->id,
                'owner_id' => $user->id,
                'token_number' => 1,
                'token_hash' => hash('sha256', $collection->id.':1:'.Str::uuid()),
                'in_sale' => (bool) ($validated['list_now'] ?? false),
            ]);

            MarketplaceTransaction::create([
                'buyer_id' => $user->id,
                'nft_id' => $nft->id,
                'amount' => 0,
                'type' => 'mint',
            ]);

            if ($nft->in_sale) {
                Listing::create([
                    'nft_id' => $nft->id,
                    'seller_id' => $user->id,
                    'price' => $validated['base_price'],
                    'status' => 'active',
                ]);
            }

            $this->appendBlock("Acuñación de {$collection->name} #1 por @{$user->handle}");
        });

        return redirect()->route('profile')->with('success', 'Colección creada y primer NFT acuñado.');
    }

    public function buy(Request $request, Listing $listing): RedirectResponse
    {
        $buyerId = $this->activeUser($request)->id;

        DB::transaction(function () use ($listing, $buyerId): void {
            $lockedListing = Listing::query()->whereKey($listing->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedListing->status !== 'active', 409, 'Esta publicación ya no está disponible.');

            $buyer = User::query()->whereKey($buyerId)->lockForUpdate()->firstOrFail();
            $seller = User::query()->whereKey($lockedListing->seller_id)->lockForUpdate()->firstOrFail();
            $nft = Nft::query()->whereKey($lockedListing->nft_id)->lockForUpdate()->firstOrFail();

            abort_if($buyer->id === $seller->id, 422, 'No puedes comprar tu propio NFT.');
            abort_if((float) $buyer->balance < (float) $lockedListing->price, 422, 'Saldo insuficiente para completar la compra.');

            $buyer->balance = (float) $buyer->balance - (float) $lockedListing->price;
            $seller->balance = (float) $seller->balance + (float) $lockedListing->price;
            $buyer->save();
            $seller->save();

            $lockedListing->update(['status' => 'sold']);
            $nft->update(['owner_id' => $buyer->id, 'in_sale' => false]);
            MarketplaceTransaction::create([
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'nft_id' => $nft->id,
                'amount' => $lockedListing->price,
                'type' => 'sale',
            ]);

            $nft->load('collection');
            $this->appendBlock("Venta de {$nft->collection->name} #{$nft->token_number}: @{$seller->handle} → @{$buyer->handle}");
        });

        return back()->with('success', 'Compra completada. El NFT ya aparece en tu inventario.');
    }

    public function profile(Request $request): View
    {
        $activeUser = $this->activeUser($request);
        $inventory = Nft::query()
            ->where('owner_id', $activeUser->id)
            ->with(['collection.creator', 'activeListing'])
            ->latest()
            ->get();
        $movements = MarketplaceTransaction::query()
            ->where(fn ($query) => $query->where('buyer_id', $activeUser->id)->orWhere('seller_id', $activeUser->id))
            ->with(['buyer', 'seller', 'nft.collection'])
            ->latest()
            ->limit(10)
            ->get();

        return view('profile', [
            'activeUser' => $activeUser,
            'users' => User::orderBy('name')->get(),
            'inventory' => $inventory,
            'movements' => $movements,
        ]);
    }

    public function sell(Request $request, Nft $nft): RedirectResponse
    {
        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0.10', 'max:999999'],
        ]);
        $userId = $this->activeUser($request)->id;

        DB::transaction(function () use ($nft, $validated, $userId): void {
            $lockedNft = Nft::query()->whereKey($nft->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedNft->owner_id === $userId, 403, 'Solo el propietario puede publicar este NFT.');
            abort_if(Listing::where('nft_id', $lockedNft->id)->where('status', 'active')->exists(), 409, 'El NFT ya está publicado.');

            Listing::create([
                'nft_id' => $lockedNft->id,
                'seller_id' => $userId,
                'price' => $validated['price'],
                'status' => 'active',
            ]);
            $lockedNft->update(['in_sale' => true]);
            MarketplaceTransaction::create([
                'seller_id' => $userId,
                'nft_id' => $lockedNft->id,
                'amount' => $validated['price'],
                'type' => 'listing',
            ]);

            $lockedNft->load(['collection', 'owner']);
            $this->appendBlock("Publicación de {$lockedNft->collection->name} #{$lockedNft->token_number} por @{$lockedNft->owner->handle}");
        });

        return back()->with('success', 'NFT publicado en el mercado.');
    }

    public function switchUser(Request $request): RedirectResponse
    {
        $validated = $request->validate(['user_id' => ['required', 'exists:users,id']]);
        $request->session()->put('demo_user_id', (int) $validated['user_id']);

        return back()->with('success', 'Usuario de demostración actualizado.');
    }

    public function roadmap(Request $request): View
    {
        return view('roadmap', [
            'activeUser' => $this->activeUser($request),
            'users' => User::orderBy('name')->get(),
            'tasks' => config('roadmap.tasks'),
            'dueDate' => config('roadmap.due_date'),
        ]);
    }

    private function activeUser(Request $request): User
    {
        $user = User::find($request->session()->get('demo_user_id')) ?? User::orderBy('id')->firstOrFail();
        $request->session()->put('demo_user_id', $user->id);

        return $user;
    }

    private function appendBlock(string $event): Block
    {
        $lastBlock = Block::query()->orderByDesc('position')->lockForUpdate()->first();
        $position = ($lastBlock?->position ?? 0) + 1;
        $previousHash = $lastBlock?->current_hash ?? str_repeat('0', 64);
        $occurredAt = now();
        $currentHash = hash('sha256', implode('|', [$position, $previousHash, $event, $occurredAt->toIso8601String()]));

        return Block::create([
            'position' => $position,
            'previous_hash' => $previousHash,
            'current_hash' => $currentHash,
            'event' => $event,
            'occurred_at' => $occurredAt,
        ]);
    }
}
