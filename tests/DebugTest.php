<?php

use IrfanTOOR\Debug;
use IrfanTOOR\Test;

function getDump($v, $t = false)
{
    ob_start();
    Debug::dump($v, $t);
    $colored_dump = ob_get_clean();

    # clean the coloring codes
    $dump = preg_replace('|\\033\[\d+m|us', '', $colored_dump);
    return mb_substr($dump, 0, strlen($dump) -1); # strip the last \n
}

class DebugTest extends Test
{
    protected $debug;

    function setup()
    {
        $l = $this->options['vverbose']['value'];
        Debug::enable(1);
    }

    function testDebugInstance()
    {
        $this->assertInstanceOf(IrfanTOOR\Debug::class, Debug::getInstance());
    }

    function testUniqueInstance()
    {
        $d1 = Debug::getInstance();
        $d2 = Debug::getInstance();

        $this->assertEquals($d1, $d2);
        $this->assertSame($d1, $d2);
    }

    function testCantCreateNewInstances()
    {
        $this->assertException(
            function(){
                new Debug(1);
            },
            Exception::class,
            "Use Debug::getInstance, cannot create a new instance"
        );
    }

    function testConstants()
    {
        $this->assertEquals("Irfan's Debug", Debug::NAME);
        $this->assertEquals("Debug, dump and trace while development", Debug::DESCRIPTION);

        $version = Debug::VERSION;
        $this->assertString($version);
        $this->assertFalse(strpos($version, 'VERSION'));
        $this->assertInt(strpos($version, '.'));
    }

    function testDefaultLevel()
    {
        $l = $this->options['vverbose']['value'];
        Debug::enable($l);
        $level = Debug::getLevel();
        $this->assertInt($level);
        $this->assertEquals($l, $level);
    }

    function testLevelCanChange()
    {
        $l1 = Debug::getLevel();
        $this->assertInt($l1);
        $this->assertEquals($l1, Debug::getLevel());

        $l2 = $l1 + 1;
        Debug::enable($l2);
        $this->assertEquals($l2, Debug::getLevel());

        Debug::enable($l1);
    }

    function testTerminalDump()
    {
        Debug::enable(0);
        $this->assertEquals("", getDump($_SERVER));

        Debug::enable(1);
        $dump = getDump($_SERVER);
        $this->assertString($dump);
        
        $this->assertEquals('""', getDump(''));
        $this->assertEquals('null', getDump(null));
        $this->assertEquals('true', getDump(true));
        $this->assertEquals('false', getDump(false));
        $this->assertEquals('0', getDump(0));
        $this->assertEquals('hello', getDump('hello'));
    }

    # This should be the final test as afterwards the level is locked
    function testLocked()
    {
        $l1 = $this->options['vverbose']['value'];
        Debug::enable($l1);
        Debug::lock();
        $l2 = $l1 + 1;
        Debug::enable($l2);
        $this->assertEquals($l1, Debug::getLevel());
    }    
}
