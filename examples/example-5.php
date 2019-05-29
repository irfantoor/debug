<?php

require dirname(__DIR__) .'/vendor/autoload.php';

use IrfanTOOR\Debug;

Debug::enable(3);
// Debug::enable(2);

Debug::dump($_SERVER);
echo "Now you see the dump as Debug::enable was called with a dump level 3" . PHP_EOL;
echo "an exception follows, you see the exception" . PHP_EOL;

throw new \Exception("Now you see the the dump and the exception", 1);

echo "this you never see!" . PHP_EOL;
