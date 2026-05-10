<?php

namespace Tests\Feature;

use App\Http\Controllers\SubcategoryController;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubcategorySeoImageUploadTest extends TestCase
{
    public function test_it_uploads_seo_editor_image_and_returns_public_url(): void
    {
        Storage::fake('public');

        $request = Request::create('/admin/subcategory-seo/upload-image', 'POST', [], [], [
            'image' => UploadedFile::fake()->image('seo-photo.jpg', 900, 600),
        ]);

        $response = app(SubcategoryController::class)->uploadSeoImage($request);
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('url', $payload);
        $this->assertStringStartsWith('/storage/subcategory-seo/', $payload['url']);

        $storedPath = str_replace('/storage/', '', $payload['url']);
        Storage::disk('public')->assertExists($storedPath);
    }
}
