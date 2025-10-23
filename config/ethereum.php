<?php

return [
    /*
     * Touch Synchronization System (TSS) config
     * If there are many addresses in the system, we synchronize only those that have been touched recently.
     * You must update touch_at in EthereumAddress, if you want sync here.
     */
    'touch' => [
        /*
         * Is system enabled?
         */
        'enabled' => false,

        /*
         * The time during which the address is synchronized after touching it (in seconds).
         */
        'waiting_seconds' => 3600,
    ],

    /*
     * Sets the handler to be used when Ethereum Wallet
     * receives a new deposit.
     */
    'webhook_handler' => \sakoora0x\LaravelEthereumModule\Webhook\EmptyWebhookHandler::class,

    /*
     * Set model class for both TronWallet, TronAddress, TronTrc20,
     * to allow more customization.
     *
     * Node model must be or extend `\sakoora0x\LaravelEthereumModule\Models\EthereumNode::class`
     * Explorer model must be or extend `\sakoora0x\LaravelEthereumModule\Models\EthereumExplorer::class`
     * Token model must be or extend `\sakoora0x\LaravelEthereumModule\Models\EthereumToken::class`
     * Wallet model must be or extend `\sakoora0x\LaravelEthereumModule\Models\EthereumWallet:class`
     * Address model must be or extend `\sakoora0x\LaravelEthereumModule\Models\EthereumAddress::class`
     */
    'models' => [
        'node' => \sakoora0x\LaravelEthereumModule\Models\EthereumNode::class,
        'explorer' => \sakoora0x\LaravelEthereumModule\Models\EthereumExplorer::class,
        'token' => \sakoora0x\LaravelEthereumModule\Models\EthereumToken::class,
        'wallet' => \sakoora0x\LaravelEthereumModule\Models\EthereumWallet::class,
        'address' => \sakoora0x\LaravelEthereumModule\Models\EthereumAddress::class,
        'transaction' => \sakoora0x\LaravelEthereumModule\Models\EthereumTransaction::class,
        'deposit' => \sakoora0x\LaravelEthereumModule\Models\EthereumDeposit::class,
    ],
];
