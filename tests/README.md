# Laravel Ethereum Module Tests

This directory contains the test suite for the Laravel Ethereum Module package.

## Test Structure

```
tests/
├── TestCase.php                         # Base test case class
└── Unit/
    ├── MnemonicTest.php                 # Tests for mnemonic generation and validation
    ├── AddressTest.php                  # Tests for Ethereum address operations
    ├── WalletTest.php                   # Tests for wallet creation and management
    ├── EthereumWalletModelTest.php      # Tests for EthereumWallet model
    ├── EthereumAddressModelTest.php     # Tests for EthereumAddress model
    ├── EthereumServiceProviderTest.php  # Tests for service provider registration
    └── EthereumTest.php                 # Tests for main Ethereum class
```

## Running Tests

### Install Dependencies

First, make sure you have installed all dependencies including dev dependencies:

```bash
composer install
```

### Run All Tests

```bash
composer test
```

Or directly with PHPUnit:

```bash
vendor/bin/phpunit
```

### Run Specific Test File

```bash
vendor/bin/phpunit tests/Unit/MnemonicTest.php
```

### Run Specific Test Method

```bash
vendor/bin/phpunit --filter it_can_generate_mnemonic_with_default_word_count
```

### Run Tests with Coverage

```bash
composer test-coverage
```

This will generate an HTML coverage report in the `coverage/` directory.

## Test Coverage

The test suite covers:

### Mnemonic Operations
- Mnemonic generation with various word counts (12, 15, 18 words)
- Mnemonic validation (both string and array formats)
- Seed generation from mnemonic
- Passphrase support
- Consistency checks

### Address Operations
- Address validation (checksum, format, length)
- Address generation from private keys
- Checksum conversion
- Hierarchical deterministic (HD) address derivation
- Watch-only address import
- Consistent address generation from same seed/index

### Wallet Operations
- Wallet generation with custom mnemonic sizes
- Wallet import from existing mnemonic
- Password encryption and management
- Passphrase support for additional security
- Wallet creation with automatic address generation
- Multiple wallet creation methods (generate, import, new, create)

### Models
- EthereumWallet model attributes and relationships
- EthereumAddress model attributes and relationships
- Encrypted field handling
- Token balance tracking
- Database relationships (wallet-address, wallet-node, wallet-explorer)

### Service Provider
- Singleton registration
- Configuration loading
- Migration registration
- Command registration
- Facade alias registration

### Ethereum Main Class
- Model retrieval from configuration
- Node selection (worked and available nodes with lowest requests)
- Explorer selection (worked and available explorers with lowest requests)
- Trait integration verification

## Writing New Tests

When adding new features to the package, please add corresponding tests:

1. Create a new test class extending `TestCase`
2. Use descriptive test method names with the `/** @test */` annotation
3. Follow the Arrange-Act-Assert pattern
4. Test both success and failure scenarios
5. Test edge cases

Example:

```php
<?php

namespace sakoora0x\LaravelEthereumModule\Tests\Unit;

use sakoora0x\LaravelEthereumModule\Tests\TestCase;

class YourFeatureTest extends TestCase
{
    /** @test */
    public function it_does_something_expected()
    {
        // Arrange
        $input = 'test';

        // Act
        $result = YourClass::doSomething($input);

        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

## Notes

- Tests use SQLite in-memory database for speed
- Migrations are automatically run before each test
- The test database is reset between test classes
- Encrypted fields are automatically decrypted in tests
- All sensitive data is properly encrypted in the database
