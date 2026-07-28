<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddNoIndexHeader
{
    private const TECHNICAL_PATHS = [
        'login',
        'register',
        'password/*',
        'cart',
        'cart/*',
        'checkout',
        'profile',
        'profile/*',
        'favorites',
        'favorites/*',
        'sheet-names',
        'sheet-names-test',
        'popup/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is(self::TECHNICAL_PATHS)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        return $response;
    }
}
