<?php

namespace Test;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hyyppa\FluentFM\Connection\FluentFMRepository;

class AuthTest extends TestBase
{
    public function testToken(): void
    {
        new FluentFMRepository(static::$config, $this->client([
            static::token_request(),
        ]));

        /** @var Request $request */
        $request = $this->history[0]['request'];

        $this->assertEquals('POST', $request->getMethod());
        $this->assertArrayHas([
            'Authorization' => ['Basic X191c2VybmFtZV9fOl9fcGFzc3dvcmRfXw=='],
            'Content-Type'  => ['application/json'],
        ], $request->getHeaders());
    }

    public function testTokenAuthRetry(): void
    {
        $fm = new FluentFMRepository(static::$config, $this->client([
            static::token_request(),
            new Response(401),
            static::token_request(),
            new Response(200, [], file_get_contents(__DIR__.'/responses/records.json')),
            new Response(200, [], file_get_contents(__DIR__.'/responses/records.json')),
            new Response(401),
            static::token_request(),
            new Response(200, [], file_get_contents(__DIR__.'/responses/records.json')),
        ]));

        $this->assertNotNull(
            $fm->records('table_a')->get()
        );

        $this->assertNotNull(
            $fm->records('table_a')->get()
        );

        $this->assertNotNull(
            $fm->records('table_a')->get()
        );
    }

    public function testRefreshToken(): void
    {
        $fm = new FluentFMRepository(static::$config, $this->client([
            static::token_request(),
            static::token_request(),
            static::token_request(),
        ]));

        $fm->refreshToken();

        $this->assertCount(3, $this->history);
    }

    public function testLogout(): void
    {
        $fm = new FluentFMRepository(static::$config, $this->client([
            static::token_request(),
            new Response(200, [], file_get_contents(__DIR__.'/responses/OK.json')),
        ]));

        $fm->logout();

        /** @var Request $request */
        $request = $this->history[1]['request'];
        $this->assertEquals('DELETE', $request->getMethod());
    }
}
