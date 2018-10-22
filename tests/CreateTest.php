<?php namespace Test;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hyyppa\FluentFM\Connection\FluentFMRepository;

class CreateTest extends TestBase
{

    public function testCreateRecords() : void
    {
        $fm = new FluentFMRepository( static::$config, $this->client( [
            static::token_request(),
            new Response( 200, [], file_get_contents( __DIR__ . '/responses/create.json' ) ),
        ] ) );

        $data = [
            'id'      => 1,
            'field_x' => 'x',
            'field_y' => 'y',
            'field_z' => 'z',
        ];

        $this->assertEquals( 1, $fm->create( 'table_a', $data ) );

        /** @var Request $request */
        $request = $this->history[ 1 ][ 'request' ];
        $this->assertEquals( 'POST', $request->getMethod() );
        $this->assertEquals( 'layouts/table_a/records', $request->getUri()->getPath() );
        $this->assertEquals(
            $request->getBody()->getContents(),
            json_encode( [ 'fieldData' => $data ] )
        );
    }

}
