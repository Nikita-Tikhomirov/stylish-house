<?php

namespace Tests\Feature;

use App\Http\Middleware\AddNoIndexHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuditTechnicalIndexingTest extends TestCase
{
    public function test_noindex_header_is_added_to_technical_pages(): void
    {
        $middleware = new AddNoIndexHeader();

        foreach (['/register', '/login', '/cart', '/checkout', '/sheet-names'] as $uri) {
            $response = $middleware->handle(
                Request::create($uri, 'GET'),
                fn () => response('ok')
            );

            $this->assertSame(
                'noindex, nofollow, noarchive',
                $response->headers->get('X-Robots-Tag'),
                $uri
            );
        }
    }

    public function test_noindex_header_is_not_added_to_catalog_pages(): void
    {
        $response = (new AddNoIndexHeader())->handle(
            Request::create('/jaluzi', 'GET'),
            fn () => response('ok')
        );

        $this->assertFalse($response->headers->has('X-Robots-Tag'));
    }

    public function test_sheet_names_test_route_is_admin_only(): void
    {
        $route = Route::getRoutes()->match(Request::create('/sheet-names-test', 'GET'));

        $this->assertContains('role:admin', $route->gatherMiddleware());
    }

    public function test_robots_file_blocks_technical_pages(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        foreach (['/register', '/login', '/password/', '/cart', '/checkout', '/profile', '/favorites', '/sheet-names'] as $path) {
            $this->assertStringContainsString('Disallow: '.$path, $robots, $path);
        }
    }

    public function test_sitemap_generator_excludes_cart_and_includes_content_pages(): void
    {
        $source = file_get_contents(app_path('Console/Commands/GenerateSitemap.php'));

        $this->assertStringNotContainsString("route('cart.show')", $source);
        $this->assertStringContainsString('Page::query()', $source);
        $this->assertStringContainsString("route('policy')", $source);
    }
}
