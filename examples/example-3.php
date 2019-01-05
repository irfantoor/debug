<?php

require dirname(__DIR__) .'/vendor/autoload.php';

use IrfanTOOR\Debug;

// Debug::enable(1);
Debug::enable(2); # now this is effective as it is the first call to enable.

Debug::dump($_SERVER);
