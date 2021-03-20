<?php

use IrfanTOOR\Test;
use IrfanTOOR\Debug;

class DebugTest extends Test
{
    # Debug::class exists
    function testInstance()
    {
        # function d & dd does not exist in global space
        # if you are using -v or -vv these tests will fail, as Debug has already
        # been loaded, so its normal
        $this->assertFalse(function_exists('d'));
        $this->assertFalse(function_exists('dd'));

        $d = new Debug();
        $this->assertInstanceOf(Debug::class, $d);
    }

    # functions d($v) and dd($vv) are defined in global namespace
    function testFunctions_D_and_DD()
    {
        Debug::enable();

        $this->assertTrue(function_exists('d'));
        $this->assertTrue(function_exists('dd'));

        $t = Debug::getOutput();
        $t->ob_start();
        Debug::dump($this);
        $t->ob_start();

        // $this->assertEquals(2, $t->ob_level());
        $o1 = ob_get_clean();
        // $this->assertEquals(1, $t->ob_level());
        $o2 = ob_get_clean();
        // $this->assertEquals(0, $t->ob_level());
        $this->assertEquals($o1, $o2);
        // print_r($t); exit;
    }

    # Debug can catch the exceptions
    function testCatchesException()
    {
        $this->assertException(function () {
            throw new \Exception("Error Processing Request", 1);
        });
    }

    # Debug can catch the errors
    function testCatchesError()
    {
        $this->assertError(function () {
            require "classes/DebugError.php";
        });
    }

    # Debug can return the output device
    function testGetOutput()
    {
        $output = Debug::getOutput();
        $this->assertNotNull($output);
        $this->assertMethod($output, "write");
        $this->assertMethod($output, "writeln");
    }

    # Debug can dump
    function testDump()
    {
        $this->assertMethod(new Debug(), 'dump');

        $t = Debug::getOutput();
        $t->ob_start();
        Debug::dump("Hello World!", false); $l = __LINE__;
        $output = $t->ob_get_clean();

        $this->assertTrue(strpos($output, "Hello World!") !== false);
        $this->assertFalse(strpos($output, "file: "));
        $this->assertFalse(strpos($output, "line: "));
    }

    # Debug can give the trace of dumps
    function testTrace()
    {
        $this->assertMethod(new Debug(), 'trace');

        $t = Debug::getOutput();
        Debug::enable(2);
        $t->ob_start();
        Debug::dump("Hello World!"); $l = __LINE__;
        $output = $t->ob_get_clean();

        $this->assertTrue(strpos($output, "Hello World!") !== false);
        $this->assertNotFalse(strpos($output, "file: " . "tests/DebugTest.php"));
        $this->assertNotFalse(strpos($output, "line: " . $l));
    }

    # todo -- convert files from examples to tests ...
    
}
