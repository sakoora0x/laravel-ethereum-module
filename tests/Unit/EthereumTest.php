<?php

namespace sakoora0x\LaravelEthereumModule\Tests\Unit;

use sakoora0x\LaravelEthereumModule\Enums\EthereumModel;
use sakoora0x\LaravelEthereumModule\Ethereum;
use sakoora0x\LaravelEthereumModule\Facades\Ethereum as EthereumFacade;
use sakoora0x\LaravelEthereumModule\Models\EthereumExplorer;
use sakoora0x\LaravelEthereumModule\Models\EthereumNode;
use sakoora0x\LaravelEthereumModule\Tests\TestCase;

class EthereumTest extends TestCase
{
    /** @test */
    public function it_can_be_resolved_from_container()
    {
        $ethereum = app(Ethereum::class);

        $this->assertInstanceOf(Ethereum::class, $ethereum);
    }

    /** @test */
    public function it_can_be_accessed_via_facade()
    {
        $this->assertInstanceOf(Ethereum::class, EthereumFacade::getFacadeRoot());
    }

    /** @test */
    public function it_gets_model_class_from_config()
    {
        $ethereum = app(Ethereum::class);

        $nodeModel = $ethereum->getModel(EthereumModel::Node);
        $explorerModel = $ethereum->getModel(EthereumModel::Explorer);
        $tokenModel = $ethereum->getModel(EthereumModel::Token);
        $walletModel = $ethereum->getModel(EthereumModel::Wallet);
        $addressModel = $ethereum->getModel(EthereumModel::Address);
        $transactionModel = $ethereum->getModel(EthereumModel::Transaction);
        $depositModel = $ethereum->getModel(EthereumModel::Deposit);

        $this->assertEquals('sakoora0x\LaravelEthereumModule\Models\EthereumNode', $nodeModel);
        $this->assertEquals('sakoora0x\LaravelEthereumModule\Models\EthereumExplorer', $explorerModel);
        $this->assertEquals('sakoora0x\LaravelEthereumModule\Models\EthereumToken', $tokenModel);
        $this->assertEquals('sakoora0x\LaravelEthereumModule\Models\EthereumWallet', $walletModel);
        $this->assertEquals('sakoora0x\LaravelEthereumModule\Models\EthereumAddress', $addressModel);
        $this->assertEquals('sakoora0x\LaravelEthereumModule\Models\EthereumTransaction', $transactionModel);
        $this->assertEquals('sakoora0x\LaravelEthereumModule\Models\EthereumDeposit', $depositModel);
    }

    /** @test */
    public function it_gets_available_node()
    {
        // Create nodes
        $node1 = new EthereumNode([
            'name' => 'Node 1',
            'base_url' => 'https://node1.example.com',
            'worked' => true,
            'available' => true,
            'requests' => 10,
        ]);
        $node1->save();

        $node2 = new EthereumNode([
            'name' => 'Node 2',
            'base_url' => 'https://node2.example.com',
            'worked' => true,
            'available' => true,
            'requests' => 5,
        ]);
        $node2->save();

        $ethereum = app(Ethereum::class);
        $node = $ethereum->getNode();

        $this->assertInstanceOf(EthereumNode::class, $node);
        // Should return node with lowest requests
        $this->assertEquals('https://node2.example.com', $node->base_url);
    }

    /** @test */
    public function it_only_gets_worked_and_available_nodes()
    {
        // Create unavailable node
        $node1 = new EthereumNode([
            'name' => 'Unavailable Node',
            'base_url' => 'https://node1.example.com',
            'worked' => true,
            'available' => false,
            'requests' => 1,
        ]);
        $node1->save();

        // Create not worked node
        $node2 = new EthereumNode([
            'name' => 'Not Worked Node',
            'base_url' => 'https://node2.example.com',
            'worked' => false,
            'available' => true,
            'requests' => 1,
        ]);
        $node2->save();

        // Create good node
        $node3 = new EthereumNode([
            'name' => 'Good Node',
            'base_url' => 'https://node3.example.com',
            'worked' => true,
            'available' => true,
            'requests' => 10,
        ]);
        $node3->save();

        $ethereum = app(Ethereum::class);
        $node = $ethereum->getNode();

        $this->assertInstanceOf(EthereumNode::class, $node);
        $this->assertEquals('https://node3.example.com', $node->base_url);
    }

