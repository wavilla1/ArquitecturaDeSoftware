<?php

namespace App\Http\Middleware;

use App\Services\DemoSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function __construct(private readonly DemoSessionService $session)
    {
    }

    /**
     * Block every marketplace write action for a suspended account. Browsing
     * and switching the demo user stay available so the restriction can be
     * demonstrated and undone.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(
            $this->session->activeUser($request)->isSuspended(),
            403,
            'Tu cuenta está suspendida por un administrador: no puedes operar en el mercado.'
        );

        return $next($request);
    }
}
