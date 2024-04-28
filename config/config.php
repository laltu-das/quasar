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

    'license_server_url' => env('LICENSE_SERVER_URL', 'http://127.0.0.1:8001'),

    'envato' => [
        'item_id' => env('ENVATO_ITEM_ID'),
        'purchase_code' => env('ENVATO_PURCHASE_CODE'),
    ],

    'github' => [
        'token' => env('GITHUB_REPOSITORY'),
        'username' => env('GITHUB_USERNAME'),
        'repository' => env('GITHUB_TOKEN'),
    ],


    /*
    |--------------------------------------------------------------------------
    | Server Requirements
    |--------------------------------------------------------------------------
    |
    | This is the default Laravel server requirements, you can add as many
    | as your application requires, we check if the extension is enabled
    | by looping through the array and run "extension_loaded" on it.
    |
    */
    'core' => [
        'minPhpVersion' => '7.0.0',
    ],
    'final' => [
        'key' => true,
        'publish' => false,
    ],
    'requirements' => [
        'php' => [
            'openssl',
            'pdo',
            'mbstring',
            'tokenizer',
            'JSON',
            'cURL',
        ],
        'apache' => [
            'mod_rewrite',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Folders Permissions
    |--------------------------------------------------------------------------
    |
    | This is the default Laravel folders permissions, if your application
    | requires more permissions just add them to the array list bellow.
    |
    */
    'permissions' => [
        'storage/framework/' => '775',
        'storage/logs/' => '775',
        'bootstrap/cache/' => '775',
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Form Wizard Validation Rules & Messages
    |--------------------------------------------------------------------------
    |
    | This are the default form field validation rules. Available Rules:
    | https://laravel.com/docs/5.4/validation#available-validation-rules
    |
    */
    'environment' => [
        'form' => [
            'rules' => [
                'app_name' => 'required|string|max:50',
                'environment' => 'required|string|max:50',
                'environment_custom' => 'required_if:environment,other|max:50',
                'app_debug' => 'required|string',
                'app_log_level' => 'required|string|max:50',
                'app_url' => 'required|url',
                'database_connection' => 'required|string|max:50',
                'database_hostname' => 'required|string|max:50',
                'database_port' => 'required|numeric',
                'database_name' => 'required|string|max:50',
                'database_username' => 'required|string|max:50',
                'database_password' => 'nullable|string|max:50',
                'broadcast_driver' => 'required|string|max:50',
                'cache_driver' => 'required|string|max:50',
                'session_driver' => 'required|string|max:50',
                'queue_driver' => 'required|string|max:50',
                'redis_hostname' => 'required|string|max:50',
                'redis_password' => 'required|string|max:50',
                'redis_port' => 'required|numeric',
                'mail_driver' => 'required|string|max:50',
                'mail_host' => 'required|string|max:50',
                'mail_port' => 'required|string|max:50',
                'mail_username' => 'required|string|max:50',
                'mail_password' => 'required|string|max:50',
                'mail_encryption' => 'required|string|max:50',
                'pusher_app_id' => 'max:50',
                'pusher_app_key' => 'max:50',
                'pusher_app_secret' => 'max:50',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Updater Enabled
    |--------------------------------------------------------------------------
    | Can the application run the '/update' route with the migrations.
    | The default option is set to False if none is present.
    | Boolean value
    |
    */
    'updaterEnabled' => 'true',
];
