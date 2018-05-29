<?php

namespace TPenaranda\BCoin\Models;

use TPenaranda\BCoin\BCoin;
use TPenaranda\BCoin\BCoinException;
use Illuminate\Support\Collection;
use Cache;

class Wallet extends Model
{
    const BASE_CACHE_KEY_NAME = 'tpenaranda-bcoin:wallet_id-';
    protected $id;

    public function getDataFromAPI(): string
    {
        return BCoin::getFromAPI("/wallet/{$this->id}");
    }

    public function getCacheKey(): string
    {
        return self::BASE_CACHE_KEY_NAME . $this->id;
    }

    public function sendTransaction(string $destination_address, int $amount_in_satoshi, array $opts = []): Transaction
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

    protected function addCoinsAttribute(): Collection
    {
        return BCoin::getWalletCoins($this->id);
    }

    protected function addConfirmedSatoshiAttribute(): int
    {
        $confirmed_satoshi = 0;

        $this->coins->each(function ($coin) use ($confirmed_satoshi) {
            $transaction = BCoin::getTransaction($coin->hash, $this->id);
            if ($transaction->confirmations >= config('bcoin.number_of_confirmations_to_consider_transaction_done')) {
                $confirmed_satoshi += $coin->value;
            }
        });

        return $confirmed_satoshi;
    }
}
