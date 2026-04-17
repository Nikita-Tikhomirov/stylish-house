<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class TrailingSlashes
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Никогда не редиректим не-GET запросы (POST/PUT/DELETE), иначе ломаются формы.
        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = $request->getPathInfo();

        // Админку и служебные маршруты не переписываем.
        if (
            Str::startsWith($path, '/admin')
            || Str::startsWith($path, '/api')
            || Str::startsWith($path, '/_ignition')
            || Str::startsWith($path, '/login')
            || Str::startsWith($path, '/logout')
            || Str::startsWith($path, '/register')
            || Str::startsWith($path, '/password')
            || Str::startsWith($path, '/sanctum')
        ) {
            return $next($request);
        }

        if ($path !== '/' && !Str::endsWith($path, '/')) {
            $queryString = $request->getQueryString();
            $targetUrl = $request->getSchemeAndHttpHost() . $path . '/';
            if ($queryString) {
                $targetUrl .= '?' . $queryString;
            }

            return Redirect::to($targetUrl, 301);
        }

        return $next($request);
    }
}
