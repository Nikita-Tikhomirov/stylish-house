<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CartTemplateCoverageTest extends TestCase
{
    public function test_every_cart_add_handler_includes_complete_product_options(): void
    {
        $viewsPath = dirname(__DIR__, 2) . '/resources/views';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));
        $handlers = [];
        $missing = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (! str_contains($contents, "fetch('/cart/add'")) {
                continue;
            }

            $handlers[] = $file->getPathname();
            if (! str_contains($contents, 'collectCartOptions')) {
                $missing[] = $file->getPathname();
            }
        }

        $this->assertCount(8, $handlers, 'Unexpected number of cart add templates; audit new handlers.');
        $this->assertSame([], $missing, 'Some cart handlers drop product configuration.');
    }
}
