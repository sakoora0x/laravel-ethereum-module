<?php

namespace sakoora0x\LaravelEthereumModule\Tests\Unit;

use sakoora0x\LaravelEthereumModule\Facades\Ethereum;
use sakoora0x\LaravelEthereumModule\Models\EthereumWallet;
use sakoora0x\LaravelEthereumModule\Tests\TestCase;

class WalletTest extends TestCase
{
    /** @test */
    public function it_can_generate_new_wallet()
    {
        $wallet = Ethereum::generateWallet(
            name: 'Test Wallet',
            mnemonicSize: 12,
            password: 'test-password'
        );

        $this->assertInstanceOf(EthereumWallet::class, $wallet);
        $this->assertEquals('Test Wallet', $wallet->name);
        $this->assertNotNull($wallet->mnemonic);
        $this->assertNotNull($wallet->seed);
        $this->assertNotNull($wallet->password);
        $this->assertCount(12, explode(' ', $wallet->mnemonic));
    }

    /** @test */
    public function it_can_generate_wallet_with_default_mnemonic_size()
    {
        $wallet = Ethereum::generateWallet(
            name: 'Test Wallet',
            password: 'test-password'
        );

        $this->assertInstanceOf(EthereumWallet::class, $wallet);
        $this->assertCount(18, explode(' ', $wallet->mnemonic));
    }

    /** @test */
    public function it_can_generate_wallet_without_password()
    {
        $wallet = Ethereum::generateWallet(
            name: 'Test Wallet'
        );

        $this->assertInstanceOf(EthereumWallet::class, $wallet);
        $this->assertEquals('Test Wallet', $wallet->name);
        $this->assertNull($wallet->password);
    }

    /** @test */
    public function it_can_generate_wallet_without_saving_password()
    {
        $wallet = Ethereum::generateWallet(
            name: 'Test Wallet',
            password: 'test-password',
            savePassword: false
        );

        $this->assertInstanceOf(EthereumWallet::class, $wallet);
        $this->assertNull($wallet->password);
    }

    /** @test */
    public function it_can_import_wallet_from_mnemonic_string()
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';

        $wallet = Ethereum::importWallet(
            name: 'Imported Wallet',
            mnemonic: $mnemonic,
            password: 'test-password'
        );

        $this->assertInstanceOf(EthereumWallet::class, $wallet);
        $this->assertEquals('Imported Wallet', $wallet->name);
        $this->assertEquals($mnemonic, $wallet->mnemonic);
        $this->assertNotNull($wallet->seed);
        $this->assertNotNull($wallet->password);
    }

    /** @test */
    public function it_can_import_wallet_from_mnemonic_array()
    {
        $mnemonic = ['abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'about'];

        $wallet = Ethereum::importWallet(
            name: 'Imported Wallet',
            mnemonic: $mnemonic,
            password: 'test-password'
        );

        $this->assertInstanceOf(EthereumWallet::class, $wallet);
        $this->assertEquals('Imported Wallet', $wallet->name);
        $this->assertEquals(implode(' ', $mnemonic), $wallet->mnemonic);
        $this->assertNotNull($wallet->seed);
    }

    /** @test */
    public function it_can_import_wallet_with_passphrase()
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $passphrase = 'test-passphrase';

        $wallet1 = Ethereum::importWallet(
            name: 'Wallet 1',
            mnemonic: $mnemonic,
            passphrase: $passphrase
        );

        $wallet2 = Ethereum::importWallet(
            name: 'Wallet 2',
            mnemonic: $mnemonic
        );

        $this->assertNotEquals($wallet1->seed, $wallet2->seed);
    }

    /** @test */
    public function it_can_create_new_wallet_without_mnemonic()
    {
        $wallet = Ethereum::newWallet(
            name: 'New Wallet',
            password: 'test-password'
        );

        $this->assertInstanceOf(EthereumWallet::class, $wallet);
        $this->assertEquals('New Wallet', $wallet->name);
        $this->assertNull($wallet->mnemonic);
        $this->assertNull($wallet->seed);
        $this->assertNotNull($wallet->password);
    }

    /** @test */
    public function it_can_create_wallet_and_save_it()
    {
        $wallet = Ethereum::createWallet(
            name: 'Created Wallet',
            password: 'test-password'
        );

        $this->assertInstanceOf(EthereumWallet::class, $wallet);
        $this->assertTrue($wallet->exists);
        $this->assertNotNull($wallet->id);
        $this->assertNotNull($wallet->mnemonic);
        $this->assertNotNull($wallet->seed);

        // Should also create primary address
        $this->assertEquals(1, $wallet->addresses()->count());
        $primaryAddress = $wallet->addresses()->first();
        $this->assertEquals('Primary Address', $primaryAddress->title);
        $this->assertEquals(0, $primaryAddress->index);
    }

    /** @test */
    public function it_can_create_wallet_with_custom_mnemonic_string()
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';

        $wallet = Ethereum::createWallet(
            name: 'Created Wallet',
            mnemonic: $mnemonic
        );

        $this->assertInstanceOf(EthereumWallet::class, $wallet);
        $this->assertEquals($mnemonic, $wallet->mnemonic);
        $this->assertTrue($wallet->exists);
    }

    /** @test */
    public function it_can_create_wallet_with_custom_mnemonic_array()
    {
        $mnemonic = ['abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'about'];

        $wallet = Ethereum::createWallet(
            name: 'Created Wallet',
            mnemonic: $mnemonic
        );

        $this->assertInstanceOf(EthereumWallet::class, $wallet);
        $this->assertEquals(implode(' ', $mnemonic), $wallet->mnemonic);
        $this->assertTrue($wallet->exists);
    }

    /** @test */
    public function it_can_create_wallet_with_custom_mnemonic_size()
    {
        $wallet = Ethereum::createWallet(
            name: 'Created Wallet',
            mnemonic: 12
        );

        $this->assertInstanceOf(EthereumWallet::class, $wallet);
        $this->assertCount(12, explode(' ', $wallet->mnemonic));
        $this->assertTrue($wallet->exists);
    }

    /** @test */
    public function it_can_create_wallet_with_passphrase()
    {
        $wallet = Ethereum::createWallet(
            name: 'Created Wallet',
            passphrase: 'test-passphrase'
        );

        $this->assertInstanceOf(EthereumWallet::class, $wallet);
        $this->assertNotNull($wallet->seed);
        $this->assertTrue($wallet->exists);
    }

    /** @test */
    public function it_generates_consistent_seed_from_same_mnemonic()
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';

        $wallet1 = Ethereum::importWallet(
            name: 'Wallet 1',
            mnemonic: $mnemonic
        );

        $wallet2 = Ethereum::importWallet(
            name: 'Wallet 2',
            mnemonic: $mnemonic
        );

        $this->assertEquals($wallet1->seed, $wallet2->seed);
    }

    /** @test */
    public function it_can_unlock_wallet_with_password()
    {
        $password = 'test-password';
        $wallet = Ethereum::generateWallet(
            name: 'Test Wallet',
            password: $password
        );

        $wallet->unlockWallet($password);

        $this->assertEquals($password, $wallet->plain_password);
    }
}
