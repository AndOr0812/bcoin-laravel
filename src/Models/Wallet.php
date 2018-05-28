<?php

namespace TPenaranda\BCoin\Models;

use TPenaranda\BCoin\BCoin;
use TPenaranda\BCoin\BCoinException;

class Wallet extends Model
{
    protected $id;

    public function getDataFromAPI(): string
    {
        return BCoin::getFromAPI("/wallet/{$this->id}");
    }

    public function sendTransaction(string $destination_address, int $amount_in_satoshi, array $opts = [])
    {
        if (!is_numeric($amount_in_satoshi) || $amount_in_satoshi <= 0) {
            throw new BCoinException('Invalid amount.');
        }

        $payload = [
            'outputs' => [
                [
                    'address' => $destination_address,
                    'value' => $amount_in_satoshi,
                ],
            ],
        ];

        $default_opts = [
            'maxFee' => BCoin::DEFAULT_MAX_TRANSACTION_FEE_IN_SATOSHI,
            'rate' => BCoin::DEFAULT_RATE_IN_SATOSHIS_PER_KB,
        ];

        return new Transaction(BCoin::postToAPI("/wallet/{$this->id}/send", array_merge($payload, array_merge($default_opts, $opts))));
    }
}
