<?php

namespace Tests\Unit;

use App\Support\CatalogModelSelection;
use PHPUnit\Framework\TestCase;

class CatalogModelSelectionTest extends TestCase
{
    public function test_it_selects_only_requested_models_available_in_the_catalog(): void
    {
        $selection = CatalogModelSelection::resolve('45,999,46', [43, 45, 46], null);

        $this->assertSame([45, 46], $selection);
    }

    public function test_configured_models_remain_the_default_without_a_menu_filter(): void
    {
        $selection = CatalogModelSelection::resolve(null, [33, 35, 37], '["35", "37"]');

        $this->assertSame([35, 37], $selection);
    }

    public function test_a_requested_model_cannot_escape_the_configured_catalog_scope(): void
    {
        $selection = CatalogModelSelection::resolve('33,35', [33, 35, 37], '["35", "37"]');

        $this->assertSame([35], $selection);
    }
}
