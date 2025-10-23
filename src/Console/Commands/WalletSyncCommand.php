<?php

namespace sakoora0x\LaravelEthereumModule\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use sakoora0x\LaravelEthereumModule\Enums\EthereumModel;
use sakoora0x\LaravelEthereumModule\Facades\Ethereum;
use sakoora0x\LaravelEthereumModule\Models\EthereumAddress;
use sakoora0x\LaravelEthereumModule\Models\EthereumNode;
use sakoora0x\LaravelEthereumModule\Models\EthereumWallet;
use sakoora0x\LaravelEthereumModule\Services\Sync\AddressSync;
use sakoora0x\LaravelEthereumModule\Services\Sync\NodeSync;
use sakoora0x\LaravelEthereumModule\Services\Sync\WalletSync;

class WalletSyncCommand extends Command
{
    protected $signature = 'ethereum:wallet-sync {wallet_id}';

    protected $description = 'Start Ethereum wallet sync';

    public function handle(): void
    {
        $walletId = (int)$this->argument('wallet_id');

        $this->line('-- Starting sync Ethereum wallet #'.$walletId.' ...');

        try {
            /** @var class-string<EthereumWallet> $model */
            $model = Ethereum::getModel(EthereumModel::Wallet);
            $wallet = $model::findOrFail($walletId);

            $this->line('-- Wallet: *'.$wallet->name.'*'.$wallet->title);

            $service = App::make(WalletSync::class, compact('wallet'));

            $service->setLogger(fn(string $message, ?string $type) => $this->{$type ? ($type === 'success' ? 'info' : $type) : 'line'}($message));

            $service->run();
        } catch (\Exception $e) {
            $this->error('-- Error: '.$e->getMessage());
        }

        $this->line('-- Completed!');
    }
}
