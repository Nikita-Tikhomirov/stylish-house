<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class TrailingSlashes
{
    private const EXCLUDED_PATHS = [
        '/admin',
        '/api',
        '/_ignition',
        '/login',
        '/logout',
        '/register',
        '/password',
        '/email',
        '/sanctum',
        '/cart',
        '/checkout',
        '/profile',
        '/favorites',
        '/sheet-names',
        '/sheet-names-test',
        '/popup',
        '/products',
        '/filter-cat-products',
        '/filter-subcat-products',
        '/category',
        '/categories',
        '/pages',
        '/allpages',
        '/header-info',
        '/get-model-image',
        '/colors',
        '/sitemap.xml',
        '/robots.txt',
        '/favicon.ico',
        '/index.php',
        '/.well-known',
        '/build',
        '/assets',
        '/css',
        '/js',
        '/images',
        '/img',
        '/fonts',
        '/media',
        '/storage',
        '/vendor',
    ];

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

        // Laravel can normalize a routed URI before middleware runs. REQUEST_URI
        // preserves whether the browser already sent the canonical trailing slash.
        $requestUri = (string) $request->server->get('REQUEST_URI', '');
        $requestUriPath = parse_url($requestUri, PHP_URL_PATH);
        $path = is_string($requestUriPath) && $requestUriPath !== ''
            ? $requestUriPath
            : $request->getPathInfo();

        // Админку, API, служебные маршруты и статические файлы не переписываем.
        if ($this->isExcludedPath($path)) {
            return $next($request);
        }

        $canonicalPath = $path === '/' ? '/' : rtrim($path, '/').'/';
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

    private function isExcludedPath(string $path): bool
    {
        foreach (self::EXCLUDED_PATHS as $excludedPath) {
            if ($path === $excludedPath || Str::startsWith($path, $excludedPath.'/')) {
                return true;
            }
        }

        return false;
    }
}
