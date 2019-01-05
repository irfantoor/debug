<?php

namespace IrfanTOOR;

/**
 * Debug, dump and trace while development
 */
class Debug
{
    /**
     * Contains a pointer to the only instance of this class
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Should we apply the colors
     *
     * @var bool
     */
    protected $apply = true;

    /**
     * Colors used by this class
     *
     * @var array
     */    
    protected $theme = [
        'error' => '41', # red background
        'dump'  => '34', # blue forground
        'trace' => '2',  # dark
    ];

    /**
     * True if debug level > 0
     *
     * @var bool
     */
    protected $enabled  = false;

    /**
     * If exception handler was called
     *
     * @var object
     */    
    protected $error    = false;

    /**
     * debug level : 0,1,2,3 ...
     *
     * @var int
     */
    protected $level    = null;

    /**
     * If we are on a terminal (and not a web app)
     *
     * @var object
     */    
    protected $terminal = false;

    /**
     * Constructs the Debug instance
     *
     * @param int $level
     */
    public function __construct($level = 1)
    {
        $this->level = $level;

        if (PHP_SAPI === 'cli')
            $this->terminal = true;

        if ($level === 0) {
            error_reporting(0);
        } else {
            $this->enabled = true;
            if ($level >= 3) {
                error_reporting(E_ALL && ~E_NOTICE);    
            } elseif($level > 2) {
                error_reporting(E_ALL);    
            } else {
                ob_start();  
            }
        }

        $this->apply = function_exists('posix_isatty') && @posix_isatty(STDOUT);

        register_shutdown_function([$this, 'shutdown']);
        set_exception_handler(function($obj){
            $this->exceptionHandler($obj);
        });
    }

    /**
     * Static (and simple) way to enable Debug mode with a level
     *
     * @param int $level
     */
    static function enable($level)
    {
        if (!self::$instance)
            self::$instance = new Debug($level);
    }

    /**
     * Dumps the passed object or variable on console or browser
     *
     * @param mixed $var
     * @param bool  $trace (should print the trace?)
     */
    static function dump($var, $trace = true)
    {
        if (!self::$instance)
            self::$instance = new Debug(0);

        $d = self::$instance;

        if (!$d->level)
            return;

        if ($d->terminal) {
            $d->_writeln(print_r($var, 1), 'dump');
        } else {
            $txt = preg_replace('/\[(.*)\]/u', '[<span style="color:#d00">$1</span>]', print_r($var, 1));
            echo '<pre style="color:blue">' . $txt . "</pre>";
        }

        if ($trace)
            $d->_trace();
    }

    /**
     * Prints the debug trace in the printable format
     *
     * @param null|array $trace
     *
     * @return string
     */
    private function _trace($trace = null)
    {
        $trace = $trace ?: debug_backtrace();
        foreach( $trace as $er) {
            $func = isset($er['function'])? $er['function']: '';
            $file = isset($er['file']) ? $er['file'] : '';

            # last two sections of the path
            if ($file) {
                $file  = $this->limitPath($file);
                $line  = isset($er['line'])? $er['line']: '';
                $class = isset($er['class'])? $er['class']: '';
                if ($class == 'IrfanTOOR\Debug' && $func=='_trace')
                    continue;

                $ftag = ($class != '') ? $class . '=>' . $func . '()' : $func . '()';
                $txt = '-- file: ' . $file . ', line: ' . $line . ', ' . $ftag;
                if ($this->terminal)
                    $this->_writeln( $txt, 'trace');
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
    function limitPath($file)
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

        $this->error = true;

        if (!$this->level)
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

        if ($this->terminal) {
            $this->_writeln([$class . ': ' . $type . $message], 'error');
            $this->_writeln('file ' . $file . ', line: ' . $line , 'dump');
        } else {
            $body =
            '<div style="border-left:4px solid #d00; padding:6px;">' .
                '<div style="color:#d00">' . $class . ': ' . $type . $message . '</div><code style="color:#36c">file: ' .
                $file . ', line: ' . $line .
                '</code></div>';

            echo $body;
        }
        $this->_trace($trace);
    }

    /**
     * Writes $text to console with a selected style
     *
     * @param string $text
     * @param string $style 'dump', 'trace' or 'error'
     */
    private function _writeln($text, $style)
    {
        if (is_array($text)) {
            $max = 0;
            foreach($text as $txt) {
                $max = max($max, strlen($txt));
            }
            $outline = str_repeat(' ', $max + 4);
            $this->_writeln($outline, $style);
            foreach($text as $txt) {
                $len = strlen($txt);
                $pre_space = str_repeat(' ', 2);
                $post_space = str_repeat(' ', $max+2 - $len);
                $this->_writeln($pre_space . $txt . $post_space, $style);
            }
            $this->_writeln($outline, $style);
        } else {
            if ($this->apply) {
                echo "\033[" . $this->theme[$style] . 'm' . $text . "\033[0m";
            } else {
                echo $text;    
            }
        }

        echo PHP_EOL;
    }

    /**
     * Called at the end of execution to dump the timing or other details
     * Note: This function is not called directly, but is registered by the
     *       Debug class
     */
    function shutdown()
    {
        if ($this->error)
            return;

        if (ob_get_level() > 0)
            ob_get_flush();

        if ($this->level) {
            echo ($this->terminal ? PHP_EOL : '<br>');
            $t  = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
            $te = sprintf(' %.2f mili sec.', $t * 1000);
            # self::dump('elapsed time: ' . $te, 0);
            $this->dump('Elapsed time: ' . $te, 0);
        }

        if ($this->level > 1) {
            $files = get_included_files();
            $this->dump($files, 0);
        }
    }
}
