<?php

namespace sakoora0x\LaravelEthereumModule;

use Illuminate\Database\Eloquent\Model;
use sakoora0x\LaravelEthereumModule\Concerns\Address;
use sakoora0x\LaravelEthereumModule\Concerns\Explorer;
use sakoora0x\LaravelEthereumModule\Concerns\Mnemonic;
use sakoora0x\LaravelEthereumModule\Concerns\Node;
use sakoora0x\LaravelEthereumModule\Concerns\Token;
use sakoora0x\LaravelEthereumModule\Concerns\Transfer;
use sakoora0x\LaravelEthereumModule\Concerns\Wallet;
use sakoora0x\LaravelEthereumModule\Enums\EthereumModel;
use sakoora0x\LaravelEthereumModule\Models\EthereumExplorer;
use sakoora0x\LaravelEthereumModule\Models\EthereumNode;

class Ethereum
{
    use Node, Explorer, Token, Mnemonic, Address, Wallet, Transfer;

    /**
     * @param EthereumModel $model
     * @return class-string<Model>
     */
    public function getModel(EthereumModel $model): string
    {
        return config('ethereum.models.'.$model->value);
    }

    public function getNode(): EthereumNode
    {
        return $this->getModel(EthereumModel::Node)::query()
            ->where('worked', '=', true)
            ->where('available', '=', true)
            ->orderBy('requests')
            ->firstOrFail();
    }

    public function getExplorer(): EthereumExplorer
    {
        return $this->getModel(EthereumModel::Explorer)::query()
            ->where('worked', '=', true)
            ->where('available', '=', true)
            ->orderBy('requests')
            ->firstOrFail();
    }
}
