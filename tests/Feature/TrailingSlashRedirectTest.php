<?php

namespace Tests\Feature;

use App\Http\Middleware\TrailingSlashes;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TrailingSlashRedirectTest extends TestCase
{
    public function test_public_web_route_redirects_to_a_trailing_slash_and_preserves_query(): void
    {
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

        $response = $this->get('/policy?utm_source=audit&utm_campaign=canonical');

        $response->assertStatus(Response::HTTP_MOVED_PERMANENTLY);
        $location = $response->headers->get('Location');

        $this->assertSame('/policy/', strtok($location, '?'));
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertCount(2, $query);
        $this->assertSame('audit', $query['utm_source']);
        $this->assertSame('canonical', $query['utm_campaign']);
    }

    public function test_redirect_preserves_the_raw_query_string_byte_for_byte(): void
    {
        $response = $this->runMiddleware(Request::create(
            '/policy?tag=a&tag=b&encoded=a%2Fb&space=a+b',
            'GET'
        ));

        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        $this->assertSame(
            '/policy/?tag=a&tag=b&encoded=a%2Fb&space=a+b',
            $response->headers->get('Location')
        );
    }

    public function test_redirect_location_does_not_reflect_the_request_host(): void
    {
        $response = $this->runMiddleware(Request::create(
            'https://attacker.example/policy?source=host',
            'GET'
        ));

        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        $this->assertSame('/policy/?source=host', $response->headers->get('Location'));
    }

    public function test_head_request_redirects_to_a_trailing_slash(): void
    {
        $response = $this->runMiddleware(Request::create('/policy?source=head', 'HEAD'));

        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        $this->assertSame('/policy/?source=head', $response->headers->get('Location'));
    }

    public function test_root_is_left_unchanged(): void
    {
        $response = $this->runMiddleware(Request::create('/?source=home', 'GET'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function test_public_path_with_a_trailing_slash_is_left_unchanged(): void
    {
        $response = $this->runMiddleware(Request::create('/policy/', 'GET'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function test_repeated_trailing_slashes_redirect_to_one_slash(): void
    {
        $response = $this->runMiddleware(Request::create('/policy//?source=duplicate', 'GET'));

        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        $this->assertSame('/policy/?source=duplicate', $response->headers->get('Location'));
    }

    public function test_public_content_slug_ending_in_a_file_extension_is_canonicalized(): void
    {
        $response = $this->runMiddleware(Request::create('/shop-pages/catalog.pdf', 'GET'));

        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        $this->assertSame('/shop-pages/catalog.pdf/', $response->headers->get('Location'));
    }

    #[DataProvider('mutatingMethods')]
    public function test_mutating_requests_are_left_unchanged(string $method): void
    {
        $response = $this->runMiddleware(Request::create('/policy', $method));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public static function mutatingMethods(): array
    {
        return [
            'POST' => ['POST'],
            'PUT' => ['PUT'],
            'PATCH' => ['PATCH'],
            'DELETE' => ['DELETE'],
        ];
    }

    #[DataProvider('excludedPaths')]
    public function test_technical_auth_api_asset_and_sitemap_paths_are_left_unchanged(string $path): void
    {
        $response = $this->runMiddleware(Request::create($path, 'GET'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), $path);
    }

    public static function excludedPaths(): array
    {
        return [
            'admin' => ['/admin'],
            'nested admin' => ['/admin/orders'],
            'api' => ['/api/models'],
            'ignition' => ['/_ignition/health-check'],
            'login' => ['/login'],
            'logout' => ['/logout'],
            'register' => ['/register'],
            'password reset' => ['/password/reset'],
            'sanctum' => ['/sanctum/csrf-cookie'],
            'cart' => ['/cart'],
            'nested cart' => ['/cart/edit/item-key'],
            'checkout' => ['/checkout'],
            'profile' => ['/profile'],
            'favorites' => ['/favorites'],
            'sheet endpoint' => ['/sheet-names'],
            'popup endpoint' => ['/popup/1'],
            'category editor' => ['/category/jaluzi/edit'],
            'category questions' => ['/categories/jaluzi/questions'],
            'page creator' => ['/pages/create'],
            'page editor' => ['/pages/edit/1'],
            'page listing' => ['/allpages'],
            'header editor' => ['/header-info/edit'],
            'model image endpoint' => ['/get-model-image/1'],
            'colors endpoint' => ['/colors'],
            'sitemap' => ['/sitemap.xml'],
            'robots' => ['/robots.txt'],
            'build asset' => ['/build/assets/app.js'],
            'css asset' => ['/css/app.css'],
            'javascript asset' => ['/js/app.js'],
            'image asset' => ['/images/logo.webp'],
            'storage asset' => ['/storage/catalog/image.jpg'],
            'favicon' => ['/favicon.ico'],
            'front controller' => ['/index.php'],
        ];
    }

    #[DataProvider('publicPathsSharingExcludedPrefixes')]
    public function test_exclusion_prefixes_only_match_complete_path_segments(string $path): void
    {
        $response = $this->runMiddleware(Request::create($path, 'GET'));

        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode(), $path);
        $this->assertSame($path.'/', $response->headers->get('Location'));
    }

    public static function publicPathsSharingExcludedPrefixes(): array
    {
        return [
            'admin prefix' => ['/administrator'],
            'api prefix' => ['/apiary'],
            'login prefix' => ['/loginov'],
        ];
    }

    #[DataProvider('representativePublicPaths')]
    public function test_representative_public_content_paths_redirect_to_a_trailing_slash(string $path): void
    {
        $response = $this->runMiddleware(Request::create($path, 'GET'));

        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode(), $path);
        $this->assertSame($path.'/', $response->headers->get('Location'));
    }

    public static function representativePublicPaths(): array
    {
        return [
            'content page' => ['/shop-pages/dostavka'],
            'subcategory' => ['/jaluzi/rulonnye-shtory'],
            'product' => ['/jaluzi/rulonnye-shtory/example-product'],
        ];
    }

    private function runMiddleware(Request $request): Response
    {
        return (new TrailingSlashes)->handle(
            $request,
            fn () => response('next')
        );
    }
}
