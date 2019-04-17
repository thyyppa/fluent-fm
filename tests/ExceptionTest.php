<?php

namespace Test;

use GuzzleHttp\Psr7\Response;
use Hyyppa\FluentFM\Connection\FluentFMRepository;
use Hyyppa\FluentFM\Exception\FilemakerException;

class ExceptionTest extends TestBase
{
    public function testAuthFailedAfterRetry() : void
    {
        $this->expectException( FilemakerException::class );
        $this->expectExceptionCode( 401 );

        $fm = new FluentFMRepository( static::$config, $this->client( [
            new Response( 401 ),
            new Response( 401 ),
        ] ) );

        $fm->records( 'table_a' )->get();
    }

    public function testLayoutMissing() : void
    {
        $this->expectException( FilemakerException::class );
        $this->expectExceptionCode( 105 );

        $fm = new FluentFMRepository( static::$config, $this->client( [
            static::token_request(),
            new Response( 500, [], file_get_contents( __DIR__.'/responses/layout_missing.json' ) ),
        ] ) );

        $fm->records( 'table_z' )->get();
    }
}
