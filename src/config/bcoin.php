<?php

return [
    'accept_self_signed_certificates' => env('BCOIN_ACCEPT_SELF_SIGNED_CERTIFICATES', true),
    'api_key' => env('BCOIN_API_KEY', null),
    'number_of_confirmations_to_consider_transaction_done' => env('BCOIN_NUMBER_OF_CONFIRMATIONS_TO_CONSIDER_TRANSACTION_DONE', 3),
    'server_ip' => env('BCOIN_SERVER_IP', '127.0.0.1'),
    'server_port' => env('BCOIN_SERVER_PORT', 8332),
    'server_ssl' => env('BCOIN_SERVER_SSL', false),
    'server_timeout_seconds' => env('BCOIN_SERVER_TIMEOUT_SECONDS', 20),
    'service_key' => env('BCOIN_SERVICE_KEY', null),
    'wallet_admin_token' => env('BCOIN_WALLET_ADMIN_TOKEN', null),
    'wallet_api_key' => env('BCOIN_WALLET_API_KEY', null),
    'wallet_server_ip' => env('BCOIN_WALLET_SERVER_IP', '127.0.0.1'),
    'wallet_server_port' => env('BCOIN_WALLET_SERVER_PORT', 8334),
    'wallet_server_ssl' => env('BCOIN_WALLET_SERVER_SSL', false),
    'wallet_service_key' => env('BCOIN_WALLET_SERVICE_KEY', null),
];
