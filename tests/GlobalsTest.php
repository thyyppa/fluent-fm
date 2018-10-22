<?php namespace Test;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hyyppa\FluentFM\Connection\FluentFMRepository;

class GlobalsTest extends TestBase
{

    public function testSetGlobals()
    {
        //todo: proper response
        $fm = new FluentFMRepository( static::$config, $this->client( [
            static::token_request(),
            new Response( 200, [], file_get_contents( __DIR__ . '/responses/OK.json' ) ),
        ] ) );

        $this->assertTrue(
            $fm->globals( 'table_a', [
                'global_a' => 'a',
                'global_b' => 'b',
            ] )
        );

        /** @var Request $request */
        $request = $this->history[ 1 ][ 'request' ];
        $this->assertEquals( 'PATCH', $request->getMethod() );
        $this->assertEquals( 'globals', $request->getUri()->getPath() );
        $this->assertEquals(
            '{"globalFields":{"table_a::global_a":"a","table_a::global_b":"b"}}',
            $request->getBody()->getContents()
        );
    }
}
