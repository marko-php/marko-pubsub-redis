<?php

declare(strict_types=1);

namespace Marko\PubSub\Redis\Tests;

use Amp\Redis\RedisConfig;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Core\Container\Container;
use Marko\PubSub\Redis\RedisPubSubConnection;
use Marko\Testing\Fake\FakeConfigRepository;

function createPubSubRedisContainer(): Container
{
    $container = new Container();
    $container->instance(ConfigRepositoryInterface::class, new FakeConfigRepository([
        'pubsub.prefix' => 'app:',
        'pubsub-redis.host' => 'redis.internal',
        'pubsub-redis.port' => 6380,
        'pubsub-redis.password' => 'secret',
        'pubsub-redis.database' => 4,
    ]));

    $module = require dirname(__DIR__) . '/module.php';

    foreach ($module['bindings'] as $id => $implementation) {
        $container->bind($id, $implementation);
    }

    foreach ($module['singletons'] ?? [] as $id) {
        $container->singleton($id);
    }

    return $container;
}

describe('pubsub-redis module bindings', function (): void {
    it('resolves RedisPubSubConnection with values from pubsub-redis config', function (): void {
        $connection = createPubSubRedisContainer()->get(RedisPubSubConnection::class);

        expect($connection)->toBeInstanceOf(RedisPubSubConnection::class)
            ->and($connection->host)->toBe('redis.internal')
            ->and($connection->port)->toBe(6380)
            ->and($connection->password)->toBe('secret')
            ->and($connection->database)->toBe(4);
    });

    it('takes the connection prefix from pubsub.prefix', function (): void {
        $connection = createPubSubRedisContainer()->get(RedisPubSubConnection::class);

        expect($connection->prefix)->toBe('app:');
    });

    it('resolves the same RedisPubSubConnection instance twice', function (): void {
        $container = createPubSubRedisContainer();

        expect($container->get(RedisPubSubConnection::class))
            ->toBe($container->get(RedisPubSubConnection::class));
    });

    it('builds a redis config carrying host, port, password and database', function (): void {
        $connection = new class () extends RedisPubSubConnection
        {
            public function __construct()
            {
                parent::__construct(host: 'redis.internal', port: 6380, password: 'secret', database: 4);
            }

            public function exposedRedisConfig(): RedisConfig
            {
                return $this->redisConfig();
            }
        };

        $config = $connection->exposedRedisConfig();

        expect($config->getConnectUri())->toBe('tcp://redis.internal:6380')
            ->and($config->getPassword())->toBe('secret')
            ->and($config->getDatabase())->toBe(4);
    });
});
