<?php

namespace App\Support;

use Illuminate\Http\Request;

final class IndexingPolicy
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

    /** Return the same robots directive for the HTTP header and HTML meta tag. */
    public static function robots(Request $request): ?string
    {
        if ($request->is(self::TECHNICAL_PATHS)) {
            return 'noindex, nofollow, noarchive';
        }

        return $request->query->has('model') ? 'noindex, follow' : null;
    }
}
