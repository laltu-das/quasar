<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Envato Product Credentials
    |--------------------------------------------------------------------------
    |
    */
    'version' => '1',

    'license_key' => env('LICENSE_KEY', 'your-default-license-key'),

    'license_server_url' => env('LICENSE_SERVER_URL', 'https://scriptspheres.com'),

    'envato' => [
        'item_id' => env('ENVATO_ITEM_ID'),
        'purchase_code' => env('ENVATO_PURCHASE_CODE'),
    ],

];
