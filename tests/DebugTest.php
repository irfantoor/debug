<?php

use IrfanTOOR\Debug;
use IrfanTOOR\Debug\Constants;
use IrfanTOOR\Test;

class DebugTest extends Test
{
    protected $debug;

    function setup()
    {
        $this->debug = Debug::getInstance();
    }

    function testDebugInstance()
    {
        $this->assertInstanceOf(IrfanTOOR\Debug::class, $this->debug);
    }

    function testUniqueInstance()
    {
        $db = Debug::getInstance();
        $this->assertEquals($this->debug, $db);
        $this->assertSame($this->debug, $db);
    }

    function testCantCreateNewInstances()
    {
        $this->assertException(
            function(){
                $db = new Debug(1);
            },
            Exception::class,
            "Use Debug::getInstance, cannot create a new instance"
        );
    }

    function testDefaultLevel()
    {
        $l = $this->options['vverbose']['value'];

        $level = $this->debug->getLevel();
        $this->assertInt($level);
        $this->assertEquals($l, $level);
    }

    function testLevelCanChange()
    {
        $l1 = $this->debug->getLevel();
        $this->assertInt($l1);
        $l2 = $l1 + 1;
        $this->debug::enable($l2);
        $this->assertEquals($l2, $this->debug->getLevel());
        $this->debug::enable($l1);
    }

    function testGetVersion()
    {
        $version = $this->debug->getVersion();
        $this->assertString($version);
        $this->assertFalse(strpos($version, 'VERSION'));
        $this->assertEquals(Constants::VERSION, $version);
    }

    function testTerminalDump()
    {
        Debug::enable(1);

        ob_start();
        Debug::dump($_SERVER, 0);
        $colored_dump = ob_get_clean();

        # clean the coloring codes
        $dump = preg_replace('|\\033\[\d+m|us', '', $colored_dump);

        $this->assertString($dump);
        $this->assertEquals(print_r($_SERVER, 1) . "\n", $dump);
    }

    # This should be the final test as afterwards the level is locked
    function testLocked()
    {
        $l1 = $this->debug->getLevel();
        $this->assertInt($l1);
        Debug::lock();
        $l2 = $l1 + 1;
        $this->debug::enable($l2);
        $this->assertEquals($l1, $this->debug->getLevel());
    }
}
