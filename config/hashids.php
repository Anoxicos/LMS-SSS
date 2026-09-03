<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    */

    'default' => 'main',

    /*
    |--------------------------------------------------------------------------
    | Hashids Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        'main' => [
            'salt' => env('HASHIDS_SALT', 'company-secret-key-2026'),
            'length' => env('HASHIDS_LENGTH', 10),
            // 'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
        ],

        'alternative' => [
            'salt' => env('HASHIDS_ALTERNATIVE_SALT', 'your-salt-string'),
            'length' => env('HASHIDS_ALTERNATIVE_LENGTH', 10),
            // 'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
        ],

    ],

];