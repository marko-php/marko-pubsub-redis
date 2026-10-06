<?php

declare(strict_types=1);

namespace Marko\PubSub\Redis;

use Amp\Redis\Connection\RedisConnector;
use Amp\Redis\Connection\SocketRedisConnector;

use function Amp\Redis\createRedisClient;

use function Amp\Redis\createRedisConnector;

use Amp\Redis\RedisClient;
use Amp\Redis\RedisConfig;
use Amp\Redis\RedisException;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Marko\PubSub\Exceptions\PubSubException;

class RedisPubSubConnection
{
    /**
     * Transport schemes: tcp is plain text, tls encrypts the connection and verifies the server
     * certificate against the host name.
     */
    public const array SCHEMES = ['tcp', 'tls'];

    private ?RedisClient $client = null;

    private ?RedisConnector $connector = null;

    /**
     * @throws PubSubException
     */
    public function __construct(
        public readonly string $host = '127.0.0.1',
        public readonly int $port = 6379,
        public readonly ?string $password = null,
        public readonly int $database = 0,
        public readonly string $prefix = 'marko:',
        public readonly string $scheme = 'tcp',
    ) {
        if (!in_array($scheme, self::SCHEMES, true)) {
            throw PubSubException::invalidConnectionOption('pubsub-redis.scheme', $scheme, self::SCHEMES);
        }
    }

    /**
     * @throws RedisException
     */
    public function client(): RedisClient
    {
        if ($this->client === null) {
            $this->client = $this->createClient();
        }

        return $this->client;
    }

    /**
     * @throws RedisException
     */
    public function connector(): RedisConnector
    {
        if ($this->connector === null) {
            $this->connector = $this->createConnector();
        }

        return $this->connector;
    }

    public function disconnect(): void
    {
        $this->client = null;
        $this->connector = null;
    }

    public function isConnected(): bool
    {
        return $this->client !== null;
    }

    /**
     * @throws RedisException
     */
    protected function createClient(): RedisClient
    {
        return createRedisClient($this->redisConfig(), $this->socketRedisConnector());
    }

    /**
     * @throws RedisException
     */
    protected function createConnector(): RedisConnector
    {
        return createRedisConnector($this->redisConfig(), $this->socketRedisConnector());
    }

    /**
     * The connector that opens the socket, or null to let amphp open a plain TCP socket. The tls scheme
     * completes a TLS handshake that verifies the server certificate against the host before the Redis
     * protocol (and the AUTH password) is sent.
     *
     * @throws RedisException
     */
    protected function socketRedisConnector(): ?RedisConnector
    {
        if ($this->scheme !== 'tls') {
            return null;
        }

        $config = $this->redisConfig();

        return new SocketRedisConnector(
            $config->getConnectUri(),
            new ConnectContext()
                ->withConnectTimeout($config->getTimeout())
                ->withTlsContext(new ClientTlsContext($this->host)),
            new TlsSocketConnector(),
        );
    }

    /**
     * @throws RedisException
     */
    protected function redisConfig(): RedisConfig
    {
        // amphp/redis only parses tcp URIs: TLS is layered on by socketRedisConnector()
        $config = RedisConfig::fromUri("tcp://$this->host:$this->port")
            ->withDatabase($this->database);

        if ($this->password !== null) {
            $config = $config->withPassword($this->password);
        }

        return $config;
    }
}
