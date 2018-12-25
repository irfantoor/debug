<?php

namespace IrfanTOOR;

use IrfanTOOR\Debug\Console;

/**
 * Debug, dump and trace while development
 */
class Debug
{
    /** @var array */
    private $styles = [
        'none' => null,
        'bold' => '1',
        'dark' => '2',
        'italic' => '3',
        'underline' => '4',
        'blink' => '5',
        'reverse' => '7',
        'concealed' => '8',

        'default' => '39',
        'black' => '30',
        'red' => '31',
        'green' => '32',
        'yellow' => '33',
        'blue' => '34',
        'magenta' => '35',
        'cyan' => '36',
        'light_gray' => '37',

        'dark_gray' => '90',
        'light_red' => '91',
        'light_green' => '92',
        'light_yellow' => '93',
        'light_blue' => '94',
        'light_magenta' => '95',
        'light_cyan' => '96',
        'white' => '97',

        'bg_default' => '49',
        'bg_black' => '40',
        'bg_red' => '41',
        'bg_green' => '42',
        'bg_yellow' => '43',
        'bg_blue' => '44',
        'bg_magenta' => '45',
        'bg_cyan' => '46',
        'bg_light_gray' => '47',

        'bg_dark_gray' => '100',
        'bg_light_red' => '101',
        'bg_light_green' => '102',
        'bg_light_yellow' => '103',
        'bg_light_blue' => '104',
        'bg_light_magenta' => '105',
        'bg_light_cyan' => '106',
        'bg_white' => '107',
    ];

    private $theme = [
        'info'    => ['light_cyan'],
        'warning' => ['bg_light_yellow', 'black'],
        'error'   => ['bg_light_red', 'white'],
        'success' => ['bg_green', 'white'],
    ];

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
            self::$terminal = true;

        self::$level = $level;

        if ($level < 3 && !self::$enabled)
            ob_start();

        if ($level > 2)
            error_reporting(E_ALL);
        elseif ($level)
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


    function apply($style, $txt)
    {
        $style = isset($this->theme[$style]) ? $this->theme[$style] : $style;

        if (!is_array($style))
            $style = [$style];

        foreach($style as $s) {
            $ss = $this->styles[$s] ?: null;
            if ($ss)
                echo "\033[{$ss}m";
        }

        echo $txt;
        echo "\033[0m";
    }

    /**
     * Write a line or a group of lines to output
     * 
     * @param mixed $text can be string or an array of strings
     * @param mixed $style can be null, a style code as string or an array of strings.
     */ 
    function write($text='', $style='none') {
        if (is_array($text)) {
            $max = 0;
            foreach($text as $txt) {
                $max = max($max, strlen($txt));
            }
            $outline = str_repeat(' ', $max + 4);
            $this->writeln($outline, $style);
            foreach($text as $txt) {
                $len = strlen($txt);
                $pre_space = str_repeat(' ', 2);
                $post_space = str_repeat(' ', $max+2 - $len);
                $this->writeln($pre_space . $txt . $post_space, $style);
            }
            $this->writeln($outline, $style);
        } else {
            echo $this->apply($style, $text);
        }
    }

    /**
     * Write a line or a group of lines to output and an End of Line finally.
     * 
     * @param mixed $text can be string or an array of strings
     * @param mixed $style can be null, a style code as string or an array of strings.
     */ 
    function writeln($text='', $style='none') {
        echo $this->write($text, $style);
        echo PHP_EOL;
    }    

    /**
     * Dumps the passed object or variable on console or browser
     *
     * @param mixed $var
     * @param bool  $trace (should print the trace?)
     */
    static function dump($var, $trace = true)
    {
        $di = self::getInstance();

        if (!self::$level)
            return;

        if (self::$terminal) {
            $di->writeln(print_r($var, 1), 'light_cyan');
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
        $di = self::getInstance();

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
                    $di->writeln( $txt, 'dark');
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

        $di = self::getInstance();

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
            $di->writeln([$class . ': ' . $type . $message], ['white','bg_red']);
            $di->writeln('file ' . $file . ', line: ' . $line , 'cyan');
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
