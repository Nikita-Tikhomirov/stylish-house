<?php

namespace Tests\Unit;

use App\Support\CanonicalUrl;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Tests\TestCase;

class CanonicalPaginationTest extends TestCase
{
    public function test_public_pagination_links_keep_the_canonical_trailing_slash(): void
    {
        $this->app->instance('request', Request::create('/jaluzi/', 'GET'));

        $paginator = new LengthAwarePaginator([], 24, 12, 1, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
        CanonicalUrl::paginator($paginator);

        $this->assertSame('http://localhost/jaluzi/?page=2', $paginator->url(2));
    }

    public function test_technical_pagination_links_are_not_rewritten(): void
    {
        $this->app->instance('request', Request::create('/admin/orders', 'GET'));

        $paginator = new LengthAwarePaginator([], 24, 12, 1, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
        CanonicalUrl::paginator($paginator);

        $this->assertSame('http://localhost/admin/orders?page=2', $paginator->url(2));
    }

    public function test_ajax_pagination_can_point_to_the_public_canonical_page(): void
    {
        config(['app.url' => 'https://stylish-house.net']);
        $this->app->instance(
            'request',
            Request::create('https://attacker.example/filter-cat-products/7', 'POST')
        );

        $paginator = new LengthAwarePaginator([], 24, 12, 1, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
        CanonicalUrl::paginator($paginator, '/jaluzi');

        $this->assertSame('https://stylish-house.net/jaluzi/?page=2', $paginator->url(2));
    }

    public function test_raw_request_path_and_query_can_be_preserved_for_canonical_redirects(): void
    {
        $request = Request::create('/wrong-category/test-subcategory?model=1%2C2', 'GET');

        $this->assertSame(
            '/wrong-category/test-subcategory',
            CanonicalUrl::requestPath($request)
        );
        $this->assertSame(
            '/test-category/test-subcategory/?model=1%2C2',
            CanonicalUrl::withQueryString(
                '/test-category/test-subcategory/',
                (string) $request->server->get('QUERY_STRING')
            )
        );
    }
}
