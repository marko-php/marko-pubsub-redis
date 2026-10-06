<?php

declare(strict_types=1);

namespace Marko\PubSub\Redis;

use Amp\Cancellation;
use Amp\Socket\ConnectContext;
use Amp\Socket\Socket;
use Amp\Socket\SocketAddress;
use Amp\Socket\SocketConnector;

use function Amp\Socket\socketConnector;

/**
 * Opens the socket and completes the TLS handshake before the Redis protocol starts. The handshake uses
 * the TLS context of the ConnectContext, which verifies the server certificate by default.
 */
readonly class TlsSocketConnector implements SocketConnector
{
    public function __construct(
        private ?SocketConnector $connector = null,
    ) {}

    public function connect(
        SocketAddress|string $uri,
        ?ConnectContext $context = null,
        ?Cancellation $cancellation = null,
    ): Socket {
        $socket = ($this->connector ?? socketConnector())->connect($uri, $context, $cancellation);
        $socket->setupTls($cancellation);

        return $socket;
    }
}
