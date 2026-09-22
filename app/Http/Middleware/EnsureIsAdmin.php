<?php

namespace App\Http\Middleware;

use App\Services\DemoSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    public function __construct(private readonly DemoSessionService $session)
    {
    }

    /**
     * Guard the admin panel: only the demo user flagged as administrator
     * may reach its dashboard and moderation actions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->session->activeUser($request)->is_admin, 403, 'Solo un administrador puede entrar al panel.');

        return $next($request);
    }
}
