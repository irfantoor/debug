<?php

require dirname(__DIR__) .'/vendor/autoload.php';

use IrfanTOOR\Debug;

Debug::enable(4);
Debug::lock();

/** ---------- {{{ CHECKPOINT **/ checkpoint(); /** CHECKPOINT }}} ---------- **/


class test
{
    function __construct()
    {
        $this->hello();
        $this->test();
    }

    function test()
    {
        /** ---------- {{{ CHECKPOINT **/ checkpoint(); /** CHECKPOINT }}} ---------- **/
        $this->hello();
    }

    function hello()
    {
        /** ---------- {{{ CHECKPOINT **/ checkpoint(); /** CHECKPOINT }}} ---------- **/
    }
}


$t = new test();
