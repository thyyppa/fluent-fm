<?php namespace Test;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hyyppa\FluentFM\Connection\FluentFMRepository;

class FindTest extends TestBase
{

    public function testFind() : void
    {
        $fm = new FluentFMRepository( static::$config, $this->client( [
            static::token_request(),
            new Response( 200, [], file_get_contents( __DIR__ . '/responses/records.json' ) ),
        ] ) );

        $result = $fm->find( 'table_a' )
                     ->where( 'field_x', 'x' )
                     ->has( 'field_y' )
                     ->sortAsc( 'field_y' )
                     ->limit( 2 )
                     ->offset( 1 )
                     ->get();

        /** @var Request $request */
        $request = $this->history[ 1 ][ 'request' ];
        $this->assertEquals( 'POST', $request->getMethod() );
        $this->assertEquals( 'layouts/table_a/_find', $request->getUri()->getPath() );
        $this->assertEquals(
            $request->getBody()->getContents(),
            '{"limit":2,"offset":1,"sort":"[{\"fieldName\":\"field_y\",\"sortOrder\":\"ascend\"}]","query":[{"field_x":"=x","field_y":"=*"}]}'
        );

        $stub = json_decode( file_get_contents( __DIR__ . '/responses/records.json' ), true );
        $this->assertArraySubset( [
            1 => $stub[ 'response' ][ 'data' ][ 0 ][ 'fieldData' ],
            2 => $stub[ 'response' ][ 'data' ][ 1 ][ 'fieldData' ],
        ], $result );
    }


    public function testFindWithDeleted() : void
    {
        $fm = new FluentFMRepository( static::$config, $this->client( [
            static::token_request(),
            new Response( 200, [], file_get_contents( __DIR__ . '/responses/records.json' ) ),
        ] ) );

        $fm->find( 'table_a' )
           ->where( 'field_x', 'x' )
           ->withDeleted()
           ->get();

        /** @var Request $request */
        $request = $this->history[ 1 ][ 'request' ];
        $this->assertEquals( 'POST', $request->getMethod() );
        $this->assertEquals( 'layouts/table_a/_find', $request->getUri()->getPath() );
        $this->assertEquals(
            $request->getBody()->getContents(),
            '{"query":[{"field_x":"=x"}]}'
        );
    }


    public function testFindWithoutDeleted() : void
    {
        $fm = new FluentFMRepository( static::$config, $this->client( [
            static::token_request(),
            new Response( 200, [], file_get_contents( __DIR__ . '/responses/records.json' ) ),
        ] ) );

        $fm->find( 'table_a' )
           ->where( 'field_x', 'x' )
           ->withoutDeleted()
           ->get();

        /** @var Request $request */
        $request = $this->history[ 1 ][ 'request' ];
        $this->assertEquals( 'POST', $request->getMethod() );
        $this->assertEquals( 'layouts/table_a/_find', $request->getUri()->getPath() );
        $this->assertEquals(
            $request->getBody()->getContents(),
            '{"query":[{"field_x":"=x","deleted_at":"="}]}'
        );
    }



}
