<?php

namespace sakoora0x\LaravelEthereumModule\Tests\Feature;

use sakoora0x\LaravelEthereumModule\Facades\Ethereum;
use sakoora0x\LaravelEthereumModule\Models\EthereumAddress;
use sakoora0x\LaravelEthereumModule\Models\EthereumWallet;
use sakoora0x\LaravelEthereumModule\Tests\TestCase;

class WalletIntegrationTest extends TestCase
{
    /** @test */
    public function it_creates_complete_wallet_with_multiple_addresses()
    {
        // Create a wallet with all features
        $wallet = Ethereum::createWallet(
            name: 'Integration Test Wallet',
            password: 'secure-password-123',
            passphrase: 'extra-security-phrase'
        );

        // Verify wallet was created and saved
        $this->assertInstanceOf(EthereumWallet::class, $wallet);
        $this->assertTrue($wallet->exists);
        $this->assertEquals('Integration Test Wallet', $wallet->name);

        // Verify wallet has encrypted data
        $this->assertTrue($wallet->has_password);
        $this->assertTrue($wallet->has_mnemonic);
        $this->assertTrue($wallet->has_seed);

        // Verify primary address was created
        $this->assertEquals(1, $wallet->addresses()->count());
        $primaryAddress = $wallet->addresses()->first();
        $this->assertEquals('Primary Address', $primaryAddress->title);
        $this->assertEquals(0, $primaryAddress->index);

        // Create additional addresses
        $address2 = Ethereum::createAddress($wallet, 'Second Address');
        $address3 = Ethereum::createAddress($wallet, 'Third Address');

        // Verify addresses are unique and incremental
        $this->assertEquals(3, $wallet->addresses()->count());
        $this->assertEquals(1, $address2->index);
        $this->assertEquals(2, $address3->index);

        // Verify all addresses are unique
        $addresses = $wallet->addresses()->pluck('address')->toArray();
        $this->assertCount(3, array_unique($addresses));

        // Verify all addresses are valid Ethereum addresses
        foreach ($addresses as $address) {
            $this->assertTrue(Ethereum::validateAddress($address));
        }
    }

    /** @test */
    public function it_imports_wallet_and_recovers_addresses()
    {
        // Create original wallet
        $originalWallet = Ethereum::createWallet('Original Wallet');
        $originalMnemonic = $originalWallet->mnemonic;

        // Create multiple addresses
        Ethereum::createAddress($originalWallet, 'Address 1', 1);
        Ethereum::createAddress($originalWallet, 'Address 2', 2);

        // Store original addresses
        $originalAddresses = $originalWallet->addresses()
            ->orderBy('index')
            ->pluck('address', 'index')
            ->toArray();

        // Import wallet using same mnemonic
        $importedWallet = Ethereum::importWallet(
            name: 'Imported Wallet',
            mnemonic: $originalMnemonic
        );
        $importedWallet->save();

        // Recreate addresses with same indexes
        foreach ($originalAddresses as $index => $originalAddress) {
            $recreatedAddress = Ethereum::createAddress($importedWallet, "Address $index", $index);

            // Verify addresses match
            $this->assertEquals($originalAddress, $recreatedAddress->address);
        }
    }

    /** @test */
    public function it_handles_wallet_with_watch_only_addresses()
    {
        // Create wallet
        $wallet = Ethereum::createWallet('Mixed Wallet');

        // Get the primary address (HD address)
        $hdAddress = $wallet->addresses()->first();

        // Import a watch-only address
        $watchOnlyAddress = Ethereum::importAddress(
            $wallet,
            '0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed'
        );

        // Verify we have both types
        $this->assertEquals(2, $wallet->addresses()->count());

        // Verify HD address has private key
        $this->assertNotNull($hdAddress->private_key);
        $this->assertFalse($hdAddress->watch_only ?? false);

        // Verify watch-only address has no private key
        $this->assertNull($watchOnlyAddress->private_key);
        $this->assertTrue($watchOnlyAddress->watch_only);
    }

    /** @test */
    public function it_maintains_address_validation_throughout_lifecycle()
    {
        // Create wallet and address
        $wallet = Ethereum::createWallet('Validation Test Wallet');
        $address = Ethereum::createAddress($wallet, 'Test Address', 5);

        // Verify address is valid throughout its lifecycle
        $this->assertTrue(Ethereum::validateAddress($address->address));

        // Reload from database
        $reloadedAddress = EthereumAddress::find($address->id);
        $this->assertTrue(Ethereum::validateAddress($reloadedAddress->address));

        // Verify address maintains proper checksum
        $checksumAddress = Ethereum::toChecksumAddress($address->address);
        $this->assertEquals($address->address, $checksumAddress);
    }

