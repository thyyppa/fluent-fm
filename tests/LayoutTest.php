<?php

namespace Test;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hyyppa\FluentFM\Connection\FluentFMRepository;
use Hyyppa\FluentFM\Exception\FilemakerException;

class LayoutTest extends TestBase
{
    public function testLayoutValueLists(): void
    {
        $fm = new FluentFMRepository(
            static::$config,
            $this->client(
                [
                    static::token_request(),
                    new Response(200, [], file_get_contents(__DIR__ . '/responses/layout_response.json')),
                    new Response(200, [], file_get_contents(__DIR__ . '/responses/layout_response.json')),
                ]
            )
        );

        $value_list = $fm->valueList('layout_name', 'RegionListField');

        $this->assertArrayHas(
            [
                'West' => 'West',
                'East' => 'East',
            ],
            $value_list
        );

        self::assertArrayNotHasKey('Aaa', $value_list);

        $this->expectException(FilemakerException::class);
        $this->expectExceptionMessage("The field 'RegionTextField' does not have an associated value list on layout 'layout_name'");
        $value_list = $fm->valueList('layout_name', 'RegionTextField');

        $this->expectException(FilemakerException::class);
        $this->expectExceptionMessage("Metadata for field 'value_list_name' not found on layout 'layout_name'");
        $value_list = $fm->valueList('layout_name', 'value_list_name');

        self::assertArrayNotHasKey('West', $value_list);

        /** @var Request $request */
        $request = $this->history[1]['request'];
        self::assertEquals('GET', $request->getMethod());
        self::assertEquals('layouts/layout_name', $request->getUri()->getPath());
    }

    /** @test */
    public function testValueListWithTextField()
    {
        $fm = new FluentFMRepository(
            static::$config,
            $this->client(
                [
                    static::token_request(),
                    new Response(200, [], file_get_contents(__DIR__ . '/responses/layout_response.json')),
                ]
            )
        );

        $this->expectException(FilemakerException::class);
        $this->expectExceptionMessage("The field 'RegionTextField' does not have an associated value list on layout 'layout_name'");
        $fm->valueList('layout_name', 'RegionTextField');
    }

    /** @test */
    public function testValueListWithMissingField()
    {
        $fm = new FluentFMRepository(
            static::$config,
            $this->client(
                [
                    static::token_request(),
                    new Response(200, [], file_get_contents(__DIR__ . '/responses/layout_response.json')),
                ]
            )
        );

        $this->expectException(FilemakerException::class);
        $this->expectExceptionMessage("Metadata for field 'value_list_name' not found on layout 'layout_name'");
        $value_list = $fm->valueList('layout_name', 'value_list_name');
    }

    public function testLayoutFieldMetadata(): void
    {
        $fm = new FluentFMRepository(
            static::$config,
            $this->client(
                [
                    static::token_request(),
                    new Response(200, [], file_get_contents(__DIR__ . '/responses/layout_response.json')),
                    new Response(200, [], file_get_contents(__DIR__ . '/responses/layout_response.json')),
                ]
            )
        );

        $metadata = $fm->fieldMeta('layout_name', 'RegionTextField');

        self::assertEquals(
            [
                "name" => "RegionTextField",
                "type" => "normal",
                "displayType" => "editText",
                "result" => "text",
                "global" => false,
                "autoEnter" => false,
                "fourDigitYear" => false,
                "maxRepeat" => 1,
                "maxCharacters" => 0,
                "notEmpty" => false,
                "numeric" => false,
                "timeOfDay" => false,
                "repetitionStart" => 1,
                "repetitionEnd" => 1
            ],
            (array) $metadata
        );

        $metadata = $fm->fieldMeta('layout_name', 'RegionListField');

        self::assertEquals(
            [
                "name" => "RegionListField",
                "type" => "normal",
                "displayType" => "popupList",
                "result" => "text",
                "global" => false,
                "autoEnter" => false,
                "fourDigitYear" => false,
                "maxRepeat" => 1,
                "maxCharacters" => 0,
                "notEmpty" => false,
                "numeric" => false,
                "timeOfDay" => false,
                "valueList" => "RegionList",
                "repetitionStart" => 1,
                "repetitionEnd" => 1
            ],
            (array) $metadata
        );

        /** @var Request $request */
        $request = $this->history[1]['request'];
        self::assertEquals('GET', $request->getMethod());
        self::assertEquals('layouts/layout_name', $request->getUri()->getPath());
    }
}
