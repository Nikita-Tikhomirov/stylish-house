<?php

namespace Tests\Unit;

use App\Support\CanonicalUrl;
use Illuminate\Http\Request;
use Tests\TestCase;

class CanonicalUrlTest extends TestCase
{
    public function test_it_canonicalizes_public_internal_urls_without_changing_the_suffix(): void
    {
        $this->assertSame('/policy/?source=menu#details', CanonicalUrl::to('/policy?source=menu#details'));
        $this->assertSame('/policy/', CanonicalUrl::to('/policy/'));
        $this->assertSame('/policy/?source=duplicate', CanonicalUrl::to('/policy//?source=duplicate'));
        $this->assertSame('http://localhost/', CanonicalUrl::to('http://localhost'));
        $this->assertSame('http://localhost/policy/', CanonicalUrl::to('http://localhost/policy'));
    }

    public function test_it_leaves_external_protocol_and_technical_urls_unchanged(): void
    {
        $this->assertSame('https://example.com/catalog', CanonicalUrl::to('https://example.com/catalog'));
        $this->assertSame('mailto:info@example.com', CanonicalUrl::to('mailto:info@example.com'));
        $this->assertSame('tel:+79991234567', CanonicalUrl::to('tel:+79991234567'));
        $this->assertSame('/cart?step=delivery', CanonicalUrl::to('/cart?step=delivery'));
        $this->assertSame('/storage/catalog/photo.jpg', CanonicalUrl::to('/storage/catalog/photo.jpg'));
        $this->assertSame('#calculator', CanonicalUrl::to('#calculator'));
    }

    public function test_current_url_uses_the_configured_origin_and_only_keeps_pagination(): void
    {
        config(['app.url' => 'https://stylish-house.net']);
        $this->app->instance(
            'request',
            Request::create('https://attacker.example/jaluzi/?page=2&utm_source=audit')
        );

        $this->assertSame(
            'https://stylish-house.net/jaluzi/?page=2',
            CanonicalUrl::current()
        );
        $this->assertSame(
            'https://attacker.example/catalog',
            CanonicalUrl::to('https://attacker.example/catalog')
        );
        $this->assertSame(
            'https://stylish-house.net/policy/',
            CanonicalUrl::route('policy')
        );
    }
}
