<?php

declare(strict_types=1);

use Amp\Cancellation;
use Amp\Socket\ConnectContext;
use Amp\Socket\Socket;
use Amp\Socket\SocketAddress;
use Amp\Socket\SocketConnector;
use Marko\PubSub\Redis\TlsSocketConnector;

it('completes the TLS handshake on the socket it opens', function (): void {
    $socket = $this->createMock(Socket::class);
    $socket->expects($this->once())->method('setupTls');

    $context = new ConnectContext();

    $inner = new class ($socket) implements SocketConnector
    {
        public SocketAddress|string|null $uri = null;

        public ?ConnectContext $context = null;

        public function __construct(
            private readonly Socket $socket,
        ) {}

        public function connect(
            SocketAddress|string $uri,
            ?ConnectContext $context = null,
            ?Cancellation $cancellation = null,
        ): Socket {
            $this->uri = $uri;
            $this->context = $context;

            return $this->socket;
        }
    };

    $result = new TlsSocketConnector($inner)->connect('tcp://redis.example.com:6380', $context);

    expect($result)->toBe($socket)
        ->and($inner->uri)->toBe('tcp://redis.example.com:6380')
        ->and($inner->context)->toBe($context);
});
