<?php

namespace App\Config;

return [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'name' => $_ENV['DB_NAME'] ?? '',
    'user' => $_ENV['DB_USER'] ?? 'root',
    'pass' => $_ENV['DB_PASS'] ?? '',
];