    /** @test */
    public function it_generates_deterministic_addresses_across_sessions()
    {
        // First session: Create wallet and addresses
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';

        $wallet1 = Ethereum::importWallet('Session 1', $mnemonic);
        $wallet1->save();

        $address1_0 = Ethereum::createAddress($wallet1, 'Address 0', 0);
        $address1_1 = Ethereum::createAddress($wallet1, 'Address 1', 1);
        $address1_2 = Ethereum::createAddress($wallet1, 'Address 2', 2);

        // Second session: Import same wallet
        $wallet2 = Ethereum::importWallet('Session 2', $mnemonic);
        $wallet2->save();

        $address2_0 = Ethereum::createAddress($wallet2, 'Address 0', 0);
        $address2_1 = Ethereum::createAddress($wallet2, 'Address 1', 1);
        $address2_2 = Ethereum::createAddress($wallet2, 'Address 2', 2);

        // Verify deterministic generation
        $this->assertEquals($address1_0->address, $address2_0->address);
        $this->assertEquals($address1_1->address, $address2_1->address);
        $this->assertEquals($address1_2->address, $address2_2->address);

        // Verify private keys also match (when decrypted)
        $this->assertEquals($address1_0->private_key, $address2_0->private_key);
        $this->assertEquals($address1_1->private_key, $address2_1->private_key);
        $this->assertEquals($address1_2->private_key, $address2_2->private_key);
    }

    /** @test */
    public function it_properly_encrypts_and_decrypts_wallet_data()
    {
        $password = 'super-secure-password';
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';

        // Create wallet with encrypted data
        $wallet = Ethereum::importWallet(
            name: 'Encrypted Wallet',
            mnemonic: $mnemonic,
            password: $password
        );
        $wallet->save();

        // Get the database record directly to verify encryption
        $rawData = \DB::table('ethereum_wallets')
            ->where('id', $wallet->id)
            ->first();

        // Verify data is encrypted in database (not plain text)
        $this->assertNotEquals($password, $rawData->password);
        $this->assertNotEquals($mnemonic, $rawData->mnemonic);

        // Verify data is properly decrypted when accessed through model
        $reloadedWallet = EthereumWallet::find($wallet->id);
        $this->assertEquals($password, $reloadedWallet->password);
        $this->assertEquals($mnemonic, $reloadedWallet->mnemonic);
    }

    /** @test */
    public function it_converts_private_key_to_valid_address()
    {
        // Generate a wallet and get an address with private key
        $wallet = Ethereum::createWallet('Private Key Test');
        $address = $wallet->addresses()->first();

        // Get the private key
        $privateKey = $address->private_key;
        $originalAddress = $address->address;

        // Convert private key back to address
        $derivedAddress = Ethereum::privateKeyToAddress($privateKey);

        // Verify they match
        $this->assertEquals($originalAddress, $derivedAddress);
        $this->assertTrue(Ethereum::validateAddress($derivedAddress));
    }

    /** @test */
    public function it_handles_mnemonic_with_different_word_counts()
    {
        // Test 12-word mnemonic
        $wallet12 = Ethereum::createWallet('12-word wallet', mnemonic: 12);
        $this->assertCount(12, explode(' ', $wallet12->mnemonic));
        $this->assertTrue(Ethereum::mnemonicValidate($wallet12->mnemonic));

        // Test 15-word mnemonic
        $wallet15 = Ethereum::createWallet('15-word wallet', mnemonic: 15);
        $this->assertCount(15, explode(' ', $wallet15->mnemonic));
        $this->assertTrue(Ethereum::mnemonicValidate($wallet15->mnemonic));

        // Test 18-word mnemonic
        $wallet18 = Ethereum::createWallet('18-word wallet', mnemonic: 18);
        $this->assertCount(18, explode(' ', $wallet18->mnemonic));
        $this->assertTrue(Ethereum::mnemonicValidate($wallet18->mnemonic));

        // Verify all wallets can generate valid addresses
        foreach ([$wallet12, $wallet15, $wallet18] as $wallet) {
            $address = $wallet->addresses()->first();
            $this->assertTrue(Ethereum::validateAddress($address->address));
        }
    }
}
