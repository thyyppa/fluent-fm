<?php

namespace Test;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hyyppa\FluentFM\Connection\FluentFMRepository;

class LayoutTest extends TestBase
{
    public function testLayoutValueLists(): void
    {
        $fm = new FluentFMRepository(
            static::$config,
            $this->client(
                [
                    static::token_request(),
                    new Response(200, [], file_get_contents(__DIR__.'/responses/layout_response.json')),
                    new Response(200, [], file_get_contents(__DIR__.'/responses/layout_response.json')),
                ]
            )
        );

        $value_list = $fm->valueList('layout_name', 'Region');

        $this->assertArrayHas(
            [
                'West' => 'West',
                'East' => 'East',
            ],
            $value_list
        );

        self::assertArrayNotHasKey('Aaa', $value_list);

        $value_list = $fm->valueList('layout_name', 'value_list_name');

        $this->assertArrayHas(
            [
                'Aaa' => '111',
                'bBb' => '222',
                'ccC' => '333',
            ],
            $value_list
        );

        self::assertArrayNotHasKey('West', $value_list);

        /** @var Request $request */
        $request = $this->history[1]['request'];
        self::assertEquals('GET', $request->getMethod());
        self::assertEquals('layouts/layout_name', $request->getUri()->getPath());
    }

    public function testLayoutFieldMetadata(): void
    {
        $fm = new FluentFMRepository(
            static::$config,
            $this->client(
                [
                    static::token_request(),
                    new Response(200, [], file_get_contents(__DIR__.'/responses/layout_response.json')),
                    new Response(200, [], file_get_contents(__DIR__.'/responses/layout_response.json')),
                ]
            )
        );

        $metadata = $fm->fieldMeta('layout_name', 'CustomerName');

        self::assertEquals(
            [
                "name"            => "CustomerName",
                "type"            => "normal",
                "displayType"     => "editText",
                "result"          => "text",
                "valueList"       => "Text",
                "global"          => false,
                "autoEnter"       => false,
                "fourDigitYear"   => false,
                "maxRepeat"       => 1,
                "maxCharacters"   => 0,
                "notEmpty"        => false,
                "numeric"         => false,
                "timeOfDay"       => false,
                "repetitionStart" => 1,
                "repetitionEnd"   => 1,
            ],
            (array) $metadata
        );

        $metadata = $fm->fieldMeta('layout_name', 'CustomerName2');

        self::assertEquals(
            [
                "name"            => "CustomerName2",
                "type"            => "normal",
                "displayType"     => "editText",
                "result"          => "text",
                "valueList"       => "Text",
                "global"          => false,
                "autoEnter"       => false,
                "fourDigitYear"   => false,
                "maxRepeat"       => 1,
                "maxCharacters"   => 0,
                "notEmpty"        => false,
                "numeric"         => false,
                "timeOfDay"       => false,
                "repetitionStart" => 1,
                "repetitionEnd"   => 1,
            ],
            (array) $metadata
        );

        /** @var Request $request */
        $request = $this->history[1]['request'];
        self::assertEquals('GET', $request->getMethod());
        self::assertEquals('layouts/layout_name', $request->getUri()->getPath());
    }
}
