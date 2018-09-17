<?php

namespace TPenaranda\BCoin;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\{ClientException as GuzzleClientException, ServerException as GuzzleServerException}
use Illuminate\Support\Collection;
use TPenaranda\BCoin\Models\{Coin, Server, Transaction, Wallet};
use Cache;

class BCoin
{
    const DEFAULT_MAX_TRANSACTION_FEE_IN_SATOSHI = 150000;
    const DEFAULT_RATE_IN_SATOSHIS_PER_KB = 35000;

    public function __construct()
    {
        if (empty(config('bcoin.api_key')) || empty(config('bcoin.server_ip')) || empty(config('bcoin.server_port'))) {
            throw new BCoinException('Empty server configuration. Check api_key, server_ip and server_port values.');
        };
    }

    protected static function requestToWalletAPI(string $url, array $payload = [], string $request_method = 'GET'): string
    {
        $client = new GuzzleClient([
            'auth' => ['x', config('bcoin.wallet_api_key')],
            'timeout' => config('bcoin.server_timeout_seconds'),
            'body' => empty($payload) ? '{}' : json_encode($payload),
            'verify' => !config('bcoin.accept_self_signed_certificates'),
        ]);

        $protocol = empty(config('bcoin.wallet_server_ssl')) ? 'http' : 'https';

        $server_address = "{$protocol}://" . config('bcoin.wallet_server_ip') . ':' . config('bcoin.wallet_server_port');

        $full_url =  "{$server_address}{$url}?token=" . config('bcoin.wallet_admin_token');

        return $client->request($request_method, $full_url)->getBody()->getContents();
    }

    protected static function requestToServerAPI(string $url, array $payload = [], string $request_method = 'GET'): string
    {
        $client = new GuzzleClient([
            'auth' => ['x', config('bcoin.api_key')],
            'timeout' => config('bcoin.server_timeout_seconds'),
            'body' => empty($payload) ? '{}' : json_encode($payload),
            'verify' => !config('bcoin.accept_self_signed_certificates'),
        ]);

        $protocol = empty(config('bcoin.server_ssl')) ? 'http' : 'https';

        $server_address = "{$protocol}://" . config('bcoin.server_ip') . ':' . config('bcoin.server_port');

        return $client->request($request_method, "{$server_address}{$url}")->getBody()->getContents();
    }

    public static function getFromServerAPI(string $url, int $cache_minutes = 10, bool $refresh_cache = false): string
    {
        return static::requestToServerAPI($url, [], 'GET', $cache_minutes, $refresh_cache);
    }

    public static function putOnServerAPI(string $url, array $payload = []): string
    {
        return static::requestToServerAPI($url, $payload, 'PUT');
    }

    public static function postToServerAPI(string $url, array $payload = []): string
    {
        return static::requestToServerAPI($url, $payload, 'POST');
    }

    public static function getFromWalletAPI(string $url, int $cache_minutes = 10, bool $refresh_cache = false): string
    {
        return static::requestToWalletAPI($url, [], 'GET', $cache_minutes, $refresh_cache);
    }

    public static function putOnWalletAPI(string $url, array $payload = []): string
    {
        return static::requestToWalletAPI($url, $payload, 'PUT');
    }

    public static function postToWalletAPI(string $url, array $payload = []): string
    {
        return static::requestToWalletAPI($url, $payload, 'POST');
    }

    public static function deleteFromWalletAPI(string $url, array $payload = []): string
    {
        return static::requestToWalletAPI($url, $payload, 'DELETE');
    }

    public function getServer(): Server
    {
        return new Server();
    }

    public function getWallet(string $wallet_id = 'primary'): Wallet
    {
        return new Wallet(['id' => $wallet_id]);
    }

    public function getWalletFromCache(string $wallet_id = 'primary'): Wallet
    {
        if (!empty($wallet_cached = Cache::get(Wallet::BASE_CACHE_KEY_NAME . $wallet_id))) {
            return $wallet_cached;
        }

        return new Wallet(['id' => $wallet_id]);
    }

    public static function getWalletsIDs(): Collection
    {
        return collect(json_decode(static::getFromWalletAPI('/wallet')));
    }

    public static function getTransaction(string $transaction_hash, string $wallet_id = null): Transaction
    {
        return new Transaction(['hash' => $transaction_hash, 'wallet_id' => $wallet_id]);
    }

    public static function getTransactionByAddress(string $transaction_address): Transaction
    {
        return new Transaction(static::getFromServerAPI("/tx/address/{$transaction_address}"));
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
        $path = "{$destination_folder}walletdb-backup-" . now()->format('YmdHis') . '.ldb';

        return json_decode(static::postToWalletAPI("/backup?path={$path}"));
    }

    public function createWallet(string $wallet_id, array $opts = ['witness' => true])
    {
        return new Wallet(static::putOnWalletAPI("/wallet/{$wallet_id}", $opts));
    }

    public static function getWalletTransactionsHistory(string $wallet_id): Collection
    {
        $transactions = collect();

        foreach (json_decode(static::getFromWalletAPI("/wallet/{$wallet_id}/tx/history")) ?? [] as $transaction_data) {
            $transactions->push(new Transaction($transaction_data));
        }

        return $transactions;
    }

    public static function getWalletCoins(string $wallet_id = 'primary')
    {
        $coins = collect();

        foreach (json_decode(static::getFromWalletAPI("/wallet/{$wallet_id}/coin")) ?? [] as $coin_data) {
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
                return (bool) static::getFromWalletAPI("/wallet/{$wallet_id}/key/{$address}");
            } catch (GuzzleClientException $e) {
                if (404 != $e->getCode()) {
                    throw $e;
                }
            }

            return false;
        });
    }

    public static function getWalletPendingTransactions(string $wallet_id): Collection
    {
        $transactions = collect();

        foreach (json_decode(static::getFromWalletAPI("/wallet/{$wallet_id}/tx/unconfirmed")) ?? [] as $transaction_data) {
            $transactions->push(new Transaction($transaction_data));
        }

        return $transactions;
    }

    public function getMempool(): Collection
    {
        return collect(json_decode(static::getFromServerAPI('/mempool')) ?? []);
    }

    public static function broadcastTransaction(string $transaction_tx): bool
    {
        return json_decode(static::postToServerAPI('/broadcast', ['tx' => $transaction_tx]));
    }

    public function broadcastAll(): bool
    {
        return json_decode(static::postToWalletAPI('/resend'));
    }

    public function zapWalletTransaction(string $wallet_id, string $transaction_hash): bool
    {
        try {
            return json_decode(static::deleteFromWalletAPI("/wallet/{$wallet_id}/tx/{$transaction_hash}"));
        } catch (GuzzleServerException $e) {
            return json_decode($e->getResponse()->getBody()->getContents());
        }
    }

    public function zapWalletTransactions(string $wallet_id, int $seconds = 259200): bool
    {
        return json_decode(static::postToWalletAPI("/wallet/{$wallet_id}/zap", ['age' => $seconds]));
    }
}
