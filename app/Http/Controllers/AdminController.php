<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\NftCollection;
use App\Models\User;
use App\Services\AdminReportService;
use App\Services\AdminService;
use App\Services\AuctionService;
use App\Services\DemoSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(
        private readonly AdminService $admin,
        private readonly AdminReportService $reports,
        private readonly AuctionService $auctions,
        private readonly DemoSessionService $session,
    ) {
    }

    public function index(Request $request): View
    {
        $this->auctions->closeExpired();

        return view('admin.dashboard', [
            'activeUser' => $this->session->activeUser($request),
            'users' => User::orderBy('name')->get(),
            'metrics' => $this->reports->metrics(),
            'balance' => $this->reports->circulatingBalance(),
            'topCollections' => $this->reports->topCollections(),
            'collections' => $this->reports->collectionsForModeration(),
            'moderatedUsers' => $this->reports->usersForModeration(),
            'recentBlocks' => Block::orderByDesc('position')->limit(6)->get(),
        ]);
    }

    public function toggleCollection(Request $request, NftCollection $collection): RedirectResponse
    {
        $updated = $this->admin->toggleCollectionVisibility($collection, $this->session->activeUser($request));

        return back()->with('success', $updated->isHidden()
            ? "Colección «{$updated->name}» oculta del mercado público."
            : "Colección «{$updated->name}» restaurada en el mercado.");
    }

    public function toggleUser(Request $request, User $user): RedirectResponse
    {
        $updated = $this->admin->toggleUserSuspension($user, $this->session->activeUser($request));

        return back()->with('success', $updated->isSuspended()
            ? "Usuario @{$updated->handle} suspendido."
            : "Usuario @{$updated->handle} reactivado.");
    }
}
