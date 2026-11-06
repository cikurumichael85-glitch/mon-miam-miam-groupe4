<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->status === AccountStatus::Active, 403, 'Compte désactivé.');

        return $next($request);
    }
}
