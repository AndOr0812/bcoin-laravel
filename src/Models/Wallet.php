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
        return BCoin::getFromWalletAPI("/wallet/{$this->id}");
    }

    public function getCacheKey(): string
    {
        return self::BASE_CACHE_KEY_NAME . $this->id;
    }

    public function deriveNestedAddress(): string
    {
        return BCoin::postToWalletAPI("/wallet/{$this->id}/nested", ['account' => 'default']);
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
            'subtractFee' => true,
        ];

        return new Transaction(BCoin::postToWalletAPI("/wallet/{$this->id}/send", array_merge($payload, array_merge($default_opts, $opts))));
    }

    protected function addCoinsAttribute(): Collection
    {
        return BCoin::getWalletCoins($this->id);
    }

    protected function addAddressAttribute(): string
    {
        return Cache::remember("{$this->getCacheKey()}_address", $minutes = 60, function () {
            return json_decode($this->deriveNestedAddress())->address;
        });
    }

    protected function addConfirmedSatoshiAttribute(): int
    {
        return Cache::remember("{$this->getCacheKey()}_confirmed_satoshi", $minutes = 2, function () {
            $confirmed_satoshi = 0;

            foreach ($this->coins as $coin) {
                $transaction = BCoin::getTransaction($coin->hash, $this->id);
                if ($transaction->confirmations >= config('bcoin.number_of_confirmations_to_consider_transaction_done')) {
                    $confirmed_satoshi += $coin->value;
                }
            }

            return $confirmed_satoshi;
        });
    }
}
