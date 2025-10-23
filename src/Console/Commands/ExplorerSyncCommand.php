<?php

namespace sakoora0x\LaravelEthereumModule\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use sakoora0x\LaravelEthereumModule\Enums\EthereumModel;
use sakoora0x\LaravelEthereumModule\Facades\Ethereum;
use sakoora0x\LaravelEthereumModule\Models\EthereumExplorer;
use sakoora0x\LaravelEthereumModule\Services\Sync\ExplorerSync;

class ExplorerSyncCommand extends Command
{
    protected $signature = 'ethereum:explorer-sync {explorer_id}';

    protected $description = 'Start Ethereum explorer sync';

    public function handle(): void
    {
        $explorerId = (int)$this->argument('explorer_id');

        $this->line('-- Starting sync Ethereum Explorer #'.$explorerId.' ...');

        try {
            /** @var class-string<EthereumExplorer> $model */
            $model = Ethereum::getModel(EthereumModel::Explorer);
            $explorer = $model::findOrFail($explorerId);

            $this->line('-- Explorer: *'.$explorer->name.'*'.$explorer->title);

            $service = App::make(ExplorerSync::class, [
                'explorer' => $explorer
            ]);

            $service->setLogger(fn(string $message, ?string $type) => $this->{$type ? ($type === 'success' ? 'info' : $type) : 'line'}($message));

            $service->run();
        } catch (\Exception $e) {
            $this->error('-- Error: '.$e->getMessage());
        }

        $this->line('-- Completed!');
    }
}
