<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nft;
use App\Services\NftCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NftCatalogController extends Controller
{
    public function __invoke(Request $request, NftCatalogService $catalog): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'required', 'integer', 'min:1', 'max:1000000'],
        ], ['page.*' => __('catalog.invalid_page')]);
        $page = $catalog->paginate((int) ($validated['page'] ?? 1));

        // An explicit public projection avoids exposing accounts or image blobs.
        $data = $page->getCollection()->map(fn (Nft $nft) => [
            'id' => $nft->id,
            'name' => $nft->collection->name.' #'.$nft->token_number,
            'description' => $nft->collection->description,
            'collection' => $nft->collection->name,
            'price' => $nft->activeListing->price,
            'currency' => 'MONO',
            'seller' => $nft->owner->handle,
            'image_url' => $nft->collection->has_image ? route('collections.image', $nft->collection) : null,
            'detail_url' => route('nfts.show', $nft),
        ])->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ])->header('Cache-Control', 'no-store');
    }
}
