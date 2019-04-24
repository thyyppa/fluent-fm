<?php

namespace Test;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hyyppa\FluentFM\Connection\FluentFMRepository;

class FileTest extends TestBase
{
    public function testFileUpload() : void
    {
        $fm = new FluentFMRepository(static::$config, $this->client([
            static::token_request(),
            new Response(200, [], file_get_contents(__DIR__.'/responses/file_upload_response.json')),
        ]));

        $fm->upload('table_c', 'file', __DIR__.'/resources/php.png', 1)->exec();

        /** @var Request $request */
        $request = $this->history[1]['request'];
        $request_body = (string) $request->getBody();

        /* @var Request $request */
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('layouts/table_c/records/1/containers/file/1', $request->getUri()->getPath());
        $this->assertContains(
            pack('H*', base_convert(
                'Content-Disposition: form-data; name="upload"; filename="php.png"
Content-Length: 3323
Content-Type: image/png', 2, 16)),
            $request_body
        );
    }

    public function testFileDownload() : void
    {
        $fm = new FluentFMRepository(static::$config, $this->client([
            static::token_request(),
            new Response(200, [], file_get_contents(__DIR__.'/responses/file_download_response.json')),
        ]));

        try {
            $fm->download('table_c', 'file')
               ->where('id', '1')
               ->limit(1)
               ->exec();
        } catch (ConnectException $e) {
            if ($e->getHandlerContext()['errno'] !== 6) {
                throw $e;
            }
        }

        /** @var Request $request */
        $request = $this->history[1]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('layouts/table_c/_find', $request->getUri()->getPath());
        $this->assertEquals(
            $request->getBody()->getContents(),
            '{"limit":1,"query":[{"id":"=1"}]}'
        );
    }
}
