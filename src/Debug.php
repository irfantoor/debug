<?php

namespace IrfanTOOR {

use Exception;
use IrfanTOOR\Console;

/**
 * Debug, dump and trace while development
 */
class Debug
{
    const NAME        = "Irfan's Debug";
    const DESCRIPTION = "Debug, dump and trace while development";
    const VERSION     = "0.4";

    /**
     * Exception handler to intercept any exceptions
     * Note: its not called dreclty but is used by Debug class
     */
    static function exceptionHandler($e) {
        if (!self::$level)
            return;

        self::$exception_handler_called = true;

        if (self::$level < 2)
            ob_get_clean();

        if (is_object($e)) {
            $class   = 'Exception';
            $message = $e->getMessage();
            $file    = self::limitPath($e->getFile());
            $line    = $e->getLine();
            $type    = '';
            $trace   = $e->getTrace();
        } else {
            $class = 'Error';
            extract($e);
            $type .= ' - ';
        }

        if (self::$terminal) {
            $c = self::$console;

            $c->writeln(' ', 'bg_light_red');
            $c->write(' ', 'bg_light_red');
            $c->writeln(' ' . $class . ': ' . $type . $message);
            $c->writeln(' ', 'bg_light_red');

            $c->writeln('line: ' . $line . ', file: ' . $file , 'blue');

            if (self::$level > 1) {
                self::trace();
            }
        } else {
            $body =
            '<div style="border-left:4px solid #d00; padding:6px;">' .
                '<code style="color:#d00">' . $class . ': ' . $type . $message . '</code><br>' .
                '<code style="color:#36c"> line: ' . $line . ', file: ' . $file . '</code>' .
            '</div>';

            ob_start();
                echo $body;

                if (self::$level > 1) {
                    self::trace(debug_backtrace()[0]['args'][0]->getTrace());
                }

            $contents = ob_get_clean();
            echo $contents;
            exit;
        }

    }
}

}

namespace {
    use IrfanTOOR\Debug;

    function d($v)
    {
        Debug::dump($v);
    }

    function dd($v)
    {
        Debug::dump($v);
        exit;
    }
}
