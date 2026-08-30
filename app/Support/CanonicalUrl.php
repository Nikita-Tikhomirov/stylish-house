<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Str;

final class CanonicalUrl
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

    public static function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $path = self::to(route($name, $parameters, false));

        return $absolute ? self::withConfiguredOrigin($path) : $path;
    }

    public static function current(): string
    {
        $url = self::withConfiguredOrigin(self::path(request()->getPathInfo()));
        $page = filter_var(request()->query('page'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 2],
        ]);

        return $page === false ? $url : $url.'?page='.$page;
    }

    public static function paginator(AbstractPaginator $paginator, ?string $path = null): AbstractPaginator
    {
        $path = parse_url($path ?? $paginator->path(), PHP_URL_PATH);
        if (! is_string($path)) {
            return $paginator;
        }

        $paginator->setPath(self::withConfiguredOrigin(self::path($path)));

        return $paginator;
    }

    /** Append the untouched server query string to a canonical path. */
    public static function withQueryString(string $url, ?string $queryString): string
    {
        return $queryString === null || $queryString === ''
            ? $url
            : $url.'?'.$queryString;
    }

    /** Return the path exactly as it arrived before Laravel route normalization. */
    public static function requestPath(Request $request): string
    {
        $requestUri = (string) $request->server->get('REQUEST_URI', '');
        $path = parse_url($requestUri, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : $request->getPathInfo();
    }

    public static function to(string $url): string
    {
        if ($url === '' || Str::startsWith($url, ['#', '?'])) {
            return $url;
        }

        $suffixOffset = strcspn($url, '?#');
        $base = substr($url, 0, $suffixOffset);
        $suffix = substr($url, $suffixOffset);
        $scheme = parse_url($base, PHP_URL_SCHEME);
        $host = parse_url($base, PHP_URL_HOST);

        if (is_string($scheme) && ! in_array(Str::lower($scheme), ['http', 'https'], true)) {
            return $url;
        }

        if (is_string($host) && ! self::isInternalHost($host)) {
            return $url;
        }

        $path = parse_url($base, PHP_URL_PATH);

        if (! is_string($path) && is_string($host)) {
            $path = '';
        } elseif (! is_string($path)) {
            return $url;
        }

        $canonicalPath = self::path($path);
        if ($canonicalPath === $path) {
            return $url;
        }

        return substr($base, 0, strlen($base) - strlen($path)).$canonicalPath.$suffix;
    }

    public static function path(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        foreach (self::EXCLUDED_PATHS as $excludedPath) {
            if ($path === $excludedPath || Str::startsWith($path, $excludedPath.'/')) {
                return $path;
            }
        }

        return rtrim($path, '/').'/';
    }

    private static function isInternalHost(string $host): bool
    {
        $configuredHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return is_string($configuredHost)
            && Str::lower($host) === Str::lower($configuredHost);
    }

    private static function withConfiguredOrigin(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
