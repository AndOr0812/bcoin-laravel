<?php

namespace TPenaranda\BCoin;

use Carbon\Carbon;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use TPenaranda\BCoin\Models\{Coin, Server, Transaction, Wallet};
use Cache;

class BCoin
{
    const DEFAULT_MAX_TRANSACTION_FEE_IN_SATOSHI = 100000;
    const DEFAULT_RATE_IN_SATOSHIS_PER_KB = 30000;

    public function __construct()
    {
        if (empty(config('bcoin.api_key')) || empty(config('bcoin.server_ip')) || empty(config('bcoin.server_port'))) {
            throw new BCoinException('Empty server configuration. Check api_key, server_ip and server_port values.');
        };
    }

    protected static function requestToAPI(string $url, array $payload = [], string $request_method = 'GET'): string
    {
        $client = new GuzzleClient([
            'auth' => ['x', config('app.bcoin_api_key')],
            'timeout' => config('bcoin.server_timeout_seconds'),
            'body' => empty($payload) ? '{}' : json_encode($payload),
            'verify' => !config('bcoin.accept_self_signed_certificates'),
        ]);

        $protocol = empty(config('bcoin.server_ssl')) ? 'http' : 'https';

        $full_url = $protocol . '://' . config('bcoin.server_ip') . ':' . config('bcoin.server_port') . $url;

        return $client->request($request_method, $full_url)->getBody()->getContents();
    }

    public static function getFromAPI(string $url, int $cache_minutes = 10, bool $refresh_cache = false): string
    {
        return static::requestToAPI($url, [], 'GET', $cache_minutes, $refresh_cache);
    }

    public static function putOnAPI(string $url, array $payload = []): string
    {
        return static::requestToAPI($url, $payload, 'PUT');
    }

    public static function postToAPI(string $url, array $payload = []): string
    {
        return static::requestToAPI($url, $payload, 'POST');
    }

    public function getServer()
    {
        return new Server();

    public static function getWallet(string $wallet_id = 'primary')
    {
        return new Wallet(['id' => $wallet_id]);
    }

    public static function getWalletFromCache(string $wallet_id = 'primary')
    {
        if (!empty($wallet_cached = Cache::get(Wallet::BASE_CACHE_KEY_NAME . $wallet_id))) {
            return $wallet_cached;
        }

        return new Wallet(['id' => $wallet_id]);
    }


    public function getWalletsIDs()
    {
        return collect(json_decode(static::getFromAPI('/wallet/_admin/wallets')));
    }

    public static function getTransaction(string $transaction_hash, string $wallet_id = null)
    {
        return new Transaction(['hash' => $transaction_hash, 'wallet_id' => $wallet_id]);
    }


    public static function getTransactionByAddress(string $transaction_address)
    {
        return new Transaction(static::getFromAPI("/tx/address/{$transaction_address}"));
    }

    public function getAllTransactions()
    {
        return static::getWalletsIDs()->transform(function ($wallet_id) {
            return static::getWalletTransactionsHistory($wallet_id);
        })->flatten();
    }

    public function backupWallets(string $destination_folder)
    {
        $destination_folder = str_finish($destination_folder, '/');
        $path = "{$destination_folder}walletdb-backup-" . Carbon::now()->format('YmdHis') . '.ldb';
        $response = json_decode(static::postToAPI("/wallet/_admin/backup?path={$path}"));

        return !empty($response->success);
    }

    public function createWallet(string $wallet_id, array $opts = ['witness' => true])
    {
        return new Wallet(static::putOnAPI("/wallet/{$wallet_id}", $opts));
    }

    public static function getWalletTransactionsHistory(string $wallet_id)
    {
        $transactions = collect();

        foreach (json_decode(static::getFromAPI("/wallet/{$wallet_id}/tx/history")) ?? [] as $transaction_data) {
            $transactions->push(new Transaction($transaction_data));
        }

        return $transactions;
    }

    public static function getWalletCoins(string $wallet_id = 'primary')
    {
        $coins = collect();

        foreach (json_decode(static::getFromAPI("/wallet/{$wallet_id}/coin")) ?? [] as $coin_data) {
            $coins->push(new Coin($coin_data));
        }

        return $coins;
    }

    public function getCoin(string $hash, int $index)
    {
        return new Coin(['hash' => $hash, 'index' => $index]);
    }

    public static function addressBelongsToWallet(string $address, string $wallet_id): bool
    {
        return Cache::rememberForever("tpenaranda-bcoin:address-{$address}-belongs-to-wallet-{$wallet_id}", function () use ($wallet_id, $address) {
            try {
                return (bool) static::getFromAPI("/wallet/{$wallet_id}/key/{$address}");
            } catch (GuzzleClientException $e) {
                if (404 != $e->getCode()) {
                    throw $e;
                }
            }

            return false;
        });
    }

    public function broadcastTransaction(string $transaction_tx): bool
    {
        $response = json_decode(static::postToAPI('/broadcast', ['tx' => $transaction_tx]));

        return !empty($response->success);
    }

    public function broadcastAll(): bool
    {
        $response = static::postToAPI('/wallet/_admin/resend');

        return !empty($response->success);
    }

    public function zapWalletTransactions(string $wallet_id, int $seconds = 900): bool
    {
        $response = static::postToAPI("/wallet/{$wallet_id}/zap", ['age' => $seconds]);

        return !empty($response->success);
    }
}
