<?php

namespace TPenaranda\BCoin\Models;

use TPenaranda\BCoin\BCoin;
use TPenaranda\BCoin\BCoinException;
use Cache;

class Transaction extends Model
{
    const BASE_CACHE_KEY_NAME = 'tpenaranda-bcoin:transaction_hash-';
    protected $hash;

    public function getDataFromAPI(): string
    {
        return empty($this->wallet_id) ? $this->getDataFromBlockchain() : $this->getDataFromWallet();
    }

    public function getDataFromWallet(): string
    {
        return BCoin::getFromWalletAPI("/wallet/{$this->wallet_id}/tx/{$this->hash}");
    }

    public function getDataFromBlockchain(): string
    {
        return BCoin::getFromServerAPI("/tx/{$this->hash}");
    }

    public function getCacheKey()
    {
        return self::BASE_CACHE_KEY_NAME . $this->hash;
    }

    public function broadcast()
    {
        return BCoin::broadcastTransaction($this->hex);
    }

    public function getWallet()
    {
        return BCoin::getWallet($this->wallet_id);
    }

    protected function addAmountTransactedToWalletSatoshiAttribute()
    {
        if (empty($this->wallet_id)) {
            throw new BCoinException("Can't calculate 'amount_transacted_to_wallet_satoshi' attribute without 'wallet_id' attribute set on the Model.");
        }

        return Cache::rememberForever("tpenaranda-bcoin:amount-in-satoshi-transacted-tx_hash:{$this->hash}-wallet_id:{$this->wallet_id}", function () {
            if (empty($this->block)) {
                return null;
            }

            if (empty($this->inputs[0]->coin) || empty($transaction->fee)) {
                $this->hydrate($this->getDataFromBlockchain());
            }

            $total_inputs_own_wallet = $total_outputs_own_wallet = 0;

            foreach ($this->inputs as $input) {
                if (!empty($input->coin->address) && BCoin::addressBelongsToWallet($input->coin->address, $this->wallet_id)) {
                    $total_inputs_own_wallet += $input->coin->value;
                }
            }

            foreach ($this->outputs as $output) {
                if (!empty($output->address) && BCoin::addressBelongsToWallet($output->address, $this->wallet_id)) {
                    $total_outputs_own_wallet += $output->value;
                }
            }

            $amount = $total_outputs_own_wallet - $total_inputs_own_wallet;

            if ($amount < 0) {
                $amount += $this->fee;
            }

            return $amount;
        });
    }
}
