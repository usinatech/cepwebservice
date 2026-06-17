<?php

$database = env('CEPWEBSERVICE_DB_PATH', env('SQLITE_DB_DATABASE', 'database/cepwebservice.sqlite'));

if (! preg_match('/^(\/|[A-Za-z]:\\\\)/', $database)) {
    $database = base_path($database);
}

return [
    'connection' => [
        'driver' => 'sqlite',
        'url' => env('SQLITE_DATABASE_URL'),
        'database' => $database,
        'prefix' => '',
        'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
    ],

    'search' => [
        'limit' => 20,
        'radius_km' => env('CEPWEBSERVICE_RADIUS_KM', 20),
    ],

    'http' => [
        'timeout' => env('CEPWEBSERVICE_HTTP_TIMEOUT', 10),
        'connect_timeout' => env('CEPWEBSERVICE_HTTP_CONNECT_TIMEOUT', 5),
        'user_agent' => env('CEPWEBSERVICE_USER_AGENT', env('APP_NAME', 'Laravel') . ' CEPWebservice'),
    ],

    'nominatim' => [
        'base_url' => env('CEPWEBSERVICE_NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
    ],

    'google' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY', ''),
        'sync_coordinates' => env('CEPWEBSERVICE_GOOGLE_SYNC_COORDINATES', false),
    ],

    'cache' => [
        'enabled' => env('CEPWEBSERVICE_CACHE_ENABLED', true),
        'ttl' => env('CEPWEBSERVICE_CACHE_TTL', 86400), // 1 day in seconds
    ],

    'rate_limit' => [
        'enabled' => env('CEPWEBSERVICE_RATE_LIMIT_ENABLED', true),
        'max_attempts' => env('CEPWEBSERVICE_RATE_LIMIT_MAX_ATTEMPTS', 60),
        'decay_minutes' => env('CEPWEBSERVICE_RATE_LIMIT_DECAY_MINUTES', 1),
    ],

    'middleware' => [
        'web',
    ],
];
