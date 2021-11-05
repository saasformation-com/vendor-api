<?php

namespace SaaSFormation\Vendor\API;

use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

class Server
{
    private HttpServer $httpServer;

    private function __construct()
    {
    }

    public static function create(): Server
    {
        return new static();
    }

    public function boot(Kernel $kernel): Server
    {
        $this->httpServer = new HttpServer(function (ServerRequestInterface $request) use($kernel) {
            try {
                return $kernel->run($request);
            } catch(\Throwable $e) {
                return new Response(
                    200,
                    array(
                        'Content-Type' => 'text/plain'
                    ),
                    json_encode([
                        'error' => [
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]
                    ])
                );
            }
        });

        return $this;
    }

    public function serve(string $host = '0.0.0.0', string $port = '80'): void
    {
        $socket = new SocketServer($host . ':' . $port);
        $this->httpServer->listen($socket);
    }
}
