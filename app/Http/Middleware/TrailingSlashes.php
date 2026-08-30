<?php

namespace App\Http\Middleware;

use App\Support\CanonicalUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

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
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        // These controllers know the physical catalog hierarchy and must build the
        // final URL themselves, otherwise a foreign slashless URL redirects twice.
        if (in_array($request->route()?->getName(), ['subcategory.show', 'product.show'], true)) {
            return $next($request);
        }

        // Laravel can normalize a routed URI before middleware runs. REQUEST_URI
        // preserves whether the browser already sent the canonical trailing slash.
        $requestUri = (string) $request->server->get('REQUEST_URI', '');
        $requestUriPath = parse_url($requestUri, PHP_URL_PATH);
        $path = is_string($requestUriPath) && $requestUriPath !== ''
            ? $requestUriPath
            : $request->getPathInfo();

        $canonicalPath = CanonicalUrl::path($path);
        if ($path !== $canonicalPath) {
            $queryString = parse_url($requestUri, PHP_URL_QUERY);
            if (! is_string($queryString)) {
                $serverQueryString = $request->server->get('QUERY_STRING');
                $queryString = is_string($serverQueryString) ? $serverQueryString : '';
            }

            $targetUrl = $canonicalPath;
            if ($queryString !== '') {
                $targetUrl .= '?'.$queryString;
            }

            return new RedirectResponse($targetUrl, Response::HTTP_MOVED_PERMANENTLY);
        }

        return $next($request);
    }
}
