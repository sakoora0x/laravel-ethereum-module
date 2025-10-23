<?php

namespace sakoora0x\LaravelEthereumModule\Webhook;

use Illuminate\Support\Facades\Log;
use sakoora0x\LaravelEthereumModule\Models\EthereumDeposit;

class EmptyWebhookHandler implements WebhookHandlerInterface
{
    public function handle(EthereumDeposit $deposit): void
    {
        Log::error('NEW DEPOSIT FOR ADDRESS '.$deposit->address->address.' = '.$deposit->txid);
    }
}