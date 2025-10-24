<?php

namespace sakoora0x\LaravelEthereumModule\Services\Sync;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use sakoora0x\LaravelEthereumModule\Api\Explorer\DTO\TokenTransactionDTO;
use sakoora0x\LaravelEthereumModule\Api\Explorer\DTO\TransactionDTO;
use sakoora0x\LaravelEthereumModule\Api\Explorer\ExplorerApi;
use sakoora0x\LaravelEthereumModule\Api\Node\NodeApi;
use sakoora0x\LaravelEthereumModule\Enums\EthereumModel;
use sakoora0x\LaravelEthereumModule\Enums\TransactionType;
use sakoora0x\LaravelEthereumModule\Facades\Ethereum;
use sakoora0x\LaravelEthereumModule\Models\EthereumAddress;
use sakoora0x\LaravelEthereumModule\Models\EthereumDeposit;
use sakoora0x\LaravelEthereumModule\Models\EthereumExplorer;
use sakoora0x\LaravelEthereumModule\Models\EthereumNode;
use sakoora0x\LaravelEthereumModule\Models\EthereumToken;
use sakoora0x\LaravelEthereumModule\Models\EthereumTransaction;
use sakoora0x\LaravelEthereumModule\Models\EthereumWallet;
use sakoora0x\LaravelEthereumModule\Services\BaseSync;
use sakoora0x\LaravelEthereumModule\Webhook\WebhookHandlerInterface;

class AddressSync extends BaseSync
{
    protected EthereumAddress $address;
    protected EthereumWallet $wallet;
    protected EthereumNode $node;
    protected NodeApi $nodeApi;
    protected EthereumExplorer $explorer;
    protected ExplorerApi $explorerApi;
    /** @var array<string, EthereumToken> */
    protected array $tokens;
    protected bool $force;
    protected ?int $fromBlock;
    protected bool $touchEnabled;
    protected int $touchPeriod;
    protected ?WebhookHandlerInterface $webhookHandler;
    /** @var array<EthereumDeposit> */
    protected array $webhooks = [];
    protected int $blockNumber;

    public function __construct(EthereumAddress $address, bool $force = false, ?int $fromBlock = null)
    {
        $this->address = $address;
        $this->force = $force;
        $this->fromBlock = $fromBlock;

        $this->wallet = $address->wallet;
        $this->node = $this->wallet->node ?? Ethereum::getNode();
        $this->nodeApi = $this->node->api();
        $this->explorer = $this->wallet->explorer ?? Ethereum::getExplorer();
        $this->explorerApi = $this->explorer->api();

        /** @var class-string<EthereumToken> $model */
        $model = Ethereum::getModel(EthereumModel::Token);
        $this->tokens = $model::query()
            ->get()
            ->keyBy(fn(EthereumToken $item) => Str::lower($item->address))
            ->all();

        $this->touchEnabled = (bool)config('ethereum.touch.enabled', false);
        $this->touchPeriod = (int)config('ethereum.touch.waiting_seconds', 3600);

        $webhookHandler = config('ethereum.webhook_handler');
        $this->webhookHandler = $webhookHandler ? App::make($webhookHandler) : null;

        $this->blockNumber = $this->node->getLatestBlockNumber();
    }

    public function run(): void
    {
        parent::run();

        if( !$this->address->available ) {
            $this->log('No synchronization required, the address has not been available!', 'success');
            return;
        }

        if (
            $this->touchEnabled &&
            !$this->force &&
            $this->address->touch_at &&
            $this->address->touch_at > Date::now()->subSeconds($this->touchPeriod)
        ) {
            $this->log('No synchronization required, the address has not been touched!', 'success');
            return;
        }

        $this
            ->balance()
            ->tokenBalances()
            ->transactions()
            ->runWebhooks();
    }

    protected function balance(): static
    {
        $this->log('Starting ETH Balance of address *'.$this->address->address.'* ...');
        $balance = $this->node->getBalance($this->address);
        $this->log('Finished ETH Balance of address *'.$this->address->address.'*: '.$balance->__toString());

        $this->address->update([
            'balance' => $balance,
            'touch_at' => $this->address->touch_at ?: Date::now(),
            'sync_at' => Date::now(),
        ]);

        return $this;
    }

    protected function tokenBalances(): static
    {
        $tokensBalances = [];

        foreach ($this->tokens as $token) {
            $this->log('Get ERC-20 Balance from contract *'.$token->address.'* started...');
            $balance = $this->node->getBalanceOfToken($this->address, $token);
            $this->log(
                'Get ERC-20 Balance from contract *'.$token->address.'* finished: '.$balance->__toString(),
                'success'
            );

            $tokensBalances[$token->address] = $balance->__toString();
        }

        $this->address->update([
            'tokens' => $tokensBalances,
            'sync_at' => Date::now(),
        ]);

        return $this;
    }

