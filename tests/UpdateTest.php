<?php namespace Test;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hyyppa\FluentFM\Connection\FluentFMRepository;

class UpdateTest extends TestBase
{

    public function testUpdate() : void
    {
        $fm = new FluentFMRepository( static::$config, $this->client( [
            static::token_request(),
            new Response( 200, [], file_get_contents( __DIR__ . '/responses/update_response_find.json' ) ),
            new Response( 200, [], file_get_contents( __DIR__ . '/responses/update_response.json' ) ),
        ] ) );

        $data = [
            'id'      => 1,
            'field_x' => 'x',
            'field_y' => 'y',
            'field_z' => 'z',
        ];

        $this->assertTrue(
            $fm->update( 'table_a', $data )
               ->where( 'id', '1' )
               ->exec()
        );

        /** @var Request $request */
        $request = $this->history[ 2 ][ 'request' ];
        $this->assertEquals( 'PATCH', $request->getMethod() );
        $this->assertEquals( 'layouts/table_a/records/1', $request->getUri()->getPath() );
        $this->assertEquals( json_encode( [ 'fieldData' => $data ] ), $request->getBody()->getContents() );
    }

}
