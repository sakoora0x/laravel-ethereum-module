<?php

namespace sakoora0x\LaravelEthereumModule\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use sakoora0x\LaravelEthereumModule\Enums\EthereumModel;
use sakoora0x\LaravelEthereumModule\Facades\Ethereum;
use sakoora0x\LaravelEthereumModule\Models\EthereumAddress;
use sakoora0x\LaravelEthereumModule\Models\EthereumNode;
use sakoora0x\LaravelEthereumModule\Models\EthereumWallet;
use sakoora0x\LaravelEthereumModule\Services\Sync\AddressSync;
use sakoora0x\LaravelEthereumModule\Services\Sync\EthereumSync;
use sakoora0x\LaravelEthereumModule\Services\Sync\NodeSync;
use sakoora0x\LaravelEthereumModule\Services\Sync\WalletSync;

class EthereumSyncCommand extends Command
{
    protected $signature = 'ethereum:sync {--from-block=0 : Start syncing from this block number (0 for all history)}';

    protected $description = 'Start Ethereum sync';

    public function handle(): void
    {
        Cache::lock('ethereum', 300)->get(function() {
            $this->line('---- Starting sync Ethereum...');

            $fromBlock = (int) $this->option('from-block');

            if ($fromBlock === 0) {
                $this->line('---- Syncing from block 0 (all history)');
            } else {
                $this->line("---- Syncing from block {$fromBlock}");
            }

            try {
                $service = App::make(EthereumSync::class, ['fromBlock' => $fromBlock]);

                $service->setLogger(fn(string $message, ?string $type) => $this->{$type ? ($type === 'success' ? 'info' : $type) : 'line'}($message));

                $service->run();
            } catch (\Exception $e) {
                $this->error('---- Error: '.$e->getMessage());
            }

            $this->line('---- Completed!');
        });
    }
}
