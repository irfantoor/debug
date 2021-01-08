<?php

/**
 * IrfanTOOR\Debug
 * php version 7.3
 *
 * @author    Irfan TOOR <email@irfantoor.com>
 * @copyright 2021 Irfan TOOR
 */

namespace IrfanTOOR
{
    use IrfanTOOR\Terminal;
    use Exception;

    # Debug class helps debugging, while developement
    class Debug
    {
        const NAME        = "Debug";
        const DESCRIPTION = "Debug your development of PHP";
        const VERSION     = "0.6.1";

        /** @var self */
        protected static $instance = null;        

        /** @var Terminal -- it can write both to a Cli or Html client*/
        protected static $terminal;

        /** @var int Debug level which could be  0, 1, 2, 3 */
        protected static $level;

        /** @var bool -- wether the level has been locked */
        protected static $locked = false;

        /**
         * Debug constructor
         *
         * @param int $level
         */
        function __construct(int $level = 1)
        {
            if (self::$instance)
                return self::$instance;

            self::$instance = $this;

            if (($pos = strpos(__DIR__, 'vendor')) === false)
                $pos = strpos(__DIR__, 'src');

            define('DEBUG_ROOT', substr(__DIR__, 0, $pos));

            self::$terminal = new Terminal();
            self::enable($level);
        }

        /**
         * Returns the instance
         *
         * @return self
         */
        static function getInstance()
        {
            return self::$instance ?: new self();
        }

        /**
         * Returns the output resource
         *
         * @returns Terminal
         */
        public static function getOutput()
        {
            return self::$terminal;
        }

        /**
         * Enables Debugging
         *
         * @param int $level Debug level 0 to 3, default is 1
         */
        public static function enable(int $level = 1)
        {
            if (self::$locked)
                return;

            self::getInstance();
            self::$level = $level;
            
            # errors will be reported by the Debug
            error_reporting(0);

            if (self::$level) {
                # exception handler
                set_exception_handler(function ($e) {
                    self::$terminal->write("| ", "light_red, bold");
                    self::$terminal->writeln(" Error: " . $e->getMessage() . " ", "light_red");

                    if (self::$level > 1) {
                        self::$terminal->writeln(
                            "line: " . ($e->getLine() ?? '') .
                            ", file: " . ($e->getFile() ?? ''),
                            "info"
                        );
                    }

                    if (self::$level > 2) {
                        self::trace($e->getTrace());
                    }
                });

                # verify that the
                register_shutdown_function(function () {
                    if (!Debug::getLevel())
                        return;

                    $e = error_get_last();
                    if (!$e)
                        return;

                    self::$terminal->writeln();
                    self::$terminal->write("| ", "light_red, bold");
                    self::$terminal->writeln("Error (" . $e['type']  . "): " . $e['message'] . " ", "light_red");

                    if (self::$level > 1) {
                        self::$terminal->write("| ", "light_red, bold");
                        self::$terminal->writeln(
                            "line: " . $e['line'] .
                            ", file: " . $e['file'],
                            "info"
                        );
                    }
                });
            }
        }

        /**
         * Locks the debug level, so that it can not be modified later
         */
        public static function lock()
        {
            self::$locked = true;
        }

        /**
         * Returns the debug level
         *
         * @return int
         */
        public static function getLevel(): int
        {
            return self::$level;
        }

        /**
         * limit the path for security/effitiancy reasons
         *
         * @param string $file
         */
        static function limitPath($file)
        {
            return str_replace(DEBUG_ROOT, '', $file);
        }

        /**
         * Prepare the $var to be printed
         *
         * @param mixed $var Variable to be printed
         */
        protected static function prepare($var)
        {
            if (is_array($var)) {
                foreach ($var as $k => $v)
                    $var[$k] = self::prepare($v);

                return $var;
            }

            if (is_object($var))
                return $var;
            else
                return json_encode($var);
        }

        /**
         * Dumps the passed variable in a readable manner
         *
         * @param mixed $var        The variable to be dumped
         * @param bool  $show_trace Dumps the trace if true
         */
        public static function dump($var, bool $show_trace = true)
        {
            if (self::$level < 1)
                return;

            $var = self::prepare($var);
            $text = print_r($var, true);

            # hack to convert the color of text inside square brackets [] to red
            if ((PHP_SAPI === 'cli'))
                $text = preg_replace('|\[(.*)\]|U', '['."\033[31m".'$1' . "\033[36m" . ']', $text);
            else
                $text = preg_replace('|\[(.*)\]|U', '[<span style="color:#d00">$1</span>]', $text);

            self::$terminal->writeln($text, "info");

            if ($show_trace)
                self::trace();
        }

        /**
         * Dumps the backtrace
         */
        protected static function trace()
        {
            $trace = debug_backtrace();
            $color = "info";

            foreach ($trace as $t) {
                if (!isset($t['file']))
                    continue;
                
                if (strpos($t['file'], 'src/Debug.php') !== false)
                    continue;

                $file  = self::limitPath($t['file']);
                $line  = $t['line'] ?? '';
                $class = $t['class'] ?? '';
                $func = $t['function'] ?? '';
                $ftag = ($class != '') ? $class . '=>' . $func . '()' : $func . '()';
                $text = 'line: ' . $line . ', file: ' . $file;

                self::$terminal->write($text, $color);
                self::$terminal->writeln(' -- ' . $ftag, 'dark');
                $color = "light_gray";
            }
        }
    }
}

namespace
{
    use IrfanTOOR\Debug;

    /**
     * Dumps the passed variable
     *
     * @param mixed $var        The variable to be dumped
     * @param bool  $show_trace Dumps the trace if true
     */
    function d($var, bool $show_trace = true)
    {
        Debug::dump($var, $show_trace);
    }

    /**
     * Dumps the passed variable and dies
     *
     * @param mixed $var        The variable to be dumped
     * @param bool  $show_trace Dumps the trace if true
     */
    function dd($var, bool $show_trace = true)
    {
        Debug::dump($var, $show_trace);
        exit;
    }
}
