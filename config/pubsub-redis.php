<?php

declare(strict_types=1);

use Marko\Config\Env;

return [
    'host' => Env::string('PUBSUB_REDIS_HOST', '127.0.0.1'),
    'port' => Env::int('PUBSUB_REDIS_PORT', 6379, min: 1, max: 65535),
    'password' => Env::nullableString('PUBSUB_REDIS_PASSWORD'),
    'database' => Env::int('PUBSUB_REDIS_DATABASE', 0, min: 0),
    // tcp (plain text) or tls (encrypted, server certificate verified against host). Use tls for any
    // Redis reached over a network you do not control.
    'scheme' => Env::string('PUBSUB_REDIS_SCHEME', 'tcp'),
];
