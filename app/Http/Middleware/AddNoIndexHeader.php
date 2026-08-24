<?php

namespace App\Http\Middleware;

use App\Support\IndexingPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddNoIndexHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($robots = IndexingPolicy::robots($request)) {
            $response->headers->set('X-Robots-Tag', $robots);
        }

        return $response;
    }
}
