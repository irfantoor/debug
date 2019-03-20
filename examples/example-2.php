<?php

require dirname(__DIR__) .'/vendor/autoload.php';

use IrfanTOOR\Debug;

Debug::enable(1);
Debug::enable(2);

Debug::dump($_SERVER);

throw new \Exception("Now you see the exception and not the dump", 1);
