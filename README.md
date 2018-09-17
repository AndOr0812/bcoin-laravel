# BCoin (bcoin.io) Laravel

[![Latest Stable Version](https://poser.pugx.org/tpenaranda/bcoin-laravel/v/stable)](https://packagist.org/packages/tpenaranda/bcoin-laravel) [![Total Downloads](https://poser.pugx.org/tpenaranda/bcoin-laravel/downloads)](https://packagist.org/packages/tpenaranda/bcoin-laravel) [![License](https://poser.pugx.org/tpenaranda/bcoin-laravel/license)](https://packagist.org/packages/tpenaranda/bcoin-laravel)

A Laravel 5 package to connect with BCoin full node API.

## About package
This packages integrates a Bitcoin (bcoin.io) node to a Laravel App by using BCoin API.
Also, it performs some calculations that bcoin node doesn't provide.
Some basic Cache is used to avoid unnecesary API calls.

Donations => bitcoin:38NYkcaqSCxijvsfvgGexPsNkVZLfaTw54

## Installation for Laravel 5

Install package using [Composer](http://getcomposer.org).

    $ composer require tpenaranda/bcoin-laravel

Publish configuration [TPenaranda\BCoin\BCoinServiceProvider] by running:

    $ php artisan vendor:publish

Set BCoin server configuration (config/bcoin.php):

```
return [
    'accept_self_signed_certificates' => true,
    'api_key' => 'secret_api_key',
    'number_of_confirmations_to_consider_transaction_done' => 3,
    'server_ip' => '127.0.0.1',
    'server_port' => 8332,
    'server_ssl' => false,
    'server_timeout_seconds' => 20,
    'wallet_admin_token' => 'secret_token',
    'wallet_api_key' => 'secret_wallet_key'
    'wallet_server_ip' => '127.0.0.1',
    'wallet_server_port' => 8334,
    'wallet_server_ssl' => false,
];
```

## Usage

Example of bcoin server fire up (Listen on port 8333, so TCP 8332, 8333 and 8334 should not be firewalled)
```
    $ bcoin --network main --http-host 0.0.0.0 --api-key secret_api_key --index-tx true --index-address true --wallet-http-host 0.0.0.0 --wallet-api-key secret_wallet_key --wallet-wallet-auth true --wallet-network main --wallet-admin-token secret_token
```

### Wallets
```

    Create Wallet

>>> BCoinNode::createWallet('my_wallet', ['witness' => true]);
=> TPenaranda\BCoin\Models\Wallet {#3129
     +"network": "main",
     +"wid": 13201,
     +"watchOnly": false,
     +"accountDepth": 1,
     +"token": "b1cd7d340e397cb62a0484ca16d1dcc71c1406a9b437283280e9b3fdbcb96def",
     +"tokenDepth": 0,
     +"master": {#3138
       +"encrypted": false,
     },
     +"balance": {#3136
       +"tx": 0,
       +"coin": 0,
       +"unconfirmed": 0,
       +"confirmed": 0,
     },
   }
>>>

    Get Wallet

>>> $wallet = BCoinNode::getWallet('my_other_wallet');
=> TPenaranda\BCoin\Models\Wallet {#3126
     +"network": "main",
     +"wid": 13202,
     +"watchOnly": false,
     +"accountDepth": 1,
     +"token": "20e2eb678be2679d6400aa733822b7eab68bca3148511403ba669ded830e7bcc",
     +"tokenDepth": 0,
     +"master": {#3151
       +"encrypted": false,
     },
     +"balance": {#3136
       +"tx": 0,
       +"coin": 0,
       +"unconfirmed": 0,
       +"confirmed": 0,
     },
   }
>>>

    Get Wallet nested address (only when ['witness' => true] opt was used for Wallet creation)

>>> $wallet->address;
=> "37vRA5NsGtnUoCtUtFAQmDWo6ucGi3J23C"
>>> BCoinNode::getWallet('my_other_wallet')->address;
=> "37vRA5NsGtnUoCtUtFAQmDWo6ucGi3J23C"
>>>

    Forget Wallet nested address. (This clears Cache but doesn't update the Wallet model. You needto refresh the
    Model in order to get the new address.)

>>> $wallet->forgetCurrentAddress();
=> true
>>> BCoinNode::getWallet('my_other_wallet')->address;
=> "38EJvoLQmn7xG2iTeZ6ED6jrVLPND7Lo62"
>>>

    Send a Transaction

>>> $wallet->sendTransaction($desination_address, $amount_in_satoshi, $opts = ['maxFee' => 150000, 'rate' => 35000]);
=> TPenaranda\BCoin\Models\Transaction {#3146 ...
...
>>>

    Get Wallet balance taking in consideration 'number_of_confirmations_to_consider_transaction_done' config parameter.

>>> $wallet->confirmed_satoshi;
=> 23432534
>>>

    Get Wallet TXs History

>>> $wallet->transactions;
=> Illuminate\Support\Collection {#3119
     all: [
       TPenaranda\BCoin\Models\Transaction {#32838 …14},
       TPenaranda\BCoin\Models\Transaction {#32845 …14},
        …2290
     ],
   }
>>>

    Get Wallet Pending TXs

>>> $wallet->pending_transactions;
=> Illuminate\Support\Collection {#62399
     all: [],
   }
>>>

    Get current Wallet coins

>>> $wallet->coins;
=> Illuminate\Support\Collection {#30339
     all: [
       TPenaranda\BCoin\Models\Coin {#30354
         +"version": 1,
         +"height": 541429,
         +"value": 46800492,
         +"script": "00144cfaaf0ebacd93af2d7ab118060084c5196f0712",
         +"address": "bc1qfna27r46ekf67tt6kyvqvqyyc5vk7pcjn5syqz",
         +"coinbase": false,
       },
       TPenaranda\BCoin\Models\Coin {#30334
         +"version": 1,
         +"height": 541734,
         +"value": 100900578,
         +"script": "001432b4535995990d59b3f14dbfe02d31866e81b1e9",
         +"address": "bc1qx269xkv4nyx4nvl3fkl7qtf3sehgrv0fkcfvlc",
         +"coinbase": false,
       },
     ],
   }
>>>
```

### Transactions
```

>>> BCoinNode::getTransaction('bdcb1474d12a73ff4d221cd4bd386e916682b4722f7330cfca5e74164016c926')
=> TPenaranda\BCoin\Models\Transaction {#3146
     +"wallet_id": null,
     +"witnessHash": "c193bcfef9b1e885a2d516dc3c75525941371f1045b0fee08891aadaba7d0b35",
     +"fee": 8812,
     +"rate": 62056,
     +"mtime": 1537023576,
     +"height": 541532,
     +"block": "00000000000000000015cf6631ef906c3d0456d218e38ae0f7d1722340e24ee5",
     +"time": 1537023574,
     +"index": 97,
     +"version": 1,
     +"inputs": [
       {#3150
         +"prevout": {#3140
           +"hash": "8a65024ec8f5175b47a62b4734718f7353c5fe427c02942a71ca1645551ef09f",
           +"index": 1,
         },
         +"script": "",
         +"witness": "02483045022100f2e29354bfa8b4...596b85b084466c82446f902fdd1f9fdcfe221bbc58132a",
         +"sequence": 4294967295,
         +"coin": {#3158
           +"version": 1,
           +"height": 541410,
           +"value": 225043512,
           +"script": "00148e2132327fff960c3cd525e6caebe30477e1f55c",
           +"address": "bc1q3csnyvnll7tqc0x4yhnv46lrq3m7ra2u33z5sw",
           +"coinbase": false,
         },
       },
     ],
     +"outputs": [
       {#3129
         +"value": 3169650,
         +"script": "a914946a64eaac4551014e5941e5a337586807dda4e787",
         +"address": "3FDmPLJjUW4B5wMhRiq592wM4up6jJMYmt",
       },
       {#3125
         +"value": 221865050,
         +"script": "0014552868a890cfa468cee7a32312959846e79ad66d",
         +"address": "bc1q255x32yse7jx3nh85v3399vcgmne44nds93xm6",
       },
     ],
     +"locktime": 0,
     +"hex": "010000000001019ff01e554516ca712a94027c...b42e0e23849f7f596b8f902fdd1f9fdcfe221bbc58132a00000000",
     +"confirmations": 216,
   }
>>>
```

### Server
```
>>> BCoinNode::getServer();
=> TPenaranda\BCoin\Models\Server {#3121
     +"version": "v1.0.2",
     +"network": "main",
     +"chain": {#3141
       +"height": 541747,
       +"tip": "0000000000000000000ecc81c57348db340f988d786e40ad16d314ac17a8e854",
       +"progress": 1,
     },
     +"pool": {#3142
       +"host": "164.93.162.95",
       +"port": 8333,
       +"agent": "/bcoin:v1.0.2/",
       +"services": "1001",
       +"outbound": 8,
       +"inbound": 8,
     },
     +"mempool": {#3143
       +"tx": 988,
       +"size": 5732464,
     },
     +"time": {#3123
       +"uptime": 624562,
       +"system": 1537155653,
       +"adjusted": 1537155653,
       +"offset": 0,
     },
     +"memory": {#3131
       +"total": 2656,
       +"jsHeap": 38,
       +"jsHeapTotal": 44,
       +"nativeHeap": 2611,
       +"external": 24,
     },
   }
>>>

    List Wallets IDs on node

>>> BCoinNode::getWalletsIDs()
=> Illuminate\Support\Collection {#3142
     all: [
       "primary",
       "my_wallet",
       "my_other_wallet",
     ],
   }
>>>

    Get an snapshot of mempool

>>> BCoinNode::getMempool()
=> Illuminate\Support\Collection {#62368
     all: [
       "45fb9cf0af00c0cbf6db6504c310f08c5f9a4c623ac15dcf8c0eafcf8075fd2c",
       "cc10f9fe9b04bf0a2a9eab7dae84b33817cbd61723be9cf95d6ccce6d53aa67d",
       "af9e991ffff34b7068d4a565e8b5158c2de3a7f76158072ed22b3bb082d2cb78",
     ],
   }
>>>

    Check if a random address belongs to a Wallet within our Node.

>>> BCoinNode::addressBelongsToWallet('bc1qx269xkv4nyx4nvl3fkl7qtf3sehgrv0fkcfvlc', 'my_other_wallet')
=> true
>>>
```

## Donations => bitcoin:38NYkcaqSCxijvsfvgGexPsNkVZLfaTw54
