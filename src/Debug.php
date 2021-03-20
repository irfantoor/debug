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
        const VERSION     = "0.6.2";

        /** @var self */
        protected static $instance = null;

        /** @var Terminal -- it can write both to a Cli or Html client*/
        protected static $terminal;

        /** @var int Debug level which could be  0, 1, 2, 3, 4 */
        protected static $level = 0;

        /** @var bool -- wether the level has been locked */
        protected static $locked = false;

        /**
         * Debug constructor
         *
         * @param int $level
         */
        function __construct()
        {
            if (self::$instance)
                return self::$instance;

            self::$instance = $this;

            if (($pos = strpos(__DIR__, 'vendor')) === false)
                $pos = strpos(__DIR__, 'src');

            define('DEBUG_ROOT', substr(__DIR__, 0, $pos));

            self::$terminal = new Terminal();

            set_exception_handler([Debug::class, "exceptionHandler"]);
            set_error_handler([Debug::class, "errorHandler"]);
            register_shutdown_function([Debug::class, "shutdown"]);
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
         * @param int $level Debug level 0 to 4, default is 1
         */
        public static function enable(int $level = 1)
        {
            if (self::$locked)
                return;

            self::getInstance();
            self::$level = $level;

            # errors will be reported by the Debug only uptill level 3
            error_reporting(($level < 4) ? 0 : E_ALL);
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
         * Displays the exception
         */
        public static function exceptionHandler($e)
        {
            if (!self::$level)
                return;

            self::$terminal->write("| ", "light_red, bold");
            self::$terminal->writeln("Exception: " . $e->getMessage() . " ", "light_red");

            if (self::$level > 1) {
                self::$terminal->write("| ", "light_red, bold");
                self::$terminal->writeln(
                    "line: " . ($e->getLine() ?? '') .
                    ", file: " . ($e->getFile() ?? ''),
                    "info"
                );
            }

            if (self::$level > 2) {
                self::trace($e->getTrace());
            }
        }

        /**
         * Display the error
         */
        public static function errorHandler($type, $message, $file, $line = null)
        {
            if (!self::$level)
                return;

            self::$terminal->write("| ", "light_red, bold");
            self::$terminal->writeln("Error (" . $type  . "): " . $message . " ", "light_red");

            if (self::$level > 1) {
                self::$terminal->write("| ", "light_red, bold");
                self::$terminal->writeln(
                    "line: " . $line .
                    ", file: " . $file,
                    "info"
                );
            }

            if (self::$level > 2)
                self::trace();
        }

        /**
         * Displays the errors, if debug level permits, causing the shutdown
         */
        public static function shutdown()
        {
            if (!self::$level)
                return;

            while ($e = error_get_last()) {
                self::errorHandler($e['type'], $e['message'], $e['file'], $e['line']);
                error_clear_last();
            }
        }

        /**
         * Dumps the passed variable in a readable manner
         *
         * @param mixed $var        The variable to be dumped
         * @param bool  $show_trace Dumps the trace if true
         */
        public static function dump($var, bool $show_trace = true)
        {
            if (!self::$level)
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
         * Dumps the debug backtrace or the provided trace
         */
        protected static function trace()
        {
            if (self::$level < 2)
                return;

            $trace = debug_backtrace();
            $color = "dark";

            foreach ($trace as $t) {
                if (!isset($t['file']))
                    continue;

                if (strpos($t['file'] ?? "", 'src/Debug.php') !== false)
                    continue;

                $file  = self::limitPath($t['file'] ?? "");
                $line  = $t['line'] ?? '';
                $class = $t['class'] ?? '';
                $func = $t['function'] ?? '';
                $ftag = ($class != '') ? $class . '=>' . $func . '()' : $func . '()';
                $text = 'line: ' . $line . ', file: ' . $file;

                self::$terminal->write($text, $color);
                self::$terminal->writeln(' -- ' . $ftag, 'dark');
                $color = "light_gray";

                if (self::$level < 3)
                    break;
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

    /**
     * Logs a numbered checkpoint,  so that the flow of execution be checked.
     *
     * Place checkpoint tag/function at control points to verify the flow. Though it
     * does not print any thing when the debug level is set to 0 (i.e. in production),
     * but it is strongly recommended to remove the tags, when once the flow has been
     * corrected, for the sake of performance.
     *
     * Note: An example tag is given bellow, note that The '// ' is not part of the tag
     */
    // /** ---------- {{{ CHECKPOINT **/ checkpoint(); /** CHECKPOINT }}} ---------- **/
    function checkpoint()
    {
        if (!debug::getLevel())
            return;

        static $n = 0;
        $n++;
        $trace = debug_backtrace();
        $t = $trace[1];

        $d = Debug::getInstance();
        $o = $d->getOutput();

        $o->writeln(
            sprintf(" %3d| %3d| %s, ", $n, $trace[0]['line'], $trace[0]['file']) .
            $t['class'] . "::" . $t['function'] . "()"
            , "bg_black, white"
        );
    }
}
