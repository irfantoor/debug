<?php

use IrfanTOOR\Test;

class SkipMethodTest extends Test
{
    function testSkipMethod()
    {
        # will be executed
        $this->assertEquals(1,1);
        
        # make the message to be skipped
        call_unknown();

        # will not be executed
        $this->assertEquals(1,1);
    }

    /**
     * throws: Error::class
     * message: Call to undefined function call_unknown()
     */
    function testDoNotSkipMethod()
    {
        call_unknown();
    }
}
