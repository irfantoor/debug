<?php

require dirname(__DIR__) .'/vendor/autoload.php';

use IrfanTOOR\Debug;

// Debug::enable(1);
// Debug::enable(2);

Debug::dump($_SERVER);
echo "you have not seen the dump as Debug::enable was never called with a dump level" . PHP_EOL;
echo "an exception follows, and nothing will be displayed after exception" . PHP_EOL;

throw new \Exception("Now you see the exception and not the dump", 1);

echo "you do not see this!" . PHP_EOL;
