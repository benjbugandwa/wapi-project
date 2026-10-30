<?php

declare(strict_types=1);

return [
    'documents' => [
        'disk' => env('DOCUMENTS_FILESYSTEM_DISK', env('FILESYSTEM_DISK', 'local')),
    ],

    'security' => [
        'headers_enabled' => env('SECURITY_HEADERS_ENABLED', true),
        'content_security_policy' => env('SECURITY_CONTENT_SECURITY_POLICY', "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.bunny.net; script-src 'self' 'unsafe-inline' 'unsafe-eval'; font-src 'self' data: https://fonts.bunny.net; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"),
    ],

    'railway' => [
        'storage_path' => env('RAILWAY_STORAGE_PATH', storage_path('app/private')),
    ],
];
