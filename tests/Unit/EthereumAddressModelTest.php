<?php

namespace sakoora0x\LaravelEthereumModule\Tests\Unit;

use Brick\Math\BigDecimal;
use sakoora0x\LaravelEthereumModule\Facades\Ethereum;
use sakoora0x\LaravelEthereumModule\Models\EthereumAddress;
use sakoora0x\LaravelEthereumModule\Models\EthereumToken;
use sakoora0x\LaravelEthereumModule\Models\EthereumWallet;
use sakoora0x\LaravelEthereumModule\Tests\TestCase;

class EthereumAddressModelTest extends TestCase
{
    /** @test */
    public function it_can_create_address()
    {
        $wallet = new EthereumWallet(['name' => 'Test Wallet']);
        $wallet->save();

        $address = new EthereumAddress([
            'wallet_id' => $wallet->id,
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
            'title' => 'Test Address',
        ]);
        $address->save();

        $this->assertInstanceOf(EthereumAddress::class, $address);
        $this->assertEquals('0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed', $address->address);
        $this->assertDatabaseHas('ethereum_addresses', [
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
            'title' => 'Test Address',
        ]);
    }

    /** @test */
    public function it_encrypts_private_key()
    {
        $wallet = new EthereumWallet(['name' => 'Test Wallet']);
        $wallet->save();

        $privateKey = '0x4c0883a69102937d6231471b5dbb6204fe512961708279f8c5c1e9f3b5b8f0e4';

        $address = new EthereumAddress([
            'wallet_id' => $wallet->id,
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
        ]);
        $address->private_key = $privateKey;
        $address->save();

        // Reload from database
        $address = $address->fresh();

        // Should decrypt automatically
        $this->assertEquals($privateKey, $address->private_key);
    }

    /** @test */
    public function it_hides_sensitive_attributes_in_array()
    {
        $wallet = new EthereumWallet(['name' => 'Test Wallet']);
        $wallet->save();

        $address = new EthereumAddress([
            'wallet_id' => $wallet->id,
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
        ]);
        $address->private_key = '0x4c0883a69102937d6231471b5dbb6204fe512961708279f8c5c1e9f3b5b8f0e4';
        $address->save();

        $array = $address->toArray();

        $this->assertArrayNotHasKey('private_key', $array);
        $this->assertArrayNotHasKey('tokens', $array);
    }

    /** @test */
    public function it_has_appended_tokens_balances_attribute()
    {
        $wallet = new EthereumWallet(['name' => 'Test Wallet']);
        $wallet->save();

        $address = new EthereumAddress([
            'wallet_id' => $wallet->id,
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
        ]);
        $address->save();

        $array = $address->toArray();

        $this->assertArrayHasKey('tokens_balances', $array);
    }

    /** @test */
    public function it_belongs_to_wallet()
    {
        $wallet = new EthereumWallet(['name' => 'Test Wallet']);
        $wallet->save();

        $address = new EthereumAddress([
            'wallet_id' => $wallet->id,
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
        ]);
        $address->save();

        $this->assertInstanceOf(EthereumWallet::class, $address->wallet);
        $this->assertEquals($wallet->id, $address->wallet->id);
    }

    /** @test */
    public function it_casts_watch_only_to_boolean()
    {
        $wallet = new EthereumWallet(['name' => 'Test Wallet']);
        $wallet->save();

        $address = new EthereumAddress([
            'wallet_id' => $wallet->id,
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
            'watch_only' => true,
        ]);
        $address->save();

        $this->assertIsBool($address->fresh()->watch_only);
        $this->assertTrue($address->fresh()->watch_only);
    }

    /** @test */
    public function it_casts_balance_to_big_decimal()
    {
        $wallet = new EthereumWallet(['name' => 'Test Wallet']);
        $wallet->save();

        $address = new EthereumAddress([
            'wallet_id' => $wallet->id,
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
            'balance' => '1000000000000000000',
        ]);
        $address->save();

        $balance = $address->fresh()->balance;

        $this->assertInstanceOf(BigDecimal::class, $balance);
        $this->assertEquals('1000000000000000000', $balance->__toString());
    }

