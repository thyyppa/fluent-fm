<?php namespace Hyyppa\FluentFM\Exception;

class ExceptionMessages
{

    /**
     * @param string $message
     *
     * @return string
     */
    protected static function baseMessage( $message ) : string
    {
        return sprintf( 'FileMaker returned error %d - %s', $message->code, $message->message );
    }


    /**
     * @param       $message
     * @param array $query
     *
     * @return string
     */
    public function generic( $message, array $query ) : string
    {
        return self::sep( self::baseMessage( $message ) )
               . self::textWrap(
                'This is the payload that was sent to FileMaker: ' . PHP_EOL . self::queryDump( $query )
            ) . self::sep();
    }


    /**
     * @param       $message
     * @param array $query
     *
     * @return string
     */
    public static function fieldMissing( $message, array $query ) : string
    {
        return self::sep( self::baseMessage( $message ) )
               . self::textWrap( 'FileMaker does not specify which field, so if you are sure that the field exists:

- You may be trying to use soft deletes without the deleted_at field
- You may be trying to sort by latest without the created_at field
- You may be trying to get the last updated without the updated_at field

Please review the payload that was sent to FileMaker:
    ' . self::queryDump( $query ) ) . self::sep();
    }


    /**
     * @param       $message
     * @param array $query
     *
     * @return string
     */
    public static function fieldInvalid( $message, array $query ) : string
    {
        return self::sep( self::baseMessage( $message ) )
               . self::textWrap(
                'FileMaker did not specify which field, please review the payload that was sent:'
                . PHP_EOL
                . self::queryDump( $query )
            ) . self::sep();
    }


    /**
     * -- Generates a line like this, with a width of $len --------
     *
     * @param string $title
     * @param int    $len
     *
     * @return string
     */
    public static function sep( string $title = '', int $len = 120 ) : string
    {
        if( $title ) {
            $len   -= strlen( $title ) + 4;
            $title = '-- ' . $title . ' ';
        }

        return str_repeat( PHP_EOL, 2 ) . $title . str_repeat( '-', $len ) . PHP_EOL;
    }


    /**
     * Wraps text to max width of $len, indents all lines 4 spaces
     *
     * @param string $string
     * @param int    $len
     *
     * @return string
     */
    public static function textWrap( string $string, int $len = 120 ) : string
    {
        return str_replace( PHP_EOL, PHP_EOL . '    ', wordwrap( PHP_EOL . $string, $len - 4, PHP_EOL ) );
    }


    /**
     * $creates = [
     *   'a' => 'square box',
     *   'formatted' => 'text dump',
     *   'of' => 'an array',
     *   'that' => 'looks',
     *   'like' => 'this',
     * ];
     *
     * @param array $query
     *
     * @return string
     */
    protected static function queryDump( array $query ) : string
    {
        $export = var_export( $query, true );
        $export = preg_replace( '/^([ ]*)(.*)/m', '$1$2', $export );
        $array  = preg_split( "/\r\n|\n|\r/", $export );
        $array  = preg_replace(
            [ "/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/", '/NULL/' ],
            [ null, ']$1', ' => [', 'null' ],
            $array
        );

        return PHP_EOL . '$request = ' . implode( PHP_EOL, array_filter( [ '[' ] + $array ) ) . ';';
    }

}
