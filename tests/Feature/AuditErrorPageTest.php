<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuditErrorPageTest extends TestCase
{
    public function test_missing_page_uses_helpful_branded_404(): void
    {
        $path = resource_path('views/errors/404.blade.php');

        $this->assertFileExists($path);

        $html = view('errors.404')->render();

        $this->assertStringContainsString('<h1>Страница не найдена</h1>', $html);
        $this->assertStringContainsString('Вернуться на главную', $html);
        $this->assertStringContainsString(route('front.home'), $html);
    }
}
