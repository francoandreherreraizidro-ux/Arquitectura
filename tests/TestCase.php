<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Las consultas del LocationController usan LEFT(), que existe en MySQL
     * pero no en SQLite. En pruebas (SQLite en memoria) se registra una
     * función equivalente para poder ejecutar las mismas consultas.
     */
    protected function registerSqliteLeftFunction(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::connection()->getPdo()->sqliteCreateFunction(
            'LEFT',
            fn ($value, $length) => $value === null ? null : substr((string) $value, 0, (int) $length),
            2
        );
    }
}
