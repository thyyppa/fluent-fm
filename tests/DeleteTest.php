<?php

namespace Test;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hyyppa\FluentFM\Connection\FluentFMRepository;

class DeleteTest extends TestBase
{

    public function testDelete() : void
    {
        $fm = new FluentFMRepository(static::$config, $this->client([
            static::token_request(),
            new Response(200, [], file_get_contents(__DIR__.'/responses/update_response_find.json')),
            new Response(200, [], file_get_contents(__DIR__.'/responses/delete_response.json')),
        ]));

        $this->assertTrue(
            $fm->delete('table_a')
               ->where('id', '1')
               ->exec()
        );

        /** @var Request $request */
        $request = $this->history[ 2 ][ 'request' ];
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals('layouts/table_a/records/1', $request->getUri()->getPath());

        /** @var Response $response */
        $response = $this->history[ 2 ][ 'response' ];
        $response->getBody()->rewind();
        $this->assertEquals(
            json_decode('{"response":{},"messages":[{"code":"0","message":"OK"}]}', true),
            json_decode($response->getBody()->getContents(), true)
        );
    }


    public function testSoftDelete() : void
    {
        $fm = new FluentFMRepository(static::$config, $this->client([
            static::token_request(),
            new Response(200, [], file_get_contents(__DIR__.'/responses/update_response_find.json')),
            new Response(200, [], file_get_contents(__DIR__.'/responses/soft_delete_response.json')),
        ]));

        $fm->softDelete('table_a')
           ->where('id', '1')
           ->limit(1)
           ->exec();

        $request = $this->history[ 2 ][ 'request' ];
        $this->assertEquals('PATCH', $request->getMethod());
        $this->assertEquals('layouts/table_a/records/1', $request->getUri()->getPath());
        $this->assertContains('{"fieldData":{"deleted_at":', $request->getBody()->getContents());
    }
}
