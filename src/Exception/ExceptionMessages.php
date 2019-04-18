<?php namespace Hyyppa\FluentFM\Exception;

class ExceptionMessages
{

    public static function sep( string $title = '', int $len = 120 ) : string
    {
        if( $title ) {
            $len   -= strlen( $title ) + 4;
            $title = '-- ' . $title . ' ';
        }

        return str_repeat( PHP_EOL, 2 ) . $title . str_repeat( '-', $len ) . PHP_EOL;
    }


    public static function textWrap( string $string, int $len = 120 ) : string
    {
        return wordwrap( $string, $len - 4, PHP_EOL . '    ' );
    }


    public static function fieldMissing( $message ) : string
    {
        $message = sprintf( 'FileMaker returned error %d - %s', $message->code, $message->message );

        return $message . self::sep( 'Field is missing' ) . self::textWrap( '
    FileMaker does not specify which field, so if you are sure that the field exists:

    - You may be trying to use soft deletes without the deleted_at field
    - You may be trying to sort by latest without the created_at field
    - You may be trying to get the last updated without the updated_at field' ) . self::sep();
    }

}
