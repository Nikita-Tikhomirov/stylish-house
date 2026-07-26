<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSafeTestDatabase();
    }

    private function configureSafeTestDatabase(): void
    {
        if (extension_loaded('pdo_sqlite')) {
            config()->set('database.default', 'sqlite');
            config()->set('database.connections.sqlite.database', ':memory:');
            DB::purge('sqlite');

            return;
        }

        $database = trim((string) getenv('TEST_DB_DATABASE'));
        if ($database === '') {
            $this->markTestSkipped('TEST_DB_DATABASE is required when pdo_sqlite is unavailable.');
        }

        if (! preg_match('/(?:^|_)test$/i', $database)) {
            throw new \RuntimeException(
                sprintf('Unsafe test database "%s". Its name must end with "_test".', $database)
            );
        }

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', $database);
        DB::purge('mysql');
    }
}
