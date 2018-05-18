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
    const DEFAULT_RATE_IN_SATOSHIS_PER_KB = 10000;

    public function __construct()
    {
        if (empty(config('bcoin.api_key')) || empty(config('bcoin.server_ip')) || empty(config('bcoin.server_port'))) {
            throw new BCoinException('Empty server configuration. Check api_key, server_ip and server_port values.');
        };
    }

    protected static function requestToAPI(string $url, array $payload = [], string $request_method = 'GET', int $cache_minutes = 10, bool $refresh_cache = false): string
    {
        $request_function = function () use ($url, $payload, $request_method) {
            $client = new GuzzleClient([
                'auth' => ['x', config('app.bcoin_api_key')],
                'timeout' => 30,
                'body' => empty($payload) ? '{}' : json_encode($payload),
                'verify' => false,
            ]);

            $full_url = 'https://' . config('app.bcoin_server_ip') . ':' . config('app.bcoin_server_port') . $url;

            return $client->request($request_method, $full_url)->getBody()->getContents();
        };

        $cache_key = md5($url . json_encode($payload) . $request_method);

        if (empty($refresh_cache)) {
            $request_content = Cache::remember($cache_key, $cache_minutes, $request_function);
        } else {
            $request_content = $request_function();
            Cache::put($cache_key, $request_content, $cache_minutes);
        }

        return $request_content;
    }

    public static function getFromAPI(string $url, int $cache_minutes = 10, bool $refresh_cache = false): string
    {
        return static::requestToAPI($url, [], 'GET', $cache_minutes, $refresh_cache);
    }

    public static function putOnAPI(string $url, array $payload = []): string
    {
        return static::requestToAPI($url, $payload, 'PUT', $cache_minutes = 0);
    }

    public static function postToAPI(string $url, array $payload = []): string
    {
        return static::requestToAPI($url, $payload, 'POST', $cache_minutes = 0);
    }

    public function getServer()
    {
        return new Server();
    }

    public function getWallet(string $wallet_id = 'primary')
    {
        return new Wallet($wallet_id);
    }

    public function getWalletsIDs()
    {
        return collect(json_decode(static::getFromAPI('/wallet/_admin/wallets')));
    }

    public function getTransaction(string $transaction_hash)
    {
        return new Transaction($transaction_hash);
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

    public function getWalletTransactionsHistory(string $wallet_id)
    {
        $transactions = collect();

        foreach (json_decode(static::getFromAPI("/wallet/{$wallet_id}/tx/history")) ?? [] as $transaction_data) {
            $transactions->push(new Transaction($transaction_data));
        }

        return $transactions;
    }

    public function getWalletCoins(string $wallet_id)
    {
        $coins = collect();

        foreach (json_decode(static::getFromAPI("/wallet/{$wallet_id}/coin")) ?? [] as $coin_data) {
            $coins->push(new Coin($coin_data));
        }

        return $coins;
    }

    public function addressBelongsToWallet(string $wallet_id, string $address): bool
    {
        return Cache::rememberForever("address-{$address}-belongs-to-wallet-{$wallet_id}", function () use ($wallet_id, $address) {
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

    public function broadcastTransaction(string $transaction_tx)
    {
        $response = json_decode(static::postToAPI('/broadcast', ['tx' => $transaction_tx]));

        return !empty($response->success);
    }

    public function broadcastAll()
    {
        $response = static::postToAPI('/wallet/_admin/resend');

        return !empty($response->success);
    }

    public function zapWalletTransactions(string $wallet_id, int $seconds = 900)
    {
        $response = static::postToAPI("/wallet/{$wallet_id}/zap", ['age' => $seconds]);

        return !empty($response->success);
    }
}