    /** @test */
    public function it_throws_exception_when_no_node_available()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $ethereum = app(Ethereum::class);
        $ethereum->getNode();
    }

    /** @test */
    public function it_gets_available_explorer()
    {
        // Create explorers
        $explorer1 = new EthereumExplorer([
            'name' => 'Explorer 1',
            'base_url' => 'https://explorer1.example.com',
            'worked' => true,
            'available' => true,
            'requests' => 10,
        ]);
        $explorer1->save();

        $explorer2 = new EthereumExplorer([
            'name' => 'Explorer 2',
            'base_url' => 'https://explorer2.example.com',
            'worked' => true,
            'available' => true,
            'requests' => 5,
        ]);
        $explorer2->save();

        $ethereum = app(Ethereum::class);
        $explorer = $ethereum->getExplorer();

        $this->assertInstanceOf(EthereumExplorer::class, $explorer);
        // Should return explorer with lowest requests
        $this->assertEquals('https://explorer2.example.com', $explorer->base_url);
    }

    /** @test */
    public function it_only_gets_worked_and_available_explorers()
    {
        // Create unavailable explorer
        $explorer1 = new EthereumExplorer([
            'name' => 'Unavailable Explorer',
            'base_url' => 'https://explorer1.example.com',
            'worked' => true,
            'available' => false,
            'requests' => 1,
        ]);
        $explorer1->save();

        // Create not worked explorer
        $explorer2 = new EthereumExplorer([
            'name' => 'Not Worked Explorer',
            'base_url' => 'https://explorer2.example.com',
            'worked' => false,
            'available' => true,
            'requests' => 1,
        ]);
        $explorer2->save();

        // Create good explorer
        $explorer3 = new EthereumExplorer([
            'name' => 'Good Explorer',
            'base_url' => 'https://explorer3.example.com',
            'worked' => true,
            'available' => true,
            'requests' => 10,
        ]);
        $explorer3->save();

        $ethereum = app(Ethereum::class);
        $explorer = $ethereum->getExplorer();

        $this->assertInstanceOf(EthereumExplorer::class, $explorer);
        $this->assertEquals('https://explorer3.example.com', $explorer->base_url);
    }

    /** @test */
    public function it_throws_exception_when_no_explorer_available()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $ethereum = app(Ethereum::class);
        $ethereum->getExplorer();
    }

    /** @test */
    public function it_uses_mnemonic_trait()
    {
        $this->assertTrue(method_exists(Ethereum::class, 'mnemonicGenerate'));
        $this->assertTrue(method_exists(Ethereum::class, 'mnemonicValidate'));
        $this->assertTrue(method_exists(Ethereum::class, 'mnemonicSeed'));
    }

    /** @test */
    public function it_uses_address_trait()
    {
        $this->assertTrue(method_exists(Ethereum::class, 'createAddress'));
        $this->assertTrue(method_exists(Ethereum::class, 'newAddress'));
        $this->assertTrue(method_exists(Ethereum::class, 'importAddress'));
        $this->assertTrue(method_exists(Ethereum::class, 'validateAddress'));
        $this->assertTrue(method_exists(Ethereum::class, 'toChecksumAddress'));
        $this->assertTrue(method_exists(Ethereum::class, 'privateKeyToAddress'));
    }

    /** @test */
    public function it_uses_wallet_trait()
    {
        $this->assertTrue(method_exists(Ethereum::class, 'generateWallet'));
        $this->assertTrue(method_exists(Ethereum::class, 'importWallet'));
        $this->assertTrue(method_exists(Ethereum::class, 'newWallet'));
        $this->assertTrue(method_exists(Ethereum::class, 'createWallet'));
    }

    /** @test */
    public function it_uses_node_trait()
    {
        $this->assertTrue(trait_exists('sakoora0x\LaravelEthereumModule\Concerns\Node'));
        $this->assertTrue(method_exists(Ethereum::class, 'getNode'));
    }

    /** @test */
    public function it_uses_explorer_trait()
    {
        $this->assertTrue(trait_exists('sakoora0x\LaravelEthereumModule\Concerns\Explorer'));
        $this->assertTrue(method_exists(Ethereum::class, 'getExplorer'));
    }

    /** @test */
    public function it_uses_token_trait()
    {
        $this->assertTrue(trait_exists('sakoora0x\LaravelEthereumModule\Concerns\Token'));
    }

    /** @test */
    public function it_uses_transfer_trait()
    {
        $this->assertTrue(trait_exists('sakoora0x\LaravelEthereumModule\Concerns\Transfer'));
    }
}
