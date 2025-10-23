<?php

namespace sakoora0x\LaravelEthereumModule;

use sakoora0x\LaravelEthereumModule\Console\Commands\AddressSyncCommand;
use sakoora0x\LaravelEthereumModule\Console\Commands\EthereumSyncCommand;
use sakoora0x\LaravelEthereumModule\Console\Commands\ExplorerSyncCommand;
use sakoora0x\LaravelEthereumModule\Console\Commands\NodeSyncCommand;
use sakoora0x\LaravelEthereumModule\Console\Commands\WalletSyncCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class EthereumServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ethereum')
            ->hasConfigFile()
            ->hasCommands(
                NodeSyncCommand::class,
                ExplorerSyncCommand::class,
                AddressSyncCommand::class,
                WalletSyncCommand::class,
                EthereumSyncCommand::class,
            )
            ->discoversMigrations()
            ->hasInstallCommand(function(InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations();
            });

        $this->app->singleton(Ethereum::class);
    }
}
