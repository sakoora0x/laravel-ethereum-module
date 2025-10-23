<?php

namespace sakoora0x\LaravelEthereumModule\Services\Sync;

use Illuminate\Support\Facades\Date;
use sakoora0x\LaravelEthereumModule\Models\EthereumExplorer;
use sakoora0x\LaravelEthereumModule\Models\EthereumNode;
use sakoora0x\LaravelEthereumModule\Services\BaseSync;

class ExplorerSync extends BaseSync
{
    protected EthereumExplorer $explorer;

    public function __construct(EthereumExplorer $explorer)
    {
        $this->explorer = $explorer;
    }

    public function run(): void
    {
        parent::run();

        $this
            ->resetRequests()
            ->syncBlock();
    }

    protected function resetRequests(): self
    {
        if( is_null($this->explorer->requests_at) || !$this->explorer->requests_at->isToday() ) {
            $this->explorer->update([
                'requests' => 0,
                'requests_at' => Date::now(),
            ]);

            $this->log('Requests counter successfully reset.');
        }

        return $this;
    }

    protected function syncBlock(): self
    {
        try {
            $api = $this->explorer->api();
            $api->getApiLimit();
        }
        catch(\Exception $e) {
            $this->explorer->update([
                'worked' => false,
            ]);

            throw $e;
        }

        $this->explorer->increment('requests');
        $this->explorer->update([
            'sync_at' => Date::now(),
            'worked' => true,
        ]);

        return $this;
    }
}