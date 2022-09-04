<?php declare(strict_types=1);

namespace Labrador\Http\Test\Helper;

use php_user_filter;

final class StreamBuffer extends php_user_filter {

    private static string $buffer = '';
    private static $streamFilter;

    public static function register() : void {
        if (!in_array('test.stream.buffer', stream_get_filters())) {
            $streamRegistered = stream_filter_register('test.stream.buffer', StreamBuffer::class);
            assert($streamRegistered === true);
        }
        self::$streamFilter = stream_filter_append(STDOUT, 'test.stream.buffer');
        assert(is_resource(self::$streamFilter));
    }

    public static function unregister() : void {
        $streamRemoved = stream_filter_remove(self::$streamFilter);
        assert($streamRemoved === true);
        self::clearBuffer();
    }

    public static function getBuffer() : string {
        return self::$buffer;
    }

    public static function clearBuffer() : void {
        self::$buffer = '';
    }

    public function filter($in, $out, &$consumed, bool $closing) : int {
        while ($bucket = stream_bucket_make_writeable($in)) {
            self::$buffer .= $bucket->data;
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_FEED_ME;
    }

}