    protected function transactions(): static
    {
        // Use fromBlock if provided, otherwise use sync_block_number, default to 0
        $startBlock = $this->fromBlock ?? $this->address->sync_block_number ?? 0;

        $this->log('Starting sync ETH transactions from block '.$startBlock.'...');
        $this->log('Fetching from explorer: '.$this->explorer->name.' for address: '.$this->address->address);

        try {
            $paginator = $this->explorerApi->getTransactionsPaginator(
                address: $this->address->address,
                startBlock: $startBlock,
                perPage: 100,
                callback: fn() => $this->explorer->increment('requests')
            );

            $count = 0;
            /** @var TransactionDTO $item */
            foreach ($paginator as $item) {
                $this->log('Found transaction: '.$item->hash());
                $this->handleTransaction($item);
                $count++;
            }

            $this->log('Processed '.$count.' ETH transactions', 'success');
        } catch (\Exception $e) {
            $this->log('Error fetching transactions: '.$e->getMessage(), 'error');
        }

        $this->address->update([
            'sync_at' => Date::now(),
        ]);

        $this->log('Starting sync token transactions from block '.$startBlock.'...');

        $paginator = $this->explorerApi->getTokenTransactionsPaginator(
            address: $this->address->address,
            contract: null,
            startBlock: $startBlock,
            perPage: 100,
            callback: fn() => $this->explorer->increment('requests')
        );

        $count = 0;
        /** @var TokenTransactionDTO $item */
        foreach ($paginator as $item) {
            $this->handleTokenTransaction($item);
            $count++;
        }

        $this->log('Processed '.$count.' token transactions', 'success');

        $this->address->update([
            'sync_at' => Date::now(),
            'sync_block_number' => $this->blockNumber,
        ]);

        return $this;
    }

    protected function handleTransaction(TransactionDTO $transaction): void
    {
        $type = $transaction->to() === Str::lower($this->address->address) ?
            TransactionType::INCOMING : TransactionType::OUTGOING;

        if( $transaction->contractAddress() || $transaction->isError() ) {
            return;
        }

        EthereumTransaction::updateOrCreate([
            'txid' => $transaction->hash(),
            'address' => $this->address->address,
        ], [
            'type' => $type,
            'time_at' => $transaction->time(),
            'from' => $transaction->from(),
            'to' => $transaction->to(),
            'amount' => $transaction->amount(),
            'token_address' => null,
            'block_number' => $transaction->blockNumber(),
            'data' => $transaction->toArray(),
        ]);

        if( $type === TransactionType::INCOMING ) {
            $deposit = $this->address
                ->deposits()
                ->updateOrCreate([
                    'address_id' => $this->address->id,
                    'txid' => $transaction->hash(),
                ], [
                    'wallet_id' => $this->address->wallet_id,
                    'amount' => $transaction->amount(),
                    'block_number' => $transaction->blockNumber(),
                    'confirmations' => $this->blockNumber > $transaction->blockNumber() ? $this->blockNumber - $transaction->blockNumber() : 0,
                    'time_at' => $transaction->time(),
                ]);

            if ($deposit->wasRecentlyCreated) {
                $deposit->setRelation('wallet', $this->wallet);
                $deposit->setRelation('address', $this->address);

                $this->webhooks[] = $deposit;
            }
        }
    }

    protected function handleTokenTransaction(TokenTransactionDTO $transaction): void
    {
        $token = $this->tokens[$transaction->contractAddress()] ?? null;
        if( !$token ) {
            return;
        }

        $type = $transaction->to() === Str::lower($this->address->address) ?
            TransactionType::INCOMING : TransactionType::OUTGOING;

         EthereumTransaction::updateOrCreate([
            'txid' => $transaction->hash(),
            'address' => $this->address->address,
        ], [
            'type' => $type,
            'time_at' => $transaction->time(),
            'from' => $transaction->from(),
            'to' => $transaction->to(),
            'amount' => $transaction->amount(),
            'token_address' => $transaction->contractAddress(),
            'block_number' => $transaction->blockNumber(),
            'data' => $transaction->toArray(),
        ]);

        if( $type == TransactionType::INCOMING ) {
            /** @var EthereumDeposit $deposit */
            $deposit = $this->address
                ->deposits()
                ->updateOrCreate([
                    'address_id' => $this->address->id,
                    'txid' => $transaction->hash(),
                ], [
                    'wallet_id' => $this->address->wallet_id,
                    'token_id' => $token->id,
                    'amount' => $transaction->amount(),
                    'block_number' => $transaction->blockNumber(),
                    'confirmations' => $transaction->confirmations(),
                    'time_at' => $transaction->time(),
                ]);

            if ($deposit->wasRecentlyCreated) {
                $deposit->setRelation('wallet', $this->wallet);
                $deposit->setRelation('address', $this->address);
                $deposit->setRelation('token', $token);

                $this->webhooks[] = $deposit;
            }
        }
    }

    protected function runWebhooks(): static
    {
        if( $this->webhookHandler ) {
            foreach( $this->webhooks as $item ) {
                try {
                    $this->log('Call Webhook Handler for Deposit #'.$item->id.'...');
                    $this->webhookHandler->handle($item);
                    $this->log('Successfully', 'success');
                }
                catch(\Exception $e) {
                    $this->log('Error: '.$e->getMessage(), 'error');
                }
            }
        }

        return $this;
    }
}