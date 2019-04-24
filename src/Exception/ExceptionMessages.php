<?php

namespace Hyyppa\FluentFM\Exception;

class ExceptionMessages
{
    /**
     * @param  string  $message
     *
     * @return string
     */
    protected static function baseMessage($message) : string
    {
        return sprintf('FileMaker returned error %d - %s', $message->code, $message->message);
    }

    /**
     * @param       $message
     * @param  array  $query
     *
     * @return string
     */
    public static function generic($message, array $query) : string
    {
        return self::format(self::sep(self::baseMessage($message))
                            .self::textWrap(
                '<fg=green>This is the payload that was sent to FileMaker:</>'.PHP_EOL.self::queryDump($query)
            ).self::sep());
    }

    /**
     * @param       $message
     * @param  array  $query
     *
     * @return string
     */
    public static function fieldMissing($message, array $query) : string
    {
        return self::format(self::sep(self::baseMessage($message))
                            .self::textWrap('FileMaker does not specify which field, so if you are sure that the field exists:

- You may be trying to use <fg=white>soft deletes</> without the <fg=white>`deleted_at`</> field.
- You may be trying to <fg=white>sort by latest</> without the <fg=white>`created_at`</> field.
- You may be trying to <fg=white>get last updated</> without the <fg=white>`updated_at`</> field.

<fg=green>Please review the payload that was sent to FileMaker:</>
    '.self::queryDump($query)).self::sep());
    }

    /**
     * @param       $message
     * @param  array  $query
     *
     * @return string
     */
    public static function fieldInvalid($message, array $query) : string
    {
        $dump = self::queryDump($query);
        $note = '';

        if (! stristr($dump, "'ids'")) {
            $note = PHP_EOL.PHP_EOL.'<fg=red;options=bold>
Note:: This payload does seem to be <fg=white;options=bold>missing the `id` field</>. This is likely the problem.
</>';
        }

        return self::format(self::sep(self::baseMessage($message))
                            .self::textWrap(
                'FileMaker did not specify which field, so here are some tips:

- This is often due to <fg=white>creating a record</> without the <fg=white>`id`</> field. 
- Ensure that you are including all fields that are <fg=white>defined as required</> by the FileMaker table.
- Ensure that you are not trying to add a duplicate value to a field <fg=white>defined as unique</>. 
- If you have a <fg=white>unique `id` field</>, make sure the id <fg=white>is not already set</>.

<fg=green>Please review the payload that was sent:</>'
                .PHP_EOL.$dump.$note
            ).self::sep());
    }

    /**
     * -- Generates a line like this, with a width of $len --------.
     *
     * @param  string  $title
     * @param  int  $len
     *
     * @return string
     */
    public static function sep(string $title = '', int $len = 120) : string
    {
        if ($title) {
            $len -= strlen($title) + 4;
            $title = '== <fg=white;options=bold>'.$title.'</> ';
        }

        return str_repeat(PHP_EOL, 2).'<fg=red;options=bold>'.$title.str_repeat('=', $len).'</>'.PHP_EOL;
    }

    /**
     * Wraps text to max width of $len, indents all lines 4 spaces.
     *
     * @param  string  $string
     * @param  int  $len
     *
     * @return string
     */
    public static function textWrap(string $string, int $len = 120) : string
    {
        return str_replace(PHP_EOL, PHP_EOL.'    ', wordwrap(PHP_EOL.$string, $len - 4, PHP_EOL));
    }

    /**
     * Strip CLI formatting if not in CLI.
     *
     * @param  string  $message
     *
     * @return string
     */
    protected static function format(string $message) : string
    {
        if (PHP_SAPI !== 'cli') {
            return preg_replace('/=+/', '=', strip_tags($message));
        }

        return $message;
    }

    /**
     * $creates = [
     *   'a' => 'square box',
     *   'formatted' => 'text dump',
     *   'of' => 'an array',
     *   'that' => 'looks',
     *   'like' => 'this',
     * ];.
     *
     * @param  array  $query
     *
     * @return string
     */
    protected static function queryDump(array $query) : string
    {
        $export = var_export($query, true);
        $export = preg_replace('/^([ ]*)(.*)/m', '  $1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(
            ["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/", '/NULL/'],
            [null, ']$1', ' => [', 'null'],
            $array
        );

        return PHP_EOL.'  <fg=white>$payload = '.implode(PHP_EOL, array_filter(['['] + $array)).';</>';
    }
}
