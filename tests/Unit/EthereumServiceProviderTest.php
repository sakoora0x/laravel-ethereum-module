<?php

namespace sakoora0x\LaravelEthereumModule\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use sakoora0x\LaravelEthereumModule\Console\Commands\AddressSyncCommand;
use sakoora0x\LaravelEthereumModule\Console\Commands\EthereumSyncCommand;
use sakoora0x\LaravelEthereumModule\Console\Commands\ExplorerSyncCommand;
use sakoora0x\LaravelEthereumModule\Console\Commands\NodeSyncCommand;
use sakoora0x\LaravelEthereumModule\Console\Commands\WalletSyncCommand;
use sakoora0x\LaravelEthereumModule\Ethereum;
use sakoora0x\LaravelEthereumModule\Tests\TestCase;

class EthereumServiceProviderTest extends TestCase
{
    /** @test */
    public function it_registers_ethereum_singleton()
    {
        $ethereum = app(Ethereum::class);

        $this->assertInstanceOf(Ethereum::class, $ethereum);

        // Verify it's a singleton
        $ethereum2 = app(Ethereum::class);
        $this->assertSame($ethereum, $ethereum2);
    }

    /** @test */
    public function it_registers_config_file()
    {
        $config = config('ethereum');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('models', $config);
        $this->assertArrayHasKey('webhook_handler', $config);
        $this->assertArrayHasKey('touch', $config);
    }

    /** @test */
    public function it_publishes_config_file()
    {
        $this->assertTrue(
            in_array('ethereum', array_keys(
                $this->app->make('config')->get('', [])
            )) || config('ethereum') !== null
        );
    }

    /** @test */
    public function it_registers_migrations()
    {
        // Check that tables exist after migrations
        $this->assertTrue(
            \Schema::hasTable('ethereum_nodes')
        );
        $this->assertTrue(
            \Schema::hasTable('ethereum_explorers')
        );
        $this->assertTrue(
            \Schema::hasTable('ethereum_tokens')
        );
        $this->assertTrue(
            \Schema::hasTable('ethereum_wallets')
        );
        $this->assertTrue(
            \Schema::hasTable('ethereum_addresses')
        );
        $this->assertTrue(
            \Schema::hasTable('ethereum_transactions')
        );
        $this->assertTrue(
            \Schema::hasTable('ethereum_deposits')
        );
    }

    /** @test */
    public function it_registers_node_sync_command()
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('ethereum:node-sync', $commands);
        $this->assertInstanceOf(NodeSyncCommand::class, $commands['ethereum:node-sync']);
    }

    /** @test */
    public function it_registers_explorer_sync_command()
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('ethereum:explorer-sync', $commands);
        $this->assertInstanceOf(ExplorerSyncCommand::class, $commands['ethereum:explorer-sync']);
    }

    /** @test */
    public function it_registers_address_sync_command()
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('ethereum:address-sync', $commands);
        $this->assertInstanceOf(AddressSyncCommand::class, $commands['ethereum:address-sync']);
    }

    /** @test */
    public function it_registers_wallet_sync_command()
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('ethereum:wallet-sync', $commands);
        $this->assertInstanceOf(WalletSyncCommand::class, $commands['ethereum:wallet-sync']);
    }

    /** @test */
    public function it_registers_ethereum_sync_command()
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('ethereum:sync', $commands);
        $this->assertInstanceOf(EthereumSyncCommand::class, $commands['ethereum:sync']);
    }

    /** @test */
    public function it_registers_facade_alias()
    {
        // Test that the facade can be resolved
        $this->assertTrue(class_exists(\sakoora0x\LaravelEthereumModule\Facades\Ethereum::class));
    }

    /** @test */
    public function it_loads_models_from_config()
    {
        $config = config('ethereum.models');

        $this->assertArrayHasKey('node', $config);
        $this->assertArrayHasKey('explorer', $config);
        $this->assertArrayHasKey('token', $config);
        $this->assertArrayHasKey('wallet', $config);
        $this->assertArrayHasKey('address', $config);
        $this->assertArrayHasKey('transaction', $config);
        $this->assertArrayHasKey('deposit', $config);

        $this->assertEquals('sakoora0x\LaravelEthereumModule\Models\EthereumNode', $config['node']);
        $this->assertEquals('sakoora0x\LaravelEthereumModule\Models\EthereumExplorer', $config['explorer']);
        $this->assertEquals('sakoora0x\LaravelEthereumModule\Models\EthereumToken', $config['token']);
        $this->assertEquals('sakoora0x\LaravelEthereumModule\Models\EthereumWallet', $config['wallet']);
        $this->assertEquals('sakoora0x\LaravelEthereumModule\Models\EthereumAddress', $config['address']);
        $this->assertEquals('sakoora0x\LaravelEthereumModule\Models\EthereumTransaction', $config['transaction']);
        $this->assertEquals('sakoora0x\LaravelEthereumModule\Models\EthereumDeposit', $config['deposit']);
    }

    /** @test */
    public function it_loads_webhook_handler_from_config()
    {
        $webhookHandler = config('ethereum.webhook_handler');

        $this->assertEquals('sakoora0x\LaravelEthereumModule\Webhook\EmptyWebhookHandler', $webhookHandler);
    }

    /** @test */
    public function it_loads_touch_config()
    {
        $touch = config('ethereum.touch');

        $this->assertIsArray($touch);
        $this->assertArrayHasKey('enabled', $touch);
        $this->assertArrayHasKey('waiting_seconds', $touch);
        $this->assertFalse($touch['enabled']);
        $this->assertEquals(3600, $touch['waiting_seconds']);
    }
}
