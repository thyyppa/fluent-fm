<?php

namespace Test;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class TestBase extends TestCase
{
    protected static $config = [
        'host' => '__hostname__',
        'file' => '__filemaker__',
        'user' => '__username__',
        'pass' => '__password__',
    ];

    protected $history = [];
    protected $real_history = [];

    /**
     * @return Response
     */
    protected static function token_request() : Response
    {
        return new Response(200, [
            'X-FM-Data-Access-Token' => ['__token__'],
        ]);
    }

    /**
     * @param  array  $responses
     *
     * @return Client
     */
    protected function client(array $responses = []) : Client
    {
        $this->history = [];

        $stack = HandlerStack::create(
            new MockHandler($responses)
        );

        $stack->push(Middleware::history($this->history));

        return new Client([
            'handler' => $stack,
        ]);
    }
}
