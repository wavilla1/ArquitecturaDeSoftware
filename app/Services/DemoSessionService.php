<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class DemoSessionService
{
    /**
     * Resolve the user the demo session is acting as. The MVP has no real
     * authentication, so the header selector is the only "login": both the
     * controllers and the admin middleware read the acting user from here.
     */
    public function activeUser(Request $request): User
    {
        $user = User::find($request->session()->get('demo_user_id')) ?? User::orderBy('id')->firstOrFail();
        $request->session()->put('demo_user_id', $user->id);

        return $user;
    }
}
