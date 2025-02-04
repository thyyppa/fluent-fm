<?php

namespace Test;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Hyyppa\Toxx\Contracts\JsonAndArrayOutput;
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
    protected static function token_request(): Response
    {
        return new Response(200, [
            'X-FM-Data-Access-Token' => ['__token__'],
        ]);
    }

    /**
     * @param  array  $responses
     * @return Client
     */
    protected function client(array $responses = []): Client
    {
        $this->history = [];
        $responses[] = new Response(200, [], file_get_contents(__DIR__.'/responses/OK.json'));

        $stack = HandlerStack::create(
            new MockHandler($responses)
        );

        $stack->push(Middleware::history($this->history));

        return new Client([
            'handler' => $stack,
        ]);
    }

    /**
     * @param  array  $expected
     * @param  array  $actual
     * @return self
     */
    protected function assertArrayHas(array $expected, array $actual): self
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual);

            if (is_array($value)) {
                $this->assertArrayHas($value, $actual[$key]);
                continue;
            }

            $this->assertEquals($value, $actual[$key]);
        }

        return $this;
    }

    /**
     * @param  array  $expected
     * @param  array  $actual
     * @return self
     */
    protected function assertArrayHasFuzzy(array $expected, array $actual): self
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual);

            if (is_array($value)) {
                $this->assertArrayHasFuzzy($value, $actual[$key]);
                continue;
            }

            $this->assertStringContainsString((string) $value, (string) $actual[$key]);
        }

        return $this;
    }

    /**
     * @param  $expected
     * @param  string  $actual
     * @return self
     */
    protected function assertJsonLike($expected, string $actual): self
    {
        $this->assertArrayHas(
            is_string($expected) ? json_decode($expected, true) : $expected,
            json_decode($actual, true)
        );

        return $this;
    }

    /**
     * @param  $expected
     * @param  JsonAndArrayOutput  $actual
     * @return self
     */
    protected function assertJsonAndArrayLike($expected, JsonAndArrayOutput $actual): self
    {
        $this->assertArrayHas($expected, $actual->array());
        $this->assertJsonLike($expected, $actual->json());

        return $this;
    }
}
