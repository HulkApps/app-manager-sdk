<?php

return [

    /*
    |-------------------------------------------
    | App manager API endpoint
    |-------------------------------------------
    |
    | The endpoint of app manager apis to retrieve data for the app
    |
    | Default: https://app-manager.hulkapps.com/api/
    |
    |-------------------------------------------
    */
    'api' => env('APP_MANAGER_URI', 'https://app-manager.hulkapps.com/api/'),

    /*
    |-------------------------------------------
    | App manager API secret [REQUIRED]
    |-------------------------------------------
    |
    | The secret of the app manager for authentication
    |
    |-------------------------------------------
    */
    'secret' => env('APP_MANAGER_SECRET', ''),

    /*
    |-------------------------------------------
    | The App manager api
    |-------------------------------------------
    |
    | The api version of app manager that SDK will use
    |
    | Default: latest
    |
    |-------------------------------------------
    */
    'version' => env('APP_MANAGER_API_VER', 'latest'),

    /*
    |-------------------------------------------
    | The slug/key of the app [REQUIRED]
    |-------------------------------------------
    |
    | The key of the app will tell app manager for which app you want to fetch the data
    |
    |-------------------------------------------
    */
    'app_key' => '',

    /*
    |-------------------------------------------
    | The Shopify User(Shop) table name
    |-------------------------------------------
    |
    | The table in which user or shop credentials are stored
    |
    | Default: users
    |
    |-------------------------------------------
    */
    'shop_table_name' => env('SHOP_TABLE_NAME', 'users'),

    /*
    |-------------------------------------------
    | Shopify users fields
    |-------------------------------------------
    |
    | Mapped shop user fields to your table
    |
    |-------------------------------------------
    */
    'field_names' => [
        'name' => env('NAME', 'name'),
        'shopify_email' => env('SHOPIFY_EMAIL', 'shopify_email'),
        'shopify_plan' => env('SHOPIFY_PLAN', 'shopify_plan'),
        'plan_id' => env('PLAN_ID', 'plan_id'),
        'created_at' => env('CREATED_AT', 'created_at'),
    ],
];