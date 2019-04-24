<?php

namespace Hyyppa\FluentFM\Connection;

/**
 * Class Url.
 */
class Url
{
    /**
     * @param  string  $layout
     * @param  int|null  $id
     *
     * @return string
     */
    public static function records(string $layout, int $id = null) : string
    {
        $record = $id ? '/'.$id : '';

        return 'layouts/'.$layout.'/records'.$record;
    }

    /**
     * @param  string  $layout
     *
     * @return string
     */
    public static function find(string $layout) : string
    {
        return 'layouts/'.$layout.'/_find';
    }

    /**
     * @return string
     */
    public static function globals() : string
    {
        return 'globals';
    }

    /**
     * @param  string  $layout
     * @param  string  $field
     * @param  int  $recordId
     *
     * @return string
     */
    public static function container(string $layout, string $field, int $recordId) : string
    {
        return sprintf('layouts/%s/records/%s/containers/%s/1',
            $layout,
            $recordId,
            $field
        );
    }
}
