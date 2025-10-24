<?php

namespace sakoora0x\LaravelEthereumModule\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use sakoora0x\LaravelEthereumModule\EthereumServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Run package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function tearDown(): void
    {
        // This ensures a clean slate for each test
        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            EthereumServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup ethereum config
        $app['config']->set('ethereum', require __DIR__ . '/../config/ethereum.php');
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Ethereum' => \sakoora0x\LaravelEthereumModule\Facades\Ethereum::class,
        ];
    }
}
