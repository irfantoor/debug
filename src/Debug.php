<?php

namespace IrfanTOOR {

use Exception;
use IrfanTOOR\Console;
use IrfanTOOR\Debug\Constants;

/**
 * Debug, dump and trace while development
 */
class Debug
{
    const NAME        = "Irfan's Debug";
    const DESCRIPTION = "Debug, dump and trace while development";
    const VERSION     = "0.4.1";

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
    protected static $level    = 0;

    /**
     * If we are on a terminal (and not a web app)
     *
     * @var bool
     */
    protected static $terminal = false;

    /**
     * IrfanTOOR\Console
     *
     * @var bool
     */
    protected static $console;

    /**
     * indicates if exceptionHandler was called
     *
     * @var bool
     */
    protected static $exception_handler_called = false;

    /**
     * Constructs the Debug instance
     *
     * @param int $level
     */
    public function __construct()
    {
        if (self::$instance)
            throw new Exception("Use Debug::getInstance, cannot create a new instance", 1);

        if (($pos = strpos(__DIR__, 'vendor')) === false) {
            $pos = strpos(__DIR__, 'src');
        }

        define('DEBUG_ROOT', substr(__DIR__, 0, $pos));

        register_shutdown_function(function() {
            if (self::$exception_handler_called) {
                return;
            }

            // if (ob_get_level() > 1)
            //     ob_get_flush();

            if (self::$level) {
                self::dump(
                    sprintf(
                        "time elapsed: %04.2f mili sec.", 
                        (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000
                    ), 
                    false
                );
            }
            
            if (self::$level > 2) {
                $files = [];
                $i = 1;
                foreach (get_included_files() as $file) {
                    $files[$i++] = self::limitPath($file);
                }
                self::dump($files, false);
            }
        });

        set_exception_handler(function($obj){
            self::exceptionHandler($obj);
            exit;
        });

        # init instance
        self::$instance = $this;
    }

    static function getInstance()
    {
        return self::$instance ?: new self();
    }

    /**
     * Static (and simple) way to enable Debug mode with a level
     *
     * @param int $level
     */
    static function enable(Int $level = 0)
    {
        if (self::$locked) {
            return;
        }

        self::getInstance();

        self::$level = $level;

        # init
        if (PHP_SAPI === 'cli') {
            self::$terminal = true;
            self::$console  = new Console;
        }

        # adjust error reporting
        switch ($level)
        {
            case 0:
                error_reporting(0);
                break;

            case 1:
                if (ob_get_level() === 0)
                    ob_start();
                break;

            case 2:
                error_reporting(E_ALL && ~E_NOTICE);
                break;

            default:
                error_reporting(E_ALL);
        }
    }

    /**
     * Debug level can not be changed when once locked
     */
    static function lock()
    {
        self::$locked = true;
    }

    static function getLevel()
    {
        return self::$level;
    }

    /**
     * limit the path for security reasons
     *
     * @param string $file
     */
    static function limitPath($file)
    {
        return str_replace(DEBUG_ROOT, '', $file);
    }

    private static function _prepare($value)
    {
        if (is_array($value)) {
            $r = [];
            foreach ($value as $k => $v) {
                $r[$k] = self::_prepare($v);
            }
            return $r;
        } elseif (is_string($value)) {
            if ($value === '') {
                return '""';
            } else {
                return htmlspecialchars($value);
            }
        } else {
            return $value;
        }
    }

    /**
     * Dumps the passed object or variable on console or browser
     *
     * @param mixed $v
     * @param bool  $trace (should print the trace?)
     */
    static function dump($v, $trace = true)
    {
        if (self::$level < 1) {
            return;
        }

        $v = self::_prepare($v);

        if (self::$terminal) {
            $c = self::$console;

            if (!is_array($v) && !is_object($v) && !is_string($v)) {
                $v = json_encode(
                    $v,
                    JSON_UNESCAPED_SLASHES | 
                    JSON_UNESCAPED_UNICODE
                );

                $c->writeln(print_r($v, 1), 'red');
            } else {
                $lines = explode("\n", print_r($v, 1));
                foreach ($lines as $line) {
                    preg_match_all('|(.*)\[(.*)\] \=\>(.*)|', $line, $m);
                    
                    if (!isset($m[0][0])) {
                        if ($line === '""') {
                            $c->writeln($line, 'red');
                        } else {
                            $c->writeln($line);
                        }
                    } else {
                        $c->write($m[1][0] . '[', 'blue');
                        $c->write($m[2][0], 'light_red');
                        $c->write('] =>', 'blue');
                        $c->writeln($m[3][0], 'blue');
                    }
                }
            }

        } else {
            if (is_array($v) || is_object($v)) {
                $v = preg_replace(
                    '|\[(.*)\]|Us', 
                    '[<span style="color:#d00">$1</span>]', 
                    print_r($v, 1)
                );
            } elseif (is_string($v)) {
                if ($v === "") {
                    $v = '""';
                }
            } else {
                $v = '<span style="color:#d00">' . json_encode(
                    $v,
                    JSON_UNESCAPED_SLASHES | 
                    JSON_UNESCAPED_UNICODE
                ) . '</span>';
            }

            echo '<pre><code style="color:#36c">' . $v . '</code></pre>';
        }

        if ($trace)
            self::_trace();
    }

    /**
     * Prints the debug trace in the printable format
     *
     * @param null|array $trace
     *
     * @return string
     */
    private static function _trace($trace = null)
    {
        $trace = $trace ?: debug_backtrace();
        foreach( $trace as $t) {
            if (
                !isset($t['file']) ||
                strpos($t['file'], 'src/Debug.php') !== false
            ) {
                continue;
            }

            // $t['file'] = self::limitPath($t['file']);
            $func = isset($t['function'])? $t['function']: '';
            $file = isset($t['file']) ? $t['file'] : '';

            # last two sections of the path
            if ($file) {
                $file  = self::limitPath($file);
                $line  = isset($t['line'])? $t['line']: '';
                $class = isset($t['class'])? $t['class']: '';
                if ($class == 'IrfanTOOR\Debug' && $func=='_trace')
                    continue;

                $ftag = ($class != '') ? $class . '=>' . $func . '()' : $func . '()';
                $txt = 'line: ' . $line . ', file: ' . $file . ', ' . $ftag;
                if (self::$terminal) {
                    self::$console->writeln( $txt, 'dark');
                } else {
                    echo '<code style="color:#999">' . $txt . '</code><br>';
                }
            }
        }
    }

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
                self::_trace();
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
                    self::_trace(debug_backtrace()[0]['args'][0]->getTrace());
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
