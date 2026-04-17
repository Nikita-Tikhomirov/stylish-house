<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Config;

class TrailingSlashes
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!preg_match('/.+\/$/', $request->getRequestUri())) {
            $base_url = 'https://stylish-house.net';
            return Redirect::to($base_url . $request->getRequestUri() . '/');
        }
        return $next($request);
    }
}
