<?php

namespace App\Http\Middleware;

use App\Services\DemoSessionService;
use Closure;
use Illuminate\Http\Request;

class EnsureMarketplaceUser
{
    public function handle(Request $request, Closure $next)
    {
        if (! app(DemoSessionService::class)->activeUser($request)) {
            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
