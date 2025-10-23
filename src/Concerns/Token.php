<?php

namespace sakoora0x\LaravelEthereumModule\Concerns;

use Illuminate\Support\Str;
use sakoora0x\LaravelEthereumModule\Enums\EthereumModel;
use sakoora0x\LaravelEthereumModule\Facades\Ethereum;
use sakoora0x\LaravelEthereumModule\Models\EthereumNode;
use sakoora0x\LaravelEthereumModule\Models\EthereumToken;

trait Token
{
    public function createToken(string $contract, ?EthereumNode $node = null)
    {
        $contract = Str::lower($contract);

        if( !$node ) {
            $node = Ethereum::getNode();
        }

        $node->increment('requests', 3);

        $api = $node->api();
        $name = $api->getTokenName($contract);
        $symbol = $api->getTokenSymbol($contract);
        $decimals = $api->getTokenDecimals($contract);

        /** @var class-string<EthereumToken> $model */
        $model = Ethereum::getModel(EthereumModel::Token);

        return $model::create([
            'address' => $contract,
            'name' => $name,
            'symbol' => $symbol,
            'decimals' => $decimals,
        ]);
    }
}
