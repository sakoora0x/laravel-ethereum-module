<?php

namespace sakoora0x\LaravelEthereumModule\Webhook;

use sakoora0x\LaravelEthereumModule\Models\EthereumDeposit;

interface WebhookHandlerInterface
{
    public function handle(EthereumDeposit $deposit): void;
}