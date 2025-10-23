<?php

namespace sakoora0x\LaravelEthereumModule\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use sakoora0x\LaravelEthereumModule\Enums\EthereumModel;
use sakoora0x\LaravelEthereumModule\Facades\Ethereum;
use sakoora0x\LaravelEthereumModule\Models\EthereumNode;
use sakoora0x\LaravelEthereumModule\Services\Sync\NodeSync;

class NodeSyncCommand extends Command
{
    protected $signature = 'ethereum:node-sync {node_id}';

    protected $description = 'Start Ethereum node sync';

    public function handle(): void
    {
        $nodeId = (int)$this->argument('node_id');

        $this->line('-- Starting sync Ethereum node #'.$nodeId.' ...');

        try {
            /** @var class-string<EthereumNode> $model */
            $model = Ethereum::getModel(EthereumModel::Node);
            $node = $model::findOrFail($nodeId);

            $this->line('-- Node: *'.$node->name.'*'.$node->title);

            $service = App::make(NodeSync::class, [
                'node' => $node
            ]);

            $service->setLogger(fn(string $message, ?string $type) => $this->{$type ? ($type === 'success' ? 'info' : $type) : 'line'}($message));

            $service->run();
        } catch (\Exception $e) {
            $this->error('-- Error: '.$e->getMessage());
        }

        $this->line('-- Completed!');
    }
}
