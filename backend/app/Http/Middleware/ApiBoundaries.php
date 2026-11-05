<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiBoundaries
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->validate(['page' => ['sometimes', 'integer', 'between:1,1000000']]);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
