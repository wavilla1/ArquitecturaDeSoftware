<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DemoSessionService;
use App\Services\NftCatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NftController extends Controller
{
    public function show(Request $request, int $nft, NftCatalogService $catalog, DemoSessionService $session): View
    {
        return view('nfts.show', [
            'nft' => $catalog->findAvailable($nft),
            'activeUser' => $session->activeUser($request),
            'users' => config('monoverse.demo') && ! app()->environment('production') ? User::orderBy('name')->get() : collect(),
        ]);
    }
}
