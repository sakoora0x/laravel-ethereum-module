<?php

namespace sakoora0x\LaravelEthereumModule\Tests\Unit;

use sakoora0x\LaravelEthereumModule\Facades\Ethereum;
use sakoora0x\LaravelEthereumModule\Tests\TestCase;

class MnemonicTest extends TestCase
{
    /** @test */
    public function it_can_generate_mnemonic_with_default_word_count()
    {
        $mnemonic = Ethereum::mnemonicGenerate();

        $this->assertIsArray($mnemonic);
        $this->assertCount(15, $mnemonic);
        $this->assertContainsOnly('string', $mnemonic);
    }

    /** @test */
    public function it_can_generate_mnemonic_with_custom_word_count()
    {
        $wordCount = 12;
        $mnemonic = Ethereum::mnemonicGenerate($wordCount);

        $this->assertIsArray($mnemonic);
        $this->assertCount($wordCount, $mnemonic);
        $this->assertContainsOnly('string', $mnemonic);
    }

    /** @test */
    public function it_can_generate_mnemonic_with_18_words()
    {
        $wordCount = 18;
        $mnemonic = Ethereum::mnemonicGenerate($wordCount);

        $this->assertIsArray($mnemonic);
        $this->assertCount($wordCount, $mnemonic);
    }

    /** @test */
    public function it_can_validate_valid_mnemonic_as_string()
    {
        $validMnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';

        $isValid = Ethereum::mnemonicValidate($validMnemonic);

        $this->assertTrue($isValid);
    }

    /** @test */
    public function it_can_validate_valid_mnemonic_as_array()
    {
        $validMnemonic = ['abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'about'];

        $isValid = Ethereum::mnemonicValidate($validMnemonic);

        $this->assertTrue($isValid);
    }

    /** @test */
    public function it_returns_false_for_invalid_mnemonic()
    {
        $invalidMnemonic = 'invalid words that are not valid mnemonic phrase at all';

        $isValid = Ethereum::mnemonicValidate($invalidMnemonic);

        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_can_generate_seed_from_mnemonic_as_string()
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';

        $seed = Ethereum::mnemonicSeed($mnemonic);

        $this->assertIsString($seed);
        $this->assertNotEmpty($seed);
        // Seed should be hex string (128 characters for 64 bytes)
        $this->assertEquals(128, strlen($seed));
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/i', $seed);
    }

    /** @test */
    public function it_can_generate_seed_from_mnemonic_as_array()
    {
        $mnemonic = ['abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'abandon', 'about'];

        $seed = Ethereum::mnemonicSeed($mnemonic);

        $this->assertIsString($seed);
        $this->assertNotEmpty($seed);
        $this->assertEquals(128, strlen($seed));
    }

    /** @test */
    public function it_can_generate_seed_with_passphrase()
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $passphrase = 'test-passphrase';

        $seedWithPassphrase = Ethereum::mnemonicSeed($mnemonic, $passphrase);
        $seedWithoutPassphrase = Ethereum::mnemonicSeed($mnemonic);

        $this->assertIsString($seedWithPassphrase);
        $this->assertNotEmpty($seedWithPassphrase);
        // Seeds should be different when passphrase is used
        $this->assertNotEquals($seedWithPassphrase, $seedWithoutPassphrase);
    }

    /** @test */
    public function it_generates_consistent_seed_for_same_mnemonic()
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';

        $seed1 = Ethereum::mnemonicSeed($mnemonic);
        $seed2 = Ethereum::mnemonicSeed($mnemonic);

        $this->assertEquals($seed1, $seed2);
    }

    /** @test */
    public function it_validates_generated_mnemonic()
    {
        $mnemonic = Ethereum::mnemonicGenerate(12);

        $isValid = Ethereum::mnemonicValidate($mnemonic);

        $this->assertTrue($isValid);
    }
}
