<?php namespace Test;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hyyppa\FluentFM\Connection\FluentFMRepository;

class RecordTest extends TestBase
{

    public function testGetSingleRecord() : void
    {
        $fm = new FluentFMRepository( static::$config, $this->client( [
            static::token_request(),
            new Response( 200, [], file_get_contents( __DIR__ . '/responses/record.json' ) ),
        ] ) );

        $record = $fm->records( 'table_a', 1 )->get();

        /** @var Request $request */
        $request = $this->history[ 1 ][ 'request' ];
        $this->assertEquals( 'GET', $request->getMethod() );
        $this->assertEquals( 'layouts/table_a/records/1', $request->getUri()->getPath() );
        $this->assertArraySubset( [
            'Authorization' => [ 'Bearer __token__' ],
        ], $request->getHeaders() );

        $this->assertArraySubset(
            [ 'response' => [ 'data' => [ [ 'fieldData' => $record[ 1 ] ] ] ] ],
            json_decode(
                file_get_contents( __DIR__ . '/responses/record.json' ),
                true
            )
        );
    }


    public function testMultipleRecords() : void
    {
        $fm = new FluentFMRepository( static::$config, $this->client( [
            static::token_request(),
            new Response( 200, [], file_get_contents( __DIR__ . '/responses/records.json' ) ),
        ] ) );

        $result = $fm->records( 'table_a' )->limit( 2 )->get();

        /** @var Request $request */
        $request = $this->history[ 1 ][ 'request' ];
        $this->assertEquals( 'GET', $request->getMethod() );
        $this->assertEquals( 'layouts/table_a/records', $request->getUri()->getPath() );
        $this->assertEquals( '_limit=2', $request->getUri()->getQuery() );
        $this->assertArraySubset( [
            'Authorization' => [ 'Bearer __token__' ],
        ], $request->getHeaders() );

        $stub = json_decode( file_get_contents( __DIR__ . '/responses/records.json' ), true );
        $this->assertEquals( [
            1 => $stub[ 'response' ][ 'data' ][ 0 ][ 'fieldData' ],
            2 => $stub[ 'response' ][ 'data' ][ 1 ][ 'fieldData' ],
        ], $result );
    }


}
