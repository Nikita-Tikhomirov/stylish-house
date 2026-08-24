<?php

namespace Tests\Feature;

use App\Http\Middleware\AddNoIndexHeader;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
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

    public function test_public_filter_indexing_policy_runs_through_the_http_kernel(): void
    {
        $this->withoutVite();
        $this->withoutExceptionHandling();
        config([
            'app.url' => 'https://stylish-house.net',
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
        ]);

        $kernel = $this->app->make(HttpKernel::class);
        $get = function (string $uri) use ($kernel) {
            $request = Request::create($uri, 'GET');
            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);

            return $this->createTestResponse($response);
        };

        $filter = $get('/policy/?model=42');
        $filter->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertSee('<meta name="robots" content="noindex, follow" />', false)
            ->assertSee(
                '<link rel="canonical" href="https://stylish-house.net/policy/" />',
                false
            );

        $filteredPagination = $get('/policy/?page=2&model=42');
        $filteredPagination->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertSee('<meta name="robots" content="noindex, follow" />', false);

        $pagination = $get('/policy/?page=2');
        $pagination->assertOk()
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertDontSee('name="robots"', false)
            ->assertSee(
                '<link rel="canonical" href="https://stylish-house.net/policy/?page=2" />',
                false
            );

        $tracking = $get('/policy/?utm_source=chatgpt.com');
        $tracking->assertOk()
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertDontSee('name="robots"', false)
            ->assertSee(
                '<link rel="canonical" href="https://stylish-house.net/policy/" />',
                false
            );

        $this->app->instance('request', Request::create('/cart/?model=42', 'GET'));
        $technicalHead = view('components.front.head', [
            'title' => 'SEO test',
            'description' => 'SEO test',
        ])->render();
        $this->assertStringContainsString(
            '<meta name="robots" content="noindex, nofollow, noarchive" />',
            $technicalHead
        );
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

    public function test_robots_file_allows_query_pages_to_receive_indexing_directives(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringNotContainsString('Disallow: /*?*', $robots);
        $this->assertStringNotContainsString('Disallow: /?*', $robots);
        $this->assertStringContainsString('Host: https://stylish-house.net/', $robots);
        $this->assertStringContainsString(
            'Sitemap: https://stylish-house.net/sitemap.xml',
            $robots
        );
    }

    public function test_sitemap_generator_excludes_cart_and_includes_content_pages(): void
    {
        $source = file_get_contents(app_path('Console/Commands/GenerateSitemap.php'));

        $this->assertStringNotContainsString("route('cart.show')", $source);
        $this->assertStringContainsString('Page::query()', $source);
        $this->assertStringContainsString("route('policy')", $source);
    }

}
