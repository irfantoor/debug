<?php

namespace IrfanTOOR;

use IrfanTOOR\Console;

/**
 * Debug, dump and trace while development
 */
class Debug
{
    protected static $instance = null;
    protected static $enabled  = false;
    protected static $error    = null;
    protected static $level    = 0;
    protected static $terminal = null;

    public function __construct()
    {
        register_shutdown_function([$this, 'shutdown']);
        set_exception_handler(function($obj){
            $this->exceptionHandler($obj);
        });

        self::$instance = $this;
    }

    public static function getInstance()
    {
        if (self::$instance)
            return self::$instance;

        return new static;
    }

    /**
     * Enable Debug with a level
     *
     * @param int $level 0,1,2 or 3
     *                   0 -- Debug::dump is not processed
     *                   1 -- elapsed time is returned
     *                   2 -- included files are listed in the dump
     *                   3 -- more details ...
     */
    static function enable($level = 1)
    {
        # make sure theat the class gets initialized!
        $di = self::getInstance();

        if (isset($_SERVER['TERM']) && !isset(self::$terminal))
            self::$terminal = new Console();

        self::$level = $level;

        if ($level < 3 && !self::$enabled)
            ob_start();

        if ($level>2)
            error_reporting(E_ALL);
        elseif($level)
            error_reporting(E_ALL && ~E_NOTICE);
        else
            error_reporting(0);

        self::$enabled = true;
    }

    /**
     * Returns the current Debug level
     *
     * @return int
     */
    static function level()
    {
        return static::$level;
    }

    /**
     * Dumps the passed object or variable on console or browser
     *
     * @param mixed $var
     * @param bool  $trace (should print the trace?)
     */
    static function dump($var, $trace = true)
    {
        if (!self::$level)
            return;

        if (self::$terminal) {
            self::$terminal->writeln(print_r($var, 1), 'light_cyan');
        } else {
            $txt = preg_replace('/\[(.*)\]/u', '[<span style="color:#d00">$1</span>]', print_r($var, 1));
            echo '<pre style="color:blue">' . $txt . "</pre>";
        }

        if ($trace)
            self::trace();
    }

    /**
     * Returns the debug trace in the printable format
     *
     * @param null|array $trace
     *
     * @return string
     */
    static function trace($trace = null)
    {
        $trace = $trace ?: debug_backtrace();
        foreach( $trace as $er) {
            $func = isset($er['function'])? $er['function']: '';
            $file = isset($er['file']) ? $er['file'] : '';

            # last two sections of the path
            if ($file) {
                $file  = self::limitPath($file);
                $line  = isset($er['line'])? $er['line']: '';
                $class = isset($er['class'])? $er['class']: '';
                if ($class == 'IrfanTOOR\Debug' && $func=='trace')
                    continue;

                $ftag = ($class != '') ? $class . '=>' . $func . '()' : $func . '()';
                $txt = '-- file: ' . $file . ', line: ' . $line . ', ' . $ftag;
                if (self::$terminal)
                    self::$terminal->writeln( $txt, 'color_111');
                else
                    echo '<code style="color:#999">' . $txt . '</code><br>';
            }
        }
    }

    /**
     * limit the path for security reasons
     *
     * @param string $file
     */
    static function limitPath($file)
    {
        $x = explode('/', $file);
        $l = count($x);
        return ($l>1) ? $x[$l-2] . '/' . $x[$l-1] : $file;
    }

    /**
     * Exception handler to intercept any exceptions
     * Note: its not called dreclty but is used by Debug class
     */
    function exceptionHandler($e) {
        ob_get_clean();

        self::$error = true;

        if (!self::$level)
            return;

        if (is_object($e)) {
            $class   = 'Exception';
            $message = $e->getMessage();
            $file    = self::limitPath($e->getFile());
            $line    = $e->getLine();
            $type    = '';
            $trace   = $e->getTrace();
        }
        else {
            $class = 'Error';
            extract($e);
            $type .= ' - ';
        }

        if (self::$terminal) {
            self::$terminal->writeln([$class . ': ' . $type . $message], ['white','bg_red']);
            self::$terminal->writeln('file ' . $file . ', line: ' . $line , 'cyan');
        } else {
            $body =
            '<div style="border-left:4px solid #d00; padding:6px;">' .
                '<div style="color:#d00">' . $class . ': ' . $type . $message . '</div><code style="color:#999">file: ' .
                $file . ', line: ' . $line .
                '</code></div>';

            echo $body;
        }
        self::trace($trace);
    }

    /**
     * Called at the end of execution to dump the timing or other details
     * Note: This function is not called directly, but is registered by the
     *       Debug class
     */
    function shutdown()
    {
        if (self::$error)
            return;

        if (ob_get_level() > 0)
            ob_get_flush();

        if (self::$level) {
            echo (self::$terminal ? PHP_EOL : '<br>');
            $t  = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
            $te = sprintf(' %.2f mili sec.', $t * 1000);
            # self::dump('elapsed time: ' . $te, 0);
            self::dump('Elapsed time: ' . $te, 0);
        }

        if (self::$level > 1) {
            $files = get_included_files();
            self::dump($files, 0);
        }
    }
}
