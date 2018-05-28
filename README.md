# BCoin (bcoin.io) Laravel
A Laravel 5 package to connect with BCoin full node API

## About package
This packages integrates a BCoin (bcoin.io) Bitcoin node to a Laravel App by using BCoin API.
Also, it performs some calculations that BCoin doesn't returns (like amount transcated from/to a Wallet).
Some basic Cache is used to avoid unnecesary API calls. It's a work in progress project but stable.

## Installation for Laravel 5

Install package using [Composer](http://getcomposer.org).

    $ composer require tpenaranda/bcoin-laravel

Publish configuration [TPenaranda\BCoin\BCoinServiceProvider] by running:

    $ php artisan vendor:publish

Set BCoin server configuration (config/bcoin.php):

```
return [
    'accept_self_signed_certificates' => env('BCOIN_ACCEPT_SELF_SIGNED_CERTIFICATES', true),
    'api_key' => env('BCOIN_API_KEY', false),
    'number_of_confirmations_to_consider_transaction_done' => env('NUMBER_OF_CONFIRMATIONS_TO_CONSIDER_TRANSACTION_DONE', 3),
    'server_ip' => env('BCOIN_SERVER_IP', '127.0.0.1'),
    'server_port' => env('BCOIN_SERVER_PORT', 18333),
    'server_ssl' => env('BCOIN_SERVER_SSL', false),
];
```

## Usage

```
<?php

use BCoinNode; // Package Facade

$serverModel = BCoinNode::getServer() // Server Model
$serverModel->version // 'v1.0.0-beta.14' string

$transaction = BCoinNode::getTransaction('a42785c8351d896329dfeab4b95bbc1185e7ffb284f5f80275bd0df3632fccbb') // Transaction Model

$collection = BCoinNode::getWalletTransactionsHistory('primary') // Collection of Transactions Models

$wallet = BCoinNode::createWallet('my_new_wallet') // Wallet Model

$wallet = BCoinNode::getWallet('primary') // Wallet Model

$integer = $wallet->confirmed_satoshi // Get Wallet confirmed balance using 'number_of_confirmations_to_consider_transaction_done' config parameter.

$transaction = $wallet->sendTransaction(<destination_address>, <amount_in_satoshi>, <opts>)

$boolean = BCoinNode::addressBelongsToWallet(<address>, <wallet_id>) // Check if a BTC address belongs to a Node Wallet

*Have a look to TPenaranda\BCoin\BCoin class for (many) undocumented methods.*
```

Donations => bitcoin:38NYkcaqSCxijvsfvgGexPsNkVZLfaTw54
