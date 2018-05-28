<?php

return [
    'accept_self_signed_certificates' => env('BCOIN_ACCEPT_SELF_SIGNED_CERTIFICATES', true),
    'api_key' => env('BCOIN_API_KEY', false),
    'number_of_confirmations_to_consider_transaction_done' => env('NUMBER_OF_CONFIRMATIONS_TO_CONSIDER_TRANSACTION_DONE', 3),
    'server_ip' => env('BCOIN_SERVER_IP', '127.0.0.1'),
    'server_port' => env('BCOIN_SERVER_PORT', 18333),
    'server_ssl' => env('BCOIN_SERVER_SSL', false),
];
