<?php

namespace sakoora0x\LaravelEthereumModule\Tests\Unit;

use sakoora0x\LaravelEthereumModule\Facades\Ethereum;
use sakoora0x\LaravelEthereumModule\Models\EthereumAddress;
use sakoora0x\LaravelEthereumModule\Models\EthereumExplorer;
use sakoora0x\LaravelEthereumModule\Models\EthereumNode;
use sakoora0x\LaravelEthereumModule\Models\EthereumToken;
use sakoora0x\LaravelEthereumModule\Models\EthereumWallet;
use sakoora0x\LaravelEthereumModule\Tests\TestCase;

class EthereumWalletModelTest extends TestCase
{
    /** @test */
    public function it_can_create_wallet()
    {
        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->save();

        $this->assertInstanceOf(EthereumWallet::class, $wallet);
        $this->assertEquals('Test Wallet', $wallet->name);
        $this->assertDatabaseHas('ethereum_wallets', [
            'name' => 'Test Wallet',
        ]);
    }

    /** @test */
    public function it_encrypts_sensitive_data()
    {
        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->password = 'test-password';
        $wallet->mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $wallet->seed = 'test-seed-data';
        $wallet->save();

        // Reload from database
        $wallet = $wallet->fresh();

        // Should decrypt automatically
        $this->assertEquals('test-password', $wallet->password);
        $this->assertEquals('abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about', $wallet->mnemonic);
        $this->assertEquals('test-seed-data', $wallet->seed);
    }

    /** @test */
    public function it_hides_sensitive_attributes_in_array()
    {
        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->password = 'test-password';
        $wallet->mnemonic = 'test-mnemonic';
        $wallet->seed = 'test-seed';
        $wallet->save();

        $array = $wallet->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('mnemonic', $array);
        $this->assertArrayNotHasKey('seed', $array);
        $this->assertArrayNotHasKey('tokens', $array);
    }

    /** @test */
    public function it_has_appended_attributes()
    {
        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->save();

        $array = $wallet->toArray();

        $this->assertArrayHasKey('tokens_balances', $array);
        $this->assertArrayHasKey('has_password', $array);
        $this->assertArrayHasKey('has_mnemonic', $array);
        $this->assertArrayHasKey('has_seed', $array);
    }

    /** @test */
    public function it_returns_correct_has_password_attribute()
    {
        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->save();

        $this->assertFalse($wallet->has_password);

        $wallet->password = 'test-password';
        $wallet->save();

        $this->assertTrue($wallet->fresh()->has_password);
    }

    /** @test */
    public function it_returns_correct_has_mnemonic_attribute()
    {
        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->save();

        $this->assertFalse($wallet->has_mnemonic);

        $wallet->mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $wallet->save();

        $this->assertTrue($wallet->fresh()->has_mnemonic);
    }

    /** @test */
    public function it_returns_correct_has_seed_attribute()
    {
        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->save();

        $this->assertFalse($wallet->has_seed);

        $wallet->seed = 'test-seed-data';
        $wallet->save();

        $this->assertTrue($wallet->fresh()->has_seed);
    }

    /** @test */
    public function it_can_unlock_wallet()
    {
        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);

        $password = 'test-password';
        $wallet->unlockWallet($password);

        $this->assertEquals($password, $wallet->plain_password);
    }

    /** @test */
    public function it_belongs_to_node()
    {
        $node = new EthereumNode([
            'name' => 'Test Node',
            'base_url' => 'https://test-node.example.com',
            'worked' => true,
            'available' => true,
        ]);
        $node->save();

        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
            'node_id' => $node->id,
        ]);
        $wallet->save();

        $this->assertInstanceOf(EthereumNode::class, $wallet->node);
        $this->assertEquals($node->id, $wallet->node->id);
    }

    /** @test */
    public function it_belongs_to_explorer()
    {
        $explorer = new EthereumExplorer([
            'name' => 'Test Explorer',
            'base_url' => 'https://test-explorer.example.com',
            'worked' => true,
            'available' => true,
        ]);
        $explorer->save();

        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
            'explorer_id' => $explorer->id,
        ]);
        $wallet->save();

        $this->assertInstanceOf(EthereumExplorer::class, $wallet->explorer);
        $this->assertEquals($explorer->id, $wallet->explorer->id);
    }

    /** @test */
    public function it_has_many_addresses()
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $seed = Ethereum::mnemonicSeed($mnemonic);

        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->seed = $seed;
        $wallet->save();

        Ethereum::createAddress($wallet, 'Address 1', 0, $seed);
        Ethereum::createAddress($wallet, 'Address 2', 1, $seed);

        $this->assertCount(2, $wallet->addresses);
        $this->assertInstanceOf(EthereumAddress::class, $wallet->addresses->first());
    }

    /** @test */
    public function it_stores_and_retrieves_tokens_array()
    {
        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->tokens = [
            '0x1234567890123456789012345678901234567890' => '1000000000000000000',
            '0xabcdefabcdefabcdefabcdefabcdefabcdefabcd' => '2000000000000000000',
        ];
        $wallet->save();

        $wallet = $wallet->fresh();

        $this->assertIsArray($wallet->tokens);
        $this->assertArrayHasKey('0x1234567890123456789012345678901234567890', $wallet->tokens);
        $this->assertEquals('1000000000000000000', $wallet->tokens['0x1234567890123456789012345678901234567890']);
    }

    /** @test */
    public function it_returns_tokens_balances_attribute()
    {
        $token = new EthereumToken([
            'address' => '0x1234567890123456789012345678901234567890',
            'name' => 'Test Token',
            'symbol' => 'TST',
            'decimals' => 18,
        ]);
        $token->save();

        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->tokens = [
            '0x1234567890123456789012345678901234567890' => '1000000000000000000',
        ];
        $wallet->save();

        $tokensBalances = $wallet->tokens_balances;

        $this->assertIsObject($tokensBalances);
        $this->assertArrayHasKey('0x1234567890123456789012345678901234567890', $tokensBalances->toArray());
        $this->assertEquals('Test Token', $tokensBalances['0x1234567890123456789012345678901234567890']['name']);
        $this->assertEquals('TST', $tokensBalances['0x1234567890123456789012345678901234567890']['symbol']);
        $this->assertEquals('1000000000000000000', $tokensBalances['0x1234567890123456789012345678901234567890']['balance']);
    }

    /** @test */
    public function it_casts_sync_at_to_datetime()
    {
        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
            'sync_at' => '2024-01-01 12:00:00',
        ]);
        $wallet->save();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $wallet->fresh()->sync_at);
    }
}
