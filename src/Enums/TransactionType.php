<?php

namespace sakoora0x\LaravelEthereumModule\Enums;

enum TransactionType: string
{
    case OUTGOING = 'out';
    case INCOMING = 'in';
}