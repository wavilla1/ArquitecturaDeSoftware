<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'HEAD', 'OPTIONS'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Content-Type'],
    'exposed_headers' => ['Retry-After'],
    'max_age' => 3600,
    'supports_credentials' => false,
];
