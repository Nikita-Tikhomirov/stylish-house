<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RepairMissingPagesMigrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Schema::dropIfExists('pages');

        parent::tearDown();
    }

    public function test_it_restores_required_service_pages_without_overwriting_existing_content(): void
    {
        Schema::dropIfExists('pages');

        $migration = require database_path('migrations/2026_07_30_000001_repair_missing_pages_table.php');
        $migration->up();

        $requiredSlugs = [
            'uslugi',
            'rasschitat',
            'portfolio',
            'oplata-i-dostavka',
            'kontakty',
            'zamer',
        ];

        $this->assertTrue(Schema::hasTable('pages'));
        $this->assertEqualsCanonicalizing(
            $requiredSlugs,
            DB::table('pages')->pluck('slug')->all()
        );

        DB::table('pages')->where('slug', 'kontakty')->update(['content' => 'Сохранить этот текст']);
        $migration->up();

        $this->assertSame(
            'Сохранить этот текст',
            DB::table('pages')->where('slug', 'kontakty')->value('content')
        );
        $this->assertSame(count($requiredSlugs), DB::table('pages')->count());
    }
}
