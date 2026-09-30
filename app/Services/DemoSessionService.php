<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class DemoSessionService
{
    /**
     * Prefer the authenticated account. The optional local demo selector
     * is never accepted in production; guests there resolve to null.
     */
    public function activeUser(Request $request): ?User
    {
        if ($request->user()) {
            return $request->user();
        }
        if (! config('monoverse.demo') || app()->environment('production')) {
            return null;
        }
        $user = User::find($request->session()->get('demo_user_id')) ?? User::orderBy('id')->firstOrFail();
        $request->session()->put('demo_user_id', $user->id);

        return $user;
    }
}
