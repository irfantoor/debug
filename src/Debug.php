<?php

namespace IrfanTOOR;

use Exception;
use IrfanTOOR\Console;
use IrfanTOOR\Debug\Constants;

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
     * Used to lock the Debug class against modification of level
     *
     * @var bool
     */
    protected static $locked = false;

    /**
     * debug level : 0,1,2,3 ...
     *
     * @var int
     */
    protected $level    = null;

    /**
     * If we are on a terminal (and not a web app)
     *
     * @var bool
     */
    protected $terminal = false;

    /**
     * IrfanTOOR\Console
     *
     * @var bool
     */
    protected $console;

    /**
     * indicates if exceptionHandler was called
     *
     * @var bool
     */
    protected $exception_handler_called = false;

    /**
     * Constructs the Debug instance
     *
     * @param int $level
     */
    public function __construct($level = 1)
    {
        if (self::$instance)
            throw new Exception("Use Debug::getInstance, cannot create a new instance", 1);

        # init instance
        self::$instance = $this;

        # init
        $this->level    = $level;
        $this->terminal = (PHP_SAPI === 'cli') ? true : false;
        $this->console  = new Console;

        # adjust error reporting
        if ($level === 0) {
            error_reporting(0);
        } else {
            if ($level >= 3) {
                error_reporting(E_ALL && ~E_NOTICE);    
            } elseif($level > 2) {
                error_reporting(E_ALL);
            } else {
                ob_start();  
            }
        }

        register_shutdown_function([$this, 'shutdown']);
        set_exception_handler(function($obj){
            $this->exceptionHandler($obj);
            exit;
        });
    }

    static function getInstance(Int $level = 0)
    {
        return self::$instance ?: new self($level);
    }

    /**
     * Static (and simple) way to enable Debug mode with a level
     *
     * @param int $level
     */
    static function enable(Int $level = 0)
    {
        if (!self::$instance)
            self::$instance  = new self($level);

        if (!self::$locked)
            self::$instance->level = $level;
    }

    /**
     * Debug level can not be changed when once locked
     */
    static function lock()
    {
        self::$locked = true;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function getVersion()
    {
        return Constants::VERSION;
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
     * Dumps the passed object or variable on console or browser
     *
     * @param mixed $var
     * @param bool  $trace (should print the trace?)
     */
    static function dump($var, $trace = true)
    {
        $d = Debug::getInstance();

        if ($d->level < 1) {
            return;
        }

        if ($d->terminal) {
            $c = $d->console;

            if (!is_array($var)) {
                $c->writeln(print_r($var, 1), 'blue');
            } else {
                $lines = explode("\n", print_r($var, 1));
                foreach ($lines as $line) {
                    preg_match_all('|(.*)\[(.*)\] \=\>(.*)|', $line, $m);
                    
                    if (!isset($m[0][0])) {
                        $c->writeln($line);
                    } else {
                        $c->write($m[1][0] . '[', 'blue');
                        $c->write($m[2][0], 'light_red');
                        $c->write('] =>', 'blue');
                        $c->writeln($m[3][0], 'blue');
                    }
                }
            }

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
                    $this->console->writeln( $txt, 'dark');
                else
                    echo '<code style="color:#999">' . $txt . '</code><br>';
            }
        }
    }

    /**
     * Exception handler to intercept any exceptions
     * Note: its not called dreclty but is used by Debug class
     */
    function exceptionHandler($e) {
        ob_get_clean();
        $this->exception_handler_called = true;

        if (!$this->level)
            return;

        if (is_object($e)) {
            $class   = 'Exception';
            $message = $e->getMessage();
            $file    = $this->limitPath($e->getFile());
            $line    = $e->getLine();
            $type    = '';
            $trace   = $e->getTrace();
        } else {
            $class = 'Error';
            extract($e);
            $type .= ' - ';
        }

        if ($this->terminal) {
            $c = $this->console;

            $c->writeln(' ', 'bg_light_red');
            $c->write(' ', 'bg_light_red');
            $c->writeln(' ' . $class . ': ' . $type . $message);
            $c->writeln(' ', 'bg_light_red');

            $c->writeln('file ' . $file . ', line: ' . $line , 'dark');
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
     * Called at the end of execution to dump the timing or other details
     * Note: This function is not called directly, but is registered by the
     *       Debug class
     */
    function shutdown()
    {
        if ($this->exception_handler_called) {
            return;
        }

        if (ob_get_level() > 0)
            ob_get_flush();

        if ($this->level) {
            echo ($this->terminal ? PHP_EOL : '<br>');
            $t  = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
            $te = sprintf(' %.2f mili sec.', $t * 1000);
            self::dump('Elapsed time: ' . $te, 0);
        }

        if ($this->level > 1) {
            $files = get_included_files();
            self::dump($files, 0);
        }
    }
}
