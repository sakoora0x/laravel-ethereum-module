<?php

namespace sakoora0x\LaravelEthereumModule\Api\Node\DTO;

use Brick\Math\BigDecimal;
use sakoora0x\LaravelEthereumModule\Api\BaseDTO;

class TransferDTO extends PreviewTransferDTO
{
    public function txid(): string
    {
        return $this->getOrFail('txid');
    }
}