<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Nft;
use App\Models\NftCollection;
use App\Models\User;
use App\Services\ChainService;
use App\Services\DemoSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionController extends Controller
{
    public function chain(Request $request, ChainService $chain, DemoSessionService $session)
    {
        return view('chain', [
            'activeUser' => $session->activeUser($request), 'users' => User::orderBy('name')->get(),
            'verification' => $chain->verify(), 'blocks' => Block::orderByDesc('position')->paginate(20),
        ]);
    }

    public function favorites(Request $request, DemoSessionService $session)
    {
        $user = $session->activeUser($request);

        return view('favorites', [
            'activeUser' => $user, 'users' => User::orderBy('name')->get(),
            'nfts' => $user->favoriteNfts()->whereHas('collection', fn ($q) => $q->visible())->with('collection')->get(),
            'collections' => $user->favoriteCollections()->visible()->get(),
        ]);
    }

    public function favoriteNft(Request $request, Nft $nft, DemoSessionService $session)
    {
        abort_if($nft->collection->isHidden(), 404);
        $this->saveFavorite($request, $session->activeUser($request), 'favoriteNfts', $nft->id);

        return back()->with('success', 'Favoritos actualizados.');
    }

    public function favoriteCollection(Request $request, NftCollection $collection, DemoSessionService $session)
    {
        abort_if($collection->isHidden(), 404);
        $this->saveFavorite($request, $session->activeUser($request), 'favoriteCollections', $collection->id);

        return back()->with('success', 'Favoritos actualizados.');
    }

    private function saveFavorite(Request $request, User $user, string $relation, int $id): void
    {
        $data = $request->validate(['saved' => ['required', 'boolean']]);
        DB::transaction(function () use ($data, $user, $relation, $id) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($data['saved']) {
                $user->$relation()->syncWithoutDetaching([$id]);
            } else {
                $user->$relation()->detach($id);
            }
        });
    }

    public function image(Request $request, NftCollection $collection, DemoSessionService $session)
    {
        $user = $session->activeUser($request);
        abort_if($collection->isHidden() && ! $user?->is_admin && $user?->id !== $collection->creator_id, 404);
        abort_unless($collection->image_data, 404);

        return response(base64_decode($collection->image_data), 200, [
            'Content-Type' => $collection->image_mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=60',
        ]);
    }
}
