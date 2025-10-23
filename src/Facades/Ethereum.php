<?php

namespace sakoora0x\LaravelEthereumModule\Facades;

use Illuminate\Support\Facades\Facade;

class Ethereum extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \sakoora0x\LaravelEthereumModule\Ethereum::class;
    }
}
