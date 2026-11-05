<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $driver = $app['config']->get('database.default');
        $connection = $app['config']->get('database.connections.'.$driver);
        $sqlite = $driver === 'sqlite' && $connection['database'] === ':memory:';
        $postgres = $driver === 'pgsql' && $connection['database'] === 'mon_miam_miam_test'
            && in_array($connection['host'], ['localhost', '127.0.0.1'], true);
        if ((! $sqlite && ! $postgres) || ! empty($connection['url']) || ! $app->environment('testing')) {
            throw new \RuntimeException('Tests refusés : seule une base de test locale dédiée est autorisée.');
        }
        Http::preventStrayRequests();

        return $app;
    }
}