    /** @test */
    public function it_stores_and_retrieves_tokens_array()
    {
        $wallet = new EthereumWallet(['name' => 'Test Wallet']);
        $wallet->save();

        $address = new EthereumAddress([
            'wallet_id' => $wallet->id,
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
        ]);
        $address->tokens = [
            '0x1234567890123456789012345678901234567890' => '1000000000000000000',
            '0xabcdefabcdefabcdefabcdefabcdefabcdefabcd' => '2000000000000000000',
        ];
        $address->save();

        $address = $address->fresh();

        $this->assertIsArray($address->tokens);
        $this->assertArrayHasKey('0x1234567890123456789012345678901234567890', $address->tokens);
        $this->assertEquals('1000000000000000000', $address->tokens['0x1234567890123456789012345678901234567890']);
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

        $wallet = new EthereumWallet(['name' => 'Test Wallet']);
        $wallet->save();

        $address = new EthereumAddress([
            'wallet_id' => $wallet->id,
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
        ]);
        $address->tokens = [
            '0x1234567890123456789012345678901234567890' => '1000000000000000000',
        ];
        $address->save();

        $tokensBalances = $address->tokens_balances;

        $this->assertIsObject($tokensBalances);
        $this->assertArrayHasKey('0x1234567890123456789012345678901234567890', $tokensBalances->toArray());
        $this->assertEquals('Test Token', $tokensBalances['0x1234567890123456789012345678901234567890']['name']);
        $this->assertEquals('TST', $tokensBalances['0x1234567890123456789012345678901234567890']['symbol']);
        $this->assertEquals('1000000000000000000', $tokensBalances['0x1234567890123456789012345678901234567890']['balance']);
    }

    /** @test */
    public function it_casts_datetime_fields()
    {
        $wallet = new EthereumWallet(['name' => 'Test Wallet']);
        $wallet->save();

        $address = new EthereumAddress([
            'wallet_id' => $wallet->id,
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
            'touch_at' => '2024-01-01 12:00:00',
            'sync_at' => '2024-01-01 13:00:00',
        ]);
        $address->save();

        $address = $address->fresh();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $address->touch_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $address->sync_at);
    }

    /** @test */
    public function it_casts_sync_block_number_to_integer()
    {
        $wallet = new EthereumWallet(['name' => 'Test Wallet']);
        $wallet->save();

        $address = new EthereumAddress([
            'wallet_id' => $wallet->id,
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
            'sync_block_number' => '12345',
        ]);
        $address->save();

        $this->assertIsInt($address->fresh()->sync_block_number);
        $this->assertEquals(12345, $address->fresh()->sync_block_number);
    }

    /** @test */
    public function it_casts_available_to_boolean()
    {
        $wallet = new EthereumWallet(['name' => 'Test Wallet']);
        $wallet->save();

        $address = new EthereumAddress([
            'wallet_id' => $wallet->id,
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
            'available' => true,
        ]);
        $address->save();

        $this->assertIsBool($address->fresh()->available);
        $this->assertTrue($address->fresh()->available);
    }

    /** @test */
    public function it_gets_password_from_wallet()
    {
        $wallet = new EthereumWallet(['name' => 'Test Wallet']);
        $wallet->password = 'test-password';
        $wallet->save();

        $address = new EthereumAddress([
            'wallet_id' => $wallet->id,
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
        ]);
        $address->save();

        $this->assertEquals('test-password', $address->password);
    }

    /** @test */
    public function it_gets_plain_password_from_wallet()
    {
        $wallet = new EthereumWallet(['name' => 'Test Wallet']);
        $wallet->unlockWallet('test-password');
        $wallet->save();

        $address = new EthereumAddress([
            'wallet_id' => $wallet->id,
            'address' => '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed',
        ]);
        $address->save();

        $this->assertEquals('test-password', $address->plain_password);
    }
}
