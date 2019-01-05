<?php

require dirname(__DIR__) .'/vendor/autoload.php';

use IrfanTOOR\Debug;

Debug::enable(1); # only this is effective as it is the first call to enable.
Debug::enable(2);

Debug::dump($_SERVER);
