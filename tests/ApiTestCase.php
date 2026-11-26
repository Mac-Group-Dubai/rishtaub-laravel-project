<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    // Safety net: RefreshDatabase wipes every table, so it must never touch the real database.
    protected function beforeRefreshingDatabase(): void
    {
        $name = (string) config('database.connections.' . config('database.default') . '.database');

        if (! str_contains($name, 'test')) {
            throw new \RuntimeException(
                "Refusing to run tests on database [{$name}]. Use a database whose name contains 'test'."
            );
        }
    }
}
