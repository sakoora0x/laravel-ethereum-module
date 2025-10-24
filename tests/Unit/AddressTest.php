<?php

namespace sakoora0x\LaravelEthereumModule\Tests\Unit;

use sakoora0x\LaravelEthereumModule\Facades\Ethereum;
use sakoora0x\LaravelEthereumModule\Models\EthereumWallet;
use sakoora0x\LaravelEthereumModule\Tests\TestCase;

class AddressTest extends TestCase
{
    /** @test */
    public function it_validates_correct_ethereum_address()
    {
        $validAddress = '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed';

        $isValid = Ethereum::validateAddress($validAddress);

        $this->assertTrue($isValid);
    }

    /** @test */
    public function it_validates_lowercase_ethereum_address()
    {
        $validAddress = '0x5aaeb6053f3e94c9b9a09f33669435e7ef1beaed';

        $isValid = Ethereum::validateAddress($validAddress);

        $this->assertTrue($isValid);
    }

    /** @test */
    public function it_rejects_address_without_0x_prefix()
    {
        $invalidAddress = '5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed';

        $isValid = Ethereum::validateAddress($invalidAddress);

        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_rejects_address_with_wrong_length()
    {
        $invalidAddress = '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeA';

        $isValid = Ethereum::validateAddress($invalidAddress);

        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_rejects_address_with_invalid_characters()
    {
        $invalidAddress = '0xZaAeb6053F3E94C9b9A09f33669435E7Ef1BeAed';

        $isValid = Ethereum::validateAddress($invalidAddress);

        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_rejects_address_with_wrong_checksum()
    {
        // This is a valid format but wrong checksum
        $invalidAddress = '0x5AAeb6053F3E94C9b9A09f33669435E7Ef1BeAed';

        $isValid = Ethereum::validateAddress($invalidAddress);

        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_converts_address_to_checksum_format()
    {
        $address = '0x5aaeb6053f3e94c9b9a09f33669435e7ef1beaed';
        $expectedChecksum = '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed';

        $checksumAddress = Ethereum::toChecksumAddress($address);

        $this->assertEquals($expectedChecksum, $checksumAddress);
    }

    /** @test */
    public function it_converts_address_to_checksum_format_without_prefix()
    {
        $address = '5aaeb6053f3e94c9b9a09f33669435e7ef1beaed';
        $expectedChecksum = '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed';

        $checksumAddress = Ethereum::toChecksumAddress($address);

        $this->assertEquals($expectedChecksum, $checksumAddress);
    }

    /** @test */
    public function it_generates_same_checksum_for_different_case_inputs()
    {
        $lowercase = '0x5aaeb6053f3e94c9b9a09f33669435e7ef1beaed';
        $uppercase = '0x5AAEB6053F3E94C9B9A09F33669435E7EF1BEAED';

        $checksum1 = Ethereum::toChecksumAddress($lowercase);
        $checksum2 = Ethereum::toChecksumAddress($uppercase);

        $this->assertEquals($checksum1, $checksum2);
    }

    /** @test */
    public function it_converts_private_key_to_address()
    {
        $privateKey = '4c0883a69102937d6231471b5dbb6204fe512961708279f8c5c1e9f3b5b8f0e4';

        $address = Ethereum::privateKeyToAddress($privateKey);

        $this->assertIsString($address);
        $this->assertStringStartsWith('0x', $address);
        $this->assertEquals(42, strlen($address));
        $this->assertTrue(Ethereum::validateAddress($address));
    }

    /** @test */
    public function it_creates_new_address_from_wallet_with_seed()
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $seed = Ethereum::mnemonicSeed($mnemonic);

        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->seed = $seed;
        $wallet->save();

        $address = Ethereum::newAddress($wallet, 'Test Address', 0, $seed);

        $this->assertInstanceOf(\sakoora0x\LaravelEthereumModule\Models\EthereumAddress::class, $address);
        $this->assertEquals('Test Address', $address->title);
        $this->assertEquals(0, $address->index);
        $this->assertNotNull($address->address);
        $this->assertTrue(Ethereum::validateAddress($address->address));
    }

    /** @test */
    public function it_creates_addresses_with_incremental_index()
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $seed = Ethereum::mnemonicSeed($mnemonic);

        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->seed = $seed;
        $wallet->save();

        $address1 = Ethereum::createAddress($wallet, 'Address 1', 0, $seed);
        $address2 = Ethereum::createAddress($wallet, 'Address 2', null, $seed);

        $this->assertEquals(0, $address1->index);
        $this->assertEquals(1, $address2->index);
        $this->assertNotEquals($address1->address, $address2->address);
    }

    /** @test */
    public function it_throws_exception_when_creating_address_without_seed()
    {
        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->save();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Argument Seed is required.');

        Ethereum::newAddress($wallet, 'Test Address', 0);
    }

    /** @test */
    public function it_imports_watch_only_address()
    {
        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->save();

        $addressString = '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed';

        $address = Ethereum::importAddress($wallet, $addressString);

        $this->assertEquals($addressString, $address->address);
        $this->assertTrue($address->watch_only);
        $this->assertEquals($wallet->id, $address->wallet_id);
    }

    /** @test */
    public function it_generates_different_addresses_for_different_indexes()
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $seed = Ethereum::mnemonicSeed($mnemonic);

        $wallet = new EthereumWallet([
            'name' => 'Test Wallet',
        ]);
        $wallet->seed = $seed;
        $wallet->save();

        $address1 = Ethereum::createAddress($wallet, 'Address 1', 0, $seed);
        $address2 = Ethereum::createAddress($wallet, 'Address 2', 1, $seed);
        $address3 = Ethereum::createAddress($wallet, 'Address 3', 2, $seed);

        $addresses = [$address1->address, $address2->address, $address3->address];
        $uniqueAddresses = array_unique($addresses);

        $this->assertCount(3, $uniqueAddresses);
    }

    /** @test */
    public function it_generates_consistent_address_for_same_seed_and_index()
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $seed = Ethereum::mnemonicSeed($mnemonic);

        $wallet1 = new EthereumWallet(['name' => 'Wallet 1']);
        $wallet1->seed = $seed;
        $wallet1->save();

        $wallet2 = new EthereumWallet(['name' => 'Wallet 2']);
        $wallet2->seed = $seed;
        $wallet2->save();

        $address1 = Ethereum::createAddress($wallet1, 'Address 1', 0, $seed);
        $address2 = Ethereum::createAddress($wallet2, 'Address 2', 0, $seed);

        $this->assertEquals($address1->address, $address2->address);
    }
}
